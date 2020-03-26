<?php
namespace threewp_broadcast\premium_pack\wpml;

/**
	@brief				Add support for <a href="http://wpml.org/">ICanLocalize's WPML translation plugin</a>.
	@plugin_group		3rd party compatability
**/
class WPML
extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		if ( ! $this->has_wpml() )
			return;

		$this->add_action( 'broadcast_hreflang_add_links' );
		$this->add_action( 'threewp_broadcast_menu' );

		$this->add_action( 'threewp_broadcast_broadcasting_after_switch_to_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_collect_post_type_taxonomies' );
		$this->add_action( 'threewp_broadcast_wp_insert_term' );

		$this->add_action( 'icl_make_duplicate', 10, 4 );
		$this->add_action( 'icl_pro_translation_completed' );
		$this->add_action( 'edit_form_advanced', 'wp_ml_translation_editor_form' );
		$this->add_action( 'wpml_after_sync_with_duplicates' );
		$this->add_action( 'wp_ml_translation_editor_form' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		broadcast_hreflang_add_links
		@since		2019-08-26 16:24:06
	**/
	public function broadcast_hreflang_add_links( $action )
	{
		if ( ! is_singular() )
			return;

		if ( isset( $this->__broadcast_hreflang_add_links ) )
			return;

		$this->__broadcast_hreflang_add_links = true;

		global $post;
		$id = $post->ID;
		$type = 'post_' . $post->post_type;
		$translations = wpml_get_content_translations( $type, $id );

		$hreflang = \threewp_broadcast\premium_pack\hreflang\Hreflang::instance();

		foreach( $translations as $post_id )
		{
			$subaction = $hreflang->new_add_links();
			$subaction->language_blogs = $hreflang->get_site_option( 'blog_languages' );
			$subaction->language_blogs = new \plainview\sdk_broadcast\collections\Collection( $subaction->language_blogs );
			$subaction->current_blog_id = get_current_blog_id();
			$subaction->current_url = static::current_url();
			$subaction->post_id = $post_id;
			$subaction->xdefault = $hreflang->get_site_option( 'xdefault_blog' );
			$subaction->execute();

			$action->links = array_merge( $action->links, $subaction->links );
		}

		unset( $this->__broadcast_hreflang_add_links );
	}

	/**
		@brief		After making a duplicate, broadcast the new language to the child posts.
		@since		2016-07-19 21:38:45
	**/
	public function icl_make_duplicate( $master_post_id, $lang, $post_array, $id )
	{
		// Find out whether this is a child post.
		$bcd = ThreeWP_Broadcast()->get_post_broadcast_data( get_current_blog_id(), $master_post_id );
		if ( $bcd->get_linked_parent() )
			return $this->debug( 'icl_make_duplicate called but this is a child post. Ignoring.' );

		$old_post = $_POST;
		$_POST = [];
		$this->debug( 'icl_make_duplicate.' );
		// Find out where the master post is broadcasted.
		$master_broadcast_data = ThreeWP_Broadcast()->get_parent_post_broadcast_data( $master_post_id );
		foreach( $master_broadcast_data->get_linked_children() as $blog_id => $post_id )
		{
			$this->debug( 'Broadcasting duplicate %s to blog %s', $id, $blog_id );
			ThreeWP_Broadcast()->api()
				->broadcast_children( $id, [ $blog_id ] );
		}
		$_POST = $old_post;
	}

	public function icl_pro_translation_completed( $new_post_id )
	{
		// Translation was not completed.
		if ( $new_post_id < 1 )
			return;

		// Is the original language broadcasted anywhere?
		$job_id = (int) $_GET['job_id'];
		$original_doc_bcd = $this->get_job_broadcast_data( $job_id );
		if ( ! $original_doc_bcd )
			return;

		// Retrieve the job data.
		$job = $this->get_translation_job( $job_id );
		$job_language = $job->language_code;

		// Broadcast this translation to all child blogs.
		$bcd = new \threewp_broadcast\broadcasting_data;
		$bcd->custom_fields = true;
		$bcd->taxonomies = true;
		$bcd->link = true;
		$bcd->parent_blog_id = get_current_blog_id();
		$bcd->parent_post_id = $new_post_id;
		$bcd->post = get_post( $new_post_id );
		$bcd->upload_dir = wp_upload_dir();

		// Broadcast to the blogs of the parent language.
		foreach( $original_doc_bcd->get_linked_children() as $blog_id => $post_id )
		{
			switch_to_blog( $blog_id );
			$languages = $this->sitepress()->get_active_languages( true );
			if ( isset( $languages[ $job_language ][ 'id' ] ) )
			{
				$blog = new \threewp_broadcast\broadcast_data\blog;
				$blog->id = $blog_id;
				$bcd->broadcast_to( $blog );
			}
			restore_current_blog();
		}

		ThreeWP_Broadcast()->broadcast_post( $bcd );
	}

	/**
		@brief		Settings.
		@since		2017-03-31 21:24:13
	**/
	public function settings()
	{
		$form = $this->form2();
		$r = '';

		$disable_language_check = $form->checkbox( 'disable_language_check' )
			->checked( $this->get_site_option( 'disable_language_check' ) )
			// Input title
			->description( __( 'Disable checking whether the language is active on the child blog. Use this setting if you have hidden languages that WPML reports as non-existing, and therefore Broadcast normally refusing to broadcast to the child.', 'threewp_broadcast' ) )
			// Input label
			->label( __( 'Disable language check', 'threewp_broadcast' ) );

		$save = $form->primary_button( 'save' )
			// Button
			->value( __( 'Save settings', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			$value = $disable_language_check->is_checked();
			$this->update_site_option( 'disable_language_check', $value );

			$r .= $this->info_message_box()->_( 'Settings saved!' );
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		// Page title for the settings page
		echo $this->wrap( $r, __( 'Settings', 'threewp_broadcast' ) );
	}

	/**
		@brief		Site options.
		@since		2017-03-31 21:27:12
	**/
	public function site_options()
	{
		return array_merge( [
			'disable_language_check' => false,		// Do not check whether the language exists on the child blog before broadcasting.
		], parent::site_options() );
	}

	/**
		@brief		Add options.
		@since		2017-03-31 21:21:50
	**/
	public function threewp_broadcast_menu( $action )
	{
		if ( ! is_super_admin() )
			return;

		$action->menu_page
			->submenu( 'threewp_broadcast_wpml' )
			->callback_this( 'settings' )
			->menu_title( 'WPML' )
			->page_title( 'WPML' );
	}

	/**
		@brief		Decide whether to Broadcast to this blog, depending on available language.
		@since		2014-10-07 09:23:31
	**/
	public function threewp_broadcast_broadcasting_after_switch_to_blog( $action )
	{
		if ( ! $this->action_check( $action ) )
			return;

		if ( $this->get_site_option( 'disable_language_check' ) )
			return $this->debug( 'Disabled language check.' );

		// Convenience.
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->wpml->language ) )
			return $this->debug( 'This post has no language. Broadcasting raw.' );

		$languages = $this->sitepress()->get_active_languages( true );
		$action->broadcast_here = isset( $languages[ $bcd->wpml->language ][ 'id' ] );
		if ( ! $action->broadcast_here )
			$this->debug( 'This blog does not have language %s enabled.', $bcd->wpml->language );

		// Force a switch to the correct language for the post in order to generate the terms in the correct lang.
		$this->debug( 'Switched language to %s', $bcd->wpml->language );
		$this->sitepress()->switch_lang( $bcd->wpml->language );

	}

	/**
		@brief		Handle translation of this post.
		@details

		Handles:
		- Marking the post as a language
		- Marking the post as a translation of a trid (language)

		@param		Broadcast_Data		The BCD object.
		@since		20140101
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		if ( ! $this->action_check( $action ) )
			return;

		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->wpml->language ) )
			return $this->debug( 'This post has no language. Finishing broadcasting raw.' );

		if ( ! $bcd->new_child_created )
		{
			$this->debug( 'No child post was created. Do nothing more, since the translations are already linked.' );
			return;
		}

		// Force the same slug on the child.
		$this->debug( 'Forcing rename of post slug to %s', $bcd->post->post_name );
		global $wpdb;
		$query = sprintf( "UPDATE `%s` SET `post_name` = '%s' WHERE `ID` = %s", $wpdb->posts, $bcd->post->post_name, $bcd->new_post( 'ID' ) );
		$this->query( $query );

		// Some convenience variables.
		$id = $bcd->new_post( 'ID' );
		$type = 'post_' . $bcd->new_post( 'post_type' );

		// What we want to do now is, if this is a translation of an existing language, find the existing languages's trid.
		// Loop through each child on this blog and query it for a language / trid.
		$trid = false;
		foreach( $bcd->wpml->broadcast_data as $lang => $broadcast_data )
		{
			// We should be looking for existing trids from other languages.
			if ( $lang == $bcd->wpml->language )
				continue;
			$child = $broadcast_data->get_linked_child_on_this_blog();
			if ( ! $child )
				continue;
			$trid = wpml_get_content_trid( $type, $child );
			$this->debug( 'The trid for %s %s, language %s, is %s.', $type, $child, $lang, $trid );
			if ( $trid > 0 )
				break;
		}

		// No trid found? Create a new one.
		if ( ! $trid )
		{
			$this->debug( 'No content trid found. Creating a new one.' );
			$trid = wpml_get_content_trid( $type, $id );
		}

		// Obsolete. No, this doesn't work, of course. Their own API doesn't work properly.
		// $result = wpml_add_translatable_content( $type, $id, $bcd->wpml->language, $trid );

		// Inform WPML that this content is available in this language.
		global $sitepress;
		$sitepress->set_element_language_details( $id, $type, $trid, $bcd->wpml->language );
		$this->debug( 'Set the element language details: %s %s %s %s', $id, $type, $trid, $bcd->wpml->language );

		icl_cache_clear();
	}

	/**
		@brief		Save info about the broadcast.
		@param		Broadcast_Data		The BCD object.
		@since		20140101
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		if ( ! $this->has_wpml() )
			return;

		$bcd = $action->broadcasting_data;

		// Conv
		$parent_blog_id = $bcd->parent_blog_id;

		$wpml = ThreeWP_Broadcast()->collection();

		// Retrieve the broadcast instance
		$broadcast = \threewp_broadcast\ThreeWP_Broadcast::instance();

		// Collect info about the translations, in order to link this language with the other languages on the child posts.
		$id = $bcd->post->ID;
		$type = 'post_' . $bcd->post->post_type;
		$wpml->translations = wpml_get_content_translations( $type, $id );

		// Is this content translateable?
		if ( ! is_array( $wpml->translations ) )
		{
			// No, then we do nothing.
			$this->debug( 'No content translations available.' );
			return;
		}

		if ( count( $wpml->translations ) < 1 )
		{
			$this->debug( 'This content is not translated. Nothing to do.' );
			return;
		}

		$this->debug( 'Translations: %s', $wpml->translations );

		// Calculate the language of this post.
		foreach( $wpml->translations as $lang => $post_id )
			if( $post_id == $id )
			{
				$wpml->language = $lang;
				break;
			}

		$wpml->trid = new \stdClass;
		$wpml->trid->$parent_blog_id = wpml_get_content_trid( $type, $id );
		$wpml->broadcast_data = new \stdClass;
		foreach( $wpml->translations as $lang => $element_id )
			$wpml->broadcast_data->$lang = $broadcast->get_post_broadcast_data( $parent_blog_id, $element_id );
		$this->debug( 'WPML data: %s', $wpml );
		$bcd->wpml = $wpml;
	}

	public function threewp_broadcast_collect_post_type_taxonomies( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! $this->has_wpml() )
			return;

		global $wpdb;

		$this->prepare_bcd( $bcd );
		// For convenience
		$td = $bcd->wpml->collection( 'taxonomy_data' );

		foreach( $bcd->parent_blog_taxonomies as $taxonomy => $data )
		{
			foreach( $data[ 'terms' ] as $term )
				$this->save_term_translations( $td, $term );
		}

		// Why are we saving this here? Because (1) the wp_insert_term action does not supply a bcd, and (2) we might not be broadcasting at all.
		$this->__wpml_data = $bcd->wpml;
	}
	/**
		@brief		Recursively save the term translations into this collection.
		@since		2017-08-09 16:05:57
	**/
	public function save_term_translations( $collection, $term )
	{
		$content_id = $term->term_id;
		$content_type = 'tax_' . $term->taxonomy;
		$taxonomy = $term->taxonomy;
		$translations = wpml_get_content_translations( $content_type, $content_id );
		foreach( $translations as $term_language => $translated_term_id )
		{
			$translated_term = get_term( $translated_term_id, $taxonomy );
			// Put the term in an index per slug and per language.
			$translations = $collection->collection( 'translations' );
			$translations->collection( $term->slug )
				->set( $term_language, $translated_term );

			if ( ! $translations->has( $translated_term->slug ) )
				$this->save_term_translations( $collection, $translated_term );
		}
	}

	/**
		@brief		Broadcast the language the user was editing, belonging to a group of duplicates.
		@since		2016-09-19 15:20:07
	**/
	public function wpml_after_sync_with_duplicates( $post_id )
	{
		$this->debug( 'wpml_after_sync_with_duplicates' );
		ThreeWP_Broadcast()->api()->update_children( $post_id );
	}

	/**
		@brief		Output info about the form.
		@since		20140101
	**/
	public function wp_ml_translation_editor_form()
	{
		if ( ! isset( $_GET[ 'job_id' ] ) )
			return;

		$job_id = intval( $_GET[ 'job_id' ] );
		$job = $this->get_translation_job( $job_id );
		$job_language = $job->language_code;

		$broadcast_data = $this->get_job_broadcast_data( $job_id );

		if ( $broadcast_data )
		{
			$blogs = [];
			foreach( $broadcast_data->get_linked_children() as $blog_id => $post_id )
			{
				switch_to_blog( $blog_id );

				$languages = $this->sitepress()->get_active_languages( true );
				if ( isset( $languages[ $job_language ][ 'id' ] ) )
					$blogs[ $blog_id ] = sprintf( '<a href="%s">%s</a>', get_bloginfo( 'url' ), get_bloginfo( 'name' ) );
				restore_current_blog();
			}
			echo ThreeWP_Broadcast()->p( __( 'This translation will be broadcast to: %s', 'threewp_broadcast' ), implode( ', ', $blogs ) );
		}
		else
			echo ThreeWP_Broadcast()->p( __( 'This translation will not be broadcasted: the original language post was not broadcasted.', 'threewp_broadcast' ) );
	}

	/**
		@brief		Link up and new terms with any existing translations.
		@since		2017-08-09 22:30:27
	**/
	public function threewp_broadcast_wp_insert_term( $action )
	{
		// We must have previously collected the wpml data.
		if ( ! isset( $this->__wpml_data ) )
			return;

		$td = $this->__wpml_data->collection( 'taxonomy_data' );

		// Do we know about this slug?
		$translations = $td->collection( 'translations' );

		// Do we know about this term?
		if ( ! $translations->has( $action->new_term->slug ) )
			return;

		global $wpdb;
		$original_language = $this->sitepress()->get_current_language();
		$term = $action->new_term;

		$term_translations = $translations->get( $action->new_term->slug );

		$child_term_ids = [];
		$term_language = '';
		// Go through all of the term translations and find their term IDs on this blog.
		foreach( $term_translations as $language => $parent_term )
		{
			if ( $parent_term->slug == $action->new_term->slug )
				$term_language = $language;
			$this->sitepress()->switch_lang( $language );
			$child_term = get_term_by( 'slug', $parent_term->slug, $parent_term->taxonomy );
			if ( ! $child_term )
				continue;
			$child_term_ids []= $child_term->term_id;
		}

		$this->debug( 'Found language as %s', $term_language );

		$content_type = 'tax_' . $term->taxonomy;

		// Return the trid for this content.
		$query = sprintf( "SELECT min(`trid` ) FROM `%sicl_translations` WHERE `element_type` = '%s' AND `element_id` IN (%s)",
			$wpdb->prefix,
			$content_type,
			implode( ',', $child_term_ids )
		);
		// The trid is automatically created upon wp_insert_term.
		$trid = $wpdb->get_var( $query );

		$this->debug( 'set_element_language_details %s %s %s %s', $action->new_term->term_id, $content_type, $trid, $term_language );
		$this->sitepress()->set_element_language_details( $action->new_term->term_id, $content_type, $trid, $term_language );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Check the action for WPML data
		@since		2014-10-07 09:33:05
	**/
	public function action_check( $action )
	{
		if ( ! $this->has_wpml() )
			return false;

		if ( ! isset( $action->broadcasting_data->wpml ) )
		{
			$this->debug( 'No WPML object.' );
			return false;
		}

		$active = get_site_option( 'active_sitewide_plugins', [], false );

		$plugin_name = 'sitepress-multilingual-cms/sitepress.php';

		// Check that WPML is active network wide.
		if ( ! in_array( $plugin_name, $active ) AND ! isset( $active[ $plugin_name ] )  )
		{
			$this->debug( 'WPML is not active network wide.' );
			// Not active? Is it, at least, active locally?
			$active = get_option( 'active_plugins' );
			if ( ! in_array( $plugin_name, $active ) )
			{
				$this->debug( 'WPML is not active locally, even.' );
				return false;
			}
		}

		return true;
	}

	public function get_job_broadcast_data( $job_id )
	{
		if ( $job_id < 1 )
			return false;
		global $iclTranslationManagement;
		$job = $iclTranslationManagement->get_translation_job( $job_id, false, true, 1 ); // don't include not-translatable and auto-assign
		$broadcast_data = ThreeWP_Broadcast()->broadcast_data_cache()->get_for( get_current_blog_id(), $job->original_doc_id );

		if ( ! $broadcast_data->has_linked_children() )
			return false;

		return $broadcast_data;
	}

	/**
		@brief		Return the translation job with this ID.
		@since		2014-08-22 17:55:45
	**/
	public function get_translation_job( $id )
	{
		global $iclTranslationManagement;
		return $iclTranslationManagement->get_translation_job( $id );
	}

	/**
		@brief		Check for the existence of WPML.
		@return		bool		True if WPML is alive and kicking. Else false.
		@since		20140101
	**/
	public function has_wpml()
	{
		$defined = defined( 'ICL_SITEPRESS_VERSION' );
		if ( $defined )
			require_once( ICL_PLUGIN_PATH . '/inc/wpml-api.php' );
		return $defined;
	}

	/**
		@brief		Check for the existence of WPML translation manager.
		@return		bool		True if WPML TM is alive and kicking. Else false.
		@since		20140101
	**/
	public function has_wpml_tm()
	{
		return defined( 'WPML_TM_VERSION' );
	}

	/**
		@brief		Prepare the WPML collection in the bcd.
		@since		2017-08-09 15:08:01
	**/
	public function prepare_bcd( $bcd )
	{
		if ( isset( $bcd->wpml ) )
			return;
		$bcd->wpml = ThreeWP_Broadcast()->collection();
	}

	/**
		@brief		Return the global sitepress object.
		@since		2014-08-22 17:51:22
	**/
	public function sitepress()
	{
		global $sitepress;
		return $sitepress;
	}
}
