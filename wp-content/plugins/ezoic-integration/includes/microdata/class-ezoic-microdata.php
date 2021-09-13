<?php

namespace Ezoic_Namespace;

class Ezoic_Microdata extends Ezoic_Feature {
    private $plugin_public;

    public function __construct() {
        $this->is_admin_enabled  = false;
        $this->is_public_enabled = false;

        if ( isset( $_SERVER['HTTP_X_EZOIC_MICRODATA'] ) && $_SERVER['HTTP_X_EZOIC_MICRODATA'] == 'true' ) {
            $this->is_public_enabled = true;
        }

        $this->plugin_public = new Ezoic_Microdata_Filters();
    }

    public function register_public_hooks( $loader ) {
        $loader->add_action( 'get_template_part',              $this->plugin_public, 'get_template_part', 10, 2 );
        $loader->add_filter( 'query_vars',                     $this->plugin_public, 'query_vars', 10, 2 );
        $loader->add_filter( 'ez_next_post_title',             $this->plugin_public, 'annotate_title', 10, 2 );
        $loader->add_filter( 'ez_next_previous_post_title',    $this->plugin_public, 'annotate_title', 10, 2 );
        $loader->add_filter( 'ez_title_secondary',             $this->plugin_public, 'annotate_title', 10, 2 );
        $loader->add_filter( 'ez_title_primary',               $this->plugin_public, 'annotate_title', 10, 2 );
        $loader->add_filter( 'ez_headline',                    $this->plugin_public, 'annotate_title', 10, 2 );
        $loader->add_filter( 'the_time',                       $this->plugin_public, 'time_target', 10, 2 );
        $loader->add_filter( 'the_content',                    $this->plugin_public, 'content_target', 10, 2 );
        $loader->add_filter( 'the_excerpt',                    $this->plugin_public, 'excerpt_target' );
        $loader->add_filter( 'the_tags',                       $this->plugin_public, 'tags_target' );
        $loader->add_filter( 'get_search_form',                $this->plugin_public, 'search_form_target' );
        $loader->add_filter( 'the_category',                   $this->plugin_public, 'category_target' );
        $loader->add_filter( 'get_the_author',                 $this->plugin_public, 'author_target', 5 );
        $loader->add_filter( 'get_the_archive_title',          $this->plugin_public, 'get_the_archive_title', 5, 2 );
        $loader->add_filter( 'ez_widget_output',               $this->plugin_public, 'widget', 5 );
        $loader->add_filter( 'wp_list_categories',             $this->plugin_public, 'category_list_item_target', 5 );
        $loader->add_filter( 'get_the_author_display_name',    $this->plugin_public, 'author_display_filter', 5 ); //      themes may esc html
        $loader->add_filter( 'the_author_posts_link',          $this->plugin_public, 'the_author_posts_link', 5 );
        $loader->add_filter( 'comments_number',                $this->plugin_public, 'comments_number', 5 );
        $loader->add_filter( 'get_comment_author_link',        $this->plugin_public, 'get_comment_author_link', 5 );
        $loader->add_filter( 'get_comment_author_url_link',    $this->plugin_public, 'get_comment_author_url_link', 5 );
        $loader->add_filter( 'comment_reply_link',             $this->plugin_public, 'comment_reply_link_filter', 5 );
        $loader->add_filter( 'get_avatar',                     $this->plugin_public, 'get_avatar', 5 );
        $loader->add_filter( 'post_thumbnail_html',            $this->plugin_public, 'post_thumbnail_html', 5 );
        $loader->add_filter( 'comments_popup_link_attributes', $this->plugin_public, 'comments_popup_link_attributes', 5 );
        $loader->add_filter( 'navigation_markup_template',     $this->plugin_public, 'navigation_markup_template', 10, 2 );
        $loader->add_filter( 'wp_kses_allowed_html',           $this->plugin_public, 'wp_kses_allowed_html', 5, 2 );
        $loader->add_filter( 'bloginfo',                       $this->plugin_public, 'bloginfo', 10, 2 );
        $loader->add_filter( 'get_sidebar',                    $this->plugin_public, 'register_widgets', 10 );

        // ob_flush executes here
        $loader->add_filter( 'ez_body_attributes',             $this->plugin_public, 'body_output', 5 );
        $loader->add_filter( 'ez_main_attributes',             $this->plugin_public, 'main_output', 5 );
        $loader->add_filter( 'ez_author_meta',                 $this->plugin_public, 'author_meta_output', 5 );
        $loader->add_filter( 'ez_author_attributes',           $this->plugin_public, 'author_output', 5 );
        $loader->add_filter( 'ez_pagination_links',            $this->plugin_public, 'modify_pagination_links', 5 );
        $loader->add_filter( 'ez_comment_replace',             $this->plugin_public, 'modify_ez_comments', 5 );
        $loader->add_filter( 'ez_head_tag',                    $this->plugin_public, 'modify_head_tag', 5 );
    }

    public function register_admin_hooks( $loader ) {

    }
}
