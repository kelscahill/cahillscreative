<?php

namespace WPForms\Pro\Admin\Entries\Import\FieldPreparer;

/**
 * Interface for field value preparers.
 *
 * Preparers transform raw CSV values into formats suitable for WPForms field format() methods.
 *
 * @since 1.10.1
 */
interface FieldPreparerInterface {

	/**
	 * Prepare the value for the field format method.
	 *
	 * @since 1.10.1
	 *
	 * @param string|array $value     Raw value from CSV.
	 * @param array        $field     Field settings.
	 * @param array        $form_data Form data and settings.
	 *
	 * @return string|array Prepared value.
	 */
	public function prepare( $value, array $field, array $form_data );

	/**
	 * Get the error message for a given status and optional subfield.
	 *
	 * @since 1.10.1
	 *
	 * @param string $status   Error status: 'skipped' or 'fixed'.
	 * @param string $subfield Optional subfield key (e.g. 'first', 'city', 'date'). Empty string when not applicable.
	 *
	 * @return string
	 */
	public function get_error_message( string $status, string $subfield = '' ): string;
}
