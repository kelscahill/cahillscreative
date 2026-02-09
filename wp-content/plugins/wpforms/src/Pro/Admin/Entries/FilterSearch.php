<?php

namespace WPForms\Pro\Admin\Entries;

/**
 * Entries FilterSearch trait.
 *
 * @since 1.6.9
 */
trait FilterSearch {

	/**
	 * Array of filtering arguments.
	 *
	 * @since 1.6.9
	 *
	 * @var array
	 */
	protected $filter = [];

	/**
	 * Watch for filtering requests from a search field.
	 *
	 * @since 1.6.9
	 *
	 * @noinspection ReturnTypeCanBeDeclaredInspection
	 */
	public function process_filter_search() {

		$form_id = $this->get_filtered_form_id();
		$search  = $this->get_search_data();

		if ( empty( $form_id ) || empty( $search ) ) {
			return;
		}

		$field      = $search['field'];
		$comparison = $search['comparison'];
		$term       = $search['term'];

		$args = [
			'select'        => 'entry_ids',
			'form_id'       => $form_id,
			'value'         => $term,
			'value_compare' => $comparison,
		];

		if ( is_numeric( $field ) ) {
			$args['field_id'] = $field;
		} else {
			$args['advanced_search'] = $field !== 'any' ? $field : '';
		}

		$this->filter = array_merge(
			$this->filter,
			$args,
			[
				'is_filtered' => true,
				'select'      => 'all',
			]
		);

		// We shouldn't limit searching by the fields.
		// Limiting the results will be done later in `WPForms_Entry_Handler::get_entries()`.
		$args['number'] = -1;

		$entries = '';

		if ( empty( $args['advanced_search'] ) && $field !== 'any' ) {
			$entries = wpforms()->obj( 'entry_fields' )->get_fields( $args );
		}

		$this->prepare_entry_ids_for_get_entries_args( $entries );

		add_filter( 'wpforms_entry_handler_get_entries_args', [ $this, 'get_filtered_entry_table_args' ] );
	}

	/**
	 * Get search request data.
	 *
	 * @since 1.9.8.6
	 *
	 * @return array
	 */
	public function get_search_data(): array {

		$form_id = $this->get_filtered_form_id();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended

		// Check for the run switch and that all data is present.
		if ( ! $form_id || ! isset( $_REQUEST['search'] ) ) {
			return [];
		}

		// Determine comparison early to decide whether a term is required.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		$raw_comparison = isset( $_REQUEST['search']['comparison'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['search']['comparison'] ) ) : '';
		$requires_term  = ! in_array( $raw_comparison, [ 'empty', 'not_empty' ], true );

		if (
			! isset( $_REQUEST['search']['field'] ) ||
			empty( $_REQUEST['search']['comparison'] ) ||
			( $requires_term && ! isset( $_REQUEST['search']['term'] ) )
		) {
			return [];
		}

		$term = isset( $_REQUEST['search']['term'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['search']['term'] ) ) : '';

		/*
		 * Because empty fields were not migrated to a field table in 1.4.3, we don't have that data
		 * and can't filter those with empty values.
		 * The current workaround - displays all entries (instead of none at all).
		 */
		if (
			$requires_term &&
			wpforms_is_empty_string( $term ) &&
			isset( $_REQUEST['search']['term'] ) &&
			wpforms_is_empty_string( $_REQUEST['search']['term'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		) {
			return [];
		}

		// Prepare the data.
		$field      = sanitize_text_field( wp_unslash( $_REQUEST['search']['field'] ) ); // We must use it as a string for the work field with id equal 0.
		$comparison = in_array( $raw_comparison, [ 'contains', 'contains_not', 'is', 'is_not', 'empty', 'not_empty' ], true ) ? $raw_comparison : 'contains';

		return [
			'field'      => $field,
			'comparison' => $comparison,
			'term'       => $this->prepare_term( $term ),
		];
	}

	/**
	 * Prepare the term for the search.
	 *
	 * Allows replacing currency symbols with their html entities.
	 * This is needed because some currency symbols are stored in the database as html entities.
	 *
	 * @since 1.8.9
	 *
	 * @param string $term Search term.
	 *
	 * @return string
	 */
	protected function prepare_term( string $term ): string {

		$currencies_map = [];

		$currencies = wpforms_get_currencies();

		foreach ( $currencies as $currency ) {
			$currencies_map[ html_entity_decode( $currency['symbol'] ) ] = $currency['symbol'];
		}

		return str_replace( array_keys( $currencies_map ), array_values( $currencies_map ), $term );
	}

	/**
	 * Get the entry IDs based on the entries array and pass it further to the
	 * WPForms_Entry_Handler::get_entries() method via a filter.
	 *
	 * @since 1.6.9
	 *
	 * @param array $entries Entries search by form fields result set.
	 */
	protected function prepare_entry_ids_for_get_entries_args( $entries ) {

		$entry_ids = [];

		if ( is_array( $entries ) ) {
			$entry_ids = wp_list_pluck( $entries, 'entry_id' );
		}

		$entry_ids = array_unique( $entry_ids );

		$this->filter['entry_id'] = $entry_ids;

		// In case of Advanced Search and if some html entered to the search box we need to return nothing.
		if (
			empty( $this->filter['value'] ) &&
			! empty( $_REQUEST['search']['term'] ) && // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			! empty( $this->filter['advanced_search'] )
		) {
			$this->filter['entry_id'] = '0';
		}
	}

	/**
	 * Merge default arguments to entries retrieval with the one we send to filter.
	 *
	 * @since 1.6.9
	 *
	 * @param array $args Arguments.
	 *
	 * @return array Filtered arguments.
	 */
	public function get_filtered_entry_table_args( $args ): array {

		$args = (array) $args;

		if ( empty( $this->filter['is_filtered'] ) ) {
			return $args;
		}

		return array_merge( $args, $this->filter );
	}

	/**
	 * Get filtered form ID.
	 *
	 * @since 1.6.9
	 *
	 * @return int
	 */
	private function get_filtered_form_id(): int {

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_REQUEST['form_id'] ) ) {
			return absint( $_REQUEST['form_id'] );
		}

		return ! empty( $_REQUEST['form'] ) ? absint( $_REQUEST['form'] ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}
}
