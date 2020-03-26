<?php

namespace threewp_broadcast\premium_pack\elementor;

use Exception;

/**
	@brief			Adds support for the <a href="https://wordpress.org/plugins/elementor/">Elementor Page Builder plugin</a>.
	@plugin_group	3rd party compatability
	@since			2017-04-28 23:16:00
**/
class Elementor
	extends \threewp_broadcast\premium_pack\base
{
	use \threewp_broadcast\premium_pack\classes\parse_and_preparse_content_trait;

	/**
		@brief		parseable_settings
		@since		2018-12-06 12:30:42
	**/
	public static $parseable_settings = [
		'link',
		'shortcode',
		'text',
		'url',
	];

	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_get_post_types' );
		new Elementor_Template_Shortcode();
	}

	/**
		@brief		Returns the post's Elementor CSS filename.
		@since		2017-08-09 17:21:09
	**/
	public function get_post_css_file( $post_id )
	{
		$wp_upload_dir = wp_upload_dir();
		$path = sprintf( '%s/elementor/css', $wp_upload_dir['basedir'] );
		$new_filename = sprintf( '%s/post-%d.css', $path, $post_id );
		return $new_filename;
	}

	/**
		@brief		Parse an EL element, looking for images and the like.
		@since		2017-04-29 02:14:28
	**/
	public function parse_element( $bcd, $element )
	{
		if ( isset( $element->settings ) )
		{
			foreach( static::$parseable_settings as $type )
			{
				if ( ! isset( $element->settings->$type ) )
					continue;
				$this->preparse_content( [
					'broadcasting_data' => $bcd,
					'content' => $element->settings->$type,
					'id' => 'elementor_' . $element->id,
				] );
			}

			if ( isset( $element->settings->background_image ) )
				if ( $element->settings->background_image->id > 0 )
				{
					$image_id = $element->settings->background_image->id;
					if ( $bcd->try_add_attachment( $image_id ) )
						$this->debug( 'Found background image %s.', $image_id );
				}
		}

		if ( $element->elType == 'widget' )
		{
			switch( $element->widgetType )
			{
				case 'devices-extended':
					$image_id = $element->settings->video_cover->id;
					if ( $bcd->try_add_attachment( $image_id ) )
						$this->debug( 'Found devices-extended widget. Adding attachment %s', $image_id );
					break;
				break;
				case 'gallery':
					foreach( $element->settings->gallery as $gallery_index => $gallery_item )
					{
						$image_id = $gallery_item->id;
						if ( $bcd->try_add_attachment( $image_id ) )
								$this->debug( 'Found gallery widget. Adding attachment %s', $image_id );
					}
					break;
				case 'image':
				case 'image-box':
					$image_id = $element->settings->image->id;
					if ( $bcd->try_add_attachment( $image_id ) )
						$this->debug( 'Found image widget. Adding attachment %s', $image_id );
					break;
				case 'image-gallery':
					foreach( $element->settings->wp_gallery as $gallery_index => $gallery_item )
					{
						$image_id = $gallery_item->id;
						if ( $bcd->try_add_attachment( $image_id ) )
								$this->debug( 'Found image-gallery widget. Adding attachment %s', $image_id );
					}
					break;
				case 'smartslider':
					// Fake a smartslider shortcode.
					$item_id = $element->settings->smartsliderid;
					$this->debug( 'Found item ID for %s is %s', $element->widgetType, $item_id );
					$preparse_content = ThreeWP_Broadcast()->new_action( 'preparse_content' );
					$preparse_content->broadcasting_data = $bcd;
					$preparse_content->content = '[smartslider3 slider="' . $item_id . '"]';
					$preparse_content->id = 'elementor_' . $element->id;
					$preparse_content->execute();
					break;
				case 'text-editor':
					// Send texts for preparsing.
					$preparse_content = ThreeWP_Broadcast()->new_action( 'preparse_content' );
					$preparse_content->broadcasting_data = $bcd;
					$preparse_content->content = $element->settings->editor;
					$preparse_content->id = 'elementor_' . $element->id;
					$preparse_content->execute();
					break;
				case 'uael-caf-styler':		// Caldera Forms.
					$caf_select_caldera_form_id = $element->settings->caf_select_caldera_form;
					$preparse_content = ThreeWP_Broadcast()->new_action( 'preparse_content' );
					$preparse_content->broadcasting_data = $bcd;
					$preparse_content->content = '[caldera_form id="' . $caf_select_caldera_form_id . '"]';
					$preparse_content->id = 'caldera_form_' . $element->id;
					$preparse_content->execute();
					break;
				case 'vt-saaspot_agency':
					$image_id = $element->settings->agency_image->id;
					if ( $bcd->try_add_attachment( $image_id ) )
						$this->debug( 'Found vt-saaspot_agency widget. Adding attachment %s', $image_id );
					break;
				break;
				case 'vt-saaspot_resource':
					foreach( $element->settings->ResourceItems as $index => $resource )
					{
						$image_id = $resource->resource_image->id;
						if ( $bcd->try_add_attachment( $image_id ) )
							$this->debug( 'Found vt-saaspot_resource widget. Adding attachment %s at index %s.', $image_id, $index );
					}
					break;
				break;
			}
		}

		if ( ! isset( $element->elements ) )
			return $element;

		// Parse subelements.
		foreach( $element->elements as $element_index => $subelement )
			$this->parse_element( $bcd, $subelement );

		return $element;
	}

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2017-04-28 23:39:15
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		$meta_key = '_elementor_data';

		$ed = $bcd->custom_fields()->get_single( '_elementor_data' );

		if ( ! $ed )
			return;

		$ed = json_decode( $ed );

		if ( ! $ed )
			return;

		foreach( $ed as $index => $element )
			$ed[ $index ] = $this->update_element( $bcd, $element );

		$ed = json_encode( $ed );

		$this->debug( 'Updating elementor data: <pre>%s</pre>', htmlspecialchars( $ed ) );
		$bcd->custom_fields()
			->child_fields()
			->update_meta_json( $meta_key, $ed );

		// Copy the css file.
		if ( ! isset( $bcd->elementor ) )
			return;
		$old_filename = $bcd->elementor->get( 'old_post_css_filename' );
		$new_filename = $this->get_post_css_file( $bcd->new_post( 'ID' ) );

		// Replace the post ID in the file.
		if ( is_readable( $old_filename ) )
		{
			$css_file = file_get_contents( $old_filename );
			$css_file = str_replace( 'elementor-' . $bcd->post->ID, 'elementor-' . $bcd->new_post( 'ID' ), $css_file );

			file_put_contents( $new_filename, $css_file );

			$this->debug( 'Copied Elementor CSS file %s to %s', $old_filename, $new_filename );
		}
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2017-04-28 23:39:00
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;

		$ed = $bcd->custom_fields()->get_single( '_elementor_data' );

		if ( ! $ed )
			return;

		$ed = json_decode( $ed );
		if ( ! $ed )
			return $this->debug( 'Warning! Elementor data is invalid!' );

		$this->debug( 'Elementor data found: %s', $ed );

		// Remember things.
		foreach( $ed as $index => $section )
			$this->parse_element( $bcd, $section );

		if ( ! isset( $bcd->elementor ) )
			$bcd->elementor = ThreeWP_Broadcast()->collection();

		$bcd->elementor->set( 'old_post_css_filename', $this->get_post_css_file( $bcd->post->ID ) );
		$this->debug( 'Saved old Elementor CSS filename %s', $bcd->elementor->get( 'old_post_css_filename' ) );
	}

	/**
		@brief		Add foogallery types.
		@since		2015-10-02 12:47:49
	**/
	public function threewp_broadcast_get_post_types( $action )
	{
		$action->add_type( 'elementor_library' );
	}

	/**
		@brief		Update the Elementor data with new values.
		@since		2017-04-29 02:26:52
	**/
	public function update_element( $bcd, $element )
	{
		if ( isset( $element->settings ) )
		{
			foreach( static::$parseable_settings as $type )
			{
				if ( ! isset( $element->settings->$type ) )
					continue;
				$element->settings->$type = $this->parse_content( [
					'broadcasting_data' => $bcd,
					'content' => $element->settings->$type,
					'id' => 'elementor_' . $element->id,
				] );
			}

			if ( isset( $element->settings->background_image ) )
				if ( $element->settings->background_image->id > 0 )
				{
					$old_image_id = $element->settings->background_image->id;
					$new_image_id = $bcd->copied_attachments()->get( $old_image_id );
					$this->debug( 'Replacing old background image %s with %s.', $old_image_id, $new_image_id );
					$element->settings->background_image->id = $new_image_id;
					$element->settings->background_image->url = ThreeWP_Broadcast()->update_attachment_ids( $bcd, $element->settings->background_image->url );
				}
		}

		if ( $element->elType == 'widget' )
		{
			switch( $element->widgetType )
			{
				case 'devices-extended':
					$image_id = $element->settings->video_cover->id;
					$new_image_id = $bcd->copied_attachments()->get( $image_id );
					$this->debug( 'Found devices-extended widget. Replacing %s with %s.', $image_id, $new_image_id );
					$element->settings->video_cover->id = $new_image_id;
					$element->settings->video_cover->url = ThreeWP_Broadcast()->update_attachment_ids( $bcd, $element->settings->video_cover->url );
				break;
				case 'gallery':
					foreach( $element->settings->gallery as $gallery_index => $gallery_item )
					{
						$image_id = $gallery_item->id;
						$new_image_id = $bcd->copied_attachments()->get( $image_id );
						$this->debug( 'Found gallery widget. Replacing %s with %s', $image_id, $new_image_id );
						$element->settings->gallery[ $gallery_index ]->id = $new_image_id;
						$element->settings->gallery[ $gallery_index ]->url = ThreeWP_Broadcast()->update_attachment_ids( $bcd, $element->settings->gallery[ $gallery_index ]->url );
					}
					break;
				case 'global':
					$template_id = $element->templateID;
					$this->debug( 'Handling global widget %s', $template_id );
					$new_template_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $template_id, get_current_blog_id() );
					$this->debug( 'New global widget ID %s is %s', $template_id, $new_template_id );
					$element->templateID = $new_template_id;
					break;
				case 'image':
				case 'image-box':
					$image_id = $element->settings->image->id;
					$new_image_id = $bcd->copied_attachments()->get( $image_id );
					$this->debug( 'Found image widget. Replacing %s with %s.', $image_id, $new_image_id );
					$element->settings->image->id = $new_image_id;
					$element->settings->image->url = ThreeWP_Broadcast()->update_attachment_ids( $bcd, $element->settings->image->url );
					break;
				case 'image-gallery':
					foreach( $element->settings->wp_gallery as $gallery_index => $gallery_item )
					{
						$image_id = $gallery_item->id;
						$new_image_id = $bcd->copied_attachments()->get( $image_id );
						$this->debug( 'Found gallery widget. Replacing %s with %s', $image_id, $new_image_id );
						$element->settings->wp_gallery[ $gallery_index ]->id = $new_image_id;
						$element->settings->wp_gallery[ $gallery_index ]->url = ThreeWP_Broadcast()->update_attachment_ids( $bcd, $element->settings->wp_gallery[ $gallery_index ]->url );
					}
					break;
				case 'template':
					$old_template_id = $element->settings->template_id;
					$new_template_id = $bcd->equivalent_posts()->get( $bcd->parent_blog_id, $old_template_id, get_current_blog_id() );
					$this->debug( 'Found template widget. Replacing %d with %d.', $old_template_id, $new_template_id );
					$element->settings->template_id = $new_template_id;
					break;
				case 'smartslider':
					// Fake a smartslider shortcode.
					$item_id = $element->settings->smartsliderid;
					$parse_content = ThreeWP_Broadcast()->new_action( 'parse_content' );
					$parse_content->broadcasting_data = $bcd;
					$parse_content->content = '[smartslider3 slider="' . $item_id . '"]';
					$parse_content->id = 'elementor_' . $element->id;
					$parse_content->execute();

					// Get the new ID
					$parse_content->content = trim( $parse_content->content, '[]' );
					$atts = shortcode_parse_atts( $parse_content->content );
					$new_value = $atts[ 'slider' ];
					$element->settings->smartsliderid = $new_value;
					$this->debug( 'New item ID for %s is %s', $element->widgetType, $new_value );
					break;
				case 'text-editor':
					$parse_content = ThreeWP_Broadcast()->new_action( 'parse_content' );
					$parse_content->broadcasting_data = $bcd;
					$parse_content->content = $element->settings->editor;
					$parse_content->id = 'elementor_' . $element->id;
					$parse_content->execute();
					$this->debug( 'Replaced element %s text-editor with %s', $element->id, htmlspecialchars( $parse_content->content ) );
					$element->settings->editor = $parse_content->content;
					break;
				case 'uael-caf-styler':		// Caldera Forms.
					// Fake a smartslider shortcode.
					$item_id = $element->settings->caf_select_caldera_form;
					$parse_content = ThreeWP_Broadcast()->new_action( 'parse_content' );
					$parse_content->broadcasting_data = $bcd;
					$parse_content->content = '[caldera_form id="' . $item_id . '"]';
					$parse_content->id = 'caldera_form_' . $element->id;
					$parse_content->execute();

					// Get the new ID
					$parse_content->content = trim( $parse_content->content, '[]' );
					$atts = shortcode_parse_atts( $parse_content->content );
					$new_value = $atts[ 'id' ];
					$element->settings->caf_select_caldera_form = $new_value;
					$this->debug( 'New item ID for %s is %s', $element->widgetType, $new_value );
					break;
				case 'vt-saaspot_agency':
					$image_id = $element->settings->agency_image->id;
					$new_image_id = $bcd->copied_attachments()->get( $image_id );
					$this->debug( 'Found vt-saaspot_agency widget. Replacing %s with %s.', $image_id, $new_image_id );
					$element->settings->agency_image->id = $new_image_id;
					$element->settings->agency_image->url = ThreeWP_Broadcast()->update_attachment_ids( $bcd, $element->settings->agency_image->url );
				break;
				case 'vt-saaspot_resource':
					foreach( $element->settings->ResourceItems as $index => $resource )
					{

						$image_id = $resource->resource_image->id;
						$new_image_id = $bcd->copied_attachments()->get( $image_id );
						$this->debug( 'Found vt-saaspot_resource widget. Replacing %s with %s at index %s.', $image_id, $new_image_id, $index );
						$element->settings->ResourceItems[ $index ]->resource_image->id = $new_image_id;
						$element->settings->ResourceItems[ $index ]->resource_image->url = ThreeWP_Broadcast()->update_attachment_ids( $bcd, $element->settings->ResourceItems[ $index ]->resource_image->url );
					}
					break;
				break;
			}
		}

		if ( ! isset( $element->elements ) )
			return $element;

		// Update subelements.
		foreach( $element->elements as $element_index => $subelement )
			$element->elements[ $element_index ] = $this->update_element( $bcd, $subelement );

		return $element;
	}
}
