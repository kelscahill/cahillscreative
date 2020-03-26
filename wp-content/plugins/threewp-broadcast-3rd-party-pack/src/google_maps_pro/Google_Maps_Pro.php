<?php

namespace threewp_broadcast\premium_pack\google_maps_pro;

/**
	@brief			Adds support for the <a href="https://premium.wpmudev.org/project/wordpress-google-maps-plugin">Google Maps Pro</a> plugin.
	@plugin_group	3rd party compatability
	@since			2017-05-01 18:38:26
**/
class Google_Maps_Pro
	extends \threewp_broadcast\premium_pack\classes\Shortcode_Preparser
{
	use \threewp_broadcast\premium_pack\classes\database_trait;

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Inherited
	// --------------------------------------------------------------------------------------------

	public function copy_item( $bcd, $item )
	{
		global $wpdb;

		switch_to_blog( $bcd->parent_blog_id );

		$source_prefix = $wpdb->prefix;
		$source_table = $source_prefix . 'agm_maps';
		$this->database_table_must_exist( $source_table );
		// Retrieve the form.
		$query = sprintf( "SELECT * FROM `%s` WHERE `id` = '%s'", $source_table, $item->id );
		$form = $wpdb->get_row( $query );

		restore_current_blog();

		// No form? Invalid shortcode. Too bad.
		if ( ! $form )
			throw new \Exception( 'No form found.' );

		$target_prefix = $wpdb->prefix;

		$target_table = $target_prefix . 'agm_maps';
		$this->database_table_must_exist( $target_table );
		// Find a form with the same name.
		$query = sprintf( "SELECT * FROM `%s` WHERE `title` = '%s'", $target_table, $form->title );
		$result = $wpdb->get_row( $query );

		if ( count( $result ) < 1 )
		{
			$columns = $this->get_database_table_columns_string( $target_table, [ 'except' => [ 'id' ] ] );
			$query = sprintf( "INSERT INTO `%s` ( %s ) ( SELECT %s FROM `%s` WHERE `id` ='%d' )",
				$target_table,
				$columns,
				$columns,
				$source_table,
				$item->id
			);
			$this->debug( $query );
			$wpdb->get_results( $query );
			$new_form_id = $wpdb->insert_id;
			$this->debug( 'Using new form %s', $new_form_id );
		}
		else
		{
			$new_form_id = $result->id;
			$this->debug( 'Using existing form %s', $new_form_id );
		}

		$target_table = $target_prefix . 'agm_maps';
		// Update active and trash status.
		$new_data = [
			'markers' => $form->markers,
			'options' => $form->options,
			'post_ids' => $form->post_ids,
		];
		$this->debug( 'Setting new data: %s', $new_data );
		$wpdb->update( $target_table, $new_data, [ 'id' => $new_form_id ] );

		return $new_form_id;
	}

	/**
		@brief		Return the name of the shortcode we are looking for.
		@since		2017-01-11 23:03:36
	**/
	public function get_shortcode_name()
	{
		return 'map';
	}
}
