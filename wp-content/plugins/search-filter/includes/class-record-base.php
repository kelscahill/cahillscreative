<?php
/**
 * The base section class for the plugin.
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 */

namespace Search_Filter;

use Search_Filter\Core\Data_Store;
use Search_Filter\Core\Exception;
use Search_Filter\Database\Rows\Field;
use Search_Filter\Database\Rows\Query;
use Search_Filter\Database\Rows\Style_Preset;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface for record objects.
 */
interface Record_Interface {
	/**
	 * Constructor.
	 *
	 * @param int $id The record ID.
	 */
	public function __construct( int $id = 0 );
}

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound
/**
 * The base class for interacting with the record stores.
 *
 * Provides a common interface for interacting with the record stores such as
 * creating, updating, and deleting records.
 *
 * Should be extended for each record type such as fields, queries, and styles.
 */
abstract class Record_Base implements Record_Interface {

	/**
	 * Internal name
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      string    $name    Name
	 */
	protected $name = '';

	/**
	 * Internal db ID
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      int    $id    ID
	 */
	protected $id = 0;

	/**
	 * The full string of the class name of the query class for this section.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      string    $records_class    The string class name.
	 */
	public static $records_class = '';
	/**
	 * The record store key
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      int    $id    ID
	 */
	public static $record_store = '';
	/**
	 * The meta type for the meta table.
	 *
	 * @since    3.0.0
	 *
	 * @var string
	 */
	public static $meta_table = '';

	/**
	 * The class name to handle interacting with the record stores.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      string    $base_class    ID
	 */
	public static $base_class = '';

	/**
	 * Usually we only want to instantiate & lookup a record once,
	 * so store the instance for easy re-use later.
	 *
	 * @var array
	 */
	protected static $instances = array();
	/**
	 * Has the record instance been init.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      bool    $has_init    Has the record instance been init
	 */
	protected $has_init = false;
	/**
	 * Status
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      string    $name    Name
	 */
	protected $status = 'enabled';

	/**
	 * All the attributes needed to init a filter
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      array    $attributes    Maintains and registers all hooks for the plugin.
	 */
	protected $attributes = array();

	/**
	 * Date modified
	 *
	 * @since 3.0.0
	 *
	 * @var int
	 */
	protected $date_modified = 0;

	/**
	 * Date created
	 *
	 * @since 3.0.0
	 *
	 * @var int
	 */
	protected $date_created = 0;

	/**
	 * A copy of the database record.
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      object    $record    The database record for this instance
	 */
	protected $record;

