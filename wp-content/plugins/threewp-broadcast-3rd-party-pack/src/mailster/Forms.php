<?php

namespace threewp_broadcast\premium_pack\mailster;

/**
	@brief		Handle the copying of the Forms.
	@since		2017-06-21 16:20:36
**/
class Forms
	extends \threewp_broadcast\premium_pack\classes\Shortcode_Preparser
{

	public function copy_item( $bcd, $item )
	{
		global $wpdb;

		switch_to_blog( $bcd->parent_blog_id );

		$source_prefix = $wpdb->prefix;

		// Retrieve the form.
		$query = sprintf( "SELECT * FROM `%smailster_forms` WHERE `id` = '%s'", $source_prefix, $item->id );
		$form = $wpdb->get_row( $query );

		restore_current_blog();

		// No form? Invalid shortcode. Too bad.
		if ( ! $form )
			throw new \Exception( 'No form found.' );

		$target_prefix = $wpdb->prefix;

		// Find a form with the same name.
		$query = sprintf( "SELECT * FROM `%smailster_forms` WHERE `name` = '%s'", $target_prefix, $form->name );
		$result = $wpdb->get_row( $query );

		if ( count( $result ) < 1 )
		{
			$columns = '`name`,`submit`,`asterisk`,`userschoice`,`precheck`,`dropdown`,`prefill`,`inline`,`overwrite`,`addlists`,`style`,`custom_style`,`doubleoptin`,`subject`,`headline`,`content`,`link`,`resend`,`resend_count`,`resend_time`,`template`,`vcard`,`vcard_content`,`confirmredirect`,`redirect`,`added`,`updated`';
			$query = sprintf( "INSERT INTO `%smailster_forms` ( %s ) ( SELECT %s FROM `%smailster_forms` WHERE `name` = '%s' )",
				$target_prefix,
				$columns,
				$columns,
				$source_prefix,
				$form->name
			);
			$wpdb->get_results( $query );
			$new_form_id = $wpdb->insert_id;
			$this->debug( 'Using new form %s', $new_form_id );
		}
		else
		{
			$new_form_id = $result->ID;
			$this->debug( 'Using existing form %s', $new_form_id );
		}

		// Update the form data.
		$new_form_data = (array)$form;
		unset( $new_form_data[ 'ID' ] );
		$wpdb->update( $target_prefix . `mailster_forms`, $new_form_data, [ 'ID' => $new_form_id ] );

		// Form fields. Delete all existing values.
		$query = sprintf( "DELETE FROM `%smailster_form_fields` WHERE `form_id` = '%s'",
			$target_prefix,
			$new_form_id
		);
		$wpdb->query( $query );

		// And reinsert the fresh data.
		$columns = '`field_id`,`name`,`error_msg`,`required`,`position`';
		$query = sprintf( "INSERT INTO `%smailster_form_fields` ( `form_id`, %s ) ( SELECT %s, %s FROM `%smailster_form_fields` WHERE `form_id` ='%s' )",
			$target_prefix,
			$columns,
			$new_form_id,
			$columns,
			$source_prefix,
			$form->ID
		);
		$wpdb->get_results( $query );

		// And now for the lists.
		// Find all lists that this form belongs to.
		$query = sprintf( "SELECT * FROM `%smailster_lists` WHERE `ID` IN ( SELECT `list_id` FROM `%smailster_forms_lists` WHERE `form_id` = '%s' )", $source_prefix, $source_prefix, $item->id );
		$lists = $wpdb->get_results( $query );

		foreach( $lists as $list )
		{
			$columns = '`parent_id`,`name`,`slug`,`description`,`added`,`updated`';

			// Find a list with the same slug.
			$query = sprintf( "SELECT * FROM `%smailster_lists` WHERE `slug` = '%s'", $target_prefix, $list->slug );
			$result = $wpdb->get_row( $query );

			if ( count( $result ) < 1 )
			{
				$query = sprintf( "INSERT INTO `%smailster_lists` ( %s ) ( SELECT %s FROM `%smailster_lists` WHERE `ID` = '%s' )",
					$target_prefix,
					$columns,
					$columns,
					$source_prefix,
					$list->ID
				);
				$wpdb->get_results( $query );
				$new_list_id = $wpdb->insert_id;
				$this->debug( 'Using new list %s', $new_list_id );
			}
			else
			{
				$new_list_id = $result->ID;
				$this->debug( 'Existing list %s', $new_list_id );
			}

			// Delete and re-add this form list assignment.
			$query = sprintf( "DELETE FROM `%smailster_forms_lists` WHERE `list_id` = '%s'", $target_prefix, $new_list_id );
			$result = $wpdb->get_row( $query );

			$query = sprintf( "INSERT INTO `%smailster_forms_lists` ( `form_id`, `list_id`, `added` ) VALUES ( '%d', '%d', '%d' )",
				$target_prefix,
				$new_form_id,
				$new_list_id,
				time()
			);
			$result = $wpdb->get_row( $query );
		}

		return $new_form_id;
	}

	/**
		@brief		Return the name of the shortcode we are looking for.
		@since		2017-01-11 23:03:36
	**/
	public function get_shortcode_name()
	{
		return 'newsletter_signup_form';
	}
}
