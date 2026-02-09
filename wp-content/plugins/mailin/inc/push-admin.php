<?php
if (!defined( 'ABSPATH' )) { http_response_code(403); exit(); }

if ( ! class_exists( 'SIB_Push_Admin' ) ) {
	class SIB_Push_Admin {

		const SAVE_POST_NONCE_ACTION = 'sib_push_save_post_nonce_action';
		const SAVE_POST_NONCE_KEY = 'sib_push_save_post_nonce_key';
		const META_BOX_ID = 'sib_push_meta_box';
		const METADATA_MULTIVALUE_SEPARATOR = '<+>';
		const MAX_NOTIFICATION_DELAY_HOURS = 24;
		const POST_META_LAST_NOTIFICATION_CONTENT = 'sib_push_last_notification_content';
		const POST_META_LAST_NOTIFICATION_TIMESTAMP = 'sib_push_last_notification_timestamp';
		const POST_META_ERROR_MESSAGE = 'sib_push_error_message';
		const POST_META_INFO_MESSAGE = 'sib_push_info_message';
		const DEDUPLICATION_SECONDS = 60;
		const API_RATE_LIMIT_SECONDS = 3;

		public static function add_admin_bar_menu_item($wp_admin_bar) {
			if (!SIB_Push_Utils::is_admin_user()) {
				return; // Only for admins
			}
			$settings = SIB_Push_Settings::getSettings();
			if (!$settings->getShowPush()) {
				return;
			}
			if ($settings->getHideAdminBarShortcut()) {
				return;
			}

			$wp_admin_bar->add_node(array(
				'id'    => 'brevo_push_admin_bar_button',
				'title' => '<span class="ab-icon" style="position: relative; top: 3px; opacity: 0.7;">&#xF16D;</span><span class="ab-label">'.__('Web push', 'mailin').'</span>',
				'href'  => add_query_arg( 'page', SIB_Page_Push::PAGE_ID, admin_url( 'admin.php' ) ),
				'meta'  => array(
					'title' => __('Go to web push dashboard', 'mailin'),
				)
			));
		}
		public static function add_dashboard_widget() {
			$settings = SIB_Push_Settings::getSettings();
			if (!$settings->getShowPush()) return;
			if (SIB_Push_Utils::is_push_active()) return;
			if (!SIB_Push_Utils::is_admin_user()) {
				return; // Only for admins
			}
			wp_add_dashboard_widget(
				'sib_push_dashboard_widget',
				__('Web Push Notifications', 'mailin'),
				array( __CLASS__, 'dashboard_widget_html' ),
				null,
				null,
				'normal',
				'high'
			);
		}

		public static function dashboard_widget_html() {
			?>
			<p>
				<?php echo __( 'Grow your audience with push notifications', 'mailin' ) ?>
			</p>
			<ul>
				<li style="list-style: inside disc"><?php echo __( 'Notify your readers whenever a new post is published.', 'mailin' ) ?></li>
				<li style="list-style: inside disc"><?php echo __( 'Let your users subscribe to their favorite topics.', 'mailin' ) ?></li>
<!--				NOTE: deactivate woocommerce-->
<!--				<li style="list-style: inside disc">--><?php //echo __( 'Set up automated e-commerce notifications for your WooCommerce business.', 'mailin' ) ?>
			</ul>
			<p><a class="button button-primary"
				  href="<?php echo admin_url( 'admin.php?page=sib_page_push' ) ?>"><?php echo __( 'Activate web push', 'mailin' ) ?></a>
			</p>
			<?php
		}

		public static function add_post_options() {
			if (!SIB_Push_Utils::can_send_notifications()) {
                return;
			}

			$settings = SIB_Push_Settings::getSettings();
			if (!$settings->getShowPush()) return;
			if ($settings->getDisableSendOnPublish()) return;

            // Add the post editor js
            wp_enqueue_script( 'sib-select2' );
			wp_enqueue_style( 'sib-select2' );
            wp_enqueue_script( 'sib-post-editor-js' );
			wp_enqueue_style('sib-font-face');
			wp_localize_script( 'sib-post-editor-js', 'brevo_push_notice', array(
				'nonce' => SIB_Push_API::get_nonce()
			));
            wp_enqueue_style(  'sib-push-admin-css' );

			// Add meta box for the "post" post type (default)
			add_meta_box(self::META_BOX_ID,
				'Brevo web push',
				array( __CLASS__, 'add_post_html' ),
				'post',
				'normal',
				'high');

			// Add meta box for all other post types that are public but not built in to WordPress
			$post_types = get_post_types(array('public'   => true, '_builtin' => false), 'names', 'and' );
			foreach ( $post_types  as $post_type ) {
				add_meta_box(
					self::META_BOX_ID,
					'Brevo web push',
					array( __CLASS__, 'add_post_html' ),
					$post_type,
					'side',
					'high'
				);
			}
		}
		public static function add_post_html($post) {
			$post_type = $post->post_type;
			$settings = SIB_Push_Settings::getSettings();
			$credentials = $settings->getWonderPushCredentials();
			try {
				$app = SIB_Push_Utils::get_push_application(SIB_Push_Utils::DEFAULT_CACHE_TTL);
			} catch (Exception $e) {
				return;
			}

			// Add an nonce field so we can check for it later.
			wp_nonce_field(self::SAVE_POST_NONCE_ACTION, self::SAVE_POST_NONCE_KEY, true);

			// Our plugin config setting "Automatically send a push notification when I publish a post from the WordPress editor"
			$disable_send_by_default = $settings->getDisableSendByDefaultOnPublish();

			/* This is a scheduled post and the user checked "Send a notification on post publish/update". */
			$send_notification_checked = (get_post_meta($post->ID, 'sib_push_send_notification', true) == true);
			// User explicitely unchecked notification and saved post
			$send_notification_unchecked = get_post_meta($post->ID, 'sib_push_send_notification', true) === '0';
			$send_notification_delay_seconds = get_post_meta($post->ID, 'sib_push_send_notification_delay_seconds', true);
			if ($send_notification_delay_seconds === null || $send_notification_delay_seconds === '') {
				$send_notification_delay_seconds = $settings->getDeliveryTimeSeconds();
			}
			$send_notification_delay_seconds = (int)$send_notification_delay_seconds;

			// Defaults
			$default_target_brevo_segment_id = (string)$settings->getDefaultTargetSegmentId() ?: '';
			$default_target_brevo_list_id = (string)$settings->getDefaultTargetListId() ?: '';

			// Brevo segment IDs
			$target_brevo_segment_ids = (string)get_post_meta($post->ID, 'sib_push_target_brevo_segment_ids', true) ?: '';
			$target_brevo_segment_ids = array_filter(explode(self::METADATA_MULTIVALUE_SEPARATOR, $target_brevo_segment_ids ?: $default_target_brevo_segment_id));
			// Brevo list IDs
			$target_brevo_list_ids = (string)get_post_meta($post->ID, 'sib_push_target_brevo_list_ids', true) ?: '';
			$target_brevo_list_ids = array_filter(explode(self::METADATA_MULTIVALUE_SEPARATOR, $target_brevo_list_ids ?: $default_target_brevo_list_id));
			$target_tags = get_post_meta($post->ID, 'sib_push_target_tags', true) ?: '';
			$target_tags = array_filter(explode(self::METADATA_MULTIVALUE_SEPARATOR, $target_tags));
			// All segments
			try {
				$all_brevo_segments = $credentials ? SIB_API_Manager::get_segments() : null;
			} catch (Exception $e) {
				$all_brevo_segments = array();
				SIB_Push_Utils::log_warn('Could not get segment list', $e);
			}
			// All lists
			try {
				$all_brevo_lists = $credentials ? SIB_API_Manager::get_lists() : null;
			} catch (Exception $e) {
				$all_brevo_lists = array();
				SIB_Push_Utils::log_warn('Could not get lists', $e);
			}


			try {
				$app = SIB_Push_Utils::get_push_application();
				$all_tags = $app && $credentials
					? SIB_Push_Utils::list_tags($credentials)
					: array();

			} catch (Exception $e) {
				$all_tags = array();
				SIB_Push_Utils::log_warn('Could not get tags', $e);
			}

			// UTM params
			$utm_params = array();
			$url_parameters = $app ? (array)$app->getUrlParameters() : array();
			foreach (SIB_Push_Utils::utm_parameters() as $utm_parameter) {
				$value = get_post_meta($post->ID, "sib_push_$utm_parameter", true);
				$value = $value ?: (array_key_exists($utm_parameter, $url_parameters) ? $url_parameters[$utm_parameter] : null);
				if ($value) $utm_params[$utm_parameter] = $value;
			}
			$hours = array();
			for ($i = 1; $i <= self::MAX_NOTIFICATION_DELAY_HOURS; $i++) {
				$hours []= $i;
			}
			$minutes = array(5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55);

			// We check the checkbox if: setting is enabled on Config page, post type is ONLY "post", and the post has not been published (new posts are status "auto-draft")
			$send_notification = (!$send_notification_unchecked
					&& !$disable_send_by_default
					&& $post->post_type == "post"
					&&  in_array($post->post_status, array("future", "draft", "auto-draft", "pending")))
				|| $send_notification_checked;

			$notification_already_sent = !!(get_post_meta($post->ID, self::POST_META_LAST_NOTIFICATION_CONTENT, true));
			$sib_push_audience = 'all';
			if (count($target_tags)) {
				$sib_push_audience = 'tags';
			} else if (!empty($target_brevo_segment_ids)) {
				$sib_push_audience = 'brevo_segments';
			} else if (!empty($target_brevo_list_ids)) {
				$sib_push_audience = 'brevo_lists';
			}
			$contactSyncMessage = SIB_Push_Utils::is_contact_sync_active()
				? null
				: sprintf(
					// translators: %1$s: advanced settings
					__( 'To use Brevo segments and lists, enable contact creation from push subscribers %1$s.', 'mailin' ),
					'<a href="'.add_query_arg( 'page', SIB_Page_Push::PAGE_ID, admin_url( 'admin.php' ) ).'#/settings/advanced" style="font-size:12px;">' . __( 'in Advanced settings', 'mailin' ) . '</a>',
				);
			?>
			<div id="sib_notification_preview"></div>
			<div id="sib_push_config" style="display:none"><?php
				echo json_encode(SIB_Push_API::get_push_configuration());
				?></div>
			<?php if (!SIB_Push_Utils::is_push_active()): ?>
			<div id="sib_push_activation">
				<p>
					<?php _e('Notify your readers:', 'mailin');?>
				</p>
				<p>
					<label>
						<input type="checkbox" id="sib_push_activation_button" value="true" />
						<strong>
							<?php if ($post->post_status == "publish") {
								/* translators: %s: Type of post. Usually is the string "post" */
								printf(__("Send web push on %s update", 'mailin'), $post_type);
							} else {
								/* translators: %s: Type of post. Usually is the string "post" */
								printf(__("Send web push on %s publish", 'mailin'), $post_type);
							}
							?>
							<span class="new-sticker"><?php _e('New', 'mailin') ?></span>
						</strong>
					</label>
					<span class="spinner" style="float: none; margin: 0;"></span>
					<span style="display: none;" class="sib_push_activating_message"><?php _e('Please wait a few seconds...', 'mailin') ?></span>
				</p>
			</div>
			<?php endif; ?>
			<div id="sib_push_editor">
				<input type="hidden" name="sib_push_meta_box_present" value="true"/>
				<?php if ($notification_already_sent) : ?>
					<input type="hidden" name="sib_push_notification_already_sent" value="true"/>
				<?php endif; ?>
				<label>
					<input type="checkbox" name="send_sib_push_notification" value="true" <?php if ($send_notification) {
						echo "checked";
					} ?> />
					<strong>
						<?php if ($post->post_status == "publish") {
							/* translators: %s: Type of post. Usually is the string "post" */
							printf(__("Send web push on %s update", 'mailin'), $post_type);
						} else {
							/* translators: %s: Type of post. Usually is the string "post" */
							printf(__("Send web push on %s publish", 'mailin'), $post_type);
						}
						?>
					</strong>
				</label>
				<div class="sib_push_audience">
					<h3 style="margin-bottom: 3px;"><?php echo __('Target audience', 'mailin') ?></h3>
					<label>
						<input
							type="radio"
							name="sib_push_audience"
							value="all"
							<?php echo $sib_push_audience === 'all' ? 'checked' : '' ?>
						/>
						<?php echo __('Everybody', 'mailin') ?>
					</label>
					<div class="sib_push_all">
						<div class="sib_push_target" style="padding: 0">
							<input type="hidden" name="sib_push_target_segment_ids[]" value="@ALL" />
						</div>
					</div>
					<label>
						<input
							type="radio"
							name="sib_push_audience"
							value="brevo_segments"
							<?php echo $sib_push_audience === 'brevo_segments' ? 'checked' : '' ?>
						/>
						<?php echo __('Users in segment(s)', 'mailin') ?>
					</label>
					<div class="sib_push_segments">
						<div class="sib_push_target">
							<label for="sib_push_target_brevo_segment_ids"><?php echo __("We'll notify users that match at least one of these segments:", 'mailin') ?></label>
							<?php if ($contactSyncMessage): ?>
								<p style="padding: 8px; background-color: #fdf5f1; border-radius: 12px;"><?php echo $contactSyncMessage; ?></p>
							<?php endif; ?>
							<select name="sib_push_target_brevo_segment_ids[]" multiple
									id="sib_push_target_brevo_segment_ids"
									class="sib_push_target_segment_id sib_push_select2">
								<option value=""><?php echo __("Everyone", 'mailin') ?></option>
								<?php
								foreach ($all_brevo_segments as $segment) {
									?>
									<option
									<?php echo array_search($segment['id'], $target_brevo_segment_ids) !== false ? 'selected="selected"' : '' ?>
									value="<?php echo $segment['id'] ?>"><?php echo $segment['segmentName'] ?: $segment['id'] ?></option><?php
								}
								?>
							</select>
						</div>
					</div>
					<label>
						<input
								type="radio"
								name="sib_push_audience"
								value="brevo_lists"
							<?php echo $sib_push_audience === 'brevo_lists' ? 'checked' : '' ?>
						/>
						<?php echo __("Users in list(s)", 'mailin') ?>
					</label>
					<div class="sib_push_lists">
						<div class="sib_push_target">
							<?php if ($contactSyncMessage): ?>
								<p style="padding: 8px; background-color: #fdf5f1; border-radius: 12px;"><?php echo $contactSyncMessage; ?></p>
							<?php endif; ?>
							<label for="sib_push_target_brevo_list_ids"><?php echo __("We'll notify users that match at least one of these lists:", 'mailin') ?></label>
							<select name="sib_push_target_brevo_list_ids[]" multiple
									id="sib_push_target_brevo_list_ids"
									class="sib_push_target_list_id sib_push_select2">
								<option value=""><?php echo __("Everyone", 'mailin') ?></option>
								<?php
								foreach ($all_brevo_lists as $list) {
									?>
									<option
									<?php echo array_search($list['id'], $target_brevo_list_ids) !== false ? 'selected="selected"' : '' ?>
									value="<?php echo $list['id'] ?>"><?php echo $list['name'] ?: $list['id'] ?></option><?php
								}
								?>
							</select>
						</div>
					</div>
					<label>
						<input
							type="radio"
							name="sib_push_audience"
							value="tags"
							<?php echo $sib_push_audience === 'tags' ? 'checked' : '' ?>
						/>
						<?php echo __("Users with tag(s)", 'mailin') ?>
					</label>
					<div class="sib_push_tags">
						<div class="sib_push_target">
							<label for="sib_push_target_tags"><?php echo __("We'll notify users that match at least one of these tags:", 'mailin') ?></label>
							<select name="sib_push_target_tags[]" multiple
									id="sib_push_target_tags"
									class="sib_push_target_tags sib_push_select2">
								<?php
								foreach ($all_tags as $tag) {
									?>
									<option
									<?php echo array_search($tag, $target_tags) !== false ? 'selected="selected"' : '' ?>
									value="<?php echo $tag ?>"><?php echo $tag ?></option><?php
								}
								?>
							</select>
						</div>
					</div>
				</div>
				<?php if ($all_brevo_segments) : ?>
				<?php endif; ?>
				<h3 style="margin-bottom: 3px;"><?php echo __("Send later", 'mailin') ?></h3>
				<small><?php echo __("Delay the notification after this post gets published:", 'mailin') ?></small>
				<br/>
				<select name="sib_push_send_notification_delay_seconds">
					<option value="0"><?php echo __("No delay", 'mailin') ?></option>
					<?php foreach ($minutes as $minute): ?>
						<option
							<?php echo (($minute * 60) === $send_notification_delay_seconds ? 'selected="selected"' : '') ?>
							value="<?php echo $minute * 60 ?>">
							<?php echo $minute ?> <?php echo __("minutes", 'mailin') ?>
						</option>
					<?php endforeach; ?>
					<?php foreach ($hours as $hour): ?>
						<option
							<?php echo (($hour * 3600) === $send_notification_delay_seconds ? 'selected="selected"' : '') ?>
							value="<?php echo $hour * 3600 ?>">
							<?php echo $hour ?><?php echo _n("hour", "hours", $hour, 'mailin') ?>
						</option>
					<?php endforeach; ?>
				</select>
				<h3 style="margin-bottom: 3px;"><?php echo __("Google campaign parameters", 'mailin') ?></h3>
				<small><?php echo __("Campaign params help you see web push traffic in Google Analytics.", "mailin") ?> <a target="_blank" href="https://support.google.com/analytics/answer/1033863#parameters"><?php echo __("Learn more", "mailin") ?></a>.</small>
				<div class="sib_push_utm_parameters">
					<?php foreach (SIB_Push_Utils::utm_parameters() as $utm_parameter): ?>
						<?php $id = 'sib_push_'. $utm_parameter; ?>
						<div class="sib_push_utm">
							<label for="<?php echo $id; ?>"><?php echo $utm_parameter; ?>:</label>
							<input type="text"
								   id="<?php echo $id; ?>" name="<?php echo $id; ?>"
								   value="<?php echo esc_attr(array_key_exists($utm_parameter, $utm_params) ? $utm_params[$utm_parameter] : '') ?>"/>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<?php
		}
		public static function on_save_post($post_id) {
			// Check nonce
			if (!isset( $_POST[self::SAVE_POST_NONCE_KEY] ) ) {
				return $post_id;
			}

			$nonce = $_POST[self::SAVE_POST_NONCE_KEY];

			// Verify nonce
			if (!wp_verify_nonce($nonce, self::SAVE_POST_NONCE_ACTION)) {
				return $post_id;
			}

			if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
				return $post_id;
			}

			$sib_push_meta_box_present = array_key_exists('sib_push_meta_box_present', $_POST);
			update_post_meta($post_id, 'sib_push_meta_box_present', $sib_push_meta_box_present ? true : false);

			if (array_key_exists('send_sib_push_notification', $_POST)) {
				$notification_already_sent = !!(get_post_meta($post_id, self::POST_META_LAST_NOTIFICATION_CONTENT, true));
				if (
					!$notification_already_sent // Notification wasn't sent
					|| ($notification_already_sent && array_key_exists('sib_push_notification_already_sent', $_POST)) // Notification was sent and the UI reflected this
				) {
					update_post_meta($post_id, 'sib_push_send_notification', true);
				}
			} else {
				// If meta box present, user explicitely unchecked
				$sib_push_send_notification = $sib_push_meta_box_present ? '0' : false;
				update_post_meta($post_id, 'sib_push_send_notification', $sib_push_send_notification);
			}

			$settings = SIB_Push_Settings::getSettings();
			if (array_key_exists('sib_push_send_notification_delay_seconds', $_POST)) {
				$meta_value = trim(sanitize_text_field($_POST['sib_push_send_notification_delay_seconds']));
				if (SIB_Push_Utils::is_int_string($meta_value) && (int)$meta_value <= self::MAX_NOTIFICATION_DELAY_HOURS * 3600) {
					update_post_meta($post_id, 'sib_push_send_notification_delay_seconds', (int)$meta_value);
				}
			} else {
				update_post_meta($post_id, 'sib_push_send_notification_delay_seconds', null);
			}

			if (array_key_exists('sib_push_target_tags', $_POST)) {
				$meta_values = array_filter(array_map(function($elt) {
					return trim(sanitize_text_field($elt));
				}, $_POST['sib_push_target_tags']));
				update_post_meta($post_id, 'sib_push_target_tags', count($meta_values) ? implode(self::METADATA_MULTIVALUE_SEPARATOR, $meta_values) : null);
			} else {
				update_post_meta($post_id, 'sib_push_target_tags', null);
			}

			if (array_key_exists('sib_push_target_segment_ids', $_POST)) {
				$meta_values = array_filter(array_map(function ($elt) {
					return trim(sanitize_text_field($elt));
				}, $_POST['sib_push_target_segment_ids']));
				update_post_meta($post_id, 'sib_push_target_segment_ids', count($meta_values) ? implode(self::METADATA_MULTIVALUE_SEPARATOR, $meta_values) : null);
			} else {
				update_post_meta($post_id, 'sib_push_target_segment_ids', null);
			}

			if (array_key_exists('sib_push_target_brevo_segment_ids', $_POST)) {
				$meta_values = array_filter(array_map(function ($elt) {
					return trim(sanitize_text_field($elt));
				}, $_POST['sib_push_target_brevo_segment_ids']));
				update_post_meta($post_id, 'sib_push_target_brevo_segment_ids', count($meta_values) ? implode(self::METADATA_MULTIVALUE_SEPARATOR, $meta_values) : null);
			} else {
				update_post_meta($post_id, 'sib_push_target_brevo_segment_ids', null);
			}

			if (array_key_exists('sib_push_target_brevo_list_ids', $_POST)) {
				$meta_values = array_filter(array_map(function ($elt) {
					return trim(sanitize_text_field($elt));
				}, $_POST['sib_push_target_brevo_list_ids']));
				update_post_meta($post_id, 'sib_push_target_brevo_list_ids', count($meta_values) ? implode(self::METADATA_MULTIVALUE_SEPARATOR, $meta_values) : null);
			} else {
				update_post_meta($post_id, 'sib_push_target_brevo_list_ids', null);
			}

			foreach (SIB_Push_Utils::utm_parameters() as $utm_parameter) {
				$key = "sib_push_$utm_parameter";
				if (array_key_exists($key, $_POST)) {
					$meta_value = trim(sanitize_text_field($_POST[$key]));
					update_post_meta($post_id, $key, $meta_value && strlen($meta_value) ? $meta_value : null);
				}
			}
		}

		public static function on_transition_post_status( $new_status, $old_status, $post ) {
			if ($old_status === 'trash' && $new_status === 'publish') {
				return;
			}
			if (!empty($post)
				&& $new_status === "publish"
				&& get_post_status($post->ID) === "publish"
				&& $post->post_type !== 'page') {
				self::send_notification_on_post($new_status, $old_status, $post);
			}
		}

		public static function send_notification_on_post($new_status, $old_status, $post) {
			try {
				if (!SIB_Push_Utils::is_push_active()) return;
				if (!SIB_Push_Utils::is_curl_installed()) {
					return;
				}
				$settings = SIB_Push_Settings::getSettings();
				$credentials = $settings->getWonderPushCredentials();
				if (!$credentials || $settings->getDisableSendOnPublish()) return;

				// quirk of Gutenberg editor leads to two passes if meta box is added
				// conditional removes first pass
				if( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
					return;
				}

				// Returns true if there is POST data
				$was_posted = !empty($_POST);

				// When this post was created or updated, the meta box in the WordPress post editor screen was visible
				$sib_push_meta_box_present = $was_posted && array_key_exists('sib_push_meta_box_present', $_POST) && $_POST['sib_push_meta_box_present'] === 'true';

				// The checkbox "Send notification on post publish/update" on the meta box is checked
				$sib_push_meta_box_send_notification_checked = $was_posted && array_key_exists('send_sib_push_notification', $_POST) && $_POST['send_sib_push_notification'] === 'true';

				// The notification date was filled
				$sib_push_meta_send_notification_delay_seconds = null;
				if ($was_posted && array_key_exists('sib_push_send_notification_delay_seconds', $_POST)) {
					$meta_value = trim(sanitize_text_field($_POST['sib_push_send_notification_delay_seconds']));
					if (SIB_Push_Utils::is_int_string($meta_value) && (int)$meta_value < self::MAX_NOTIFICATION_DELAY_HOURS * 3600) {
						$sib_push_meta_send_notification_delay_seconds = (int)$meta_value;
					}
				}

				// Target WonderPush segment IDs - This is REQUIRED to handle @ALL
				// We currently will store "@ALL" in the `sib_push_target_segment_ids` key of the postmeta
				$target_segment_ids = array();
				if ($was_posted && array_key_exists('sib_push_target_segment_ids', $_POST)) {
					$target_segment_ids = array_filter(array_map(function($elt) {
						return trim(sanitize_text_field($elt));
					}, $_POST['sib_push_target_segment_ids']));
				} else {
					$meta_value = get_post_meta($post->ID, 'sib_push_target_segment_ids', true) ?: '';
					$target_segment_ids = array_filter(explode(self::METADATA_MULTIVALUE_SEPARATOR, $meta_value));
				}

				// Target Brevo segment IDs
				$target_brevo_segment_ids = array();

				if ($was_posted && array_key_exists('sib_push_target_brevo_segment_ids', $_POST)) {
					$target_brevo_segment_ids = array_filter(array_map(function($elt) {
						return trim(sanitize_text_field($elt));
					}, $_POST['sib_push_target_brevo_segment_ids']));
				} else {
					$meta_value = get_post_meta($post->ID, 'sib_push_target_brevo_segment_ids', true) ?: '';
					$target_brevo_segment_ids = array_filter(explode(self::METADATA_MULTIVALUE_SEPARATOR, $meta_value));
				}

				// Target Brevo list IDs
				$target_brevo_list_ids = array();

				if ($was_posted && array_key_exists('sib_push_target_brevo_list_ids', $_POST)) {
					$target_brevo_list_ids = array_filter(array_map(function($elt) {
						return trim(sanitize_text_field($elt));
					}, $_POST['sib_push_target_brevo_list_ids']));
				} else {
					$meta_value = get_post_meta($post->ID, 'sib_push_target_brevo_list_ids', true) ?: '';
					$target_brevo_list_ids = array_filter(explode(self::METADATA_MULTIVALUE_SEPARATOR, $meta_value));
				}

				// Target tags
				$target_tags = array();
				if ($was_posted && array_key_exists('sib_push_target_tags', $_POST)) {
					$target_tags = array_filter(array_map(function($elt) {
						return trim(sanitize_text_field($elt));
					}, $_POST['sib_push_target_tags']));
				} else {
					$meta_value = get_post_meta($post->ID, 'sib_push_target_tags', true) ?: '';
					$target_tags = array_filter(explode(self::METADATA_MULTIVALUE_SEPARATOR, $meta_value));
				}

				// utm parameters
				$utm_params = array();
				foreach (SIB_Push_Utils::utm_parameters() as $utm_parameter) {
					$value = null;
					$key = "sib_push_$utm_parameter";
					if ($was_posted) {
						if (array_key_exists($key, $_POST)) {
							$value = $_POST[$key];
						}
					} else {
						$value = get_post_meta($post->ID, $key, true);
					}
					$value = $value ? trim(sanitize_text_field($value)) : $value;
					$value = $value && strlen($value) > 256 ? substr($value, 0, 256) : $value;
					$value = $value && strlen($value) ? $value : null;
					if ($value) $utm_params[$utm_parameter] = $value;
				}
				// This is a scheduled post and the meta box was present.
				$post_metadata_was_sib_push_meta_box_present = (get_post_meta($post->ID, 'sib_push_meta_box_present', true) == true);

				// This is a scheduled post and the user checked "Send a notification on post publish/update".
				$post_metadata_was_send_notification_checked = (get_post_meta($post->ID, 'sib_push_send_notification', true) == true);

				// This is a scheduled post and the user filled notification delay
				$post_metadata_send_notification_delay_seconds = get_post_meta($post->ID, 'sib_push_send_notification_delay_seconds', true);
				if ($post_metadata_send_notification_delay_seconds === null
					|| $post_metadata_send_notification_delay_seconds === '') {
					// Backwards compat: set this to the settings value for those who saved the post with a previous version of the plugin
					// The current plugin version always sets a $post_metadata_send_notification_delay_seconds
					$post_metadata_send_notification_delay_seconds = $settings->getDeliveryTimeSeconds();
				}
				$post_metadata_send_notification_delay_seconds = (int)$post_metadata_send_notification_delay_seconds;

				// Either we were just posted from the WordPress post editor form, or this is a scheduled notification and it was previously submitted from the post editor form
				$posted_from_wordpress_editor = $sib_push_meta_box_present || $post_metadata_was_sib_push_meta_box_present;

				$last_sent_title = get_post_meta($post->ID, self::POST_META_LAST_NOTIFICATION_CONTENT, true);

				$send_notification_delay_seconds = null;

				$settings_send_notification_on_non_editor_post_publish = $settings->getSendOnThirdPartyPublish();
				$additional_custom_post_types_string = str_replace(' ', '', $settings->getAdditionalCustomPostTypes() ?: '');
				$additional_custom_post_types_array = array_filter(explode(',', $additional_custom_post_types_string));
				$non_editor_post_publish_do_send_notification = $settings_send_notification_on_non_editor_post_publish &&
					($post->post_type === 'post' || in_array($post->post_type, $additional_custom_post_types_array, true)) &&
					$old_status !== 'publish';

				if ($posted_from_wordpress_editor) {
					$do_send_notification = ($was_posted && $sib_push_meta_box_send_notification_checked) ||
						(!$was_posted && $post_metadata_was_send_notification_checked);

					if ($was_posted) {
						// When posting and the notification has already been sent, make sure the 'sib_push_notification_already_sent' key was sent along
						// Otherwise, this may be a page that wasn't refreshed as the post was published in the background.
						if ($last_sent_title && !array_key_exists('sib_push_notification_already_sent', $_POST)) {
							$do_send_notification = false;
						}

						$send_notification_delay_seconds = $sib_push_meta_send_notification_delay_seconds;
					} else {
						$send_notification_delay_seconds = $post_metadata_send_notification_delay_seconds;
					}
				} else {
					// This was not submitted via the WordPress editor
					$do_send_notification = $non_editor_post_publish_do_send_notification;
				}

				if (!$do_send_notification) return;

				// Create WonderPush client
				$management_api_client = SIB_Push_Utils::management_api_client($credentials);
				$default_target_segment_id = $settings->getDefaultTargetSegmentId();
				$default_target_list_id = $settings->getDefaultTargetListId();

				update_post_meta($post->ID, 'sib_push_meta_box_present', false);
				update_post_meta($post->ID, 'sib_push_send_notification', false);

				// Some WordPress environments seem to be inconsistent about whether on_save_post is called before transition_post_status
				// This sets the metadata back to true, and will cause a post to be sent even if the checkbox is not checked the next time
				// We remove all related $_POST data to prevent this
				if ($was_posted) {
					if (array_key_exists('sib_push_meta_box_present', $_POST)) {
						unset($_POST['sib_push_meta_box_present']);
					}
					if (array_key_exists('send_sib_push_notification', $_POST)) {
						unset($_POST['send_sib_push_notification']);
					}
				}

				$title = SIB_Push_Utils::decode_entities(get_the_title($post->ID));

				$site_title = "";
				if ($settings->getNotificationTitle()) {
					$site_title = SIB_Push_Utils::decode_entities($settings->getNotificationTitle());
				} else {
					$site_title = SIB_Push_Utils::decode_entities(get_bloginfo('name'));
				}

				$icon_image = null;
				$big_picture = null;
				if (has_post_thumbnail($post->ID)) {

					$post_thumbnail_id = get_post_thumbnail_id($post->ID);

					// Higher resolution (2x retina, + a little more) for the notification small icon
					$thumbnail_sized_images_array = wp_get_attachment_image_src($post_thumbnail_id, 'medium', false);
					$thumbnail_image = $thumbnail_sized_images_array && count($thumbnail_sized_images_array) > 0 ? $thumbnail_sized_images_array[0] : null;

					// Much higher resolution for the notification large image
					$large_sized_images_array = wp_get_attachment_image_src($post_thumbnail_id, 'large', false);
					$large_image = $large_sized_images_array && count($large_sized_images_array) > 0 ? $large_sized_images_array[0] : null;

					$config_use_featured_image_as_icon = !($settings->getDisableUsePostImageForNotification());
					$config_use_featured_image_as_image = !($settings->getDisableUsePostImageForNotification());
					$use_large_image = $settings->getPreferLargeImageForNotification();

					// Use the same image in any case
					$image = $use_large_image ? ($large_image ?: $thumbnail_image) : ($thumbnail_image ?: $large_image);

					// WPRocket support
					if ( function_exists( 'get_rocket_cdn_url' ) && $image ) {
						try {
							$rocket_url = get_rocket_cdn_url($image);
							if ($rocket_url) {
								$image = $rocket_url;
							}
						} catch (Exception $e) {
							SIB_Push_Utils::log_warn('Rocket cdn function get_rocket_cdn_url threw', $e);
						}
					}

					if ($config_use_featured_image_as_icon) {
						$icon_image = $image;
					}
					if ($config_use_featured_image_as_image) {
						$big_picture = $image;
					}
				}

				// Send the notification
				$notification = new \WonderPush\Obj\Notification();
				$alert = new \WonderPush\Obj\NotificationAlert();
				$notification->setAlert($alert);
				$permalink = get_permalink($post->ID);
				$target_url = SIB_Push_Utils::inject_query_string_params($permalink, $utm_params);
				$alert->setTargetUrl($target_url);
				$alert->setTitle($site_title);
				$alert->setText($title);

				// Android
				$android = new \WonderPush\Obj\NotificationAlertAndroid();
				$alert->setAndroid($android);
				if ($big_picture) {
					$android->setBigPicture($big_picture);
					$android->setType('bigPicture');
				}
				$ios = new \WonderPush\Obj\NotificationAlertIos();
				$alert->setIos($ios);
				if ($big_picture) {
					$attachment = new \WonderPush\Obj\NotificationAlertIosAttachment();
					$attachment->setUrl($big_picture);
					$attachment->setType('image/png'); // Valid for all image types
					$ios->setAttachments(array($attachment));
				}
				$ios->setSound('default');
				$web = new \WonderPush\Obj\NotificationAlertWeb();
				$alert->setWeb($web);
				if ($icon_image) $web->setIcon($icon_image);
				if ($big_picture) $web->setImage($big_picture);
				$params = new \WonderPush\Params\DeliveriesCreateParams();
				$params->setDeliverySource('brevoWordPressPlugin');
				$params->setInheritUrlParameters(true);
				$params->setNotification($notification);
				$brevoSegmentIds = array();
				$brevoListIds = array();
				$segmentIds = array();
				if (count($target_tags)) {
					$params->setTargetTags($target_tags);
				} else if (count($target_brevo_segment_ids)) {
					$brevoSegmentIds = $target_brevo_segment_ids;
					$params->setTargetBrevoSegmentIds( array_map(function ($x) { return (int)$x; }, $brevoSegmentIds) );
				} else if (count($target_brevo_list_ids)) {
					$brevoListIds = $target_brevo_list_ids;
					$params->setTargetBrevoListIds( $target_brevo_list_ids );
				} else if (count($target_segment_ids)) {
					$segmentIds = $target_segment_ids;
					$params->setTargetSegmentIds($segmentIds);
				} else if ($default_target_segment_id) {
					$brevoSegmentIds = array($default_target_segment_id);
					$params->setTargetBrevoSegmentIds( $brevoSegmentIds );
				} else if ($default_target_list_id) {
					$brevoListIds = array($default_target_list_id);
					$params->setTargetBrevoListIds( $brevoListIds );
				} else {
					$segmentIds = array('@ALL');
					$params->setTargetSegmentIds($segmentIds);
				}
				if ($send_notification_delay_seconds !== null && $send_notification_delay_seconds > 0) {
					$params->setDeliveryTime('' . $send_notification_delay_seconds . 's');
				}

				// Set filterWebDomains if sendOnlyToThisDomain is enabled
				$settings = SIB_Push_Settings::getSettings();
				if ($settings->getSendOnlyToThisDomain()) {
					$current_site_url = home_url();
					$parsed_current = parse_url($current_site_url);
					if ($parsed_current) {
						$current_origin = $parsed_current['scheme'] . '://' . $parsed_current['host'] . (isset($parsed_current['port']) ? (':' . $parsed_current['port']) : '');
						$params->setFilterWebDomains(array($current_origin));
					}
				}

				// Deduplicate notifications
				$last_sent_timestamp = get_post_meta($post->ID, self::POST_META_LAST_NOTIFICATION_TIMESTAMP, true);
				$elapsed = current_time('timestamp') - ($last_sent_timestamp ? $last_sent_timestamp : 0);
				if ($elapsed < self::DEDUPLICATION_SECONDS && $last_sent_title === $title) {
					SIB_Push_Utils::log_debug('Discarding duplicate notification', $params);
					return;
				}

				// Rate limit
				$wait_time = self::get_sending_rate_limit_wait_time();
				if ($wait_time) {
					update_post_meta($post->ID, self::POST_META_ERROR_MESSAGE, 'You must wait ' . $wait_time . 's before sending another notification');
					return;
				}

				// Remember last notification content and timestamp
				update_post_meta($post->ID, self::POST_META_LAST_NOTIFICATION_CONTENT, $title);
				update_post_meta($post->ID, self::POST_META_LAST_NOTIFICATION_TIMESTAMP, current_time('timestamp'));

				// Send the notification
				SIB_Push_Utils::log_debug('Sending Brevo push notification', $params);
				self::update_last_sent_timestamp();
				$response = $management_api_client->deliveries()->create($params);

				// Handle success/failure
				if ($response->isSuccess()) {
					if (count($brevoListIds) || count($brevoSegmentIds) || $settings->getDisableFeedbackOnPublish()) {
						update_post_meta($post->ID, self::POST_META_INFO_MESSAGE, __('Brevo push notification sent.', 'mailin'));
					} else {
						// Fetch the number of subscribers
						try {
							$countResponse = $management_api_client->installations()->all(array(
								'limit' => 1,
								'reachability' => 'optIn',
								'segmentIds' => $segmentIds,
								'tags' => $target_tags,
							));
							$count = $countResponse->getCount();
							if ($count) {
								if ($send_notification_delay_seconds) {
									$dt = new DateTime();
									$dt->setTimestamp($send_notification_delay_seconds + $dt->getTimestamp());
									$formatted_date = $dt->format(DateTime::RFC850);
									// translators: %d is the number of subscribers, %s is the date and time
									update_post_meta($post->ID, self::POST_META_INFO_MESSAGE, sprintf(__("Brevo will send a notification to %d subscribers on %s.", "mailin"), $count, $formatted_date));
								} else {
									// translators: %d is the number of subscribers
									update_post_meta($post->ID, self::POST_META_INFO_MESSAGE, sprintf(__("Brevo notification sent to %d subscribers.", "mailin"), $count));
								}
							} else {
								update_post_meta($post->ID, self::POST_META_ERROR_MESSAGE, __("Brevo notification sent but the target audience is empty.", "mailin"));
							}
						} catch (\WonderPush\Errors\Base $e) {}
					}
				} else {
					update_post_meta($post->ID, self::POST_META_ERROR_MESSAGE, __("Brevo notification could not be sent.", "mailin"));
				}
			} catch (\WonderPush\Errors\Base $e) {
				switch ($e->getCode()) {
					default:
						update_post_meta($post->ID, self::POST_META_ERROR_MESSAGE, $e->getMessage());
						break;
				}
			} catch (Exception $e) {
				SIB_Push_Utils::log_warn('Caught Exception', $e);
			}
		}

		public static function get_sending_rate_limit_wait_time() {
			$last_send_time = get_option('sib_push.last_send_time');
			if ($last_send_time) {
				$current_time = current_time('timestamp');
				$time_elapsed_since_last_send = self::API_RATE_LIMIT_SECONDS - ($current_time - intval($last_send_time));
				if ($time_elapsed_since_last_send > 0) {
					return $time_elapsed_since_last_send;
				}
			}
			return false;
		}

		/**
		 * Updates the last sent timestamp, used in rate limiting notifications sent more than 1 per minute.
		 */
		public static function update_last_sent_timestamp() {
			$current_time = current_time('timestamp');
			update_option('sib_push.last_send_time', $current_time);
		}

    }

}
