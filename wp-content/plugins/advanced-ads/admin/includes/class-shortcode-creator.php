<?php
/**
 * Shortcode generator for TinyMCE editor
 */
class Advanced_Ads_Shortcode_Creator {
	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Advanced_Ads_Shortcode_Creator constructor.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Call needed hooks and functions
	 */
	public function init() {
		$options = Advanced_Ads::get_instance()->options();

		if ( 'true' !== get_user_option( 'rich_editing' )
			|| ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_place_ads' ) )
			|| defined( 'ADVANCED_ADS_DISABLE_SHORTCODE_BUTTON' )
			|| apply_filters( 'advanced-ads-disable-shortcode-button', false )
		) {
			return;
		}

		add_action( 'wp_ajax_advads_content_for_shortcode_creator', array( $this, 'get_content_for_shortcode_creator' ) );

		// @see self::hooks_exist
		add_filter( 'mce_buttons', array( $this, 'register_buttons' ) );
		add_filter( 'tiny_mce_plugins', array( $this, 'tiny_mce_plugins' ) );
		add_action( 'wp_tiny_mce_init', array( $this, 'print_shortcode_plugin' ) );
		add_action( 'print_default_editor_scripts', array( $this, 'print_shortcode_plugin' ) );
	}

	/**
	 * Check if needed actions and filters have not been removed by a plugin.
	 *
	 * @return array
	 */
	private function hooks_exist() {
		if (
			(
				has_action( 'wp_tiny_mce_init', array( $this, 'print_shortcode_plugin' ) )
				|| has_action( 'print_default_editor_scripts', array( $this, 'print_shortcode_plugin' ) )
			)
			&& has_filter( 'mce_buttons', array( $this, 'register_buttons' ) )
			&& has_filter( 'tiny_mce_plugins', array( $this, 'tiny_mce_plugins' ) ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Print shortcode plugin inline.
	 *
	 * @param array|null $mce_settings TinyMCE settings array.
	 */
	public function print_shortcode_plugin( $mce_settings = array() ) {
		static $printed = null;

		if ( $printed !== null ) {
			return;
		}

		$printed = true;

		// The `tinymce` argument of the `wp_editor()` function is set  to `false`.
		if ( empty( $mce_settings ) && ! ( doing_action( 'print_default_editor_scripts' ) && user_can_richedit() ) ) {
			return;
		}

		if ( ! $this->hooks_exist() ) {
			return;
		}

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo "<script>\n"
			. $this->get_l10n() . "\n"
			. file_get_contents( ADVADS_BASE_PATH . 'admin/assets/js/shortcode.js' ) . "\n"
			. "</script>\n";
		// phpcs:enable
	}

	/**
	 * Get localization strings.
	 *
	 * @return string
	 */
	private function get_l10n() {
		static $script = null;

		if ( null === $script ) {
			include_once ADVADS_BASE_PATH . 'admin/includes/shortcode-creator-l10n.php';
			$script = $strings;
		}

		return $script;
	}

	/**
	 * Add the plugin to the array of default TinyMCE plugins.
	 * We do not use the array of external TinyMCE plugins because we print the plugin file inline.
	 *
	 * @see self::admin_enqueue_scripts
	 *
	 * @param array $plugins An array of default TinyMCE plugins.
	 * @return array $plugins An array of default TinyMCE plugins.
	 */
	public function tiny_mce_plugins( $plugins ) {
		if ( ! $this->hooks_exist() ) {
			return $plugins;
		}

		$plugins[] = 'advads_shortcode';
		return $plugins;
	}

	/**
	 * Include the shortcode plugin inline to prevent it from being blocked by ad blockers.
	 */
	public function admin_enqueue_scripts() {
		// Add the localization.
		include_once ADVADS_BASE_PATH . 'admin/includes/shortcode-creator-l10n.php';
		$script = $strings . "\n";
		// Add the plugin.
		$script .= file_get_contents( ADVADS_BASE_PATH . 'admin/assets/js/shortcode.js' );

		wp_add_inline_script( 'wp-tinymce', $script );
	}

	/**
	 * Add button to tinyMCE window
	 *
	 * @param array $buttons array with existing buttons.
	 *
	 * @return array
	 */
	public function register_buttons( $buttons ) {
		if ( ! $this->hooks_exist() ) {
			return $buttons;
		}
		if ( ! is_array( $buttons ) ) {
			$buttons = array();
		}
		$buttons[] = 'advads_shortcode_button';
		return $buttons;
	}

	/**
	 * Prints html select field for shortcode creator
	 */
	public function get_content_for_shortcode_creator() {
		if ( ! ( current_user_can( 'edit_posts' ) || current_user_can( 'edit_pages' ) ) ) {
			return;
		}

		$items = self::items_for_select(); ?>

		<select id="advads-select-for-shortcode">
			<option value=""><?php esc_html_e( '--empty--', 'advanced-ads' ); ?></option>
			<?php if ( isset( $items['ads'] ) ) : ?>
				<optgroup label="<?php esc_html_e( 'Ads', 'advanced-ads' ); ?>">
					<?php foreach ( $items['ads'] as $_item_id => $_item_title ) : ?>
					<option value="<?php echo esc_attr( $_item_id ); ?>"><?php echo esc_html( $_item_title ); ?></option>
					<?php endforeach; ?>
				</optgroup>
			<?php endif; ?>
			<?php if ( isset( $items['groups'] ) ) : ?>
				<optgroup label="<?php esc_html_e( 'Ad Groups', 'advanced-ads' ); ?>">
					<?php foreach ( $items['groups'] as $_item_id => $_item_title ) : ?>
					<option value="<?php echo esc_attr( $_item_id ); ?>"><?php echo esc_html( $_item_title ); ?></option>
					<?php endforeach; ?>
				</optgroup>
			<?php endif; ?>
			<?php if ( isset( $items['placements'] ) ) : ?>
				<optgroup label="<?php esc_html_e( 'Placements', 'advanced-ads' ); ?>">
					<?php foreach ( $items['placements'] as $_item_id => $_item_title ) : ?>
					<option value="<?php echo esc_attr( $_item_id ); ?>"><?php echo esc_html( $_item_title ); ?></option>
					<?php endforeach; ?>
				</optgroup>
			<?php endif; ?>
		</select>
		<?php
		exit();
	}

	/**
	 * Get items for item select field
	 *
	 * @return array $select items for select field.
	 */
	public static function items_for_select() {
		$select = array();
		$model  = Advanced_Ads::get_instance()->get_model();

		// load all ads.
		$ads = $model->get_ads(
			array(
				'orderby' => 'title',
				'order'   => 'ASC',
			)
		);
		foreach ( $ads as $_ad ) {
			$select['ads'][ 'ad_' . $_ad->ID ] = $_ad->post_title;
		}

		// load all ad groups.
		$groups = $model->get_ad_groups();
		foreach ( $groups as $_group ) {
			$select['groups'][ 'group_' . $_group->term_id ] = $_group->name;
		}

		// load all placements.
		$placements = $model->get_ad_placements_array();
		ksort( $placements );
		foreach ( $placements as $key => $_placement ) {
			$select['placements'][ 'placement_' . $key ] = $_placement['name'];
		}

		return $select;
	}
}
