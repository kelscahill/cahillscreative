<?php
/**
 *
 * @file
 * Register custom content types.
 *
 * @package WordPress
 */

function register_custom_post_types() {
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
    "taxonomies" => array( "category", "affiliate_tag", "store", "room" ),
    "yarpp_support" => true,
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
    "taxonomies" => array( "work_tag" ),
  );

  register_post_type( "work", $args );
}
add_action('init', 'register_custom_post_types');
