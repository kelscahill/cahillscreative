<?php

class Meow_WPMC_Engine {
	private $core;
	private $admin;

	function __construct( $core, $admin ) {
		$this->core = $core;
		$this->admin = $admin;
	}

	/*
		STEP 1: Parse the content, and look for references
	*/

	/**
	 * Returns the posts to check the references
	 * @param int $offset Negative number means no limit
	 * @param int $size   Negative number means no limit
	 * @return NULL|array
	 */
	function get_posts_to_check( $offset = -1, $size = -1 ) {
		global $wpdb;
		$r = null;

		// Maybe we could avoid to check more post_types.
		// SELECT post_type, COUNT(*) FROM `wp_posts` GROUP BY post_type
		$q = <<<SQL
SELECT p.ID FROM $wpdb->posts p
WHERE p.post_status NOT IN ('inherit', 'trash', 'auto-draft')
AND p.post_type NOT IN ('attachment', 'shop_order', 'shop_order_refund', 'nav_menu_item', 'revision', 'auto-draft', 'wphb_minify_group', 'customize_changeset', 'oembed_cache', 'nf_sub', 'jp_img_sitemap')
AND p.post_type NOT LIKE 'dlssus_%'
AND p.post_type NOT LIKE 'ml-slide%'
AND p.post_type NOT LIKE '%acf-%'
AND p.post_type NOT LIKE '%edd_%'
SQL;
		$q .= " ORDER BY p.ID ASC";
		if ( $offset >= 0 && $size >= 0 ) {
			$q .= " LIMIT %d, %d";
			$r = $wpdb->get_col( $wpdb->prepare( $q, $offset, $size ) );

		} else // No limit
			$r = $wpdb->get_col( $q );

		return $r;
	}

	/**
	 * Returns the count of posts to check (memory-efficient alternative to count(get_posts_to_check()))
	 * @return int
	 */
	function count_posts_to_check() {
		global $wpdb;

		$q = <<<SQL
SELECT COUNT(p.ID) FROM $wpdb->posts p
WHERE p.post_status NOT IN ('inherit', 'trash', 'auto-draft')
AND p.post_type NOT IN ('attachment', 'shop_order', 'shop_order_refund', 'nav_menu_item', 'revision', 'auto-draft', 'wphb_minify_group', 'customize_changeset', 'oembed_cache', 'nf_sub', 'jp_img_sitemap')
AND p.post_type NOT LIKE 'dlssus_%'
AND p.post_type NOT LIKE 'ml-slide%'
AND p.post_type NOT LIKE '%acf-%'
AND p.post_type NOT LIKE '%edd_%'
SQL;
		return (int) $wpdb->get_var( $q );
	}

