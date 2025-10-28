<?php

namespace threewp_broadcast\premium_pack\yoast_seo;

/**
	@brief				Adds support for the <a href="https://wordpress.org/plugins/wordpress-seo/">Yoast SEO</a> plugin.
	@plugin_group		3rd party compatability
	@since				2016-08-10 21:22:10
**/
class Yoast_SEO
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		These custom fields must be handled separately if they are placed in the protect list.
		@since		2021-11-30 17:40:49
	**/
	public static $protectable_custom_fields =
	[
		'_yoast_wpseo_meta-robots-nofollow',
		'_yoast_wpseo_meta-robots-noindex',
	];

	public function _construct()
	{
		$this->add_action( 'broadcast_bulk_cloner_clone_these_tables' );
		$this->add_action( 'threewp_broadcast_broadcasting_after_update_post' );
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_collect_post_type_taxonomies' );
		$this->add_action( 'threewp_broadcast_menu' );
		$this->add_action( 'threewp_broadcast_wp_update_term' );
		$this->add_filter( 'wpseo_enable_notification_post_slug_change' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
	 * Do not clone the contents of the indexeable table.
	 *
	 * @since		2025-04-22 15:46:26
	 **/
	public function broadcast_bulk_cloner_clone_these_tables( $action )
	{
		global $wpdb;
		$table = $wpdb->prefix . 'yoast_indexable';
		$action->skip_table_contents []= $table;

		$this->debug( 'Telling Bulk Cloner to not clone the indexable table %s.', $table );
	}

	/**
		@brief		Plugin settings.
		@since		2016-10-30 15:35:24
	**/
	public function settings()
	{
		$form = $this->form2();
		$r = '';

		$modify_post_canonical = $form->checkbox( 'modify_post_canonical' )
			->checked( $this->get_site_option( 'modify_post_canonical' ) )
			->description( "Replace the parent site's URL in the canonical with the child site's URL" )
			->label( "Modify the post's canonical URL" );

		$keep_canonical_input = $form->checkbox( 'keep_canonical' )
			->checked( $this->get_site_option( 'keep_canonical' ) )
			// Input title
			->description( __( "When broadcasting, keep the existing taxonomy canonical URL on the child blog and prevent it from being overwritten with the value from the parent blog.", 'threewp_broadcast' ) )
			// Input label
			->label( __( 'Keep child taxonomy canonical URL', 'threewp_broadcast' ) );

		$save = $form->primary_button( 'save' )
			// Button
			->value( __( 'Save settings', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			$value = $modify_post_canonical->is_checked();
			$this->update_site_option( 'modify_post_canonical', $value );

			$value = $keep_canonical_input->is_checked();
			$this->update_site_option( 'keep_canonical', $value );

			$r .= $this->info_message_box()->_( __( 'Settings saved!', 'threewp_broadcast' ) );
		}


		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		// Page heading
		echo $this->wrap( $r, __( 'Yoast SEO settings', 'threewp_broadcast' ) );
	}

	/**
		@brief		Site options.
		@since		2016-10-30 15:37:22
	**/
	public function site_options()
	{
		return array_merge( [
			'keep_canonical' => false,
			'modify_post_canonical' => false,
		], parent::site_options() );
	}

	/**
		@brief		threewp_broadcast_broadcasting_after_update_post
		@since		2021-11-30 17:16:30
	**/
	public function threewp_broadcast_broadcasting_after_update_post( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->yoast_seo ) )
			return;

		foreach( static::$protectable_custom_fields as $key )
		{
			$bcd->yoast_seo->forget( $key );
			// Handling the nofollow custom field is very complicated...
			if( $bcd->custom_fields()->protectlist_has( $key ) )
			{
				// nofollow = custom field is 1
				// normal follow = no custom field (!)
				$has_nofollow = $bcd->custom_fields()->child_fields()->has( $key );
				$this->debug( 'Current %s status: %s', $key, intval( $has_nofollow ) );
				$bcd->yoast_seo->set( $key, $has_nofollow );
			}
		}
	}

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2018-02-14 11:31:47
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		// Modify the canonical?
		if ( $this->get_site_option( 'modify_post_canonical' ) )
		{
			$key = '_yoast_wpseo_canonical';

			$old_canonical = $bcd->custom_fields()->get_single( $key );

			$new_url = get_bloginfo( 'url' );
			$new_canonical = str_replace(
				$bcd->bloginfo_url,
				$new_url,
				$old_canonical,
			);

			$bcd->custom_fields()
				->child_fields()
				->update_meta( $key, $new_canonical );
		}

		$this->clear_yoast_indexable( 'post', $bcd->new_post( 'ID' ) );

		foreach( $bcd->parent_post_taxonomies as $taxonomy => $ignore )
		{
			// Handle the primary category.
			$key = '_yoast_wpseo_primary_' . $taxonomy;
			if ( $bcd->custom_fields()->protectlist_has( $key ) )
				continue;
			$old_term_id = $bcd->custom_fields()->get_single( $key );
			if ( $old_term_id > 0 )
			{
				// Get the equivalent category here.
				$new_term_id = $bcd->terms()->get( $old_term_id );
				$this->debug( 'Setting new primary %s: %s', $taxonomy, $new_term_id );
				$bcd->custom_fields()
					->child_fields()
					->update_meta( $key, $new_term_id );
			}
		}

		$key = '_yoast_wpseo_opengraph-image-id';
		if ( ! $bcd->custom_fields()->protectlist_has( $key ) )
		{
			$old_image_id = $bcd->custom_fields()->get_single( $key );
			if ( $old_image_id > 0 )
			{
				$new_image_id = $bcd->copied_attachments()->get( $old_image_id );
				$this->debug( 'Replacing %s with %s', $key, $new_image_id );
				$bcd->custom_fields()
					->child_fields()
					->update_meta( $key, $new_image_id );

				$key = '_yoast_wpseo_opengraph-image';
				$new_url = wp_get_attachment_url( $new_image_id );
				$this->debug( 'Replacing %s with %s', $key, $new_url );
				$bcd->custom_fields()
					->child_fields()
					->update_meta( $key, $new_url );
			}
		}

		if ( isset( $bcd->yoast_seo ) )
			foreach( static::$protectable_custom_fields as $key )
			{
				if ( $bcd->yoast_seo->has( $key ) )
				{
					$value = $bcd->yoast_seo->get( $key );
					$this->debug( 'Resetting %s to %s', $key, intval( $value ) );
					if ( $value )
						$bcd->custom_fields()
							->child_fields()
							->update_meta( $key, true );
					else
						$bcd->custom_fields()
							->child_fields()
							->delete_meta( $key );
				}
			}
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2017-12-12 11:53:22
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$this->maybe_disable_link_watcher();

		$bcd = $action->broadcasting_data;

		$bcd->bloginfo_url = get_bloginfo( 'url' );

		$key = '_yoast_wpseo_opengraph-image-id';
		$image_id = $bcd->custom_fields()->get_single( $key );
		if ( $image_id > 0 )
		{
			$this->debug( 'Found %s image: %s', $key, $image_id );
			$bcd->try_add_attachment( $image_id );
		}
	}

	/**
		@brief		Looks like we're going to sync taxonomies. Take note of all yoast meta, if any.
		@since		2016-07-19 20:55:17
	**/
	public function threewp_broadcast_collect_post_type_taxonomies( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->yoast_seo ) )
			$bcd->yoast_seo = ThreeWP_Broadcast()->collection();

		$meta = get_option( 'wpseo_taxonomy_meta', true );
		$meta = maybe_unserialize( $meta );

		foreach( $bcd->parent_post_taxonomies as $parent_post_taxonomy => $terms )
		{
			$this->debug( 'Collecting Yoast SEO meta fields for %s', $parent_post_taxonomy );
			if ( ! isset( $meta[ $parent_post_taxonomy ] ) )
				continue;
			// Get all of the fields for all terms
			foreach( $terms as $term )
			{
				$term_id = $term->term_id;		// Conv.
				if ( ! isset( $meta[ $parent_post_taxonomy ][ $term_id ] ) )
					continue;

				$the_meta = $meta[ $parent_post_taxonomy ][ $term_id ];

				// Trim blacklisted term meta.
				foreach( $the_meta as $meta_key => $meta_value )
					if ( $bcd->taxonomies()->blacklist_has( $parent_post_taxonomy, $term->slug, $meta_key ) )
					{
						$this->debug( '%s / %s / %s found in the term meta blacklist. Removing.', $parent_post_taxonomy, $term->slug, $meta_key );
						unset( $the_meta[ $meta_key ] );
					}

				$bcd->yoast_seo->set( $term_id, $the_meta );
			}
		}
	}

	/**
		@brief		Add ourselves into the menu.
		@since		2016-01-26 14:00:24
	**/
	public function threewp_broadcast_menu( $action )
	{
		if ( ! is_super_admin() )
			return;

		$action->menu_page
			->submenu( 'threewp_broadcast_yoast_seo' )
			->callback_this( 'settings' )
			->menu_title( 'Yoast SEO' )
			->page_title( 'Yoast SEO' );
	}

	/**
		@brief		Updating the term.
		@since		2016-07-22 16:36:28
	**/
	public function threewp_broadcast_wp_update_term( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->yoast_seo ) )
			return;

		if ( ! $bcd->yoast_seo->has( $action->old_term->term_id ) )
			return;

		$meta = get_option( 'wpseo_taxonomy_meta', true );
		$meta = maybe_unserialize( $meta );

		$new_meta = $bcd->yoast_seo->get( $action->old_term->term_id );

		if ( ! isset( $meta[ $action->taxonomy ] ) )
			$meta[ $action->taxonomy ] = [];

		if ( ! isset( $meta[ $action->taxonomy ][ $action->new_term->term_id ] ) )
			$meta[ $action->taxonomy ][ $action->new_term->term_id ] = $new_meta;

		$old_meta = $meta[ $action->taxonomy ][ $action->new_term->term_id ];

		if ( $this->get_site_option( 'keep_canonical' ) )
		{
			$old_canonical = '';
			if ( isset( $old_meta[ 'wpseo_canonical' ] ) )
				$old_canonical = $old_meta[ 'wpseo_canonical' ];

			$this->debug( 'Saving old canonical URL %s', $old_canonical );
			$new_meta[ 'wpseo_canonical' ] = $old_canonical;
		}

		foreach( $new_meta as $meta_key => $meta_value )
		{
			// Should this meta be protected?
			if ( $bcd->taxonomies()->protectlist_has( $action->taxonomy, $action->new_term->slug, $meta_key ) )
				if ( isset( $meta[ $action->taxonomy ][ $action->new_term->term_id ][ $meta_key ] ) )
					$meta_value = $meta[ $action->taxonomy ][ $action->new_term->term_id ][ $meta_key ];
			$meta[ $action->taxonomy ][ $action->new_term->term_id ][ $meta_key ] = $meta_value;
		}

		$this->debug( 'Saving new meta for term %s: %s', $action->old_term->slug, $meta );
		update_option( 'wpseo_taxonomy_meta', $meta );
		$this->clear_yoast_indexable( 'term', $action->new_term->term_id );
	}

	// -------------------------------------------------------------------------------------------------------------------
	// --- MISC
	// -------------------------------------------------------------------------------------------------------------------

	/**
		@brief		Disable the Link Watcher, if possible.
		@since		2020-05-07 19:17:05
	**/
	public function maybe_disable_link_watcher()
	{
		// Remove Canonical Link Added By Yoast WordPress SEO Plugin
		if ( ! class_exists( '\\WPSEO_Link_Watcher' ) )
			return;
		// Go through everything hooked into save_post to find the link watcher.
		global $wp_filter;
		if ( ! isset( $wp_filter[ 'save_post' ] ) )
			return;
		$filters = $wp_filter[ 'save_post' ];
		foreach( $filters->callbacks as $callbacks )
			foreach( $callbacks as $callback )
			{
				$function = $callback[ 'function' ];
				if ( ! is_array( $function ) )
					continue;
				$class = $function[ 0 ];
				if ( ! is_object( $class ) )
					continue;
				$classname = get_class( $class );
				if ( $classname != 'WPSEO_Link_Watcher' )
					continue;
				// We've found it! Nuke it from orbit.
				$this->debug( 'Disabling %s', $classname );
				remove_action( 'save_post', [ $class, 'save_post' ], 10, 2 );
			}
	}

	/**
		@brief		Set up the bcd for ourselves.
		@since		2020-12-16 20:48:37
	**/
	public function prepare_bcd( $bcd )
	{
		if ( isset( $bcd->yoast_seo ) )
			return;
		$bcd->yoast_seo = ThreeWP_Broadcast()->collection();
	}

	/**
		@brief		Clear the yoast indexable of this object.
		@since		2020-12-16 20:52:13
	**/
	public function clear_yoast_indexable( $type, $post_id )
	{
		global $wpdb;
		$table = $wpdb->prefix . 'yoast_indexable';
		$this->debug( 'Clearing yoast_indexable' );
		$wpdb->delete( $table, [
			'object_type' => $type,
			'object_id' => $post_id,
		] );
	}

	/**
		@brief		Disable the warning for changed slugs, since slugs will change during broadcasting.
		@since		2017-06-27 10:35:33
	**/
	public function wpseo_enable_notification_post_slug_change( $show )
	{
		return false;
	}

}
