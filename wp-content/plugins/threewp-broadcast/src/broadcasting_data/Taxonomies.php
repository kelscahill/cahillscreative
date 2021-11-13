<?php

namespace threewp_broadcast\broadcasting_data;

/**
	@brief		Convenience class for handling taxonomies.
	@since		2017-07-10 17:01:02
**/
class Taxonomies
{
	/**
		@brief		The broadcasting data object.
		@since		2015-06-06 09:02:08
	**/
	public $broadcasting_data;

	/**
		@brief		Constructor.
		@since		2015-06-06 09:01:58
	**/
	public function __construct( $broadcasting_data )
	{
		$this->broadcasting_data = $broadcasting_data;
		// Convenience.
		$bcd = $this->broadcasting_data;

		if ( ! is_object( $bcd->taxonomy_data ) )
			$bcd->taxonomy_data = ThreeWP_Broadcast()->collection();

		// If the blacklist isn't set, then nothing is.
		if ( ! $bcd->taxonomy_data->has( 'taxonomy_term_meta' ) )
		{
			// Set up the blacklist and protectlist.
			foreach( [ 'blacklist', 'protectlist' ] as $list_type )
			{
				// Get the option.
				$key = 'taxonomy_term_' . $list_type;
				$option_value = ThreeWP_Broadcast()->get_site_option( $key );
				// Convert the option to collections.
				$lines = explode( "\n", $option_value );
				$lines = array_filter( $lines );
				foreach( $lines as $line )
				{
					$line = trim( $line );
					$columns = explode( ' ', $line );
					$columns = array_filter( $columns );

					// Each line MUST have 1 taxonomy, 1 term and at least 1 field.
					if ( count( $columns ) < 3 )
						continue;
					$taxonomy = array_shift( $columns );
					$term = array_shift( $columns );

					$meta_key_collection = $bcd->taxonomy_data
							->collection( 'taxonomy_term_meta' )
							->collection( $list_type )
							->collection( $taxonomy )
							->collection( $term );

					foreach( $columns as $column )
							$meta_key_collection->append( $column );
				}
			}
		}
	}

	/**
		@brief		Convenience method to also sync this taxonomy.
		@details	Used best during broadcasting_started.
		@deprecated	2018-08-30 13:26:38
		@since		2017-11-08 12:38:59
	**/
	public function also_sync( $post_type, $taxonomy )
	{
		// If no post type is specified, absolutely force syncing of the taxonomy.
		if ( ! $post_type )
			if ( isset( $this->broadcasting_data->parent_blog_taxonomies[ $taxonomy ] ) )
				unset( $this->broadcasting_data->parent_blog_taxonomies[ $taxonomy ] );

		$this->also_sync_taxonomy( [
			'post_type' => $post_type,
			'taxonomy' => $taxonomy,
		] );
		return $this;
	}

