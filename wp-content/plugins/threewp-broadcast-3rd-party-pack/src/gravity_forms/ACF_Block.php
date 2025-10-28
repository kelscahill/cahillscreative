<?php

namespace threewp_broadcast\premium_pack\gravity_forms;

/**
	@brief		Handles the ACF gravity forms block.
	@since		2023-02-15 22:07:21
**/
class ACF_Block
	extends \threewp_broadcast\premium_pack\classes\fake_shortcodes\In_Content
{
	/**
		@brief		Look for Gravity Form blocks.
		@since		2023-02-15 21:33:47
	**/
	public function threewp_broadcast_parse_content( $action )
	{
		$gb = ThreeWP_Broadcast()->gutenberg();
		$blocks = $gb->find_blocks_by_name( 'acf/gravity-form', $action->content );
		if ( count( $blocks ) < 1 )
			return;

		foreach( $blocks as $block )
		{
			$form_id = $block[ 'attrs' ][ 'data' ][ 'form' ];

			$options = (object) [];
			$options->broadcasting_data = $action->broadcasting_data;
			$options->shortcode = 'gravityform';
			$options->shortcode_item_key = 'id';
			$options->shortcode_item_value = $form_id;

			$this->fake_parse_shortcode( $options );

			$this->debug( 'New form ID for ACF GB block: %s', $options->new_shortcode_item_value );

			$block[ 'attrs' ][ 'data' ][ 'form' ] = $options->new_shortcode_item_value;

			$render_options = [
				'force_json_options' => true,
				'json_options' => JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES,
			];

			$action->content = ThreeWP_Broadcast()->gutenberg()->replace_text_with_block( $block[ 'original' ], $block, $action->content, $render_options );
		}
	}

	/**
		@brief		Look in the content.
		@since		2023-02-15 22:13:28
	**/
	public function threewp_broadcast_preparse_content( $action )
	{
		$gb = ThreeWP_Broadcast()->gutenberg();
		$blocks = $gb->find_blocks_by_name( 'acf/gravity-form', $action->content );
		if ( count( $blocks ) < 1 )
			return;

		foreach( $blocks as $block )
		{
			$form_id = $block[ 'attrs' ][ 'data' ][ 'form' ];

			$options = (object) [];
			$options->broadcasting_data = $action->broadcasting_data;
			$options->shortcode = 'gravityform';
			$options->shortcode_item_key = 'id';
			$options->shortcode_item_value = $form_id;

			$this->fake_preparse_shortcode( $options );
		}
	}
}
