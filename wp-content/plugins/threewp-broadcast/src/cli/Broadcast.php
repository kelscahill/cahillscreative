<?php

namespace threewp_broadcast\cli;

use \WP_CLI;

/**
 * Broadcast commands to handle broadcasting and linking.
 * @since		2018-10-31 18:14:07
**/
class Broadcast
{
	/**
		* Is this post linked to another?
		*
		* ## OPTIONS
		*
		* [<hook>]
		* : The hook name.
		*
		* [<next-run>]
		* : A Unix timestamp or an English textual datetime description compatible with `strtotime()`. Defaults to now.
		*
		* [<recurrence>]
		* : How often the event should recur. See `wp cron schedule list` for available schedule names. Defaults to no recurrence.
		*
		* [--blog_id=<value>]
		* : The ID of the blog to check on.
		*
		* [--post_id=<value>]
		* : The ID of the post to check.
		*
		* ## EXAMPLES
		*
		*     # Schedule a new cron event.
		*     $ wp cron event schedule cron_test
		*     Success: Scheduled event with hook 'cron_test' for 2016-05-31 10:19:16 GMT.
		*
		*     # Schedule new cron event with hourly recurrence.
		*     $ wp cron event schedule cron_test now hourly
		*     Success: Scheduled event with hook 'cron_test' for 2016-05-31 10:20:32 GMT.
		*
		*     # Schedule new cron event and pass associative arguments.
		*     $ wp cron event schedule cron_test '+1 hour' --foo=1 --bar=2
		*     Success: Scheduled event with hook 'cron_test' for 2016-05-31 11:21:35 GMT.

		* @since		2018-10-31 18:27:52
	**/
	public function is_post_linked( $args, $assoc_args )
	{
		WP_CLI::line( json_encode( $assoc_args ) );
	}
}
