<?php

/**
 * Global function includes
 */

// Needed for nginx servers
// https://www.php.net/manual/en/function.getallheaders.php
if (!function_exists( 'getallheaders' )) {
	function getallheaders() {
		$headers = array();
		foreach ( $_SERVER as $name => $value ) {
			if ( substr( $name, 0, 5 ) == 'HTTP_' ) {
				$headers[ str_replace(' ', '-', ucwords( strtolower( str_replace( '_', ' ', substr( $name, 5 ) ) ) ) ) ] = $value;
			}
		}
		return $headers;
	}
}

if (!function_exists( 'is_ssl' )) {
	function is_ssl() {
	        if ( isset( $_SERVER[ 'HTTPS' ] ) ) {
	                if ( 'on' == strtolower( $_SERVER[ 'HTTPS' ] ) ) {
	                        return true;
	                }

	                if ( '1' == $_SERVER[ 'HTTPS' ] ) {
	                        return true;
	                }
	        } elseif ( isset( $_SERVER[ 'SERVER_PORT' ] ) && ( '443' == $_SERVER[ 'SERVER_PORT' ] ) ) {
	                return true;
	        }
	        return false;
	}
}

//The global wp object strips ending slashes before exposing the request url path. This checks if it originally had it so we can add it back in
if (!function_exists( 'should_current_path_end_in_slash' )) {
	function should_current_path_end_in_slash() {
		$pathinfo         = isset( $_SERVER[ 'PATH_INFO' ] ) ? $_SERVER[ 'PATH_INFO' ] : '';
		list( $pathinfo ) = explode( '?', $pathinfo );
		$pathinfo         = str_replace( '%', '%25', $pathinfo );
		list( $req_uri )	= explode( '?', $_SERVER[ 'REQUEST_URI' ] );
		$req_uri				= str_replace( $pathinfo, '', $req_uri );

		if ( substr($req_uri,-1) == '/' ) {
			return true;
		}
		return false;
	}
}

/**
 * Get string length
 */
if ( !function_exists( 'ez_strlen' ) ) {
	function ez_strlen( $str ) {
		if ( extension_loaded( 'mbstring' ) ) {
			return \mb_strlen( $str );
		}

		return \strlen( $str );
	}
}

/**
 * Find the position of the first occurrence of a substring in a string
 */
if ( !function_exists( 'ez_strpos' ) ) {
	function ez_strpos( $haystack, $needle ) {
		if ( extension_loaded( 'mbstring' ) ) {
			return \mb_strpos( $haystack, $needle );
		}

		return \strpos( $haystack, $needle );
	}
}

/**
 * Return part of a string
 */
if ( !function_exists( 'ez_substr' ) ) {
	function ez_substr( $string, $start, $length = null ) {
		if ( extension_loaded( 'mbstring' ) ) {
			return \mb_substr( $string, $start, $length );
		}
		return \substr( $string, $start, $length );
	}
}

/**
 * Convert a string to lower-case
 */
if ( !function_exists( 'ez_strtolower' ) ) {
	function ez_strtolower( $string ) {
		if ( extension_loaded( 'mbstring' ) ) {
			return \mb_strtolower( $string );
		}
		return \strtolower( $string );
	}
}

/**
 * Replace text within a portion of a string
 */
if ( !function_exists( 'ez_substr_replace' ) ) {
	function ez_substr_replace( $string, $replacement, $start, $length = 0 ) {
		if ( extension_loaded( 'mbstring' ) ) {
			return \mb_substr( $string, 0, $start ) . $replacement . \mb_substr( $string, $start + $length );
		}

		return \substr_replace( $string, $replacement, $start, $length );
	}
}

/**
 * Find the position of the first occurrence of a case-insensitive substring in a string
 */
if ( !function_exists( 'ez_stripos' ) ) {
	function ez_stripos( $haystack, $needle, $offset = 0 ) {
		// If offset is greater than the length of the string, return
		if ( $offset > \ez_strlen( $haystack ) ) {
			return false;
		}

		// Use multibyte extension, if available
		if ( extension_loaded( 'mbstring' ) ) {
			return \mb_stripos( $haystack, $needle, $offset );
		}

		return \stripos( $haystack, $needle, $offset );
	}
}

/**
 * Determines if the given character is a valid space
 */
if ( !function_exists( 'ez_ctype_space' ) ) {
	function ez_ctype_space( $string ) {
		if ( preg_match('/^[\x09-\x0D]|^\x20/', $string) || $string == '') {
			return true;
		}

		return false;
	}
}

/**
 * Converts all instances of unicode characters into their HTML-valid hex representation
 */
if ( !function_exists( 'ez_encode_unicode' ) ) {
	function ez_encode_unicode( $content ) {
		$content = preg_replace_callback('/[\x{80}-\x{10FFFF}]/u', function ($match) {
			list($utf8) = $match;
			$binary = iconv('UTF-8', 'UTF-32BE', $utf8);
			$entity = vsprintf('&#x%X;', unpack('N', $binary));
			return $entity;
		}, $content);

		return $content;
	}
}

/**
 * Counts the number of words within a block of text (ignores HTML tags)
 */
if ( !function_exists( 'ez_word_count' ) ) {
	function ez_word_count( $content ) {
		// Strip HTML tags
		$content = \preg_replace( '/(<\/[^>]+?>)(<[^>\/][^>]*?>)/', '$1 $2', $content );
		$content = \strip_tags( \nl2br( $content ) );

		if ( \preg_match("/[\x{4e00}-\x{9fa5}]+/u", $content) ) {
			$content = preg_replace( '/[\x80-\xff]{1,3}/', ' ', $content, -1, $n );
			$n += str_word_count( $content );

			return $n;
		} else {
			return \count( \preg_split( '/\s+/', $content ) );
		}
	}
}
