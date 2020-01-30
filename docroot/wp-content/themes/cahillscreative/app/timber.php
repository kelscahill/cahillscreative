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

		/* Navigation */
		$context['primary_nav_left'] = new TimberMenu('primary_navigation_left');
		$context['primary_nav_right'] = new TimberMenu('primary_navigation_right');
		$context['footer_nav_col_1'] = new TimberMenu('footer_navigation_col_1');
		$context['footer_nav_col_2'] = new TimberMenu('footer_navigation_col_2');

		return $context;
	}
}

/**
 * Posts: 2 Posts
 */
$latest_posts_args = get_posts(array(
	'posts_per_page' => 2,
	'post_type' => 'post',
	'post_status' => 'publish',
	'orderby'	=> 'date',
	'order' => 'DESC',
));
$context['latest_posts'] = Timber::get_posts($latest_posts_args);

/**
 * Featured work: 2 posts
 */
$featured_work_args = array(
  'post_type' => 'work',
  'posts_per_page' => 2,
  'post_status' => 'publish',
  'order' => 'DESC',
  'tax_query' => array(
    array(
      'taxonomy' => 'post_tag',
      'field' => 'slug',
      'terms' => 'featured'
    )
  )
);
$context['featured_work'] = Timber::get_posts($featured_work_args);

/**
 * Featured posts: 4 posts not in health
 */
$featured_posts_args = array(
	'post_type' => 'post',
	'posts_per_page' => 4,
	'post_status' => 'publish',
	'order' => 'DESC',
	'tax_query' => array(
		array(
			'taxonomy' => 'category',
			'field' => 'slug',
			'terms' => 'health',
			'operator' => 'NOT IN'
		)
	)
);
$context['featured_posts'] = Timber::get_posts($featured_posts_args);

/**
 * Affilate posts: Category camper
 */
$affiliate_args = array(
 'post_type' => array(
	 'affiliate',
 ),
 'posts_per_page' => 12,
 'post_status' => 'publish',
 'order' => 'DESC',
 'tax_query' => array(
	 array(
		 'taxonomy' => 'post_tag',
		 'field' => 'slug',
		 'terms' => 'camper'
	 )
 )
);
$context['camper_posts'] = Timber::get_posts($affiliate_args);

/**
 * Affilate posts: Favorites
 */
$favorites_args = array(
 'post_type' => 'affiliate',
 'posts_per_page' => -1,
 'post_status' => 'publish',
 'order' => 'DESC',
 'tax_query' => array(
	 'relation' => 'AND',
	 array(
		 'taxonomy' => 'post_tag',
		 'field' => 'slug',
		 'terms' => 'favorite'
	 ),
	 array(
		 'taxonomy' => 'category',
		 'field' => 'slug',
		 'terms' => get_the_category()[0]->slug
	 )
 )
);
$context['favorites'] = Timber::get_posts($favorites_args);

$menu_args_left_mobile = array(
	'echo' => false,
	'menu_class' => 'primary-nav__list',
	'container' => false,
	'depth' => 2,
	'theme_location' => 'primary_navigation_left',
);

$menu_args_right_mobile = array(
	'echo' => false,
	'menu_class' => 'primary-nav__list',
	'container' => false,
	'depth' => 2,
	'theme_location' => 'primary_navigation_right',
);

// Native WordPress menu classes to be replaced.
$replace_mobile = array(
	'menu-item ',
	'sub-menu',
	'menu-item-has-children',
	'<a',
);
// Custom ALPS classes to replace.
$replace_with_mobile = array(
	'primary-nav__list-item rel ',
	'primary-nav__subnav-list',
	'primary-nav--with-subnav js-toggle',
	'<a class="primary-nav__link" ',
);

$context['primary_nav_left_mobile'] = str_replace($replace_mobile, $replace_wit_mobileh, wp_nav_menu($menu_args_left_mobile));
$context['primary_nav_right_mobile'] = str_replace($replace_mobile, $replace_with_mobile, wp_nav_menu($menu_args_right_mobile));


$menu_args_left = array(
	'echo' => false,
	'menu_class' => 'primary-nav__list',
	'container' => false,
	'depth' => 2,
	'theme_location' => 'primary_navigation_left',
);

$menu_args_right = array(
	'echo' => false,
	'menu_class' => 'primary-nav__list',
	'container' => false,
	'depth' => 2,
	'theme_location' => 'primary_navigation_right',
);

// Native WordPress menu classes to be replaced.
$replace = array(
	'menu-item ',
	'sub-menu',
	'menu-item-has-children',
	'<a',
);
// Custom ALPS classes to replace.
$replace_with = array(
	'primary-nav__list-item rel ',
	'primary-nav__subnav-list',
	'primary-nav--with-subnav js-hover',
	'<a class="primary-nav__link" ',
);

$context['primary_nav_left'] = str_replace($replace, $replace_with, wp_nav_menu($menu_args_left));
$context['primary_nav_right'] = str_replace($replace, $replace_with, wp_nav_menu($menu_args_right));

$context['post']['previous'] = get_previous_post(true, '', 'category');
$context['post']['next'] = get_next_post(true, '', 'category');

new SageTimberTheme();