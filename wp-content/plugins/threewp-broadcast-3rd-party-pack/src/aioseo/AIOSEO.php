<?php

namespace threewp_broadcast\premium_pack\aioseo;

/**
	@brief				Adds support for the <a href="https://wordpress.org/plugins/all-in-one-seo-pack/">All in One SEO</a> plugin.
	@plugin_group		3rd party compatability
	@since				2021-02-17 14:58:24
**/
class AIOSEO
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
	}

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2021-02-17 15:03:06
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->aioseo ) )
			return;

		global $wpdb;
		$new_post_id = $bcd->new_post( 'ID' );

		$table = $wpdb->prefix . 'aioseo_posts';
		$wpdb->delete( $table, [ 'post_id' => $new_post_id ] );

		$row = $bcd->aioseo->get( 'row' );
		$row = (array) $row;
		unset( $row[ 'id' ] );
		$row[ 'post_id' ] = $new_post_id;

		$this->debug( 'Inserting row %s', $row );
		$wpdb->insert( $table, $row );
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2021-02-17 14:58:24
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;

		global $wpdb;
		$table = $wpdb->prefix . 'aioseo_posts';
		$query = sprintf( "SELECT * FROM `%s` WHERE `post_id` = '%s'",
			$table,
			$bcd->post->ID
		);
		$this->debug( $query );
		$row = $wpdb->get_row( $query );
		if ( $row )
		{
			$bcd->aioseo = ThreeWP_Broadcast()->collection();
			$this->debug( 'Saving row: %s', $row );
			$bcd->aioseo->set( 'row', $row );
		}
	}
}
