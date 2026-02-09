<?php

namespace WPForms\Pro\SmartTags\SmartTag;

use WPForms\SmartTags\SmartTag\SmartTag;

/**
 * Class EntryType.
 *
 * @since 1.9.9
 */
class EntryType extends SmartTag {

	/**
	 * Get smart tag value.
	 *
	 * @since 1.9.9
	 *
	 * @param array  $form_data Form data.
	 * @param array  $fields    List of fields.
	 * @param string $entry_id  Entry ID.
	 *
	 * @return string
	 */
	public function get_value( $form_data, $fields = [], $entry_id = '' ) {

		if ( empty( $entry_id ) ) {
			return '';
		}

		$entry = wpforms()->obj( 'entry' )->get( $entry_id, [ 'cap' => '' ] );

		if ( ! $entry || ! property_exists( $entry, 'status' ) ) {
			return '';
		}

		return $entry->status ? ucfirst( $entry->status ) : __( 'Completed', 'wpforms' );
	}
}
