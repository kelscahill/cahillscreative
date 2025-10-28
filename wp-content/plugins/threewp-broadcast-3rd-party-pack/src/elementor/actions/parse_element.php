<?php

namespace threewp_broadcast\premium_pack\elementor\actions;

/**
	* @brief		Parse an element, updating all of its blog-unique IDs.
	* @since		2025-05-09 20:01:17
**/
class parse_element
	extends action
{
	/**
	 *	@brief	IN: The broadcasting data object.
	 *	@since	2025-05-09 19:43:16
	 **/
	public $broadcasting_data;

	/**
	 *	@brief	IN / OUT: The element we are updating.
	 *	@since	2025-05-09 19:52:21
	 **/
	public $element;
}
