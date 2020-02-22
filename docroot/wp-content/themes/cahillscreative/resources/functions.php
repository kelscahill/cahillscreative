<?php
/**
 * Do not edit anything in this file unless you know what you're doing
 */

use Roots\Sage\Config;
use Roots\Sage\Container;

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
if (version_compare('7', phpversion(), '>=')) {
  $sage_error(__('You must be using PHP 7 or greater.', 'sage'), __('Invalid PHP version', 'sage'));
}

/**
 * Ensure compatible version of WordPress is used
 */
if (version_compare('5.0.0', get_bloginfo('version'), '>=')) {
  $sage_error(__('You must be using WordPress 5.0.0 or greater.', 'sage'), __('Invalid WordPress version', 'sage'));
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
}, ['helpers', 'setup', 'filters', 'admin', 'timber']);

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
array_map(
  'add_filter',
  ['theme_file_path', 'theme_file_uri', 'parent_theme_file_path', 'parent_theme_file_uri'],
  array_fill(0, 4, 'dirname')
);
Container::getInstance()
  ->bindIf('config', function () {
    return new Config([
      'assets' => require dirname(__DIR__).'/config/assets.php',
      'theme' => require dirname(__DIR__).'/config/theme.php',
      'view' => require dirname(__DIR__).'/config/view.php',
    ]);
  }, true);

/**
 * Allow SVG's through WP media uploader
 */
function cc_mime_types($mimes) {
  $mimes['svg'] = 'image/svg+xml';
  return $mimes;
}
add_filter('upload_mimes', 'cc_mime_types');

/**
 * ACF Options Page
 */
if (function_exists('acf_add_options_page')) {
  acf_add_options_page(array(
    'page_title'  => 'Theme General Settings',
    'menu_title'  => 'Theme Settings',
    'menu_slug'   => 'theme-general-settings',
    'capability'  => 'edit_posts',
    'redirect'    => false
  ));
}

function acf_timber_context( $context ) {
  $context['options'] = get_fields('option');
  return $context;
}
add_filter('timber_context', 'acf_timber_context');

/**
 * Save ACF's to acf_json folder in /resources
 */
// function my_acf_json_save_point( $path ) {
//   $path = get_stylesheet_directory() . '/acf-json';
//   // return
//   return $path;
// }
// add_filter('acf/settings/save_json', 'my_acf_json_save_point');

/**
 * Change Term Description
 */
remove_filter('term_description','wpautop');

/**
 * Add excerpt to pages
 */
add_post_type_support( 'page', 'excerpt' );

/**
 * Load ajax script on news template
 */
// function enqueue_ajax_load_more() {
//    wp_enqueue_script('ajax-load-more'); // Already registered, just needs to be enqueued
// }
// add_action('wp_enqueue_scripts', 'enqueue_ajax_load_more');

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
    "show_in_rest" => true,
    "rest_base" => "",
    "has_archive" => false,
    "show_in_menu" => true,
    "exclude_from_search" => false,
    "capability_type" => "post",
    "map_meta_cap" => true,
    "hierarchical" => false,
    "rewrite" => array( "slug" => "affiliate", "with_front" => true ),
    "query_var" => true,
    "menu_icon" => "dashicons-cart",
    "supports" => array( "title", "editor", "thumbnail", "excerpt" ),
    "taxonomies" => array( "category", "post_tag" ),
  );

  register_post_type( "affiliate", $args );

if (is_main_site()) {
  $work_slug = 'work';
} else {
  $work_slug = 'rentals';
}

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
    "show_in_rest" => true,
    "rest_base" => "",
    "has_archive" => false,
    "show_in_menu" => true,
    "exclude_from_search" => false,
    "capability_type" => "post",
    "map_meta_cap" => true,
    "hierarchical" => false,
    "rewrite" => array( "slug" => $work_slug, "with_front" => true ),
    "query_var" => true,
    "menu_icon" => "dashicons-format-gallery",
    "supports" => array( "title", "editor", "thumbnail", "excerpt" ),
    "taxonomies" => array( "post_tag", "category" ),
  );

  register_post_type( "work", $args );
}

add_action( 'init', 'cptui_register_my_cpts' );

