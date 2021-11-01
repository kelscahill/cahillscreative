<?php
namespace Ezoic_Namespace;

class Ezoic_Speed_Settings {
	public function __construct() {
		if ( ! is_admin() ) {
			if ( self::is_setting_enabled( 'ezoic_disable_emojis' ) ) {
				add_action( 'init', array( $this, 'disable_emojis' ) );
			}
	
			if ( self::is_setting_enabled( 'ezoic_schemeless_urls' ) ) {
				add_filter( 'script_loader_src', array( $this, 'schemeless_urls' ) );
				add_filter( 'style_loader_src', array( $this, 'schemeless_urls' ) );
			}
	
			if ( self::is_setting_enabled( 'ezoic_remove_jquery_migrate' ) ) {
				add_action( 'wp_default_scripts', array( $this, 'remove_jquery_migrate' ) );
			}

			add_action( 'template_redirect', array( $this, 'ezoic_header_cleanup' ) );
		}
	}

	public function initialize_ezoic_speed_settings() {
		if ( false === \get_option( 'ezoic_speed_settings' ) ) {
			$default_array = $this->default_speed_settings();
			update_option( 'ezoic_speed_settings' , $default_array );
		}

		add_settings_section(
			'ezoic_speed_settings_section',
			__( 'Speed Settings', 'ezoic'),
			array( $this, 'ezoic_speed_settings_callback' ),
			'ezoic_speed_settings'
		);

		add_settings_field(
			'ezoic_disable_emojis',
			'Disable Emojis',
			array( $this, 'ezoic_disable_emojis_callback' ),
			'ezoic_speed_settings',
			'ezoic_speed_settings_section',
			array(
				__( 'Disable Emojis', 'ezoic' ),
			)
		);

		add_settings_field(
			'ezoic_schemeless_urls',
			__( 'Set scheme-less URLs for JavaScript and CSS files, e.g. remove <code>http:</code> and <code>https:</code> from URLs' , 'ezoic' ),
			array( $this, 'ezoic_disable_schemeless_urls_callback' ),
			'ezoic_speed_settings',
			'ezoic_speed_settings_section',
			array(
				__( 'Disable Schemeless URLs', 'ezoic' ),
			)
		);

		add_settings_field(
			'ezoic_remove_jquery_migrate',
			__( 'Remove jQuery Migrate' , 'ezoic' ),
			array( $this, 'ezoic_remove_jquery_migrate_callback' ),
			'ezoic_speed_settings',
			'ezoic_speed_settings_section',
			array(
				__( 'Remove jQuery Migrate', 'ezoic' ),
			)
		);

		add_settings_field(
			'ezoic_remove_feed_links',
			__( 'Remove feed links' , 'ezoic' ),
			array( $this, 'ezoic_remove_feed_links_callback' ),
			'ezoic_speed_settings',
			'ezoic_speed_settings_section',
			array(
				__( 'Remove feed links', 'ezoic' ),
			)
		);

		add_settings_field(
			'ezoic_remove_rsd_link',
			__( 'Remove RSD link' ),
			array( $this, 'ezoic_remove_rsd_link_callback' ),
			'ezoic_speed_settings',
			'ezoic_speed_settings_section',
			array(
				__( 'Remove RSD link', 'ezoic' ),
			)
		);

		add_settings_field(
			'ezoic_remove_wlwmanifest_link',
			__( 'Remove wlwmanifest link' ),
			array( $this, 'ezoic_remove_wlwmanifest_link_callback' ),
			'ezoic_speed_settings',
			'ezoic_speed_settings_section',
			array(
				__( 'Remove wlwmanifest link', 'ezoic' ),
			)
		);

		add_settings_field(
			'ezoic_remove_adjacent_posts_links',
			__( 'Remove adjacent posts links' ),
			array( $this, 'ezoic_remove_adjacent_posts_links_callback' ),
			'ezoic_speed_settings',
			'ezoic_speed_settings_section',
			array(
				__( 'Remove adjacent posts links', 'ezoic' ),
			)
		);

		add_settings_field(
			'ezoic_remove_wordpress_version_number',
			__( 'Remove WordPress version number' ),
			array( $this, 'ezoic_remove_wordpress_version_number_callback' ),
			'ezoic_speed_settings',
			'ezoic_speed_settings_section',
			array(
				__( 'Remove WordPress version number', 'ezoic' ),
			)
		);

		add_settings_field(
			'ezoic_remove_shortlink',
			__( 'Remove shortlink' ),
			array( $this, 'ezoic_remove_shortlink_callback' ),
			'ezoic_speed_settings',
			'ezoic_speed_settings_section',
			array(
				__( 'Remove shortlink', 'ezoic' ),
			)
		);

		// register_setting( 'ezoic_speed_settings', 'ezoic_disable_emojis' );
		// register_setting( 'ezoic_speed_settings', 'ezoic_schemeless_urls' );
		// register_setting( 'ezoic_speed_settings', 'ezoic_remove_jquery_migrate' );
		// register_setting( 'ezoic_speed_settings', 'ezoic_remove_feed_links' );
		register_setting( 'ezoic_speed_settings', 'ezoic_speed_settings' );
	}

	public function ezoic_speed_settings_callback() {
		echo '<p>' . __( 'These settings can improve your site\'s speed by removing or disabling certain features', 'ezoic' ) . '</p>';
		echo '<hr/>';
	}

	public function ezoic_disable_emojis_callback() {
		$html = '<input type="checkbox" value="1" name="ezoic_speed_settings[ezoic_disable_emojis]" ' . checked( self::is_setting_enabled( 'ezoic_disable_emojis' ), true, false ) . '>';

		echo $html;
	}

