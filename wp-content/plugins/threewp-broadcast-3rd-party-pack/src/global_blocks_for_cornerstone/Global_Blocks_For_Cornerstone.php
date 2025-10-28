<?php

namespace threewp_broadcast\premium_pack\global_blocks_for_cornerstone;

/**
	@brief			OBSOLETE. Replaced by Cornerstone add-on.
	@plugin_group	3rd party compatability
	@since			2017-01-11 22:51:31
**/
class Global_Blocks_For_Cornerstone
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		Constructor.
		@since		2019-04-09 20:47:04
	**/
	public function _construct()
	{
		$this->v2 = new Global_Blocks_For_Cornerstone_2();
		$this->v3 = new Global_Blocks_For_Cornerstone_3();
	}
}
