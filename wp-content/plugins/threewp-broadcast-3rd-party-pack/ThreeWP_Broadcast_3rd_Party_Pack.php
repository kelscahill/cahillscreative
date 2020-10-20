<?php
/*
Author:			edward_plainview
Author Email:	edward@plainviewplugins.com
Author URI:		http://plainviewplugins.com
Description:	A pack of add-ons that make Broadcast compatible with 3rd party plugins.
Plugin Name:	Broadcast 3rd Party Pack
Plugin URI:		https://broadcast.plainviewplugins.com
Version:		46.14
*/

define( 'BROADCAST_3RD_PARTY_PACK_VERSION', 46.14 );

/**
	@brief		This class handles the loading of the pack.
	@since		2015-10-29 15:37:13
**/
class threewp_broadcast_3rd_party_pack_loader
{
	/**
		@brief		The plugin pack object, once loaded.
		@since		2015-10-29 15:22:52
	**/
	public $plugin_pack = false;

	/**
		@brief		Constructor.
		@since		2015-10-29 15:17:29
	**/
	public function __construct()
	{
		add_action( 'threewp_broadcast_loaded', [ $this, 'pack' ] );
		register_activation_hook( __FILE__, [ $this, 'activate' ] );
		register_deactivation_hook( __FILE__, [ $this, 'deactivate' ] );
	}

	public function activate()
	{
		if ( ! function_exists( 'ThreeWP_Broadcast' ) )
			wp_die( 'Please activate Broadcast before this plugin pack.' );
		$this->pack()->activate();
	}

	public function deactivate()
	{
		if ( ! function_exists( 'ThreeWP_Broadcast' ) )
			return;
		$this->pack()->deactivate();
	}

	/**
		@brief		Init the pack, or return the pack class if already initialized.
		@since		2015-10-29 15:20:00
	**/
	public function pack()
	{
		if ( $this->plugin_pack === false )
		{
			require_once( __DIR__ . '/vendor/autoload.php' );
			$this->plugin_pack = ThreeWP_Broadcast()->plugin_pack();
			new \threewp_broadcast\premium_pack\ThreeWP_Broadcast_3rd_Party_Pack();
		}
		return $this->plugin_pack;
	}
}

new threewp_broadcast_3rd_party_pack_loader();
