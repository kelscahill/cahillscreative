<?php

namespace threewp_broadcast\premium_pack\tablepress;

/**
	@brief			Adds support for broadcasting <a href="https://wordpress.org/plugins/tablepress/">TablePress</a> shortcodes.
	@plugin_group	3rd party compatability
	@since			2015-07-08 16:12:07
**/
class TablePress
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_filter( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_broadcasting_after_switch_to_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_modify_post' );
	}

	/**
		@brief		Get the TablePress tables object.
		@since		2015-07-08 22:11:42
	**/
	public function get_tables()
	{
		$tables = \TablePress::$model_table->load_all();
		$r = [];
		foreach( $tables as $table_id )
			$r[ $table_id ] = \TablePress::$model_table->load( $table_id );

		return $r;
	}

	/**
		@brief		Is tablepress installed?
		@since		2015-07-08 21:00:08
	**/
	public function has_tablepress()
	{
		return defined( 'TABLEPRESS_ABSPATH' );
	}

	/**
		@brief		threewp_broadcast_broadcasting_after_switch_to_blog
		@since		2015-07-08 22:23:53
	**/
	public function threewp_broadcast_broadcasting_after_switch_to_blog( $action )
	{
		if ( ! $this->has_tablepress() )
			return;

		// Rerun the whole of TablePress in order to clear the caches.
		$this->debug( 'Reloading TablePress.' );
		\TablePress::run();
	}

	/**
		@brief		threewp_broadcast_broadcasting_modify_post
		@since		2015-07-08 21:01:00
	**/
	public function threewp_broadcast_broadcasting_modify_post( $action )
	{
		if ( ! $this->has_tablepress() )
			return;

		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->tablepress ) )
			return;

		$tp = $bcd->tablepress;

		$tables = $this->get_tables();

		// A simple of array of equivalent shortcodes for each original shortcode.
		$shortcodes = [];

		foreach( $tp->collection( 'tables' ) as $table_id => $table )
		{
			$equivalent_table_id = false;
			$table_id_is_int = ( intval( $table_id ) . '' == $table_id );		// Why does the intval need to be converted back to a string?

			foreach( $tables as $blog_table )
				if ( $blog_table[ 'name' ] == $table->table[ 'name' ] )
				{
					$this->debug( 'Equivalent table has ID %s. Updating.', $blog_table[ 'id' ] );
					// Note the equivalent ID.
					$equivalent_table_id = $blog_table[ 'id' ];
					// And now update the existing table with current info.
					$new_table = $table->table;
					$new_table[ 'id' ] = $equivalent_table_id;
					\TablePress::$model_table->save( $new_table );
					break;
				}

			if ( ! $equivalent_table_id )
			{
				// Create the new table.
				$equivalent_table_id = \TablePress::$model_table->add( $table->table );
				if ( ! $table_id_is_int )
				{
					$this->debug( 'Changing table ID from %s to %s.', $equivalent_table_id, $table_id );
					\TablePress::$model_table->change_table_id( $equivalent_table_id, $table_id );
					$equivalent_table_id = $table_id;
				}
				$this->debug( 'Created new table for %s. ID is %s', $table->table[ 'name' ], $equivalent_table_id );
			}

			if ( $table_id_is_int )
				$shortcodes[ $table->shortcode ] = str_replace( $table->id, $equivalent_table_id, $table->shortcode );
			else
				$this->debug( 'The shortcode does not use an integer ID. It uses %s. Will not replace anything.', $table_id );
		}

		foreach( $shortcodes as $old_shortcode => $new_shortcode )
		{
			$this->debug( 'Replacing shortcode %s with %s', $old_shortcode, $new_shortcode );
			$bcd->modified_post->post_content = str_replace( $old_shortcode, $new_shortcode, $bcd->modified_post->post_content );
		}
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2015-07-08 21:01:00
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		if ( ! $this->has_tablepress() )
			return;

		$bcd = $action->broadcasting_data;

		// Does this post have any tablepress shortcodes?
		$matches = ThreeWP_Broadcast()->find_shortcodes( $bcd->post->post_content, [ 'table' ] );

		$this->debug( 'Table shortcodes found: %s', count( $matches[ 0 ] ) );

		if ( count( $matches[ 0 ] ) < 1 )
			return;

		$tp = ThreeWP_Broadcast()->collection();		// tp is shorter than tablepress.
		$bcd->tablepress = $tp;

		foreach( $matches[ 0 ] as $index => $shortcode )
		{
			$attributes = shortcode_parse_atts( $shortcode );

			if ( ! isset( $attributes[ 'id' ] ) )
			{
				$this->debug( 'Shortcode %s has no ID. Ignoring.', $shortcode );
				continue;
			}

			$table_id = $attributes[ 'id' ];

			$table = (object)[];
			$table->shortcode = $shortcode;
			$table->id = $table_id;
			$table->table = \TablePress::$model_table->load( $table->id );

			if ( ! $table->table )
			{
				$this->debug( 'Found shortcode %s, with ID %s, but that ID is unknown by TablePress. Ignoring.', $shortcode, $table_id );
				continue;
			}

			$this->debug( 'Storing TablePress shortcode: %s', $table );

			$tp->collection( 'tables' )->set( $table->id, $table );
		}
	}
}
