<?php

namespace WPForms\Pro\Emails;

use WPForms\Emails\Templates\Summary;
use WPForms\Pro\Reports\EntriesCount;

/**
 * Re-engagement alert box for the weekly summary email.
 *
 * Renders a blue alert inside the weekly summary for paid users. Two conditional
 * states share the same block:
 *  - Tenure-based variants for sites that have forms but have never collected entries.
 *  - An engagement-drop state for sites whose forms were active and then went quiet.
 *
 * Stateless — the weekly cron cadence and the detection windows themselves ensure each
 * state fires at most once per event (see detect_drop() for the drop suppression logic).
 *
 * @since 1.10.1.1
 */
class ReengagementAlert {

	/**
	 * Hard stop day. No alerts after this tenure.
	 *
	 * @since 1.10.1.1
	 */
	private const HARD_STOP_DAY = 270;

	/**
	 * Cron cadence in days. Used as the trigger-window width.
	 *
	 * @since 1.10.1.1
	 */
	private const WINDOW_DAYS = 7;

	/**
	 * Variant definitions: slug => [ trigger_days, utm_content ].
	 *
	 * @since 1.10.1.1
	 */
	private const VARIANTS = [
		'phase-a-early' => [
			'trigger_days' => [ 30, 60, 90 ],
			'utm_content'  => '0entries-phase-a-early',
		],
		'phase-a-late'  => [
			'trigger_days' => [ 120, 150 ],
			'utm_content'  => '0entries-phase-a-late',
		],
		'phase-b'       => [
			'trigger_days' => [ 180, 210, 240, 270 ],
			'utm_content'  => '0entries-phase-b',
		],
	];

	/**
	 * Minimum entries in a week for a form to count as active for drop detection.
	 *
	 * @since 2.0.0
	 */
	private const DROP_MIN_ENTRIES = 5;

	/**
	 * Consecutive active weeks required before a drop to zero fires the alert.
	 *
	 * @since 2.0.0
	 */
	private const DROP_STREAK_WEEKS = 3;

	/**
	 * Slug and UTM content identifier for the engagement-drop state.
	 *
	 * @since 2.0.0
	 */
	private const DROP_VARIANT_SLUG = 'engagement-drop';

