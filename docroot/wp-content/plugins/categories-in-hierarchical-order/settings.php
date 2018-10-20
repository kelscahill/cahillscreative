<?php if ( ! defined( 'WPINC' ) ) {
	die;
}

/*
Plugin Name: Categories in Hierarchical Order
Plugin URI: https://www.astech.solutions/wordpress-javascript-jquery-plugins/categories-hierarchical-order/
Description: This plugin maintains the hierarchical order of categories list in Category tab under your WordPress admin post editor.
Version: 1.3.1
Author: AS Tech Solutions
Author URI: https://www.astech.solutions/
Text Domain: categories-in-hierarchical-order
Domain Path: /languages
License: GPLv2 or later
*/

if ( ! class_exists( 'ftChangeTaxonomyCheckboxlistOrder' ) ) {

	class ftChangeTaxonomyCheckboxlistOrder {

		function __construct() {

			function changeTaxonomyCheckboxlistOrder( $args, $post_id ) {
				if ( isset( $args['taxonomy'] ) ) {
					$args['checked_ontop'] = false;
				}

				return $args;
			}

			add_filter( 'wp_terms_checklist_args', 'changeTaxonomyCheckboxlistOrder', 10, 2 );
		}

	} // class ends here

	$fttaxonomychangeorder = new ftChangeTaxonomyCheckboxlistOrder();

}// top most if condition ends here