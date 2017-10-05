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

/**
 * Load ajax script on news template
 */
function enqueue_ajax_load_more() {
   wp_enqueue_script('ajax-load-more'); // Already registered, just needs to be enqueued
}
add_action('wp_enqueue_scripts', 'enqueue_ajax_load_more');

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
 * Adds ACF options page
 */
if (function_exists('acf_add_options_page')) {
  acf_add_options_page(
    array(
  		'page_title' 	=> 'Theme General Settings',
  		'menu_title'	=> 'Theme Settings',
  		'menu_slug' 	=> 'theme-general-settings',
  		'capability'	=> 'edit_posts',
  		'redirect'		=> false
  	)
  );
}


/**
 * Post Types
 */
function cptui_register_my_cpts() {

	/**
	 * Post Type: Events.
	 */
	$labels = array(
		"name" => __( 'Events', 'sage' ),
		"singular_name" => __( 'Event', 'sage' ),
		"menu_name" => __( 'Events', 'sage' ),
		"all_items" => __( 'All Events', 'sage' ),
		"add_new" => __( 'Add New Event', 'sage' ),
		"edit_item" => __( 'Edit Event', 'sage' ),
		"new_item" => __( 'New Event', 'sage' ),
		"view_item" => __( 'View Event', 'sage' ),
		"view_items" => __( 'View Events', 'sage' ),
		"search_items" => __( 'Search Events', 'sage' ),
		"not_found" => __( 'No Events Found', 'sage' ),
		"not_found_in_trash" => __( 'No Events found in Trash', 'sage' ),
	);

	$args = array(
		"label" => __( 'Events', 'sage' ),
		"labels" => $labels,
		"description" => "A Wilkes School Event",
		"public" => true,
		"publicly_queryable" => true,
		"show_ui" => true,
		"show_in_rest" => false,
		"rest_base" => "",
		"show_in_menu" => true,
		"exclude_from_search" => false,
		"capability_type" => "post",
		"hierarchical" => true,
		"rewrite" => array( "slug" => "life-at-wilkes/calendar", "with_front" => true ),
    'has_archive' => false,
		"query_var" => true,
		"menu_position" => 3,
		"menu_icon" => "dashicons-calendar",
		"supports" => array( "title", "editor", "thumbnail", "excerpt" ),
	);

	register_post_type( "events", $args );
}
add_action( 'init', 'cptui_register_my_cpts' );

