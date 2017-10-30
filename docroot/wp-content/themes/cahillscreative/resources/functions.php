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

if( function_exists('acf_add_local_field_group') ):

acf_add_local_field_group(array (
  'key' => 'group_59dd4f329ea69',
  'title' => 'Accordion',
  'fields' => array (
    array (
      'key' => 'field_59dd50d81137f',
      'label' => 'Accordion',
      'name' => 'accordion',
      'type' => 'repeater',
      'instructions' => '',
      'required' => 0,
      'conditional_logic' => 0,
      'wrapper' => array (
        'width' => '',
        'class' => '',
        'id' => '',
      ),
      'collapsed' => 'field_59dd510e2ca05',
      'min' => '',
      'max' => '',
      'layout' => 'row',
      'button_label' => 'Add Row',
      'sub_fields' => array (
        array (
          'key' => 'field_59dd510e2ca05',
          'label' => 'Accordion Title',
          'name' => 'accordion_title',
          'type' => 'text',
          'instructions' => '',
          'required' => '',
          'conditional_logic' => '',
          'wrapper' => array (
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
        array (
          'key' => 'field_59dd51172ca06',
          'label' => 'Accordion Body',
          'name' => 'accordion_body',
          'type' => 'wysiwyg',
          'instructions' => '',
          'required' => '',
          'conditional_logic' => '',
          'wrapper' => array (
            'width' => '',
            'class' => '',
            'id' => '',
          ),
          'default_value' => '',
          'tabs' => 'all',
          'toolbar' => 'full',
          'media_upload' => 1,
        ),
      ),
    ),
  ),
  'location' => array (
    array (
      array (
        'param' => 'post_type',
        'operator' => '==',
        'value' => 'post',
      ),
    ),
    array (
      array (
        'param' => 'page',
        'operator' => '==',
        'value' => '15',
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

acf_add_local_field_group(array (
  'key' => 'group_59dd557545e2c',
  'title' => 'Affiliate Link',
  'fields' => array (
    array (
      'key' => 'field_59dd557a35fa9',
      'label' => 'Affiliate Link',
      'name' => 'affiliate_link',
      'type' => 'url',
      'instructions' => '',
      'required' => 0,
      'conditional_logic' => 0,
      'wrapper' => array (
        'width' => '',
        'class' => '',
        'id' => '',
      ),
      'default_value' => '',
      'placeholder' => '',
    ),
  ),
  'location' => array (
    array (
      array (
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

acf_add_local_field_group(array (
  'key' => 'group_59ee78d57e05d',
  'title' => 'Category Featured Image',
  'fields' => array (
    array (
      'key' => 'field_59ee78dbcceb4',
      'label' => 'Category Featured Image',
      'name' => 'category_featured_image',
      'type' => 'image',
      'instructions' => '',
      'required' => 0,
      'conditional_logic' => 0,
      'wrapper' => array (
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
  ),
  'location' => array (
    array (
      array (
        'param' => 'taxonomy',
        'operator' => '==',
        'value' => 'all',
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

acf_add_local_field_group(array (
  'key' => 'group_59dd55e277d9d',
  'title' => 'Etsy Link',
  'fields' => array (
    array (
      'key' => 'field_59dd563892514',
      'label' => 'Etsy Link',
      'name' => 'etsy_link',
      'type' => 'url',
      'instructions' => '',
      'required' => 0,
      'conditional_logic' => 0,
      'wrapper' => array (
        'width' => '',
        'class' => '',
        'id' => '',
      ),
      'default_value' => '',
      'placeholder' => '',
    ),
  ),
  'location' => array (
    array (
      array (
        'param' => 'post_type',
        'operator' => '==',
        'value' => 'post',
      ),
      array (
        'param' => 'post_taxonomy',
        'operator' => '==',
        'value' => 'category:diy',
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

acf_add_local_field_group(array (
  'key' => 'group_59f13c8f1a2d4',
  'title' => 'Featured Affiliates',
  'fields' => array (
    array (
      'key' => 'field_59f13c96b4e6a',
      'label' => 'Featured Affiliates',
      'name' => 'featured_affiliates',
      'type' => 'relationship',
      'instructions' => '',
      'required' => 0,
      'conditional_logic' => 0,
      'wrapper' => array (
        'width' => '',
        'class' => '',
        'id' => '',
      ),
      'post_type' => array (
        0 => 'affiliate',
      ),
      'taxonomy' => array (
      ),
      'filters' => array (
        0 => 'search',
        1 => 'post_type',
        2 => 'taxonomy',
      ),
      'elements' => array (
        0 => 'featured_image',
      ),
      'min' => '',
      'max' => '',
      'return_format' => 'object',
    ),
  ),
  'location' => array (
    array (
      array (
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

acf_add_local_field_group(array (
  'key' => 'group_59dd7371c909e',
  'title' => 'Gallery',
  'fields' => array (
    array (
      'key' => 'field_59dd73787d380',
      'label' => 'Gallery',
      'name' => 'gallery',
      'type' => 'gallery',
      'instructions' => '',
      'required' => 0,
      'conditional_logic' => 0,
      'wrapper' => array (
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
  'location' => array (
    array (
      array (
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

acf_add_local_field_group(array (
  'key' => 'group_59dd4fcd97ca8',
  'title' => 'Instructions',
  'fields' => array (
    array (
      'key' => 'field_59dd51f142fe1',
      'label' => 'Instructions',
      'name' => 'instructions',
      'type' => 'repeater',
      'instructions' => '',
      'required' => 0,
      'conditional_logic' => 0,
      'wrapper' => array (
        'width' => '',
        'class' => '',
        'id' => '',
      ),
      'collapsed' => 'field_59e952492119c',
      'min' => '',
      'max' => '',
      'layout' => 'row',
      'button_label' => 'Add Step',
      'sub_fields' => array (
        array (
          'key' => 'field_59e952492119c',
          'label' => 'Instructions Content',
          'name' => 'instructions_content',
          'type' => 'wysiwyg',
          'instructions' => '',
          'required' => 0,
          'conditional_logic' => 0,
          'wrapper' => array (
            'width' => '',
            'class' => '',
            'id' => '',
          ),
          'default_value' => '',
          'tabs' => 'all',
          'toolbar' => 'full',
          'media_upload' => 0,
        ),
        array (
          'key' => 'field_59e952732119d',
          'label' => 'Instructions Image',
          'name' => 'instructions_image',
          'type' => 'gallery',
          'instructions' => '',
          'required' => 0,
          'conditional_logic' => 0,
          'wrapper' => array (
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
  'location' => array (
    array (
      array (
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

acf_add_local_field_group(array (
  'key' => 'group_59df823bcb67c',
  'title' => 'Page Settings',
  'fields' => array (
    array (
      'key' => 'field_59df8240d11ef',
      'label' => 'Display Title',
      'name' => 'display_title',
      'type' => 'text',
      'instructions' => '',
      'required' => 0,
      'conditional_logic' => 0,
      'wrapper' => array (
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
    array (
      'key' => 'field_59e7f09581902',
      'label' => 'Intro',
      'name' => 'intro',
      'type' => 'wysiwyg',
      'instructions' => '',
      'required' => 0,
      'conditional_logic' => 0,
      'wrapper' => array (
        'width' => '',
        'class' => '',
        'id' => '',
      ),
      'default_value' => '',
      'tabs' => 'all',
      'toolbar' => 'full',
      'media_upload' => 0,
    ),
  ),
  'location' => array (
    array (
      array (
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

acf_add_local_field_group(array (
  'key' => 'group_59f39dd845bca',
  'title' => 'Process',
  'fields' => array (
    array (
      'key' => 'field_59f39de24dde0',
      'label' => 'Process Steps',
      'name' => 'process_steps',
      'type' => 'repeater',
      'instructions' => '',
      'required' => 0,
      'conditional_logic' => 0,
      'wrapper' => array (
        'width' => '',
        'class' => '',
        'id' => '',
      ),
      'collapsed' => 'field_59f39df44dde1',
      'min' => '',
      'max' => '',
      'layout' => 'block',
      'button_label' => 'Add Row',
      'sub_fields' => array (
        array (
          'key' => 'field_59f39e3eb522f',
          'label' => 'Process Title',
          'name' => 'process_title',
          'type' => 'text',
          'instructions' => '',
          'required' => '',
          'conditional_logic' => '',
          'wrapper' => array (
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
        array (
          'key' => 'field_59f39e43b5230',
          'label' => 'Process Body',
          'name' => 'process_body',
          'type' => 'wysiwyg',
          'instructions' => '',
          'required' => '',
          'conditional_logic' => '',
          'wrapper' => array (
            'width' => '',
            'class' => '',
            'id' => '',
          ),
          'default_value' => '',
          'tabs' => 'all',
          'toolbar' => 'full',
          'media_upload' => 0,
        ),
      ),
    ),
  ),
  'location' => array (
    array (
      array (
        'param' => 'page',
        'operator' => '==',
        'value' => '15',
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

acf_add_local_field_group(array (
  'key' => 'group_59efaac250adc',
  'title' => 'Work',
  'fields' => array (
    array (
      'key' => 'field_59efadea4970d',
      'label' => 'Website Url',
      'name' => 'website_url',
      'type' => 'url',
      'instructions' => '',
      'required' => 0,
      'conditional_logic' => 0,
      'wrapper' => array (
        'width' => '',
        'class' => '',
        'id' => '',
      ),
      'default_value' => '',
      'placeholder' => '',
    ),
    array (
      'key' => 'field_59efaac5d85fb',
      'label' => 'Featured Banner Image',
      'name' => 'featured_banner_image',
      'type' => 'image',
      'instructions' => '',
      'required' => 0,
      'conditional_logic' => 0,
      'wrapper' => array (
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
    array (
      'key' => 'field_59efaaf5d85fc',
      'label' => 'Featured Work Image',
      'name' => 'featured_work_image',
      'type' => 'image',
      'instructions' => '',
      'required' => 0,
      'conditional_logic' => 0,
      'wrapper' => array (
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
    array (
      'key' => 'field_59efab31d85fd',
      'label' => 'Work',
      'name' => 'work',
      'type' => 'repeater',
      'instructions' => '',
      'required' => 0,
      'conditional_logic' => 0,
      'wrapper' => array (
        'width' => '',
        'class' => '',
        'id' => '',
      ),
      'collapsed' => 'field_59efad10128ce',
      'min' => '',
      'max' => '',
      'layout' => 'block',
      'button_label' => 'Add Work Section',
      'sub_fields' => array (
        array (
          'key' => 'field_59efad10128ce',
          'label' => 'Work Section Title',
          'name' => 'work_section_title',
          'type' => 'text',
          'instructions' => '',
          'required' => '',
          'conditional_logic' => '',
          'wrapper' => array (
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
        array (
          'key' => 'field_59efad18128cf',
          'label' => 'Work Section Images',
          'name' => 'work_section_images',
          'type' => 'gallery',
          'instructions' => '',
          'required' => '',
          'conditional_logic' => '',
          'wrapper' => array (
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
  'location' => array (
    array (
      array (
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
