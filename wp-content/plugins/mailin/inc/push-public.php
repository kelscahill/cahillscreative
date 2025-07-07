<?php
if (!defined( 'ABSPATH' )) { http_response_code(403); exit(); }

if ( ! class_exists( 'SIB_Push_Public' ) ) {
	class SIB_Push_Public {
		const INSTALLATION_ID_COOKIE_NAME = 'sbjs_installation_id';
		static function init() {

			// For AMP
			add_filter( 'the_content', array(__CLASS__, 'the_content'), 10);

			// Cookie
			try {
				if (SIB_Push_Utils::get_woocommerce()
					&& SIB_Push_WooCommerce::cart_reminder_enabled()) {
					add_action('wp_head', array(__CLASS__, 'sib_push_cookie'), 10);
				}
			} catch ( SIB_Push_MissingCredentialsException $e ) {
				// Ignore
			} catch ( Exception $e ) {
				SIB_Push_Utils::log_warn($e);
			}


			// AMP support through official plugin (https://amp-wp.org/):
			// https://wordpress.org/plugins/amp/
			// Display the snippet and top button
			add_action('amp_post_template_body_open', array(__CLASS__, 'amp_post_template_body_open'));

			// AMP support through alternative plugin (AMP for WP, https://ampforwp.com/):
			// https://wordpress.org/plugins/accelerated-mobile-pages/
			// Display the snippet and top button
			add_filter('ampforwp_after_header', array(__CLASS__, 'ampforwp_after_header'), 10, 3);

			// AMP support, common to both plugins
			// Add the official support for web push in AMP
			add_filter('amp_post_template_head', array(__CLASS__, 'amp_post_template_head'), 10, 3);
			// Add the css
			add_action('amp_post_template_css', array(__CLASS__, 'amp_post_template_css'));
			// Display the bottom button
			add_action('amp_post_template_footer', array(__CLASS__, 'amp_post_template_footer'));
		}

		public static function amp_post_template_head() {
			if (SIB_Push_Settings::getSettings()->getDisableAmpTopSubscribeButton()
				&& SIB_Push_Settings::getSettings()->getDisableAmpBottomSubscribeButton()) {
				return;
			}
			?><script type='text/javascript' src='https://cdn.ampproject.org/v0/amp-web-push-0.1.js' async custom-element="amp-web-push"></script><?php
		}
		public static function amp_post_template_body_open() {
			if (!SIB_Push_Settings::getSettings()->getDisableAmpTopSubscribeButton()
				|| !SIB_Push_Settings::getSettings()->getDisableAmpBottomSubscribeButton()) {
				echo SIB_Push_Amp::snippet();
			}
			if (!SIB_Push_Settings::getSettings()->getDisableAmpTopSubscribeButton()) {
				echo SIB_Push_Amp::widget();
			}
		}
		public static function amp_post_template_footer() {
			if (SIB_Push_Settings::getSettings()->getDisableAmpBottomSubscribeButton()) {
				return;
			}
			echo SIB_Push_Amp::widget();
		}
		public static function amp_post_template_css() {
			$plugin_dir_path = plugin_dir_path(__FILE__);
			$real_path = realpath($plugin_dir_path . '/../css');
			include ($real_path . '/push-amp.css');
		}
		public static function ampforwp_after_header() {
			if (!SIB_Push_Settings::getSettings()->getDisableAmpTopSubscribeButton()
				|| !SIB_Push_Settings::getSettings()->getDisableAmpBottomSubscribeButton()) {
				echo SIB_Push_Amp::snippet();
			}
			if (!SIB_Push_Settings::getSettings()->getDisableAmpTopSubscribeButton()) {
				echo SIB_Push_Amp::widget();
			}
		}
		public static function the_content($content) {
			// Support for transitional mode of the official AMP plugin
			// Only single post, attachment, page, custom post types
			if (
				is_main_query()
				&& in_the_loop()
				&& is_singular() // post, attachment, page, custom post types
			) {
				if (SIB_Push_Utils::is_amp_request()) {
					$disableTop = SIB_Push_Settings::getSettings()->getDisableAmpTopSubscribeButton();
					$disableBottom = SIB_Push_Settings::getSettings()->getDisableAmpBottomSubscribeButton();
					if ($disableBottom && $disableTop) return $content;
					$file_content = SIB_Push_Amp::widget();
					return ($disableTop ? '' : $file_content) . $content . ($disableBottom ? '' : $file_content);
				}
			}

			return $content;
		}

		public static function sib_push_cookie() {
			if (!SIB_Push_Utils::is_push_active()) return;
			?>
			<script>
              (function() {
                window.addEventListener('WonderPushEvent', function(event) {
                  if (event.detail.name === 'session') {
                    window.WonderPush.push(function() {
                      window.WonderPush.getInstallationId()
                        .then(function(installationId) {
                          document.cookie = '<?php echo self::INSTALLATION_ID_COOKIE_NAME ?>=' + encodeURIComponent(installationId || '') + '; expires=' + new Date(new Date().getTime() + 86400000).toGMTString() + '; path=/';
                        });
                    });
                  }
                });
              })();
			</script>
			<?php
		}
	}
}