<?php

namespace Ezoic_Namespace;

// Only create custom widget if WP_Widget is supported
if ( class_exists( 'WP_Widget' ) ) {
	class Ezoic_AdTester_Widget extends \WP_Widget {
		public function __construct() {
			parent::__construct( 'ezoic_adtester_widget', 'Ezoic AdTester Widget' );
		}

		public function widget( $args, $instance ) {
			echo $instance['embed_code'];
		}

		public function form( $instance ) {
			// Do nothing
		}
	}
}
