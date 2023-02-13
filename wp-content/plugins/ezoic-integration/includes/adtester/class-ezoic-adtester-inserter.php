<?php

namespace Ezoic_Namespace;

abstract class Ezoic_AdTester_Inserter {
	protected $config;
	protected $page_type;

	protected function __construct( $config ) {
		$this->config = $config;

		// Figure out page type:

		// When the front page of the site is displayed, regardless of whether
		// it is set to show posts or a static page.
		if (\is_front_page()) {
			$this->page_type = 'home';
		}

		// When a Category archive page is being displayed, or when the main
		// blog page is being displayed. If your home page has been set to a
		// Static Page instead, then this will only prove true on the page
		// which you set as the "Posts page" in Settings > Reading.
		elseif (\is_category() || \is_home()) {
			$this->page_type = 'category';
		}
		
		// When any single Post (or attachment, or custom Post Type) is being
		// displayed, or archive pages which include category, tag, author, date,
		// custom post type, and custom taxonomy based archives is being displayed.
		elseif (\is_single() || \is_archive()) {
			$this->page_type = 'post';
		}
		
		// When any Page is being displayed. This refers to WordPress Pages,
		// not any generic webpage from your blog
		elseif (\is_page()) {
			$this->page_type = 'page';
		}

	}
}
