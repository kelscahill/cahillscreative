<?php

namespace threewp_broadcast\premium_pack\wp_job_manager;

/**
	@brief			Adds support for the <a href="https://wordpress.org/plugins/wp-job-manager/">WP Job Manager plugin</a>.
	@plugin_group	3rd party compatability
	@since			2026-05-19 21:16:44
**/
class WP_Job_Manager
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		Constructor.
		@since		2026-05-19 21:17:01
	**/
	public function _construct()
	{
		$this->add_action( 'job_manager_job_submitted' );
		$this->add_action( 'threewp_broadcast_get_post_types' );
	}

	/**
		@brief		React to a job being submitted on the front-end.
		@since		2026-05-19 21:17:27
	**/
	public function job_manager_job_submitted( $job_id )
	{
		$this->debug( 'Found job_manager_job_submitted action for job %s', $job_id );

		$post = get_post( $job_id );
		$this->debug( json_encode( $post ) );

		$_POST['ID'] = $job_id;
		$_GET[ 'post_type' ] = 'job_listing';		// This is required for the UBS post type criterion to detect the post type.
		ThreeWP_Broadcast()->save_post( $job_id );
	}

	/**
		@brief		threewp_broadcast_get_post_types
		@since		2026-05-20 19:24:02
	**/
	public function threewp_broadcast_get_post_types( $action )
	{
		$action->add_type( 'job_listing' );
	}
}