	// Parse the posts for references (based on $limit and $limitsize for paging the scan)
	function extractRefsFromContent( $limit, $limitsize, &$message = '', $post_id = null, &$processed = null ) {
		$processed = 0;
		$method = $this->core->current_method;


		// Check content is a different option depending on the method
		$check_content = false;
		if ( $method === 'media' || $method === 'duplicates' ) {
			$check_content = $this->core->get_option( 'content' );
		}
		else if ( $method === 'files' ) {
			$check_content = $this->core->get_option( 'filesystem_content' );
		}

		if ( $method == 'media' && !$check_content ) {
			$message = __( "Skipped, as Content is not selected.", 'media-cleaner' );
			return true;
		}

		if ( $method == 'files' && !$check_content ) {
			$message = __( "Skipped, as Content is not selected.", 'media-cleaner' );
			return true;
		}

		// Initialize the parsers
		$this->core->timeout_check_start( $limitsize );
		$this->core->safe_do_action( 'wpmc_initialize_parsers' );

		$posts = $post_id !== null ? [ $post_id ] : $this->get_posts_to_check( $limit, $limitsize );

		// Only at the beginning, check the Widgets and the Scan Once in the Parsers
		if ( empty( $limit ) ) {
			$this->core->log( "🏁 Extracting refs from content..." );
			//if ( get_option( 'wpmc_widgets', false ) ) {
				global $wp_registered_widgets;
				$syswidgets = is_array( $wp_registered_widgets ) ? $wp_registered_widgets : array();
				$active_widgets = get_option( 'sidebars_widgets' );
				$active_widgets = is_array( $active_widgets ) ? $active_widgets : array();
				foreach ( $active_widgets as $sidebar_name => $widgets ) {
					if ( $sidebar_name != 'wp_inactive_widgets' && !empty( $widgets ) && is_array( $widgets ) ) {
						foreach ( $widgets as $key => $widget ) {
							if ( isset( $syswidgets[ $widget ] ) ) $this->core->safe_do_action( 'wpmc_scan_widget', $syswidgets[ $widget ] );
						}
					}
				}
				$this->core->safe_do_action( 'wpmc_scan_widgets' );
			//}
			$this->core->safe_do_action( 'wpmc_scan_once' );

			
		}

		$is_debug = $this->core->is_debug();
		$yielded = false;

		foreach ( $posts as $post ) {
			if ( $this->core->timeout_should_yield() ) {
				$yielded = true;
				break;
			}
			$this->core->timeout_check();

			// Debug logging for timeout detection
			if ( $is_debug ) {
				$post_obj = get_post( $post );
				$post_type = $post_obj ? $post_obj->post_type : 'unknown';
				$post_title = $post_obj ? substr( $post_obj->post_title, 0, 50 ) : 'no title';
				$start_time = microtime( true );
				$this->core->log( "🔍 Processing post ID: $post | Type: $post_type | Title: $post_title" );
			}

			// Check content
			if ( $check_content ) {
				$this->core->safe_do_action( 'wpmc_scan_postmeta', $post );
				$html = get_post_field( 'post_content', $post );
				$this->core->safe_do_action( 'wpmc_scan_post', $html, $post );
			}

			// Extra scanning methods
			// do_action( 'wpmc_scan_extra', $post );

			if ( $is_debug ) {
				$elapsed_ms = round( ( microtime( true ) - $start_time ) * 1000, 2 );
				$this->core->log( "✓ Completed post ID: $post in {$elapsed_ms}ms" );
			}

			$this->core->timeout_check_additem();
			$processed++;
		}

		// Write the references found (and cached) by the parsers
		$this->core->write_references();
		$progress_phase = $post_id !== null ? 'extractReferencesFromContentPartial' : 'extractReferencesFromContent';
		$this->core->save_progress( $progress_phase, array(
			'type' => 'content',
			'limit' => $limit,
			'limitSize' => $limitsize,
			'next' => $limit + $processed,
			'processed' => $processed,
			'postId' => $post_id,
		) );

		$finished = !$yielded && $processed === count( $posts ) && ( $post_id !== null || count( $posts ) < $limitsize );
		if ( $finished && $post_id === null )
		{
			$this->core->log();
			$this->core->save_progress( 'extractReferencesFromContent_finished' );
		}

		$elapsed = $this->core->timeout_get_elapsed();
		$message = sprintf(
			// translators: %1$d is number of posts, %2$s is time in milliseconds
			__( "Extracted references from %1\$d posts in %2\$s.", 'media-cleaner' ), $processed, $elapsed
		);
		return $finished;
	}

