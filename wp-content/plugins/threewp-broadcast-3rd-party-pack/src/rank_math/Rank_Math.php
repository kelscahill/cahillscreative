<?php

namespace threewp_broadcast\premium_pack\rank_math;

/**
	@brief			Adds support for the <a href="https://wordpress.org/plugins/seo-by-rank-math/">Rank Math</a> plugin.
	@plugin_group	3rd party compatability
	@since			2021-04-03 18:55:26
**/
class Rank_Math
	extends \threewp_broadcast\premium_pack\base
{
	use \threewp_broadcast\premium_pack\classes\database_trait;

	/**
		@brief		Constructor.
		@since		2021-04-02 20:39:44
	**/
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );

		// Disable the canonical.
		add_filter( 'rank_math/frontend/canonical', function( $canonical )
		{
			return '';
		} );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2021-04-02 20:41:41
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->rank_math ) )
			return;

		global $wpdb;

		$row = $bcd->rank_math->get( 'rank_math_analytics_objects' );
		if ( $row )
		{
			$table = $this->database_table( 'rank_math_analytics_objects' );

			$data = clone( $row );
			unset( $data->id );

			$new_post_id = $bcd->new_post( 'ID' );

			$query = sprintf( "DELETE FROM `%s` WHERE `object_type` = 'post' AND `object_id` = '%s'",
				$table,
				$new_post_id
			);
			$this->debug( $query );
			$row = $wpdb->get_row( $query );

			$data->object_id = $new_post_id;

			$this->debug( 'Inserting %s', $data );
			$wpdb->insert( $table, (array) $data );
		}
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2021-04-02 20:41:15
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		global $wpdb;
		$bcd = $action->broadcasting_data;

		$table = $this->database_table( 'rank_math_analytics_objects' );
		if ( $this->database_table_exists( $table ) )
		{
			$this->prepare_broadcasting_data( $bcd );

			// Return the row for this object.
			$query = sprintf( "SELECT * FROM `%s` WHERE `object_type` = 'post' AND `object_id` = '%s'",
				$table,
				$bcd->post->ID
			);
			$this->debug( $query );
			$row = $wpdb->get_row( $query );

			$this->debug( 'Saved analytics row: %s', $row );
			$bcd->rank_math->set( 'rank_math_analytics_objects', $row );
		}

	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Add the data storage.
		@since		2021-04-02 20:40:22
	**/
	public function prepare_broadcasting_data( $broadcasting_data )
	{
		if ( ! isset( $broadcasting_data->rank_math ) )
			$broadcasting_data->rank_math = ThreeWP_Broadcast()->collection();
	}
}
