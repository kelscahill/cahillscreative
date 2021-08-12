<?php

namespace threewp_broadcast\premium_pack\classes\gutenberg_items;

use \Exception;
use \plainview\sdk_broadcast\collections\collection;

/**
	@brief		The item object.
	@since		2016-07-14 12:46:32
**/
class Item
	extends \threewp_broadcast\premium_pack\classes\generic_items\Item
{
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
			foreach( $this->values as $slug => $ignore )
				$r[] = sprintf( '%s="%s,%s,%s"', $slug, rand( 1000, 10000 ), rand( 1000, 10000 ), rand( 1000, 10000 ) );
		}

		if ( count( $r ) < 1 )
			return 'No IDs found.';

		return sprintf( '&lt;!-- wp:%s&emsp;%s&emsp;--&gt;', $this->get_slug(), implode( '&emsp;', $r ) );
	}

	/**
		@brief		Return the values as a string for the textarea.
		@details	No need for delimiters.
		@since		2019-07-31 18:21:44
	**/
	public function get_values_text()
	{
		$r = '';
		foreach( $this->values as $slug => $ignore )
			$r .= sprintf( "%s\n", $slug );
		return $r;
	}

	/**
		@brief		Parse the values string from the settings tab into the value array.
		@since		2019-07-31 18:23:28
	**/
	public function parse_values( $string )
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
		$this->values = $r;
	}
}
