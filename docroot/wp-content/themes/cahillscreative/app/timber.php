<?php

/**
 * If you are installing Timber as a Composer dependency in your theme, you'll need this block
 * to load your dependencies and initialize Timber. If you are using Timber via the WordPress.org
 * plug-in, you can safely delete this block.
 */
$composer_autoload = dirname(__DIR__) . '/vendor/autoload.php';
if ( file_exists( $composer_autoload ) ) {
	require_once $composer_autoload;
	$timber = new Timber\Timber();
}

// Check if Timber is not activated
if ( ! class_exists( 'Timber' ) ) {

	add_action( 'admin_notices', function() {
		echo '<div class="error"><p>Timber not activated. Make sure you activate the plugin.</p></div>';
	} );
	return;

}

// Add the directory of patterns in include path
Timber::$dirname = array('_patterns');

/**
 * Extend TimberSite with site wide properties
 */
class SageTimberTheme extends TimberSite {

	function __construct() {
		add_filter( 'timber_context', array( $this, 'add_to_context' ) );
		parent::__construct();
	}

	function add_to_context( $context ) {

		/* Menu */
		$context['menu'] = new TimberMenu();

		/* Site info */
		$context['site'] = $this;

		/* Site info */
		$context['sidebar_primary'] = Timber::get_widgets('sidebar-primary');

		return $context;
	}
}
new SageTimberTheme();