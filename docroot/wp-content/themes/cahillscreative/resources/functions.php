<?php

/**
 * Do not edit anything in this file unless you know what you're doing
 */

/**
 * Helper function for prettying up errors
 * @param string $message
 * @param string $subtitle
 * @param string $title
 */
$sage_error = function ($message, $subtitle = '', $title = '') {
    $title = $title ?: __('Sage &rsaquo; Error', 'sage');
    $footer = '<a href="https://roots.io/sage/docs/">roots.io/sage/docs/</a>';
    $message = "<h1>{$title}<br><small>{$subtitle}</small></h1><p>{$message}</p><p>{$footer}</p>";
    wp_die($message, $title);
};

/**
 * Ensure compatible version of PHP is used
 */
if (version_compare('5.6.4', phpversion(), '>=')) {
    $sage_error(__('You must be using PHP 5.6.4 or greater.', 'sage'), __('Invalid PHP version', 'sage'));
}

/**
 * Ensure compatible version of WordPress is used
 */
if (version_compare('4.7.0', get_bloginfo('version'), '>=')) {
    $sage_error(__('You must be using WordPress 4.7.0 or greater.', 'sage'), __('Invalid WordPress version', 'sage'));
}

/**
 * Ensure dependencies are loaded
 */
if (!class_exists('Roots\\Sage\\Container')) {
    if (!file_exists($composer = __DIR__.'/../vendor/autoload.php')) {
        $sage_error(
            __('You must run <code>composer install</code> from the Sage directory.', 'sage'),
            __('Autoloader not found.', 'sage')
        );
    }
    require_once $composer;
}

remove_filter('term_description','wpautop');

/**
 * Load ajax script on news template
 */
function enqueue_ajax_load_more() {
   wp_enqueue_script('ajax-load-more'); // Already registered, just needs to be enqueued
}
add_action('wp_enqueue_scripts', 'enqueue_ajax_load_more');

/**
 * Blog Filter
 */
function misha_filter_function(){

  $args = array(
  	'post_type' => 'post',
    'orderby' => 'date',
  );

  if( isset( $_POST['projects'] ) )
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'projects',
				'field' => 'term_id',
				'terms' => array($_POST['projects']),
        'operator' => 'IN',
			)
		);

	$query = new WP_Query( $args );

	if( $query->have_posts() ) :
		while( $query->have_posts() ): $query->the_post();
			echo '<h2>' . $query->post->post_title . '</h2>';
      echo $_POST['projects'];
		endwhile;
		wp_reset_postdata();
	else :
		echo 'No posts found';
	endif;

	die();
}

add_action('wp_ajax_myfilter', 'misha_filter_function');
add_action('wp_ajax_nopriv_myfilter', 'misha_filter_function');


/**
 * Add excerpt to pages
 */
add_post_type_support( 'page', 'excerpt' );

/**
 * Sage required files
 *
 * The mapped array determines the code library included in your theme.
 * Add or remove files to the array as needed. Supports child theme overrides.
 */
array_map(function ($file) use ($sage_error) {
    $file = "../app/{$file}.php";
    if (!locate_template($file, true, true)) {
        $sage_error(sprintf(__('Error locating <code>%s</code> for inclusion.', 'sage'), $file), 'File not found');
    }
}, ['helpers', 'setup', 'filters', 'admin']);

/**
 * Here's what's happening with these hooks:
 * 1. WordPress initially detects theme in themes/sage/resources
 * 2. Upon activation, we tell WordPress that the theme is actually in themes/sage/resources/views
 * 3. When we call get_template_directory() or get_template_directory_uri(), we point it back to themes/sage/resources
 *
 * We do this so that the Template Hierarchy will look in themes/sage/resources/views for core WordPress themes
 * But functions.php, style.css, and index.php are all still located in themes/sage/resources
 *
 * This is not compatible with the WordPress Customizer theme preview prior to theme activation
 *
 * get_template_directory()   -> /srv/www/example.com/current/web/app/themes/sage/resources
 * get_stylesheet_directory() -> /srv/www/example.com/current/web/app/themes/sage/resources
 * locate_template()
 * ├── STYLESHEETPATH         -> /srv/www/example.com/current/web/app/themes/sage/resources/views
 * └── TEMPLATEPATH           -> /srv/www/example.com/current/web/app/themes/sage/resources
 */
