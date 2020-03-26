<?php

namespace threewp_broadcast\premium_pack\divi_builder;

/**
	@brief				Adds support for <a href="https://www.elegantthemes.com/plugins/divi-builder/">Divi Builder</a> and themes using it.
	@plugin_group		3rd party compatability
	@since				2016-11-10 21:33:22
**/
/**
	@brief
**/
class Divi_Builder
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		new Global_Module();
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2016-11-10 21:33:22
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$this->debug( 'Disabling et_pb_force_regenerate_templates.' );
		remove_action( 'created_term', 'et_pb_force_regenerate_templates' );
		remove_action( 'edited_term', 'et_pb_force_regenerate_templates' );
		remove_action( 'delete_term', 'et_pb_force_regenerate_templates' );
	}
}
