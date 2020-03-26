<?php

namespace threewp_broadcast\premium_pack\metaslider;

/**
	@brief		Storage class for the metaslider [shortcode] data.
	@details	This was created in order to allow more generic handling of Metasliders, which in turn allowed me to write a custom plugin for someone who had their metaslider shortcode in a custom field.
	@since		2015-07-26 17:52:11
**/
class data
	extends \threewp_broadcast\collection
{
	/**
		@brief		The broadcasting data.
		@since		2015-07-26 18:05:05
	**/
	public $__broadcasting_data;

	/**
		@brief		Copies the sliders and sets the correct slides for each slider.
		@since		2015-07-26 18:01:49
	**/
	public function copy_sliders( $source_blog_id, $target_blog_id )
	{
		foreach( $this->collection( 'shortcodes' ) as $shortcode => $id )
		{
			Metaslider::instance()->debug( 'Handling shortcode: %s', $shortcode );

			$current_blog = get_current_blog_id();

			// The API works from the current blog, so we have to switch.
			switch_to_blog( $this->collection( 'source_blog_id' )->get( $shortcode ) );
			$slider_bcd = ThreeWP_Broadcast()->api()->broadcast_children( $id, [ $current_blog ] );
			restore_current_blog();

			$new_slider_id = $slider_bcd->broadcast_data->get_linked_child_on_this_blog();
			$new_shortcode = str_replace( $id, $new_slider_id, $shortcode );

			Metaslider::instance()->debug( 'New shortcode: %s', $new_shortcode );
			$this->collection( 'new_shortcodes' )->set( $shortcode, $new_shortcode );

			if ( ! term_exists( $new_slider_id, 'ml-slider' ) )
				wp_insert_term( $new_slider_id, 'ml-slider' );

			$new_term = get_term_by( 'name', $new_slider_id, 'ml-slider' );

			$slides = $this->collection( 'slides' )->get( $shortcode );
			foreach( $slides as $slide )
			{
				// We need the new image ID.
				$new_slide_id = $this->__broadcasting_data->copied_attachments()->get( $slide->ID );
				Metaslider::instance()->debug( 'The new slide ID is %s.', $new_slide_id );

				// Retrieve the image's current terms.
				$terms = wp_get_object_terms( $new_slide_id, 'ml-slider', array( 'fields' => 'ids' ) );
				Metaslider::instance()->debug( 'The images current terms are: %s', $terms );

				// And maybe append new slider's term.
				if ( ! in_array( $new_term->term_id, $terms ) )
				{
					$terms []= $new_term->term_id;
					wp_set_object_terms( $new_slide_id, $terms, 'ml-slider' );
					Metaslider::instance()->debug( 'The images new terms are: %s', $terms );
				}
			}
		}
	}

	/**
		@brief		Extracts the ID of the slider in the shortcode.
		@since		2015-07-26 18:12:44
	**/
	public static function get_slider_id( $shortcode )
	{
		// We want just the ID,
		$shortcode = preg_replace( '/.*id=/', '', $shortcode );
		// And nothing after the numbers.
		$shortcode = preg_replace( '/[ \]].*/', '', $shortcode );
		// Remove all non-numbers.
		$shortcode = preg_replace( '/[^0-9]/', '', $shortcode );
		return $shortcode;
	}

	/**
		@brief		Parse the shortcode.
		@since		2015-07-26 17:53:35
	**/
	public function parse_shortcode( $shortcode )
	{
		if ( strpos( $shortcode, 'metaslider' ) === false )
			return;

		$slider_id = static::get_slider_id( $shortcode );

		// Is there a ml-slider term?
		$term = get_term_by( 'name', $slider_id, 'ml-slider' );

		if ( ! $term )
		{
			Metaslider::instance()->debug( 'No ml-slider term called %s found. Ignoring.', $slider_id );
			return;
		}

		$this->collection( 'shortcodes' )->set( $shortcode, $slider_id );
		$this->collection( 'source_blog_id' )->set( $shortcode, get_current_blog_id() );

		$slider = new \MetaSlider( $slider_id, [] );
		$slides = $slider->get_slides();
		$slides = $slides->posts;
		$this->collection( 'slides' )->set( $shortcode, $slides );
	}

	/**
		@brief		Add the the attachments from all of the slides.
		@since		2015-07-26 17:56:43
	**/
	public function add_attachments( $broadcasting_data )
	{
		foreach( $this->collection( 'slides' ) as $slides )
			foreach( $slides as $slide )
			{
				Metaslider::instance()->debug( 'Adding slide %s', $slide->ID );
				$broadcasting_data->try_add_attachment( $slide->ID );
			}
	}
}