if (is_customize_preview() && isset($_GET['theme'])) {
    $sage_error(__('Theme must be activated prior to using the customizer.', 'sage'));
}
$sage_views = basename(dirname(__DIR__)).'/'.basename(__DIR__).'/views';
add_filter('stylesheet', function () use ($sage_views) {
    return dirname($sage_views);
});
add_filter('stylesheet_directory_uri', function ($uri) {
    return dirname($uri);
});
if ($sage_views !== get_option('stylesheet')) {
    update_option('stylesheet', $sage_views);
    if (php_sapi_name() === 'cli') {
        return;
    }
    wp_redirect($_SERVER['REQUEST_URI']);
    exit();
}

/**
 * Allow SVG's through WP media uploader
 */
function cc_mime_types($mimes) {
  $mimes['svg'] = 'image/svg+xml';
  return $mimes;
}
add_filter('upload_mimes', 'cc_mime_types');

/**
 * Post Types
 */

 function cptui_register_my_taxes() {

	/**
	 * Taxonomy: Room.
	 */

	$labels = array(
		"name" => __( "Room", "sage" ),
		"singular_name" => __( "Room", "sage" ),
	);

	$args = array(
		"label" => __( "Room", "sage" ),
		"labels" => $labels,
		"public" => true,
		"hierarchical" => false,
		"label" => "Room",
		"show_ui" => true,
		"show_in_menu" => true,
		"show_in_nav_menus" => true,
		"query_var" => true,
		"rewrite" => array( 'slug' => 'room', 'with_front' => true, ),
		"show_admin_column" => false,
		"show_in_rest" => false,
		"rest_base" => "",
		"show_in_quick_edit" => false,
	);
	register_taxonomy( "room", array( "post" ), $args );

	/**
	 * Taxonomy: Cost.
	 */

	$labels = array(
		"name" => __( "Cost", "sage" ),
		"singular_name" => __( "Cost", "sage" ),
	);

	$args = array(
		"label" => __( "Cost", "sage" ),
		"labels" => $labels,
		"public" => true,
		"hierarchical" => false,
		"label" => "Cost",
		"show_ui" => true,
		"show_in_menu" => true,
		"show_in_nav_menus" => true,
		"query_var" => true,
		"rewrite" => array( 'slug' => 'cost', 'with_front' => true, ),
		"show_admin_column" => false,
		"show_in_rest" => false,
		"rest_base" => "",
		"show_in_quick_edit" => false,
	);
	register_taxonomy( "cost", array( "post" ), $args );

	/**
	 * Taxonomy: Projects.
	 */

	$labels = array(
		"name" => __( "Project", "sage" ),
		"singular_name" => __( "Project", "sage" ),
	);

	$args = array(
		"label" => __( "Project", "sage" ),
		"labels" => $labels,
		"public" => true,
		"hierarchical" => false,
		"label" => "Projects",
		"show_ui" => true,
		"show_in_menu" => true,
		"show_in_nav_menus" => true,
		"query_var" => true,
		"rewrite" => array( 'slug' => 'project', 'with_front' => true, ),
		"show_admin_column" => false,
		"show_in_rest" => false,
		"rest_base" => "",
		"show_in_quick_edit" => false,
	);
	register_taxonomy( "project", array( "post" ), $args );

  /**
	 * Taxonomy: Store.
	 */

	$labels = array(
		"name" => __( "Store", "sage" ),
		"singular_name" => __( "Store", "sage" ),
	);

	$args = array(
		"label" => __( "Store", "sage" ),
		"labels" => $labels,
		"public" => true,
		"hierarchical" => false,
		"label" => "Store",
		"show_ui" => true,
		"show_in_menu" => true,
		"show_in_nav_menus" => true,
		"query_var" => true,
		"rewrite" => array( 'slug' => 'store', 'with_front' => true, ),
		"show_admin_column" => false,
		"show_in_rest" => false,
		"rest_base" => "",
		"show_in_quick_edit" => false,
	);
	register_taxonomy( "store", array( "affiliate" ), $args );

	/**
	 * Taxonomy: Skill Levels.
	 */

	$labels = array(
		"name" => __( "Skill Level", "sage" ),
		"singular_name" => __( "Skill Level", "sage" ),
	);

	$args = array(
		"label" => __( "Skill Level", "sage" ),
		"labels" => $labels,
		"public" => true,
		"hierarchical" => false,
		"label" => "Skill Levels",
		"show_ui" => true,
		"show_in_menu" => true,
		"show_in_nav_menus" => true,
		"query_var" => true,
		"rewrite" => array( 'slug' => 'skill_level', 'with_front' => true, ),
		"show_admin_column" => false,
		"show_in_rest" => false,
		"rest_base" => "",
		"show_in_quick_edit" => false,
	);
	register_taxonomy( "skill_level", array( "post" ), $args );
}

