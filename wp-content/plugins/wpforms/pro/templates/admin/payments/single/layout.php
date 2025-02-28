<?php
/**
 * Single Payment page - Payment entry layout template.
 *
 * @since 1.9.3
 *
 * @var array $field        Field data.
 * @var array $form_data    Form data.
 * @var array $entry_fields Entry fields.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPForms\Pro\Forms\Fields\Layout\Helpers as LayoutHelpers;

$rows = isset( $field['columns'] ) && is_array( $field['columns'] ) ? LayoutHelpers::get_row_data( $field ) : [];

if ( ! $rows ) {
	return '';
}

$display = $field['display'] ?? 'rows';

$args = [
	'field' => $field,
];

if ( $display === 'rows' ) {
	$args['rows'] = $rows;
} else {
	$args['columns'] = $field['columns'];
}

echo wpforms_render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	"admin/payments/single/layout-{$display}",
	$args,
	true
);
