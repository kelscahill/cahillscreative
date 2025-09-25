<?php

namespace threewp_broadcast\maintenance\checks\view_option;

use \threewp_broadcast\BroadcastData;

/**
	* @brief		View an option.
	* @since		2025-07-10 23:03:10
**/
class check
	extends \threewp_broadcast\maintenance\checks\check
{
	public function get_description()
	{
		// Maintenance check description
		return __( 'View the contents of an option.', 'threewp-broadcast' );
	}

	public function get_name()
	{
		// Maintenance check name
		return __( 'View option', 'threewp-broadcast' );
	}

	public function step_start()
	{
		$o = new \stdClass;
		$o->inputs = new \stdClass;
		$o->form = $this->broadcast()->form2();
		$o->r = '';

		$o->inputs->option_name = $o->form->select( 'option_name' )
			->description( __( 'The name of the option to view', 'threewp-broadcast' ) )
			->label( __( 'Option name', 'threewp-broadcast' ) );

		global $wpdb;
		$query = sprintf( "SELECT `option_name` FROM `%s` ORDER BY `option_name`", $wpdb->options );
		$all_options = $wpdb->get_col( $query );
		foreach( $all_options as $an_option )
			$o->inputs->option_name->opt( $an_option, $an_option );

		$button = $o->form->primary_button( 'dump' )
			// Button
			->value( __( 'Find and display the option', 'threewp-broadcast' ) );

		if ( $o->form->is_posting() )
		{
			$o->form->post()->use_post_value();
			$this->view_option( $o );
		}

		$o->r .= $o->form->open_tag();
		$o->r .= $o->form->display_form_table();
		$o->r .= $o->form->close_tag();
		return $o->r;
	}

	public function view_option( $o )
	{
		$option_name = $o->inputs->option_name->get_filtered_post_value();

		$option = get_option( $option_name );

		$option = maybe_unserialize( $option );

		$o->r .= sprintf( '<pre>%s</pre>', stripslashes( var_export( $option, true ) ) );
	}
}