	public function ezoic_disable_schemeless_urls_callback() {
		$html = '<input type="checkbox" value="1" name="ezoic_speed_settings[ezoic_schemeless_urls]" ' . checked( self::is_setting_enabled( 'ezoic_schemeless_urls' ), true, false ) . '>';

		echo $html;
	}

	public function ezoic_remove_jquery_migrate_callback() {
		$html = '<input type="checkbox" value="1" name="ezoic_speed_settings[ezoic_remove_jquery_migrate]" ' . checked( self::is_setting_enabled( 'ezoic_remove_jquery_migrate' ), true, false ) . '>';

		echo $html;
	}

	public function ezoic_remove_feed_links_callback() {
		$html = '<input type="checkbox" value="1" name="ezoic_speed_settings[ezoic_remove_feed_links]" ' . checked( self::is_setting_enabled( 'ezoic_remove_feed_links' ), true, false ) . '>';

		echo $html;
	}

	public function ezoic_remove_rsd_link_callback() {
		$html = '<input type="checkbox" value="1" name="ezoic_speed_settings[ezoic_remove_rsd_link]" ' . checked( self::is_setting_enabled( 'ezoic_remove_rsd_link' ), true, false ) . '>';

		echo $html;
	}

	public function ezoic_remove_wlwmanifest_link_callback() {
		$html = '<input type="checkbox" value="1" name="ezoic_speed_settings[ezoic_remove_wlwmanifest_link]" ' . checked( self::is_setting_enabled( 'ezoic_remove_wlwmanifest_link' ), true, false ) . '>';

		echo $html;
	}
	
	public function ezoic_remove_adjacent_posts_links_callback() {
		$html = '<input type="checkbox" value="1" name="ezoic_speed_settings[ezoic_remove_adjacent_posts_links]" ' . checked( self::is_setting_enabled( 'ezoic_remove_adjacent_posts_links' ), true, false ) . '>';

		echo $html;
	}

	public function ezoic_remove_wordpress_version_number_callback() {
		$html = '<input type="checkbox" value="1" name="ezoic_speed_settings[ezoic_remove_wordpress_version_number]" ' . checked( self::is_setting_enabled( 'ezoic_remove_wordpress_version_number' ), true, false ) . '>';

		echo $html;
	}

	public function ezoic_remove_shortlink_callback() {
		$html = '<input type="checkbox" value="1" name="ezoic_speed_settings[ezoic_remove_shortlink]" ' . checked( self::is_setting_enabled( 'ezoic_remove_shortlink' ), true, false ) . '>';

		echo $html;
	}

	public function default_speed_settings() {
		$defaults = array(
			'ezoic_remove_feed_links' => "1",
			'ezoic_remove_rsd_link' => "1",
			'ezoic_remove_wlwmanifest_link' => "1",
			'ezoic_remove_adjacent_posts_links' => "1",
			'ezoic_remove_wordpress_version_number' => "1",
			'ezoic_remove_shortlink' => "1",
		);

		return $defaults;
	}

	public static function is_setting_enabled( $setting ) {
		$settings = get_option( 'ezoic_speed_settings', null );

		if ( ! is_array($settings) ) {
			return false;
		}

		if ( array_key_exists( $setting, $settings ) ) {
			return true;
		}

		return false;
	}

	public function disable_emojis() {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

		add_filter( 'tiny_mce_plugins', array( $this, 'disable_emojis_tinymce' ) );
		add_filter( 'wp_resource_hints', array( $this, 'remove_emojis_dns_prefetch' ), 10, 2 );
	}

	public function schemeless_urls( $url ) {
		return str_replace( [ 'https:', 'http:' ], '', $url );
	}

	public function remove_jquery_migrate( $scripts ) {
		if ( empty( $scripts->registered['jquery'] ) ) {
			return;
		}
		$script = $scripts->registered['jquery'];
		if ( $script->deps ) {
			$script->deps = array_diff( $script->deps, array( 'jquery-migrate' ) );
		}
	}

	public function remove_emojis_dns_prefetch( $urls, $relation_type ) {
		if ( 'dns-prefetch' !== $relation_type ) {
			return $urls;
		}
		return array_filter( $urls, function( $url ) {
			return false === strpos( $url, 'https://s.w.org/images/core/emoji/' );
		} );
	}

	public function disable_emojis_tinymce( $plugins ) {
		return is_array( $plugins ) ? array_diff( $plugins, ['wpemoji'] ) : [];
	}

	public function ezoic_header_cleanup() {
		if ( self::is_setting_enabled( 'ezoic_remove_feed_links' ) ) {
			remove_action( 'wp_head', 'feed_links', 2 );
			remove_action( 'wp_head', 'feed_links_extra', 3 );
		}

		if ( self::is_setting_enabled( 'ezoic_remove_rsd_link' ) ) {
			remove_action( 'wp_head', 'rsd_link' );
		}

		if ( self::is_setting_enabled( 'ezoic_remove_wlwmanifest_link' ) ) {
			remove_action( 'wp_head', 'wlwmanifest_link' );
		}

		if ( self::is_setting_enabled( 'ezoic_remove_adjacent_posts_links' ) ) {
			remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );
		}

		if ( self::is_setting_enabled( 'ezoic_remove_wordpress_version_number' ) ) {
			remove_action( 'wp_head', 'wp_generator' );
		}

		if ( self::is_setting_enabled( 'ezoic_remove_shortlink' ) ) {
			remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		}
	}
}
