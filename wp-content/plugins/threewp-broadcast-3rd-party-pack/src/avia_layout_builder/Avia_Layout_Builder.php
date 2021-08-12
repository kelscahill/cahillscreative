<?php

namespace threewp_broadcast\premium_pack\avia_layout_builder;

use \threewp_broadcast\actions;

/**
	@brief			Adds support for the <a href="http://www.kriesi.at/">Avia Layout Builder plugin from Kriesi.at</a>.
	@details		Thanks for Francis from quai13.com for his work on this plugin.
	@plugin_group	3rd party compatability
	@since			2015-11-19 19:00:21
	@author			Francis
	@author_url		http://quai13.com
**/
class Avia_Layout_Builder
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		The meta key in which Avia stores its data.
		@since		2016-07-19 19:33:53
	**/
	public static $meta_key = '_aviaLayoutBuilderCleanData';

	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2016-07-19 19:27:21
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;

		$content = $bcd->custom_fields()->get_single( static::$meta_key );
		if ( strlen( $content ) < 1 )
			return $this->debug( 'No Avia Layout Builder content found.' );

		$bcd->avia_layout_builder = ThreeWP_Broadcast()->collection();
		$bcd->avia_layout_builder->set( static::$meta_key, $content );

		$preparse_content = ThreeWP_Broadcast()->new_action( 'preparse_content' );
		$preparse_content->broadcasting_data = $bcd;
		$preparse_content->content = $content;
		$preparse_content->id = static::$meta_key;
		$preparse_content->execute();
	}

	/**
		@brief		Put in the new attachment IDs.
		@since		2014-04-06 15:54:36
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->avia_layout_builder ) )
			return;

		$content = $bcd->avia_layout_builder->get( static::$meta_key, '' );

		$parse_content = ThreeWP_Broadcast()->new_action( 'parse_content' );
		$parse_content->broadcasting_data = $bcd;
		$parse_content->content = $content;
		$parse_content->id = static::$meta_key;
		$parse_content->execute();

		$content = $parse_content->content;

		$this->debug( 'New Avia Layout Builder data: %s', htmlspecialchars( $content ) );

		$bcd->custom_fields()
			->child_fields()
			->update_meta( static::$meta_key, $content );
	}
}
