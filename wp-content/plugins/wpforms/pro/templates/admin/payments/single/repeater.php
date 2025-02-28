<?php
/**
 * Single Payment page - Payment entry repeater template.
 *
 * @since 1.8.9
 *
 * @var array $field        Field data.
 * @var array $form_data    Form data.
 * @var array $entry_fields Entry fields.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPForms\Pro\Forms\Fields\Repeater\Helpers as RepeaterHelpers;

$blocks = RepeaterHelpers::get_blocks( $field, $form_data );

if ( ! $blocks ) {
	return '';
}

$display = $field['display'] ?? 'blocks';

$args = [
	'field' => $field,
];

if ( $display === 'blocks' ) {
	$args['blocks'] = $blocks;
} else {
	$args['rows'] = $field['columns'];
}

echo wpforms_render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	"admin/payments/single/repeater-{$display}",
	$args,
	true
);
