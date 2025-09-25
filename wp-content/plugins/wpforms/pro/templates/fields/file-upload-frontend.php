<?php
/**
 * Modern file upload template.
 *
 * @var int        $field_id          Field ID.
 * @var int        $form_id           Form ID.
 * @var string     $value             Field value.
 * @var string     $input_name        Field name.
 * @var string     $extensions        Allowed extensions.
 * @var int        $max_size          Max file size.
 * @var int|string $max_file_number   Max file number.
 * @var int        $post_max_size     Max size for POST request.
 * @var int        $chunk_size        Chunk size.
 * @var string     $preview_hint      Preview hint.
 * @var string     $required          Is the field required?
 * @var bool       $is_full           Does the field have maximum uploaded files?
 * @var array      $classes           Field classes.
 * @var bool       $camera_enabled    Is a camera enabled for this field?
 * @var string     $camera_format     Camera format.
 * @var string     $camera_time_limit Camera time limit in seconds.
 * @var int        $wait_time         Wait time.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div
		class="wpforms-uploader <?php echo esc_attr( implode( ' ', $classes ) ); ?>"
		data-field-id="<?php echo absint( $field_id ); ?>"
		data-form-id="<?php echo absint( $form_id ); ?>"
		data-input-name="<?php echo esc_attr( $input_name ); ?>"
		data-extensions="<?php echo esc_attr( $extensions ); ?>"
		data-max-size="<?php echo absint( $max_size ); ?>"
		data-max-file-number="<?php echo absint( $max_file_number ); ?>"
		data-post-max-size="<?php echo absint( $post_max_size ); ?>"
		data-max-parallel-uploads="4"
		data-parallel-uploads="true"
		data-file-chunk-size="<?php echo absint( $chunk_size ); ?>">
	<div class="dz-message<?php echo $is_full ? ' hide' : ''; ?>">
		<svg  viewBox="0 0 640 640" focusable="false" data-icon="inbox" width="50px" height="50px" fill="currentColor" aria-hidden="true">
			<path d="M352 173.3L352 384C352 401.7 337.7 416 320 416C302.3 416 288 401.7 288 384L288 173.3L246.6 214.7C234.1 227.2 213.8 227.2 201.3 214.7C188.8 202.2 188.8 181.9 201.3 169.4L297.3 73.4C309.8 60.9 330.1 60.9 342.6 73.4L438.6 169.4C451.1 181.9 451.1 202.2 438.6 214.7C426.1 227.2 405.8 227.2 393.3 214.7L352 173.3zM320 464C364.2 464 400 428.2 400 384L480 384C515.3 384 544 412.7 544 448L544 480C544 515.3 515.3 544 480 544L160 544C124.7 544 96 515.3 96 480L96 448C96 412.7 124.7 384 160 384L240 384C240 428.2 275.8 464 320 464zM464 488C477.3 488 488 477.3 488 464C488 450.7 477.3 440 464 440C450.7 440 440 450.7 440 464C440 477.3 450.7 488 464 488z"/>
		</svg>

		<span class="modern-title">
			<?php if ( $camera_enabled ) : ?>
				<?php echo esc_html__( 'Drag & Drop Files, ', 'wpforms' ); ?>
				<span><?php echo esc_html__( 'Choose Files to Upload', 'wpforms' ); ?></span><?php echo esc_html__( ', or', 'wpforms' ); ?>
				<span class="wpforms-camera"><?php echo esc_html__( 'Capture With Your Camera', 'wpforms' ); ?></span>
			<?php else : ?>
				<?php echo esc_html__( 'Drag & Drop Files, ', 'wpforms' ); ?>
				<span><?php echo esc_html__( 'Choose Files to Upload', 'wpforms' ); ?></span>
			<?php endif; ?>
		</span>

		<?php if ( (int) $max_file_number > 1 ) : ?>
			<span class="modern-hint"><?php echo esc_html( $preview_hint ); ?></span>
		<?php endif; ?>
	</div>
</div>

<?php
if ( $camera_enabled ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo wpforms_render(
		'fields/camera-modal',
		[
			'field_id'          => $field_id,
			'form_id'           => $form_id,
			'camera_format'     => $camera_format,
			'camera_time_limit' => $camera_time_limit,
			'wait_time'         => $wait_time,
		],
		true
	);
}
?>
<input
		type="text"
		autocomplete="off"
		readonly
		class="dropzone-input"
		style="position:absolute!important;clip:rect(0,0,0,0)!important;height:1px!important;width:1px!important;border:0!important;overflow:hidden!important;padding:0!important;margin:0!important;"
		id="wpforms-<?php echo absint( $form_id ); ?>-field_<?php echo absint( $field_id ); ?>"
		name="<?php echo esc_attr( $input_name ); ?>" <?php echo esc_attr( $required ); ?>
		value="<?php echo esc_attr( $value ); ?>">
