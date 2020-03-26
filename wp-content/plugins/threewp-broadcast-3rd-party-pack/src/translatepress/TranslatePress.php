<?php

namespace threewp_broadcast\premium_pack\translatepress
{

/**
	@brief				Adds support for the <a href="https://wordpress.org/plugins/translatepress-multilingual/">TranslatePress</a> plugin.
	@plugin_group		3rd party compatability
**/
class TranslatePress
	extends \threewp_broadcast\premium_pack\base
{
	use \threewp_broadcast\premium_pack\classes\database_trait;

	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2020-03-17 22:16:40
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		if ( ! $this->has_requirements() )
			return;

		$bcd = $action->broadcasting_data;		// Convenience
		$tp = $bcd->translatepress;
		global $wpdb;

		$child_original_strings_table = $this->database_table( 'trp_original_strings' );
		$child_original_meta_table = $this->database_table( 'trp_original_meta' );

		$original_strings = $tp->collection( 'original_strings' )->to_array();

		// 1. Get the current original strings for the child.
		$query = sprintf( "SELECT `original_id` FROM `%s` WHERE `meta_key` = 'post_parent_id' AND `meta_value` = '%d'",
			$child_original_meta_table,
			$bcd->new_post( 'ID' )
		);
		$this->debug( $query );
		$child_original_ids = $wpdb->get_col( $query );
		$this->debug( 'Original string IDs for this post: %s', $child_original_ids );

		// Retrieve the strings.
		$query = sprintf( "SELECT * FROM `%s` WHERE `id` IN ( %s )",
			$child_original_strings_table,
			implode( ",", $child_original_ids )
		);
		$this->debug( $query );
		$child_original_strings = $wpdb->get_results( $query );
		$this->debug( 'Original string for this post: %s', $child_original_ids );

		// 2. Find the equivalent strings.
		$missing_strings = $original_strings;
		$equivalent_strings = [];
		foreach( $child_original_strings as $child_original_string )
			foreach( $original_strings as $original_string )
			{
				if ( $original_string != $child_original_string->original )
					continue;
				unset( $missing_strings[ $original_string ] );
				$equivalent_strings[ $original_string ] = $child_original_string->id;
			}

		// 3. Insert missing strings.
		foreach( $missing_strings as $missing_string )
		{
			$wpdb->insert( $child_original_strings_table, [ 'original' => $missing_string ] );
			$id = $wpdb->insert_id;
			$equivalent_strings[ $missing_string ] = $id;
			$this->debug( 'Inserted missing string: %s (%s)', $missing_string, $id );

			// Insert it into the meta table.
			$wpdb->insert( $child_original_meta_table, [
				'meta_key' => 'post_parent_id',
				'meta_value' => $bcd->new_post( 'ID' ),
				'original_id' => $id,
			] );
		}

		foreach( $tp->get( 'languages' ) as $language )
		{
			$table = $this->database_table( 'trp_dictionary_' . $language );

			// We only want to insert the languages that exist.
			if ( ! $this->database_table_exists( $table ) )
				continue;

			$query = sprintf( "SELECT * FROM `%s` WHERE `original_id` IN (%s)",
				$table,
				implode( ",", $child_original_ids )
			);
			$this->debug( $query );
			$existing_strings = $wpdb->get_results( $query );

			$missing_strings = $equivalent_strings;
			$original_strings = $tp->collection( 'dictionaries' )->collection( $language );

			// Process each string, comparing to the parent.
			foreach( $original_strings as $original_string => $original_row )
				foreach( $existing_strings as $existing_string_row )
				{
					if ( $original_row->original != $existing_string_row->original )
						continue;
					// Match found.
					unset( $missing_strings[ $original_row->original ] );
					$update = false;
					// Update the translation?
					if ( $existing_string_row->translated != $original_row->translated )
						$update = true;
					if ( $existing_string_row->status != $original_row->status )
						$update = true;
					if ( $update )
					{
						$data = [
							'translated' => $original_row->translated,
							'status' => $original_row->status,
							'block_type' => $original_row->block_type,
						];
						$row_id = $existing_string_row->id;
						$this->debug( 'Updating existing row %s with %s', $row_id, $data );
						$wpdb->update( $table, $data, [ 'id' => $row_id ] );
					}
				}
			// Insert missing rows.
			foreach( $missing_strings as $missing_string => $missing_string_id )
			{
				foreach( $original_strings as $original_string => $original_row )
				{
					if ( $original_row->original != $missing_string )
						continue;
					$data = [
						'original' => $original_row->original,
						'translated' => $original_row->translated,
						'status' => $original_row->status,
						'block_type' => $original_row->block_type,
						'original_id' => $missing_string_id,
					];
					$this->debug( 'Inserting %s', $data );
					$wpdb->insert( $table, $data );
				}
			}
		}
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2020-03-17 19:47:46
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		if ( ! $this->has_requirements() )
			return;

		$bcd = $action->broadcasting_data;		// Convenience
		$bcd->translatepress = ThreeWP_Broadcast()->collection();
		$tp = $bcd->translatepress;

		$trp = \TRP_Translate_Press::get_trp_instance();
		$settings = $trp->get_component( 'settings' );
		$settings = $settings->get_settings();
		$this->debug( 'TRP settings: %s', $settings );
		$tp->set( 'settings', $settings );

		$languages = [];
		$default_language = strtolower( $settings[ 'default-language' ] );
		foreach( $settings[ 'translation-languages' ] as $language )
		{
			$language = strtolower( $language );
			if ( $default_language == $language )
				continue;
			$a_language = $default_language . '_' . $language;
			$languages []= $a_language;
		}
		$tp->set( 'languages', $languages );

		global $wpdb;

		// Get the original strings of this post.
		$table = $this->database_table( 'trp_original_meta' );
		$query = sprintf( "SELECT `original_id` FROM `%s` WHERE `meta_key` = 'post_parent_id' AND `meta_value` = '%d'",
			$table,
			$bcd->post->ID
		);
		$this->debug( $query );
		$original_ids = $wpdb->get_col( $query );
		$this->debug( 'Original string IDs for this post: %s', $original_ids );

		// Store the translations for each language.
		foreach( $tp->get( 'languages' ) as $language )
		{
			$table = $this->database_table( 'trp_dictionary_' . $language );
			$query = sprintf( "SELECT * FROM `%s` WHERE `original_id` IN (%s) AND `status` > 0",
				$table,
				implode( ",", $original_ids )
			);
			$this->debug( $query );
			$strings = $wpdb->get_results( $query );

			// Store the strings.
			foreach( $strings as $row )
			{
				$tp->collection( 'original_strings' )->set( $row->original, $row->original );
				$tp->collection( 'dictionaries' )->collection( $language )->set( $row->original, $row );
			}
		}
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------

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
		@brief		Is TRP installed?
		@since		2020-03-17 19:48:13
	**/
	public function has_requirements()
	{
		return class_exists( 'TRP_Translate_Press' );
	}

}	// class

}	// namespace

namespace
{
	/**
		@brief		Return the instance of the add-on.
		@since		2020-03-12 22:56:33
	**/
	function Broadcast_Translatepress()
	{
		return \threewp_broadcast\premium_pack\translatepress\TranslatePress::instance();
	}
}
