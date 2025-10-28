<?php

namespace threewp_broadcast\premium_pack\classes;

/**
	@brief		Database handling methods.
	@since		2017-04-23 14:13:52
**/
trait database_trait
{
	/**
		@brief		Return the complete database table name.
		@since		2020-03-17 19:51:10
	**/
	public function database_table( $table )
	{
		global $wpdb;
		return $wpdb->prefix . $table;
	}

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
	public static function get_prefixed_table_name( $table_name, $blog_id = null )
	{
		global $wpdb;

		if ( $blog_id > 0 )
			switch_to_blog( $blog_id );

		$r = sprintf( "%s%s", $wpdb->prefix, $table_name );

		if ( $blog_id > 0 )
			restore_current_blog();

		return $r;
	}

	/**
		@brief		Sync the rows of two tables.
		@since		2019-12-11 17:47:42
	**/
	public function sync_database_rows( $options )
	{
		$options = array_merge( [
			'debug_class' => $this,				// Which class to use for debug() calls.
			'except' => [ 'id' ],				// Do not sync these columns
			'source' => 'source',				// Source table name
			'source_value' => '123',			// The item value of the source.
			'target' => 'target',				// Target table name
			'target_value' => '234',			// The item value of the target to update.
			'unique_column' => 'field_key',		// Which column to use as the unique row key.
			'value_column' => 'form_id',		// From which column to fetch the value of the item.
		], $options );

		// Objects are easier to work with.
		$options = (object) $options;

		// Conv.
		$debug_class = $options->debug_class;

		global $wpdb;

		$columns = $this->get_database_table_columns( $options->source, (array)$options );
		$columns_string = '`' . implode( "`,`", $columns ) . '`';

		// Delete rows that no longer exist on the target.
		$query = sprintf( "DELETE FROM `%s` WHERE `%s` = '%s' AND `%s` NOT IN ( SELECT `%s` FROM `%s` WHERE `%s` = '%s' )",
			$options->target,
			$options->value_column,
			$options->target_value,
			$options->unique_column,
			$options->unique_column,
			$options->source,
			$options->value_column,
			$options->source_value
		);
		$debug_class->debug( $query );
		$wpdb->get_results( $query );

		// Find existing rows, so we can update them.
		$query = sprintf( "
			( SELECT %s FROM `%s` WHERE `%s` = '%s' AND `%s` IN
			  ( SELECT `%s` FROM `%s` WHERE `%s` = '%s' )
			)"
			,
			$columns_string,
			$options->source,
			$options->value_column,
			$options->source_value,
			$options->unique_column,
			$options->unique_column,
			$options->target,
			$options->value_column,
			$options->target_value
		);
		$debug_class->debug( $query );
		$existing_rows = $wpdb->get_results( $query );

		$debug_class->debug( '%s existing rows found.', count( $existing_rows ) );

		// Insert new rows.
		$query = sprintf( "
			INSERT INTO `%s` ( `%s`, %s )
			( SELECT '%s',%s FROM `%s` WHERE `%s` = '%s' AND `%s` NOT IN
			  ( SELECT `%s` FROM `%s` WHERE `%s` = '%s' )
			)"
			,
			$options->target,
			$options->value_column,
			$columns_string,
			$options->target_value,
			$columns_string,
			$options->source,
			$options->value_column,
			$options->source_value,
			$options->unique_column,
			$options->unique_column,
			$options->target,
			$options->value_column,
			$options->target_value
		);
		$debug_class->debug( $query );
		$wpdb->get_results( $query );

		// Update existing rows.
		$uc = $options->unique_column;
		foreach( $existing_rows as $existing_row )
		{
			$debug_class->debug( 'Updating existing row %s', $existing_row->$uc );
			$wpdb->update( $options->target, (array) $existing_row, [ $options->unique_column => $existing_row->$uc ] );
		}
	}
}