	/**
	 * Contructor, runs init on the $attributes.
	 *
	 * @param int $id Field ID.
	 *
	 * @throws Exception If the field is not found.
	 */
	public function __construct( int $id = 0 ) {
		// TODO - load field from ID if its passed.
		$this->attributes = $this->get_default_attributes();

		$this->id = absint( $id );

		if ( $this->id > 0 ) {
			// Try to load from the store first before making a new request.
			$record = Data_Store::get( static::$record_store, $id );
			if ( ! $record ) {
				$query_args = array(
					'number'  => 1, // Only retrieve a single record.
					'orderby' => 'date_published',
					'order'   => 'asc',
					'id'      => $id,
				);
				/**
				 * TODO - we probably want to wrap this in our settings API
				 * so we never call the same query twice (maybe we need to
				 * update the API to support searching for fields without
				 * query ID)
				 */
				$static_class  = static::class;
				$records_class = $static_class::$records_class;
				$query         = new $records_class( $query_args );
				// Bail if nothing found.
				if ( empty( $query->items ) ) {
					/* translators: %s: Record type, %s: Field ID */
					throw new Exception( esc_html( sprintf( __( 'Couldn\'t init a `%1$s` with the id `%2$s`.', 'search-filter' ), static::$record_store, (string) $id ) ), SEARCH_FILTER_EXCEPTION_RECORD_NOT_EXISTS ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception code is a constant.
				} else {
					$record = $query->items[0];
				}
			}
			if ( $record ) {
				// Create the query using the record.
				$this->load_record( $record );
			}
		}
	}

	/**
	 * Overridable function for setting  defaults.
	 *
	 * TODO - make get_default_attributes abstract once we colocate the settings
	 * configuration to the local folder of the class extending this one.
	 *
	 * @return array New default attributes.
	 */
	public function get_default_attributes() {
		return array();
	}

	/**
	 * Init the query from already loaded attributes.
	 */
	public function init() {
		$this->has_init = true;
	}
	/**
	 * Inits the data from a DB record.
	 *
	 * @param Field|Query|Style_Preset $item Database record.
	 */
	public function load_record( $item ) {
		$this->set_id( $item->get_id() );
		$this->set_status( $item->get_status() );
		$this->set_name( $item->get_name() );
		$this->set_record( $item );
		$this->set_attributes( $item->get_attributes() );
		$this->set_date_modified( $item->get_date_modified() );
		$this->set_date_created( $item->get_date_created() );

		$this->init();
	}
	/**
	 * Process the attributes and run init local vars
	 *
	 * @since    3.0.0
	 *
	 * @param array $attributes  Field attributes.
	 * @param bool  $replace     Whether to replace the existing attributes.
	 */
	public function set_attributes( array $attributes, bool $replace = false ) {

		if ( ! $replace ) {
			$this->attributes = wp_parse_args( $attributes, $this->attributes );
		} else {
			$this->attributes = $attributes;
		}

		$this->attributes = apply_filters( 'search-filter/record/set_attributes', $this->attributes, static::$record_store, $this );
	}
	/**
	 * Get the attributes of the query.
	 *
	 * @since 3.0.0
	 *
	 * @param bool $unfiltered       Whether to return the unfiltered attributes.
	 *
	 * @return array The attributes of the query.
	 */
	public function get_attributes( bool $unfiltered = false ) {
		$attributes = $this->attributes;
		if ( ! $unfiltered ) {
			$attributes = apply_filters( 'search-filter/record/get_attributes', $attributes, $this );
		}
		return $attributes;
	}

	/**
	 * Get the name of the query
	 *
	 * @since 3.0.0
	 *
	 * @return string The name of the query.
	 */
	public function get_name() {
		return $this->name;
	}
	/**
	 * Set the name of the query
	 *
	 * @since 3.0.0
	 *
	 * @param string $name The name to set.
	 */
	public function set_name( string $name ) {
		$this->name = $name;
	}


	/**
	 * Get the date modified
	 *
	 * @since 3.0.0
	 *
	 * @return int The date modified.
	 */
	public function get_date_modified() {
		return $this->date_modified;
	}

	/**
	 * Set the date modified
	 *
	 * @since 3.0.0
	 *
	 * @param int $date The date to set.
	 */
	public function set_date_modified( int $date ) {
		$this->date_modified = $date;
	}

	/**
	 * Get the date created
	 *
	 * @since 3.0.0
	 *
	 * @return int The date created.
	 */
	public function get_date_created() {
		return $this->date_created;
	}

	/**
	 * Set the date created
	 *
	 * @since 3.0.0
	 *
	 * @param int $date The date to set.
	 */
	public function set_date_created( int $date ) {
		$this->date_created = $date;
	}

	/**
	 * Get the ID of the query
	 *
	 * @since 3.0.0
	 *
	 * @return int The ID of the query.
	 */
	public function get_id() {
		return $this->id;
	}
	/**
	 * Set the ID of the query
	 *
	 * @param int $id The ID to set.
	 */
	public function set_id( int $id ) {
		$this->id = absint( $id );
	}

	/**
	 * Get the status of the query
	 */
	public function get_status() {
		return $this->status;
	}
	/**
	 * Set the status of the query
	 *
	 * @param string $status The status to set.
	 */
	public function set_status( string $status ) {
		$this->status = $status;
	}

	/**
	 * Get the status of the query
	 */
	public function is_enabled() {
		return $this->status === 'enabled';
	}
	/**
	 * Sets the the db record
	 *
	 * @param Field|Query|Style_Preset $record The record to set.
	 */
	public function set_record( $record ) {
		$this->record = $record;
	}
	/**
	 * Gets the db record for this query.
	 *
	 * @return Field|Query|Style_Preset
	 */
	public function get_record() {
		return $this->record;
	}
	/**
	 * Checks if the filter has been init properly (parent class functions were called)
	 *
	 * @since    3.0.0
	 *
	 * @return bool
	 */
	public function has_init() {
		return $this->has_init;
	}
	/**
	 * Get the json data
	 *
	 * @since    3.0.0
	 *
	 * @return array The json data.
	 */
	public function get_json_data() {
		// TODO - add ID, name and query_id?
		return array(
			'id'         => $this->get_id(),
			'attributes' => $this->attributes,
		);
	}
	/**
	 * Gets an attribute
	 *
	 * @param string $attribute_name   The attribute name to get.
	 * @param bool   $unfiltered       Whether to return the unfiltered attribute.
	 *
	 * @return mixed The attribute value or null if no attribute found.
	 */
	public function get_attribute( string $attribute_name, bool $unfiltered = false ) {
		$attributes = $this->get_attributes( $unfiltered );

		$attribute = isset( $attributes[ $attribute_name ] ) ? $attributes[ $attribute_name ] : null;

		if ( ! $unfiltered ) {
			$attribute = apply_filters( 'search-filter/record/get_attribute', $attribute, $attribute_name, $this );
		}

		return $attribute;
	}
	/**
	 * Deletes an attribute
	 *
	 * @param string $attribute_name   The attribute name to delete.
	 */
	public function delete_attribute( string $attribute_name ) {
		// Use array_key_exists so we can work with NULL values.
		if ( array_key_exists( $attribute_name, $this->attributes ) ) {
			unset( $this->attributes[ $attribute_name ] );
		}
	}
	/**
	 * Set an attribute
	 *
	 * @param string $attribute_name   The attribute name to set.
	 * @param mixed  $attribute_value  The attribute value to set.
	 */
	public function set_attribute( string $attribute_name, $attribute_value ) {
		$this->attributes[ $attribute_name ] = $attribute_value;
	}

	/**
	 * Saves the query
	 *
	 * @since 3.0.0
	 *
	 * @param array $extra_args Extra arguments to pass to the save function.
	 *
	 * @return int The saved query ID.
	 */
	public function save( array $extra_args = array() ) {
		// TODO: figure out some good permissions.
		// Need to create new permissions for:
			// - edit_search_filter_fields.
			// - edit_search_filter_queries.
			// - edit_search_filter_styles.
			// - manage_search_filter.

		/*
		 * Removed: manage_options capability check.
		 * We shouldn't require manage options anymore, we save fields in lots of context
		 * such as upgrade routines, and we can't guarntee a user will have manage_options
		 * Instead we should do capability checks in our endpoints where needed.
		 */

		// Prepare the record.
		$record_data = array(
			'name'       => $this->get_name(),
			'status'     => $this->get_status(),
			// Use `get_attributes( true )` with a true param so we don't save
			// the filtered data in the database.
			'attributes' => wp_json_encode( (object) $this->get_attributes( true ) ),
		);

		// Add extra args.
		$record_data = wp_parse_args( $record_data, $extra_args );

		// Grab db instance.
		$static_class  = static::class;
		$records_class = $static_class::$records_class;
		$query         = new $records_class();

		do_action( 'search-filter/record/pre_save', $this, static::$record_store );

		// Figure out of we need to add or update the record.
		$is_new = false;
		if ( $this->id === 0 ) {
			$is_new = true;
		} else {
			// Lets check to see if the record exists.
			$existing_record = $query->get_item( $this->id );
			if ( ! $existing_record ) {
				$is_new = true;
			}
		}

		if ( ! $is_new ) {
			// Lets check to see if the record exists.
			$result = $query->update_item( $this->id, $record_data );
		} else {
			if ( $this->id !== 0 ) {
				$record_data['id'] = $this->id;
			}
			$this->id = $query->add_item( $record_data );
		}

		// TODO - we need to update the local record and the Data_Store.
		$this->record = $query->get_item( $this->id );

		// Update the static instance cache so subsequent get_instance() calls get fresh data.
		static::set_instance( $this );

		do_action( 'search-filter/record/save', $this, static::$record_store, $is_new );
		return $this->id;
	}

	/**
	 * Deletes an instantiated record from the database.
	 *
	 * @return bool True if deleted, false if not.
	 */
	public function delete() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}
		if ( $this->id > 0 ) {
			$result = self::destroy( $this->id );
			do_action( 'search-filter/record/delete', $this, static::$record_store );
			$this->id = 0;
			return $result;
		}
		return false;
	}

	/**
	 * Creates a new instance of the query class with given data.
	 *
	 * @param array $attributes The init query attributes.
	 *
	 * @return static The new instance of the static class.
	 */
	public static function create( array $attributes = array() ) {
		$static_class = static::class;
		$new_record   = new $static_class();
		$new_record->set_attributes( $attributes );
		$new_record->init();
		return $new_record;
	}

	/**
	 * Creates a new record instance from a database record.
	 *
	 * @param Field|Query|Style_Preset $item The database record.
	 * @return static The new instance of the query class (static).
	 */
	public static function create_from_record( $item ) {
		$new_record = new static();
		$new_record->load_record( $item );
		return $new_record;
	}

	/**
	 * Find a query by conditions
	 *
	 * @param array  $conditions Column name => value pairs.
	 * @param string $return_type Whether to return the object or the record.
	 *
	 * @return static|\WP_Error
	 * @throws Exception If conditions are not an array.
	 */
	public static function find( array $conditions, string $return_type = 'object' ) {

		$query_args = array(
			'number'  => 1, // Only retrieve a single record.
			'orderby' => 'date_published',
			'order'   => 'asc',
		);
		$query_args = wp_parse_args( $conditions, $query_args );
		/**
		 * TODO - we probably want to wrap this in our settings API
		 * so we never call the same field twice (maybe we need to
		 * update the API to support searching for fields without
		 * query ID)
		 */
		$static_class  = static::class;
		$records_class = $static_class::$records_class;
		$query         = new $records_class( $query_args );

		// Bail if nothing found.
		if ( empty( $query->items ) ) {
			return new \WP_Error( 'search_filter_not_found', __( 'Record not found.', 'search-filter' ), array( 'status' => 404 ) );
		}

		if ( $return_type === 'record' ) {
			return $query->items[0];
		}

		// Create the record instance.
		$item = static::create_from_record( $query->items[0] );
		return $item;
	}

	/**
	 * Get the global instance of the record by ID.
	 *
	 * Re-use the same instance of the record if it already exists, finds the
	 * record and initializes it if it has not been retrieved before.
	 *
	 * @param int  $id The ID of the record to get.
	 * @param bool $refetch Whether to refetch the record from the database.
	 *
	 * @return mixed|WP_Error
	 */
	public static function get_instance( int $id, $refetch = false ) {
		$static_class = static::class;
		$id           = absint( $id );

		// If the ID is 0 then return an error, get_instance should only be used
		// for retrieving instance that exist in the database.
		if ( $id === 0 ) {
			return new \WP_Error( 'search_filter_not_found', __( 'Record not found.', 'search-filter' ), array( 'status' => 404 ) );
		}

		if ( $refetch === false && isset( $static_class::$instances[ $id ] ) ) {
			return $static_class::$instances[ $id ];
		}

		// Use `find` so that the Field class can use Field_Factory to create
		// the instance, if we used  `new $static_class( $id )` then we might
		// not create the correct class instance.
		$new_record = $static_class::find( array( 'id' => $id ) );
		if ( is_wp_error( $new_record ) ) {
			return $new_record;
		}

		$static_class::$instances[ $id ] = $new_record;
		return $new_record;
	}

	/**
	 * Forget a cached instance by ID.
	 *
	 * Removes a specific instance from the cache without affecting others.
	 * The next access to this ID will re-fetch from the database.
	 *
	 * @since 3.2.0
	 *
	 * @param int $id The ID of the record to forget.
	 */
	public static function forget_instance( int $id ) {
		$static_class = static::class;
		if ( isset( $static_class::$instances[ $id ] ) ) {
			unset( $static_class::$instances[ $id ] );
		}
	}

	/**
	 * Flush the instances.
	 *
	 * @since 3.2.0
	 */
	public static function flush_instances() {
		$static_class             = static::class;
		$static_class::$instances = array();
	}

	/**
	 * Set an instance.
	 *
	 * @since 3.2.0
	 *
	 * @param static $instance The instance to set.
	 */
	public static function set_instance( $instance ) {
		if ( is_wp_error( $instance ) ) {
			return;
		}
		if ( $instance->get_id() === 0 ) {
			return;
		}
		$static_class                                    = static::class;
		$static_class::$instances[ $instance->get_id() ] = $instance;
	}

	/**
	 * Check if an instance exists for a given ID.
	 *
	 * @since 3.2.0
	 *
	 * @param int $id The record ID.
	 * @return bool Whether the instance exists.
	 */
	public static function has_instance( $id ) {
		$static_class = static::class;
		return isset( $static_class::$instances[ $id ] );
	}

	/**
	 * Deletes a query by ID
	 *
	 * @param int $id The ID of the query to delete.
	 *
	 * @return bool True if deleted, false if not.
	 */
	public static function destroy( int $id ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}
		$static_class  = static::class;
		$records_class = $static_class::$records_class;
		$query         = new $records_class();

		do_action( 'search-filter/record/pre_destroy', $id, static::$record_store );
		// Cast to bool, as delete will return an int of number of deleted items on success
		// or false on failure.
		$delete_result = (bool) $query->delete_item( absint( $id ) );
		self::forget_instance( $id );
		do_action( 'search-filter/record/destroy', $id, static::$record_store );
		return $delete_result;
	}

	/**
	 * Adds meta data to the field.
	 *
	 * @param int     $id          The ID of the field to add meta to.
	 * @param string  $meta_key    The key of the meta data to add.
	 * @param mixed   $meta_value  The value of the meta data to add.
	 * @param boolean $unique      Whether to make the meta data unique.
	 */
	public static function add_meta( int $id, string $meta_key, $meta_value, bool $unique = false ) {
		if ( empty( $meta_key ) ) {
			return false;
		}
		return add_metadata( static::$meta_table, $id, $meta_key, $meta_value, $unique );
	}
	/**
	 * Updates meta data for the field.
	 *
	 * @param int    $id          The ID of the field to update meta for.
	 * @param string $meta_key    The key of the meta data to update.
	 * @param mixed  $meta_value  The value of the meta data to update.
	 * @param mixed  $prev_value  The previous value to update - leave empty to update all.
	 */
	public static function update_meta( int $id, string $meta_key, $meta_value, $prev_value = '' ) {
		if ( empty( $meta_key ) ) {
			return false;
		}
		return update_metadata( static::$meta_table, $id, $meta_key, $meta_value, $prev_value );
	}
	/**
	 * Deletes meta data for the field.
	 *
	 * @param int    $id          The ID of the field to delete meta for.
	 * @param string $meta_key    The key of the meta data to delete.
	 * @param mixed  $meta_value  The value of the meta data to delete.
	 */
	public static function delete_meta( int $id, string $meta_key, $meta_value = '' ) {
		if ( empty( $meta_key ) ) {
			return false;
		}
		return delete_metadata( static::$meta_table, $id, $meta_key, $meta_value );
	}
	/**
	 * Gets meta data for the field.
	 *
	 * @param int    $id        The ID of the field to get meta for.
	 * @param string $meta_key  The key of the meta data to get.
	 * @param bool   $single    Whether to retreive a single instance or all.
	 *
	 * @return mixed
	 */
	public static function get_meta( int $id, string $meta_key, bool $single = false ) {
		if ( empty( $meta_key ) ) {
			return false;
		}

		return get_metadata( static::$meta_table, $id, $meta_key, $single );
	}
}
