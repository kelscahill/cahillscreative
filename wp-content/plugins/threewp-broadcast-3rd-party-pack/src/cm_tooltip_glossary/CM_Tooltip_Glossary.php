<?php
namespace threewp_broadcast\premium_pack\cm_tooltip_glossary;

/**
	@brief			Adds support for the <a href="https://wordpress.org/plugins/enhanced-tooltipglossary/">CM Tooltip Glossary</a> plugin.
	@plugin_group	3rd party compatability
**/
class CM_Tooltip_Glossary
extends \threewp_broadcast\premium_pack\base
{
	use \threewp_broadcast\premium_pack\classes\parse_custom_field_taxonomies_trait;
	use \threewp_broadcast\premium_pack\classes\sync_taxonomy_trait;

	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2018-05-23 20:41:26
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;
		$this->parse_custom_field_taxonomies( $bcd, 'glossary_post_page_custom_cats' );
		$this->parse_custom_field_taxonomies( $bcd, 'glossary_post_page_custom_terms' );
	}

	/**
		@brief		Add the post type, for manual broadcast.
		@since		2016-07-26 19:07:17
	**/
	public function threewp_broadcast_get_post_types( $action )
	{
		$action->add_type( 'glossary' );
	}
}
