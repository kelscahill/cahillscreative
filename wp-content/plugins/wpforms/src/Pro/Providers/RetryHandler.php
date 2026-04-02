<?php

namespace WPForms\Pro\Providers;

use WPForms\Admin\Notice;
use WPForms_Entries_Single;
use WPForms_Entry_Meta_Handler;

/**
 * Abstract Retry Handler class.
 *
 * @since 1.10.0
 */
abstract class RetryHandler {

	/**
	 * Initialize.
	 *
	 * @since 1.10.0
	 *
	 * @return RetryHandler
	 */
	public function init(): self {

		$this->hooks();

		return $this;
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.10.0
	 */
	private function hooks(): void {

		add_action( 'wpforms_entry_details_init', [ $this, 'maybe_display_retry_notice' ] );
		add_filter( 'wpforms_entry_details_sidebar_actions_link', [ $this, 'add_retry_action' ], 10, 3 );
		add_action( 'wpforms_entry_details_init', [ $this, 'maybe_process_retry' ], -100 );
	}

	/**
	 * Get the retry slug.
	 *
	 * @since 1.10.0
	 *
	 * @return string
	 */
	abstract protected function get_slug(): string;

	/**
	 * Get the retry action name.
	 *
	 * @since 1.10.0
	 *
	 * @return string
	 */
	private function get_action_name(): string {

		return sanitize_key( 'wpforms_' . $this->get_slug() . '_retry_failed' );
	}

	/**
	 * Get the failed meta-key.
	 *
	 * @since 1.10.0
	 *
	 * @return string
	 */
	private function get_failed_meta_key(): string {

		return $this->get_slug() . '_failed';
	}

	/**
	 * Get the retry meta-key.
	 *
	 * @since 1.10.0
	 *
	 * @return string
	 */
	private function get_retry_meta_key(): string {

		return $this->get_slug() . '_failed_retry';
	}

	/**
	 * Get the retry link label.
	 *
	 * @since 1.10.0
	 *
	 * @return string
	 */
	abstract protected function get_label(): string;

	/**
	 * Get the retry link description.
	 *
	 * @since 1.10.0
	 *
	 * @return string
	 */
	protected function get_description(): string {

		return '';
	}

	/**
	 * Check if retry is available for an entry.
	 *
	 * @since 1.10.0
	 *
	 * @param int   $entry_id  Entry ID.
	 * @param array $form_data Form data.
	 *
	 * @return bool
	 */
	final public function is_available( int $entry_id, array $form_data ): bool {

		// phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
		return $this->is_enabled( $form_data ) && $this->has_failed_connections( $entry_id, $form_data );
	}

	/**
	 * Check if the provider is enabled for this form.
	 *
	 * @since 1.10.0
	 *
	 * @param array $form_data Form data.
	 *
	 * @return bool
	 */
	abstract protected function is_enabled( array $form_data ): bool;

	/**
	 * Check if there are failed connections that still exist in the form.
	 *
	 * @since 1.10.0
	 *
	 * @param int   $entry_id  Entry ID.
	 * @param array $form_data Form data.
	 *
	 * @return bool
	 */
	abstract protected function has_failed_connections( int $entry_id, array $form_data ): bool;

	/**
	 * Execute the actual retry logic.
	 *
	 * @since 1.10.0
	 *
	 * @param int $entry_id Entry ID.
	 *
	 * @return int Number of successfully sent items.
	 */
	abstract protected function retry( int $entry_id ): int;

	/**
	 * Add retry action to the entry sidebar.
	 *
	 * @since 1.10.0
	 *
	 * @param array|mixed $actions   Current sidebar actions.
	 * @param object      $entry     Entry object.
	 * @param array       $form_data Form data.
	 *
	 * @return array Modified actions array.
	 */
	public function add_retry_action( $actions, object $entry, array $form_data ): array {

		$actions  = (array) $actions;
		$entry_id = (int) $entry->entry_id;

		if ( ! $this->is_available( $entry_id, $form_data ) ) {
			return $actions;
		}

		$action_data = [
			'url'   => $this->get_retry_url( $entry_id ),
			'label' => $this->get_label(),
			'icon'  => 'dashicons-external',
		];

		$description = $this->get_description();

		if ( ! empty( $description ) ) {
			$action_data['description'] = $description;
		}

		return wpforms_array_insert(
			$actions,
			[
				$this->get_action_name() => $action_data,
			],
			'print',
			'before'
		);
	}

	/**
	 * Generate the retry URL for a specific entry.
	 *
	 * @since 1.10.0
	 *
	 * @param int $entry_id Entry ID.
	 *
	 * @return string
	 */
	private function get_retry_url( int $entry_id ): string {

		return add_query_arg(
			[
				$this->get_action_name() => '1',
				'_wpnonce'               => wp_create_nonce( $this->get_action_name() ),
			],
			$this->get_entry_details_url( $entry_id )
		);
	}

	/**
	 * Generate a URL for viewing entry details.
	 *
	 * @since 1.10.0
	 *
	 * @param int $entry_id Entry ID.
	 *
	 * @return string
	 */
	private function get_entry_details_url( int $entry_id ): string {

		return add_query_arg(
			[
				'page'     => 'wpforms-entries',
				'view'     => 'details',
				'entry_id' => $entry_id,
			],
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Display retry notice after processing.
	 *
	 * @since 1.10.0
	 *
	 * @param WPForms_Entries_Single $entry_single Entry single instance.
	 *
	 * @noinspection JsonEncodingApiUsageInspection
	 */
	public function maybe_display_retry_notice( WPForms_Entries_Single $entry_single ): void {

		$entry_meta_handler = wpforms()->obj( 'entry_meta' );

		if ( ! $entry_meta_handler ) {
			return;
		}

		$existing_record = $entry_meta_handler->get_meta(
			[
				'entry_id' => (int) $entry_single->entry->entry_id,
				'type'     => $this->get_retry_meta_key(),
				'number'   => 1,
			]
		);

		if ( empty( $existing_record ) || empty( $existing_record[0]->data ) ) {
			return;
		}

		$data = (string) $existing_record[0]->data;

		if ( ! wpforms_is_json( $data ) ) {
			return;
		}

		$retry_data = json_decode( $data, true );

		// Delete the temporary meta-record.
		$entry_meta_handler->delete( $existing_record[0]->id );

		if ( empty( $retry_data['count'] ) ) {
			return;
		}

		$this->display_success_notice( (int) $retry_data['count'] );
	}

	/**
	 * Display success notice.
	 *
	 * @since 1.10.0
	 *
	 * @param int $count Number of resent items.
	 */
	protected function display_success_notice( int $count ): void {

		Notice::success(
			sprintf(
				/* translators: %d - number of items. */
				_n( '%d item was resent.', '%d items were resent.', $count, 'wpforms' ),
				$count
			)
		);
	}

	/**
	 * Maybe process the retry request.
	 *
	 * @since 1.10.0
	 *
	 * @param WPForms_Entries_Single $entry_single Entry single instance.
	 *
	 * @noinspection ForgottenDebugOutputInspection
	 */
	public function maybe_process_retry( WPForms_Entries_Single $entry_single ): void {

		$action = $this->get_action_name();

		// Check if this is a retry request.
		if (
			! isset( $_GET[ $action ] ) ||
			empty( sanitize_key( $_GET[ $action ] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		) {
			return;
		}

		// Verify nonce.
		if (
			! isset( $_GET['_wpnonce'] ) ||
			! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), $action )
		) {
			wp_die( esc_html__( 'Security check failed.', 'wpforms' ) );
		}

		$entry_id    = (int) $entry_single->entry->entry_id;
		$retry_count = $this->retry( $entry_id );

		if ( $retry_count > 0 ) {
			$this->save_retry_notice_data( $entry_id, (int) $entry_single->entry->form_id, $retry_count );
		}

		$this->redirect( $entry_id );
	}

	/**
	 * Save retry notice data for display.
	 *
	 * @since 1.10.0
	 *
	 * @param int $entry_id Entry ID.
	 * @param int $form_id  Form ID.
	 * @param int $count    Number of items resent.
	 */
	private function save_retry_notice_data( int $entry_id, int $form_id, int $count ): void {

		$entry_meta_handler = wpforms()->obj( 'entry_meta' );

		if ( ! $entry_meta_handler ) {
			return;
		}

		$entry_meta_handler->add(
			[
				'entry_id' => $entry_id,
				'form_id'  => $form_id,
				'user_id'  => get_current_user_id(),
				'type'     => $this->get_retry_meta_key(),
				'data'     => wp_json_encode( [ 'count' => $count ] ),
			],
			'entry_meta'
		);
	}

	/**
	 * Redirect with cleaned URL.
	 *
	 * @since 1.10.0
	 *
	 * @param int $entry_id Entry ID.
	 */
	private function redirect( int $entry_id ): void {

		wp_safe_redirect( $this->get_entry_details_url( $entry_id ) );
		exit;
	}

	/**
	 * Mark a failed attempt.
	 *
	 * @since 1.10.0
	 *
	 * @param int   $entry_id Entry ID.
	 * @param int   $form_id  Form ID.
	 * @param mixed $data     Data to store (e.g., ID of the failed item).
	 */
	public function mark_failed( int $entry_id, int $form_id, $data ): void {

		$entry_meta_handler = wpforms()->obj( 'entry_meta' );

		if ( ! $entry_meta_handler ) {
			return;
		}

		$meta         = $this->get_failed_meta_record( $entry_meta_handler, $entry_id );
		$failed_items = $this->decode_failed_items( $meta );

		if ( ! in_array( $data, $failed_items, true ) ) {
			$failed_items[] = $data;
		}

		$this->save_failed_items( $entry_meta_handler, $entry_id, $form_id, $meta, $failed_items );
	}

	/**
	 * Mark a successful attempt (remove from a failed list).
	 *
	 * @since 1.10.0
	 *
	 * @param int   $entry_id Entry ID.
	 * @param mixed $data     Data to remove (e.g., ID of the successful item).
	 */
	public function mark_success( int $entry_id, $data ): void {

		$entry_meta_handler = wpforms()->obj( 'entry_meta' );

		if ( ! $entry_meta_handler ) {
			return;
		}

		$meta         = $this->get_failed_meta_record( $entry_meta_handler, $entry_id );
		$failed_items = $this->decode_failed_items( $meta );

		if ( empty( $failed_items ) ) {
			return;
		}

		$failed_items = array_values( array_diff( $failed_items, (array) $data ) );

		$this->save_failed_items( $entry_meta_handler, $entry_id, 0, $meta, $failed_items );
	}

	/**
	 * Get the failed meta-record for an entry.
	 *
	 * @since 1.10.0
	 *
	 * @param WPForms_Entry_Meta_Handler $entry_meta_handler Entry meta object.
	 * @param int                        $entry_id           Entry ID.
	 *
	 * @return array
	 */
	private function get_failed_meta_record( WPForms_Entry_Meta_Handler $entry_meta_handler, int $entry_id ): array {

		return $entry_meta_handler->get_meta(
			[
				'entry_id' => $entry_id,
				'type'     => $this->get_failed_meta_key(),
				'number'   => 1,
			]
		);
	}

	/**
	 * Decode failed items from meta.
	 *
	 * @since 1.10.0
	 *
	 * @param array $meta Meta array.
	 *
	 * @return array
	 * @noinspection JsonEncodingApiUsageInspection
	 */
	private function decode_failed_items( array $meta ): array {

		if ( empty( $meta ) || empty( $meta[0]->data ) ) {
			return [];
		}

		$data = (string) $meta[0]->data;

		if ( ! wpforms_is_json( $data ) ) {
			return [];
		}

		$items = json_decode( $data, true );

		return is_array( $items ) ? $items : [];
	}

	/**
	 * Save failed items list.
	 *
	 * Adds, updates, or deletes entry meta depending on the list state.
	 *
	 * @since 1.10.0
	 *
	 * @param WPForms_Entry_Meta_Handler $entry_meta_handler Entry meta object.
	 * @param int                        $entry_id           Entry ID.
	 * @param int                        $form_id            Form ID (only required for add()).
	 * @param array                      $meta               Existing meta-record array.
	 * @param array                      $failed_items       Failed items list.
	 */
	private function save_failed_items( WPForms_Entry_Meta_Handler $entry_meta_handler, int $entry_id, int $form_id, array $meta, array $failed_items ): void {

		$has_record = ! empty( $meta ) && ! empty( $meta[0]->id );

		// Delete record when a list becomes empty.
		if ( empty( $failed_items ) ) {
			if ( $has_record ) {
				$entry_meta_handler->delete( $meta[0]->id );
			}

			return;
		}

		$data = wp_json_encode( $failed_items );

		// Update existing record.
		if ( $has_record ) {
			$entry_meta_handler->update(
				$meta[0]->id,
				[
					'data' => $data,
				]
			);

			return;
		}

		// Add a new record (only for a mark_failed use case).
		if ( $form_id > 0 ) {
			$entry_meta_handler->add(
				[
					'entry_id' => $entry_id,
					'form_id'  => $form_id,
					'user_id'  => get_current_user_id(),
					'type'     => $this->get_failed_meta_key(),
					'data'     => $data,
				],
				'entry_meta'
			);
		}
	}

	/**
	 * Get failed items.
	 *
	 * @since 1.10.0
	 *
	 * @param int $entry_id Entry ID.
	 *
	 * @return array
	 * @noinspection JsonEncodingApiUsageInspection
	 */
	public function get_failed_items( int $entry_id ): array {

		$entry_meta_handler = wpforms()->obj( 'entry_meta' );

		if ( ! $entry_meta_handler ) {
			return [];
		}

		$meta = $this->get_failed_meta_record( $entry_meta_handler, $entry_id );

		if ( empty( $meta ) || empty( $meta[0]->data ) ) {
			return [];
		}

		$data = (string) $meta[0]->data;

		if ( ! wpforms_is_json( $data ) ) {
			return [];
		}

		$failed_items = json_decode( $data, true );

		return is_array( $failed_items ) ? $failed_items : [];
	}
}
