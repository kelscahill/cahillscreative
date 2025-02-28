<?php
/**
 * Single Entry block header template for layout and repeater fields.
 *
 * @since 1.9.1
 *
 * @var array                  $field          Field data.
 * @var array                  $form_data      Form data and settings.
 * @var WPForms_Entries_Single $entries_single Single entry object.
 * @var int                    $block_number   Block number.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$field_description = $form_data['fields'][ $field['id'] ]['description'] ?? '';
$description_hide  = $entries_single->entry_view_settings['fields']['show_field_descriptions']['value'] === 1 ? '' : ' wpforms-hide';
$hide_name         = empty( $field['label_hide'] ) || ( $field_description && ! $description_hide ) ? '' : ' wpforms-hide';

?>
<p class="wpforms-entry-field-name <?php echo esc_attr( $hide_name ); ?>">
	<?php if ( empty( $field['label_hide'] ) ) { ?>
		<span class="wpforms-entry-field-name-wrapper">
			<?php echo esc_html( $field['label'] ); ?>
			<?php if ( isset( $block_number ) ) : ?>
				<span class="wpforms-entry-field-block-number">
					<?php echo esc_html( $block_number ); ?>
				</span>
			<?php endif; ?>
		</span>
	<?php } ?>

	<?php if ( $field_description ) { ?>
		<span class="wpforms-entry-field-description<?php echo esc_attr( $description_hide ); ?>">
			<?php echo wp_kses_post( $field_description ); ?>
		</span>
	<?php } ?>
</p>
