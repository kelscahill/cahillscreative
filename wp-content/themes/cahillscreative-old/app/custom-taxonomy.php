<?php
/**
 *
 * @file
 * Register custom taxonomies.
 *
 * @package WordPress
 */

/**
 * Registers custom taxonomies.
 */
function register_custom_taxonomy() {
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
    "show_ui" => true,
    "show_in_menu" => true,
    "show_in_nav_menus" => true,
    "query_var" => true,
    "rewrite" => array( 'slug' => 'room', 'with_front' => true, ),
    "show_admin_column" => true,
    "show_in_rest" => true,
    "rest_base" => "",
    "show_in_quick_edit" => true,
  );
  register_taxonomy( "room", array( "post", "affiliate", "plans" ), $args );

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
    "show_ui" => true,
    "show_in_menu" => true,
    "show_in_nav_menus" => true,
    "query_var" => true,
    "rewrite" => array( 'slug' => 'cost', 'with_front' => true, ),
    "show_admin_column" => true,
    "show_in_rest" => true,
    "rest_base" => "",
    "show_in_quick_edit" => true,
  );
  register_taxonomy( "cost", array( "post", "plans" ), $args );

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
    "show_ui" => true,
    "show_in_menu" => true,
    "show_in_nav_menus" => true,
    "query_var" => true,
    "rewrite" => array( 'slug' => 'project', 'with_front' => true, ),
    "show_admin_column" => true,
    "show_in_rest" => true,
    "rest_base" => "",
    "show_in_quick_edit" => true,
  );
  register_taxonomy( "project", array( "post", "plans" ), $args );

  /**
   * Taxonomy: Skill Levels.
   */

  $labels = array(
    "name" => __( "Skill Level", "sage" ),
    "singular_name" => __( "Skill Level", "sage" ),
    "menu_name" => __( "Skill Levels", "sage" ),
  );

  $args = array(
    "label" => __( "Skill Level", "sage" ),
    "labels" => $labels,
    "public" => true,
    "hierarchical" => false,
    "show_ui" => true,
    "show_in_menu" => true,
    "show_in_nav_menus" => true,
    "query_var" => true,
    "rewrite" => array( 'slug' => 'skill-level', 'with_front' => true, ),
    "show_admin_column" => true,
    "show_in_rest" => true,
    "rest_base" => "",
    "show_in_quick_edit" => true,
  );
  register_taxonomy( "skill_level", array( "post", "plans" ), $args );

  /**
   * Taxonomy: Work Tags.
   */

  $labels = array(
    "name" => __( "Work Tags", "sage" ),
    "singular_name" => __( "Work Tag", "sage" ),
    "menu_name" => __( "Tags", "sage" ),
  );

  $args = array(
    "label" => __( "Work Tags", "sage" ),
    "labels" => $labels,
    "public" => true,
    "hierarchical" => false,
    "show_ui" => true,
    "show_in_menu" => true,
    "show_in_nav_menus" => true,
    "query_var" => true,
    "rewrite" => array( 'slug' => 'work-tag', 'with_front' => true, ),
    "show_admin_column" => true,
    "show_in_rest" => true,
    "rest_base" => "",
    "show_in_quick_edit" => true,
  );
  register_taxonomy( "work_tag", array( "work" ), $args );

  /**
   * Taxonomy: Renovation Category.
   */

  $labels = array(
    "name" => __( "Renovation Categories", "sage" ),
    "singular_name" => __( "Renovation Category", "sage" ),
    "menu_name" => __( "Categories", "sage" ),
  );

  $args = array(
    "label" => __( "Renovation Category", "sage" ),
    "labels" => $labels,
    "public" => true,
    "hierarchical" => true,
    "show_ui" => true,
    "show_in_menu" => true,
    "show_in_nav_menus" => true,
    "query_var" => true,
    "rewrite" => array( 'slug' => 'renovation-category', 'with_front' => true, ),
    "show_admin_column" => true,
    "show_in_rest" => true,
    "rest_base" => "",
    "show_in_quick_edit" => true,
  );
  register_taxonomy( "renovation_category", array( "renovation" ), $args );

  /**
   * Taxonomy: Store.
   */

  $labels = array(
    "name" => __( "Store", "sage" ),
    "singular_name" => __( "Store", "sage" ),
    "menu_name" => __( "Stores", "sage" ),
  );

  $args = array(
    "label" => __( "Store", "sage" ),
    "labels" => $labels,
    "public" => true,
    "hierarchical" => false,
    "show_ui" => true,
    "show_in_menu" => true,
    "show_in_nav_menus" => true,
    "query_var" => true,
    "rewrite" => array( 'slug' => 'store', 'with_front' => true, ),
    "show_admin_column" => true,
    "show_in_rest" => true,
    "rest_base" => "",
    "show_in_quick_edit" => true,
  );
  register_taxonomy( "store", array( "affiliate", "product" ), $args );

  /**
   * Taxonomy: Product Category.
   */

  $labels = array(
    "name" => __( "Categories", "sage" ),
    "singular_name" => __( "Category", "sage" ),
    "menu_name" => __( "Categories", "sage" ),
  );

  $args = array(
    "label" => __( "Category", "sage" ),
    "labels" => $labels,
    "public" => true,
    "hierarchical" => true,
    "show_ui" => true,
    "show_in_menu" => true,
    "show_in_nav_menus" => true,
    "query_var" => true,
    "rewrite" => array( 'slug' => 'product-category', 'with_front' => true, ),
    "show_admin_column" => true,
    "show_in_rest" => true,
    "rest_base" => "",
    "show_in_quick_edit" => true,
  );
  register_taxonomy( "affiliate_category", array( "product", "affiliate" ), $args );

  /**
   * Taxonomy: Product Tag.
   */

  $labels = array(
    "name" => __( "Tags", "sage" ),
    "singular_name" => __( "Tag", "sage" ),
    "menu_name" => __( "Tags", "sage" ),
  );

  $args = array(
    "label" => __( "Tag", "sage" ),
    "labels" => $labels,
    "public" => true,
    "hierarchical" => false,
    "show_ui" => true,
    "show_in_menu" => true,
    "show_in_nav_menus" => true,
    "query_var" => true,
    "rewrite" => array( 'slug' => 'product-tag', 'with_front' => true, ),
    "show_admin_column" => true,
    "show_in_rest" => true,
    "rest_base" => "",
    "show_in_quick_edit" => true,
  );
  register_taxonomy( "affiliate_tag", array( "product", "affiliate" ), $args );

  /**
   * Taxonomy: RV.
   */

  $labels = array(
    "name" => __( "RVs", "sage" ),
    "singular_name" => __( "RV", "sage" ),
    "menu_name" => __( "RVs", "sage" ),
  );

  $args = array(
    "label" => __( "RV", "sage" ),
    "labels" => $labels,
    "public" => true,
    "hierarchical" => true,
    "show_ui" => true,
    "show_in_menu" => true,
    "show_in_nav_menus" => true,
    "query_var" => true,
    "rewrite" => array( 'slug' => 'rv', 'with_front' => true, ),
    "show_admin_column" => true,
    "show_in_rest" => true,
    "rest_base" => "",
    "show_in_quick_edit" => true,
  );
  register_taxonomy( "rv", array( "product", "affiliate" ), $args );
}
add_action('init', 'register_custom_taxonomy');
