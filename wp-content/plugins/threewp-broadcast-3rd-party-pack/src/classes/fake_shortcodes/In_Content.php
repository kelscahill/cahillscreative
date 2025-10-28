<?php

namespace threewp_broadcast\premium_pack\classes\fake_shortcodes;

/**
	@brief		Fake a shortcode in the content.
	@details	This is used for plugins that have add-ons that broadcast things via shortcodes - but the thing ID is in a GB block or something.
	@see		Gravity Forms add-on.
	@since		2023-02-15 22:10:52
**/
class In_Content
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		Constructor.
		@since		2023-02-15 22:08:24
	**/
	public function _construct()
	{
		$this->add_filter( 'threewp_broadcast_parse_content' );
		$this->add_filter( 'threewp_broadcast_preparse_content' );
	}

	/**
		@brief		Cause the shortcode to be parsed and the ID attribute translated.
		@since		2023-02-15 22:19:53
	**/
	public function fake_parse_shortcode( $options )
	{
		$action = ThreeWP_Broadcast()->new_action( 'parse_content' );
		$action->broadcasting_data = $options->broadcasting_data;
		$action->content = sprintf( '[%s %s="%s"]',
			$options->shortcode,
			$options->shortcode_item_key,
			$options->shortcode_item_value,
		);
		$action->id = md5( $action->content );
		$action->execute();

		// $action->content will contain the new shortcode.
		// We need to extract the key attribute from it.
		// Remove the []
		$action->content = trim( $action->content, '[]' );
		// And break it out into an array.
		$atts = shortcode_parse_atts( $action->content );

		$item_key = $options->shortcode_item_key;
		$options->new_shortcode_item_value = $atts[ $item_key ];
	}

	/**
		@brief		Detect any shortcodes in the content.
		@since		2023-02-15 22:19:53
	**/
	public function fake_preparse_shortcode( $options )
	{
		$action = ThreeWP_Broadcast()->new_action( 'preparse_content' );
		$action->broadcasting_data = $options->broadcasting_data;
		$action->content = sprintf( '[%s %s="%s"]',
			$options->shortcode,
			$options->shortcode_item_key,
			$options->shortcode_item_value,
		);
		$action->id = md5( $action->content );
		$action->execute();
	}

	/**
		@brief		Fake broadcast the content.
		@since		2023-02-15 22:13:28
	**/
	public function threewp_broadcast_parse_content( $action )
	{
	}

	/**
		@brief		Look in the content.
		@since		2023-02-15 22:13:28
	**/
	public function threewp_broadcast_preparse_content( $action )
	{
	}
}
