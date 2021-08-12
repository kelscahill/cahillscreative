<?php

namespace threewp_broadcast\premium_pack\classes\generic_items;

use \Exception;
use \plainview\sdk_broadcast\collections\collection;

/**
	@brief		The item object.
	@since		2016-07-14 12:46:32
**/
class Item
	extends \plainview\sdk_broadcast\collections\collection
{
	use \plainview\sdk_broadcast\traits\method_chaining;

	/**
		@brief		The item slug.
		@since		2014-03-12 14:41:03
	**/
	public $slug;

	/**
		@brief		A collection of single attachment IDs.
		@since		2014-03-12 14:41:03
	**/
	public $value;

	/**
		@brief		A collection of multiple attachment IDs.
		@since		2014-03-12 14:41:03
	**/
	public $values;

	/**
		@brief		Constructor.
		@since		2014-03-12 14:41:44
	**/
	public function __construct()
	{
		$this->value = new collection;
		$this->values = new collection;
	}

	/**
		@brief		Convert to a string.
		@since		2014-03-12 15:10:29
	**/
	public function __toString()
	{
		return sprintf( '%s', $this->slug );
	}

	/**
		@brief		Add a value attribute.
		@since		2014-03-14 09:22:16
	**/
	public function add_value( $attribute )
	{
		$this->value->set( $attribute, true );
	}

	/**
		@brief		Add a value attribute.
		@since		2014-03-14 09:22:16
	**/
	public function add_values( $attribute, $separators = ',' )
	{
		if ( ! is_array( $separators ) )
			$separators = [ $separators ];
		$this->values->set( $attribute, $separators );
	}

	/**
		@brief		Apply a wizard type.
		@since		2016-07-14 13:04:01
	**/
	public function apply_wizard( $type )
	{
	}

	/**
		@brief		Returns some text describing this item.
		@since		2014-03-12 15:12:49
	**/
	public function get_info()
	{
		$r = [];

		if ( $this->value->count() > 0 )
		{
			foreach( $this->value as $slug => $ignore )
				$r[] = sprintf( '%s="%s"', $slug, rand( 1000, 10000 ) );
		}

		if ( $this->values->count() > 0 )
		{
			foreach( $this->values as $slug => $delimiters )
			{
				$delimited = [];
				foreach( $delimiters as $delimiter )
					$delimited[] = sprintf( '%s="%s%s%s%s%s"', $slug, rand( 1000, 10000 ), $delimiter, rand( 1000, 10000 ), $delimiter, rand( 1000, 10000 ) );
			}
			$r []= implode( ' <em>or</em> ', $delimited );
		}

		if ( count( $r ) < 1 )
			return 'No IDs found.';

		return sprintf( '[&emsp;%s&emsp;%s&emsp;]', $this->get_slug(), implode( '&emsp;', $r ) );
	}

	/**
		@brief		Retrieves the slug.
		@since		2014-03-12 14:44:00
	**/
	public function get_slug()
	{
		return $this->slug;
	}

	/**
		@brief		Return the value as a string, fit for a textarea.
		@since		2014-03-12 16:03:48
	**/
	public function get_value_text()
	{
		$r = '';
		foreach( $this->value as $slug => $ignore )
			$r .= sprintf( "%s\n", $slug );
		return $r;
	}

	/**
		@brief		Return the values as a string, fit for a textarea.
		@since		2014-03-12 16:03:48
	**/
	public function get_values_text()
	{
		$r = '';
		foreach( $this->values as $slug => $values )
		{
			$r .= sprintf( "%s %s\n", $slug, implode( ' ', $values ) );
		}
		return $r;
	}

	/**
		@brief		Return an array of select options showing which wizards are available.
		@since		2016-07-14 12:51:16
	**/
	public function get_wizard_options()
	{
		return [];
	}

	/**
		@brief		Parse the value string from the settings tab into the value array.
		@since		2014-03-13 20:06:15
	**/
	public function parse_value( $string )
	{
		$string = trim( $string );
		$lines = explode( "\n", $string );
		$lines = array_filter( $lines );
		$r = new collection;
		foreach( $lines as $index => $line )
		{
			// We only want the first word.
			$word = preg_replace( '/ .*/', '', $line );
			$word = trim( $word );
			if ( $word == '' )
				continue;
			$r->set( $word, true );
		}
		$this->value = $r;
	}

	/**
		@brief		Parse the value string from the settings tab into the value array.
		@since		2014-03-13 20:06:15
	**/
	public function parse_values( $string )
	{
		$string = trim( $string );
		$lines = explode( "\n", $string );
		$lines = array_filter( $lines );
		$r = new collection;
		foreach( $lines as $index => $line )
		{
			// We need the first word as the attribute key.
			$word = preg_replace( '/ .*/', '', $line );
			$word = trim( $word );
			if ( $word == '' )
				continue;
			// Whatever other words are left in the string, use them as delimiters.
			$delimiters = str_replace( $word, '', $line );
			$delimiters = trim( $delimiters );
			$delimiters = explode( ' ', $delimiters );
			$delimiters = array_filter( $delimiters );
			if ( count( $delimiters ) < 1 )
				$delimiters []= ',';
			$r->set( $word, $delimiters );
		}
		$this->values = $r;
	}

	/**
		@brief		Sets the slug.
		@since		2014-03-12 14:40:52
	**/
	public function set_slug( $key )
	{
		return $this->set_key( 'slug', $key );
	}
}
