<?php

namespace threewp_broadcast\premium_pack\classes\local_things;

use \threewp_broadcast\premium_pack\local_links\meta_box_data\item;
use \DOMDocument;

/**
	@brief		Generic "Local XXX" handling class.
	@since		2016-09-20 13:14:33
**/
abstract class Local_Things
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_filter( 'threewp_broadcast_parse_content' );
		$this->add_filter( 'threewp_broadcast_prepare_meta_box' );
		$this->add_filter( 'threewp_broadcast_preparse_content' );
	}

	/**
		@brief		Check for requirements.
		@since		2016-09-20 13:14:52
	**/
	public function activate()
	{
		if ( ! class_exists( 'DOMDocument' ) )
			wp_die( sprintf( '%s: The DOM PHP extension must be installed.', get_called_class( $this ) ) );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	public function threewp_broadcast_parse_content( $action )
	{
		$bcd = $action->broadcasting_data;
		$key = $this->get_addon_key();

		if ( ! isset( $bcd->$key ) )
			return;

		if ( ! $bcd->$key->has( $action->id ) )
			return;

		$things = $bcd->$key->get( $action->id );
		$blog_id = get_current_blog_id();

		$this->debug( '%s things found for content %s', count( $things ), $action->id );

		// Go through all of the things in the post content.
		foreach( $things as $thing )
		{
			$o = (object)[];
			$o->broadcasting_data = $bcd;
			$o->content = $action->content;
			$o->thing = $thing;
			$this->parse_content_with_thing( $o );

			// Why is $o->content not being a pointer?
			$action->content = $o->content;
		}
	}

	/**
		@brief		Allow the subclasses to modify the meta box with a checkbox.
		@since		2016-09-21 12:51:37
	**/
	public function threewp_broadcast_prepare_meta_box( $action )
	{
	}

	/**
		@brief		Preparse the content.
		@since		2016-04-22 13:19:14
	**/
	public function threewp_broadcast_preparse_content( $action )
	{
		$bcd = $action->broadcasting_data;
		$mbd = $bcd->meta_box_data;
		$key = $this->get_addon_key();
		$item = $mbd->html->get( $key );

		if ( ! is_object( $item ) )
			return;

		$checkbox = $item->inputs->get( $key );
		if ( ! $checkbox->is_checked() )
			return;

		// Get the post content
		$content = $action->content;

		if ( strlen( $content ) < 1 )
			return;

		// Create a DOMDocument.
		$html = new DOMDocument;
		// Use some charset meta to help the domdocument parse the content.
		$html_meta = '<meta http-equiv="content-type" content="text/html; charset=utf-8">';
		// @ because sometimes HTML is badly formed.
		@$html->loadHTML( $html_meta . $content );

		// We need to get the url of this blog.
		$url = get_bloginfo( 'url' );
		$url_key = 'shown_url_' . get_current_blog_id();
		if ( ! isset( $this->$url_key ) )
		{
			$this->$url_key = true;
			$this->debug( 'URL is %s', $url );
		}

		$things = $this->new_things();

		// Find all elements and attributes.
		foreach( $this->get_find_elements() as $element_tag => $element_attribute )
		{
			$elements = $html->getElementsByTagName( $element_tag );
			if ( $elements->length < 1 )
				continue;
			$this->debug( '%s anchors found.', $elements->length );
			foreach( $elements as $element )
			{
				$attribute = $element->getAttribute( $element_attribute );
				$local = false;

				// Does the href contain a site url?
				if ( strpos( $attribute, $url ) !== false )
					$local = true;

				// No scheme = local
				if ( ! $local )
				{
					$parsed_attribute = parse_url( $attribute );
					$parsed_attribute = (object) $parsed_attribute;
					if ( ! property_exists( $parsed_attribute, 'scheme' ) )
						$local = true;
				}

				if ( ! $local )
					continue;

				$thing = $this->parse_element_attribute( (object)[
					'attribute' => $attribute,
					'broadcasting_data' => $bcd,
					'element' => $element,
				] );

				if ( ! $thing )
					continue;

				$things->append( $thing );
			}
		}

		$this->after_preparse_find_loop( (object)[
			'content' => $action->content,
			'things' => $things,
		] );

		// Are there any links left after checking the broadcast data?
		if ( count( $things ) < 1 )
			return;

		$this->debug( 'Saving %s things for content %s.', count( $things ), $action->id );

		// Save the things in the broadcasting data, ready to be used for each child post.
		if ( ! isset( $bcd->$key ) )
			$bcd->$key = ThreeWP_Broadcast()->collection();

		$bcd->$key->set( $action->id, $things );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Do this after the preparse find loop.
		@details	$o is an options array:
						->content is the content being parsed.
						->things
		@since		2016-09-20 22:15:25
	**/
	public function after_preparse_find_loop( $o )
	{
	}

	/**
		@brief		Return the key that is used generally around the plugin: local_links or local_files or whatever.
		@since		2016-09-20 22:02:50
	**/
	public abstract function get_addon_key();

	/**
		@brief		Return an array of HTML element tags and atts to search for.
		@since		2016-09-20 22:20:15
	**/
	public function get_find_elements()
	{
		return [
			'a' => 'href',
		];
	}

	/**
		@brief		Create a new thing object.
		@since		2016-09-20 22:36:38
	**/
	public function new_thing()
	{
		return new Thing();
	}

	/**
		@brief		Return a new things container.
		@since		2016-09-20 13:17:16
	**/
	public function new_things()
	{
		return new Things();
	}

	/**
		@brief		Parse the content using a thing.
		@since		2016-09-21 00:12:31
	**/
	public function parse_content_with_thing( $o )
	{
	}

	/**
		@brief		Parse an attribute, converting it to a thing.
		@since		2016-09-20 22:33:49
	**/
	public function parse_element_attribute( $o )
	{
	}
}
