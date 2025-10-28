<?php

require_once( __DIR__ . '/JetEngine.class.php' );

/**
 * Return the instance of the JetEngine add-on.
 *
 * @since		2025-05-09 21:20:01
 **/
function broadcast_jetengine()
{
	return \threewp_broadcast\premium_pack\jetengine\JetEngine::instance();
}