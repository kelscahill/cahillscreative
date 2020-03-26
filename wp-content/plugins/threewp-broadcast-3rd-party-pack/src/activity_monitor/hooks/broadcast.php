<?php

namespace threewp_broadcast\premium_pack\activity_monitor\hooks;

class broadcast
	extends \plainview\wordpress\activity_monitor\hooks\posts
{
	public function get_description()
	{
		return 'A post is broadcasted.';
	}

	public function log_post()
	{
		$this->html_and_execute();
	}
}
