<?php
/**
 * Single payment entry repeater blocks template.
 *
 * @since 1.9.3
 *
 * @var array $field  Field data.
 * @var array $blocks Blocks data.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$label_hide = $field['label_hide'] ?? false;

?>

<div class="wpforms-payment-entry-repeater-block">
	<?php foreach ( $blocks as $key => $rows ) : ?>
		<?php if ( ! $label_hide ) : ?>
			<?php $block_number = $key >= 1 ? ' #' . ( $key + 1 ) : ''; ?>

			<p class="wpforms-payment-entry-field-name">
				<?php echo esc_html( $field['label'] . $block_number ); ?>
			</p>
		<?php endif; ?>

		<?php
			foreach ( $rows as $row_data ) {
				echo wpforms_render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'admin/payments/single/row-items',
					[
						'items' => $row_data,
						'type'  => $field['type'],
					],
					true
				);
			}
		?>
	<?php endforeach; ?>
</div>
