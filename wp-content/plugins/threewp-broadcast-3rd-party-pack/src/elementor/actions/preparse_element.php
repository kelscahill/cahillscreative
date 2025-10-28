<?php

namespace threewp_broadcast\premium_pack\elementor\actions;

/**
	* @brief		Preparse an element, finding all images and taking note of all necessary IDs before switching blogs and parsing the element.
	* @since		2025-05-09 20:01:52
**/
class preparse_element
	extends action
{
	/**
	 *	@brief	IN: The broadcasting data object.
	 *	@since	2025-05-09 19:43:16
	 **/
	public $broadcasting_data;

	/**
	 *	@brief	IN: The element we are parsing.
	 *	@since	2025-05-09 19:52:21
	 **/
	public $element;
}
