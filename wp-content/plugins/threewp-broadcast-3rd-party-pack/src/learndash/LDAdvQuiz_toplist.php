<?php

namespace threewp_broadcast\premium_pack\learndash;

/**
	@brief		The toplist needs to be handled the same way as a quiz.
	@since		2020-02-07 21:01:46
**/
class LDAdvQuiz_toplist
	extends LDAdvQuiz
{
	/**
		@brief		Return the name of the shortcode we are looking for.
		@since		2017-01-11 23:03:36
	**/
	public function get_shortcode_name()
	{
		return 'LDAdvQuiz_toplist';
	}
}