	/**
		@brief		Convenience method to also sync another taxonomy, but with more control than also_sync().
		@see		also_sync()
		@since		2018-08-30 13:26:38
	**/
	public function also_sync_taxonomy( $options )
	{
		$options = array_merge( [
			'broadcasting_data' => null,
			'blog_id' => 0,
			'post' => null,
			'post_type' => false,
			'post_id' => 0,
			'taxonomy' => '',
		], $options );

		// Objects are easier to reference.
		$options = (object) $options;

		$bc = ThreeWP_Broadcast();

		if ( isset( $this->broadcasting_data->already_also_synced_taxonomies ) )
		{
			if ( $this->broadcasting_data->already_also_synced_taxonomies->has( $options->taxonomy ) )
			{
				$bc->debug( 'Has already also_synced taxonomy %s', $options->taxonomy );
				return $this;
			}
		}
		else
			$this->broadcasting_data->already_also_synced_taxonomies = $bc->collection();
		$this->broadcasting_data->already_also_synced_taxonomies->set( $options->taxonomy, true );

		// Nothing set? Try to resync.
		if ( isset( $this->broadcasting_data->parent_blog_taxonomies[ $options->taxonomy ] ) )
			if ( count ( $this->broadcasting_data->parent_blog_taxonomies[ $options->taxonomy ][ 'terms' ] ) < 1 )
				unset( $this->broadcasting_data->parent_blog_taxonomies[ $options->taxonomy ] );

		if ( isset( $this->broadcasting_data->parent_blog_taxonomies[ $options->taxonomy ] ) )
		{
			$bc->debug( 'Not bothering to sync taxonomy <em>%s</em> for post type <em>%s</em> because it is already being synced.', $options->taxonomy, $options->post_type );
			return $this;
		}

		// Fetch the post.
		if ( $options->post_id > 0 )
		{
			if ( $options->blog_id > 0 )
				switch_to_blog( $options->blog_id );
			$options->post = get_post( $options->post_id );
			if ( $options->blog_id > 0 )
				restore_current_blog();
		}

		// Fetch the post type.
		if ( $options->post !== null )
			$options->post_type = $options->post->post_type;

		if ( ! $options->post_type )
		{
			// Find a post type that uses this taxonomy.
			$taxonomies = get_taxonomies( [], 'objects' );
			foreach( $taxonomies as $taxonomy_name => $taxonomy_data )
			{
				if ( $taxonomy_name != $options->taxonomy )
					continue;
				$options->post_type = reset( $taxonomy_data->object_type );
				break;
			}
		}

		$bc->debug( 'Also syncing taxonomy <em>%s</em> for post type <em>%s</em>.', $options->taxonomy, $options->post_type );

		$old_values = [];
		$values_to_save = [
			'add_new_taxonomies',
			'parent_post_id',
			'parent_blog_taxonomies',
			'parent_post_taxonomies',
			'post',
		];
		foreach( $values_to_save as $key )
			$old_values[ $key ] = $this->broadcasting_data->$key;

		// We need to store the taxonomy + terms of the post type.
		if ( $options->post === null )
			// Fake a post
			$options->post = (object)[
				'ID' => 0,
				'post_type' => $options->post_type,
				'post_status' => 'publish',
			];

		$this->broadcasting_data->add_new_taxonomies = true;
		$this->broadcasting_data->parent_post_id = -1;
		$this->broadcasting_data->post = $options->post;

		if ( $options->post_id < 1 )
			unset( $this->post->ID );		// This is so that collect_post_type_taxonomies returns ALL the terms, not just those from the non-existent post.

		$bc->collect_post_type_taxonomies( $this->broadcasting_data );

		$new_taxonomy_data = $this->broadcasting_data->parent_blog_taxonomies[ $options->taxonomy ];

		foreach( $values_to_save as $key )
			$this->broadcasting_data->$key = $old_values[ $key ];

		// Restore the blog taxonomies separately.
		if ( ! isset( $this->broadcasting_data->parent_blog_taxonomies[ $options->taxonomy ] ) )
		{
			$bc->debug( 'Not merging taxonomy data: %s', $options->taxonomy );
			$this->broadcasting_data->parent_blog_taxonomies[ $options->taxonomy ] = $new_taxonomy_data;
		}
		else
		{
			// Merge the old with the new.
			$bc->debug( 'Merging with old taxonomy data: %s', $options->taxonomy );
			$this->broadcasting_data->parent_blog_taxonomies[ $options->taxonomy ] = array_merge(
				$this->broadcasting_data->parent_blog_taxonomies[ $options->taxonomy ][ 'terms' ],
				$new_taxonomy_data[ 'terms' ]
			);
		}

		// Broadcast will primarily sync parent_post_taxonomies. If an empty parent_post_taxonomies is found, it will sync the equivalent parent_blog_taxonomies.
		if ( ! isset( $this->broadcasting_data->parent_post_taxonomies[ $options->taxonomy ] ) )
			$this->broadcasting_data->parent_post_taxonomies[ $options->taxonomy ] = [];

		return $this;
	}

	/**
		@brief		Checks whether a taxonomy + term + meta_key combo exist in the blacklist.
		@since		2017-07-10 17:13:49
	**/
	public function blacklist_has( $taxonomy_slug, $term_slug, $meta_key )
	{
		return $this->list_has( 'blacklist', $taxonomy_slug, $term_slug, $meta_key  );
	}

	/**
		@brief		Build a nice array of terms, good for debugging and taking up little space.
		@since		2021-10-10 23:18:57
	**/
	public function build_nice_terms_list( $terms )
	{
		$r = [];
		foreach( $terms as $term_id => $term )
		{
			$r[ $term_id ] = sprintf( '%s : %s : %s : %s',
				$term->name,
				$term->slug,
				json_encode( $term->description ),
				$term->parent
			);
		}
		ksort( $r );
		return $r;
	}


	/**
		@brief		Return the term index.
		@since		2021-10-11 20:30:09
	**/
	public function get_term_index()
	{
		if ( ! $this->broadcasting_data->taxonomy_data->has( 'term_index' ) )
		{
			$ti = new taxonomies\Term_Index();
			$this->broadcasting_data->taxonomy_data->set( 'term_index', $ti );
		}
		return $this->broadcasting_data->taxonomy_data->get( 'term_index' );
	}

	/**
		@brief		Return a collection of all used term IDs
		@since		2021-10-11 20:25:04
	**/
	public function get_used_terms()
	{
		return $this->broadcasting_data
			->taxonomy_data
			->collection( 'used_terms' );
	}

