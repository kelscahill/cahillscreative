<?php

namespace threewp_broadcast\premium_pack\mailster;

/**
	@brief			Adds support for the <a href="https://codecanyon.net/item/mailster-email-newsletter-plugin-for-wordpress/3078294">Mailster</a> plugin.
	@plugin_group	3rd party compatability
	@since			2017-06-15 10:16:38
**/
class Mailster
	extends \threewp_broadcast\premium_pack\classes\Shortcode_Preparser
{
	/**
		@brief		Newsletter post type.
		@since		2017-07-06 22:05:43
	**/
	public static $newsletter_post_type = 'newsletter';

	/**
		@brief		Constructor.
		@since		2017-06-15 10:17:15
	**/
	public function _construct()
	{
		parent::_construct();
		$this->add_filter( 'threewp_broadcast_allowed_post_statuses' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_filter( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		new Forms();
	}

	/**
		@brief		Common method for preparing the bcd.
		@since		2017-07-06 22:07:49
	**/
	public function prepare_bcd( $bcd )
	{
		if ( ! isset( $bcd->mailster ) )
			$bcd->mailster = ThreeWP_Broadcast()->collection();
	}

	/**
		@brief		threewp_broadcast_allowed_post_statuses
		@since		2017-06-15 10:17:47
	**/
	public function threewp_broadcast_allowed_post_statuses( $allowed_statuses )
	{
		$allowed_statuses[ 'paused' ] = 'paused';
		$allowed_statuses[ 'autoresponder' ] = 'autoresponder';
		return $allowed_statuses;
	}

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2017-07-06 22:08:33
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		global $wpdb;
		$bcd = $action->broadcasting_data;		// Convenience.

		$this->prepare_bcd( $bcd );

		$lists = $bcd->mailster->get( 'lists' );
		if ( is_array( $lists ) )
		{
			$child_lists = [];
			$table = sprintf( '%smailster_lists', $wpdb->prefix );

			// Go through each list.
			// Find out if it exists. If not, create it.
			foreach( $lists as $list )
			{
				// The slug is the identifier.
				$query = sprintf( "SELECT `ID` FROM `%s` WHERE `slug` = '%s'",
					$table,
					$list->slug
				);
				$list_id = $wpdb->get_var( $query );

				if ( ! $list_id )
				{
					// Insert a new list.
					unset( $list->ID );
					$wpdb->insert( $table, (array) $list );
					$list_id = $wpdb->insert_id;
				}

				$child_lists []= $list_id;
			}

			// Update the custom field.
			$this->debug( 'Updating lists with %s', $child_lists );
			$bcd->custom_fields()->child_fields()
				->update_meta( '_mailster_lists', $child_lists );
		}
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2017-07-06 22:05:02
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;		// Convenience.

		if ( $bcd->post->post_type != static::$newsletter_post_type )
			return;

		$this->prepare_bcd( $bcd );

		$lists = $bcd->custom_fields()->get_single( '_mailster_lists' );
		$lists = maybe_unserialize( $lists );
		if ( ! is_array( $lists ) )
			return;

		// Save the lists.
		global $wpdb;
		$query = sprintf( "SELECT * FROM `%smailster_lists` WHERE `ID` IN ( %s )",
			$wpdb->prefix,
			implode( ',', $lists )
		);
		$lists = $wpdb->get_results( $query );

		$bcd->mailster->set( 'lists', $lists );
	}

	/**
		@brief		threewp_broadcast_get_post_types
		@since		2017-06-15 10:31:02
	**/
	public function threewp_broadcast_get_post_types( $action )
	{
		$action->add_type( static::$newsletter_post_type );
	}
}
