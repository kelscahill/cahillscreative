<?php

namespace threewp_broadcast\premium_pack\formidable;

/**
	@brief			Handles the shortcode.
	@since			2018-08-20 10:36:00
**/
class Formidable_Shortcode
	extends \threewp_broadcast\premium_pack\classes\Shortcode_Preparser
{
	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Inherited
	// --------------------------------------------------------------------------------------------

	public function copy_item( $bcd, $item )
	{
		$form_data = new Form_Data();
		//$new_form_id = $form_data->get_equivalent_form_id( $bcd->parent_blog_id, $item->id );
		$new_form_id = $form_data->broadcast_form( $bcd->parent_blog_id, $item->id );
		return $new_form_id;
	}

	/**
		@brief		Return the name of the shortcode we are looking for.
		@since		2017-01-11 23:03:36
	**/
	public function get_shortcode_name()
	{
		return 'formidable';
	}
}
