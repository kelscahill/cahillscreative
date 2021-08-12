<?php

namespace threewp_broadcast\premium_pack;

/**
	@brief		A parent class for all official Broadcast plugin packs (Premium, 3rd Party, Control, Efficiency and Utilities).
	@details	Saves me from repeating myself regarding the construction and uninstall, among other things.
	@since		2015-10-29 12:17:13
**/
abstract class Plugin_Pack
	extends \plainview\sdk_broadcast\wordpress\base
{
	use \plainview\sdk_broadcast\wordpress\updater\edd;

	/**
		@brief		The language domain to use.
		@details	Use the same as the basic plugin in order to leech off its translations.
		@since		2017-02-21 20:00:41
	**/
	public $language_domain = 'threewp_broadcast';

	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_started', 'dump_pack_info' );
		$this->add_action( 'ThreeWP_Broadcast_Plugin_Pack_get_plugin_classes' );
		$this->add_action( 'threewp_broadcast_plugin_pack_uninstall' );
		$this->add_action( 'threewp_broadcast_plugin_pack_tabs' );
		$this->add_action( 'threewp_broadcast_prepare_meta_box' );
		$this->edd_init();
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- EDD Updater
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Show some text about the SSL workaround.
		@since		2016-04-14 14:01:01
	**/
	public function edd_admin_license_tab_text()
	{
		$status = $this->edd_get_cached_license_status();
		if ( in_array( $status->license, [ 'deactivated', 'valid' ] ) )
			return;
		$r = $this->p(
			__( "If the pack is not activating as it should due to an SSL error, add this to your wp-config.php file: %s", 'threewp-broadcast' ),
			"<code>define( 'BROADCAST_PP_SSL_WORKAROUND', true );</code>"
		);
		$r .= $this->p(
			__( "If even that doesn't work, try using the %sBroadcast license download tool%s together with your license key.", 'threewp-broadcast' ),
			'<a href="https://broadcast.plainviewplugins.com/download/">',
			'</a>'
		);
		return $r;
	}

	/**
		@brief		edd_enable_ssl_workaround
		@since		2016-04-14 12:24:30
	**/
	public function edd_enable_ssl_workaround()
	{
		return defined( 'BROADCAST_PP_SSL_WORKAROUND' );
	}

	public abstract function edd_get_item_name();

	/**
		@brief		All official BC plugin packs have one EDD url.
		@since		2015-10-29 12:18:23
	**/
	public function edd_get_url()
	{
		return ThreeWP_Broadcast()->plugin_pack()->edd_get_url();
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc functions
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Show the pack's license key.
		@since		2016-11-28 16:56:33
	**/
	public function dump_pack_info()
	{
		$key = $this->get_site_option( 'edd_updater_license_key' );
		$key = substr( $key, -8 );
		ThreeWP_Broadcast()->debug( 'My license key is ~%s', $key );
	}

	public abstract function get_plugin_classes();

	/**
		@brief		Return an array of our site options.
		@since		2014-09-27 16:35:34
	**/
	public function site_options()
	{
		return array_merge( [
			'edd_updater_license_key' => '',
		], parent::site_options() );
	}

	/**
		@brief		Show our license in the tabs.
		@since		2015-10-28 15:10:14
	**/
	public abstract function threewp_broadcast_plugin_pack_tabs( $action );

	/**
		@brief		threewp_broadcast_prepare_meta_box
		@since		2019-10-03 15:21:42
	**/
	public function threewp_broadcast_prepare_meta_box( $action )
	{
		$status = $this->edd_get_cached_license_status();
		if (  $status->license != 'valid' )
			return;
		$expiration = strtotime( $status->expires );
		$diff = $expiration - time();
		if ( $diff > 30 * DAY_IN_SECONDS )
			return;
		$meta_box_data = $action->meta_box_data;	// Convenience.

		$renewal_url = add_query_arg( [
			'edd_license_key' => $this->get_site_option( 'edd_updater_license_key' ),
		], $this->edd_get_url() . 'checkout' );
		$pack_name = str_replace( 'ThreeWP Broadcast', '', $this->edd_get_item_name() );
		$text = sprintf( 'Your %s license <strong>expires</strong> in %s. If you wish to renew it, and receive a renewal discount, use this <a href="%s">renewal url</a>.',
			$pack_name,
			human_time_diff( $expiration ),
			$renewal_url
		);
		$meta_box_data->html->put( $this->edd_get_item_name(), $text );
	}

	/**
		@brief		Put all of our plugins in the list.
		@since		2015-01-06 09:54:47
	**/
	public function ThreeWP_Broadcast_Plugin_Pack_get_plugin_classes( $action )
	{
		$action->add( $this->get_plugin_classes() );
	}

	/**
		@brief		Uninstall ourself.
		@since		2015-10-28 23:21:26
	**/
	public function ThreeWP_Broadcast_Plugin_Pack_uninstall( $action )
	{
		$this->uninstall_internal();
		$this->deactivate_me();
	}
}
