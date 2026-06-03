<?php

namespace WPForms\Pro\Admin\Entries\Import;

use WPForms\Helpers\Transient;
use WPForms\Pro\Admin\Entries\Import\Source\AbstractSource;

/**
 * Import session backed by a WPForms transient.
 *
 * @since 1.10.1
 */
class ImportSession {

	/**
	 * Transient key prefix.
	 *
	 * @since 1.10.1
	 */
	public const KEY_PREFIX = 'import_session_';

	/**
	 * Session TTL in seconds.
	 *
	 * @since 1.10.1
	 */
	private const TTL = DAY_IN_SECONDS;

	/**
	 * Unique session identifier.
	 *
	 * @since 1.10.1
	 *
	 * @var string
	 */
	private $request_id;

	/**
	 * Session data.
	 *
	 * @since 1.10.1
	 *
	 * @var array
	 */
	private $data = [];

	/**
	 * Constructor.
	 *
	 * @since 1.10.1
	 *
	 * @param string $request_id Unique session ID. Pass an empty string to generate a new one.
	 */
	public function __construct( string $request_id = '' ) {

		$this->request_id = ! empty( $request_id ) ? $request_id : self::generate_request_id();
	}

	/**
	 * Generate a unique request ID.
	 *
	 * @since 1.10.1
	 *
	 * @return string
	 */
	private static function generate_request_id(): string {

		return wp_generate_uuid4();
	}

	/**
	 * Create and persist a new session.
	 *
	 * @since 1.10.1
	 *
	 * @param AbstractSource $source            Source object.
	 * @param int            $form_id           Target WPForms form ID.
	 * @param string         $source_identifier Slug or file extension identifying the source (e.g. 'cf7', 'csv').
	 *
	 * @return self
	 */
	public static function create( AbstractSource $source, int $form_id, string $source_identifier ): self {

		$source->validate();

		$request_id = self::generate_request_id();

		Transient::set(
			self::KEY_PREFIX . $request_id,
			[
				'source_identifier' => $source_identifier,
				'source_args'       => $source->get_args(),
				'form_id'           => $form_id,
				'cursor'            => 0,
				'total'             => $source->get_total(),
				'imported'          => 0,
				'source_fields'     => $source->get_fields(),
				'status'            => 'active',
				'errors'            => [],
			],
			self::TTL
		);

		$session = new self( $request_id );

		$session->load();

		return $session;
	}

	/**
	 * Load an existing session from the transient store.
	 *
	 * @since 1.10.1
	 *
	 * @return bool True if a session was found and loaded.
	 */
	public function load(): bool {

		$data = Transient::get( self::KEY_PREFIX . $this->request_id );

		if ( ! is_array( $data ) ) {
			return false;
		}

		$this->data = $data;

		return true;
	}

	/**
	 * Update cursor and imported count after a processed chunk.
	 *
	 * @since 1.10.1
	 *
	 * @param int   $cursor   New cursor position.
	 * @param int   $imported Number of entries successfully imported in the last chunk.
	 * @param array $errors   Per-entry errors from the last chunk.
	 */
	public function advance( int $cursor, int $imported, array $errors ): void {

		$this->data['cursor']    = $cursor;
		$this->data['imported'] += $imported;
		$this->data['errors']    = array_merge( $this->data['errors'], $errors );

		Transient::set( self::KEY_PREFIX . $this->request_id, $this->data, self::TTL );
	}

	/**
	 * Mark the session as failed.
	 *
	 * @since 1.10.1
	 */
	public function fail(): void {

		$this->data['status'] = 'failed';

		Transient::set( self::KEY_PREFIX . $this->request_id, $this->data, self::TTL );
	}

	/**
	 * Delete the session transient.
	 *
	 * @since 1.10.1
	 */
	public function delete(): void {

		Transient::delete( self::KEY_PREFIX . $this->request_id );
	}

	/**
	 * Return the unique session request ID.
	 *
	 * @since 1.10.1
	 *
	 * @return string
	 */
	public function get_request_id(): string {

		return $this->request_id;
	}

	/**
	 * Return a session data value by key.
	 *
	 * @since 1.10.1
	 *
	 * @param string $key           Data key.
	 * @param mixed  $default_value Default value if the key is absent.
	 *
	 * @return mixed
	 */
	public function get_data( string $key, $default_value = null ) {

		return $this->data[ $key ] ?? $default_value;
	}

	/**
	 * Return true if all entries have been processed or the session has failed.
	 *
	 * NOTE: advance() must receive the count of entries actually written to the
	 * database, not the count returned by process_chunk(), to keep this check
	 * accurate when write failures occur at the orchestrator level.
	 *
	 * @since 1.10.1
	 *
	 * @return bool
	 */
	public function is_complete(): bool {

		if ( $this->data['status'] === 'failed' ) {
			return true;
		}

		return $this->data['cursor'] >= $this->data['total'];
	}
}
