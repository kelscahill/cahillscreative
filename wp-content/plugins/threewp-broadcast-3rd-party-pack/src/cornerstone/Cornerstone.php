<?php

namespace threewp_broadcast\premium_pack\cornerstone;

/**
	@brief			Adds support for the <a href="http://theme.co/cornerstone">Cornerstone Page Builder</a>.
	@plugin_group		3rd party compatability
	@since		2020-11-20 07:58:44
**/
class Cornerstone
	extends \threewp_broadcast\premium_pack\base
{
	use \threewp_broadcast\premium_pack\classes\broadcast_generic_post_ui_trait;

	public static $image_keys = [
		'bg_lower_image',
		'bg_lower_img_src',
		'bg_upper_image',
		'image_src',
		'toggle_anchor_graphic_image_src',
	];

	/**
		@brief		Constructor.
		@since		2020-11-20 07:59:18
	**/
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_modify_post' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_menu' );
		new Global_Blocks_For_Cornerstone_2();
		new Global_Blocks_For_Cornerstone_3();
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2020-11-20 07:59:18
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;		// Convenience

		$this->debug( 'Deleting component cache' );
		$bcd->custom_fields()->child_fields()->delete_meta( '_cs_component_map' );
		delete_option( 'cs_component_cache' );

		$key = '_cornerstone_data';
		$cf = $bcd->custom_fields()->get_single( $key );
		if ( $cf )
		{
			$cf = json_decode( $cf );
			$this->debug( 'Cornerstone data found in custom field %s.', $key );
			$value = cs_json_encode( $this->parse_cornerstone( $bcd, $cf ) );
			$value = wp_slash( $value );
			$bcd->custom_fields()->child_fields()->update_meta( $key, $value );
		}

		$key = '_cs_generated_styles';
		$data = $bcd->custom_fields()->get_single( $key );

		if ( ! $data )
			return;

		// Replace the post ID.
		$old_string = sprintf( ".e%s-", $bcd->post->ID );
		$new_string = sprintf( ".e%s-", $bcd->new_post( 'ID' ) );
		$this->debug( 'Replacing %s with %s in %s', $old_string, $new_string, $key );
		$data = str_replace( $old_string, $new_string, $data );
		$bcd->custom_fields()->child_fields()->update_meta( $key, $data );
	}

	/**
		@brief		threewp_broadcast_broadcasting_modify_post
		@since		2021-01-13 11:39:02
	**/
	public function threewp_broadcast_broadcasting_modify_post( $action )
	{
		$bcd = $action->broadcasting_data;		// Convenience

		// Replace the [cs_content _p='8190'] shortcode.
		$bcd->modified_post->post_content = str_replace(
			"[cs_content _p='" . $bcd->post->ID . "']",
			"[cs_content _p='" . $bcd->new_post( 'ID' ) . "']",
			$bcd->modified_post->post_content
		);

		$this->maybe_parse_cornerstone_data( $bcd );
	}

	/**
		@brief		Menu
		@since		2020-11-20 14:50:49
	**/
	public function threewp_broadcast_menu( $action )
	{
		$action->menu_page
			->submenu( 'broadcast_cornerstone' )
			->callback_this( 'admin_tabs' )
			->menu_title( 'Cornerstone' )
			->page_title( 'Broadcast Cornerstone' );
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2021-01-13 10:10:57
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;

		$key = '_cornerstone_data';
		$cf = $bcd->custom_fields()->get_single( $key );
		if ( $cf )
		{
			$cf = json_decode( $cf );
			$this->debug( 'Cornerstone data found in custom field %s.', $key );
			// Remember things.
			foreach( $cf as $index => $region )
				$this->preparse_cornerstone( $bcd, $region );
		}

		if ( $bcd->post->post_status == 'tco-data' )
		{
			$post_content = json_decode( $bcd->post->post_content );

			if ( ! $post_content )
				return;

			$this->debug( 'Cornerstone data found in post content.' );

			// Remember things.
			foreach( $post_content as $index => $region )
				$this->preparse_cornerstone( $bcd, $region );
		}
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Add our tabs to the menu.
		@since		2020-05-13 22:01:21
	**/
	public function admin_tabs( $action )
	{
		$tabs = $this->tabs();

		$tabs->tab( 'bc_cornerstone_global_block' )
			->callback_this( 'broadcast_cornerstone_global_block' )
			->heading( __( 'Broadcast Cornerstone Global_blocks', 'threewp_broadcast' ) )
			->name( __( 'Global Blocks', 'threewp_broadcast' ) );

		$tabs->tab( 'bc_cornerstone_header' )
			->callback_this( 'broadcast_cornerstone_header' )
			->heading( __( 'Broadcast Cornerstone Headers', 'threewp_broadcast' ) )
			->name( __( 'Headers', 'threewp_broadcast' ) );

		$tabs->tab( 'bc_cornerstone_footer' )
			->callback_this( 'broadcast_cornerstone_footer' )
			->heading( __( 'Broadcast Cornerstone Footers', 'threewp_broadcast' ) )
			->name( __( 'Footers', 'threewp_broadcast' ) );

		$tabs->tab( 'bc_cornerstone_layout' )
			->callback_this( 'broadcast_cornerstone_layout' )
			->heading( __( 'Broadcast Cornerstone Layouts', 'threewp_broadcast' ) )
			->name( __( 'Layouts', 'threewp_broadcast' ) );

		$tabs->tab( 'bc_cornerstone_template' )
			->callback_this( 'broadcast_cornerstone_template' )
			->heading( __( 'Broadcast Cornerstone Templates', 'threewp_broadcast' ) )
			->name( __( 'Templates', 'threewp_broadcast' ) );

		$tabs->tab( 'bc_cornerstone_user_template' )
			->callback_this( 'broadcast_cornerstone_user_template' )
			->heading( __( 'Broadcast Cornerstone User Templates', 'threewp_broadcast' ) )
			->name( __( 'User Templates', 'threewp_broadcast' ) );

		echo $tabs->render();
	}

	/**
		@brief		Broadcast the CS footer post types.
		@since		2020-11-20 14:48:17
	**/
	public function broadcast_cornerstone_footer()
	{
		echo $this->broadcast_generic_post_ui( [
			'post_type' => 'cs_footer',
			'post_status' => 'tco-data',
			'label_plural' => 'footers',
			'label_singular' => 'footer',
		] );
	}

	/**
		@brief		Broadcast the CS global block post types.
		@since		2020-11-20 14:48:17
	**/
	public function broadcast_cornerstone_global_block()
	{
		echo $this->broadcast_generic_post_ui( [
			'post_type' => 'cs_global_block',
			'post_status' => 'tco-data',
			'label_plural' => 'global blocks',
			'label_singular' => 'global block',
		] );
	}

	/**
		@brief		Broadcast the CS header post types.
		@since		2020-11-20 14:48:17
	**/
	public function broadcast_cornerstone_header()
	{
		echo $this->broadcast_generic_post_ui( [
			'post_type' => 'cs_header',
			'post_status' => 'tco-data',
			'label_plural' => 'headers',
			'label_singular' => 'header',
		] );
	}

	/**
		@brief		Broadcast the CS layout post types.
		@since		2020-11-20 14:48:17
	**/
	public function broadcast_cornerstone_layout()
	{
		echo $this->broadcast_generic_post_ui( [
			'post_type' => 'cs_layout',
			'post_status' => 'tco-data',
			'label_plural' => 'layouts',
			'label_singular' => 'layout',
		] );
	}

	/**
		@brief		Broadcast the CS template post types.
		@since		2020-11-20 14:48:17
	**/
	public function broadcast_cornerstone_template()
	{
		echo $this->broadcast_generic_post_ui( [
			'post_type' => 'cs_template',
			'post_status' => 'tco-data',
			'label_plural' => 'templates',
			'label_singular' => 'template',
		] );
	}

	/**
		@brief		Broadcast the CS user_template post types.
		@since		2020-11-20 14:48:17
	**/
	public function broadcast_cornerstone_user_template()
	{
		echo $this->broadcast_generic_post_ui( [
			'post_type' => 'cs_user_template',
			'post_status' => 'tco-data',
			'label_plural' => 'user_templates',
			'label_singular' => 'user_template',
		] );
	}

	/**
		@brief		Decide whether to parse the data.
		@since		2021-01-13 11:29:20
	**/
	public function maybe_parse_cornerstone_data( $bcd )
	{
		if ( $bcd->post->post_status != 'tco-data' )
			return;

		$post_content = json_decode( $bcd->modified_post->post_content );

		if ( ! $post_content )
			return;

		$bcd->modified_post->post_content = cs_json_encode( $this->parse_cornerstone( $bcd, $post_content ) );
	}

	/**
		@brief		Parse the cornerstone data.
		@since		2021-01-13 10:37:34
	**/
	public function parse_cornerstone( $bcd, $data )
	{
		if ( is_object( $data ) )
		{
			foreach( static::$image_keys as $key )
			{
				if ( isset( $data->$key ) )
				{
					$value = $data->$key;
					$old_image_id = $value;
					$old_image_id = preg_replace( '/:.*/', '', $old_image_id );
					$old_image_id = intval( $old_image_id );
					if ( intval( $old_image_id ) > 0 )
					{
						$new_image_id = $bcd->copied_attachments()->get( $old_image_id );
						$this->debug( 'Replacing image %s with %s', $old_image_id, $new_image_id );
						$data->$key = str_replace( $old_image_id, $new_image_id, $data->$key );
					}
				}
			}
		}

		foreach( (array)$data as $key => $value )
		{
			if ( is_object( $data ) )
				$data->$key = $this->parse_cornerstone( $bcd, $value );
			if ( is_array( $data ) )
				$data[ $key ] = $this->parse_cornerstone( $bcd, $value );
		}

		return $data;
	}

	/**
		@brief		Preparse the cornerstone data.
		@since		2021-01-13 10:37:34
	**/
	public function preparse_cornerstone( $bcd, $data )
	{
		if ( is_object( $data ) )
		{
			foreach( static::$image_keys as $key )
			{
				if ( isset( $data->$key ) )
				{
					$value = $data->$key;
					$image_id = $value;
					$image_id = preg_replace( '/:.*/', '', $image_id );
					$image_id = intval( $image_id );
					$this->debug( 'Image ID: %s -> %s', $value, $image_id );
					if ( intval( $image_id ) > 0 )
						if ( $bcd->try_add_attachment( $image_id ) )
							$this->debug( 'Found image %s.', $image_id );
				}
			}
		}

		foreach( (array)$data as $key => $value )
		{
			if ( is_object( $value ) || is_array( $value ) )
				$this->preparse_cornerstone( $bcd, $value );
		}

		return $data;
	}
}
