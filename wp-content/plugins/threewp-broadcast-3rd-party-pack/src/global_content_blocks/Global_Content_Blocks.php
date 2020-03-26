<?php

namespace threewp_broadcast\premium_pack\global_content_blocks;

use \gcb;

/**
	@brief			Adds support for shortcodes from <a href="https://wordpress.org/plugins/global-content-blocks/">WP Xpert's Global Content Blocks</a> plugin.
	@plugin_group	3rd party compatability
	@since			2015-05-20 20:27:30
**/
class Global_Content_Blocks
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_filter( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_broadcasting_modify_post' );
	}

	/**
		@brief		Return an array of entries with both the ID and the custom ID as keys.
		@since		2015-05-20 20:59:34
	**/
	public function get_merged_entries()
	{
		$entries = gcb::get_entries();
		$entries_id = $this->array_rekey( $entries, 'id' );
		$entries_alt = $this->array_rekey( $entries, 'custom_id' );
		$entries = $entries_id + $entries_alt;
		return $entries;
	}

	/**
		@brief		Is GCB installed?
		@since		2015-05-20 20:29:09
	**/
	public function has_gcb()
	{
		return defined( 'GCB_VERSION' );
	}

	/**
		@brief		threewp_broadcast_broadcasting_modify_post
		@since		2015-05-20 20:50:00
	**/
	public function threewp_broadcast_broadcasting_modify_post( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->global_content_blocks ) )
			return;

		$gcb_data = $bcd->global_content_blocks;

		// Get the current entries and add or update any of our entries.
		$entries = gcb::get_entries();
		// We need the entries by name.
		$entries = $this->array_rekey( $entries, 'name' );

		$this->debug( 'Entries on this blog: %s', $entries );

		// The equivalent IDs of entries from the parent to the children.
		$equivalents = [];

		foreach( $gcb_data->collection( 'entries' ) as $id => $entry )
		{
			$name = $entry[ 'name' ];

			// Update it?
			if ( isset( $entries[ $name ] ) )
			{
				$local_entry = $entries[ $name ];
				$entry[ 'id' ] = $local_entry[ 'id' ];
				gcb::update_entry( $entry, $local_entry[ 'id' ] );
				$equivalent_id = $local_entry[ 'id' ];
				$this->debug( 'Updated entry %s', $name );
			}
			else
			{
				// Or add it.
				$equivalent_id = gcb::add_entry( $entry );
				$this->debug( 'Added entry %s', $name );
			}

			$equivalents[ $id ] = $equivalent_id;
			$this->debug( 'Local equivalent of <em>%s</em> %s is %s', $name, $id, $equivalent_id );
		}

		// And now replace the matches in the post.
		$mp = $bcd->modified_post;

		foreach( $gcb_data->collection( 'matches' ) as $match => $id )
		{
			// We only want to replace numeric IDs.
			if ( ! is_numeric( $id ) )
			{
				$this->debug( 'Ignoring non-numeric %s for shortcode %s.', $id, $match );
				continue;
			}

			$new_id = $equivalents[ $id ];
			$new_match = str_replace( '=' . $id, '=' . $new_id, $match );

			$this->debug( 'Replacing numeric id %s in shortcode %s with %s, which becomes %s', $id, $match, $new_id, $new_match );

			$mp->post_content = str_replace( $match, $new_match, $mp->post_content );
		}

		$this->debug( 'Done replacing shortcodes.' );
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2015-05-20 20:28:14
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		if ( ! $this->has_gcb() )
			return;

		$bcd = $action->broadcasting_data;

		$entries = $this->get_merged_entries();
		$gcb_data = ThreeWP_Broadcast()->collection();

		// Find all normal contentblock shortcodes.
		$matches = ThreeWP_Broadcast()->find_shortcodes( $bcd->post->post_content, [ 'contentblock' ] );

		$count = count( $matches[ 0 ] );
		$this->debug( 'Found %s shortcodes.', $count );

		// Any normal shortcodes found?
		if ( $count < 1 )
			return;

		foreach( $matches[ 0 ] as $index => $match )
		{
			$id = $match;
			// Find the ID. Strip off everything before the ID.
			// Apparently, the GCB shortcode generator does not care for quotes, so we don't account for them.
			$id = preg_replace( '/.*id=/', '', $id );
			// And everything after the numbers.
			$id = preg_replace( '/[ \]].*/', '', $id );

			$this->debug( 'ID of %s is %s', $match, $id );

			// Is there any entry with this ID?
			if ( ! isset( $entries[ $id ] ) )
			{
				$this->debug( 'No such entry.' );
				continue;
			}

			$this->debug( 'Saving %s with ID %s', $match, $id );
			// Great! Save this entry for later.
			$gcb_data->collection( 'entries' )->set( $id, $entries[ $id ] );
			$gcb_data->collection( 'matches' )->set( $match, $id );
		}

		if ( count( $gcb_data ) < 1 )
			return;

		$bcd->global_content_blocks = $gcb_data;
	}
}
