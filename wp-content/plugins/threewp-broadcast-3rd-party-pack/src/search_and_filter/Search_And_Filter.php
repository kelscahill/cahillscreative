<?php

namespace threewp_broadcast\premium_pack\search_and_filter;

/**
	@brief			Adds support for the <a href="https://searchandfilter.com/">Search And Filter</a> plugin.
	@plugin_group	3rd party compatability
	@since			2021-08-22 19:55:47
**/
class Search_And_Filter
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_filter( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_get_post_types' );
		new Custom_Layout_Shortcode();
	}

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2021-08-22 19:56:11
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		$new_post_id = $bcd->new_post( 'ID' );
		do_action('search_filter_update_post_cache', $new_post_id );

		$this->maybe_restore_layout( $bcd );
		$this->maybe_restore_search_filter_widget( $bcd );
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2022-12-12 19:38:24
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;
		$this->maybe_save_layout( $bcd );
		$this->maybe_save_search_filter_widget( $bcd );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Save
	// --------------------------------------------------------------------------------------------

	/**
		@brief		maybe_save_layout
		@since		2022-12-12 19:38:54
	**/
	public function maybe_save_layout( $bcd )
	{
		if ( $bcd->post->post_type != 'cl-layout' )
			return;

		$this->prepare_bcd( $bcd );

		$wp_upload_dir = wp_upload_dir();
		$bcd->search_and_filter->set( 'style_css', $wp_upload_dir[ 'basedir' ] . 'custom-layouts' . DIRECTORY_SEPARATOR . 'style.css' );
	}

	/**
		@brief		maybe_save_search_filter_widget
		@since		2023-05-15 20:55:10
	**/
	public function maybe_save_search_filter_widget( $bcd )
	{
		if ( $bcd->post->post_type != 'search-filter-widget' )
			return;

		$this->prepare_bcd( $bcd );

		$key = '_search-filter-results-url';
		$results_url = $bcd->custom_fields()->get_single( $key );
		if ( $results_url != '' )
		{
			$url = get_bloginfo( 'url' );
			$bcd->search_and_filter->set( 'url', $url );
			$bcd->search_and_filter->set( 'results_url', $results_url );
		}

	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Restore
	// --------------------------------------------------------------------------------------------

	/**
		@brief		maybe_restore_layout
		@since		2022-12-08 21:02:27
	**/
	public function maybe_restore_layout( $bcd )
	{
		if ( $bcd->post->post_type != 'cl-layout' )
			return;

		$key = 'custom-layouts-layout';
		$custom_layouts_layout = $bcd->custom_fields()->child_fields()->get( $key );
		$custom_layouts_layout = reset( $custom_layouts_layout );
		$custom_layouts_layout = maybe_unserialize( $custom_layouts_layout );
		if ( ! is_array( $custom_layouts_layout ) )
			return;

		foreach( [ 'search_filter_id', 'template_id' ] as $key_to_replace )
		{
			$key_id = $custom_layouts_layout[ $key_to_replace ];
			$key_id = intval( $key_id );
			if ( $key_id > 0 )
			{
				$this->debug( 'Finding new %s: %s', $key_to_replace, $key_id );
				$new_key_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $key_id, get_current_blog_id() );
				$this->debug( 'Found new %s: %s', $key_to_replace, $new_key_id );
				$custom_layouts_layout[ $key_to_replace ] = $new_key_id;

				if ( $key_to_replace == 'template_id' )
				{
					// Ask custom layouts to regenerate the CSS for this post.
					$this->debug( 'Regenerating CSS for template %s', $new_key_id );
					\Custom_Layouts\Core\CSS_Loader::save_css( [ $new_key_id ] );
				}
			}
		}

		$this->debug( '' );
		$wp_upload_dir = wp_upload_dir();
		$source = $bcd->search_and_filter->get( 'style_css' );
		$target = $wp_upload_dir[ 'basedir' ] . 'custom-layouts' . DIRECTORY_SEPARATOR . 'style.css';
		$this->debug( 'Copying %s to %s', $source, $target );
		copy( $source, $target );

		$bcd->custom_fields()
			->child_fields()
			->update_meta( $key, $custom_layouts_layout );
	}

	/**
		@brief		maybe_restore_search_filter_widget
		@since		2023-05-15 21:04:32
	**/
	public function maybe_restore_search_filter_widget( $bcd )
	{
		if ( $bcd->post->post_type != 'search-filter-widget' )
			return;

		$results_url = $bcd->search_and_filter->get( 'results_url' );
		if ( $results_url != '' )
		{
			$old_url = $bcd->search_and_filter->get( 'url' );
			$new_url = get_bloginfo( 'url' );
			$results_url = str_replace( $old_url, $new_url, $results_url );

			$key = '_search-filter-results-url';
			$bcd->custom_fields()
				->child_fields()
				->update_meta( $key, $results_url );
		}
	}

	/**
		@brief		Prepare the BCD.
		@since		2023-05-15 20:55:31
	**/
	public function prepare_bcd( $bcd )
	{
		if ( ! isset( $bcd->search_and_filter ) )
			$bcd->search_and_filter = ThreeWP_Broadcast()->collection();
	}

	public function threewp_broadcast_get_post_types( $action )
	{
		$action->add_types(
			'search-filter-widget',
		);
	}

}