	/**
	 * Initialize the class.
	 *
	 * @since 1.10.1.1
	 */
	public function init(): void {

		$this->hooks();
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.10.1.1
	 */
	private function hooks(): void {

		add_filter( 'wpforms_emails_summaries_template', [ $this, 'maybe_add_alert_args' ] );
		add_filter( 'wpforms_emails_summaries_cron_skip_empty', [ $this, 'maybe_allow_empty_entries' ] );
	}

	/**
	 * Resolve the active variant for the current site, or null when no alert should fire.
	 *
	 * @since 1.10.1.1
	 *
	 * @return string|null Variant slug or null.
	 */
	private function get_active_variant(): ?string {

		$tenure_days = $this->get_tenure_days();

		if ( $tenure_days < 0 ) {
			return null;
		}

		$variant = $this->resolve_variant( $tenure_days );

		if ( $variant === null ) {
			return null;
		}

		if ( ! $this->has_forms() || ! $this->has_zero_entries() ) {
			return null;
		}

		return $variant;
	}

	/**
	 * Allow the weekly summary cron to send when this site qualifies for a re-engagement alert.
	 *
	 * @since 1.10.1.1
	 *
	 * @param bool $skip_empty Whether to skip sending when entries are empty.
	 *
	 * @return bool
	 */
	public function maybe_allow_empty_entries( $skip_empty ): bool {

		$skip_empty = (bool) $skip_empty;

		if ( ! $skip_empty ) {
			return false;
		}

		return $this->get_active_variant() === null;
	}

	/**
	 * Resolve the active variant slug for the given tenure days, or null if none.
	 *
	 * @since 1.10.1.1
	 *
	 * @param int $tenure_days Days since Pro plugin activation.
	 *
	 * @return string|null Variant slug or null when outside all trigger windows.
	 */
	private function resolve_variant( int $tenure_days ): ?string {

		if ( $tenure_days < 30 || $tenure_days >= self::HARD_STOP_DAY + self::WINDOW_DAYS ) {
			return null;
		}

		foreach ( self::VARIANTS as $slug => $config ) {
			foreach ( $config['trigger_days'] as $trigger ) {
				if ( $tenure_days >= $trigger && $tenure_days < $trigger + self::WINDOW_DAYS ) {
					return $slug;
				}
			}
		}

		return null;
	}

	/**
	 * Days elapsed since Pro plugin activation. Returns -1 if activation timestamp is missing.
	 *
	 * @since 1.10.1.1
	 *
	 * @return int
	 */
	private function get_tenure_days(): int {

		$activated        = get_option( 'wpforms_activated', [] );
		$pro_activated_at = isset( $activated['pro'] ) ? (int) $activated['pro'] : 0;

		if ( $pro_activated_at <= 0 ) {
			return -1;
		}

		return (int) floor( ( time() - $pro_activated_at ) / DAY_IN_SECONDS );
	}

	/**
	 * Whether the site has at least one form.
	 *
	 * @since 1.10.1.1
	 *
	 * @return bool
	 */
	private function has_forms(): bool {

		$form_obj = wpforms()->obj( 'form' );

		if ( ! $form_obj ) {
			return false;
		}

		$forms = $form_obj->get(
			'',
			[
				'fields'      => 'ids',
				'numberposts' => 1,
			]
		);

		return ! empty( $forms );
	}

	/**
	 * Whether the site has zero entries across all forms.
	 *
	 * @since 1.10.1.1
	 *
	 * @return bool
	 */
	private function has_zero_entries(): bool {

		$entry_obj = wpforms()->obj( 'entry' );

		if ( ! $entry_obj ) {
			return false;
		}

		return (int) $entry_obj->get_entries( [], true ) === 0;
	}

	/**
	 * Whether any form suddenly went quiet: active for several consecutive weeks
	 * and then zero entries in the week being reported.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	private function has_engagement_drop(): bool {

		return $this->detect_drop( $this->get_weekly_counts_by_form() );
	}

	/**
	 * Per-form entry counts for the reported week and the preceding streak weeks.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	private function get_weekly_counts_by_form(): array {

		// The reported week ends on the previous Sunday, matching Summaries::get_entries().
		$last_sunday = date_create( 'previous sunday' );
		$reports     = new EntriesCount();
		$weekly      = [];

		for ( $week = 0; $week <= self::DROP_STREAK_WEEKS; $week++ ) {
			$week_end = ( clone $last_sunday )->modify( '-' . ( $week * self::WINDOW_DAYS ) . ' days' );
			$counts   = $reports->get_by( 'form', 0, self::WINDOW_DAYS, $week_end->format( 'Y-m-d' ) );

			// get_by( 'form' ) is keyed by form ID; wp_list_pluck keeps those keys (no re-index).
			$weekly[ $week ] = wp_list_pluck( $counts, 'count' );
		}

		return $weekly;
	}

	/**
	 * Detect an engagement drop in the given per-week counts.
	 *
	 * @since 2.0.0
	 *
	 * @param array $weekly Per-form counts keyed by week index (0 = reported week).
	 *
	 * @return bool
	 */
	private function detect_drop( array $weekly ): bool {

		// Need the reported week plus a full streak of preceding weeks to decide.
		if ( count( $weekly ) <= self::DROP_STREAK_WEEKS ) {
			return false;
		}

		$reported_week = $weekly[0] ?? [];

		// A dropped form must have been active in the most recent streak week,
		// so candidates come from that week alone.
		foreach ( $weekly[1] ?? [] as $form_id => $count ) {

			// Skip forms below the threshold last week, or still active this week.
			if ( $count < self::DROP_MIN_ENTRIES || ! empty( $reported_week[ $form_id ] ) ) {
				continue;
			}

			// Confirm the threshold held across the remaining streak weeks.
			$has_active_streak = true;

			for ( $week = 2; $week <= self::DROP_STREAK_WEEKS; $week++ ) {
				if ( ( $weekly[ $week ][ $form_id ] ?? 0 ) < self::DROP_MIN_ENTRIES ) {
					$has_active_streak = false;

					break;
				}
			}

			if ( $has_active_streak ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Build the alert template args for the resolved variant.
	 *
	 * @since 1.10.1.1
	 *
	 * @param string $variant Variant slug.
	 *
	 * @return array Alert args (slug, title, content, button_text, button_url).
	 */
	private function build_args( string $variant ): array {

		$utm_content  = self::VARIANTS[ $variant ]['utm_content'];
		$utm_medium   = 'email';
		$utm_campaign = 'weekly-summary-reengagement';

		// Copy table per issue #17326.
		$copy = [
			'phase-a-early' => [
				'title'       => __( 'Your forms aren\'t collecting entries yet', 'wpforms' ),
				'content'     => __( 'Forms only collect entries when they\'re embedded on a page visitors can see. Check that each form is added to a page (via shortcode or block) and that the page actually gets traffic.', 'wpforms' ),
				'button_text' => __( 'Check Your Forms', 'wpforms' ),
				'admin_path'  => 'admin.php?page=wpforms-overview',
			],
			'phase-a-late'  => [
				'title'       => __( 'Your forms have been quiet for a while', 'wpforms' ),
				'content'     => __( 'After a few months at zero, the most common cause is page placement. Try moving your form to a higher-traffic page: homepage, pricing, or a top blog post.', 'wpforms' ),
				'button_text' => __( 'Check Your Forms', 'wpforms' ),
				'admin_path'  => 'admin.php?page=wpforms-overview',
			],
			'phase-b'       => [
				'title'       => __( 'Still no entries? Try a different form type.', 'wpforms' ),
				'content'     => __( 'When placement isn\'t the issue, matching the form to visitor intent usually unsticks things: quote request for services, newsletter signup for content, or demo booking for software.', 'wpforms' ),
				'button_text' => __( 'Browse Templates', 'wpforms' ),
				'admin_path'  => 'admin.php?page=wpforms-builder',
			],
		][ $variant ];

		$button_url = add_query_arg(
			[
				'utm_source'   => 'wpforms-plugin',
				'utm_medium'   => $utm_medium,
				'utm_campaign' => $utm_campaign,
				'utm_content'  => $utm_content,
			],
			admin_url( $copy['admin_path'] )
		);

		return [
			'slug'        => $variant,
			'title'       => $copy['title'],
			'content'     => $copy['content'],
			'button_text' => $copy['button_text'],
			'button_url'  => $button_url,
		];
	}

	/**
	 * Build the alert template args for the engagement-drop state.
	 *
	 * @since 2.0.0
	 *
	 * @return array Alert args (slug, title, content, content_plain, button_text, button_url).
	 */
	private function build_drop_args(): array {

		$button_url = add_query_arg(
			[
				'utm_source'   => 'wpforms-plugin',
				'utm_medium'   => 'email',
				'utm_campaign' => 'weekly-summary-reengagement',
				'utm_content'  => self::DROP_VARIANT_SLUG,
			],
			admin_url( 'admin.php?page=wpforms-overview' )
		);

		return [
			'slug'          => self::DROP_VARIANT_SLUG,
			'title'         => __( 'Your forms have gone quiet this week.', 'wpforms' ),
			'content'       => __( 'Your submissions dropped compared to recent activity. If this is unexpected, take a look at your form analytics or <a href="mailto:support@wpforms.com">reach out to our support team</a>.', 'wpforms' ),
			'content_plain' => __( 'Your submissions dropped compared to recent activity. If this is unexpected, take a look at your form analytics or reach out to our support team at support@wpforms.com.', 'wpforms' ),
			'button_text'   => __( 'Check Your Forms', 'wpforms' ),
			'button_url'    => $button_url,
		];
	}

	/**
	 * Inject the re-engagement alert args into the summary template when conditions are met.
	 *
	 * @since 1.10.1.1
	 *
	 * @param Summary $template Summary email template.
	 *
	 * @return Summary
	 */
	public function maybe_add_alert_args( $template ) {

		if ( ! $template instanceof Summary ) {
			return $template;
		}

		$alert_args = $this->get_alert_args();

		if ( $alert_args === null ) {
			return $template;
		}

		$template->set_args(
			[
				'body' => [
					'reengagement_alert' => $alert_args,
				],
			]
		);

		return $template;
	}

	/**
	 * Resolve the alert args for whichever conditional state applies, or null when none does.
	 *
	 * @since 2.0.0
	 *
	 * @return array|null Alert args or null.
	 */
	private function get_alert_args(): ?array {

		if ( $this->has_engagement_drop() ) {
			return $this->build_drop_args();
		}

		$variant = $this->get_active_variant();

		return $variant === null ? null : $this->build_args( $variant );
	}
}
