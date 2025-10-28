<?php

namespace threewp_broadcast\premium_pack\tablepress;

use Exception;

/**
	@brief			Adds support for broadcasting <a href="https://wordpress.org/plugins/tablepress/">TablePress</a> shortcodes.
	@plugin_group	3rd party compatability
	@since			2015-07-08 16:12:07
**/
class TablePress
	extends \threewp_broadcast\premium_pack\classes\Shortcode_Preparser
{
	/**
		@brief		Constructor.
		@since		2021-01-12 20:43:08
	**/
	public function _construct()
	{
		parent::_construct();
		$this->add_action( 'threewp_broadcast_broadcasting_after_switch_to_blog' );
	}

	/**
		@brief		Copy the item (shortcode ID) from the parent blog to this blog.
		@details	The default is to broadcast the post ID, but is overridable in case the item is not a post.
		@since		2017-01-11 23:20:49
	**/
	public function copy_item( $bcd, $item )
	{
		$table_id = $item->id;
		$tables = $this->get_tables();

		// A simple of array of equivalent shortcodes for each original shortcode.
		$shortcodes = [];

		$equivalent_table_id = false;
		$table_id_is_int = ( intval( $table_id ) . '' == $table_id );		// Why does the intval need to be converted back to a string?

		foreach ( $tables as $blog_table )
		{
			if ( $blog_table['name'] == $item->table['name'] )
			{
				$this->debug( 'Equivalent table has ID %s. Updating.', $blog_table['id'] );
				// Note the equivalent ID.
				$equivalent_table_id = $blog_table[ 'id' ];
				// And now update the existing table with current info.
				$new_table = $item->table;
				$new_table[ 'id' ] = $equivalent_table_id;
				\TablePress::$model_table->save( $new_table );
				break;
			}
		}

		if ( ! $equivalent_table_id )
		{
			// Create the new table.
			$equivalent_table_id = \TablePress::$model_table->add( $item->table );
			if ( ! $table_id_is_int )
			{
				$this->debug( 'Changing table ID from %s to %s.', $equivalent_table_id, $table_id );
				\TablePress::$model_table->change_table_id( $equivalent_table_id, $table_id );
				$equivalent_table_id = $table_id;
			}
			$this->debug( 'Created new table for %s. ID is %s', $item->table[ 'name' ], $equivalent_table_id );
		}

		return $equivalent_table_id;
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
		{
			$data = \TablePress::$model_table->load( $table_id );
			if ( is_wp_error( $data ) )
				continue;
			$r[ $table_id ] = $data;
		}

		return $r;
	}

	/**
		@brief		Return the name of the shortcode we are looking for.
		@since		2017-01-11 23:03:36
	**/
	public function get_shortcode_name()
	{
		return 'table';
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
		@brief		Allow subclases to handle the newly found item from the shortcode.
		@details	If you don't want to save this item, perhaps because the post isn't found, then throw an exception.
		@since		2017-01-12 12:25:55
	**/
	public function remember_item( $bcd, $item )
	{
		if ( ! $this->has_tablepress() )
			return;

		global $wpdb;

		$table_id = $item->id;

		$table = \TablePress::$model_table->load( $table_id );

		if ( ! $table )
		{
			$this->debug( 'Found shortcode with ID %s, but that ID is unknown by TablePress. Ignoring.', $table_id );
			throw new Exception();
		}
		$item->table = $table;
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
}
