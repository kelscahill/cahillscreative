<?php

namespace threewp_broadcast\premium_pack\simple_custom_post_order;

/**
	@brief			Adds support for the <a href="https://wordpress.org/plugins/simple-custom-post-order/">Simple Custom Post Order</a> plugin.
	@plugin_group	3rd party compatability
	@since			2018-10-15 11:40:17
**/
class Simple_Custom_Post_Order
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_filter( 'threewp_broadcast_wp_update_term' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Decide whether to update the term_order.
		@since		2018-10-15 11:41:56
	**/
	public function threewp_broadcast_wp_update_term( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $action->new_term->term_order ) )
			return;

		$action->switch_data( 'term_order' );

		if ( $action->new_term->term_order == $action->old_term->term_order )
			return;

		// Term order differs! Update!
		global $wpdb;
		$query = sprintf( "UPDATE `%s` SET `term_order` = '%s' WHERE `term_id` = '%s'",
			$wpdb->terms,
			$action->old_term->term_order,
			$action->new_term->term_id
		);
		$this->debug( $query );
		$wpdb->query( $query );
	}
}
