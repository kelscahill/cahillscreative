<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://ezoic.com
 * @since      1.0.0
 *
 * @package    Ezoic_Emote
 * @subpackage Ezoic_Emote/public
 */
namespace Ezoic_Namespace;

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Ezoic_Emote
 * @subpackage Ezoic_Emote/public
 * @author     Ryan Outtrim <routtrim@ezoic.com>
 */
class Ezoic_Emote_Template {
	public function emote_comments_template( $comment_template ) {
		if ( !( is_singular() ) ) {
			return;
		}
		return dirname(__FILE__) . '/emote-comments.php';
	}
}
