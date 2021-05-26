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
    "label" => "Room",
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
    "show_admin_column" => true,
    "show_in_rest" => true,
    "rest_base" => "",
    "show_in_quick_edit" => true,
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
    "show_admin_column" => true,
    "show_in_rest" => true,
    "rest_base" => "",
    "show_in_quick_edit" => true,
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
    "show_admin_column" => true,
    "show_in_rest" => true,
    "rest_base" => "",
    "show_in_quick_edit" => true,
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
    "show_admin_column" => true,
    "show_in_rest" => true,
    "rest_base" => "",
    "show_in_quick_edit" => true,
  );
  register_taxonomy( "skill_level", array( "post" ), $args );

  /**
   * Taxonomy: Affiliate Tags.
   */

  $labels = array(
    "name" => __( "Affiliate Tags", "sage" ),
    "singular_name" => __( "Affiliate Tag", "sage" ),
  );

  $args = array(
    "label" => __( "Affiliate Tags", "sage" ),
    "labels" => $labels,
    "public" => true,
    "hierarchical" => false,
    "label" => "Affiliate Tags",
    "show_ui" => true,
    "show_in_menu" => true,
    "show_in_nav_menus" => true,
    "query_var" => true,
    "rewrite" => array( 'slug' => 'affiliate-tag', 'with_front' => true, ),
    "show_admin_column" => true,
    "show_in_rest" => true,
    "rest_base" => "",
    "show_in_quick_edit" => true,
  );
  register_taxonomy( "affiliate_tag", array( "affiliate" ), $args );

  /**
   * Taxonomy: Work Tags.
   */

  $labels = array(
    "name" => __( "Work Tags", "sage" ),
    "singular_name" => __( "Work Tag", "sage" ),
  );

  $args = array(
    "label" => __( "Work Tags", "sage" ),
    "labels" => $labels,
    "public" => true,
    "hierarchical" => false,
    "label" => "Work Tags",
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

}
add_action('init', 'register_custom_taxonomy');