/**
 * Custom fields
 */

 if( function_exists('acf_add_local_field_group') ):

 acf_add_local_field_group(array (
 	'key' => 'group_59776ee2eeee6',
 	'title' => 'Alert',
 	'fields' => array (
 		array (
 			'key' => 'field_59776ee64ff73',
 			'label' => 'Alert Title',
 			'name' => 'alert_title',
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
 			'key' => 'field_59776ef64ff74',
 			'label' => 'Alert Date',
 			'name' => 'alert_date',
 			'type' => 'date_picker',
 			'instructions' => '',
 			'required' => 0,
 			'conditional_logic' => 0,
 			'wrapper' => array (
 				'width' => '',
 				'class' => '',
 				'id' => '',
 			),
 			'display_format' => 'F j, Y',
 			'return_format' => 'd/m/Y',
 			'first_day' => 1,
 		),
 		array (
 			'key' => 'field_59776f094ff75',
 			'label' => 'Alert Description',
 			'name' => 'alert_description',
 			'type' => 'textarea',
 			'instructions' => 'Character limit 100',
 			'required' => 0,
 			'conditional_logic' => 0,
 			'wrapper' => array (
 				'width' => '',
 				'class' => '',
 				'id' => '',
 			),
 			'default_value' => '',
 			'placeholder' => '',
 			'maxlength' => 100,
 			'rows' => 2,
 			'new_lines' => 'wpautop',
 			'readonly' => 0,
 			'disabled' => 0,
 		),
 		array (
 			'key' => 'field_59776f484ff76',
 			'label' => 'Alert Link',
 			'name' => 'alert_link',
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
 				'param' => 'options_page',
 				'operator' => '==',
 				'value' => 'theme-general-settings',
 			),
 		),
 	),
 	'menu_order' => 0,
 	'position' => 'normal',
 	'style' => 'default',
 	'label_placement' => 'left',
 	'instruction_placement' => 'label',
 	'hide_on_screen' => '',
 	'active' => 1,
 	'description' => '',
 ));

 acf_add_local_field_group(array (
 	'key' => 'group_5967d150c19aa',
 	'title' => 'Content Blocks',
 	'fields' => array (
 		array (
 			'key' => 'field_5967d165ef9f1',
 			'label' => 'Content Block',
 			'name' => 'content_block',
 			'type' => 'repeater',
 			'instructions' => '',
 			'required' => 0,
 			'conditional_logic' => 0,
 			'wrapper' => array (
 				'width' => '',
 				'class' => '',
 				'id' => '',
 			),
 			'collapsed' => 'field_5967d195ef9f2',
 			'min' => '',
 			'max' => '',
 			'layout' => 'row',
 			'button_label' => 'Add Block',
 			'sub_fields' => array (
 				array (
 					'key' => 'field_5967d195ef9f2',
 					'label' => 'Title',
 					'name' => 'content_block_title',
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
 					'key' => 'field_5967d1d3ef9f3',
 					'label' => 'Subtitle',
 					'name' => 'content_block_subtitle',
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
 					'key' => 'field_5967d227ef9f4',
 					'label' => 'Body',
 					'name' => 'content_block_body',
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
 					'toolbar' => 'basic',
 					'media_upload' => 0,
 				),
 				array (
 					'key' => 'field_5967d251ef9f5',
 					'label' => 'Image',
 					'name' => 'content_block_image',
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
 		),
 	),
 	'location' => array (
 		array (
 			array (
 				'param' => 'page_template',
 				'operator' => '==',
 				'value' => 'views/template-default-blocks.blade.php',
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
 	'key' => 'group_59774d3024882',
 	'title' => 'Event Details',
 	'fields' => array (
 		array (
 			'key' => 'field_59774d34a9f7a',
 			'label' => 'Event Start Date & Time',
 			'name' => 'event_start_date_time',
 			'type' => 'date_time_picker',
 			'instructions' => '',
 			'required' => 0,
 			'conditional_logic' => 0,
 			'wrapper' => array (
 				'width' => 50,
 				'class' => '',
 				'id' => '',
 			),
 			'display_format' => 'F j, Y g:i a',
 			'return_format' => 'd/m/Y g:i a',
 			'first_day' => 1,
 		),
 		array (
 			'key' => 'field_5977b068db7dc',
 			'label' => 'Event End Date & Time',
 			'name' => 'event_end_date_time',
 			'type' => 'date_time_picker',
 			'instructions' => '',
 			'required' => 0,
 			'conditional_logic' => 0,
 			'wrapper' => array (
 				'width' => 50,
 				'class' => '',
 				'id' => '',
 			),
 			'display_format' => 'F j, Y g:i a',
 			'return_format' => 'd/m/Y g:i a',
 			'first_day' => 1,
 		),
 		array (
 			'key' => 'field_59774d6ba9f7b',
 			'label' => 'Event Duration',
 			'name' => 'event_duration',
 			'type' => 'checkbox',
 			'instructions' => 'Check this box if this is an all day event.',
 			'required' => 0,
 			'conditional_logic' => 0,
 			'wrapper' => array (
 				'width' => 50,
 				'class' => '',
 				'id' => '',
 			),
 			'choices' => array (
 				'true' => 'All Day Event',
 			),
 			'default_value' => array (
 			),
 			'layout' => 'vertical',
 			'toggle' => 0,
 		),
 	),
 	'location' => array (
 		array (
 			array (
 				'param' => 'post_type',
 				'operator' => '==',
 				'value' => 'events',
 			),
 		),
 	),
 	'menu_order' => 0,
 	'position' => 'normal',
 	'style' => 'default',
 	'label_placement' => 'left',
 	'instruction_placement' => 'label',
 	'hide_on_screen' => '',
 	'active' => 1,
 	'description' => '',
 ));

 acf_add_local_field_group(array (
 	'key' => 'group_596cc781e42a1',
 	'title' => 'Homepage Content',
 	'fields' => array (
 		array (
 			'key' => 'field_5977b47f69bad',
 			'label' => 'Hero',
 			'name' => 'hero',
 			'type' => 'tab',
 			'instructions' => '',
 			'required' => 0,
 			'conditional_logic' => 0,
 			'wrapper' => array (
 				'width' => '',
 				'class' => '',
 				'id' => '',
 			),
 			'placement' => 'left',
 			'endpoint' => 0,
 		),
 		array (
 			'key' => 'field_5977b48b69bae',
 			'label' => 'Hero Slideshow',
 			'name' => 'hero_slideshow',
 			'type' => 'repeater',
 			'instructions' => '',
 			'required' => 0,
 			'conditional_logic' => 0,
 			'wrapper' => array (
 				'width' => '',
 				'class' => '',
 				'id' => '',
 			),
 			'collapsed' => 'field_5977b49569baf',
 			'min' => '',
 			'max' => '',
 			'layout' => 'block',
 			'button_label' => 'Add Slide',
 			'sub_fields' => array (
 				array (
 					'key' => 'field_5977b49569baf',
 					'label' => 'Hero Title',
 					'name' => 'hero_title',
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
 					'key' => 'field_5977b4b069bb0',
 					'label' => 'Hero Description',
 					'name' => 'hero_description',
 					'type' => 'textarea',
 					'instructions' => '',
 					'required' => 0,
 					'conditional_logic' => 0,
 					'wrapper' => array (
 						'width' => '',
 						'class' => 2,
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
 				array (
 					'key' => 'field_5977b4bd69bb1',
 					'label' => 'Hero Link',
 					'name' => 'hero_link',
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
 					'key' => 'field_5977b50669bb4',
 					'label' => 'Hero Image or Video?',
 					'name' => 'hero_image_video',
 					'type' => 'select',
 					'instructions' => 'Select for the slide to display and image or video.',
 					'required' => 0,
 					'conditional_logic' => 0,
 					'wrapper' => array (
 						'width' => '',
 						'class' => '',
 						'id' => '',
 					),
 					'choices' => array (
 						'image' => 'Image',
 						'video' => 'Video',
 					),
 					'default_value' => array (
 						0 => 'image',
 					),
 					'allow_null' => 0,
 					'multiple' => 0,
 					'ui' => 0,
 					'ajax' => 0,
 					'placeholder' => '',
 					'disabled' => 0,
 					'readonly' => 0,
 				),
 				array (
 					'key' => 'field_5977b4ca69bb2',
 					'label' => 'Hero Image',
 					'name' => 'hero_image',
 					'type' => 'image',
 					'instructions' => '',
 					'required' => 0,
 					'conditional_logic' => array (
 						array (
 							array (
 								'field' => 'field_5977b50669bb4',
 								'operator' => '==',
 								'value' => 'image',
 							),
 						),
 					),
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
 					'key' => 'field_5977b4d769bb3',
 					'label' => 'Hero Video ID',
 					'name' => 'hero_video',
 					'type' => 'text',
 					'instructions' => 'Enter the media ID for the video from jwplayer.',
 					'required' => 0,
 					'conditional_logic' => array (
 						array (
 							array (
 								'field' => 'field_5977b50669bb4',
 								'operator' => '==',
 								'value' => 'video',
 							),
 						),
 					),
 					'wrapper' => array (
 						'width' => '',
 						'class' => '',
 						'id' => '',
 					),
 					'default_value' => '',
 					'placeholder' => '',
 					'maxlength' => '',
 					'rows' => '',
 					'new_lines' => '',
 					'readonly' => 0,
 					'disabled' => 0,
 				),
 			),
 		),
 		array (
 			'key' => 'field_596cc87be47fa',
 			'label' => 'Top Section',
 			'name' => 'top_section',
 			'type' => 'tab',
 			'instructions' => '',
 			'required' => 0,
 			'conditional_logic' => 0,
 			'wrapper' => array (
 				'width' => '',
 				'class' => '',
 				'id' => '',
 			),
 			'placement' => 'left',
 			'endpoint' => 0,
 		),
 		array (
 			'key' => 'field_597a3dbd7bf33',
 			'label' => 'Top Freeform Block Kicker',
 			'name' => 'top_freeform_block_kicker',
 			'type' => 'text',
 			'instructions' => '',
 			'required' => 0,
 			'conditional_logic' => 0,
 			'wrapper' => array (
 				'width' => 50,
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
 			'key' => 'field_597a3dcd7bf34',
 			'label' => 'Top Freeform Block Kicker Icon',
 			'name' => 'top_freeform_block_kicker_icon',
 			'type' => 'select',
 			'instructions' => '',
 			'required' => 0,
 			'conditional_logic' => 0,
 			'wrapper' => array (
 				'width' => 50,
 				'class' => '',
 				'id' => '',
 			),
 			'choices' => array (
 				'admissions' => 'Admissions',
 				'academics' => 'Academics',
 				'building' => 'Building',
 				'chart' => 'Chart',
 				'contact' => 'Contact',
 				'events' => 'Events',
 				'news' => 'News',
 				'heart' => 'Heart',
 				'pencil' => 'Pencil',
 				'start' => 'Star',
 			),
 			'default_value' => array (
 			),
 			'allow_null' => 0,
 			'multiple' => 0,
 			'ui' => 0,
 			'ajax' => 0,
 			'placeholder' => '',
 			'disabled' => 0,
 			'readonly' => 0,
 		),
 		array (
 			'key' => 'field_597a3bf7234a9',
 			'label' => 'Top Freeform Block Body',
 			'name' => 'top_freeform_block_body',
 			'type' => 'textarea',
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
 			'maxlength' => '',
 			'rows' => 4,
 			'new_lines' => '',
 			'readonly' => 0,
 			'disabled' => 0,
 		),
 		array (
 			'key' => 'field_597a3c10234aa',
 			'label' => 'Top Freeform Block Link Text',
 			'name' => 'top_freeform_block_link_text',
 			'type' => 'text',
 			'instructions' => '',
 			'required' => 0,
 			'conditional_logic' => 0,
 			'wrapper' => array (
 				'width' => 50,
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
 			'key' => 'field_597a3c1d234ab',
 			'label' => 'Top Freeform Block Link Url',
 			'name' => 'top_freeform_block_link_url',
 			'type' => 'url',
 			'instructions' => '',
 			'required' => 0,
 			'conditional_logic' => 0,
 			'wrapper' => array (
 				'width' => 50,
 				'class' => '',
 				'id' => '',
 			),
 			'default_value' => '',
 			'placeholder' => '',
 		),
 		array (
 			'key' => 'field_596cc788ef4f7',
 			'label' => 'Top Link Blocks',
 			'name' => 'top_link_blocks',
 			'type' => 'relationship',
 			'instructions' => 'Select three pages to feature below the hero image. The first selected page will become featured. ',
 			'required' => 0,
 			'conditional_logic' => 0,
 			'wrapper' => array (
 				'width' => '',
 				'class' => '',
 				'id' => '',
 			),
 			'post_type' => array (
 				0 => 'page',
 			),
 			'taxonomy' => array (
 			),
 			'filters' => array (
 				0 => 'search',
 			),
 			'elements' => array (
 				0 => 'featured_image',
 			),
 			'min' => 2,
 			'max' => 2,
 			'return_format' => 'object',
 		),
 		array (
 			'key' => 'field_596ccb45958c3',
 			'label' => 'Center Section',
 			'name' => 'center_section',
 			'type' => 'tab',
 			'instructions' => '',
 			'required' => 0,
 			'conditional_logic' => 0,
 			'wrapper' => array (
 				'width' => '',
 				'class' => '',
 				'id' => '',
 			),
 			'placement' => 'left',
 			'endpoint' => 0,
 		),
 		array (
 			'key' => 'field_596ccb59958c4',
 			'label' => 'Center Link Block',
 			'name' => 'center_link_blocks',
 			'type' => 'relationship',
 			'instructions' => 'Select one page to feature in the large center section.',
 			'required' => 0,
 			'conditional_logic' => 0,
 			'wrapper' => array (
 				'width' => '',
 				'class' => '',
 				'id' => '',
 			),
 			'post_type' => array (
 				0 => 'page',
 			),
 			'taxonomy' => array (
 			),
 			'filters' => array (
 				0 => 'search',
 			),
 			'elements' => array (
 				0 => 'featured_image',
 			),
 			'min' => 1,
 			'max' => 1,
 			'return_format' => 'object',
 		),
 		array (
 			'key' => 'field_597a36545da28',
 			'label' => 'Center Link Background Image',
 			'name' => 'center_link_background_image',
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
 			'key' => 'field_596cc891e47fb',
 			'label' => 'Academics Section',
 			'name' => 'bottom_section',
 			'type' => 'tab',
 			'instructions' => '',
 			'required' => 0,
 			'conditional_logic' => 0,
 			'wrapper' => array (
 				'width' => '',
 				'class' => '',
 				'id' => '',
 			),
 			'placement' => 'top',
 			'endpoint' => 0,
 		),
 		array (
 			'key' => 'field_596cc809e8f13',
 			'label' => 'Academic Link Blocks',
 			'name' => 'bottom_link_blocks',
 			'type' => 'relationship',
 			'instructions' => 'Select three pages to feature in the Academics section.',
 			'required' => 0,
 			'conditional_logic' => 0,
 			'wrapper' => array (
 				'width' => '',
 				'class' => '',
 				'id' => '',
 			),
 			'post_type' => array (
 				0 => 'page',
 			),
 			'taxonomy' => array (
 			),
 			'filters' => array (
 				0 => 'search',
 			),
 			'elements' => array (
 				0 => 'featured_image',
 			),
 			'min' => 2,
 			'max' => 3,
 			'return_format' => 'object',
 		),
 		array (
 			'key' => 'field_5977b35beaa13',
 			'label' => 'News Section',
 			'name' => 'news_section',
 			'type' => 'tab',
 			'instructions' => '',
 			'required' => 0,
 			'conditional_logic' => 0,
 			'wrapper' => array (
 				'width' => '',
 				'class' => '',
 				'id' => '',
 			),
 			'placement' => 'left',
 			'endpoint' => 0,
 		),
 		array (
 			'key' => 'field_5977b3c6eaa18',
 			'label' => 'Featured News',
 			'name' => 'featured_news',
 			'type' => 'relationship',
 			'instructions' => 'Select the news post to feature next to the news feed on the homepage.',
 			'required' => 0,
 			'conditional_logic' => 0,
 			'wrapper' => array (
 				'width' => '',
 				'class' => '',
 				'id' => '',
 			),
 			'post_type' => array (
 				0 => 'post',
 			),
 			'taxonomy' => array (
 				0 => 'category:news',
 			),
 			'filters' => array (
 				0 => 'search',
 			),
 			'elements' => array (
 				0 => 'featured_image',
 			),
 			'min' => '',
 			'max' => 1,
 			'return_format' => 'object',
 		),
 	),
 	'location' => array (
 		array (
 			array (
 				'param' => 'page_type',
 				'operator' => '==',
 				'value' => 'front_page',
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
 	'key' => 'group_5968bb027191d',
 	'title' => 'Landing Page Content',
 	'fields' => array (
 		array (
 			'key' => 'field_5968c582c3cdf',
 			'label' => 'CTA Block',
 			'name' => '',
 			'type' => 'tab',
 			'instructions' => '',
 			'required' => 0,
 			'conditional_logic' => 0,
 			'wrapper' => array (
 				'width' => '',
 				'class' => '',
 				'id' => '',
 			),
 			'placement' => 'left',
 			'endpoint' => 0,
 		),
 		array (
 			'key' => 'field_5968bb35e5b49',
 			'label' => 'CTA Block',
 			'name' => 'cta_block',
 			'type' => 'select',
 			'instructions' => '',
 			'required' => 0,
 			'conditional_logic' => 0,
 			'wrapper' => array (
 				'width' => '',
 				'class' => 'Update',
 				'id' => '',
 			),
 			'choices' => array (
 				'news' => 'News Feed',
 				'freeform' => 'Freeform',
 			),
 			'default_value' => array (
 				0 => 'news',
 			),
 			'allow_null' => 0,
 			'multiple' => 0,
 			'ui' => 0,
 			'ajax' => 0,
 			'placeholder' => '',
 			'disabled' => 0,
 			'readonly' => 0,
 		),
 		array (
 			'key' => 'field_59761469260b6',
 			'label' => 'Kicker',
 			'name' => 'freeform_kicker',
 			'type' => 'text',
 			'instructions' => '',
 			'required' => 0,
 			'conditional_logic' => array (
 				array (
 					array (
 						'field' => 'field_5968bb35e5b49',
 						'operator' => '==',
 						'value' => 'freeform',
 					),
 				),
 			),
 			'wrapper' => array (
 				'width' => 50,
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
 			'key' => 'field_59761480260b7',
 			'label' => 'Kicker Icon',
 			'name' => 'freeform_kicker_icon',
 			'type' => 'select',
 			'instructions' => '',
 			'required' => 0,
 			'conditional_logic' => array (
 				array (
 					array (
 						'field' => 'field_5968bb35e5b49',
 						'operator' => '==',
 						'value' => 'freeform',
 					),
 				),
 			),
 			'wrapper' => array (
 				'width' => 50,
 				'class' => '',
 				'id' => '',
 			),
 			'choices' => array (
 				'admissions' => 'Admissions',
 				'academics' => 'Academics',
 				'building' => 'Building',
 				'chart' => 'Chart',
 				'contact' => 'Contact',
 				'events' => 'Events',
 				'news' => 'News',
 				'heart' => 'Heart',
 				'pencil' => 'Pencil',
 				'start' => 'Star',
 			),
 			'default_value' => array (
 			),
 			'allow_null' => 1,
 			'multiple' => 0,
 			'ui' => 0,
 			'ajax' => 0,
 			'placeholder' => '',
 			'disabled' => 0,
 			'readonly' => 0,
 		),
 		array (
 			'key' => 'field_5971128ed47a3',
 			'label' => 'Title',
 			'name' => 'freeform_title',
 			'type' => 'text',
 			'instructions' => '',
 			'required' => 0,
 			'conditional_logic' => array (
 				array (
 					array (
 						'field' => 'field_5968bb35e5b49',
 						'operator' => '==',
 						'value' => 'freeform',
 					),
 				),
 			),
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
 			'key' => 'field_5968c033e5b4b',
 			'label' => 'Body',
 			'name' => 'freeform_body',
 			'type' => 'wysiwyg',
 			'instructions' => '',
 			'required' => 0,
 			'conditional_logic' => array (
 				array (
 					array (
 						'field' => 'field_5968bb35e5b49',
 						'operator' => '==',
 						'value' => 'freeform',
 					),
 					array (
 						'field' => 'field_5968bb35e5b49',
 						'operator' => '==',
 						'value' => 'freeform',
 					),
 				),
 			),
 			'wrapper' => array (
 				'width' => '',
 				'class' => '',
 				'id' => '',
 			),
 			'default_value' => '',
 			'tabs' => 'all',
 			'toolbar' => 'basic',
 			'media_upload' => 0,
 		),
 		array (
 			'key' => 'field_5968c110e5b4e',
 			'label' => 'Link Text',
 			'name' => 'freeform_link_text',
 			'type' => 'text',
 			'instructions' => '',
 			'required' => 0,
 			'conditional_logic' => array (
 				array (
 					array (
 						'field' => 'field_5968bb35e5b49',
 						'operator' => '==',
 						'value' => 'freeform',
 					),
 				),
 			),
 			'wrapper' => array (
 				'width' => 50,
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
 			'key' => 'field_5968c12ae5b4f',
 			'label' => 'Link Url',
 			'name' => 'freeform_link_url',
 			'type' => 'url',
 			'instructions' => '',
 			'required' => 0,
 			'conditional_logic' => array (
 				array (
 					array (
 						'field' => 'field_5968bb35e5b49',
 						'operator' => '==',
 						'value' => 'freeform',
 					),
 				),
 			),
 			'wrapper' => array (
 				'width' => 50,
 				'class' => '',
 				'id' => '',
 			),
 			'default_value' => '',
 			'placeholder' => '',
 		),
 		array (
 			'key' => 'field_5968c6ad2ab4e',
 			'label' => 'Quicklinks',
 			'name' => '',
 			'type' => 'tab',
 			'instructions' => '',
 			'required' => 0,
 			'conditional_logic' => 0,
 			'wrapper' => array (
 				'width' => '',
 				'class' => '',
 				'id' => '',
 			),
 			'placement' => 'left',
 			'endpoint' => 0,
 		),
 		array (
 			'key' => 'field_5968c6ba2ab4f',
 			'label' => 'Quicklinks',
 			'name' => 'quicklinks',
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
 				0 => 'page',
 			),
 			'taxonomy' => array (
 			),
 			'filters' => array (
 				0 => 'search',
 			),
 			'elements' => array (
 				0 => 'featured_image',
 			),
 			'min' => 2,
 			'max' => 3,
 			'return_format' => 'object',
 		),
 		array (
 			'key' => 'field_5968c6e62ab50',
 			'label' => 'By The Numbers',
 			'name' => '',
 			'type' => 'tab',
 			'instructions' => '',
 			'required' => 0,
 			'conditional_logic' => 0,
 			'wrapper' => array (
 				'width' => '',
 				'class' => '',
 				'id' => '',
 			),
 			'placement' => 'left',
 			'endpoint' => 0,
 		),
 		array (
 			'key' => 'field_5968c8d92ab54',
 			'label' => 'Number',
 			'name' => 'numbers',
 			'type' => 'repeater',
 			'instructions' => '',
 			'required' => 0,
 			'conditional_logic' => 0,
 			'wrapper' => array (
 				'width' => '',
 				'class' => '',
 				'id' => '',
 			),
 			'collapsed' => 'field_5968c9210c4b9',
 			'min' => '',
 			'max' => 3,
 			'layout' => 'block',
 			'button_label' => 'Add Number',
 			'sub_fields' => array (
 				array (
 					'key' => 'field_5968c9210c4b9',
 					'label' => 'Number',
 					'name' => 'number',
 					'type' => 'text',
 					'instructions' => '',
 					'required' => 0,
 					'conditional_logic' => 0,
 					'wrapper' => array (
 						'width' => 20,
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
 					'key' => 'field_5968c9590c4ba',
 					'label' => 'Description',
 					'name' => 'number_description',
 					'type' => 'text',
 					'instructions' => '',
 					'required' => 0,
 					'conditional_logic' => 0,
 					'wrapper' => array (
 						'width' => 80,
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
 					'key' => 'field_5968c9be0c4bc',
 					'label' => 'Link',
 					'name' => 'number_link',
 					'type' => 'select',
 					'instructions' => '',
 					'required' => 0,
 					'conditional_logic' => 0,
 					'wrapper' => array (
 						'width' => '',
 						'class' => '',
 						'id' => '',
 					),
 					'choices' => array (
 						'page' => 'Link to page',
 						'custom' => 'Custom link',
 					),
 					'default_value' => array (
 					),
 					'allow_null' => 0,
 					'multiple' => 0,
 					'ui' => 0,
 					'ajax' => 0,
 					'placeholder' => '',
 					'disabled' => 0,
 					'readonly' => 0,
 				),
 				array (
 					'key' => 'field_5968c96c0c4bb',
 					'label' => 'Link to Page',
 					'name' => 'number_link_page',
 					'type' => 'relationship',
 					'instructions' => '',
 					'required' => 0,
 					'conditional_logic' => array (
 						array (
 							array (
 								'field' => 'field_5968c9be0c4bc',
 								'operator' => '==',
 								'value' => 'page',
 							),
 						),
 					),
 					'wrapper' => array (
 						'width' => '',
 						'class' => '',
 						'id' => '',
 					),
 					'post_type' => array (
 						0 => 'page',
 					),
 					'taxonomy' => array (
 					),
 					'filters' => array (
 						0 => 'search',
 					),
 					'elements' => '',
 					'min' => '',
 					'max' => '',
 					'return_format' => 'object',
 				),
 				array (
 					'key' => 'field_5968c9ec0c4bd',
 					'label' => 'Link Text',
 					'name' => 'number_link_text',
 					'type' => 'text',
 					'instructions' => '',
 					'required' => 0,
 					'conditional_logic' => array (
 						array (
 							array (
 								'field' => 'field_5968c9be0c4bc',
 								'operator' => '==',
 								'value' => 'custom',
 							),
 						),
 					),
 					'wrapper' => array (
 						'width' => 50,
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
 					'key' => 'field_5968ca030c4be',
 					'label' => 'Link Url',
 					'name' => 'number_link_url',
 					'type' => 'url',
 					'instructions' => '',
 					'required' => 0,
 					'conditional_logic' => array (
 						array (
 							array (
 								'field' => 'field_5968c9be0c4bc',
 								'operator' => '==',
 								'value' => 'custom',
 							),
 						),
 					),
 					'wrapper' => array (
 						'width' => 50,
 						'class' => '',
 						'id' => '',
 					),
 					'default_value' => '',
 					'placeholder' => '',
 				),
 			),
 		),
 		array (
 			'key' => 'field_59691fed1b2cc',
 			'label' => 'Numbers Background',
 			'name' => 'numbers_background',
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
 			'key' => 'field_5968ce88b57fb',
 			'label' => 'Testimonials',
 			'name' => '',
 			'type' => 'tab',
 			'instructions' => '',
 			'required' => 0,
 			'conditional_logic' => 0,
 			'wrapper' => array (
 				'width' => '',
 				'class' => '',
 				'id' => '',
 			),
 			'placement' => 'left',
 			'endpoint' => 0,
 		),
 		array (
 			'key' => 'field_5968ce9ab57fc',
 			'label' => 'Testimonial',
 			'name' => 'testimonial',
 			'type' => 'repeater',
 			'instructions' => '',
 			'required' => 0,
 			'conditional_logic' => 0,
 			'wrapper' => array (
 				'width' => '',
 				'class' => '',
 				'id' => '',
 			),
 			'collapsed' => '',
 			'min' => '',
 			'max' => '',
 			'layout' => 'row',
 			'button_label' => 'Add Testimonial',
 			'sub_fields' => array (
 				array (
 					'key' => 'field_5968ceceb57fd',
 					'label' => 'Testimonial',
 					'name' => 'testimonial_body',
 					'type' => 'textarea',
 					'instructions' => '',
 					'required' => 0,
 					'conditional_logic' => 0,
 					'wrapper' => array (
 						'width' => 70,
 						'class' => '',
 						'id' => '',
 					),
 					'default_value' => '',
 					'placeholder' => '',
 					'maxlength' => '',
 					'rows' => 6,
 					'new_lines' => 'br',
 					'readonly' => 0,
 					'disabled' => 0,
 				),
 				array (
 					'key' => 'field_5968cef2b57fe',
 					'label' => 'Credit',
 					'name' => 'testimonial_credit',
 					'type' => 'text',
 					'instructions' => 'Credit to whom gave the testimonal',
 					'required' => 0,
 					'conditional_logic' => 0,
 					'wrapper' => array (
 						'width' => 30,
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
 			),
 		),
 		array (
 			'key' => 'field_596920211b2cd',
 			'label' => 'Testimonial Image',
 			'name' => 'testimonial_image',
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
 			'key' => 'field_5968d09525865',
 			'label' => 'FAQs',
 			'name' => '',
 			'type' => 'tab',
 			'instructions' => '',
 			'required' => 0,
 			'conditional_logic' => 0,
 			'wrapper' => array (
 				'width' => '',
 				'class' => '',
 				'id' => '',
 			),
 			'placement' => 'left',
 			'endpoint' => 0,
 		),
 		array (
 			'key' => 'field_5968d0b525866',
 			'label' => 'FAQs',
 			'name' => 'faqs',
 			'type' => 'repeater',
 			'instructions' => '',
 			'required' => 0,
 			'conditional_logic' => 0,
 			'wrapper' => array (
 				'width' => '',
 				'class' => '',
 				'id' => '',
 			),
 			'collapsed' => 'field_5968d0c625867',
 			'min' => '',
 			'max' => '',
 			'layout' => 'row',
 			'button_label' => 'Add FAQ',
 			'sub_fields' => array (
 				array (
 					'key' => 'field_5968d0c625867',
 					'label' => 'Question',
 					'name' => 'faq_question',
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
 					'key' => 'field_5968d0d125868',
 					'label' => 'Answer',
 					'name' => 'faq_answer',
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
 					'toolbar' => 'basic',
 					'media_upload' => 0,
 				),
 			),
 		),
 	),
 	'location' => array (
 		array (
 			array (
 				'param' => 'page_template',
 				'operator' => '==',
 				'value' => 'views/template-landing.blade.php',
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
 	'key' => 'group_5970ec5822d20',
 	'title' => 'Page Content',
 	'fields' => array (
 		array (
 			'key' => 'field_5970ed41b05c9',
 			'label' => 'Gallery',
 			'name' => '',
 			'type' => 'tab',
 			'instructions' => '',
 			'required' => 0,
 			'conditional_logic' => 0,
 			'wrapper' => array (
 				'width' => '',
 				'class' => '',
 				'id' => '',
 			),
 			'placement' => 'left',
 			'endpoint' => 0,
 		),
 		array (
 			'key' => 'field_5978a934324b4',
 			'label' => 'Gallery Title',
 			'name' => 'gallery_title',
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
 			'key' => 'field_5970ed5ab05ca',
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
 		array (
 			'key' => 'field_5970ecc562b1c',
 			'label' => 'Accordion',
 			'name' => '',
 			'type' => 'tab',
 			'instructions' => '',
 			'required' => 0,
 			'conditional_logic' => 0,
 			'wrapper' => array (
 				'width' => '',
 				'class' => '',
 				'id' => '',
 			),
 			'placement' => 'left',
 			'endpoint' => 0,
 		),
 		array (
 			'key' => 'field_5978a32972d58',
 			'label' => 'Accordion Title',
 			'name' => 'accordion_title',
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
 			'key' => 'field_5978a25580b93',
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
 			'collapsed' => 'field_5978a28d80b94',
 			'min' => '',
 			'max' => '',
 			'layout' => 'row',
 			'button_label' => 'Add Row',
 			'sub_fields' => array (
 				array (
 					'key' => 'field_5978a28d80b94',
 					'label' => 'Accordion Heading',
 					'name' => 'accordion_heading',
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
 					'key' => 'field_5978a29380b95',
 					'label' => 'Accordion Body',
 					'name' => 'accordion_body',
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
 					'media_upload' => 1,
 				),
 			),
 		),
 	),
 	'location' => array (
 		array (
 			array (
 				'param' => 'page_template',
 				'operator' => '==',
 				'value' => 'default',
 			),
 			array (
 				'param' => 'page_type',
 				'operator' => '!=',
 				'value' => 'front_page',
 			),
 		),
 		array (
 			array (
 				'param' => 'page_template',
 				'operator' => '==',
 				'value' => 'views/template-default-blocks.blade.php',
 			),
 			array (
 				'param' => 'page_type',
 				'operator' => '!=',
 				'value' => 'front_page',
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
 	'key' => 'group_596cc6a9204d7',
 	'title' => 'Page Icon',
 	'fields' => array (
 		array (
 			'key' => 'field_596cc6b6865d6',
 			'label' => 'Page Icon',
 			'name' => 'page_icon',
 			'type' => 'select',
 			'instructions' => '',
 			'required' => 0,
 			'conditional_logic' => 0,
 			'wrapper' => array (
 				'width' => '',
 				'class' => '',
 				'id' => '',
 			),
 			'choices' => array (
 				'admissions' => 'Admissions',
 				'academics' => 'Academics',
 				'building' => 'Building',
 				'chart' => 'Chart',
 				'contact' => 'Contact',
 				'events' => 'Events',
 				'news' => 'News',
 				'heart' => 'Heart',
 				'pencil' => 'Pencil',
 				'start' => 'Star',
 			),
 			'default_value' => array (
 			),
 			'allow_null' => 1,
 			'multiple' => 0,
 			'ui' => 0,
 			'ajax' => 0,
 			'placeholder' => '',
 			'disabled' => 0,
 			'readonly' => 0,
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
 	'position' => 'side',
 	'style' => 'default',
 	'label_placement' => 'top',
 	'instruction_placement' => 'label',
 	'hide_on_screen' => '',
 	'active' => 1,
 	'description' => '',
 ));

 acf_add_local_field_group(array (
 	'key' => 'group_596f94a585a6a',
 	'title' => 'Page Settings',
 	'fields' => array (
 		array (
 			'key' => 'field_596ccc12ad77d',
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
 			'key' => 'field_596ccd1e755ae',
 			'label' => 'Intro',
 			'name' => 'intro',
 			'type' => 'textarea',
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
 			'maxlength' => '',
 			'rows' => 2,
 			'new_lines' => 'wpautop',
 			'readonly' => 0,
 			'disabled' => 0,
 		),
 		array (
 			'key' => 'field_596ccd3951cd8',
 			'label' => 'Link Text',
 			'name' => 'link_text',
 			'type' => 'text',
 			'instructions' => '',
 			'required' => 0,
 			'conditional_logic' => 0,
 			'wrapper' => array (
 				'width' => 50,
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
 			'key' => 'field_596cccf7755ad',
 			'label' => 'Link Url',
 			'name' => 'link_url',
 			'type' => 'url',
 			'instructions' => '',
 			'required' => 0,
 			'conditional_logic' => 0,
 			'wrapper' => array (
 				'width' => 50,
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
 				'param' => 'page_template',
 				'operator' => '==',
 				'value' => 'views/template-events.blade.php',
 			),
 		),
    array (
 			array (
 				'param' => 'page_template',
 				'operator' => '==',
 				'value' => 'views/template-news.blade.php',
 			),
 		),
 		array (
 			array (
 				'param' => 'page_template',
 				'operator' => '==',
 				'value' => 'default',
 			),
      array (
 				'param' => 'page_type',
 				'operator' => '!=',
 				'value' => 'front_page',
 			),
 		),
    array (
      array (
        'param' => 'page_type',
        'operator' => '==',
        'value' => 'posts_page',
      ),
    ),
 		array (
 			array (
 				'param' => 'page_template',
 				'operator' => '==',
 				'value' => 'views/template-default-blocks.blade.php',
 			),
 		),
 		array (
 			array (
 				'param' => 'page_template',
 				'operator' => '==',
 				'value' => 'views/template-landing.blade.php',
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
 	'key' => 'group_5968d815a9b6f',
 	'title' => 'Sidebar Content',
 	'fields' => array (
 		array (
 			'key' => 'field_5968d81dd1d79',
 			'label' => 'Title',
 			'name' => 'sidebar_title',
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
 			'key' => 'field_5968d883d1d7b',
 			'label' => 'Body',
 			'name' => 'sidebar_body',
 			'type' => 'textarea',
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
 			'maxlength' => '',
 			'rows' => 4,
 			'new_lines' => 'wpautop',
 			'readonly' => 0,
 			'disabled' => 0,
 		),
 		array (
 			'key' => 'field_5968d8a6d1d7d',
 			'label' => 'Link Url',
 			'name' => 'sidebar_link_url',
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
 			'key' => 'field_5968d89ad1d7c',
 			'label' => 'Link Text',
 			'name' => 'sidebar_link_text',
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
 	),
 	'location' => array (
 		array (
 			array (
 				'param' => 'page_template',
 				'operator' => '==',
 				'value' => 'default',
 			),
 			array (
 				'param' => 'page_type',
 				'operator' => '!=',
 				'value' => 'front_page',
 			),
 		),
 		array (
 			array (
 				'param' => 'page_template',
 				'operator' => '==',
 				'value' => 'views/template-default-blocks.blade.php',
 			),
 			array (
 				'param' => 'page_type',
 				'operator' => '!=',
 				'value' => 'front_page',
 			),
 		),
 	),
 	'menu_order' => 1,
 	'position' => 'side',
 	'style' => 'default',
 	'label_placement' => 'top',
 	'instruction_placement' => 'label',
 	'hide_on_screen' => '',
 	'active' => 1,
 	'description' => '',
 ));

 acf_add_local_field_group(array (
 	'key' => 'group_5967d7661f5a2',
 	'title' => 'Promotional Content',
 	'fields' => array (
 		array (
 			'key' => 'field_596cd0138ae29',
 			'label' => 'Promotional Content',
 			'name' => 'promotional_content',
 			'type' => 'relationship',
 			'instructions' => 'Select a page or post to feature at the section towards the bottom of the page',
 			'required' => 0,
 			'conditional_logic' => 0,
 			'wrapper' => array (
 				'width' => '',
 				'class' => '',
 				'id' => '',
 			),
 			'post_type' => array (
 				0 => 'page',
 				1 => 'post',
 			),
 			'taxonomy' => array (
 			),
 			'filters' => array (
 				0 => 'search',
 			),
 			'elements' => array (
 				0 => 'featured_image',
 			),
 			'min' => '',
 			'max' => 1,
 			'return_format' => 'object',
 		),
 	),
 	'location' => array (
 		array (
 			array (
 				'param' => 'post_type',
 				'operator' => '==',
 				'value' => 'page',
 			),
 			array (
 				'param' => 'page_template',
 				'operator' => '!=',
 				'value' => 'views/template-landing.blade.php',
 			),
 			array (
 				'param' => 'page_type',
 				'operator' => '!=',
 				'value' => 'front_page',
 			),
 		),
 	),
 	'menu_order' => 10,
 	'position' => 'normal',
 	'style' => 'default',
 	'label_placement' => 'top',
 	'instruction_placement' => 'label',
 	'hide_on_screen' => '',
 	'active' => 1,
 	'description' => '',
 ));

 endif;
