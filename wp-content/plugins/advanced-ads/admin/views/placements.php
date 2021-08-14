<?php
defined( 'ABSPATH' ) || exit;
/**
 * The view for the placements page
 *
 * @var array $placement_types placement types.
 */

$quick_actions           = array();
$quick_actions['delete'] = '<a style="cursor: pointer;" class="advads-delete-tag">' . __( 'Delete', 'advanced-ads' ) . '</a>';

?>
<div class="wrap">

	<?php
	if ( isset( $_GET['message'] ) ) :
		if ( $_GET['message'] === 'error' ) :
			?>
			<div id="message" class="error"><p><?php esc_html_e( 'Couldn’t create the new placement. Please check your form field and whether the name is already in use.', 'advanced-ads' ); ?></p></div>
		<?php elseif ( $_GET['message'] === 'updated' ) : ?>
			<div id="message" class="updated"><p><?php esc_html_e( 'Placements updated', 'advanced-ads' ); ?></p></div>
		<?php endif; ?>
	<?php endif; ?>
	<h1 class="wp-heading-inline"><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<a href="#" class="page-title-action" title="<?php esc_html_e( 'Create a new placement', 'advanced-ads' ); ?>" class="button-secondary" onclick="advads_toggle('.advads-placements-new-form'); advads_scroll_to_element('#advads-placements-new-form');">
		<?php esc_html_e( 'New Placement', 'advanced-ads' ); ?>
	</a>

	<hr class="wp-header-end">

	<p class="description"><?php esc_html_e( 'Placements are physically places in your theme and posts. You can use them if you plan to change ads and ad groups on the same place without the need to change your templates.', 'advanced-ads' ); ?></p>
	<p class="description">
		<?php
		printf(
			wp_kses(
			// Translators: %s is a URL.
				__( 'See also the manual for more information on <a href="%s">placements</a>.', 'advanced-ads' ),
				array(
					'a' => array(
						'href' => array(),
					),
				)
			),
			esc_url( ADVADS_URL ) . 'manual/placements/#utm_source=advanced-ads&utm_medium=link&utm_campaign=placements'
		);
		?>
	</p>
	<?php

	// Add placement form.
	require_once ADVADS_BASE_PATH . 'admin/views/placement-form.php';

	if ( isset( $placements ) && is_array( $placements ) && count( $placements ) ) :
		$existing_types = array_unique( array_column( $placements, 'type' ) );
		do_action( 'advanced-ads-placements-list-before', $placements );
		?>
		<h2><?php esc_html_e( 'Placements', 'advanced-ads' ); ?></h2>
		<form method="POST" action="" id="advanced-ads-placements-form">

			<?php
			$columns = array(
				array(
					'key'          => 'type_name',
					'display_name' => esc_html__( 'Type', 'advanced-ads' ) . ' / ' . esc_html__( 'Name', 'advanced-ads' ),
					'custom_sort'  => true,
				),
				array(
					'key'          => 'options',
					'display_name' => esc_html__( 'Output', 'advanced-ads' ),
				),
				array(
					'key'          => 'conditons',
					'display_name' => esc_html__( 'Delivery', 'advanced-ads' ),
				),
			);
			?>

			<?php if ( isset( $placement_types ) && ! empty( $placement_types ) ) : ?>
				<div class="tablenav top">
					<select class="advads_filter_placement_type">
						<option value="0"><?php esc_html_e( '- show all types -', 'advanced_ads' ); ?></option>
						<?php foreach ( $placement_types as $type_name => $placement_type ) : ?>
							<?php if ( in_array( $type_name, $existing_types, true ) ) : ?>
								<option value="<?php echo esc_attr( $type_name ); ?>"><?php echo esc_html( $placement_type['title'] ); ?></option>
							<?php endif; ?>
						<?php endforeach; ?>
					</select>
					<input type="text" class="advads_search_placement_name" placeholder="<?php esc_html_e( 'filter by name', 'advanced_ads' ); ?>"/>
				</div>
			<?php endif; ?>

		<table class="wp-list-table advads-placements-table widefat striped posts">
				<thead>
				<tr>
					<?php
					foreach ( $columns as $column ) {
						$class               = '';
						$column_display_name = $column['display_name'];

						if ( $column['key'] === 'type_name' ) :
							list ( $order_type, $name ) = explode( '/', $column_display_name );

							printf( '<th class="column-primary"><a href="#" class="advads-sort ' . ( $orderby === 'type' ? 'advads-placement-sorted' : '' ) . '"
			data-order="type" data-dir="asc">%1$s %2$s</a> / <a href="#" class="advads-sort ' . ( $orderby === 'name' ? 'advads-placement-sorted' : '' ) . '"
			data-order="name" data-dir="asc" style="margin-left:9px;">%3$s %2$s<a/></th>', esc_html( $order_type ), '<span class="advads-placement-sorting-indicator"></span>', esc_html( $name ) );
						else :
							echo '<th>' . esc_html( $column_display_name ) . '</th>';
						endif;

						if ( false && ! empty( $column['custom_sort'] ) ) :
							$column_display_name = '<a href="#" class="advads-sort"
			data-order="name" data-dir="asc">
				' . $column_display_name . '
			<span class="advads-placement-sorting-indicator"></span></a>';
						endif;
					}

					do_action( 'advanced-ads-placements-list-column-header' ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
					?>
				</tr>
				</thead>
				<tbody>
				<?php
				// Sort placements.
				$placements = Advanced_Ads_Placements::sort( $placements, $orderby );
				$display_conditions = Advanced_Ads_Display_Conditions::get_instance();
				$visitor_conditions = Advanced_Ads_Visitor_Conditions::get_instance();

				foreach ( $placements as $_placement_slug => $_placement ) :
					$type_missing = false;
					if ( isset( $_placement['type'] ) && ! isset( $placement_types[ $_placement['type'] ] ) ) {
						$missed_type        = $_placement['type'];
						$_placement['type'] = 'default';
						$type_missing       = true;
					}
					if ( ! isset( $_placement['type'] ) || $_placement['type'] === 'default' ) {
						$_placement['type'] = 'default';
						$quick_actions['usage'] = '<a href="#modal-' . esc_attr( $_placement_slug ) . '-usage" class="usage-modal-link">' . esc_html__( 'show usage', 'advanced-ads' ) . '</a>';
					}

					ob_start();

					do_action( 'advanced-ads-placement-options-before-advanced', $_placement_slug, $_placement );

					if ( $_placement['type'] !== 'header' ) :
						$type_options = isset( $placement_types[ $_placement['type'] ]['options'] ) ? $placement_types[ $_placement['type'] ]['options'] : array();

						if ( ! isset( $type_options['placement-ad-label'] ) || $type_options['placement-ad-label'] ) {
							$_label    = isset( $_placement['options']['ad_label'] ) ? $_placement['options']['ad_label'] : 'default';
							$_position = ! empty( $_placement['options']['placement_position'] ) ? $_placement['options']['placement_position'] : 'default';
							$_clearfix = ! empty( $_placement['options']['placement_clearfix'] );

							ob_start();
							include ADVADS_BASE_PATH . 'admin/views/placements-ad-label.php';
							if ( ! empty( $placement_types[ $_placement['type'] ]['options']['show_position'] ) ) :
								include ADVADS_BASE_PATH . 'admin/views/placements-ad-label-position.php';
							endif;
							$option_content = ob_get_clean();

							Advanced_Ads_Admin_Options::render_option(
								'placement-ad-label',
								__( 'ad label', 'advanced-ads' ),
								$option_content
							);
						}

						// Renders inline css option.
						ob_start();
						include ADVADS_BASE_PATH . 'admin/views/placements-inline-css.php';
						$option_content = ob_get_clean();

						Advanced_Ads_Admin_Options::render_option(
							'placement-inline-css',
							__( 'Inline CSS', 'advanced-ads' ),
							$option_content
						);

						// Show Pro features if Pro is not actiavated.
						if ( ! defined( 'AAP_VERSION' ) ) {
							// Display Conditions for placements.
							Advanced_Ads_Admin_Options::render_option(
								'placement-display-conditions',
								__( 'Display Conditions', 'advanced-ads' ),
								'is_pro_pitch',
								__( 'Use display conditions for placements.', 'advanced-ads' ) .
								' ' . __( 'The free version provides conditions on the ad edit page.', 'advanced-ads' )
							);

							// Visitor Condition for placements.
							Advanced_Ads_Admin_Options::render_option(
								'placement-visitor-conditions',
								__( 'Visitor Conditions', 'advanced-ads' ),
								'is_pro_pitch',
								__( 'Use visitor conditions for placements.', 'advanced-ads' ) .
								' ' . __( 'The free version provides conditions on the ad edit page.', 'advanced-ads' )
							);

							// Minimum Content Length.
							Advanced_Ads_Admin_Options::render_option(
								'placement-content-minimum-length',
								__( 'Minimum Content Length', 'advanced-ads' ),
								'is_pro_pitch',
								__( 'Minimum length of content before automatically injected ads are allowed in them.', 'advanced-ads' )
							);

							// Words Between Ads.
							Advanced_Ads_Admin_Options::render_option(
								'placement-skip-paragraph',
								__( 'Words Between Ads', 'advanced-ads' ),
								'is_pro_pitch',
								__( 'A minimum amount of words between automatically injected ads.', 'advanced-ads' )
							);
						}
					endif;
					do_action( 'advanced-ads-placement-options-after-advanced', $_placement_slug, $_placement );
					$advanced_options = ob_get_clean();
					?>

					<tr id="single-placement-<?php echo esc_attr( $_placement_slug ); ?>"
						class="advanced-ads-placement-row"
						data-type="<?php echo esc_attr( $_placement['type'] ); ?>"
						data-typename="<?php echo esc_html( $placement_types[ $_placement['type'] ]['title'] ); ?>"
						data-name="<?php echo esc_html( $_placement['name'] ); ?>">
						<td class="column-primary">
							<?php if ( $advanced_options ) : ?>
								<?php
								$modal_slug    = esc_attr( $_placement_slug );
								$modal_content = $advanced_options;
								$modal_title   = esc_html__( 'Options', 'advanced-ads' );
								$close_action  = esc_html__( 'Close and save', 'advanced-ads' );
								include ADVADS_BASE_PATH . 'admin/views/modal.php';
								?>

							<?php endif; ?>
							<?php if ( $type_missing ) :  // Type is not given. ?>
								<p class="advads-error-message">
									<?php
									printf(
										wp_kses(
										// Translators: %s is the name of a placement.
											__( 'Placement type "%s" is missing and was reset to "default".<br/>Please check if the responsible add-on is activated.', 'advanced-ads' ),
											array(
												'br' => array(),
											)
										),
										esc_html( $missed_type )
									);
									?>
								</p>
							<?php elseif ( isset( $_placement['type'] ) ) : ?>
							<div class="advads-placement-type">
								<?php if ( isset( $placement_types[ $_placement['type'] ]['image'] ) ) : ?>
									<img src="<?php echo esc_url( $placement_types[ $_placement['type'] ]['image'] ); ?>"
										 alt="<?php echo esc_attr( $placement_types[ $_placement['type'] ]['title'] ); ?>"/>
									<p class="advads-placement-description">
										<strong><?php echo esc_html__( 'Type', 'advanced-ads' ) . ': ' . esc_html( $placement_types[ $_placement['type'] ]['title'] ); ?></strong>
									</p>
									<div class="advads-placement-name">
										<a href="#modal-<?php echo esc_attr( $_placement_slug ); ?>" style="cursor: pointer;"
										   class="row-title"
										   data-placement="<?php echo esc_attr( $_placement_slug ); ?>"><?php echo esc_html( $_placement['name'] ); ?></a><br/>
									</div>
								<?php else : ?>
									<?php echo esc_html( $placement_types[ $_placement['type'] ]['title'] ); ?>
								<?php endif; ?>
							</div>
							<?php else : ?>
								<?php __( 'default', 'advanced-ads' ); ?>
							<?php endif; ?>
							<div class="row-actions">
								<span class="edit">
								<a href="#modal-<?php echo esc_attr( $_placement_slug ); ?>" style="cursor: pointer;"
								   class=""
								   data-placement="<?php echo esc_attr( $_placement_slug ); ?>"><?php esc_html_e( 'Edit', 'advanced-ads' ); ?></a> |
								</span>
								<?php $last_key = array_search( end( $quick_actions ), $quick_actions, true ); ?>
								<?php foreach ( $quick_actions as $quick_action => $action_link ) : ?>
									<span class='<?php echo esc_attr( $quick_action ); ?> '>
									<?php
									echo wp_kses(
										$action_link,
										array(
											'a' => array(
												'class' => array(),
												'href'  => array(),
												'style' => 'cursor: pointer',
											),
										)
									);
									?>
								</span>
									<?php if ( $quick_action !== $last_key ) : ?>
										<span class="separator"> | </span>
								<?php endif; ?>
								<?php endforeach; ?>
							</div>
							<button type="button" class="toggle-row"><span class="screen-reader-text">Mehr Details anzeigen</span></button>
							<input type="hidden" class="advads-placement-slug" value="<?php echo esc_attr( $_placement_slug ); ?>"/>
							<?php if ( ! isset( $_placement['type'] ) || $_placement['type'] === 'default' ) : ?>
								<div id="modal-<?php echo esc_attr( $_placement_slug ); ?>-usage" class="advads-modal">
									<a href="#close" class="advads-modal-close-background">Close</a>
									<div class="advads-modal-content">
										<div class="advads-modal-header">
											<a href="#close" class="advads-modal-close">&times;</a>
											<h2><?php esc_html_e( 'Usage', 'advanced-ads' ); ?></h2>
										</div>
										<div class="advads-modal-body">
											<div class="advads-usage">
												<h2><?php esc_html_e( 'shortcode', 'advanced-ads' ); ?></h2>
												<code><input type="text" onclick="this.select();"
															 value='[the_ad_placement id="<?php echo esc_attr( $_placement_slug ); ?>"]'/></code>
												<h2><?php esc_html_e( 'template (PHP)', 'advanced-ads' ); ?></h2>
												<code><input type="text" onclick="this.select();"
															 value="if( function_exists('the_ad_placement') ) { the_ad_placement('<?php echo esc_attr( $_placement_slug ); ?>'); }"/></code>
											</div>
										</div>
										<div class="advads-modal-footer">
											<div class="tablenav bottom">
												<a href="#close" type="button" title="<?php esc_html_e( 'Close', 'advanced-ads' ); ?>"
												   class="button button-secondary advads-modal-close"><?php esc_html_e( 'Close', 'advanced-ads' ); ?></a>
											</div>
										</div>
									</div>
								</div>
							<?php endif; ?>
						</td>
						<td class="advads-placements-table-options">
							<?php do_action( 'advanced-ads-placement-options-before', $_placement_slug, $_placement ); ?>

							<?php
							ob_start();

							// Get the currently selected item.
							$placement_item_array = explode( '_', $_placement['item'] );
							$placement_item_type  = is_array( $placement_item_array ) && isset( $placement_item_array[0] ) ? $placement_item_array[0] : null;
							$placement_item_id    = is_array( $placement_item_array ) && isset( $placement_item_array[1] ) ? $placement_item_array[1] : null;

							include ADVADS_BASE_PATH . 'admin/views/placements-item.php';
							$item_option_content = ob_get_clean();

							Advanced_Ads_Admin_Options::render_option(
								'placement-item',
								__( 'Item', 'advanced-ads' ),
								$item_option_content
							);
							switch ( $_placement['type'] ) :
								case 'post_content':
									$option_index = isset( $_placement['options']['index'] ) ? absint( max( 1, (int) $_placement['options']['index'] ) ) : 1;
									$option_tag = isset( $_placement['options']['tag'] ) ? $_placement['options']['tag'] : 'p';

									// Automatically select the 'custom' option.
									if ( ! empty( $_COOKIE['advads_frontend_picker'] ) ) {
										$option_tag = ( $_COOKIE['advads_frontend_picker'] === $_placement_slug ) ? 'custom' : $option_tag;
									}

									$option_xpath = isset( $_placement['options']['xpath'] ) ? stripslashes( $_placement['options']['xpath'] ) : '';
									$positions    = array(
										'after'  => __( 'after', 'advanced-ads' ),
										'before' => __( 'before', 'advanced-ads' ),
									);
									ob_start();
									include ADVADS_BASE_PATH . 'admin/views/placements-content-index.php';
									if ( ! defined( 'AAP_VERSION' ) ) {
										include ADVADS_BASE_PATH . 'admin/views/upgrades/repeat-the-position.php';
									}

									do_action( 'advanced-ads-placement-post-content-position', $_placement_slug, $_placement );
									$option_content = ob_get_clean();

									Advanced_Ads_Admin_Options::render_option(
										'placement-content-injection-index',
										__( 'position', 'advanced-ads' ),
										$option_content
									);

									if ( ! extension_loaded( 'dom' ) ) :
										?>
										<p><span class="advads-error-message"><?php esc_html_e( 'Important Notice', 'advanced-ads' ); ?>: </span>
											<?php
											printf(
											// Translators: %s is a name of a module.
												esc_html__( 'Missing PHP extensions could cause issues. Please ask your hosting provider to enable them: %s', 'advanced-ads' ),
												'dom (php_xml)'
											);
											?>
										</p>
									<?php endif; ?>
									<?php
									break;
							endswitch;
							do_action( 'advanced-ads-placement-options-after', $_placement_slug, $_placement );

							// Information after options.
							if ( isset( $_placement['type'] ) && 'header' === $_placement['type'] ) :
								?>
								<br/><p>
								<?php
								printf(
									wp_kses(
									// Translators: %s is a URL.
										__( 'Tutorial: <a href="%s" target="_blank">How to place visible ads in the header of your website</a>.', 'advanced-ads' ),
										array(
											'a' => array(
												'href'   => array(),
												'target' => array(),
											),
										)
									),
									esc_url( ADVADS_URL ) . 'place-ads-in-website-header/#utm_source=advanced-ads&utm_medium=link&utm_campaign=header-ad-tutorial'
								);
								?>
							</p>
							<?php endif; ?>
							<?php if ( $advanced_options ) : ?>
								<div class="advads-placements-show-options">
									<a href="#modal-<?php echo esc_attr( $_placement_slug ); ?>" style="cursor: pointer;"
									   data-placement="<?php echo esc_attr( $_placement_slug ); ?>"><?php esc_html_e( 'show all options', 'advanced-ads' ); ?></a>
								</div>
							<?php endif; ?>
						</td>
						<td class="advads-placement-conditions">
							<?php if ( ! empty( $_placement['options']['placement_conditions']['display'] ) ) : ?>
								<h4><?php echo esc_html__( 'Display Conditions', 'advanced-ads' ); ?></h4>
								<ul>
									<?php foreach ( $_placement['options']['placement_conditions']['display'] as $condition ) : ?>
										<li><?php echo esc_html( $display_conditions->conditions[ $condition['type'] ]['label'] ); ?></li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
							<?php if ( ! empty( $_placement['options']['placement_conditions']['visitors'] ) ) : ?>
								<h4><?php echo esc_html__( 'Visitor Conditions', 'advanced-ads' ); ?></h4>
								<ul>
									<?php foreach ( $_placement['options']['placement_conditions']['visitors'] as $condition ) : ?>
										<li><?php echo esc_html( $visitor_conditions->conditions[ $condition['type'] ]['label'] ); ?></li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
							<?php if ( $advanced_options ) : ?>
								<a href="#modal-<?php echo esc_attr( $_placement_slug ); ?>" style="cursor: pointer;"
								   data-placement="<?php echo esc_attr( $_placement_slug ); ?>" class="advads-mobile-hidden"><?php esc_html_e( 'edit conditions', 'advanced-ads' ); ?></a>
							<?php endif; ?>
						</td>
						<?php do_action( 'advanced-ads-placements-list-column', $_placement_slug, $_placement ); ?>
						<td class="hidden">
							<input type="checkbox"
								   id="advads-placements-item-delete-<?php echo esc_attr( $_placement_slug ); ?>"
								   class="advads-placements-item-delete"
								   name="advads[placements][<?php echo esc_attr( $_placement_slug ); ?>][delete]"
								   value="1"/>
							<label for="advads-placements-item-delete-<?php echo esc_attr( $_placement_slug ); ?>"><?php echo esc_html_x( 'delete', 'checkbox to remove placement', 'advanced-ads' ); ?></label>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<div class="tablenav bottom">
				<input type="submit" id="advads-save-placements-button" class="button button-primary" value="<?php esc_html_e( 'Save Placements', 'advanced-ads' ); ?>"/>
				<?php wp_nonce_field( 'advads-placement', 'advads_placement', true ); ?>
				<button type="button" title="<?php esc_html_e( 'Create a new placement', 'advanced-ads' ); ?>" class="button button-secondary" onclick="advads_toggle('.advads-placements-new-form'); advads_scroll_to_element('#advads-placements-new-form');">
					<?php
					esc_html_e( 'New Placement', 'advanced-ads' );
					?>
				</button>
				<?php do_action( 'advanced-ads-placements-list-buttons', $placements ); ?>
			</div>
			<input type="hidden" name="advads-last-edited-placement" id="advads-last-edited-placement" value="0"/>
		</form>
		<?php
		include ADVADS_BASE_PATH . 'admin/views/frontend-picker-script.php';
		do_action( 'advanced-ads-placements-list-after', $placements );
	endif;

	?>
</div>
