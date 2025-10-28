<?php

namespace threewp_broadcast\premium_pack\learndash\actions;

/**
	@brief		Merge the existing sfwd_course meta field.
	@since		2020-12-01 21:58:03
**/
class merge_existing_course_meta
	extends action
{
	/**
		@brief		[IN]: Broadcasting data.
		@since		2020-12-01 21:58:35
	**/
	public $broadcasting_data;

	/**
		@brief		The keys to merge.
		@since		2020-12-01 21:58:47
	**/
	public $keys = [];
}