	/**
		@brief		Checks whether a taxonomy + term combo exist in the *list.
		@since		2017-07-10 17:13:49
	**/
	public function list_has( $list_type, $taxonomy_slug, $term_slug, $meta_key )
	{
		// Extract the list type.
		$ttm = $this->broadcasting_data
			->taxonomy_data
			->collection( 'taxonomy_term_meta' )
			->collection( $list_type );

		foreach( $ttm->to_array() as $ttm_taxonomy_slug => $ttm_terms )
		{
			// Does this slug match?
			if ( ! static::matches( $taxonomy_slug, $ttm_taxonomy_slug ) )
				continue;
			// Go through the terms and see if they match.
			foreach( $ttm_terms as $ttm_term_slug => $meta_keys )
			{
				// Does this slug match?
				if ( ! static::matches( $term_slug, $ttm_term_slug ) )
					continue;
				// And now look for a match in the metya keys.
				foreach( $meta_keys as $ttm_meta_key )
					if ( static::matches( $meta_key, $ttm_meta_key ) )
						return true;
			}
		}
		return false;
	}

	/**
		@brief		Mark this term used, and all its parents.
		@since		2021-10-11 20:23:06
	**/
	public function mark_parent_term_used( $term_id )
	{
		$this->use_term( $term_id );
		$ti = $this->get_term_index();
		$term = $ti->get( $term_id );
		if ( $term->parent > 0 )
			$this->mark_parent_term_used( $term->parent );
		return $this;
	}

	/**
		@brief		Mark the parent terms of used terms as used.
		@details	Without this, the parent terms will not be synced.
		@since		2021-10-11 19:35:06
	**/
	public function mark_parent_terms_used()
	{
		$ti = $this->get_term_index();
		foreach( $this->get_used_terms() as $term_id )
		{
			$term = $ti->get( $term_id );
			if ( ! $term )
				continue;
			if ( $term->parent < 1 )
				continue;
			$this->mark_parent_term_used( $term->parent );
		}
		return $this;
	}

	/**
		@brief		Does this needle exist in the haystack?
		@since		2017-07-12 06:57:23
	**/
	public static function matches( $haystack, $needle )
	{
		// No wildcard = straight match
		if ( strpos( $needle, '*' ) === false )
		{
			if ( $needle == $haystack )
				return true;
		}
		else
		{
			$preg = str_replace( '*', '.*', $needle );
			$preg = sprintf( '/%s/', $preg );
			preg_match( $preg, $haystack, $matches );
			if ( ( count( $matches ) == 1 ) && $matches[ 0 ] == $haystack )
				return true;
		}
		return false;
	}

	/**
		@brief		Checks whether a taxonomy + term + meta_key combo exist in the protectlist.
		@since		2017-07-10 17:13:49
	**/
	public function protectlist_has( $taxonomy_slug, $term_slug, $meta_key )
	{
		return $this->list_has( 'protectlist', $taxonomy_slug, $term_slug, $meta_key );
	}

	/**
		@brief		Remove all parent blog terms that are not marked as used.
		@since		2021-10-11 19:26:22
	**/
	public function prune_parent_blog_terms()
	{
		$bc = ThreeWP_Broadcast();
		$bc->debug( '%s terms have been marked as used.', $this->get_used_terms()->count() );
		foreach( $this->broadcasting_data->parent_blog_taxonomies as $taxonomy => $data )
		{
			foreach( $data[ 'terms' ] as $term_id => $term )
			{
				if ( $this->used_term( $term_id ) )
					continue;
				$bc->debug( 'Term %s is not used. Forgetting.', $term_id );
				unset( $this->broadcasting_data->parent_blog_taxonomies[ $taxonomy ][ 'terms' ][ $term_id ] );
			}
		}
		return $this;
	}

	/**
		@brief		Mark the term as "used".
		@since		2021-10-10 23:08:25
	**/
	public function use_term( $term_id )
	{
		$bc = ThreeWP_Broadcast();
		$bc->debug( 'Using term %s', $term_id );
		$this->broadcasting_data
			->taxonomy_data
			->collection( 'used_terms' )
			->set( $term_id, $term_id );
		return $this;
	}

	/**
		@brief		Marke the terms as "used".
		@see		use_term()
		@since		2021-10-22 23:08:07
	**/
	public function use_terms( $terms )
	{
		if ( ! is_array( $terms ) )
			$terms = [ $terms ];
		foreach( $terms as $term_id )
			$this->use_term( $term_id );
		return $this;
	}

	/**
		@brief		Is this term used?
		@since		2021-10-10 23:09:12
	**/
	public function used_term( $term_id )
	{
		return $this->broadcasting_data
			->taxonomy_data
			->collection( 'used_terms' )
			->has( $term_id );
	}
}
