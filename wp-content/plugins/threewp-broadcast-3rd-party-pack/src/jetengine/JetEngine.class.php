<?php

namespace threewp_broadcast\premium_pack\jetengine;

/**
	@brief			Adds support for <a href="https://crocoblock.com/plugins/jetengine/custom-post-type/">JetEngine custom post types</a>.
	@plugin_group	3rd party compatability
	@since			2022-10-06 13:33:56
**/
class JetEngine
	extends \threewp_broadcast\premium_pack\base
{
	use \threewp_broadcast\premium_pack\classes\broadcast_generic_post_ui_trait;
	use \threewp_broadcast\premium_pack\classes\broadcast_things_ui_trait;
	use \threewp_broadcast\premium_pack\classes\database_trait;

	/**
	 *	@brief	The instanciated Queries class.
	 *	@since	2025-05-09 21:23:28
	 **/
	public static $queries_instance;

	public function _construct()
	{
		$this->add_action( 'broadcast_elementor_parse_element' );
		$this->add_action( 'broadcast_elementor_preparse_element' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_collect_post_type_taxonomies' );
		$this->add_action( 'threewp_broadcast_menu' );
		$this->add_action( 'threewp_broadcast_wp_update_term' );

		new Jet_Smart_Filters();
	}

	// -------------------------------------------------------------------------------------------------------------------
	// --- CALLBACKS
	// -------------------------------------------------------------------------------------------------------------------

	/**
		@brief		Add our tabs to the menu.
		@since		2023-08-24 21:29:47
	**/
	public function admin_tabs( $action )
	{
		$tabs = $this->tabs();

		$tabs->tab( 'bc_jetengine_cct' )
			->callback_this( 'broadcast_cct' )
			->heading( 'Broadcast JetEngine Custom Content Types' )
			->name( 'Custom Content Types' );

		$tabs->tab( 'bc_jetengine_options' )
			->callback_this( 'broadcast_options_pages' )
			->heading( 'Broadcast JetEngine Options Pages' )
			->name( 'Options Pages' );

		$tabs->tab( 'bc_jetengine_meta_boxes' )
			->callback_this( 'broadcast_meta_boxes' )
			->heading( 'Broadcast JetEngine Meta Boxes' )
			->name( 'Meta Boxes' );

		$tabs->tab( 'bc_jetengine_queries' )
			->callback_this( 'broadcast_queries' )
			->heading( 'Broadcast JetEngine Queries' )
			->name( 'Queries' );

		$tabs->tab( 'bc_jetengine_smart_filters' )
			->callback_this( 'broadcast_smart_filters' )
			->heading( 'Broadcast Jet Smart Filters' )
			->name( 'Smart Filters' );

		$tabs->tab( 'bc_jetengine_taxonomies' )
			->callback_this( 'broadcast_taxonomies' )
			->heading( 'Broadcast JetEngine Taxonomies' )
			->name( 'Taxonomies' );

		echo $tabs->render();
	}

	/**
	 * Broadcast custom content types
	 *
	 * @since		2025-04-30 16:51:05
	 **/
	public function broadcast_cct()
	{
		echo $this->broadcast_things_ui( [
			'actions_add_callback' => function( $options )
			{
				$options->purge_broadcast_items = $options->actions_fieldset->checkbox( 'purge_broadcast_items' )
					->description( 'This will empty the selected blogs of CCT items and rebroadcast them.' )
					->label( 'Purge and broadcast CCT items' );
			},
			'get_items_function' => function( $options )
			{
				global $wpdb;
				$table = sprintf( '%sjet_post_types', $wpdb->prefix );

				$this->database_table_must_exist( $table );

				if ( ! $options->broadcasting )
					$options->source_database_prefix = $wpdb->prefix;

				$query = sprintf( "SELECT * FROM `%s` WHERE `status` = 'content-type' ORDER BY `slug`", $table );
				$this->debug( $query );
				$rows = $wpdb->get_results( $query );

				$r = ThreeWP_Broadcast()->collection();
				foreach( $rows as $row )
					$r->set( $row->slug, $row );

				return $r;
			},
			'label_plural' => 'custom content types',
			'label_singular' => 'custom content type',
			'set_items_function' => function( $array, $options )
			{
				$new_row = $array;
				$new_row = reset( $new_row );
				$new_row = (array) $new_row;
				unset( $new_row[ 'id' ] );

				global $wpdb;
				$table = sprintf( '%sjet_post_types', $wpdb->prefix );

				// Find the existing row, if any.
				$query = sprintf( "SELECT `id` FROM `%s` WHERE `slug` = '%s' AND `status` = '%s'",
					$table,
					$new_row[ 'slug' ],
					$new_row[ 'status' ],
				);
				$this->debug( $query );
				$existing_row = $wpdb->get_row( $query );
				if( $existing_row )
					$new_row[ 'id' ] = $existing_row->id;

				$query = sprintf( "DELETE FROM `%s` WHERE `slug` = '%s' AND `status` = '%s'",
					$table,
					$new_row[ 'slug' ],
					$new_row[ 'status' ],
				);
				$this->debug( $query );
				$wpdb->query( $query );

				$this->debug( 'Inserting new row: %s', $new_row );
				$wpdb->insert( $table, $new_row );

				$cct_slug = $new_row[ 'slug' ];

				// Maybe create the cct table.
				$query = sprintf( "CREATE TABLE IF NOT EXISTS `%sjet_cct_%s` LIKE `%sjet_cct_%s`;",
					$wpdb->prefix,
					$cct_slug,
					$options->source_database_prefix,
					$cct_slug,
				);
				$this->debug( $query );
				$wpdb->query( $query );

				if ( $options->purge_broadcast_items->is_checked() )
				{
					$query = sprintf( "DELETE FROM `%sjet_cct_%s`",
						$wpdb->prefix,
						$cct_slug,
					);
					$this->debug( $query );
					$wpdb->query( $query );

					$query = sprintf( "INSERT INTO `%sjet_cct_%s` ( SELECT * FROM `%sjet_cct_%s` )",
						$wpdb->prefix,
						$cct_slug,
					$options->source_database_prefix,
					$cct_slug,
					);
					$this->debug( $query );
					$wpdb->query( $query );
				}
			},
			'show_item_label_callback' => function( $items, $item_id, $item )
			{
				$args = $item->args;
				$args = maybe_unserialize( $args );
				$r = sprintf( '%s (%s)', $args[ 'name' ], $item->id );
				return $r;
			},
		] );
	}

	/**
	 * Replace the query IDs.
	 *
	 * @since		2025-05-09 22:18:52
	 **/
	public function broadcast_elementor_parse_element( $action )
	{
		$bcd = $action->broadcasting_data;
		$element = $action->element;

		if ( ! isset( $bcd->elementor_jet_engine_queries ) )
			return;

		if ( ! isset( $element->settings ) )
			return;

		foreach( $element->settings as $key => $value )
		{
			if ( ! str_starts_with( $key, 'jet_engine_query_id_' ) )
				continue;

			if ( ! $bcd->elementor_jet_engine_queries->has( $value ) )
				continue;

			$old_row = $bcd->elementor_jet_engine_queries->get( $value );

			$this->queries()->insert_or_update( $old_row );
			$new_row = $this->queries()->get_by_name( $this->queries()->get_name( $old_row ) );

			$element->settings->$key = $new_row->id;
			$this->debug( 'Replaced old %s %s with %s',
				$key,
				$old_row->id,
				$new_row->id,
			);

		}
	}

	/**
	 * Find any jet engine query builder IDs.
	 *
	 * @since		2025-05-09 22:18:52
	 **/
	public function broadcast_elementor_preparse_element( $action )
	{
		$bcd = $action->broadcasting_data;
		$element = $action->element;

		if ( ! isset( $element->settings ) )
			return;

		foreach( $element->settings as $key => $value )
		{
			if ( ! str_starts_with( $key, 'jet_engine_query_id_' ) )
				continue;
			$row = $this->queries()->get_by_id( $value );
			$this->debug( 'Found %s: %s', $key, $row );

			if ( ! isset( $bcd->elementor_jet_engine_queries ) )
				$bcd->elementor_jet_engine_queries = ThreeWP_Broadcast()->collection();

			$bcd->elementor_jet_engine_queries->set( $value, $row );
		}
	}

	/**
		@brief		broadcast_options_pages
		@since		2023-08-24 21:30:41
	**/
	public function broadcast_options_pages()
	{
		echo $this->broadcast_things_ui( [
			'get_items_function' => function()
			{
				// We could use this.
				// $items = jet_engine()->options_pages->data->get_items();
				// But Jetengine doesn't reload options pages during a blog switch. So we go directly to the DB.

				global $wpdb;
				$table = sprintf( '%sjet_post_types', $wpdb->prefix );

				$this->database_table_must_exist( $table );

				$query = sprintf( "SELECT * FROM `%s` WHERE `status` IN ( 'page', 'publish' ) ORDER BY `slug`", $table );
				$this->debug( $query );
				$rows = $wpdb->get_results( $query );

				$r = ThreeWP_Broadcast()->collection();
				foreach( $rows as $row )
					$r->set( $row->slug, $row );

				return $r;
			},
			'label_plural' => 'options pages',
			'label_singular' => 'options page',
			'set_items_function' => function( $array )
			{
				$new_row = $array;
				$new_row = reset( $new_row );
				$new_row = (array) $new_row;
				unset( $new_row[ 'id' ] );

				global $wpdb;
				$table = sprintf( '%sjet_post_types', $wpdb->prefix );

				$query = sprintf( "DELETE FROM `%s` WHERE `slug` = '%s' AND `status` = '%s'",
					$table,
					$new_row[ 'slug' ],
					$new_row[ 'status' ],
				);
				$this->debug( $query );
				$wpdb->query( $query );

				$this->debug( 'Inserting new row: %s', $new_row );
				$wpdb->insert( $table, $new_row );
			},
			'show_item_label_callback' => function( $items, $item_id, $item )
			{
				$labels = $item->labels;
				$labels = maybe_unserialize( $labels );
				$r = sprintf( '%s (%s)', $labels[ 'name' ], $item->id );
				return $r;
			},
		] );
	}

	/**
		@brief		Broadcast meta boxes.
		@since		2023-08-24 21:30:41
	**/
	public function broadcast_meta_boxes()
	{
		echo $this->broadcast_things_ui( [
			'get_items_function' => function()
			{
				$option_key = 'jet_engine_meta_boxes';
				$option = get_option( $option_key );
				$items = maybe_unserialize( $option );

				$r = ThreeWP_Broadcast()->collection();
				foreach( $items as $item )
				{
					$name = sanitize_title( $item[ 'args' ][ 'name' ] );
					$item[ 'id' ] = $name;
					$r->set( $name, $item );
				}

				return $r;
			},
			'label_plural' => 'meta boxes',
			'label_singular' => 'meta box',
			'set_items_function' => function( $array )
			{
				$new_key = key( $array );
				$new_value = reset( $array );

				$option_key = 'jet_engine_meta_boxes';
				$option = get_option( $option_key );
				$items = maybe_unserialize( $option );
				$found = false;

				// Merge or replace.
				foreach( $items as $item_id => $item )
				{
					$name = sanitize_title( $item[ 'args' ][ 'name' ] );
					if ( $name != $new_key )
						continue;
					$this->debug( 'Updating existing %s', $new_key );
					$found = true;
					$items[ $item_id ] = $new_value;
				}

				if ( ! $found )
				{
					$this->debug( 'Inserting new %s', $new_key );
					$items[ $new_key ] = $new_value;
				}

				update_option( $option_key, $items );
			},
			'show_item_label_callback' => function( $items, $item_id, $item )
			{
				$name = $item[ 'args' ][ 'name' ];
				$r = sprintf( '%s (%s)', $name, $item_id );
				return $r;
			},
		] );
	}

	/**
		* @brief		Broadcast queries.
		* @since		2025-05-09 21:12:27
	**/
	public function broadcast_queries()
	{
		echo $this->broadcast_things_ui( [
			'get_items_function' => function( $options )
			{
				global $wpdb;
				$table = sprintf( '%sjet_post_types', $wpdb->prefix );

				$this->database_table_must_exist( $table );

				if ( ! $options->broadcasting )
					$options->source_database_prefix = $wpdb->prefix;

				$queries = broadcast_jetengine()->queries();

				$query = sprintf( "SELECT * FROM `%s` WHERE `status` = 'query' ORDER BY `slug`", $table );
				$this->debug( $query );
				$rows = $wpdb->get_results( $query );

				$r = ThreeWP_Broadcast()->collection();
				foreach( $rows as $row )
				{
					$name = $queries->get_name( $row );
					$r->set( $name, $row );
				}

				return $r;
			},
			'label_plural' => 'queries',
			'label_singular' => 'query',
			'set_items_function' => function( $array, $options )
			{
				$queries = broadcast_jetengine()->queries();

				$new_row = $array;
				$new_row = reset( $new_row );

				$queries->insert_or_update( $new_row );
			},
			'show_item_label_callback' => function( $items, $item_id, $item )
			{
				return broadcast_jetengine()->queries()->get_name( $item );
			},
		] );
	}

	/**
		* @brief		Broadcast smart filters.
		* @since		2025-05-12 18:53:51
	**/
	public function broadcast_smart_filters()
	{
		echo $this->broadcast_generic_post_ui( [
			'post_type' => 'jet-smart-filters',
			'label_plural' => 'Smart Filters',
			'label_singular' => 'Smart Filter',
		] );
	}

	/**
		* @brief		Broadcast taxonomies.
		* @since		2025-05-21 20:19:27
	**/
	public function broadcast_taxonomies()
	{
		echo $this->broadcast_things_ui( [
			'get_items_function' => function( $options )
			{
				global $wpdb;
				$table = sprintf( '%sjet_taxonomies', $wpdb->prefix );

				$this->database_table_must_exist( $table );

				if ( ! $options->broadcasting )
					$options->source_database_prefix = $wpdb->prefix;

				$query = sprintf( "SELECT * FROM `%s` WHERE `status` = 'publish' ORDER BY `slug`", $table );
				$this->debug( $query );
				$rows = $wpdb->get_results( $query );

				$r = ThreeWP_Broadcast()->collection();
				foreach( $rows as $row )
					$r->set( $row->slug, $row );

				return $r;
			},
			'label_plural' => 'taxonomies',
			'label_singular' => 'taxonomy',
			'set_items_function' => function( $array, $options )
			{
				$new_row = $array;
				$new_row = reset( $new_row );
				$new_row = (array) $new_row;
				unset( $new_row[ 'id' ] );

				global $wpdb;
				$table = sprintf( '%sjet_taxonomies', $wpdb->prefix );

				// Find the existing row, if any.
				$query = sprintf( "SELECT `id` FROM `%s` WHERE `slug` = '%s' AND `status` = '%s'",
					$table,
					$new_row[ 'slug' ],
					$new_row[ 'status' ],
				);
				$this->debug( $query );
				$existing_row = $wpdb->get_row( $query );
				if( $existing_row )
					$new_row[ 'id' ] = $existing_row->id;

				$query = sprintf( "DELETE FROM `%s` WHERE `slug` = '%s' AND `status` = '%s'",
					$table,
					$new_row[ 'slug' ],
					$new_row[ 'status' ],
				);
				$this->debug( $query );
				$wpdb->query( $query );

				$this->debug( 'Inserting new row: %s', $new_row );
				$wpdb->insert( $table, $new_row );
			},
			'show_item_label_callback' => function( $items, $item_id, $item )
			{
				$labels = $item->labels;
				$labels = maybe_unserialize( $labels );
				$r = sprintf( '%s (%s)', $labels[ 'name' ], $item->id );
				return $r;
			},
		] );
	}

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2022-10-10 18:00:22
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->jetengine ) )
			return;

		$bcd->jetengine->set( 'restoring_post', true );
		$this->debug( 'Setting restoring_post.' );

		$this->maybe_restore_fields( $bcd, $bcd->jetengine->collection( 'fields' ) );

		if ( apply_filters( 'broadcast_jetengine_restore_relationships', true ) )
			$this->restore_relationships( $bcd );

		$bcd->jetengine->forget( 'restoring_post' );
		$this->debug( 'Forgetting restoring_post.' );

		wp_cache_flush();
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2022-10-10 18:00:22
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		if ( ! function_exists( 'jet_engine' ) )
			return;

		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->jetengine ) )
			$bcd->jetengine = ThreeWP_Broadcast()->collection();

		$this->maybe_save_fields( $bcd );

		$table = static::get_prefixed_table_name( 'jet_rel_default' );
		$this->relationships_available = $this->database_table_exists( $table );

		if ( ! $this->relationships_available )
			return;

		$bcd->jetengine->relationships = ThreeWP_Broadcast()->collection();

		$this->save_relationships( $bcd );
	}

	/**
		@brief		Handle the category images.
		@since		2022-10-10 18:02:02
	**/
	public function threewp_broadcast_collect_post_type_taxonomies( $action )
	{
		// For older versions of WC.
		if ( ! function_exists( 'get_term_meta' ) )
			return;

		if ( ! function_exists( 'jet_engine' ) )
			return;

		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->jetengine ) )
			$bcd->jetengine = ThreeWP_Broadcast()->collection();

		if ( ! isset( $bcd->jetengine_termmeta ) )
			$bcd->jetengine_termmeta = ThreeWP_Broadcast()->collection();

		foreach( $bcd->parent_blog_taxonomies as $parent_post_taxonomy => $taxonomy_data )
		{
			$terms = $taxonomy_data[ 'terms' ];

			$category_fields = jet_engine()->meta_boxes->get_fields_for_context( 'taxonomy', $parent_post_taxonomy );

			$this->debug( 'Collecting termmeta for %s', $parent_post_taxonomy );
			// Get all of the fields for all terms
			foreach( $terms as $term )
			{
				$term_id = $term->term_id;

				foreach( $category_fields as $field )
				{
					$field = (object) $field;
					$field->term_id = $term_id;
					$this->save_field( $bcd, $field );
				}

				// Save the image.
				// 2024 04 07 - I wonder if this is perhaps a meta field someone added and I thought was a standard field that is automatically added?
				$key = 'category-image';
				$image_id = get_term_meta( $term_id, $key, true );

				if ( $image_id > 0 )
				{
				  $this->debug( 'Found category image %s for term %s (%s)',
					  $image_id,
					  $term->slug,
					  $term_id
				  );

				  $bcd->try_add_attachment( $image_id );
				  $bcd->jetengine_termmeta
				  	->collection( 'category-image' )
				  	->set( $term_id , $image_id );
				}
			}
		}
	}

	/**
		@brief		Add ourselves to the Jetengine menu
		@since		2023-08-23 07:42:41
	**/
	public function threewp_broadcast_menu( $action )
	{
		if ( ! function_exists( 'jet_engine' ) )
			return;

		if ( ! is_super_admin() )
			return;

		$action->menu_page
			->submenu( 'broadcast_jet_engine' )
			->callback_this( 'admin_tabs' )
			->menu_title( 'Jet Engine' )
			->page_title( 'Jet Engine Broadcast' );
	}

	/**
		@brief		Restore the image.
		@since		2022-10-10 18:01:32
	**/
	public function threewp_broadcast_wp_update_term( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->jetengine_termmeta ) )
			return;

		ThreeWP_Broadcast()->copy_attachments_to_child( $bcd );

		$old_term_id = $action->old_term->term_id;
		$new_term_id = $action->new_term->term_id;


		$c = $bcd->jetengine_termmeta->collection( 'fields' )
					->collection( $old_term_id );
		foreach( $c as $field )
		{
			$field->term_id = $new_term_id;
			$this->debug( 'Restoring term field %s', $old_term_id );
			$this->maybe_restore_fields( $bcd, [ $field ] );
		}

		$c = $bcd->jetengine_termmeta->collection( 'category-image' );
		if ( count( $c ) < 1 )
			return;

		// Restore photo.
		$old_image_id = $bcd->jetengine_termmeta->collection( 'category-image' )
			->get( $old_term_id, 0 );

		if ( $old_image_id > 0 )
		{
			$new_key = 'category-image';
			$new_value = $bcd->copied_attachments()->get( $old_image_id );

			if ( $new_value < 1 )
				return $this->debug( 'Old attachment %s was not copied. Unable to restore category image.', $old_image_id );

			$this->debug( 'Restoring %s with new attachment ID %s from %s.',
				$new_key,
				$new_value,
				$old_image_id
			);
			update_term_meta( $new_term_id, $new_key, $new_value );
		}
	}

	// -------------------------------------------------------------------------------------------------------------------
	// --- MISC
	// -------------------------------------------------------------------------------------------------------------------

	/**
		@brief		maybe_restore_fields
		@since		2022-10-14 05:02:50
	**/
	public function maybe_restore_fields( $bcd, $fields )
	{
		foreach( $fields as $field )
		{
			$field_name = $field->name;
			$old_value = $field->old_value;

			switch( $field->type )
			{
				case 'gallery':
				case 'media':
				case 'posts':
					$new_value = $this->restore_field( $bcd, $field, $old_value );
				break;
				case 'repeater':
					$new_values = [];
					$old_values = unserialize( $old_value );

					$key = 'repeater-fields';
					foreach( $old_values as $value_index => $repeater_values )
					{
						$new_values[ $value_index ] = [];
						foreach( $field->$key as $repeater_index => $repeater_field )
						{
							$repeater_field = (object) $repeater_field;
							$new_value = $this->restore_field( $bcd, $repeater_field, $repeater_values[ $repeater_field->name ] );
							$new_values[ $value_index ][ $repeater_field->name ] = $new_value;
						}
					}
					$new_value = $new_values;		// Update meta does the serializing.
				break;
				case 'text':
					$parse_action = new \threewp_broadcast\actions\parse_content();
					$parse_action->broadcasting_data = $bcd;
					$parse_action->content = $old_value;
					$parse_action->id = 'jetengine_' . $field->name;
					$parse_action->execute();
					$new_value = $parse_action->content;
				break;
			}

			$this->debug( 'Restoring %s %s: %s -> %s', $field->type, $field_name, json_encode( $old_value ), json_encode( $new_value ) );

			$restoring_post = false;
			if ( isset( $bcd->jetengine ) )
				if ( $bcd->jetengine->has( 'restoring_post' ) )
					$restoring_post = true;

			if ( $restoring_post )
			{
				$bcd->custom_fields()->child_fields()
					->update_meta( $field_name, $new_value );
			}
			else
			{
				update_term_meta( $field->term_id, $field_name, $new_value );
			}
		}
	}

	/**
	 * Return an instance of the Queries handler.
	 *
	 * @since		2025-05-09 21:22:52
	 **/
	public function queries()
	{
		if ( static::$queries_instance === null )
			static::$queries_instance = new Queries();
		return static::$queries_instance;
	}

	/**
		@brief		Restore a field.
		@since		2022-10-15 08:53:35
	**/
	public function restore_field( $bcd, $field, $old_value )
	{
		switch( $field->type )
		{
			case 'gallery':
				$this->debug( 'About to restore gallery.' );
				$new_values = [];

				$id_format = true;
				if ( isset( $field->value_format ) )
				{
					if ( $field->value_format == 'both' )
					{
						$old_values = maybe_unserialize( $old_value );
						foreach( $old_values as $image_data )
						{
							$image_data[ 'id' ] = $bcd->copied_attachments()->get( $image_data[ 'id' ] );
							$image_data[ 'url' ] = wp_get_attachment_url( $image_data[ 'id' ] );
							$new_values []= $image_data;
						}
						$id_format = false;
						$new_value = $new_values;
					}
					if ( $field->value_format == 'url' )
					{
						$new_values = [];
						foreach( $old_value as $old_post_id )
						{
							$new_post_id = $bcd->copied_attachments()->get( $old_post_id );
							$new_value = wp_get_attachment_url( $new_post_id );
							$new_values []= $new_value;
						}
						$new_value = implode( ",", $new_values );
						$id_format = false;
					}
				}

				if ( $id_format )
				{
					$old_values = explode( ",", $old_value );
					foreach( $old_values as $old_image_id )
					{
						$new_value = $bcd->copied_attachments()->get( $old_image_id );
						$this->debug( 'Replacing old gallery image %s with %s', $old_image_id, $new_value );
						$new_values []= $new_value;
					}
					$new_value = implode( ",", $new_values );
				}
			break;
			case 'media':
				$id_format = true;
				if ( isset( $field->value_format ) )
				{
					$this->debug( 'Value format for %s: %s', $field->type, $field->value_format );

					if ( $field->value_format == 'both' )
					{
						$image_data = maybe_unserialize( $old_value );
						$image_data[ 'id' ] = $bcd->copied_attachments()->get( $image_data[ 'id' ] );
						$image_data[ 'url' ] = wp_get_attachment_url( $image_data[ 'id' ] );
						$new_value []= $image_data;
						$id_format = false;
					}
					if ( $field->value_format == 'url' )
					{
						$new_post_id = $bcd->copied_attachments()->get( $old_value );
						$this->debug( 'New image ID for media %s is %s', $old_value, $new_post_id );
						$new_value = wp_get_attachment_url( $new_post_id );
						$id_format = false;
					}
				}

				if ( $id_format )
				{
					$new_value = $bcd->copied_attachments()->get( $old_value );
				}
			break;
			case 'posts':
				$old_values = maybe_unserialize( $old_value );
				if ( is_array ( $old_values ) )
				{
					$new_values = [];
					foreach( $old_values as $old_value )
					{
						$new_value = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $old_value, get_current_blog_id() );
						$new_values []= $new_value;
					}
					$new_value = $new_values;
				}
				else
				{
					$new_value = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $old_value, get_current_blog_id() );
				}
			break;
			default:
				$new_value = $old_value;
		}

		return $new_value;
	}

	/**
		@brief		Maybe save the fields of this post.
		@since		2022-10-13 21:28:10
	**/
	public function maybe_save_fields( $bcd )
	{
		$fields = jet_engine()->meta_boxes->get_registered_fields();

		if ( ! isset( $fields[ $bcd->post->post_type ] ) )
			return;

		$fields = $fields[ $bcd->post->post_type ];

		$this->debug( 'The fields are: %s', $fields );

		foreach( $fields as $field )
		{
			$field = (object) $field;
			$this->save_field( $bcd, $field );
		}
	}

	/**
		@brief		Restore the relationships.
		@since		2022-10-10 18:00:22
	**/
	public function restore_relationships( $bcd )
	{
		if ( ! $this->relationships_available )
			return;

		global $wpdb;

		$equivalent_relationships = [];
		$r = $bcd->jetengine->relationships;

		// Before adding relationships, they must first be cleared.
		// Keep track of which rel IDs have been cleared.
		$rels_cleared = [];

		foreach( [
			'child_object_id',
			'parent_object_id',
		] as $looking_for )
		{
			if ( $r->get( 'empty' ) )
				$this->delete_relationships( $bcd->new_post->ID, $looking_for );

			$col = $r->collection( 'looking_for' )
				->collection( $looking_for );

			foreach( $col->collection( 'post_relationships' ) as $post_relationship )
			{
				$rel_id = $post_relationship->rel_id;
				if ( ! array_key_exists( $rel_id, $equivalent_relationships ) )
				{
					$jet_post_types_table = static::get_prefixed_table_name( 'jet_post_types' );
					$parent_relationship = $col->collection( 'relationships' )->get( $rel_id );
					// Find the relationship with the same status and labels.
					$query = sprintf( "SELECT * FROM `%s` WHERE `status` = 'relation' AND `labels` = '%s'",
						$jet_post_types_table,
						addslashes( $parent_relationship->labels )
					);
					$this->debug( $query );
					$equivalent_relationship = $wpdb->get_row( $query );

					if ( ! $equivalent_relationship )
					{
						// Create this relationship.
						unset( $parent_relationship->id );
						$this->debug( 'Creating relationship %s', $rel_id );
						$wpdb->insert( $jet_post_types_table, (array) $parent_relationship );
						$equivalent_rel_id = $wpdb->insert_id;
					}
					else
					{
						$this->debug( 'Equivalent relationship found.' );
						$equivalent_rel_id = $equivalent_relationship->id;
					}
					$equivalent_relationships[ $rel_id ] = $equivalent_rel_id;
				}

				$equivalent_rel_id = $equivalent_relationships[ $rel_id ];
				$this->debug( 'Equivalent relationship ID of %s is %s', $rel_id, $equivalent_rel_id );

				$jet_rel_table = static::get_prefixed_table_name( 'jet_rel_' . $equivalent_rel_id );

				if ( ! isset( $rels_cleared[ $equivalent_rel_id ] ) )
				{
					// Delete all existing relationships for the child.
					$this->delete_relationships( $bcd->new_post->ID, $looking_for, $equivalent_rel_id );
					$rels_cleared[ $equivalent_rel_id ] = true;
				}

				// Find the equivalent parent_object_id
				$other_object_key = ( $looking_for == 'child_object_id' ? 'parent_object_id' : 'child_object_id' );
				$other_object_id = $post_relationship->$other_object_key;
				$equivalent_other_object_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $other_object_id, get_current_blog_id() );

				$table_data = [
					'created' => date( 'Y-m-d H:i:s' ),
					$other_object_key => $equivalent_other_object_id,
					$looking_for => $bcd->new_post->ID,
					'rel_id' => $equivalent_rel_id,
					'parent_rel' => 0,	// No support for this yet. Nobody has asked.
				];
				$this->debug( 'Inserting relationship into table %s: %s', $jet_rel_table, $table_data );
				$wpdb->insert( $jet_rel_table, $table_data );
			}
		}
	}

	/**
		@brief		Delete the relationships for this post and type.
		@since		2024-07-13 17:56:06
	**/
	public function delete_relationships( $post_id, $type, $equivalent_rel_id = '' )
	{
		global $wpdb;
		if ( $equivalent_rel_id == '' )
		{
			// Find all relationship tables and delete all relationships from there.
			$jet_rel_table = static::get_prefixed_table_name( 'jet_rel_' );
			$query = sprintf( "SHOW TABLES LIKE '%s%%'", $jet_rel_table );
			$this->debug( $query );
			$results = $wpdb->get_col( $query );
			foreach( $results as $result )
			{
				$relationship_number = str_replace( $jet_rel_table, '', $result );
				$this->debug( 'Deleting realtionships from%s', $relationship_number );
				$this->delete_relationships( $post_id, $type, $relationship_number );
			}
			return;
		}

		$jet_rel_table = static::get_prefixed_table_name( 'jet_rel_' . $equivalent_rel_id );
		$query = sprintf( "DELETE FROM `%s` WHERE `%s` = '%s'",
			$jet_rel_table,
			$type,
			$post_id,
		);
		$this->debug( $query );
		$wpdb->query( $query );
	}

	/**
		@brief		Save this field.
		@since		2022-10-14 22:10:22
	**/
	public function save_field( $bcd, $field, $value = null )
	{
		$field = (object) $field;
		$field_name = $field->name;
		$value_given = true;

		if ( $value === null )
		{
			$value_given = false;		// This is repeaters.
			if ( isset( $field->term_id ) )
			{
				// Term
				$value = get_term_meta( $field->term_id, $field_name, true );
				$this->debug( 'Value for term %s field %s is %s', $field->term_id, $field_name, json_encode( $value ) );
			}
			else
			{
				// Post!
				$value = $bcd->custom_fields()->get_single( $field_name );
			}
		}

		if ( ! $value )
			return;

		$save = false;

		switch( $field->type )
		{
			case 'gallery':
				$save = true;

				if ( ! isset( $field->value_format ) )
					$field->value_format = 'id';

				switch( $field->value_format )
				{
					case 'both':
						$values = maybe_unserialize( $value );
						foreach( $values as $image_data )
							$bcd->try_add_attachment( $image_data[ 'id' ] );
						break;
					case 'url':
						// Try convert these URL to an ID
						$values = explode( ',', $value );
						$post_ids = [];
						foreach( $values as $value )
						{
							$post_id = attachment_url_to_postid( $value );
							if ( $post_id > 0 )
							{
								$bcd->try_add_attachment( $post_id );
								$this->debug( 'Found image URL %s as %s', $value, $post_id );
								$bcd->jetengine->collection( 'old_image_url' )->set( $value, $post_id );
								$post_ids []= $post_id;
							}
						}
						$value = $post_ids;
						break;
					// id
					default:
						$values = explode( ",", $value );
						foreach( $values as $image_id )
							$bcd->try_add_attachment( $image_id );
				}
			break;
			case 'media':
				$save = true;

				if ( ! isset( $field->value_format ) )
					$field->value_format = 'id';

				switch( $field->value_format )
				{
					case 'both':
						if ( $field->value_format == 'both' )
						{
							$image_data = maybe_unserialize( $value );
							$bcd->try_add_attachment( $image_data[ 'id' ] );
						}
						break;
					case 'url':
						// Try convert this URL to an ID
						$post_id = attachment_url_to_postid( $value );
						if ( $post_id > 0 )
						{
							$this->debug( 'Found image URL %s as %s', $value, $post_id );
							$bcd->jetengine->collection( 'old_image_url' )->set( $value, $post_id );
							$value = $post_id;
						}
						else
							break;
					// id
					default:
						if ( $bcd->try_add_attachment( $value ) )
							$this->debug( 'Added attachment %s in media field %s', $value, $field_name );
				}
			break;
			case 'posts':
				$save = true;
			break;
			case 'repeater':
				$values = unserialize( $value );
				$key = 'repeater-fields';
				foreach( $values as $value_index => $repeater_values )
				{
					foreach( $field->$key as $repeater_index => $repeater_field )
					{
						$repeater_field = (object) $repeater_field;
						$this->debug( 'Saving repeater field %s', $repeater_field );
						$this->save_field( $bcd, $repeater_field, $repeater_values[ $repeater_field->name ] );
					}
				}
				$save = true;
			break;
			case 'text':
				$preparse_action = new \threewp_broadcast\actions\preparse_content();
				$preparse_action->broadcasting_data = $bcd;
				$preparse_action->content = $value;
				$preparse_action->id = 'jetengine_' . $field->name;
				$preparse_action->execute();
				$save = true;
			break;
		}

		if ( ! $value_given && $save )
		{
			$field->old_value = $value;
			$this->debug( 'Saving %s %s: %s', $field->type, $field_name, $value );

			if ( isset( $field->term_id ) )
			{
				$bcd->jetengine_termmeta->collection( 'fields' )
					->collection( $field->term_id )
					->set( $field->id, $field );
			}
			else
			{
				$bcd->jetengine->collection( 'fields' )
					->set( $field->id, $field );
			}
		}
	}

	/**
		@brief		Save the relationships.
		@since		2022-10-10 18:00:22
	**/
	public function save_relationships( $bcd )
	{
		global $wpdb;
		$r = $bcd->jetengine->relationships;
		$jet_rel_default_table = static::get_prefixed_table_name( 'jet_rel_default' );
		$empty = true;

		foreach( [
			'child_object_id',
			'parent_object_id',
		] as $looking_for )
		{
			$col = $r->collection( 'looking_for' )
				->collection( $looking_for );

			$query = sprintf( "SELECT * FROM `%s` WHERE `%s` = '%s'",
				$jet_rel_default_table,
				$looking_for,
				$bcd->post->ID
			);
			$this->debug( $query );
			$results = $wpdb->get_results( $query );

			$this->debug( 'Results: %s', $results );

			foreach( $results as $result )
			{
				$empty = false;
				$query = sprintf( "SELECT * FROM `%s%s` WHERE `id` = '%s'",
					$wpdb->prefix,
					'jet_post_types',
					$result->rel_id
				);
				$this->debug( $query );
				$relationship = $wpdb->get_row( $query );

				// Sometimes, we encounter invalid rel_ids, so why save the relationship itself?
				if ( ! $relationship )
					continue;

				$col->collection( 'post_relationships' )->set( $result->_ID, $result );
				$col->collection( 'relationships' )->set( $result->rel_id, $relationship );
			}
		}

		if ( $empty )
		{
			$this->debug( 'No relationships found!' );
			$r->set( 'empty', true );
		}
	}
}