<?php

namespace threewp_broadcast\premium_pack\caldera_forms;

/**
	@brief			Adds support for the <a href="https://wordpress.org/plugins/caldera-forms/">Caldera Forms</a> plugin.
	@plugin_group	3rd party compatability
	@since		2019-03-22 13:23:09
**/
class Caldera_Forms
	extends \threewp_broadcast\premium_pack\classes\Shortcode_Preparser
{
	use \threewp_broadcast\premium_pack\classes\database_trait;
	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc functions
	// --------------------------------------------------------------------------------------------

	public function copy_item( $bcd, $item )
	{
		global $wpdb;

		switch_to_blog( $bcd->parent_blog_id );

		$source_prefix = $wpdb->prefix;

		// Retrieve the form.
		$table = sprintf( '%s%s', $source_prefix, 'cf_forms' );
		$query = sprintf( "SELECT * FROM `%s` WHERE `form_id` = '%s' AND `type` = 'primary'", $table, $item->id );
		$form = $wpdb->get_row( $query );

		restore_current_blog();

		// No form? Invalid shortcode. Too bad.
		if ( ! $form )
			throw new \Exception( 'No form found.' );

		$target_prefix = $wpdb->prefix;

		// Find a form with the same name.
		$table = sprintf( '%s%s', $target_prefix, 'cf_forms' );
		$query = sprintf( "SELECT * FROM `%s` WHERE `form_id` = '%s' AND `type` = 'primary'", $table, $item->id );
		$result = $wpdb->get_row( $query );

		if ( count( $result ) < 1 )
		{
			$columns = $this->get_database_table_columns_string( $table, [ 'except' => [ 'id' ] ] );
			$query = sprintf( "INSERT INTO `%scf_forms` ( %s ) ( SELECT %s FROM `%scf_forms` WHERE `form_id` ='%s' AND `type` = 'primary' )",
				$target_prefix,
				$columns,
				$columns,
				$source_prefix,
				$form->form_id
			);
			$wpdb->get_results( $query );
			$new_form_id = $wpdb->insert_id;
			$this->debug( 'Using new form %s', $new_form_id );
		}
		else
		{
			$new_form_id = $result->id;
			$this->debug( 'Using existing form %s', $new_form_id );
		}

		$form_option[ 'id' ] = $new_form_id;

		// Update the form data.
		$new_form_data = (array)$form;
		unset( $new_form_data[ 'id' ] );
		$wpdb->update( $target_prefix . 'cf_forms', $new_form_data, [ 'id' => $new_form_id ] );

		return $item->id;
	}

	/**
		@brief		Return the name of the shortcode we are looking for.
		@since		2017-01-11 23:03:36
	**/
	public function get_shortcode_name()
	{
		return 'caldera_form';
	}

}
