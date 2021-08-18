<?php

namespace threewp_broadcast;

/**
	@brief		GB related functions.
	@since		2019-07-22 17:54:04
**/
class gutenberg
{
	/**
		@brief		Return an array of gutenberg blocks.
		@since		2019-07-19 16:44:16
	**/
	public static function parse_blocks( $content, $options = [] )
	{
		$options = array_merge( [
			/**
				@brief		Detect whether to stripslashes from the attributes.
				@details	true = always
							false = never
							null = detect
				@since		2020-02-13 16:00:06
			**/
			'stripslashes' => null,
		], $options );
		$blocks = [];	// Completely ignore the blocks WP reports. We want the real blocks.
		// Thanks for the help with this amazing regexp.
		// bo: KubiQ
		preg_match_all(
			'/<!--\s+(?P<closer>\/)?wp:(?P<namespace>[a-z][a-z0-9_-]*\/)?(?P<name>[a-z][a-z0-9_-]*)\s+(?P<attrs>{(?:(?:[^}]+|}+(?=})|(?!}\s+\/?-->).)*+)?}\s+)?(?P<void>\/)?-->/s',
			$content,
			$matches/*,
			PREG_OFFSET_CAPTURE*/
		);
		// eo: KubiQ

		foreach ( $matches[ 0 ] as $index => $match )
		{
			if ( strpos( $match, '<!-- /' ) !== false )
				continue;
			$match = str_replace( '<!-- wp:', '', $match );
			$key = preg_replace( '/ .*/m', '', $match );
			$key = preg_replace( '/\n.*/m', '', $key );
			$params = str_replace( $key . ' ', '', $match );
			$params = preg_replace( '/[\/]?-->/', '', $params );

			// If the params are \u encoded, we'll need to preserve the slashes, otherwise remove them.
			if ( $options[ 'stripslashes' ] !== false )
			{
				$strip = false;
				if ( $options[ 'stripslashes' ] === true )
					$strip = true;
				if ( $options[ 'stripslashes' ] === null )
					$strip = static::string_has_unicode( $params );
				if ( $strip )
					$params = stripslashes( $params );
			}

			$params = json_decode( $params, true);

			$blocks []= [
				'attrs' => $params,
				'blockName' => $key,
				'innerContent' => [],
				'original' => $matches[ 0 ][ $index ],
			];
		}
		return $blocks;
	}

	/**
		@brief		Convert a GB array to a HTML.
		@details	Since the render_block doesn't actually do what it says, I have to do it myself.
		@since		2019-06-25 21:30:15
	**/
	public static function render_block( $block, $options = [] )
	{
		$options = array_merge( [
			'force_json_options' => false,
			'json_options' => JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
		], $options );
		$options = ( object ) $options;

		switch( $block[ 'blockName' ] )
		{
			case 'core/gallery':
				$block_name = 'gallery';
			break;
			default:
				$block_name = $block[ 'blockName' ];
			break;
		}

		// Fancy encode?
		$json_options = 0;
		if ( $options->force_json_options || static::string_has_unicode( $block[ 'original' ] ) )
			$json_options = $options->json_options;

		return sprintf( "<!-- wp:%s %s -->",
			$block_name,
			json_encode( $block[ 'attrs' ], $json_options )
		);
	}

	/**
		@brief		Replace a text string with a rendered block.
		@since		2019-07-22 18:01:48
	**/
	public static function replace_text_with_block( $string, $block, $text, $options = [] )
	{
		$block_text = static::render_block( $block, $options );

		// If the original block ends with /-->, make sure the new block also does so.
		if ( strpos( $string, "/-->" ) !== false )
			$block_text = str_replace( "-->", "/-->", $block_text );

		$text = str_replace( $string, $block_text, $text );

		return $text;
	}

	/**
		@brief		Does this string have any unicode escape sequences?
		@since		2020-02-13 22:33:03
	**/
	public static function string_has_unicode( $string )
	{
		foreach( [ '\u0022', '\u003C', ] as $needle )
			if ( strpos( $string, $needle ) !== false )
				return true;
		return false;
	}
}
