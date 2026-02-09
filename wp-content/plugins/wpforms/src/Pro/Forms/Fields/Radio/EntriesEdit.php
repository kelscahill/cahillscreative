<?php

namespace WPForms\Pro\Forms\Fields\Radio;

use WPForms\Pro\Forms\Fields\Base\EntriesEdit as EntriesEditBase;
use WPForms\Pro\Forms\Fields\Traits\ChoicesEntriesEdit as EntriesEditTrait;

/**
 * Editing Radio field entries.
 *
 * @since 1.6.5
 */
class EntriesEdit extends EntriesEditBase {

	use EntriesEditTrait {
		EntriesEditTrait::field_display as choices_field_display;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.6.5
	 */
	public function __construct() {

		parent::__construct( 'radio' );
	}

	/**
	 * Find the "Other" choice in a field's choices, if it exists.
	 *
	 * @since 1.9.8.3
	 *
	 * @param array $choices Field choices.
	 *
	 * @return array
	 */
	private function get_other_choice( array $choices ): array {

		// We expect the Other choice to be always the last.
		$keys     = array_keys( $choices );
		$last_key = end( $keys );
		$last     = $choices[ $last_key ] ?? null;

		if ( ! empty( $last['other'] ) ) {
			return [
				'key'   => $last_key,
				'label' => (string) ( $last['label'] ?? '' ),
			];
		}

		return [
			'key'   => null,
			'label' => null,
		];
	}

	/**
	 * Determine which choice key was selected based on saved entry data.
	 *
	 * @since 1.9.8.3
	 *
	 * @param array $field       Field data.
	 * @param array $entry_field Entry field data from the submission.
	 * @param bool  $show_values Whether the field is configured to show values.
	 *
	 * @return int|null Choice key if found, otherwise null.
	 */
	private function find_selected_key( array $field, array $entry_field, bool $show_values ) {

		foreach ( $field['choices'] as $key => $choice ) {
			$needle = $show_values ? ( $entry_field['value_raw'] ?? null ) : ( $entry_field['value'] ?? null );
			$hay    = $show_values ? ( $choice['value'] ?? null ) : ( $choice['label'] ?? null );

			if ( isset( $needle, $hay ) && (string) $needle === (string) $hay ) {
				return $key;
			}
		}

		return null;
	}

	/**
	 * Reset all input defaults and clear selected CSS classes for a field.
	 *
	 * @since 1.9.8.3
	 *
	 * @param array &$field Field data, passed by reference. Will be modified in place.
	 */
	private function reset_defaults( array &$field ) {

		if ( empty( $field['properties']['inputs'] ) || ! is_array( $field['properties']['inputs'] ) ) {
			return;
		}

		foreach ( $field['properties']['inputs'] as $i => $props ) {
			$props['default'] = 0;

			if ( isset( $props['container']['class'] ) ) {
				$props['container']['class'] = array_diff(
					(array) $props['container']['class'],
					[ 'wpforms-selected' ]
				);
			}

			$field['properties']['inputs'][ $i ] = $props;
		}
	}

	/**
	 * Display the field on the Edit Entry page.
	 *
	 * @since 1.9.8.3
	 *
	 * @param array $entry_field Entry field data.
	 * @param array $field       Field data and settings.
	 * @param array $form_data   Form data and settings.
	 */
	public function field_display( $entry_field, $field, $form_data ) {

		// Return early if it's NOT a field with Other choice enabled.
		// Otherwise, there would be issues with selected choices for fields that have dynamic choices.
		if ( empty( $field['choices_other'] ) ) {
			$this->choices_field_display( $entry_field, $field, $form_data );

			return;
		}

		$show_values       = ! empty( $field['show_values'] );
		$selected_is_other = false;

		// Find "Other" choice.
		[ 'key' => $other_key, 'label' => $other_label ] = $this->get_other_choice( $field['choices'] );

		// Find a selected choice.
		$selected_key = $this->find_selected_key( $field, $entry_field, $show_values );

		// Detect if the saved value is the "Other" option.
		if ( $other_key !== null && isset( $entry_field['value_raw'] ) && (string) $entry_field['value_raw'] === (string) $other_label ) {
			$selected_key      = $other_key;
			$selected_is_other = true;
		}

		// Reset defaults.
		$this->reset_defaults( $field );

		// Apply selection.
		if ( $selected_key !== null ) {
			$field['properties']['inputs'][ $selected_key ]['default']              = 1;
			$field['properties']['inputs'][ $selected_key ]['container']['class'][] = 'wpforms-selected';

			// Special handling for the Other option.
			if ( $selected_is_other && $other_key !== null ) {
				$field['choices'][ $other_key ]['value'] = (string) ( $entry_field['value'] ?? '' );

				// Rebuild properties to reflect changed show_values/choice values.
				if ( method_exists( $this->field_object, 'field_properties' ) ) {
					$field['properties'] = $this->field_object->field_properties( $field['properties'], $field, $form_data );
					// Ensure our default stays set after rebuild.
					$field['properties']['inputs'][ $other_key ]['default']              = 1;
					$field['properties']['inputs'][ $other_key ]['container']['class'][] = 'wpforms-selected';
				}
			}
		}

		// Render the final field.
		$this->field_object->field_display( $field, null, $form_data );
	}
}
