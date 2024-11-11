<?php
/**
 * Admin Data class - the main class for handling data changes in wp-admin
 *
 * TODO - this might need deprecating - it used to store our logic for
 * save and updating our CPTs, but now everything is done via the rest api
 * Perhaps we should use this as a wrapper for saving and loading data assoc
 * with the rest api
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter
 * @subpackage Search_Filter/Admin
 */

namespace Search_Filter\Admin;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Data {
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 3.0.0
	 *
	 * @param class $screens  Instance of the screens class.
	 */
	public function __construct( $screens ) {
		$this->screens = $screens;
	}
}