	function extractRefsFromThumbnails( $limit, $limitsize, &$message = '', $post_id = null, &$processed = null ) {
		$medias = $this->get_media_entries( $limit, $limitsize, false );
		$processed = 0;
		$yielded = false;
		$this->core->timeout_check_start( count( $medias ) );

		// Get the sizes that should be marked as issues
		$force_issue_sizes = $this->core->get_option( 'thumbnail_force_issues' );
		if ( !is_array( $force_issue_sizes ) ) {
			$force_issue_sizes = [];
		}

		foreach ( $medias as $media_id ) {
			if ( $this->core->timeout_should_yield() ) {
				$yielded = true;
				break;
			}
			$this->core->timeout_check();
			$file = get_attached_file( $media_id );
			$meta = wp_get_attachment_metadata( $media_id );

			if ( ! is_array( $meta ) || ! isset( $meta['sizes'] ) ) {
				$meta = array( 'sizes' => array() );
			}

			// Get the current registered image sizes
			$needed_sizes = wp_get_registered_image_subsizes();
			
			foreach ( array_keys( $needed_sizes ) as $size ) {
				$image_path = path_join( dirname( $file ), $meta['sizes'][ $size ]['file'] ?? '' );
				$file_exists = isset( $meta['sizes'][ $size ] ) && file_exists( $image_path ) && filesize( $image_path ) > 0;
				if ( !$file_exists ) {
					continue;
				}

				$image_path = $this->core->clean_uploaded_filename( $image_path );

				// Check if this size should be marked as an issue instead of a reference
				if ( in_array( $size, $force_issue_sizes ) ) {
					// Mark as issue instead of reference
					$this->core->add_issue( $image_path, 'FORCED_THUMBNAIL_ISSUE', $media_id );
				} else {
					// Add a reference for generated thumbnail
					$this->core->add_reference_url(
						$image_path,
						"{OG_THUMB}" . $size,
						$media_id, ['force_cache' => true ]
					);
				}
			}
			$this->core->timeout_check_additem();
			$processed++;
		}

		$this->core->write_references();
		$this->core->save_progress( 'extractReferencesFromThumbnails', array(
			'type' => 'thumbnails',
			'limit' => $limit,
			'limitSize' => $limitsize,
			'next' => $limit + $processed,
			'processed' => $processed,
		) );

		$finished = !$yielded && $processed === count( $medias ) && count( $medias ) < $limitsize;

		if ( $finished )
		{
			$this->core->save_progress( 'extractReferencesFromThumbnails_finished' );
			$this->core->log("Finished extracting refs from Thumbnails.");
		}
		$message = sprintf( __( 'Analyzed %d media thumbnail sets.', 'media-cleaner' ), $processed );

		return $finished;
	}


	// For each media, let's get a hash of the file and add it as a reference
	function extractRefsFromDuplicates( $limit, $limitsize, &$message = '', $post_id = null, &$processed = null ) {
		$medias = $this->get_media_entries( $limit, $limitsize, false );
		$processed = 0;
		$yielded = false;
		$this->core->timeout_check_start( count( $medias ) );
		foreach ( $medias as $media ) {
			if ( $this->core->timeout_should_yield() ) {
				$yielded = true;
				break;
			}
			$this->core->timeout_check();
			$full_path = get_attached_file( $media );
			if ( !$full_path || !is_file( $full_path ) || !is_readable( $full_path ) || is_link( $full_path ) ) {
				throw new RuntimeException( sprintf( __( 'Duplicate analysis could not read the original file for Media #%d.', 'media-cleaner' ), $media ) );
			}
			try {
				$hash = $this->hash_file_safely( $full_path, $media );
			}
			catch ( Meow_WPMC_Transient_Exception $e ) {
				if ( $processed === 0 ) {
					throw new RuntimeException( sprintf( __( 'Media #%d is too large or slow to hash within this server request budget.', 'media-cleaner' ), $media ), 0, $e );
				}
				$yielded = true;
				break;
			}
			$path = $this->core->clean_uploaded_filename( $full_path );
			$this->core->add_reference_url( $path, 'HASH:' . $hash, $media, array( 'force_cache' => true ) );
			$this->core->timeout_check_additem();
			$processed++;
		}

		$this->core->write_references();
		$this->core->save_progress( 'extractReferencesFromDuplicates', array(
			'type' => 'duplicates',
			'limit' => $limit,
			'limitSize' => $limitsize,
			'next' => $limit + $processed,
			'processed' => $processed,
		) );

		$finished = !$yielded && $processed === count( $medias ) && count( $medias ) < $limitsize;
		if ( $finished )
		{
			$this->core->save_progress( 'extractReferencesFromDuplicates_finished' );
			$this->core->log("Finished extracting refs from Duplicates.");
		}
		$message = sprintf( __( 'Hashed %d media files.', 'media-cleaner' ), $processed );

		return $finished;
	}

	private function hash_file_safely( $path, $media_id ) {
		$handle = @fopen( $path, 'rb' );
		if ( !$handle ) throw new RuntimeException( sprintf( __( 'Duplicate analysis could not open the original file for Media #%d.', 'media-cleaner' ), $media_id ) );
		$context = hash_init( 'sha256' );
		try {
			while ( !feof( $handle ) ) {
				$this->core->timeout_check();
				$chunk = fread( $handle, 1024 * 1024 );
				if ( $chunk === false ) throw new RuntimeException( sprintf( __( 'Duplicate analysis could not read the original file for Media #%d.', 'media-cleaner' ), $media_id ) );
				if ( $chunk !== '' ) hash_update( $context, $chunk );
			}
		}
		finally {
			fclose( $handle );
		}
		return hash_final( $context );
	}

