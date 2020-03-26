<?php

namespace threewp_broadcast\maintenance\checks\view_user_info;

/**
	@brief		View the info of a user.
	@since		2019-09-23 20:19:09
**/
class check
	extends \threewp_broadcast\maintenance\checks\check
{
	/**
		@brief		Get the description.
		@since		2019-09-23 20:27:38
	**/
	public function get_description()
	{
		// Maintenance check description
		return __( 'View user information including metadata.', 'threewp-broadcast' );
	}
	/**
		@brief		Name.
		@since		2019-09-23 20:19:28
	**/
	public function get_name()
	{
		// Maintenance check name
		return __( 'View user info', 'threewp-broadcast' );
	}

	public function step_start()
	{
		$o = new \stdClass;
		$o->inputs = new \stdClass;
		$o->form = $this->broadcast()->form2();
		$o->r = '';

		$users = get_users();
		$users_opts = [];
		foreach( $users as $user )
			$users_opts[ $user->data->ID ] = $user->data->user_login;

		$o->inputs->user_id = $o->form->select( 'user_id' )
			->label( __( 'User', 'threewp-broadcast' ) )
			->opts( $users_opts )
			->value( 0 );

		$button = $o->form->primary_button( 'dump' )
			// Button
			->value( __( 'Display the user info', 'threewp-broadcast' ) );

		if ( $o->form->is_posting() )
		{
			$o->form->post()->use_post_value();
			$this->view_post_info( $o );
		}

		$o->r .= $o->form->open_tag();
		$o->r .= $o->form->display_form_table();
		$o->r .= $o->form->close_tag();
		return $o->r;
	}

	public function view_post_info( $o )
	{
		$user_id = $o->inputs->user_id->get_post_value();

		$user = get_user_by( 'ID', $user_id );

		$user->meta = get_user_meta( $user_id );

		$text = sprintf( '<pre>%s</pre>', stripslashes( var_export( $user, true ) ) );
		$o->r .= $this->broadcast()->message( $text );
	}
}
