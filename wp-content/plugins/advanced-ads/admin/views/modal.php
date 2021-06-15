<?php
/**
 * Advanced Ads - Backend modal
 *
 * @var string $modal_slug    Unique slug that can be addressed by a link or button.
 * @var string $modal_content The modal content. May contain HTML.
 * @var string $modal_title   The modal title.
 * @var string $close_action  Adds another close button that can trigger an action.
 */
$modal_slug    = isset( $modal_slug ) ? $modal_slug : '';
$modal_content = isset( $modal_content ) ? $modal_content : '';
$modal_title   = isset( $modal_title ) ? $modal_title : '';
$close_action  = isset( $close_action ) ? $close_action : '';
?>

<div id="modal-<?php echo esc_attr( $modal_slug ); ?>" class="advads-modal"
	 data-modal-id="<?php echo esc_attr( $modal_slug ); ?>">
	<a href="#close" class="advads-modal-close-background">Close</a>
	<div class="advads-modal-content">
		<div class="advads-modal-header">
			<a href="#close" class="advads-modal-close">&times;</a>
			<h2>
				<?php
				echo esc_html( $modal_title );
				?>
			</h2>
		</div>
		<div class="advads-modal-body">
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- modal content may contain any kind of custom html
			echo $modal_content;
			?>
		</div>
		<div class="advads-modal-footer">
			<div class="tablenav bottom">
				<a href="#close" type="button" title="<?php esc_html_e( 'Close', 'advanced-ads' ); ?>"
				   class="button button-secondary advads-modal-close"><?php esc_html_e( 'Close', 'advanced-ads' ); ?></a>
				<?php if ( $close_action ) : ?>
					<a href="#close" type="button" title="<?php esc_attr_e( 'Close and save', 'advanced-ads' ); ?>"
					   class="button button-primary advads-modal-close-action">
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- close action may contain custom html like button bar, image or span tag e.g.
						echo $close_action;
						?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