	// Parse the posts for references (based on $limit and $limitsize for paging the scan)
	function extractRefsFromLibrary( $limit, $limitsize, &$message = '', $post_id = null, &$processed = null ) {
		$processed = 0;
		$method = $this->core->current_method;
		if ( $method == 'media' ) {
			$message = __( "Skipped, as it is not needed for the Media Library method.", 'media-cleaner' );
			return true;
		}
		$check_library = $this->core->get_option( 'media_library' );
		if ( !$check_library ) {
			$message = __( "Skipped, as Media Library is not selected.", 'media-cleaner' );
			return true;
		}

		$medias = $this->get_media_entries( $limit, $limitsize, false, $post_id );

		// Only at the beginning
		if ( empty( $limit ) ) {
			$this->core->log( "🏁 Extracting refs from Media Library..." );
		}

		$this->core->timeout_check_start( count( $medias ) );
		$yielded = false;
		foreach ( $medias as $media ) {
			if ( $this->core->timeout_should_yield() ) {
				$yielded = true;
				break;
			}
			$this->core->timeout_check();
			// Check the media
			$paths = $this->core->get_paths_from_attachment( $media );
			$this->core->add_reference_url( $paths, 'MEDIA LIBRARY' );
			$this->core->timeout_check_additem();
			$processed++;
		}

		// Write the references found (and cached) by the parsers
		$this->core->write_references();
		$progress_phase = $post_id !== null ? 'extractReferencesFromLibraryPartial' : 'extractReferencesFromLibrary';
		$this->core->save_progress( $progress_phase, array(
			'type' => 'library',
			'limit' => $limit,
			'limitSize' => $limitsize,
			'next' => $limit + $processed,
			'processed' => $processed,
			'postId' => $post_id,
		) );

		$finished = !$yielded && $processed === count( $medias ) && ( $post_id !== null || count( $medias ) < $limitsize );
		if ( $finished && $post_id === null )
		{
			$this->core->save_progress( 'extractReferencesFromLibrary_finished' );
			$this->core->log("Finished extracting refs from Media Library.");
		}
		$elapsed = $this->core->timeout_get_elapsed();
		$message = sprintf( __( "Extracted references from %d medias in %s.", 'media-cleaner' ), $processed, $elapsed );
		return $finished;
	}

	/*
		STEP 2: List the media entries (or files)
	*/

	function get_hash_duplicates( $offset = 0, $limit = 100 ) {
		// Get the hashes from the referenes ( unique ones ) 
		global $wpdb;
		$run_id = $this->core->get_run_id();
		$table = $wpdb->prefix . 'mclean_refs';
		$hashes = $wpdb->get_col( $wpdb->prepare(
			"SELECT originType FROM $table
			WHERE run_id = %d AND originType LIKE 'HASH:%%'
			GROUP BY originType HAVING COUNT(DISTINCT mediaUrl) > 1
			ORDER BY originType ASC LIMIT %d, %d",
			$run_id,
			max( 0, (int) $offset ),
			max( 1, min( 500, (int) $limit ) )
		) );

		return $hashes;	
	}

	// Get files in /uploads (if path is null, the root of /uploads is returned)
	function get_files( $path = null, $offset = 0, $limit = -1 ) {
		$files = apply_filters( 'wpmc_list_uploaded_files', null, $path, $offset, $limit );
		return $files ? $files : array();
	}

	function get_file_page_info( $fallback_count = 0, $limit = -1 ) {
		$default = array(
			'scanned' => $fallback_count,
			'finished' => $limit < 1 || $fallback_count < $limit,
			'skipped' => array(),
		);
		return apply_filters( 'wpmc_file_page_info', $default );
	}

