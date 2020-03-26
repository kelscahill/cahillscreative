<?php

namespace threewp_broadcast\cli;

/**
	@brief		Handle linking of posts.
	@since		2018-10-31 18:16:57
**/
class Link
	extends \WP_CLI_Command
{
}
$link = new Link();
WP_CLI::add_command( 'broadcast', 'Link' );