if( function_exists('acf_add_local_field_group') ):

  // Theme Settings
  acf_add_local_field_group(array(
  	'key' => 'group_5e3368ac5da9c',
  	'title' => 'Theme Settings',
  	'fields' => array(
  		array(
  			'key' => 'branding_tab',
  			'label' => 'Branding',
  			'name' => '',
  			'type' => 'tab',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'placement' => 'top',
  			'endpoint' => 0,
  		),
  		array(
  			'key' => 'field_5e33691bf6719',
  			'label' => 'Logo',
  			'name' => 'logo',
  			'type' => 'image',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'return_format' => 'array',
  			'preview_size' => 'thumbnail',
  			'library' => 'all',
  			'min_width' => '',
  			'min_height' => '',
  			'min_size' => '',
  			'max_width' => '',
  			'max_height' => '',
  			'max_size' => '',
  			'mime_types' => '',
  		),
  		array(
  			'key' => 'field_5e33692df671a',
  			'label' => 'Favicon',
  			'name' => 'favicon',
  			'type' => 'image',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'return_format' => 'array',
  			'preview_size' => 'thumbnail',
  			'library' => 'all',
  			'min_width' => '',
  			'min_height' => '',
  			'min_size' => '',
  			'max_width' => '',
  			'max_height' => '',
  			'max_size' => '',
  			'mime_types' => '',
  		),
  		array(
  			'key' => 'field_5e336938f671b',
  			'label' => 'Primary Color',
  			'name' => 'primary_color',
  			'type' => 'color_picker',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '33',
  				'class' => '',
  				'id' => '',
  			),
  			'default_value' => '#393939',
  		),
  		array(
  			'key' => 'field_5e336946f671c',
  			'label' => 'Secondary Color',
  			'name' => 'secondary_color',
  			'type' => 'color_picker',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '33',
  				'class' => '',
  				'id' => '',
  			),
  			'default_value' => '#393939',
  		),
      array(
  			'key' => 'field_5e336945g671c',
  			'label' => 'Tertiary Color',
  			'name' => 'tertiary_color',
  			'type' => 'color_picker',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '33',
  				'class' => '',
  				'id' => '',
  			),
  			'default_value' => '#393939',
  		),
  		array(
  			'key' => 'social_links_tab',
  			'label' => 'Social Links',
  			'name' => '',
  			'type' => 'tab',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'placement' => 'top',
  			'endpoint' => 0,
  		),
  		array(
  			'key' => 'field_5e336968f671e',
  			'label' => 'Facebook Url',
  			'name' => 'facebook_url',
  			'type' => 'url',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'default_value' => '',
  			'placeholder' => '',
  		),
  		array(
  			'key' => 'field_5e33696ef671f',
  			'label' => 'Instagram Url',
  			'name' => 'instagram_url',
  			'type' => 'url',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'default_value' => '',
  			'placeholder' => '',
  		),
  		array(
  			'key' => 'field_5e336976f6720',
  			'label' => 'Pinterest Url',
  			'name' => 'pinterest_url',
  			'type' => 'url',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'default_value' => '',
  			'placeholder' => '',
  		),
  		array(
  			'key' => 'field_5e336996f6721',
  			'label' => 'Mailchimp Url',
  			'name' => 'mailchimp_url',
  			'type' => 'url',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'default_value' => '',
  			'placeholder' => '',
  		),
      array(
  			'key' => 'modal_tab',
  			'label' => 'Modal',
  			'name' => '',
  			'type' => 'tab',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'placement' => 'top',
  			'endpoint' => 0,
  		),
      array(
  			'key' => 'field_59fa3338fd311',
  			'label' => 'Modal Kicker',
  			'name' => 'modal_kicker',
  			'type' => 'text',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'default_value' => '',
  			'placeholder' => '',
  			'prepend' => '',
  			'append' => '',
  			'maxlength' => '',
  			'readonly' => 0,
  			'disabled' => 0,
  		),
  		array(
  			'key' => 'field_59fa31d814228',
  			'label' => 'Modal Title',
  			'name' => 'modal_title',
  			'type' => 'text',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'default_value' => '',
  			'placeholder' => '',
  			'prepend' => '',
  			'append' => '',
  			'maxlength' => '',
  			'readonly' => 0,
  			'disabled' => 0,
  		),
  		array(
  			'key' => 'field_59fa320714229',
  			'label' => 'Modal Description',
  			'name' => 'modal_description',
  			'type' => 'textarea',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'default_value' => '',
  			'placeholder' => '',
  			'maxlength' => '',
  			'rows' => '',
  			'new_lines' => 'wpautop',
  			'readonly' => 0,
  			'disabled' => 0,
  		),
  		array(
  			'key' => 'field_59fa325c1422a',
  			'label' => 'Modal Embed Code',
  			'name' => 'modal_embed_code',
  			'type' => 'textarea',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'default_value' => '',
  			'placeholder' => '',
  			'maxlength' => '',
  			'rows' => '',
  			'new_lines' => 'br',
  			'readonly' => 0,
  			'disabled' => 0,
  		),
  	),
  	'location' => array(
  		array(
  			array(
  				'param' => 'options_page',
  				'operator' => '==',
  				'value' => 'theme-general-settings',
  			),
  		),
  	),
  	'menu_order' => 0,
  	'position' => 'normal',
  	'style' => 'default',
  	'label_placement' => 'top',
  	'instruction_placement' => 'label',
  	'hide_on_screen' => '',
  	'active' => 1,
  	'description' => '',
  ));

  // Page Settings
  acf_add_local_field_group(array(
  	'key' => 'group_59df823bcb67c',
  	'title' => 'Page Settings',
  	'fields' => array(
  		array(
  			'key' => 'field_5e402e219e0ae',
  			'label' => 'Kicker',
  			'name' => 'kicker',
  			'type' => 'text',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'default_value' => '',
  			'placeholder' => '',
  			'prepend' => '',
  			'append' => '',
  			'maxlength' => '',
  		),
  		array(
  			'key' => 'field_59df8240d11ef',
  			'label' => 'Display Title',
  			'name' => 'display_title',
  			'type' => 'text',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'default_value' => '',
  			'placeholder' => '',
  			'prepend' => '',
  			'append' => '',
  			'maxlength' => '',
  			'readonly' => 0,
  			'disabled' => 0,
  		),
  		array(
  			'key' => 'field_59e7f09581902',
  			'label' => 'Intro',
  			'name' => 'intro',
  			'type' => 'wysiwyg',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'default_value' => '',
  			'tabs' => 'all',
  			'toolbar' => 'full',
  			'media_upload' => 0,
  			'delay' => 0,
  		),
  		array(
  			'key' => 'field_5e4728355a930',
  			'label' => 'Hero',
  			'name' => 'hero',
  			'type' => 'true_false',
  			'instructions' => 'Check to display the page header as a hero.',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'message' => '',
  			'default_value' => 0,
  			'ui' => 0,
  			'ui_on_text' => '',
  			'ui_off_text' => '',
  		),
  	),
  	'location' => array(
  		array(
  			array(
  				'param' => 'post_type',
  				'operator' => '==',
  				'value' => 'page',
  			),
  		),
  	),
  	'menu_order' => 0,
  	'position' => 'normal',
  	'style' => 'default',
  	'label_placement' => 'top',
  	'instruction_placement' => 'label',
  	'hide_on_screen' => '',
  	'active' => 1,
  	'description' => '',
  ));

  // Page Sections
  acf_add_local_field_group(array(
  	'key' => 'group_5e472cab5fba5',
  	'title' => 'Page Sections',
  	'fields' => array(
  		array(
  			'key' => 'field_5e472dbeb3b00',
  			'label' => 'Page Sections',
  			'name' => 'page_sections',
  			'type' => 'flexible_content',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'layouts' => array(
  				'5e472df81e3d7' => array(
  					'key' => '5e472df81e3d7',
  					'name' => 'promo_blocks_section',
  					'label' => 'Promo Blocks',
  					'display' => 'row',
  					'sub_fields' => array(
              array(
  							'key' => 'field_5e4c7b554e64e',
  							'label' => 'Promo Blocks Kicker',
  							'name' => 'promo_blocks_kicker',
  							'type' => 'text',
  							'instructions' => '',
  							'required' => 0,
  							'conditional_logic' => 0,
  							'wrapper' => array(
  								'width' => '',
  								'class' => '',
  								'id' => '',
  							),
  							'default_value' => '',
  							'placeholder' => '',
  							'prepend' => '',
  							'append' => '',
  							'maxlength' => '',
  						),
  						array(
  							'key' => 'field_5e4c7b604e64f',
  							'label' => 'Promo Blocks Title',
  							'name' => 'promo_blocks_title',
  							'type' => 'wysiwyg',
  							'instructions' => '',
  							'required' => 0,
  							'conditional_logic' => 0,
  							'wrapper' => array(
  								'width' => '',
  								'class' => '',
  								'id' => '',
  							),
  							'default_value' => '',
  							'tabs' => 'all',
  							'toolbar' => 'full',
  							'media_upload' => 0,
  							'delay' => 0,
  						),
  						array(
  							'key' => 'field_5e472e05b3b01',
  							'label' => 'Promo Blocks',
  							'name' => 'promo_blocks',
  							'type' => 'repeater',
  							'instructions' => '',
  							'required' => 0,
  							'conditional_logic' => 0,
  							'wrapper' => array(
  								'width' => '',
  								'class' => '',
  								'id' => '',
  							),
  							'collapsed' => '',
  							'min' => 0,
  							'max' => 0,
  							'layout' => 'row',
  							'button_label' => 'Add Block',
  							'sub_fields' => array(
  								array(
  									'key' => 'field_5e472cb883196',
  									'label' => 'Promo Icon',
  									'name' => 'promo_icon',
  									'type' => 'image',
  									'instructions' => '',
  									'required' => 0,
  									'conditional_logic' => 0,
  									'wrapper' => array(
  										'width' => '',
  										'class' => '',
  										'id' => '',
  									),
  									'return_format' => 'array',
  									'preview_size' => 'thumbnail',
  									'library' => 'all',
  									'min_width' => '',
  									'min_height' => '',
  									'min_size' => '',
  									'max_width' => '',
  									'max_height' => '',
  									'max_size' => '',
  									'mime_types' => '',
  								),
  								array(
  									'key' => 'field_5e472cb183195',
  									'label' => 'Promo Title',
  									'name' => 'promo_title',
  									'type' => 'text',
  									'instructions' => '',
  									'required' => 0,
  									'conditional_logic' => 0,
  									'wrapper' => array(
  										'width' => '',
  										'class' => '',
  										'id' => '',
  									),
  									'default_value' => '',
  									'placeholder' => '',
  									'prepend' => '',
  									'append' => '',
  									'maxlength' => '',
  								),
  								array(
  									'key' => 'field_5e472cdd83197',
  									'label' => 'Promo Description',
  									'name' => 'promo_description',
                    'type' => 'wysiwyg',
      							'instructions' => '',
      							'required' => 0,
      							'conditional_logic' => 0,
      							'wrapper' => array(
      								'width' => '',
      								'class' => '',
      								'id' => '',
      							),
      							'default_value' => '',
      							'tabs' => 'all',
      							'toolbar' => 'full',
      							'media_upload' => 0,
      							'delay' => 0,
  								),
  								array(
  									'key' => 'field_5e472ceb83198',
  									'label' => 'Promo Link',
  									'name' => 'promo_link',
  									'type' => 'link',
  									'instructions' => '',
  									'required' => 0,
  									'conditional_logic' => 0,
  									'wrapper' => array(
  										'width' => '',
  										'class' => '',
  										'id' => '',
  									),
  									'return_format' => 'array',
  								),
  							),
  						),
              array(
  							'key' => 'field_5e3c7b204e64f',
  							'label' => 'Promo Blocks Description',
  							'name' => 'promo_blocks_description',
  							'type' => 'wysiwyg',
  							'instructions' => '',
  							'required' => 0,
  							'conditional_logic' => 0,
  							'wrapper' => array(
  								'width' => '',
  								'class' => '',
  								'id' => '',
  							),
  							'default_value' => '',
  							'tabs' => 'all',
  							'toolbar' => 'full',
  							'media_upload' => 0,
  							'delay' => 0,
  						),
  						array(
  							'key' => 'field_5e4c7b864e650',
  							'label' => 'Promo Blocks CTA',
  							'name' => 'promo_blocks_cta',
  							'type' => 'link',
  							'instructions' => '',
  							'required' => 0,
  							'conditional_logic' => 0,
  							'wrapper' => array(
  								'width' => '',
  								'class' => '',
  								'id' => '',
  							),
  							'return_format' => 'array',
  						),
  					),
  					'min' => '',
  					'max' => '',
  				),
  				'layout_5e4c78f28db0d' => array(
  					'key' => 'layout_5e4c78f28db0d',
  					'name' => 'accordion_section',
  					'label' => 'Accordion',
  					'display' => 'row',
  					'sub_fields' => array(
  						array(
  							'key' => 'field_5e4c79168db0e',
  							'label' => 'Accordion Section Kicker',
  							'name' => 'accordion_section_kicker',
  							'type' => 'text',
  							'instructions' => '',
  							'required' => 0,
  							'conditional_logic' => 0,
  							'wrapper' => array(
  								'width' => '',
  								'class' => '',
  								'id' => '',
  							),
  							'default_value' => '',
  							'placeholder' => '',
  							'prepend' => '',
  							'append' => '',
  							'maxlength' => '',
  						),
  						array(
  							'key' => 'field_5e4c792e8db0f',
  							'label' => 'Accordion Section Title',
  							'name' => 'accordion_section_title',
                'type' => 'text',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                  'width' => '',
                  'class' => '',
                  'id' => '',
                ),
                'default_value' => '',
                'placeholder' => '',
                'prepend' => '',
                'append' => '',
                'maxlength' => '',
  						),
  						array(
  							'key' => 'field_5e4c78648db07',
  							'label' => 'Accordion',
  							'name' => 'accordion',
  							'type' => 'repeater',
  							'instructions' => '',
  							'required' => 0,
  							'conditional_logic' => 0,
  							'wrapper' => array(
  								'width' => '',
  								'class' => '',
  								'id' => '',
  							),
  							'collapsed' => '',
  							'min' => 0,
  							'max' => 0,
  							'layout' => 'row',
  							'button_label' => '',
  							'sub_fields' => array(
  								array(
  									'key' => 'field_5e4c78878db08',
  									'label' => 'Accordion Title',
  									'name' => 'accordion_title',
  									'type' => 'text',
  									'instructions' => '',
  									'required' => 0,
  									'conditional_logic' => 0,
  									'wrapper' => array(
  										'width' => '',
  										'class' => '',
  										'id' => '',
  									),
  									'default_value' => '',
  									'placeholder' => '',
  									'prepend' => '',
  									'append' => '',
  									'maxlength' => '',
  								),
  								array(
  									'key' => 'field_5e4c788f8db09',
  									'label' => 'Accordion Description',
  									'name' => 'accordion_description',
  									'type' => 'wysiwyg',
  									'instructions' => '',
  									'required' => 0,
  									'conditional_logic' => 0,
  									'wrapper' => array(
  										'width' => '',
  										'class' => '',
  										'id' => '',
  									),
  									'default_value' => '',
  									'tabs' => 'all',
  									'toolbar' => 'full',
  									'media_upload' => 1,
  									'delay' => 0,
  								),
  							),
  						),
              array(
  							'key' => 'field_6e7c792k8db0f',
  							'label' => 'Accordion Section Description',
  							'name' => 'accordion_section_description',
  							'type' => 'wysiwyg',
  							'instructions' => '',
  							'required' => 0,
  							'conditional_logic' => 0,
  							'wrapper' => array(
  								'width' => '',
  								'class' => '',
  								'id' => '',
  							),
  							'default_value' => '',
  							'tabs' => 'all',
  							'toolbar' => 'basic',
  							'media_upload' => 0,
  							'delay' => 0,
  						),
  						array(
  							'key' => 'field_5e4c7bab4e651',
  							'label' => 'Accordion Section CTA',
  							'name' => 'accordion_section_cta',
  							'type' => 'link',
  							'instructions' => '',
  							'required' => 0,
  							'conditional_logic' => 0,
  							'wrapper' => array(
  								'width' => '',
  								'class' => '',
  								'id' => '',
  							),
  							'return_format' => 'array',
  						),
  					),
  					'min' => '',
  					'max' => '',
  				),
  				'layout_5e4c78f08db0c' => array(
  					'key' => 'layout_5e4c78f08db0c',
  					'name' => 'steps_section',
  					'label' => 'Steps',
  					'display' => 'row',
  					'sub_fields' => array(
  						array(
  							'key' => 'field_5e4c7abeab5db',
  							'label' => 'Steps Section Kicker',
  							'name' => 'steps_section_kicker',
  							'type' => 'text',
  							'instructions' => '',
  							'required' => 0,
  							'conditional_logic' => 0,
  							'wrapper' => array(
  								'width' => '',
  								'class' => '',
  								'id' => '',
  							),
  							'default_value' => '',
  							'placeholder' => '',
  							'prepend' => '',
  							'append' => '',
  							'maxlength' => '',
  						),
  						array(
  							'key' => 'field_5e4c7accab5dc',
  							'label' => 'Steps Section Title',
  							'name' => 'steps_section_title',
                'type' => 'text',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                  'width' => '',
                  'class' => '',
                  'id' => '',
                ),
                'default_value' => '',
                'placeholder' => '',
                'prepend' => '',
                'append' => '',
                'maxlength' => '',
  						),
  						array(
  							'key' => 'field_5e4c79e9ab5d7',
  							'label' => 'Steps',
  							'name' => 'steps',
  							'type' => 'repeater',
  							'instructions' => '',
  							'required' => 0,
  							'conditional_logic' => 0,
  							'wrapper' => array(
  								'width' => '',
  								'class' => '',
  								'id' => '',
  							),
  							'collapsed' => '',
  							'min' => 0,
  							'max' => 0,
  							'layout' => 'row',
  							'button_label' => 'Add Step',
  							'sub_fields' => array(
  								array(
  									'key' => 'field_5e4c79f5ab5d8',
  									'label' => 'Steps Title',
  									'name' => 'steps_title',
  									'type' => 'text',
  									'instructions' => '',
  									'required' => 0,
  									'conditional_logic' => 0,
  									'wrapper' => array(
  										'width' => '',
  										'class' => '',
  										'id' => '',
  									),
  									'default_value' => '',
  									'placeholder' => '',
  									'prepend' => '',
  									'append' => '',
  									'maxlength' => '',
  								),
  								array(
  									'key' => 'field_5e4c7a3fab5d9',
  									'label' => 'Steps Description',
  									'name' => 'steps_description',
  									'type' => 'wysiwyg',
  									'instructions' => '',
  									'required' => 0,
  									'conditional_logic' => 0,
  									'wrapper' => array(
  										'width' => '',
  										'class' => '',
  										'id' => '',
  									),
  									'default_value' => '',
  									'tabs' => 'all',
  									'toolbar' => 'full',
  									'media_upload' => 1,
  									'delay' => 0,
  								),
  								array(
  									'key' => 'field_5e4c7a61ab5da',
  									'label' => 'Steps Images',
  									'name' => 'steps_images',
  									'type' => 'gallery',
  									'instructions' => '',
  									'required' => 0,
  									'conditional_logic' => 0,
  									'wrapper' => array(
  										'width' => '',
  										'class' => '',
  										'id' => '',
  									),
  									'min' => '',
  									'max' => '',
  									'insert' => 'append',
  									'library' => 'all',
  									'min_width' => '',
  									'min_height' => '',
  									'min_size' => '',
  									'max_width' => '',
  									'max_height' => '',
  									'max_size' => '',
  									'mime_types' => '',
  								),
  							),
  						),
              array(
  							'key' => 'field_5e4c8accbb5dc',
  							'label' => 'Steps Section Description',
  							'name' => 'steps_section_description',
  							'type' => 'wysiwyg',
  							'instructions' => '',
  							'required' => 0,
  							'conditional_logic' => 0,
  							'wrapper' => array(
  								'width' => '',
  								'class' => '',
  								'id' => '',
  							),
  							'default_value' => '',
  							'tabs' => 'all',
  							'toolbar' => 'full',
  							'media_upload' => 0,
  							'delay' => 0,
  						),
  						array(
  							'key' => 'field_5e4c7bba4e652',
  							'label' => 'Steps Section CTA',
  							'name' => 'steps_section_cta',
  							'type' => 'link',
  							'instructions' => '',
  							'required' => 0,
  							'conditional_logic' => 0,
  							'wrapper' => array(
  								'width' => '',
  								'class' => '',
  								'id' => '',
  							),
  							'return_format' => 'array',
  						),
  					),
  					'min' => '',
  					'max' => '',
  				),
  			),
  			'button_label' => 'Add Section',
  			'min' => '',
  			'max' => '',
  		),
  	),
  	'location' => array(
  		array(
  			array(
  				'param' => 'post_type',
  				'operator' => '==',
  				'value' => 'page',
  			),
  		),
  		array(
  			array(
  				'param' => 'page_type',
  				'operator' => '==',
  				'value' => 'front_page',
  			),
  		),
  	),
  	'menu_order' => 1,
  	'position' => 'normal',
  	'style' => 'default',
  	'label_placement' => 'top',
  	'instruction_placement' => 'label',
  	'hide_on_screen' => '',
  	'active' => 1,
  	'description' => '',
  ));

  // Post Sections
  acf_add_local_field_group(array(
  	'key' => 'group_5e4c7e643282b',
  	'title' => 'Post Sections',
  	'fields' => array(
      array(
  			'key' => 'field_5e33690ff6718',
  			'label' => 'Etsy',
  			'name' => '',
  			'type' => 'tab',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'placement' => 'top',
  			'endpoint' => 0,
  		),
      array(
  			'key' => 'etsy_tab',
  			'label' => 'Etsy Link',
  			'name' => 'etsy_link',
  			'type' => 'url',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'default_value' => '',
  			'placeholder' => '',
  		),
  		array(
  			'key' => 'field_5a5c06c56c395',
  			'label' => 'Etsy Link Text',
  			'name' => 'etsy_link_text',
  			'type' => 'text',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'default_value' => '',
  			'placeholder' => '',
  			'prepend' => '',
  			'append' => '',
  			'maxlength' => '',
  			'readonly' => 0,
  			'disabled' => 0,
  		),
  		array(
  			'key' => 'field_5a5c06d56c396',
  			'label' => 'Etsy Link Description',
  			'name' => 'etsy_link_description',
  			'type' => 'textarea',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'default_value' => '*The plans include a material cut list, a list of necessary tools &amp; hardware, assembly directions, and dimensions.',
  			'placeholder' => '',
  			'maxlength' => '',
  			'rows' => '',
  			'new_lines' => 'wpautop',
  			'readonly' => 0,
  			'disabled' => 0,
  		),
      array(
  			'key' => 'gallery_tab',
  			'label' => 'Gallery',
  			'name' => '',
  			'type' => 'tab',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'placement' => 'top',
  			'endpoint' => 0,
  		),
  		array(
  			'key' => 'field_59dd73787d380',
  			'label' => 'Gallery',
  			'name' => 'gallery',
  			'type' => 'gallery',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'min' => '',
  			'max' => '',
  			'preview_size' => 'thumbnail',
  			'insert' => 'append',
  			'library' => 'all',
  			'min_width' => '',
  			'min_height' => '',
  			'min_size' => '',
  			'max_width' => '',
  			'max_height' => '',
  			'max_size' => '',
  			'mime_types' => '',
  		),
      array(
  			'key' => 'accordion_tab',
  			'label' => 'Accordion',
  			'name' => '',
  			'type' => 'tab',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'placement' => 'top',
  			'endpoint' => 0,
  		),
      array(
  			'key' => 'field_59dd50d81137f',
  			'label' => 'Accordion',
  			'name' => 'accordion',
  			'type' => 'repeater',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'collapsed' => 'field_59dd510e2ca05',
  			'min' => 0,
  			'max' => 0,
  			'layout' => 'row',
  			'button_label' => 'Add Row',
  			'sub_fields' => array(
  				array(
  					'key' => 'field_59dd510e2ca05',
  					'label' => 'Accordion Title',
  					'name' => 'accordion_title',
  					'type' => 'text',
  					'instructions' => '',
  					'required' => '',
  					'conditional_logic' => '',
  					'wrapper' => array(
  						'width' => '',
  						'class' => '',
  						'id' => '',
  					),
  					'default_value' => '',
  					'placeholder' => '',
  					'prepend' => '',
  					'append' => '',
  					'maxlength' => '',
  					'readonly' => 0,
  					'disabled' => 0,
  				),
  				array(
  					'key' => 'field_59dd51172ca06',
  					'label' => 'Accordion Body',
  					'name' => 'accordion_body',
  					'type' => 'wysiwyg',
  					'instructions' => '',
  					'required' => '',
  					'conditional_logic' => '',
  					'wrapper' => array(
  						'width' => '',
  						'class' => '',
  						'id' => '',
  					),
  					'default_value' => '',
  					'tabs' => 'all',
  					'toolbar' => 'full',
  					'media_upload' => 1,
  					'delay' => 0,
  				),
  			),
  		),
      array(
  			'key' => 'instructions_tab',
  			'label' => 'Instructions',
  			'name' => '',
  			'type' => 'tab',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'placement' => 'top',
  			'endpoint' => 0,
  		),
      array(
  			'key' => 'field_59dd51f142fe1',
  			'label' => 'Instructions',
  			'name' => 'instructions',
  			'type' => 'repeater',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'collapsed' => 'field_59e952492119c',
  			'min' => 0,
  			'max' => 0,
  			'layout' => 'row',
  			'button_label' => 'Add Step',
  			'sub_fields' => array(
  				array(
  					'key' => 'field_59e952492119c',
  					'label' => 'Instructions Content',
  					'name' => 'instructions_content',
  					'type' => 'wysiwyg',
  					'instructions' => '',
  					'required' => 0,
  					'conditional_logic' => 0,
  					'wrapper' => array(
  						'width' => '',
  						'class' => '',
  						'id' => '',
  					),
  					'default_value' => '',
  					'tabs' => 'all',
  					'toolbar' => 'full',
  					'media_upload' => 0,
  					'delay' => 0,
  				),
  				array(
  					'key' => 'field_59e952732119d',
  					'label' => 'Instructions Image',
  					'name' => 'instructions_image',
  					'type' => 'gallery',
  					'instructions' => '',
  					'required' => 0,
  					'conditional_logic' => 0,
  					'wrapper' => array(
  						'width' => '',
  						'class' => '',
  						'id' => '',
  					),
  					'min' => '',
  					'max' => '',
  					'preview_size' => 'thumbnail',
  					'insert' => 'append',
  					'library' => 'all',
  					'min_width' => '',
  					'min_height' => '',
  					'min_size' => '',
  					'max_width' => '',
  					'max_height' => '',
  					'max_size' => '',
  					'mime_types' => '',
  				),
  			),
  		),
      array(
  			'key' => 'affiliates_tab',
  			'label' => 'Affiliates',
  			'name' => '',
  			'type' => 'tab',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'placement' => 'top',
  			'endpoint' => 0,
  		),
      array(
  			'key' => 'field_59f13c96b4e6a',
  			'label' => 'Featured Affiliates',
  			'name' => 'featured_affiliates',
  			'type' => 'relationship',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'post_type' => array(
  				0 => 'affiliate',
  			),
  			'taxonomy' => array(
  			),
  			'filters' => array(
  				0 => 'search',
  				1 => 'post_type',
  				2 => 'taxonomy',
  			),
  			'elements' => array(
  				0 => 'featured_image',
  			),
  			'min' => '',
  			'max' => '',
  			'return_format' => 'object',
  		),
  	),
  	'location' => array(
  		array(
  			array(
  				'param' => 'post_type',
  				'operator' => '==',
  				'value' => 'post',
  			),
  		),
  	),
  	'menu_order' => 0,
  	'position' => 'normal',
  	'style' => 'default',
  	'label_placement' => 'top',
  	'instruction_placement' => 'label',
  	'hide_on_screen' => '',
  	'active' => 1,
  	'description' => '',
  ));

  // Affiliate Link
  acf_add_local_field_group(array(
  	'key' => 'group_59dd557545e2c',
  	'title' => 'Affiliate Link',
  	'fields' => array(
  		array(
  			'key' => 'field_59dd557a35fa9',
  			'label' => 'Affiliate Link',
  			'name' => 'affiliate_link',
  			'type' => 'url',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'default_value' => '',
  			'placeholder' => '',
  		),
  	),
  	'location' => array(
  		array(
  			array(
  				'param' => 'post_type',
  				'operator' => '==',
  				'value' => 'affiliate',
  			),
  		),
  	),
  	'menu_order' => 0,
  	'position' => 'normal',
  	'style' => 'default',
  	'label_placement' => 'top',
  	'instruction_placement' => 'label',
  	'hide_on_screen' => '',
  	'active' => 1,
  	'description' => '',
  ));

  // Work
  acf_add_local_field_group(array(
  	'key' => 'group_59efaac250adc',
  	'title' => 'Work',
  	'fields' => array(
  		array(
  			'key' => 'field_59efadea4970d',
  			'label' => 'Website Url',
  			'name' => 'website_url',
  			'type' => 'url',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'default_value' => '',
  			'placeholder' => '',
  		),
  		array(
  			'key' => 'field_59efaac5d85fb',
  			'label' => 'Featured Banner Image',
  			'name' => 'featured_banner_image',
  			'type' => 'image',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'return_format' => 'array',
  			'preview_size' => 'thumbnail',
  			'library' => 'all',
  			'min_width' => '',
  			'min_height' => '',
  			'min_size' => '',
  			'max_width' => '',
  			'max_height' => '',
  			'max_size' => '',
  			'mime_types' => '',
  		),
  		array(
  			'key' => 'field_59efaaf5d85fc',
  			'label' => 'Featured Work Image',
  			'name' => 'featured_work_image',
  			'type' => 'image',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'return_format' => 'array',
  			'preview_size' => 'thumbnail',
  			'library' => 'all',
  			'min_width' => '',
  			'min_height' => '',
  			'min_size' => '',
  			'max_width' => '',
  			'max_height' => '',
  			'max_size' => '',
  			'mime_types' => '',
  		),
  		array(
  			'key' => 'field_59efab31d85fd',
  			'label' => 'Work',
  			'name' => 'work',
  			'type' => 'repeater',
  			'instructions' => '',
  			'required' => 0,
  			'conditional_logic' => 0,
  			'wrapper' => array(
  				'width' => '',
  				'class' => '',
  				'id' => '',
  			),
  			'collapsed' => 'field_59efad10128ce',
  			'min' => 0,
  			'max' => 0,
  			'layout' => 'block',
  			'button_label' => 'Add Work Section',
  			'sub_fields' => array(
  				array(
  					'key' => 'field_59efad10128ce',
  					'label' => 'Work Section Title',
  					'name' => 'work_section_title',
  					'type' => 'text',
  					'instructions' => '',
  					'required' => '',
  					'conditional_logic' => '',
  					'wrapper' => array(
  						'width' => '',
  						'class' => '',
  						'id' => '',
  					),
  					'default_value' => '',
  					'placeholder' => '',
  					'prepend' => '',
  					'append' => '',
  					'maxlength' => '',
  					'readonly' => 0,
  					'disabled' => 0,
  				),
  				array(
  					'key' => 'field_59efad18128cf',
  					'label' => 'Work Section Images',
  					'name' => 'work_section_images',
  					'type' => 'gallery',
  					'instructions' => '',
  					'required' => '',
  					'conditional_logic' => '',
  					'wrapper' => array(
  						'width' => '',
  						'class' => '',
  						'id' => '',
  					),
  					'min' => '',
  					'max' => '',
  					'preview_size' => 'thumbnail',
  					'insert' => 'append',
  					'library' => 'all',
  					'min_width' => '',
  					'min_height' => '',
  					'min_size' => '',
  					'max_width' => '',
  					'max_height' => '',
  					'max_size' => '',
  					'mime_types' => '',
  				),
  			),
  		),
  	),
  	'location' => array(
  		array(
  			array(
  				'param' => 'post_type',
  				'operator' => '==',
  				'value' => 'work',
  			),
  		),
  	),
  	'menu_order' => 0,
  	'position' => 'normal',
  	'style' => 'default',
  	'label_placement' => 'top',
  	'instruction_placement' => 'label',
  	'hide_on_screen' => '',
  	'active' => 1,
  	'description' => '',
  ));

endif;