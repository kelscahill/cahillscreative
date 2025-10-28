<?php

namespace threewp_broadcast\premium_pack\elementor;

use Exception;

/**
	@brief			Adds support for the <a href="https://wordpress.org/plugins/elementor/">Elementor Page Builder plugin</a>.
	@plugin_group	3rd party compatability
	@since			2017-04-28 23:16:00
**/
class Elementor
	extends \threewp_broadcast\premium_pack\base
{
	use \threewp_broadcast\premium_pack\classes\broadcast_data_trait;
	use \threewp_broadcast\premium_pack\classes\parse_and_preparse_content_trait;

	/**
		@brief		These are where images are usually found.
		@since		2022-03-07 18:56:36
	**/
	public static $image_settings = [
		'_background_image',
		'background_image',
		'background_image_mobile',
		'bg_image',
		'image',
	];

	/**
		* @brief
		* @since		2025-06-25 12:49:10
	**/
	public static $loop_term_ids = [
		'post_query_exclude_term_ids',
		'post_query_include_term_ids',
		'product_query_exclude_term_ids',
		'product_query_include_term_ids',
	];

	/**
		@brief		parseable_settings
		@since		2018-12-06 12:30:42
	**/
	public static $parseable_settings = [
		'link',
		'shortcode',
		'text',
		'url',
	];

	/**
		* @brief		Which generic setting contain post ids in an array.
		* @since		2025-06-25 14:50:22
	**/
	public static $post_ids_settings = [
		'ae_post_ids',
		'post_query_posts_ids',
		'page_filter',
	];

	public function _construct()
	{
		$this->add_action( 'elementor/editor/after_save', 'elementor_editor_after_save' );
		$this->add_action( 'broadcast_elementor_parse_element', 5);		// We go first.
		$this->add_action( 'broadcast_elementor_preparse_element', 5);		// We go first.
		$this->add_action( 'broadcast_php_code_load_wizards' );
		$this->add_action( 'threewp_broadcast_broadcasting_after_switch_to_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_collect_post_type_taxonomies' );
		$this->add_action( 'threewp_broadcast_get_post_types' );
		$this->add_action( 'threewp_broadcast_trash_untrash_delete_post', 'threewp_broadcast_trash_untrash_delete_post_begin', 5 );
		$this->add_action( 'threewp_broadcast_trash_untrash_delete_post', 'threewp_broadcast_trash_untrash_delete_post_end', 1000 );
		$this->add_action( 'threewp_broadcast_wp_update_term' );
		new Elementor_Tag();
		new Elementor_Template_Shortcode();
		new Unlimited_Elements();
	}

	/**
		@brief		elementor_editor_after_save
		@since		2018-12-26 13:04:37
	**/
	public function elementor_editor_after_save( $post_id )
	{
		if ( defined( 'BROADCAST_ELEMENTOR_NO_UPDATE_ON_SAVE' ) )
			return;
		$this->debug( 'elementor_editor_after_save: %s', $post_id );
		ThreeWP_Broadcast()->api()->update_children( $post_id );
	}

	/**
		@brief		Add the wizard for JetEngine.
		@since		2020-05-09 21:42:58
	**/
	public function broadcast_php_code_load_wizards( $action )
	{
		$wizard = $action->new_wizard();
		$wizard->set( 'group', '3rdparty' );
		$wizard->set( 'id', 'elementor_jetengine_copy_tables' );
		$wizard->set( 'label', __( "Elementor: Copy Jet Engine custom post table and taxonomies database tables", 'threewp_broadcast' ) );
		$wizard->load_code_from_disk( __DIR__ . '/php_code/' );
		$action->add_wizard( $wizard );
	}

	/**
		@brief		threewp_broadcast_broadcasting_after_switch_to_blog
		@since		2022-08-27 08:34:17
	**/
	public function threewp_broadcast_broadcasting_after_switch_to_blog( $action )
	{
		$bcd = $action->broadcasting_data;
		$this->prepare_bcd( $bcd );

		// Save the conditions otherwise they get overwritten.
		$key = 'elementor_pro_theme_builder_conditions';
		$bcd->elementor->forget( $key );

		$tbc = get_option( $key );
		if ( ! $tbc )
			return $this->debug( 'No %s here.', $key );

		$this->debug( 'Saving %s: %s', $key, $tbc );
		$bcd->elementor->set( $key, $tbc );
	}

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2017-04-28 23:39:15
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		if ( ! $this->has_requirement() )
			return;

		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->elementor ) )
			return;

		// Save the conditions otherwise they get overwritten.
		$key = 'elementor_pro_theme_builder_conditions';
		$tbc = $bcd->elementor->get( $key );
		if ( $tbc )
		{
			$this->debug( 'Restoring %s: %s', $key, $tbc );
			update_option( $key, $tbc );
		}

		$this->maybe_restore_conditions( $bcd );
		$this->maybe_restore_css( $bcd );
		$this->maybe_restore_data( $bcd );
		$this->maybe_restore_page_settings( $bcd );

		if ( class_exists( '\\ElementorPro\\Plugin' ) )
		{
			$tb = \ElementorPro\Modules\ThemeBuilder\Module::instance();
			$this->debug( 'Regenerating conditions cache.' );
			$tb->get_conditions_manager()->get_cache()->regenerate();
		}

		if ( $bcd->post->post_type == 'elementor_library' )
			if ( $bcd->post->post_name == 'default-kit' )
			{
				$this->debug( 'Setting default-kit option: %s', $bcd->new_post( 'ID' ) );
				update_option( 'elementor_active_kit', $bcd->new_post( 'ID' ) );
			}
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2017-04-28 23:39:00
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! $this->has_requirement() )
			return;

		$this->maybe_save_css( $bcd );
		$this->maybe_save_data( $bcd );
		$this->maybe_save_page_settings( $bcd );
		$this->maybe_save_conditions( $bcd );
	}

	/**
		@brief		Save the taxonomy thumbnail, as per the Elementor Powerpack.
		@since		2021-10-07 21:40:30
	**/
	public function threewp_broadcast_collect_post_type_taxonomies( $action )
	{
		$bcd = $action->broadcasting_data;

		$this->prepare_bcd( $bcd );

		foreach( $bcd->parent_blog_taxonomies as $parent_post_taxonomy => $taxonomy_data )
		{
			$terms = $taxonomy_data[ 'terms' ];

			$this->debug( 'Collecting termmeta for %s', $parent_post_taxonomy );
			// Get all of the fields for all terms
			foreach( $terms as $term )
			{
				$term_id = $term->term_id;

				// Save the image.
				$key = 'taxonomy_thumbnail_id';
				$image_id = get_term_meta( $term_id, $key, true );

				if ( $image_id > 0 )
				{
				  $this->debug( 'Found %s %s for term %s (%s)',
				  	  $key,
					  $image_id,
					  $term->slug,
					  $term_id
				  );

				  $bcd->try_add_attachment( $image_id );
				  $bcd->elementor->collection( 'taxonomy_thumbnail_id' )->set( $term_id, $image_id );
				}
			}
		}
	}

	/**
		@brief		Add post types.
		@since		2015-10-02 12:47:49
	**/
	public function threewp_broadcast_get_post_types( $action )
	{
		$action->add_type( 'elementor_library' );
	}

	/**
		@brief		Do we have to store the template conditions while deleting posts?
		@since		2022-09-13 00:29:20
	**/
	public function threewp_broadcast_trash_untrash_delete_post_begin( $action )
	{
		switch_to_blog( $action->child_blog_id );

		$key = 'elementor_pro_theme_builder_conditions';
		$value = get_option( $key );
		if ( ! $value )
			$this->debug( 'No %s here.', $key );
		else
		{
			$busy_key = $key . '_' . $action->child_blog_id;
			$this->$busy_key = $value;
			$this->debug( 'While deleting on blog %s, saving %s: %s',
				$action->child_blog_id,
				$key,
				json_encode( $value )
			);
		}

		restore_current_blog();
	}

	/**
		@brief		Do we restore the template conditions?
		@since		2022-09-13 00:29:20
	**/
	public function threewp_broadcast_trash_untrash_delete_post_end( $action )
	{
		switch_to_blog( $action->child_blog_id );

		$key = 'elementor_pro_theme_builder_conditions';
		$busy_key = $key . '_' . $action->child_blog_id;

		if ( isset( $this->$busy_key ) )
		{
			$value = $this->$busy_key;
			$this->debug( 'While deleting on blog %s, restoring %s: %s',
				$action->child_blog_id,
				$key,
				json_encode( $value ) );
			update_option( $key, $value );
		}

		restore_current_blog();
	}

	/**
		@brief		Restore the image.
		@since		2021-10-07 21:43:27
	**/
	public function threewp_broadcast_wp_update_term( $action )
	{
		$bcd = $action->broadcasting_data;

		$old_term_id = $action->old_term->term_id;
		$new_term_id = $action->new_term->term_id;

		foreach( [
			'thegem_page_data',
			'thegem_product_archive_page_data',
			'thegem_blog_archive_page_data',
			] as $custom_field )
		{
			$this->debug( 'Looking at custom field %s', $custom_field );
			$meta_value = get_term_meta( $new_term_id, $custom_field );
			$meta_value = reset( $meta_value );
			$meta_value = maybe_unserialize( $meta_value );

			// Check that this key is an array.
			if ( ! is_array( $meta_value ) )
				continue;

			$modified = false;
			foreach( $meta_value as $key => $value )
			{
				$this->debug( 'Looking at %s', $key );
				// Handle arrays
				if ( is_array( $value ) )
				{
					foreach( $value as $subkey => $subvalue )
					{
						$this->debug( 'Looking at subkey %s', $subkey );
						if ( ! str_ends_with( $subkey, '_template' ) )
							continue;
						$intval_subvalue = intval( $subvalue );
						if ( $intval_subvalue < 1 )
							continue;

						$this->debug( 'Looking to translate %s in %s from %s.',
							$subkey,
							$custom_field,
							$intval_subvalue,
						);

						try
						{
							$new_intval_subvalue = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $intval_subvalue, get_current_blog_id() );
						}
						catch ( Exception $e )
						{
							$new_intval_subvalue = false;
						}

						// Only replace the value if the value IS replaced.
						if ( $new_intval_subvalue < 1 )
							continue;

						$this->debug( 'New %s is %s', $subkey, $new_intval_subvalue );

						$meta_value[ $key ][ $subkey ] = $new_intval_subvalue;
						$modified = true;
					}
				}

				// We are looking for all keys that end in _template.
				if ( ! str_ends_with( $key, '_template' ) )
					continue;

				// The value must have a value.
				$template_value = intval( $value );
				if ( $template_value < 1 )
					continue;

				$this->debug( 'Looking to translate %s in %s from %s.',
					$key,
					$custom_field,
					$template_value,
				);

				try
				{
					$new_template_value = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $template_value, get_current_blog_id() );
				}
				catch ( Exception $e )
				{
					$new_template_value = false;
				}

				// Only replace the value if the value IS replaced.
				if ( $new_template_value < 1 )
					continue;

				$meta_value[ $key ] = $new_template_value;
				$modified = true;
			}

			if ( $modified )
			{
				$this->debug( 'Updating %s for %s with %s',
					$custom_field,
					$new_term_id,
					$meta_value,
				);
				update_term_meta( $new_term_id, $custom_field, $meta_value );
			}
		}

		if ( ! isset( $bcd->elementor ) )
			return;

		$old_image_id = $bcd->elementor->collection( 'taxonomy_thumbnail_id' )->get( $old_term_id );

		if ( ! $old_image_id )
			return;

		ThreeWP_Broadcast()->copy_attachments_to_child( $bcd );

		$key = 'taxonomy_thumbnail_id';
		$new_image_id = $bcd->copied_attachments()->get( $old_image_id );

		if ( $new_image_id > 0 )
		{
			$this->debug( 'Setting new %s %s for term %s.', $key, $new_image_id, $new_term_id );
			update_term_meta( $new_term_id, $key, $new_image_id );
		}
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- SAVE
	// --------------------------------------------------------------------------------------------

	/**
		@brief		maybe_save_conditions
		@since		2021-03-09 18:24:02
	**/
	public function maybe_save_conditions( $bcd )
	{
		$meta_key = '_elementor_conditions';
		$conditions = $bcd->custom_fields()->get_single( $meta_key );
		if ( ! $conditions )
			return;

		$this->prepare_bcd( $bcd );

		$conditions = maybe_unserialize( $conditions );
		$bcd->elementor->set( 'conditions', $conditions );

		foreach( $conditions as $condition )
		{
			$parts = explode( '/', $condition );
			if ( ! isset( $parts[ 2 ] ) )
				continue;

			$this->debug( 'Condition: %s', $condition );
			// Taxonomies
			if ( strpos( $parts[ 2 ], 'in_' ) === 0 )
			{
				$taxonomy = $parts[ 2 ];
				$taxonomy = str_replace( 'in_', '', $taxonomy );
				$taxonomy = str_replace( '_children', '', $taxonomy );
				$bcd->taxonomies()
					->also_sync( null, $taxonomy )
					->use_term( $parts[ 3 ] );
			}

			if ( $parts[ 1 ] == 'woocommerce' )
			{
				$nice_taxonomy = str_replace( 'in_', '', $parts[ 2 ] );
				$bcd->taxonomies()
					->also_sync( null, $nice_taxonomy )
					->use_term( $parts[ 3 ] );
			}

			if ( $parts[ 2 ] == 'product_cat' )
			{
				$bcd->taxonomies()
					->also_sync( null, 'product_cat' )
					->use_term( $parts[ 3 ] );
			}
		}
	}

	/**
		@brief		Save the CSS file, if any.
		@since		2023-02-02 16:23:17
	**/
	public function maybe_save_css( $bcd )
	{
		if ( $bcd->post->post_name == 'default-kit' )
			$filename = $this->get_post_css_file( null );
		else
			$filename = $this->get_post_css_file( $bcd->post->ID );
		$bcd->elementor->set( 'old_post_css_filename', $filename );
		$this->debug( 'Saved old Elementor CSS filename %s', $filename );
	}

	/**
		@brief		Maybe save the elementor data.
		@since		2021-03-09 18:35:33
	**/
	public function maybe_save_data( $bcd )
	{
		$ed = $bcd->custom_fields()->get_single( '_elementor_data' );
		if ( ! $ed )
			return;

		$ed = json_decode( $ed );
		if ( ! $ed )
			return $this->debug( 'Warning! Elementor data is invalid!' );

		$this->prepare_bcd( $bcd );

		$this->debug( 'Elementor data found: %s', $ed );

		// Remember things.
		foreach( $ed as $index => $section )
			$this->preparse_element( $bcd, $section );
	}

	/**
		@brief		Maybe save the page settings.
		@details	This is for the default-kit page.
		@since		2023-01-27 19:02:23
	**/
	public function maybe_save_page_settings( $bcd )
	{
		$ps = $bcd->custom_fields()->get_single( '_elementor_page_settings' );
		if ( ! $ps )
			return;

		$ps = maybe_unserialize( $ps );
		if ( ! $ps )
			return $this->debug( 'Warning! Elementor page settings are invalid!' );

		if ( ! isset( $ps[ 'site_logo' ] ) )
			return;

		$this->prepare_bcd( $bcd );

		$bcd->elementor->set( 'page_settings', $ps );

		$image_id = $ps[ 'site_logo' ][ 'id' ];
		$this->debug( 'Trying to add site_logo image %s', $image_id );
		if ( $bcd->try_add_attachment( $image_id ) )
			$bcd->elementor->set( 'site_logo_id', $image_id );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- RESTORE
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Maybe restore the conditions.
		@since		2021-03-09 18:52:55
	**/
	public function maybe_restore_conditions( $bcd )
	{
		$meta_key = '_elementor_conditions';
		if ( $bcd->custom_fields()->protectlist_has( $meta_key ) )
			return $this->debug(  '%s is protected. Not restoring.' );

		$conditions = $bcd->elementor->get( 'conditions' );

		if ( ! $conditions )
			return;

		$new_conditions = [];

		foreach( $conditions as $index => $condition )
		{
			$parts = explode( '/', $condition );
			if ( ! isset( $parts[ 2 ] ) )
			{
				$new_conditions [ $index ] = $condition;
				continue;
			}

			$this->debug( 'Condition: %s', $condition );

			// Taxonomies
			if ( strpos( $parts[ 2 ], 'in_' ) === 0 )
			{
				$old_term_id = $parts[ 3 ];
				$new_term_id = $bcd->terms()->get( $old_term_id );
				$parts[ 3 ] = $new_term_id;
				$this->debug( 'Replacing term %s with %s', $old_term_id, $new_term_id );
			}

			// Posts
			if ( in_array( $parts[ 2 ], [
				'page',
				'post',
				'any_child_of',
			] ) )
			{
				$old_post_id = $parts[ 3 ];
				$new_post_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $old_post_id, get_current_blog_id() );
				$parts[ 3 ] = $new_post_id;
				$this->debug( 'Replacing post %s with %s', $old_post_id, $new_post_id );
			}

			// This is handled by in_, above.
			/**
			if ( $parts[ 1 ] == 'woocommerce' )
			{
				$old_term_id = $parts[ 3 ];
				$new_term_id = $bcd->terms()->get( $old_term_id );
				$parts[ 3 ] = $new_term_id;
				$this->debug( 'Replacing Woocommerce term %s with %s', $old_term_id, $new_term_id );
			}
			**/

			if ( $parts[ 2 ] == 'product_cat' )
			{
				$old_term_id = $parts[ 3 ];
				$new_term_id = $bcd->terms()->get( $old_term_id );
				$parts[ 3 ] = $new_term_id;
				$this->debug( 'Replacing product_cat %s with %s', $old_term_id, $new_term_id );
			}

			$condition = implode( '/', $parts );
			$new_conditions [ $index ] = $condition;
		}

		$this->debug( 'New conditions: %s', $new_conditions );
		$bcd->custom_fields()
			->child_fields()
			->update_meta( $meta_key, $new_conditions );
	}

	/**
		@brief		Maybe restore the CSS file.
		@since		2023-02-02 16:25:53
	**/
	public function maybe_restore_css( $bcd )
	{
		// Copy the css file.
		if ( ! isset( $bcd->elementor ) )
			return;
		$old_filename = $bcd->elementor->get( 'old_post_css_filename' );

		if ( $bcd->post->post_name == 'default-kit' )
			$new_filename = $this->get_post_css_file( null );
		else
			$new_filename = $this->get_post_css_file( $bcd->new_post( 'ID' ) );

		// Replace the post ID in the file.
		if ( is_readable( $old_filename ) )
		{
			$css_file = file_get_contents( $old_filename );
			$css_file = str_replace( 'elementor-' . $bcd->post->ID, 'elementor-' . $bcd->new_post( 'ID' ), $css_file );

			file_put_contents( $new_filename, $css_file );

			$this->debug( 'Copied Elementor CSS file %s to %s', $old_filename, $new_filename );
		}
		else
			$this->debug( 'Elementor CSS file %s is not readable.', $old_filename );

		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}

	/**
		@brief		maybe_restore_data
		@since		2021-03-09 18:37:05
	**/
	public function maybe_restore_data( $bcd )
	{
		$meta_key = '_elementor_data';

		$ed = $bcd->custom_fields()->get_single( '_elementor_data' );

		if ( ! $ed )
			return;

		$ed = json_decode( $ed );

		if ( ! $ed )
			return;

		foreach( $ed as $index => $element )
			$ed[ $index ] = $this->parse_element( $bcd, $element );

		$ed = json_encode( $ed );

		$this->debug( 'Updating elementor data: <pre>%s</pre>', htmlspecialchars( $ed ) );
		$bcd->custom_fields()
			->child_fields()
			->update_meta_json( $meta_key, $ed );
	}

	/**
		@brief		Restore the page settings.
		@since		2023-01-27 19:08:32
	**/
	public function maybe_restore_page_settings( $bcd )
	{
		$site_logo_id = $bcd->elementor->get( 'site_logo_id' );
		if ( ! $site_logo_id )
			return;

		$ps = $bcd->elementor->get( 'page_settings' );

		$new_image_id = $bcd->copied_attachments()->get( $site_logo_id );

		if ( $new_image_id > 0 )
		{
			$this->debug( 'Setting new site_logo_id %s from %s.', $new_image_id, $site_logo_id );
			$ps[ 'site_logo' ][ 'id' ] = $new_image_id;
			$new_url = ThreeWP_Broadcast()->update_attachment_ids( $bcd, $ps[ 'site_logo' ][ 'url' ] );
			$ps[ 'site_logo' ][ 'url' ] = $new_url;
		}

		$bcd->custom_fields()
			->child_fields()
			->update_meta( '_elementor_page_settings', $ps );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc functions
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Returns the post's Elementor CSS filename.
		@since		2017-08-09 17:21:09
	**/
	public function get_post_css_file( $post_id = null )
	{
		$wp_upload_dir = wp_upload_dir();
		$path = sprintf( '%s/elementor/css', $wp_upload_dir['basedir'] );

		if ( ! is_dir( $path ) )
		{
			$this->debug( 'Creating directory %s', $path );
			mkdir( $path, true );
		}

		if ( $post_id === null )
			$new_filename = sprintf( '%s/global.css', $path, $post_id );
		else
			$new_filename = sprintf( '%s/post-%d.css', $path, $post_id );

		return $new_filename;
	}

	/**
		@brief		Check for the plugin.
		@since		2024-03-19 18:40:22
	**/
	public function has_requirement()
	{
		return class_exists( '\\Elementor\\Plugin' );
	}

	/**
	 * Preparse this element.
	 *
	 * @since		2025-05-09 19:53:50
	 **/
	public function broadcast_elementor_preparse_element( $action )
	{
		if ( $action->is_finished() )
			return;

		// Extract our data from the action.
		$bcd = $action->broadcasting_data;
		$element = $action->element;

		if ( isset( $element->settings ) )
		{
			foreach( static::$parseable_settings as $type )
			{
				if ( ! isset( $element->settings->$type ) )
					continue;
				if ( is_object( $element->settings->$type ) )
				{
					foreach( (array) $element->settings->$type as $key => $value )
					{
						$id = 'elementor_' . $element->id . '_' . $type . '_' . $key;
						$this->debug( "Preparsing image element %s", $id );
						$this->preparse_content( [
							'broadcasting_data' => $bcd,
							'content' => $value,
							'id' => $id,
						] );
					}
				}
				else
					$this->preparse_content( [
						'broadcasting_data' => $bcd,
						'content' => $element->settings->$type,
						'id' => 'elementor_' . $element->id,
					] );
			}

			foreach( static::$image_settings as $image_setting )
				if ( isset( $element->settings->$image_setting ) )
				{
					if ( $element->settings->$image_setting->id > 0 )
					{
						$image_id = $element->settings->$image_setting->id;
						if ( $bcd->try_add_attachment( $image_id ) )
							$this->debug( 'Found %s image %s.', $image_setting, $image_id );
					}
				}
		}

		if ( $element->elType == 'widget' )
		{
			// Handle id / url pairs in all widgets.
			foreach( $element->settings as $setting_index => $settings )
			{
				if ( ! isset( $settings->value ) )
					continue;
				$value_is_object = is_object( $settings->value );
				$old_value = (object) $settings->value;

				if ( ! isset( $old_value->id ) )
					continue;
				if ( ! isset( $old_value->url ) )
					continue;

				if ( $old_value->id < 1 )
					continue;

				if ( $bcd->try_add_attachment( $old_value->id ) )
					$this->debug( 'Found id / url %s in value setting %s.',
						$old_value->id,
						$setting_index,
					);
			}

			switch( $element->widgetType )
			{
				case 'avante-portfolio-grid':
				case 'dyxnet-testimonial-card':
					foreach( $element->settings->slides as $slide )
					{
						$image_id = $slide->slide_image->id;
						if ( $bcd->try_add_attachment( $image_id ) )
							$this->debug( 'Found %s slide widget. Adding attachment %s', $element->widgetType, $image_id );
					}
					break;
				case 'button':
				case 'call-to-action':
				case 'heading':
				case 'image-box':
					if ( ! isset( $element->settings->link ) )
						break;
					$url = $element->settings->link->url;
					$link_data = $this->url_to_broadcast_data( $url );
					$bcd->elementor->collection( 'url' )->set( $url, $link_data );
					$this->debug( 'Handling %s link: %s, %s', $element->widgetType, $url, $link_data );
					break;
				case 'devices-extended':
					$image_id = $element->settings->video_cover->id;
					if ( $bcd->try_add_attachment( $image_id ) )
						$this->debug( 'Found devices-extended widget. Adding attachment %s', $image_id );
					break;
				case 'dyncontel-popup':
					// The modal content might contain a shortcode to another template.
					$preparse_content = ThreeWP_Broadcast()->new_action( 'preparse_content' );
					$preparse_content->broadcasting_data = $bcd;
					$preparse_content->content = $element->settings->modal_content;
					$preparse_content->id = 'elementor_' . $element->id;
 					$this->debug( 'Sending %s for preparse.', $preparse_action->id );
					$preparse_content->execute();
					break;
				case 'gallery':
					if ( ! isset( $element->settings->gallery ) )
						break;
					foreach( $element->settings->gallery as $gallery_index => $gallery_item )
					{
						$image_id = $gallery_item->id;
						if ( $bcd->try_add_attachment( $image_id ) )
								$this->debug( 'Found gallery widget. Adding attachment %s', $image_id );
					}
					break;
				case 'ha-comparison-table':
					foreach( $element->settings->rows_data as $row_index => $row )
					{
						if ( ! isset( $row->__dynamic__->column_text ) )
							continue;
						$column_text = $row->__dynamic__->column_text;
						$preparse_action = new \threewp_broadcast\actions\preparse_content();
						$preparse_action->broadcasting_data = $bcd;
						$preparse_action->content = $column_text;
						$preparse_action->id = 'elementor_column_text_' . $row->_id;
						$this->debug( 'Sending %s for preparse.', $preparse_action->id );
						$preparse_action->execute();
					}
					break;
				case 'ha-slider':
					foreach( $element->settings->slides as $slides_index => $slides_item )
					{
						$image_id = $slides_item->image->id;
						if ( $bcd->try_add_attachment( $image_id ) )
								$this->debug( 'Found ha-slider widget. Adding attachment %s', $image_id );
					}
					break;
				case 'hotspot':
					// The image is handled by the general image handler.
					/**
					if ( isset( $element->settings->image ) )
					{
						$image_id = $element->settings->image->id;
						if ( $bcd->try_add_attachment( $image_id ) )
								$this->debug( 'Found hotspot image. Adding attachment %s', $image_id );
					}
					**/
					foreach ( $element->settings->hotspot as $index => $hotspot )
					{
						if ( ! isset( $hotspot->hotspot_icon->value ) )
							continue;
						// Skip the internal library.
						if ( ! is_object( $hotspot->hotspot_icon->value ) )
							continue;
						$image_id = $hotspot->hotspot_icon->value->id;
						if ( $bcd->try_add_attachment( $image_id ) )
								$this->debug( 'Found hotspot icon. Adding attachment %s', $image_id );
					}
					break;
				case 'icon-list':
					foreach( $element->settings->icon_list as $icon_list_index => $icon )
					{
						if ( ! isset( $icon->selected_icon->value ) )
							continue;
						$image_id = $icon->selected_icon->value->id;
						if ( $bcd->try_add_attachment( $image_id ) )
							$this->debug( 'Found icon-list icon %s. Adding attachment %s', $icon_list_index, $image_id );
					}
					break;
				case 'image':
				case 'image-box':
					// Some image widgets are not image widgets.
					if ( ! isset( $element->settings->image ) )
						break;
					$image_id = $element->settings->image->id;
					if ( $bcd->try_add_attachment( $image_id ) )
						$this->debug( 'Found image widget. Adding attachment %s', $image_id );
					break;
				case 'image-carousel':
					foreach( $element->settings->carousel as $carousel_index => $carousel_item )
					{
						$image_id = $carousel_item->id;
						if ( $bcd->try_add_attachment( $image_id ) )
								$this->debug( 'Found image-carousel widget. Adding attachment %s', $image_id );
					}
					break;
				case 'image-gallery':
					foreach( $element->settings->wp_gallery as $gallery_index => $gallery_item )
					{
						$image_id = $gallery_item->id;
						if ( $bcd->try_add_attachment( $image_id ) )
								$this->debug( 'Found image-gallery widget. Adding attachment %s', $image_id );
					}
					break;
				case 'global':
					$this->debug( 'Handling global slides.' );
					break;
				case 'loop-carousel':
				case 'loop-grid':
					$taxonomies = [];
					foreach( static::$loop_term_ids as $key )
					{
						if ( isset( $element->settings->$key ) )
						{
							$used_term_ids = [];
							foreach( $element->settings->$key as $old_term_id )
							{
								$term = get_term( $old_term_id );
								$taxonomies [ $term->taxonomy ] = $term->taxonomy;
								$used_term_ids []= $old_term_id;
							}
							$this->debug( 'In this %s, also syncing %s: %s',
								$element->widgetType,
								$key,
								implode( ", ", $taxonomies ),
							);
							foreach( $taxonomies as $taxonomy )
								$bcd
									->taxonomies()
									->also_sync( null, $taxonomy )
									->use_terms( $used_term_ids );
						}
					}
				case 'media-carousel':
					foreach( $element->settings->slides as $slide_index => $carousel_item )
					{
						$image_id = $carousel_item->image->id;
						if ( $bcd->try_add_attachment( $image_id ) )
								$this->debug( 'Found media-carousel slide %s. Adding attachment %s', $slide_index, $image_id );
						if ( isset( $carousel_item->image_link_to ) )
						{
							$url = $carousel_item->image_link_to->url;
							$bcd->elementor->collection( 'url' )->set( $url, $this->url_to_broadcast_data( $url ) );
						}
					}
					break;
				case 'ProductIntroFullDetail':
					foreach( [
						'bg_image',
						'bg_image_mobile',
						'image',
						'overlay_image',
						'overlay_image_mobile',
					] as $type )
					{
						$image_id = $element->settings->$type->id;
						if ( $bcd->try_add_attachment( $image_id ) )
							$this->debug( 'Found ProductIntroFullDetail %s. Adding attachment %s', $image_id );
					}
					break;
				case 'slides':
					foreach( $element->settings->slides as $slide )
					{
						if( $slide->background_image && 'library' == $slide->background_image->source ) {
							$image_id = $slide->background_image->id;
							if ( $bcd->try_add_attachment( $image_id ) )
								$this->debug( 'Found %s slide widget. Adding attachment %s', $element->widgetType, $image_id );
						}
					}
					break;
				case 'smartslider':
					// Fake a smartslider shortcode.
					$item_id = $element->settings->smartsliderid;
					$this->debug( 'Found item ID for %s is %s', $element->widgetType, $item_id );
					$preparse_content = ThreeWP_Broadcast()->new_action( 'preparse_content' );
					$preparse_content->broadcasting_data = $bcd;
					$preparse_content->content = '[smartslider3 slider="' . $item_id . '"]';
					$preparse_content->id = 'elementor_' . $element->id;
					$preparse_content->execute();
					break;
				case 'text-editor':
					// Send texts for preparsing.
					$preparse_content = ThreeWP_Broadcast()->new_action( 'preparse_content' );
					$preparse_content->broadcasting_data = $bcd;
					$preparse_content->content = $element->settings->editor;
					$preparse_content->id = 'elementor_' . $element->id;
					$preparse_content->execute();
					break;
				case 'uael-caf-styler':		// Caldera Forms.
					$caf_select_caldera_form_id = $element->settings->caf_select_caldera_form;
					$preparse_content = ThreeWP_Broadcast()->new_action( 'preparse_content' );
					$preparse_content->broadcasting_data = $bcd;
					$preparse_content->content = '[caldera_form id="' . $caf_select_caldera_form_id . '"]';
					$preparse_content->id = 'caldera_form_' . $element->id;
					$preparse_content->execute();
					break;
				case 'ucaddon_advance_svg_icons':
					if ( isset( $element->settings->add_icon->value ) )
					{
						$image_id = $element->settings->add_icon->value->id;
						if ( intval( $image_id ) > 0 )
							if ( $bcd->try_add_attachment( $image_id ) )
								$this->debug( 'Found %s widget. Adding attachment %s', $element->widgetType, $image_id );
					}
					break;
				case 'video':
					if ( isset( $element->settings->image_overlay ) )
					{
						$image_id = $element->settings->image_overlay->id;
						if ( intval( $image_id ) > 0 )
							if ( $bcd->try_add_attachment( $image_id ) )
								$this->debug( 'Found video image_overlay. Adding attachment %s', $image_id );
					}
					if ( isset( $element->settings->play_icon->value ) )
					{
						$image_id = $element->settings->play_icon->value->id;
						if ( intval( $image_id ) > 0 )
							if ( $bcd->try_add_attachment( $image_id ) )
								$this->debug( 'Found video play_icon. Adding attachment %s', $image_id );
					}
					break;
				case 'vt-saaspot_agency':
					$image_id = $element->settings->agency_image->id;
					if ( $bcd->try_add_attachment( $image_id ) )
						$this->debug( 'Found vt-saaspot_agency widget. Adding attachment %s', $image_id );
					break;
				case 'vt-saaspot_resource':
					foreach( $element->settings->ResourceItems as $index => $resource )
					{
						$image_id = $resource->resource_image->id;
						if ( $bcd->try_add_attachment( $image_id ) )
							$this->debug( 'Found vt-saaspot_resource widget. Adding attachment %s at index %s.', $image_id, $index );
					}
					break;
			}
		}

		if ( ! isset( $element->elements ) )
			return;

		// Preparse subelements.
		foreach( $element->elements as $element_index => $subelement )
			$this->preparse_element( $bcd, $subelement );
	}

	/**
		@brief		Preparse an EL element, looking for images and the like.
		@since		2017-04-29 02:14:28
	**/
	public function preparse_element( $bcd, $element )
	{
		$preparse_element_action = $this->new_action( 'preparse_element' );
		$preparse_element_action->broadcasting_data = $bcd;
		$preparse_element_action->element = $element;
		$preparse_element_action->execute();
	}

	/**
		@brief		Prepare the BCD object.
		@since		2021-03-09 18:33:58
	**/
	public function prepare_bcd( $bcd )
	{
		if ( ! isset( $bcd->elementor ) )
			$bcd->elementor = ThreeWP_Broadcast()->collection();
	}

	/**
	 * Handle the updating of the element.
	 *
	 * @since		2025-05-09 19:44:05
	 **/
	public function broadcast_elementor_parse_element( $action )
	{
		if ( $action->is_finished() )
			return;

		// Extract our data from the action.
		$bcd = $action->broadcasting_data;
		$element = $action->element;

		if ( isset( $element->settings ) )
		{
			foreach( static::$parseable_settings as $type )
			{
				if ( ! isset( $element->settings->$type ) )
					continue;
				if ( is_object( $element->settings->$type ) )
				{
					foreach( (array) $element->settings->$type as $key => $value )
					{
						$id = 'elementor_' . $element->id . '_' . $type . '_' . $key;
						$this->debug( "Preparsing image element %s", $id );
						$new_value = $this->parse_content( [
							'broadcasting_data' => $bcd,
							'content' => $value,
							'id' => $id,
						] );
						$element->settings->$type->$key = $new_value;
					}
				}
				else
					$element->settings->$type = $this->parse_content( [
						'broadcasting_data' => $bcd,
						'content' => $element->settings->$type,
						'id' => 'elementor_' . $element->id,
					] );
			}

			// Handle generic image fields.
			foreach( static::$image_settings as $image_setting )
				if ( isset( $element->settings->$image_setting ) )
					if ( $element->settings->$image_setting->id > 0 )
					{
						$old_image_id = $element->settings->$image_setting->id;
						$new_image_id = $bcd->copied_attachments()->get( $old_image_id );
						$this->debug( 'Replacing old %s %s with %s.', $image_setting, $old_image_id, $new_image_id );
						$element->settings->$image_setting->id = $new_image_id;
						$element->settings->$image_setting->url = ThreeWP_Broadcast()->update_attachment_ids( $bcd, $element->settings->$image_setting->url );
					}

			// Handle generic post ID fields.
			foreach( static::$post_ids_settings as $post_ids_setting )
				if ( isset( $element->settings->$post_ids_setting ) )
				{
					$new_post_ids = [];
					foreach( $element->settings->$post_ids_setting as $old_post_id )
						$new_post_ids []= $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $old_post_id, get_current_blog_id() );
					$this->debug( 'Replacing old %s %s with %s.', $post_ids_setting, $element->settings->$post_ids_setting, $new_post_ids );
					$element->settings->$post_ids_setting = $new_post_ids;
				}
		}

		if ( isset( $element->settings->__dynamic__->image) )
		{
			$image = $element->settings->__dynamic__->image;

			if ( strpos( $image, '"pods-' ) !== false )
			{
				$this->debug( 'Handling dynamic pods image %s', htmlspecialchars( $image ) );
				$old_settings = $image;
				$old_settings = preg_replace( '/.*settings="/', '', $old_settings );
				$old_settings = preg_replace( '/".*/', '', $old_settings );
				$old_settings_decoded = urldecode( $old_settings );
				$old_settings_decoded = json_decode( $old_settings_decoded );

				$key = $old_settings_decoded->key;
				$parts = explode( ':', $key );
				$old_post_id = $parts[ 1 ];
				$new_post_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $old_post_id, get_current_blog_id() );

				$parts[ 1 ] = $new_post_id;
				$key = implode( ':', $parts );
				$new_settings_decoded = $old_settings_decoded;
				$new_settings_decoded->key= $key;
				$new_settings = json_encode( $new_settings_decoded );
				$new_settings = urlencode( $new_settings );
				$element->settings->__dynamic__->image = str_replace(
					$old_settings,
					$new_settings,
					$element->settings->__dynamic__->image,
				);
				$this->debug( 'Replaced dynamic pods image ID %s with %s', $old_post_id, $new_settings );
			}
		}

		if ( isset( $element->settings->__dynamic__->link ) )
			$this->maybe_handle_dynamic_link( [
				'base' => $element->settings->__dynamic__,
				'bcd' => $bcd,
			] );

		if ( $element->elType == 'widget' )
		{
			// Handle id / url pairs in all widgets.
			foreach( $element->settings as $setting_index => $settings )
			{
				if ( ! isset( $settings->value ) )
					continue;
				$value_is_object = is_object( $settings->value );
				$old_value = (object) $settings->value;

				if ( ! isset( $old_value->id ) )
					continue;
				if ( ! isset( $old_value->url ) )
					continue;

				if ( $old_value->id < 1 )
					continue;

				$new_image_id = $bcd->copied_attachments()->get( $old_value->id );
				$this->debug( 'Found id / url in value setting %s. Replacing %s with %s.',
					$setting_index,
					$old_value->id,
					$new_image_id,
				);

				$new_value = clone( $old_value );

				$new_value->id = $new_image_id;
				$new_value->url = ThreeWP_Broadcast()->update_attachment_ids( $bcd, $old_value->url );

				if ( ! $value_is_object )
					$new_value = (array) $new_value;

				$element->settings->$setting_index->value = $new_value;
			}

			switch( $element->widgetType )
			{
				case 'avante-portfolio-grid':
				case 'dyxnet-testimonial-card':
					foreach( $element->settings->slides as $slide_index => $slide )
					{
						$image_id = $slide->slide_image->id;
						$new_image_id = $bcd->copied_attachments()->get( $image_id );
						$this->debug( 'Found %s slide widget. Replacing %s with %s', $element->widgetType, $image_id, $new_image_id );
						$element->settings->slides[ $slide_index ]->slide_image->id = $new_image_id;
						$element->settings->slides[ $slide_index ]->slide_image->url = ThreeWP_Broadcast()->update_attachment_ids( $bcd, $element->settings->slides[ $slide_index ]->slide_image->url );
					}
					break;
				case 'ae-post-blocks':
					$template_id = $element->settings->template;
					$new_template_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $template_id, get_current_blog_id() );
					$this->debug( 'New ae-post-blocks template ID %s is %s', $template_id, $new_template_id );
					$element->settings->template = $new_template_id;
					break;
				case 'blog-buttons':
					if ( isset( $element->settings->list ) )
					{
						$this->debug( 'Detected a list in the blog-buttons widget.' );
						foreach( $element->settings->list as $list_index => $list_item )
						{
							if ( isset( $list_item->__dynamic__->link ) )
								$this->maybe_handle_dynamic_link( [
									'base' => $element->settings->list[ $list_index ]->__dynamic__,
									'bcd' => $bcd,
								] );
						}
					}
					break;
				case 'button':
				case 'call-to-action':
				case 'heading':
				case 'image-box':
					if ( ! isset( $element->settings->link ) )
						break;
					$url = $element->settings->link->url;
					$bd = $bcd->elementor->collection( 'url' )->get( $url );
					$new_url = $this->broadcast_data_to_url( $bd, $url );
					$element->settings->link->url = $new_url;
					$this->debug( 'Replacing %s url %s with %s', $element->widgetType, $url, $new_url );
					break;
				case 'dce-dynamicposts-v2':
					$template_id = $element->settings->template_id;
					$new_template_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $template_id, get_current_blog_id() );
					$this->debug( 'New %s template ID %s is %s',
						$element->widgetType,
						$template_id,
						$new_template_id
					);
					$element->settings->template_id = $new_template_id;
					break;
				case 'devices-extended':
					$image_id = $element->settings->video_cover->id;
					$new_image_id = $bcd->copied_attachments()->get( $image_id );
					$this->debug( 'Found devices-extended widget. Replacing %s with %s.', $image_id, $new_image_id );
					$element->settings->video_cover->id = $new_image_id;
					$element->settings->video_cover->url = ThreeWP_Broadcast()->update_attachment_ids( $bcd, $element->settings->video_cover->url );
					break;
				case 'dyncontel-popup':
					$template_id = $element->settings->template;
					if ( $template_id > 0 )
					{
						$new_template_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $template_id, get_current_blog_id() );
						$this->debug( 'New %s template ID %s is %s',
							$element->widgetType,
							$template_id,
							$new_template_id
						);
						$element->settings->template = $new_template_id;
					}

					// The modal content might contain a shortcode to another template.
					$parse_content = ThreeWP_Broadcast()->new_action( 'parse_content' );
					$parse_content->broadcasting_data = $bcd;
					$parse_content->content = $element->settings->modal_content;
					$parse_content->id = 'elementor_' . $element->id;
					$parse_content->execute();
					$this->debug( 'Replaced modal_content %s with %s', $element->id, htmlspecialchars( $parse_content->content ) );
					$element->settings->modal_content = $parse_content->content;

					break;
				case 'gallery':
					if ( ! isset( $element->settings->gallery ) )
						break;
					foreach( $element->settings->gallery as $gallery_index => $gallery_item )
					{
						$image_id = $gallery_item->id;
						$new_image_id = $bcd->copied_attachments()->get( $image_id );
						$this->debug( 'Found gallery widget. Replacing %s with %s', $image_id, $new_image_id );
						$element->settings->gallery[ $gallery_index ]->id = $new_image_id;
						$element->settings->gallery[ $gallery_index ]->url = ThreeWP_Broadcast()->update_attachment_ids( $bcd, $element->settings->gallery[ $gallery_index ]->url );
					}
					break;
				case 'global':
					$template_id = $element->templateID;
					$this->debug( 'Handling global widget %s', $template_id );
					$new_template_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $template_id, get_current_blog_id() );
					$this->debug( 'New global widget ID %s is %s', $template_id, $new_template_id );
					$element->templateID = $new_template_id;
					break;
				case 'ha-comparison-table':
					foreach( $element->settings->rows_data as $row_index => $row )
					{
						if ( ! isset( $row->__dynamic__->column_text ) )
							continue;
						$column_text = $row->__dynamic__->column_text;
						$parse_action = new \threewp_broadcast\actions\parse_content();
						$parse_action->broadcasting_data = $bcd;
						$parse_action->content = $column_text;
						$parse_action->id = 'elementor_column_text_' . $row->_id;
						$this->debug( 'Sending %s for parse.', $parse_action->id );
						$parse_action->execute();
						$new_column_text = $parse_action->content;
						$element->settings->rows_data[ $row_index ]->__dynamic__->column_text = $new_column_text;
					}
					break;
				case 'ha-slider':
					foreach( $element->settings->slides as $slides_index => $slides_item )
					{
						$image_id = $slides_item->image->id;
						$new_image_id = $bcd->copied_attachments()->get( $image_id );
						$this->debug( 'Replacing ha-slider %s image %s with %s.', $slides_index, $image_id, $new_image_id );
						$element->settings->slides[ $slides_index ]->image->id = $new_image_id;
						$element->settings->slides[ $slides_index ]->image->url = ThreeWP_Broadcast()->update_attachment_ids( $bcd, $element->settings->slides[ $slides_index ]->image->url );
					}
					break;
				case 'hotspot':
					// This is handled by the generic image handler.
					// if ( isset( $element->settings->image ) )

					foreach ( $element->settings->hotspot as $index => $hotspot )
					{
						if ( ! isset( $hotspot->hotspot_icon->value ) )
							continue;
						if ( ! is_object( $hotspot->hotspot_icon->value ) )
							continue;
						$image_id = $hotspot->hotspot_icon->value->id;
						$new_image_id = $bcd->copied_attachments()->get( $image_id );
						$this->debug( 'Replacing hotspot %s image %s with %s.', $index, $image_id, $new_image_id );
						$element->settings->hotspot[ $index ]->hotspot_icon->value->id = $new_image_id;
						$element->settings->hotspot[ $index ]->hotspot_icon->value->url = ThreeWP_Broadcast()->update_attachment_ids( $bcd, $element->settings->hotspot[ $index ]->hotspot_icon->value->url );
					}
					break;
				case 'icon-list':
					foreach( $element->settings->icon_list as $icon_list_index => $icon )
					{
						if ( ! isset( $icon->selected_icon->value ) )
							continue;
						$image_id = $icon->selected_icon->value->id;
						$new_image_id = $bcd->copied_attachments()->get( $image_id );
						if ( $new_image_id > 0 )
						{
							$this->debug( 'Found icon-list %s. Replacing %s with %s.', $icon_list_index, $image_id, $new_image_id );
							$element->settings->icon_list[ $icon_list_index ]->selected_icon->value->id = $new_image_id;
							$element->settings->icon_list[ $icon_list_index ]->selected_icon->value->url = ThreeWP_Broadcast()->update_attachment_ids( $bcd, $icon->value->url );
						}
					}
					break;
				//case 'image':		// This is already handled by the generic image settings.
				case 'image-box':
					// Some image widgets are not image widgets.
					if ( ! isset( $element->settings->image ) )
						break;
					$image_id = $element->settings->image->id;
					$new_image_id = $bcd->copied_attachments()->get( $image_id );
					$this->debug( 'Found image widget. Replacing %s with %s.', $image_id, $new_image_id );
					$element->settings->image->id = $new_image_id;
					$element->settings->image->url = ThreeWP_Broadcast()->update_attachment_ids( $bcd, $element->settings->image->url );
					break;
				case 'image-carousel':
					foreach( $element->settings->carousel as $carousel_index => $carousel_item )
					{
						$image_id = $carousel_item->id;
						$new_image_id = $bcd->copied_attachments()->get( $image_id );
						$this->debug( 'Found carousel widget. Replacing %s with %s', $image_id, $new_image_id );
						$element->settings->carousel[ $carousel_index ]->id = $new_image_id;
						$element->settings->carousel[ $carousel_index ]->url = ThreeWP_Broadcast()->update_attachment_ids( $bcd, $element->settings->carousel[ $carousel_index ]->url );
					}
					break;
				case 'image-gallery':
					foreach( $element->settings->wp_gallery as $gallery_index => $gallery_item )
					{
						$image_id = $gallery_item->id;
						$new_image_id = $bcd->copied_attachments()->get( $image_id );
						$this->debug( 'Found gallery widget. Replacing %s with %s', $image_id, $new_image_id );
						$element->settings->wp_gallery[ $gallery_index ]->id = $new_image_id;
						$element->settings->wp_gallery[ $gallery_index ]->url = ThreeWP_Broadcast()->update_attachment_ids( $bcd, $element->settings->wp_gallery[ $gallery_index ]->url );
					}
					break;
				case 'jet-listing-grid':
					// Note that THEY typo'd 'lisitng_id'. Real quality there.
					if ( isset( $element->settings->lisitng_id ) )
					{
						$new_id = $bcd->equivalent_posts()->broadcast_once( $bcd->parent_blog_id, $element->settings->lisitng_id );
						$this->debug( 'In %s, replacing %s with %s.', $element->widgetType, $element->settings->lisitng_id, $new_id );
						$element->settings->lisitng_id = $new_id;
					}
					break;
				case 'jet-smart-filters-checkboxes':
				case 'jet-smart-filters-radio':
				case 'jet-smart-filters-range':
					if ( isset( $element->settings->filter_id ) )
					{
						$new_post_ids = [];
						foreach( $element->settings->filter_id as $old_post_id )
							$new_post_ids[]= $bcd->equivalent_posts()->broadcast_once( $bcd->parent_blog_id, $old_post_id );
						$this->debug( 'In %s, replacing %s with %s.', $element->widgetType, $element->settings->filter_id, $new_post_ids );
						$element->settings->filter_id = $new_post_ids;
					}
					break;
				case 'jet-smart-filters-search':
					if ( isset( $element->settings->filter_id ) )
					{
						$new_post_id = $bcd->equivalent_posts()->broadcast_once( $bcd->parent_blog_id, $element->settings->filter_id );
						$this->debug( 'In %s, replacing %s with %s.', $element->widgetType, $element->settings->filter_id, $new_post_id );
						$element->settings->filter_id = $new_post_id;
					}
					break;
				case 'jet-tabs':
					foreach( $element->settings->tabs as $tab_index => $tab_data )
					{
						if ( $tab_data->item_template_id > 0 )
						{
							$old_post_id = $tab_data->item_template_id;
							$new_post_id= $bcd->equivalent_posts()->broadcast_once( $bcd->parent_blog_id, $old_post_id );
							$this->debug( 'In jet tab %s, replacing item_template_id %s with %s.', $tab_index, $old_post_id, $new_post_id );
							$element->settings->tabs[ $tab_index ]->item_template_id = $new_post_id;
						}
					}
					break;
				case 'loop-carousel':
				case 'loop-grid':
					$template_id = $element->settings->template_id;
					$this->debug( 'Handling %s widget %s',
						$element->widgetType,
						$template_id,
					);
					$new_template_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $template_id, get_current_blog_id() );
					$this->debug( 'New %s template ID %s is %s',
						$element->widgetType,
						$template_id,
						$new_template_id,
					);
					$element->settings->template_id = $new_template_id;

					foreach( static::$loop_term_ids as $key )
					{
						if ( isset( $element->settings->$key ) )
						{
							$new_ids = [];
							foreach( $element->settings->$key as $old_term_id )
								$new_ids []= $bcd->terms()->get( $old_term_id );
							$element->settings->$key = $new_ids;
							$this->debug( 'Setting new %s: %s', $key, $new_ids );
						}
					}

					break;

					break;
				case 'global':
					$this->debug( 'Handling global slides.' );
					// No break!
				case 'media-carousel':
					foreach( $element->settings->slides as $slide_index => $carousel_item )
					{
						$image_id = $carousel_item->image->id;
						$new_image_id = $bcd->copied_attachments()->get( $image_id );
						$this->debug( 'Found media-carousel slide %s. Replacing %s with %s', $slide_index, $image_id, $new_image_id );
						$element->settings->slides[ $slide_index ]->image->id = $new_image_id;
						$element->settings->slides[ $slide_index ]->image->url = ThreeWP_Broadcast()->update_attachment_ids( $bcd, $carousel_item->image->url );
						if ( isset( $carousel_item->image_link_to ) )
						{
							$url = $carousel_item->image_link_to->url;
							$bd = $bcd->elementor->collection( 'url' )->get( $url );
							$new_url = $this->broadcast_data_to_url( $bd, $url );
							$this->debug( 'Replacing media-carousel image_link_to url %s with %s', $url, $new_url );
							$element->settings->slides[ $slide_index ]->image_link_to->url = $new_url;
						}
					}
					break;
				case 'ProductIntroFullDetail':
					foreach( [
						'bg_image',
						'bg_image_mobile',
						'image',
						'overlay_image',
						'overlay_image_mobile',
					] as $type )
					{
						$image_id = $element->settings->$type->id;
						$new_image_id = $bcd->copied_attachments()->get( $image_id );
						$this->debug( 'Found ProductIntroFullDetail %s. Replacing %s with %s.', $type, $image_id, $new_image_id );
						$element->settings->$type->id = $new_image_id;
						$element->settings->$type->url = ThreeWP_Broadcast()->update_attachment_ids( $bcd, $element->settings->$type->url );
					}
					break;
				case 'template':
					$old_template_id = $element->settings->template_id;
					$new_template_id = $bcd->equivalent_posts()->get( $bcd->parent_blog_id, $old_template_id, get_current_blog_id() );
					$this->debug( 'Found template widget. Replacing %d with %d.', $old_template_id, $new_template_id );
					$element->settings->template_id = $new_template_id;
					break;
				case 'slides':
					foreach( $element->settings->slides as $slide_index => $slide )
					{
						if( $slide->background_image && 'library' == $slide->background_image->source ) {
							$image_id = $slide->background_image->id;
							$new_image_id = $bcd->copied_attachments()->get( $image_id );
							$this->debug( 'Found %s slide widget. Replacing %s with %s', $element->widgetType, $image_id, $new_image_id );
							$element->settings->slides[ $slide_index ]->background_image->id = $new_image_id;
							$element->settings->slides[ $slide_index ]->background_image->url = ThreeWP_Broadcast()->update_attachment_ids( $bcd, $element->settings->slides[ $slide_index ]->background_image->url );
						}
					}
					break;

				case 'smartslider':
					// Fake a smartslider shortcode.
					$item_id = $element->settings->smartsliderid;
					$parse_content = ThreeWP_Broadcast()->new_action( 'parse_content' );
					$parse_content->broadcasting_data = $bcd;
					$parse_content->content = '[smartslider3 slider="' . $item_id . '"]';
					$parse_content->id = 'elementor_' . $element->id;
					$parse_content->execute();

					// Get the new ID
					$parse_content->content = trim( $parse_content->content, '[]' );
					$atts = shortcode_parse_atts( $parse_content->content );
					$new_value = $atts[ 'slider' ];
					$element->settings->smartsliderid = $new_value;
					$this->debug( 'New item ID for %s is %s', $element->widgetType, $new_value );
					break;
				case 'text-editor':
					if ( isset( $element->settings->editor ) )
					{
						$parse_content = ThreeWP_Broadcast()->new_action( 'parse_content' );
						$parse_content->broadcasting_data = $bcd;
						$parse_content->content = $element->settings->editor;
						$parse_content->id = 'elementor_' . $element->id;
						$parse_content->execute();
						$this->debug( 'Replaced element %s text-editor with %s', $element->id, htmlspecialchars( $parse_content->content ) );
						$element->settings->editor = $parse_content->content;
					}
					break;
				case 'thegem-extended-blog-grid':
				case 'thegem-posts-carousel':
					$loop_builder = $element->settings->loop_builder;
					$new_loop_builder = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $loop_builder, get_current_blog_id() );
					$this->debug( 'New %s loop_builder ID %s is %s',
						$element->widgetType,
						$loop_builder,
						$new_loop_builder,
					);
					$element->settings->loop_builder = $new_loop_builder;
					break;
				case 'thegem-template':
					$template_id = $element->settings->template_id;
					$new_template_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $template_id, get_current_blog_id() );
					$this->debug( 'New thegem-template template ID %s is %s', $template_id, $new_template_id );
					$element->settings->template_id = $new_template_id;
					break;
				case 'uael-caf-styler':		// Caldera Forms.
					// Fake a smartslider shortcode.
					$item_id = $element->settings->caf_select_caldera_form;
					$parse_content = ThreeWP_Broadcast()->new_action( 'parse_content' );
					$parse_content->broadcasting_data = $bcd;
					$parse_content->content = '[caldera_form id="' . $item_id . '"]';
					$parse_content->id = 'caldera_form_' . $element->id;
					$parse_content->execute();

					// Get the new ID
					$parse_content->content = trim( $parse_content->content, '[]' );
					$atts = shortcode_parse_atts( $parse_content->content );
					$new_value = $atts[ 'id' ];
					$element->settings->caf_select_caldera_form = $new_value;
					$this->debug( 'New item ID for %s is %s', $element->widgetType, $new_value );
					break;
				// Handle generic id / url value.
				/**
				case 'ucaddon_advance_svg_icons':
					if ( isset( $element->settings->add_icon->value ) )
						if ( intval( $element->settings->add_icon->value ) > 0 )
						{
							$image_id = $element->settings->add_icon->value->id;
							$new_image_id = $bcd->copied_attachments()->get( $image_id );
							$this->debug( 'Found %s widget. Replacing %s with %s.', $element->widgetType, $image_id, $new_image_id );
							$element->settings->add_icon->value->id = $new_image_id;
							$element->settings->add_icon->value->url = ThreeWP_Broadcast()->update_attachment_ids( $bcd, $element->settings->add_icon->value->url );
						}
					break;
				**/
				case 'video':
					if ( isset( $element->settings->image_overlay ) )
					{
						$image_id = $element->settings->image_overlay->id;
						if ( intval( $image_id ) > 0 )
						{
							$new_image_id = $bcd->copied_attachments()->get( $image_id );
							$element->settings->image_overlay->id = $new_image_id;
							$element->settings->image_overlay->url = wp_get_attachment_url( $new_image_id );
						}
					}
					// Handled generically.
					/**
					if ( isset( $element->settings->play_icon->value ) )
					{
						$image_id = $element->settings->play_icon->value->id;
						if ( intval( $image_id ) > 0 )
						{
							$new_image_id = $bcd->copied_attachments()->get( $image_id );
							$element->settings->play_icon->value->id = $new_image_id;
							$element->settings->play_icon->value->url = wp_get_attachment_url( $new_image_id );
						}
					}
					**/
					break;
				case 'vt-saaspot_agency':
					$image_id = $element->settings->agency_image->id;
					$new_image_id = $bcd->copied_attachments()->get( $image_id );
					$this->debug( 'Found vt-saaspot_agency widget. Replacing %s with %s.', $image_id, $new_image_id );
					$element->settings->agency_image->id = $new_image_id;
					$element->settings->agency_image->url = ThreeWP_Broadcast()->update_attachment_ids( $bcd, $element->settings->agency_image->url );
					break;
				case 'vt-saaspot_resource':
					foreach( $element->settings->ResourceItems as $index => $resource )
					{

						$image_id = $resource->resource_image->id;
						$new_image_id = $bcd->copied_attachments()->get( $image_id );
						$this->debug( 'Found vt-saaspot_resource widget. Replacing %s with %s at index %s.', $image_id, $new_image_id, $index );
						$element->settings->ResourceItems[ $index ]->resource_image->id = $new_image_id;
						$element->settings->ResourceItems[ $index ]->resource_image->url = ThreeWP_Broadcast()->update_attachment_ids( $bcd, $element->settings->ResourceItems[ $index ]->resource_image->url );
					}
					break;
				default:
					if ( isset( $element->settings->jet_attached_popup ) )
					{
						$old_popup_id = $element->settings->jet_attached_popup;
						$new_popup_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $old_popup_id, get_current_blog_id() );
						$this->debug( 'New button jet_attached_popup for %s is %s', $old_popup_id, $new_popup_id );
						$element->settings->jet_attached_popup = $new_popup_id;
					}
					break;
			}
		}

		if ( ! isset( $element->elements ) )
			return $element;

		// Update subelements.
		foreach( $element->elements as $element_index => $subelement )
			$element->elements[ $element_index ] = $this->parse_element( $bcd, $subelement );

		return $element;
	}

	/**
	 * Maybe handle this dynamic link.
	 *
	 * @since		2025-08-04 16:38:38
	 **/
	public function maybe_handle_dynamic_link( $options )
	{
		$options = (object) $options;
		$base = $options->base;
		$bcd = $options->bcd;			// Convenience, since it is shorter to write.

		$link = $base->link;

		$this->debug( 'Handling dynamic link %s', htmlspecialchars( $link ) );
		$old_settings = $link;
		$old_settings = preg_replace( '/.*settings="/', '', $old_settings );
		$old_settings = preg_replace( '/".*/', '', $old_settings );
		$old_settings_decoded = urldecode( $old_settings );
		$old_settings_decoded = json_decode( $old_settings_decoded );

		if ( isset( $old_settings_decoded->popup ) )
		{
			$old_post_id = $old_settings_decoded->popup;
			$new_post_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $old_post_id, get_current_blog_id() );
			$new_post_id = (string) $new_post_id;
			$new_settings_decoded = $old_settings_decoded;
			$new_settings_decoded->popup = $new_post_id;
			$new_settings = json_encode( $new_settings_decoded );
			$new_settings = urlencode( $new_settings );
			$base->link = str_replace(
				$old_settings,
				$new_settings,
				$base->link,
			);
			$this->debug( 'Replaced dynamic link popup ID %s with %s', $old_post_id, $new_post_id );
		}

		if ( isset( $old_settings_decoded->post_id ) )
		{
			$old_post_id = $old_settings_decoded->post_id;
			$new_post_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $old_post_id, get_current_blog_id() );
			$new_settings_decoded = $old_settings_decoded;
			$new_settings_decoded->post_id = $new_post_id;
			$new_settings = json_encode( $new_settings_decoded );
			$new_settings = urlencode( $new_settings );
			$base->link = str_replace(
				$old_settings,
				$new_settings,
				$base->link,
			);
			$this->debug( 'Replaced dynamic link post ID %s with %s', $old_post_id, $new_post_id );
		}
	}

	/**
		@brief		Update the Elementor data with new values.
		@since		2017-04-29 02:26:52
	**/
	public function parse_element( $bcd, $element )
	{
		$parse_element_action = $this->new_action( 'parse_element' );
		$parse_element_action->broadcasting_data = $bcd;
		$parse_element_action->element = $element;
		$parse_element_action->execute();
		return $parse_element_action->element;
	}

}
