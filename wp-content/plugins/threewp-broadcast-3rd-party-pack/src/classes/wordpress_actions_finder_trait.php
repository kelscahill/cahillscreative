<?php

namespace threewp_broadcast\premium_pack\classes;

/**
	@brief		Find classes in Wordpress actions and filters.
	@since		2018-02-28 21:27:18
**/
trait wordpress_actions_finder_trait
{
	/**
		@brief		Finds and removes a class and method from an action.
		@since		2018-02-28 21:33:35
	**/
	public function find_and_remove_action( $action, $class_and_method, $priority = null, $params = null )
	{
		global $wp_filter;
		if ( ! isset( $wp_filter[ $action ] ) )
			return;
		$filters = $wp_filter[ $action ];
		if ( is_object( $filters ) )
			$filters = $filters->callbacks;
		ksort( $filters );
		foreach( $filters as $callback_priority => $callbacks )
		{
			foreach( $callbacks as $callback )
			{
				$function = $callback[ 'function' ];
				// We are only interested in arrays since normal functions can be manually removed without searching.
				if ( ! is_array( $function ) )
					continue;

				$remove = false;

				$function_name = $function[ 0 ];
				if ( is_object( $function_name ) )
				{
					if ( get_class( $function_name ) == $class_and_method[ 0 ] )
						$remove = true;
				}
				else
				{
					if ( $function_name  == $class_and_method[ 0 ] )
						$remove = true;
				}

				if ( ! $remove )
					continue;

				// Check that the priority matches.
				if ( $priority !== null )
					$remove &= ( $callback_priority == $priority );

				// And the params.
				if ( $params !== null )
					$remove &= ( $callback[ 'accepted_args' ] == $params );

				if ( ! $remove )
					continue;

				$this->debug( 'Removing action %s for %s %s %s %s',
					$action,
					$class_and_method[ 0 ],
					$class_and_method[ 1 ],
					$priority,
					$params
				);
				remove_action( $action, [ $function_name, $function[ 1 ] ], $priority, $params );
			}
		}
	}
}
