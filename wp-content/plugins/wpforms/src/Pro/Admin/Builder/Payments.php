<?php

namespace WPForms\Pro\Admin\Builder;

/**
 * Handles payment-related functionality within the application.
 *
 * @since 1.9.6
 */
class Payments {

	/**
	 * Initializes the necessary hooks for the application.
	 *
	 * @since 1.9.6
	 */
	public function init(): void {

		$this->hooks();
	}

	/**
	 * Registers the required hooks for the functionality.
	 *
	 * @since 1.9.6
	 */
	private function hooks(): void {

		add_action( 'wpforms_builder_enqueues', [ $this, 'enqueues' ] );
		add_filter( 'wpforms_builder_strings', [ $this, 'add_localized_strings' ], 10, 2 );
	}


	/**
	 * Enqueues the necessary scripts for payment functionalities in the builder view.
	 *
	 * @since 1.9.6
	 *
	 * @param string|null $view The current view being processed.
	 */
	public function enqueues( $view ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

		$min = wpforms_get_min_suffix();

		wp_enqueue_script(
			'wpforms-builder-payments',
			WPFORMS_PLUGIN_URL . "assets/pro/js/admin/builder/payments{$min}.js",
			[ 'wpforms-builder-conditionals' ],
			WPFORMS_VERSION,
			true
		);
	}

	/**
	 * Add localized strings.
	 *
	 * @since 1.9.6
	 *
	 * @param array  $strings List of builder strings.
	 * @param object $form    CPT of the form.
	 *
	 * @return array
	 */
	public function add_localized_strings( $strings, $form ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		$disabled_message = sprintf(
			wp_kses( /* translators: %s - payment provider. */
				__( "<p>One of %s's recurring payment plans doesn't have conditional logic, which means that One-Time Payments will never work and were disabled.</p>", 'wpforms' ), // phpcs:ignore WordPress.WP.I18n.NoHtmlWrappedStrings
				[
					'p' => [],
				]
			),
			'{provider}'
		);

		$disabled_message .= sprintf(
			wp_kses( /* translators: %s - payment provider. */
				__( '<p>You should check your settings in <strong>Payments » %s</strong>.</p>', 'wpforms' ), // phpcs:ignore WordPress.WP.I18n.NoHtmlWrappedStrings
				[
					'p'      => [],
					'strong' => [],
				]
			),
			'{provider}'
		);

		return array_merge(
			(array) $strings,
			[
				'payment_one_time_payments_disabled' => $disabled_message,
				'payment_plan_prompt'                => esc_html__( 'Enter a plan name', 'wpforms' ),
				'payment_plan_prompt_placeholder'    => esc_html__( 'Eg: Monthly Subscription', 'wpforms' ),
				'payment_plan_placeholder'           => esc_html__( 'Plan Name #{id}', 'wpforms' ),
				'payment_plan_confirm'               => esc_html__( 'Are you sure you want to delete this recurring plan?', 'wpforms' ),
				'payment_error_name'                 => esc_html__( 'You must provide a plan name.', 'wpforms' ),
			]
		);
	}
}
