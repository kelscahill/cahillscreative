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

		// Nothing set? Try to resync.
		if ( isset( $this->broadcasting_data->parent_blog_taxonomies[ $options->taxonomy ] ) )
			if ( count ( $this->broadcasting_data->parent_blog_taxonomies[ $options->taxonomy ][ 'terms' ] ) < 1 )
				unset( $this->broadcasting_data->parent_blog_taxonomies[ $options->taxonomy ] );

		if ( isset( $this->broadcasting_data->parent_blog_taxonomies[ $options->taxonomy ] ) )
			return ThreeWP_Broadcast()->debug( 'Not bothering to sync taxonomy <em>%s</em> for post type <em>%s</em> because it is already being synced.', $options->taxonomy, $options->post_type );

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

		ThreeWP_Broadcast()->debug( 'Also syncing taxonomy <em>%s</em> for post type <em>%s</em>.', $options->taxonomy, $options->post_type );

		// We need to store the taxonomy + terms of the post type.
		if ( $options->post === null )
			// Fake a post
			$options->post = (object)[
				'ID' => 0,
				'post_type' => $options->post_type,
				'post_status' => 'publish',
			];

		// And now collect the taxonomy info for the post type.
		$post_bcd = new \threewp_broadcast\broadcasting_data( [
			'parent_post_id' => -1,
			'post' => $options->post,
		] );
		$post_bcd->add_new_taxonomies = true;

		if ( $options->post_id < 1 )
			unset( $post_bcd->post->ID );		// This is so that collect_post_type_taxonomies returns ALL the terms, not just those from the non-existent post.

		ThreeWP_Broadcast()->collect_post_type_taxonomies( $post_bcd );

		// Copy the collected taxonomy data.
		$this->broadcasting_data->parent_blog_taxonomies[ $options->taxonomy ] = $post_bcd->parent_blog_taxonomies[ $options->taxonomy ];

		// Broadcast will primarily sync parent_post_taxonomies. If an empty parent_post_taxonomies is found, it will sync the equivalent parent_blog_taxonomies.
		if ( ! isset( $this->broadcasting_data->parent_post_taxonomies[ $options->taxonomy ] ) )
			$this->broadcasting_data->parent_post_taxonomies[ $options->taxonomy ] = [];
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
}
