<?php
/*
Plugin Name: Categories in Hierarchical Order
Plugin URI: http://dev.fellowtuts.com/categories-in-hierarchical-order-plugin/
Description: This plugin maintains the hierarchical order of categories list in Category tab under your WordPress admin post editor.
Version: 1.2
Author: Kamal Agrawal, Amit Sonkhiya
Author URI: http://fellowtuts.com
License: GPLv2 or later
*/

if ( ! class_exists( 'ftChangeTaxonomyCheckboxlistOrder' ) ){
	
	class ftChangeTaxonomyCheckboxlistOrder {	
		
		function __construct(){
	
			function changeTaxonomyCheckboxlistOrder( $args, $post_id)
			{
				if ( isset( $args['taxonomy']))
		 			$args['checked_ontop'] = false;
    	   		return $args;
			}
	
		add_filter('wp_terms_checklist_args','changeTaxonomyCheckboxlistOrder',10,2);
	}
	
} // class ends here

$fttaxonomychangeorder = new ftChangeTaxonomyCheckboxlistOrder();

}// top most if condition ends here