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

		/* Navigation */
		$context['primary_nav_left'] = new TimberMenu('primary_nav_left');
		$context['primary_nav_right'] = new TimberMenu('primary_nav_right');
		$context['footer_nav_col_1'] = new TimberMenu('footer_nav_col_1');
		$context['footer_nav_col_2'] = new TimberMenu('footer_nav_col_2');

		/* Site info */
		$context['site'] = $this;

		/* Site info */
		$context['sidebar_primary'] = Timber::get_widgets('sidebar-primary');

		/* Functions */
		$context['is_main_site'] = TimberHelper::ob_function('is_main_site');
		$context['related_posts'] = TimberHelper::ob_function('related_posts');

		if (is_main_site()) {
			$context['work_category'] = "Work";
			$context['gtm_id'] = "GTM-KX6K5Q";
		} else {
			$context['work_category'] = "Rental";
			$context['gtm_id'] = "GTM-MM3WF6J";
		}

		/* Get Posts */
		$args = array(
		  'post_type' => 'post',
		  'posts_per_page' => 2,
		  'post_status' => 'publish',
		  'order' => 'DESC',
		);
		$context['latest_posts'] = Timber::query_posts($args);

		/* Get Terms */
		// $context['term_projects'] = Timber::get_terms('project');
		// $context['term_room'] = Timber::get_terms('room');
		// $context['term_cost'] = Timber::get_terms('cost');
		// $context['term_store'] = Timber::get_terms('store');

		return $context;
	}
}
new SageTimberTheme();