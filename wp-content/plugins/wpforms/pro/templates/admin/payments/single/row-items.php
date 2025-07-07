<?php
/**
 * Single payment entry layout or repeater rows items template.
 *
 * @since 1.9.3
 *
 * @var array $items Items data.
 * @var string $type Field type.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wpforms-payment-entry-<?php echo esc_attr( $type ); ?>-row">
	<?php foreach ( $items as $data ) : ?>
		<?php $width = wpforms_get_column_width( $data ); ?>
		<div class="wpforms-payment-entry-column" style="--field-layout-column-width: <?php echo esc_attr( $width ); ?>%">
			<?php
				if ( isset( $data['field'] ) && $data['field'] ) {
					echo wpforms_render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						'admin/payments/single/field',
						[
							'field' => $data['field'],
						],
						true
					);
				}

				if ( isset( $data['fields'] ) ) {
					foreach ( $data['fields'] as $field_data ) {
						echo wpforms_render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							'admin/payments/single/field',
							[
								'field' => $field_data,
							],
							true
						);
					}
				}
			?>
		</div>
	<?php endforeach; ?>
</div>
