<?php
/**
 *
 * @file
 * Register custom content types.
 *
 * @package WordPress
 */

function register_custom_post_types() {
  if (is_main_site()) {

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
      "taxonomies" => array( "work_tag" ),
    );

    register_post_type( "work", $args );
  } else {
    /**
     * Post Type: Renovations.
     */

    $labels = array(
      "name" => __( "Renovations", "sage" ),
      "singular_name" => __( "Renovation", "sage" ),
      "menu_name" => __( "Renovations", "sage" ),
      "all_items" => __( "All Renovations", "sage" ),
      "add_new" => __( "Add New Renovation", "sage" ),
      "add_new_item" => __( "Add New Renovation Item", "sage" ),
      "edit_item" => __( "Edit Renovation", "sage" ),
      "new_item" => __( "New Renovation", "sage" ),
      "view_item" => __( "View Renovation", "sage" ),
      "view_items" => __( "View Renovation", "sage" ),
      "search_items" => __( "Search Renovations", "sage" ),
      "not_found" => __( "No Renovations Found", "sage" ),
      "not_found_in_trash" => __( "No Renovations Found in Trash", "sage" ),
      "parent_item_colon" => __( "Parent Renovation", "sage" ),
      "parent_item_colon" => __( "Parent Renovation", "sage" ),
    );

    $args = array(
      "label" => __( "Renovations", "sage" ),
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
      "rewrite" => array( "slug" => "renovation", "with_front" => true ),
      "query_var" => true,
      "menu_icon" => "dashicons-admin-appearance",
      "supports" => array( "title", "editor", "thumbnail", "excerpt" ),
      "taxonomies" => array( "renovation_category" ),
    );

    register_post_type( "renovation", $args );

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
      "publicly_queryable" => false,
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
      "taxonomies" => array( "product_cat", "product_tag", "store", "room", "rv" ),
      "yarpp_support" => true,
    );

    register_post_type( "affiliate", $args );
  }
}
add_action('init', 'register_custom_post_types');