	/**
	 * Returns the media entries to check the references
	 * @param int $offset Negative number means no limit
	 * @param int $size   Negative number means no limit
	 * @param bool $unattachedOnly
	 * @param int|null $post_parent_id If this is set with $unattachedOnly, this is ignored. ($unattachedOnly is prioritized)
	 * @return NULL|array
	 */
	function get_media_entries( $offset = -1, $size = -1, $unattachedOnly = false, $post_parent_id = null ) {
		global $wpdb;
		$r = null;

		$extraAnd = $unattachedOnly
			? "AND p.post_parent = 0"
			: ( $post_parent_id !== null
				? $wpdb->prepare( "AND p.post_parent = %d", $post_parent_id )
				: '' );

		$q = <<<SQL
SELECT p.ID FROM $wpdb->posts p
WHERE p.post_status = 'inherit'
$extraAnd
AND p.post_type = 'attachment'
SQL;
		if ( $this->core->get_option( 'images_only' ) ) {
			// Get only media entries which are images
			$q .= " AND p.post_mime_type IN ( 'image/jpeg', 'image/gif', 'image/png', 'image/webp',
				'image/bmp', 'image/tiff', 'image/x-icon', 'image/svg' )";
		}
		$q .= " ORDER BY p.ID ASC";

		if ( $offset >= 0 && $size >= 0 ) {
			$q .= " LIMIT %d, %d";
			$r = $wpdb->get_col( $wpdb->prepare( $q, $offset, $size ) );

		} else // No limit
			$r = $wpdb->get_col( $q );

		return $r;
	}

	/**
	 * Returns the count of media entries (memory-efficient alternative to count(get_media_entries()))
	 * @param bool $unattachedOnly
	 * @return int
	 */
	function count_media_entries( $unattachedOnly = false ) {
		global $wpdb;

		$extraAnd = $unattachedOnly ? "AND p.post_parent = 0" : '';

		$q = <<<SQL
SELECT COUNT(p.ID) FROM $wpdb->posts p
WHERE p.post_status = 'inherit'
$extraAnd
AND p.post_type = 'attachment'
SQL;
		if ( $this->core->get_option( 'images_only' ) ) {
			$q .= " AND p.post_mime_type IN ( 'image/jpeg', 'image/gif', 'image/png', 'image/webp',
				'image/bmp', 'image/tiff', 'image/x-icon', 'image/svg' )";
		}

		return (int) $wpdb->get_var( $q );
	}

	/*
		STEP 3: Check the media entries (or files) against the references
	*/

	function check_duplicates( $hash ) {
		// Check if the hash exists in the database
		global $wpdb;
		$table_name_refs = $wpdb->prefix . "mclean_refs";
		$run_id = $this->core->get_run_id();

		$request = $wpdb->prepare(
			"SELECT mediaUrl, MIN(mediaId) AS mediaId FROM $table_name_refs
			WHERE run_id = %d AND originType = %s AND mediaUrl IS NOT NULL
			GROUP BY mediaUrl
			ORDER BY mediaUrl ASC",
			$run_id,
			$hash
		);

		$medias = $wpdb->get_results( $request );

		if( count( $medias ) <= 1 ) {
			// No issue
			return false;
		}

		// Protect one deterministic canonical copy from cleanup.
		array_shift( $medias );
		foreach ( $medias as $media ) {
			$media_id = (int) $media->mediaId;
			$media_url = (string) $media->mediaUrl;
			$referenced = $wpdb->get_var( $wpdb->prepare(
				"SELECT 1 FROM $table_name_refs
				WHERE run_id = %d AND originType NOT LIKE 'HASH:%%'
				AND ((%d > 0 AND mediaId = %d) OR (mediaUrl_hash = %s AND mediaUrl = %s))
				LIMIT 1",
				$run_id,
				$media_id,
				$media_id,
				hash( 'sha256', $media_url ),
				$media_url
			) );
			if ( $referenced ) {
				continue;
			}
			$this->core->add_issue( $media_url, 'DUPLICATE' );
		}

		return true;

	}

	function check_media( $media ) {
		return $this->core->check_media( $media );
	}

	function check_file( $file ) {
		// Basically, wpmc_check_file returns either true if it's used, or
		// the codename of the issue.
		$issue = apply_filters( 'wpmc_check_file', false, $file );
		$used = $issue === true;
		if ( !$used ) {
			$this->core->add_issue( $file, is_string( $issue ) ? $issue : 'NO_CONTENT' );
		}
		return $used;
	}

}

?>
