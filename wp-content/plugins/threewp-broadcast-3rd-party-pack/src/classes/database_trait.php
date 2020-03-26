<?php

namespace threewp_broadcast\premium_pack\classes;

/**
	@brief		Database handling methods.
	@since		2017-04-23 14:13:52
**/
trait database_trait
{
	/**
		@brief		Check whether the database table exists.
		@since		2018-02-16 09:44:57
	**/
	public function database_table_exists( $table_name )
	{
		global $wpdb;
		$query = sprintf( "SHOW TABLES LIKE '%s'", $table_name );
		$results = $wpdb->get_results( $query );
		return count( $results ) > 0;
	}

	/**
		@brief		Check for the existence of a database table.
		@since		2017-04-23 14:14:02
	**/
	public function database_table_must_exist( $table_name )
	{
		if ( ! $this->database_table_exists( $table_name ) )
			wp_die( sprintf( "Broadcast fatal error! The table <em>%s</em> does not exist on this blog, even though it should (%s)", $table_name, get_called_class() ) );
	}

	/**
		@brief		Return an array of column names for this table.
		@see		get_database_table_columns_string()
		@since		2018-02-16 09:31:38
	**/
	public function get_database_table_columns( $table, $options = [] )
	{
		$options = (object) array_merge( [
			/**
				@brief		Except these column.
				@since		2018-02-16 09:35:26
			**/
			'except' => [],
		], $options );
		global $wpdb;
		$query = sprintf( "DESCRIBE `%s`", $table );
		$results = $wpdb->get_results( $query );
		$r = [];
		foreach( $results as $result )
		{
			$field = $result->Field;
			if ( in_array( $field, $options->except ) )
				continue;
			$r []= $field;
		}
		return $r;

	}

	/**
		@brief		Return the column names for this table as a string.
		@see		get_database_table_columns()
		@since		2018-02-16 09:32:14
	**/
	public function get_database_table_columns_string( $table, $options = [] )
	{
		$columns = $this->get_database_table_columns( $table, $options );
		if ( count( $columns ) < 1 )
			return '';
		return '`' . implode( "`,`", $columns ) . '`';
	}

	/**
		@brief		Convenience method to return the prefixed table name.
		@since		2020-01-23 09:27:22
	**/
	public static function get_prefixed_table_name( $table_name )
	{
		global $wpdb;
		return sprintf( "%s%s", $wpdb->prefix, $table_name );
	}
}