add_action( 'init', 'cptui_register_my_taxes' );

function cptui_register_my_cpts() {

	/**
	 * Post Type: Affiliates.
	 */

	$labels = array(
		"name" => __( "Affiliates", "sage" ),
		"singular_name" => __( "Affiliates", "sage" ),
		"menu_name" => __( "Affiliates", "sage" ),
		"all_items" => __( "All Affiliates", "sage" ),
		"add_new" => __( "Add New Affiliate", "sage" ),
		"add_new_item" => __( "Add New Affiliate Item", "sage" ),
		"edit_item" => __( "Edit Affiliate", "sage" ),
		"new_item" => __( "New Affiliate", "sage" ),
		"view_item" => __( "View Affiliate", "sage" ),
		"view_items" => __( "View Affiliates", "sage" ),
		"search_items" => __( "Search Affiliates", "sage" ),
		"not_found" => __( "No Affiliates Found", "sage" ),
		"not_found_in_trash" => __( "No Affiliates Found in Trash", "sage" ),
		"parent_item_colon" => __( "Parent Affiliate", "sage" ),
		"parent_item_colon" => __( "Parent Affiliate", "sage" ),
	);

	$args = array(
		"label" => __( "Affiliates", "sage" ),
		"labels" => $labels,
		"description" => "",
		"public" => true,
		"publicly_queryable" => true,
		"show_ui" => true,
		"show_in_rest" => false,
		"rest_base" => "",
		"has_archive" => false,
		"show_in_menu" => true,
		"exclude_from_search" => false,
		"capability_type" => "post",
		"map_meta_cap" => true,
		"hierarchical" => false,
		"rewrite" => array( "slug" => "affiliate", "with_front" => false ),
		"query_var" => true,
		"menu_icon" => "dashicons-cart",
		"supports" => array( "title", "editor", "thumbnail", "excerpt" ),
		"taxonomies" => array( "category", "post_tag" ),
	);

	register_post_type( "affiliate", $args );

/**
 * Post Type: Work.
 */

  $labels = array(
    "name" => __( "Work", "sage" ),
    "singular_name" => __( "Work", "sage" ),
    "menu_name" => __( "Work", "sage" ),
    "all_items" => __( "All Work", "sage" ),
    "add_new" => __( "Add New Work", "sage" ),
    "add_new_item" => __( "Add New Work Item", "sage" ),
    "edit_item" => __( "Edit Work", "sage" ),
    "new_item" => __( "New Work", "sage" ),
    "view_item" => __( "View Work", "sage" ),
    "view_items" => __( "View Work", "sage" ),
    "search_items" => __( "Search Work", "sage" ),
    "not_found" => __( "No Work Found", "sage" ),
    "not_found_in_trash" => __( "No Work Found in Trash", "sage" ),
    "parent_item_colon" => __( "Parent Work", "sage" ),
    "parent_item_colon" => __( "Parent Work", "sage" ),
  );

  $args = array(
    "label" => __( "Work", "sage" ),
    "labels" => $labels,
    "description" => "",
    "public" => true,
    "publicly_queryable" => true,
    "show_ui" => true,
    "show_in_rest" => false,
    "rest_base" => "",
    "has_archive" => true,
    "show_in_menu" => true,
    "exclude_from_search" => false,
    "capability_type" => "post",
    "map_meta_cap" => true,
    "hierarchical" => false,
    "rewrite" => array( "slug" => "work", "with_front" => true ),
    "query_var" => true,
    "menu_icon" => "dashicons-format-gallery",
    "supports" => array( "title", "editor", "thumbnail", "excerpt" ),
    "taxonomies" => array( "post_tag", "category" ),
  );

  register_post_type( "work", $args );
}

add_action( 'init', 'cptui_register_my_cpts' );
