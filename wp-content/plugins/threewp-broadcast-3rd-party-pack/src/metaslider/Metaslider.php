<?php

namespace threewp_broadcast\premium_pack\metaslider;

/**
	@brief			Adds support for <a href="https://www.metaslider.com/">Metaslider</a> shortcodes.
	@plugin_group	3rd party compatability
	@since			2015-07-26 15:59:26
**/
class Metaslider
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_filter( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_broadcasting_modify_post' );
	}

	/**
		@brief		Return the slides of a slider.
		@details	Taken from ml-slider/inc/slider/metaslider.class.php.
		@since		2015-07-26 16:31:42
	**/
	public function get_slides( $slider_id )
	{
        $args = array(
            'force_no_custom_order' => true,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'lang' => '', // polylang, ingore language filter
            'suppress_filters' => 1, // wpml, ignore language filter
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'ml-slider',
                    'field' => 'slug',
                    'terms' => $slider_id
                )
            )
        );

        //$args = apply_filters( 'metaslider_populate_slides_args', $args, $this->id, $this->settings );

        $query = new WP_Query( $args );

        return $query;
	}

	/**
		@brief		Is Metaslider installed?
		@since		2015-07-26 16:00:30
	**/
	public function has_metaslider()
	{
		return class_exists( 'MetaSliderPlugin' );
	}

	/**
		@brief		threewp_broadcast_broadcasting_modify_post
		@since		2015-05-20 20:50:00
	**/
	public function threewp_broadcast_broadcasting_modify_post( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->metaslider ) )
			return;

		$metaslider = $bcd->metaslider;

		// And now replace the matches in the post.
		$mp = $bcd->modified_post;

		// Copy all of the sliders to this current blog.
		$metaslider->copy_sliders( $bcd->parent_blog_id, get_current_blog_id() );

		// And replace the text in the content.
		foreach( $metaslider->collection( 'new_shortcodes' ) as $old_shortcode => $new_shortcode )
		{
			$this->debug( 'Replacing the old shortcode %s with the new shortcode %s.', $old_shortcode, $new_shortcode );
			$mp->post_content = str_replace( $old_shortcode, $new_shortcode, $mp->post_content );
		}
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2015-05-20 20:28:14
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		if ( ! $this->has_metaslider() )
			return;

		$bcd = $action->broadcasting_data;
		$metaslider = new data();

		$matches = ThreeWP_Broadcast()->find_shortcodes( $bcd->post->post_content, [ 'metaslider' ] );

		$count = count( $matches[ 0 ] );
		$this->debug( 'Found %s shortcodes.', $count );

		if ( $count < 1 )
			return;

		foreach( $matches[ 0 ] as $shortcode )
		{
			$metaslider->parse_shortcode( $shortcode );
			$metaslider->add_attachments( $bcd );
			$this->debug( 'Shortcode %s has the ID %s', $shortcode, $metaslider->get_slider_id( $shortcode ) );
		}

		if ( count( $metaslider->collection( 'slides' ) ) < 1 )
			return;

		$bcd->metaslider = $metaslider;
		$metaslider->__broadcasting_data = $bcd;
	}
}
