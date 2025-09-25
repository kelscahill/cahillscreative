<?php
/**
 * Camera modal template.
 *
 * @var int    $field_id          Field ID.
 * @var int    $form_id           Form ID.
 * @var string $camera_format     Camera format.
 * @var string $camera_time_limit Camera time limit in seconds.
 * @var int    $wait_time         Wait time before taking a photo.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="wpforms-camera-modal-<?php echo absint( $form_id ); ?>-<?php echo absint( $field_id ); ?>" class="wpforms-camera-modal-overlay wpforms-camera-format-<?php echo esc_attr( $camera_format ); ?>"  style="display: none;" data-field-id="<?php echo absint( $field_id ); ?>"  data-form-id="<?php echo absint( $form_id ); ?>">
	<div class="wpforms-camera-modal">
		<div class="wpforms-camera-modal-header">
			<div class="wpforms-camera-modal-title"><?php echo esc_html__( 'Camera Preview', 'wpforms' ); ?></div>
			<div class="wpforms-camera-modal-close">
				<svg xmlns="http://www.w3.org/2000/svg" width="12" height="11" fill="none">
					<path d="M10.688 2.219 7.405 5.5l3.282 3.313a.964.964 0 0 1 0 1.406.964.964 0 0 1-1.407 0L6 6.938l-3.313 3.28a.964.964 0 0 1-1.406 0 .964.964 0 0 1 0-1.405L4.563 5.5 1.28 2.219a.964.964 0 0 1 0-1.406.964.964 0 0 1 1.407 0L6 4.092 9.281.814a.964.964 0 0 1 1.406 0 .964.964 0 0 1 0 1.406Z"/>
				</svg>
			</div>
		</div>

		<div class="wpforms-camera-modal-content">
			<div class="wpforms-camera-preview">
				<video id="wpforms-camera-video-<?php echo absint( $form_id ); ?>-<?php echo absint( $field_id ); ?>" autoplay playsinline muted disablepictureinpicture></video>
			</div>
			<div class="wpforms-camera-error"></div>
		</div>

		<?php if ( $camera_format === 'photo' ) : ?>
			<div class="wpforms-camera-modal-footer">
				<div class="wpforms-camera-modal-actions">
					<button class="wpforms-camera-capture" title="<?php echo esc_attr( esc_html__( 'Capture Photo', 'wpforms' ) ); ?>" disabled>
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="22" fill="none">
								<path fill="#fff" d="M6.984 2.047C7.313 1.109 8.156.5 9.141.5h5.718c.985 0 1.829.61 2.157 1.547l.468 1.453H21c1.64 0 3 1.36 3 3v12c0 1.64-1.36 3-3 3H3c-1.64 0-3-1.36-3-3v-12c0-1.64 1.36-3 3-3h3.516l.468-1.453ZM12 8a4.501 4.501 0 0 0 0 9 4.501 4.501 0 0 0 0-9Z"/>
							</svg>
					</button>
					<button class="wpforms-camera-stop" style="display:none;">O</button>
					<div class="wpforms-camera-countdown" style="display: none;">
						<svg>
							<circle class="bg" r="22"></circle>
							<circle class="progress" r="22"></circle>
						</svg>
						<span><?php echo absint( $wait_time ); ?></span>
					</div>
					<div class="wpforms-camera-flip"></div>
				</div>
				<div class="wpforms-camera-modal-buttons" style="display: none;">
					<button class="wpforms-camera-cancel" title="<?php echo esc_attr( esc_html__( 'Retake Photo', 'wpforms' ) ); ?>"></button>
					<button class="wpforms-camera-accept"><?php echo esc_html__( 'Use Photo', 'wpforms' ); ?></button>
					<button class="wpforms-camera-accept-crop" style="display: none;"><?php echo esc_html__( 'Apply Crop', 'wpforms' ); ?></button>
					<button class="wpforms-camera-crop" title="<?php echo esc_attr( esc_html__( 'Crop Photo', 'wpforms' ) ); ?>"></button>
					<button class="wpforms-camera-crop-cancel" title="<?php echo esc_attr( esc_html__( 'Cancel Crop', 'wpforms' ) ); ?>" style="display: none;"></button>
				</div>
		</div>
		<?php else : ?>
			<div class="wpforms-camera-modal-footer">
				<div class="wpforms-camera-modal-actions">
					<div class="wpforms-camera-video-countdown" data-time-limit="<?php echo esc_attr( $camera_time_limit ); ?>"><span>00:00</span> <div><?php echo esc_html__( 'Remaining', 'wpforms' ); ?></div></div>
					<button class="wpforms-camera-capture" title="<?php echo esc_attr( esc_html__( 'Record Video', 'wpforms' ) ); ?>" disabled>
						<svg xmlns="http://www.w3.org/2000/svg" width="26" height="18" fill="none">
							<path fill="#fff" d="M3.5 0h12c1.64 0 3 1.36 3 3v12c0 1.64-1.36 3-3 3h-12c-1.64 0-3-1.36-3-3V3c0-1.64 1.36-3 3-3Zm17.25 12.75v-7.5l3.469-2.766a1.12 1.12 0 0 1 .656-.234c.61 0 1.125.516 1.125 1.125v11.25a1.14 1.14 0 0 1-1.125 1.125 1.12 1.12 0 0 1-.656-.234L20.75 12.75Z"/>
						</svg>
					</button>
					<button class="wpforms-camera-stop" style="display:none;" title="<?php echo esc_attr( esc_html__( 'Stop Recording', 'wpforms' ) ); ?>">O</button>
					<div class="wpforms-camera-countdown" style="display: none;">
						<svg>
							<circle class="bg" r="22"></circle>
							<circle class="progress" r="22"></circle>
						</svg>
						<span><?php echo absint( $wait_time ); ?></span>
					</div>
					<div class="wpforms-camera-flip"></div>
				</div>
				<div class="wpforms-camera-modal-buttons" style="display: none;">
					<button class="wpforms-camera-cancel" title="<?php echo esc_attr( esc_html__( 'Redo Video', 'wpforms' ) ); ?>"></button>
					<button class="wpforms-camera-accept"><?php echo esc_html__( 'Use Video', 'wpforms' ); ?></button>
					<button class="wpforms-camera-cancel-video" title="<?php echo esc_attr( esc_html__( 'Delete Video', 'wpforms' ) ); ?>" style="display: none;"></button>
				</div>
		</div>
		<?php endif; ?>

		<canvas id="wpforms-camera-canvas-<?php echo absint( $form_id ); ?>-<?php echo absint( $field_id ); ?>" style="display:none;"></canvas>
	</div>
</div>
