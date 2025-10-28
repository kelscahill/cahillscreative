<?php

namespace threewp_broadcast\premium_pack\bricks_builder;

use \Exception;
use \plainview\sdk_broadcast\collections\collection;
use \threewp_broadcast\attachment_data;
use \threewp_broadcast\broadcasting_data;

/**
	@brief				Adds support for the <a href="https://bricksbuilder.io/">Bricks Builder page editor</a>.
	@plugin_group		3rd party compatability
	@since				2024-06-18 18:24:38
**/
class Bricks_Builder
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		Custom fields with parseable content.
		@since		2024-08-15 11:04:45
	**/
	public static $content_custom_fields = [
		'_bricks_page_content_2',
		'_bricks_page_header_2',
		'_bricks_page_footer_2',
	];

	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2024-06-18 18:33:17
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->bricks_builder ) )
			return;

		// Handle CSS
		$parent_bricks_global_classes = $bcd->bricks_builder->get( 'bricks_global_classes' );
		$child_bricks_global_classes = get_option( 'bricks_global_classes' );
		$child_bricks_global_classes = maybe_unserialize( $child_bricks_global_classes );
		if ( ! $child_bricks_global_classes )
			$child_bricks_global_classes = [];
		$child_bricks_global_classes = $this->array_rekey( $child_bricks_global_classes, 'id' );

		// And the global elements.
		$parent_bricks_global_elements = $bcd->bricks_builder->get( 'bricks_global_elements' );
		$child_bricks_global_elements = get_option( 'bricks_global_elements' );
		$child_bricks_global_elements = maybe_unserialize( $child_bricks_global_elements );
		if ( ! $child_bricks_global_elements )
			$child_bricks_global_elements = [];
		$child_bricks_global_elements = $this->array_rekey( $child_bricks_global_elements, 'global' );

		foreach( static::$content_custom_fields as $custom_field_type )
		{
			$type_data = $bcd->bricks_builder->get( $custom_field_type );

			if ( ! $type_data )
				continue;

			foreach( $type_data as $index => $section )
			{
				if ( isset( $section[ 'global' ] ) )
				{
					$global_id = $section[ 'global' ];
					if ( ! isset( $parent_bricks_global_elements[ $global_id ] ) )
						continue;
					$child_bricks_global_elements[ $global_id ] = $parent_bricks_global_elements[ $global_id ];
				}

				if ( ! isset( $section[ 'settings' ] ) )
					continue;

				$settings = $section[ 'settings' ];
				if ( isset( $settings[ '_cssGlobalClasses' ] ) )
				{
					foreach( $settings[ '_cssGlobalClasses' ] as $css_class )
					{
						if ( ! isset( $parent_bricks_global_classes[ $css_class ] ) )
							continue;
						$child_bricks_global_classes[ $css_class ] = $parent_bricks_global_classes[ $css_class ];
					}
				}
			}

			foreach( $type_data as $index => $section )
			{
				if ( ! isset( $section[ 'name' ] ) )
					continue;

				$settings = false;
				if ( isset( $section[ 'settings' ] ) )
					$settings = $section[ 'settings' ];

				switch( $section[ 'name' ] )
				{
					case 'image-gallery':
						$new_images = [];
						foreach( $settings[ 'items' ][ 'images' ] as $image )
						{
							$new_image = [];
							$image_id = $image[ 'id' ];
							$new_image_id = $bcd->copied_attachments()->get( $image_id );
							$this->debug( 'Replacing image %s with %s', $image_id, $new_image_id );
							$new_image[ 'id' ] = $new_image_id;

							foreach( [ 'full', 'url' ] as $type )
							{
								$original = $image[ $type ];
								$new_image[ $type ] =
									ThreeWP_Broadcast()->update_attachment_ids( $bcd, $original );
							}
							$new_images []= $new_image;
						}
						$this->debug( 'New gallery images: %s', $new_images );
						$type_data[ $index ][ 'settings' ][ 'items' ][ 'images' ] = $new_images;
					break;
					case 'image':
						$image = $settings[ 'image' ];
						if ( ! isset( $image[ 'id' ] ) )
							break;
						$image_id = $image[ 'id' ];
						$new_image_id = $bcd->copied_attachments()->get( $image_id );
						$this->debug( 'Replacing image %s with %s', $image_id, $new_image_id );
						$type_data[ $index ][ 'settings' ][ 'image' ][ 'id' ] = $new_image_id;

						foreach( [ 'full', 'url' ] as $type )
						{
							$original = $type_data[ $index ][ 'settings' ][ 'image' ][ $type ];
							$type_data[ $index ][ 'settings' ][ 'image' ][ $type ] =
								ThreeWP_Broadcast()->update_attachment_ids( $bcd, $original );
						}
					break;
					case 'logo':
						$image = $settings[ 'logo' ];
						if ( ! isset( $image[ 'id' ] ) )
							break;
						$image_id = $image[ 'id' ];
						$new_image_id = $bcd->copied_attachments()->get( $image_id );
						$this->debug( 'Replacing logo %s with %s', $image_id, $new_image_id );
						$type_data[ $index ][ 'settings' ][ 'logo' ][ 'id' ] = $new_image_id;

						foreach( [ 'full', 'url' ] as $type )
						{
							$original = $type_data[ $index ][ 'settings' ][ 'logo' ][ $type ];
							$type_data[ $index ][ 'settings' ][ 'logo' ][ $type ] =
								ThreeWP_Broadcast()->update_attachment_ids( $bcd, $original );
						}
					break;
					case 'svg':
						$image = $settings[ 'file' ];
						if ( ! isset( $image[ 'id' ] ) )
							break;
						$image_id = $image[ 'id' ];
						$new_image_id = $bcd->copied_attachments()->get( $image_id );
						$this->debug( 'Replacing svg %s with %s', $image_id, $new_image_id );
						$type_data[ $index ][ 'settings' ][ 'file' ][ 'id' ] = $new_image_id;

						foreach( [ 'url' ] as $type )
						{
							$original = $type_data[ $index ][ 'settings' ][ 'file' ][ $type ];
							$type_data[ $index ][ 'settings' ][ 'file' ][ $type ] =
								ThreeWP_Broadcast()->update_attachment_ids( $bcd, $original );
						}
					break;
				}

				if ( isset( $settings[ '_typography' ] ) )
				{
					if ( isset( $settings[ '_typography' ][ 'font-family' ] ) )
					{
						$ff = $settings[ '_typography' ][ 'font-family' ];
						if ( str_starts_with( $ff, 'custom_font_' ) )
						{
							$ff_id = str_replace( 'custom_font_', '', $ff );
							$new_ff_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $ff_id, get_current_blog_id() );
							$this->debug( 'Replacing %s with %s', $ff, $new_ff_id );
							$type_data[ $index ][ 'settings' ][ '_typography' ][ 'font-family' ] = 'custom_font_' . $new_ff_id;
						}
					}
				}

				if ( isset( $settings[ 'icon' ] ) )
				{
					if ( isset( $settings[ 'icon' ][ 'svg' ] ) )
					{
						$image = $settings[ 'icon' ][ 'svg' ];
						$image_id = $image[ 'id' ];
						$new_image_id = $bcd->copied_attachments()->get( $image_id );
						$this->debug( 'Replacing icon svg %s with %s', $image_id, $new_image_id );
						$type_data[ $index ][ 'settings' ][ 'icon' ][ 'svg' ][ 'id' ] = $new_image_id;
						$type_data[ $index ][ 'settings' ][ 'icon' ][ 'svg' ][ 'url' ] = ThreeWP_Broadcast()->update_attachment_ids( $bcd, $original );
					}
				}
			}

			$this->debug( 'Updating bricks field %s with %s', $custom_field_type, $type_data );

			$bcd->custom_fields()
				->child_fields()
				->update_meta( $custom_field_type, $type_data );
		}

		$child_bricks_global_classes = array_values( $child_bricks_global_classes );
		$this->debug( 'Setting new bricks_global_classes to %s', $child_bricks_global_classes );
		update_option( 'bricks_global_classes', $child_bricks_global_classes );

		$child_bricks_global_elements = array_values( $child_bricks_global_elements );
		$this->debug( 'Setting new bricks_global_elements to %s', $child_bricks_global_elements );
		update_option( 'bricks_global_elements', $child_bricks_global_elements );

		// Regenerate the CSS.
		do_action( 'bricks_regenerate_css_files' );
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2024-06-18 18:25:33
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;

		$this->prepare_bcd( $bcd );

		foreach( static::$content_custom_fields as $type )
		{
			$type_data = $bcd->custom_fields()->get_single( $type );

			if ( ! $type_data )
				continue;

			$type_data = maybe_unserialize( $type_data );
			$bcd->bricks_builder->set( $type, $type_data );

			$this->debug( 'Type data for %s is %s', $type, $type_data );

			foreach( $type_data as $index => $section )
			{
				if ( ! isset( $section[ 'settings' ] ) )
					continue;

				$settings = $section[ 'settings' ];
				if ( ! isset( $settings[ '_cssGlobalClasses' ] ) )
					continue;
				foreach( $settings[ '_cssGlobalClasses' ] as $css_class )
				{
					if ( ! isset( $parent_bricks_global_classes[ $css_class ] ) )
						continue;
					$child_bricks_global_classes[ $css_class ] = $parent_bricks_global_classes[ $css_class ];
				}
			}

			foreach( $type_data as $index => $section )
			{
				if ( ! isset( $section[ 'name' ] ) )
					continue;

				$settings = false;
				if ( isset( $section[ 'settings' ] ) )
					$settings = $section[ 'settings' ];

				switch( $section[ 'name' ] )
				{
					case 'image-gallery':
						foreach( $settings[ 'items' ][ 'images' ] as $image )
						{
							$image_id = $image[ 'id' ];
							if ( $bcd->try_add_attachment( $image_id ) )
								$this->debug( 'Image in image-gallery %s found', $image_id );
						}
					break;
					case 'image':
						$image = $settings[ 'image' ];
						if ( ! isset( $image[ 'id' ] ) )
							break;
						$image_id = $image[ 'id' ];
						if ( $bcd->try_add_attachment( $image_id ) )
							$this->debug( 'Image %s found', $image_id );
					break;
					case 'logo':
						$image = $settings[ 'logo' ];
						if ( ! isset( $image[ 'id' ] ) )
							break;
						$image_id = $image[ 'id' ];
						if ( $bcd->try_add_attachment( $image_id ) )
							$this->debug( 'Logo %s found', $image_id );
					break;
					case 'svg':
						$image = $settings[ 'file' ];
						if ( ! isset( $image[ 'file' ] ) )
							break;
						$image_id = $image[ 'id' ];
						if ( $bcd->try_add_attachment( $image_id ) )
							$this->debug( 'SVG %s found', $image_id );
					break;
				}

				if ( isset( $settings[ 'icon' ] ) )
				{
					if ( isset( $settings[ 'icon' ][ 'svg' ] ) )
					{
						$image = $settings[ 'icon' ][ 'svg' ];
						$image_id = $image[ 'id' ];
						if ( $bcd->try_add_attachment( $image_id ) )
							$this->debug( 'Icon svg %s found', $image_id );
					}
				}
			}
		}

		$bricks_global_classes = get_option( 'bricks_global_classes' );
		$bricks_global_classes = maybe_unserialize( $bricks_global_classes );
		// Rekey them to make them easier to access.
		$bricks_global_classes = $this->array_rekey( $bricks_global_classes, 'id' );
		$this->debug( 'bricks_global_classes %s', $bricks_global_classes );
		$bcd->bricks_builder->set( 'bricks_global_classes', $bricks_global_classes );

		$bricks_global_elements = get_option( 'bricks_global_elements' );
		$bricks_global_elements = maybe_unserialize( $bricks_global_elements );
		// Rekey them to make them easier to access.
		$bricks_global_elements = $this->array_rekey( $bricks_global_elements, 'global' );
		$this->debug( 'bricks_global_elements %s', $bricks_global_elements );
		$bcd->bricks_builder->set( 'bricks_global_elements', $bricks_global_elements );
	}

	/**
		@brief		Prepare the BCD.
		@since		2024-08-08 20:51:49
	**/
	public function prepare_bcd( $bcd )
	{
		if ( ! isset( $bcd->bricks_builder ) )
			$bcd->bricks_builder = ThreeWP_Broadcast()->collection();
	}
}
