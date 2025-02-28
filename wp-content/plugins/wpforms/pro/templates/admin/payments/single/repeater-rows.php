<?php
/**
 * Single payment entry repeater rows template.
 *
 * @since 1.9.3
 *
 * @var array $field Field data.
 * @var array $rows  Rows data.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$hide_label = $field['hide_label'] ?? false;

?>

<div class="wpforms-payment-entry-repeater-block">

	<?php if ( ! $hide_label ) : ?>
		<p class="wpforms-payment-entry-field-name">
			<?php echo esc_html( $field['label'] ); ?>
		</p>
	<?php endif; ?>

	<?php
		echo wpforms_render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'admin/payments/single/row-items',
			[
				'items' => $rows,
				'type'  => $field['type'],
			],
			true
		);
	?>

</div>
