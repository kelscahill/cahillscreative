<?php

namespace threewp_broadcast\premium_pack\classes\term_meta_items;

/**
	@brief		Base class for handling taxonomy term meta items.
	@since		2021-04-08 22:00:31
**/
class Term_Meta_Items
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_menu' );
		$this->add_action( 'threewp_broadcast_collect_post_type_taxonomies' );
		$this->add_action( 'threewp_broadcast_wp_update_term' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Admin
	// --------------------------------------------------------------------------------------------

	public function admin_menu_settings()
	{
		$form = $this->form2();
		$class_settings = $this->get_class_settings();
		$form->id( $class_settings->slug );

		$id_fields_setting = $this->get_site_option( 'id_fields', [] );

		$id_fields = $form->textarea( 'id_fields' )
			// Setting textarea input title
			->description( __( 'A list of taxonomy / term / keys. Wildcards OK.', 'threewp_broadcast' ) )
			// Setting textarea input label
			->label( __( 'ID fields', 'threewp_broadcast' ) )
			->placeholder( 'TAXONOMY_SLUG TERM_SLUG META_KEY' )
			->rows( 10, 40 )
			->trim()
			->value( implode( "\n", $id_fields_setting ) );

		$save = $form->primary_button( 'save' )
			->value( __( 'Save settings', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			foreach( [ 'id_fields' ] as $key )
			{
				$value = $$key->get_filtered_post_value();
				$values = $this->parse_textarea_lines( $value );
				foreach( $values as $index => $value )
					$values[ $index ] = trim( $value );
				$this->update_site_option( $key, $values );
			}

			$this->message( __( 'Settings saved!', 'threewp_broadcast' ) );
		}

		$r = $this->p( __( "Some taxonomy term meta keys can contain IDs that normally aren't updated when broadcasting to child blogs.", 'threewp_broadcast' ) );

		$r .= $this->p( __( "Enter the names of the meta fields in the text box to tell Broadcast that the IDs need to be translated into their equivalent IDs on each child blog. Specify wildcards with an asterisk. You can use most regexps also, as long as you include an asterisk somewhere.", 'threewp_broadcast' ) );

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		$site_options = $this->site_options();
		if ( count( $site_options[ 'id_fields' ] ) > 0 )
		{
			$r .= $this->p( __( "Some examples:", 'threewp_broadcast' ) );
			$r .= $this->p( "<code>" . implode( "<br/>", $site_options[ 'id_fields' ] ) . "</code>" );
		}

		echo $r;
	}

	public function admin_menu_tabs()
	{
		$tabs = $this->tabs();

		$name = sprintf( '%s %s',
			$this->get_class_settings()->long_name,
			__( 'Settings', 'threewp_broadcast' )
		);

		$tabs->tab( 'settings' )
			->callback_this( 'admin_menu_settings' )
			// Tab name for add-on settings
			->name( $name );

		echo $tabs->render();
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Syncing taxonomies? Remember the variation swatch images.
		@since		2016-08-27
	**/
	public function threewp_broadcast_collect_post_type_taxonomies( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->term_meta_items ) )
			$bcd->term_meta_items = ThreeWP_Broadcast()->collection();

		$id_fields = static::load_id_fields();

		$o = (object)[];
		$o->broadcasting_data = $bcd;
		$o->id_fields = static::load_id_fields();
		$o->type = 'add';
		$o->array = $bcd->parent_blog_taxonomies;
		$this->process_array( $o );
	}

	/**
		@brief		Hide the premium pack info.
		@since		20131030
	**/
	public function threewp_broadcast_menu( $action )
	{
		$class_settings = $this->get_class_settings();
		$action->menu_page
			->submenu( $class_settings->slug )
			->callback_this( 'admin_menu_tabs' )
			// Menu item for menu
			->menu_title( $class_settings->short_name )
			// Page title for menu
			->page_title( $class_settings->long_name );
	}

	/**
		@brief		Update the term.
		@since		2021-04-11 21:37:08
	**/
	public function threewp_broadcast_wp_update_term( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->term_meta_items ) )
			return;

		ThreeWP_Broadcast()->copy_attachments_to_child( $bcd );

		$o = (object)[];
		$o->broadcasting_data = $bcd;
		$o->multiple = [];
		$o->id_fields = static::load_id_fields();
		$o->type = 'replace';

		$o->array =
		[
			$action->new_term->taxonomy =>
			[
				'terms' =>
				[
					$action->new_term->term_id => $action->new_term,
				],
			],
		];

		$this->debug( 'Process meta array.' );
		$this->process_array( $o );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Add the IDs found in the custom field.
		@since		2019-05-31 08:42:57
	**/
	public function add_ids( $options, $ids, $key )
	{
	}

	/**
		@brief		Return the unique settings for this class.
		@since		2019-05-31 09:08:20
	**/
	public function get_class_settings()
	{
		return (object)[
			'slug' => 'broadcast_term_meta_items',
			'long_name' => 'Broadcast Term Meta Items',
			'short_name' => 'Term Meta Items',
		];
	}

	/**
		@brief		Return a slug unique for this called class.
		@since		2019-05-31 09:06:53
	**/
	public function get_class_slug()
	{
		return sanitize_title( get_called_class() );
	}

	/**
		@brief		Load the ID fields object.
		@since		2021-04-11 19:59:17
	**/
	public function load_id_fields()
	{
		$id_fields = new ID_Fields();
		$id_fields->load( $this->get_site_option( 'id_fields', [] ) );
		return $id_fields;
	}

	/**
		@brief		Parses a textarea into an array of unique lines.
		@since		2014-04-19 23:55:38
	**/
	public function parse_textarea_lines( $text )
	{
		$lines = array_filter( explode( "\n", $text ) );
		$lines = array_flip( $lines );
		$lines = array_flip( $lines );
		return $lines;
	}

	public function process_array( $options )
	{
		// Convenience.
		$bcd = $options->broadcasting_data;
		$id_fields = $options->id_fields;

		foreach( $options->array as $taxonomy_slug => $taxonomy_data )
		{
			if ( ! $id_fields->has_taxonomy( $taxonomy_slug ) )
				continue;

			// Are we interested in this taxonomy?
			$terms = $taxonomy_data[ 'terms' ];

			// Get all of the fields for all terms
			foreach( $terms as $term )
			{
				if ( ! $id_fields->has_taxonomy_term( $taxonomy_slug, $term->slug ) )
					continue;

				$term_id = $term->term_id;
				$metas = get_term_meta( $term_id );

				$match = false;
				foreach( $metas as $meta_key => $value )
				{
					if ( count( $value ) == 1 )
						$value = reset( $value );
					if ( ! $id_fields->has_taxonomy_term_meta( $taxonomy_slug, $term->slug, $meta_key ) )
						continue;
					$match = true;

					if ( $match )
					{
						$multiple = is_array( $value );
						$possible_subvalues = $value;

						// Key matches. Try to extract as much information from the value as possible.
						// Convert the value to an array, if it is not already one, in order to simplify handling.
						if ( ! is_array( $possible_subvalues ) )
							$possible_subvalues = [ $possible_subvalues ];

						$ids = [];
						foreach( $possible_subvalues as $single_key => $single_value )
						{
							$original_value = $single_value;

							$unserialized = maybe_unserialize( $single_value );
							if ( is_array( $unserialized ) )
								$single_value = $unserialized;
							else
								$single_value = [ $single_value ];
							// Extract as many IDs as possible.
							foreach( $single_value as $maybe_id )
								$ids = array_merge( $ids, preg_split( '/[^0-9]/', $maybe_id ) );
						}

						if ( $options->type == 'add' )
						{
							$this->debug( 'Adding ids %s', $ids );
							$this->add_ids( $options, $ids, $meta_key );
						}

						if ( $options->type == 'replace' )
						{
							$this->debug( 'Beginning replace of %s', $meta_key );

							delete_term_meta( $term_id, $meta_key );

							$new_ids = $this->replace_ids( $options, $ids, $ids );
							foreach( $new_ids as $new_id )
							{
								$this->debug( 'Inserting new value for %s: %s', $meta_key, $new_id );
								add_term_meta( $term_id, $meta_key, $new_id);
							}
						}
					}
				}
			}
		}
	}

	/**
		@brief		Replace a single ID.
		@since		2019-05-31 08:54:27
	**/
	public function replace_id( $bcd, $id )
	{
		return $id;
	}

	/**
		@brief		Replace the IDs found in the meta.
		@since		2019-05-31 08:42:57
	**/
	public function replace_ids( $options, $ids, $original_value )
	{
		$bcd = $options->broadcasting_data;
		$new_value = $original_value;
		foreach( $ids as $id )
		{
			$new_id = $this->replace_id( $bcd, $id );
			if ( ! $new_id )
				continue;
			$new_value = preg_replace( '/' . $id . '/', $new_id, $new_value, 1 );
			$this->debug( 'New value for %s is %s', $id, $new_id );
		}
		return $new_value;
	}

	/**
		@brief		The site options we are expeciting to use.
		@since		2019-05-31 09:24:58
	**/
	public function site_options()
	{
		return array_merge( parent::site_options(), [
			'id_fields' => [
			],					// Array of custom fields that are expected to contain an ID.
		] );
	}
}
