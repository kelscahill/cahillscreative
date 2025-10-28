<?php

namespace threewp_broadcast\premium_pack\jetengine;

/**
	* @brief		Handle the custom queries.
	* @since		2025-05-09 21:22:17
**/
class Queries
	extends \threewp_broadcast\premium_pack\base
{
	/**
	 * Return all of the queries stored on this blog.
	 *
	 * @since		2025-05-09 21:24:58
	 **/
	public function get_all()
	{
		global $wpdb;
		$table = sprintf( '%sjet_post_types', $wpdb->prefix );

		// Find the existing row, if any.
		$query = sprintf( "SELECT * FROM `%s` WHERE `status` = 'query'",
			$table,
		);
		return $wpdb->get_results( $query );
	}

	/**
	 * Return the row with this ID.
	 *
	 * @since		2025-05-09 21:48:24
	 **/
	public function get_by_id( $id )
	{
		$all = $this->get_all();
		foreach( $all as $row )
			if ( $row->id == $id )
				return $row;
		return false;
	}

	/**
	 * Find the query that has this name.
	 *
	 * @since		2025-05-09 21:24:31
	 **/
	public function get_by_name( $name )
	{
		$all = $this->get_all();
		foreach( $all as $row )
		{
			if ( $this->get_name( $row ) != $name )
				continue;
			return $row;
		}
		return false;
	}

	/**
	 * Return the name of the query, from the SQL result row.
	 *
	 * @since		2025-05-09 21:29:42
	 **/
	public function get_name( $row )
	{
		$labels = maybe_unserialize( $row->labels );
		return $labels[ 'name' ];
	}

	/**
	 * Return the table name.
	 *
	 * @since		2025-05-09 21:51:50
	 **/
	public function get_table()
	{
		global $wpdb;
		$table = sprintf( '%sjet_post_types', $wpdb->prefix );
		return $table;
	}

	/**
	 * Insert this database row into the db.
	 *
	 * @details		Does all of the necessary processing to insert it via a wpdb call.
	 * @since		2025-05-09 21:52:58
	 **/
	public function insert( $db_row )
	{
		global $wpdb;

		$array = (array) $db_row;
		unset( $array [ 'id' ] );

		$table = $this->get_table();

		return $wpdb->insert( $table, $array );
	}

	/**
	 * Convenience function to either insert a new row, or update an existing one, depending of the name of the query.
	 *
	 * @since		2025-05-09 21:55:51
	 **/
	public function insert_or_update( $db_row )
	{
		global $wpdb;

		$array = (array) $db_row;
		unset( $array [ 'id' ] );

		$table = $this->get_table();

		$name = $this->get_name( $db_row );

		$existing_row = $this->get_by_name( $name );
        broadcast_jetengine()->debug( 'Insert or updating: Looking for %s and found %s', $db_row, $existing_row );
		if ( ! $existing_row )
			$this->insert( $db_row );
		else
		{
			$array[ 'id' ] = $existing_row->id;
			$this->update( $array );
		}
	}

	/**
	 * Update this database row into the table.
	 *
	 * @since		2025-05-09 21:54:32
	 **/
	public function update( $db_row )
	{
		global $wpdb;

		$array = (array) $db_row;
		$table = $this->get_table();

		return $wpdb->update( $table, $array, [ 'id' => $array[ 'id' ] ] );
	}
}