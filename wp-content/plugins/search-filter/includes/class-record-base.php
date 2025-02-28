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

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The base class for interacting with the record stores.
 *
 * Provides a common interface for interacting with the record stores such as
 * creating, updating, and deleting records.
 *
 * Should be extended for each record type such as fields, queries, and styles.
 */
abstract class Record_Base {

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
	 * Has the query been init.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      bool    $has_init    Has the query been init
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
	 * Default attributes.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	private $default_attributes = array();

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
	public function __construct( $id = 0 ) {
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
					throw new Exception( sprintf( esc_html__( 'Couldn\'t init a `%1$s` with the id `%2$s`.', 'search-filter' ), esc_html( static::$record_store ), esc_html( $id ) ), SEARCH_FILTER_EXCEPTION_RECORD_NOT_EXISTS ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
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
	 * @param mixed $item Database record.
	 */
	public function load_record( $item ) {
		$this->set_id( $item->get_id() );
		$this->set_status( $item->get_status() );
		$this->set_name( $item->get_name() );
		$this->set_record( $item );
		$this->set_attributes( $item->get_attributes() );
		$this->set_date_modified( $item->get_date_modified() );
		$this->set_date_created( $item->get_date_created() );
	}
	/**
	 * Process the attributes and run init local vars
	 *
	 * @since    3.0.0
	 *
	 * @param array $attributes  Field attributes.
	 * @param bool  $replace     Whether to replace the existing attributes.
	 */
	public function set_attributes( $attributes, $replace = false ) {

		if ( ! $replace ) {
			$this->attributes = wp_parse_args( $attributes, $this->attributes );
		} else {
			$this->attributes = $attributes;
		}

		$this->attributes = apply_filters( 'search-filter/record/set_attributes', $this->attributes, static::$record_store, $this );

		// TODO - maybe we need to move init() out of here.
		$this->init();
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
	public function get_attributes( $unfiltered = false ) {
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
	public function set_name( $name ) {
		$this->name = $name;
	}


	/**
	 * Get the date modified
	 *
	 * @since 3.0.0
	 *
	 * @return string The date modified.
	 */
	public function get_date_modified() {
		return $this->date_modified;
	}

	/**
	 * Set the date modified
	 *
	 * @since 3.0.0
	 *
	 * @param string $date The date to set.
	 */
	public function set_date_modified( $date ) {
		$this->date_modified = $date;
	}

	/**
	 * Get the date created
	 */
	public function get_date_created() {
		return $this->date_created;
	}

	/**
	 * Set the date created
	 *
	 * @since 3.0.0
	 *
	 * @param string $date The date to set.
	 */
	public function set_date_created( $date ) {
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
	public function set_id( $id ) {
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
	public function set_status( $status ) {
		$this->status = $status;
	}
		/**
		 * Sets the the db record
		 *
		 * @param string $record The record to set.
		 */
	public function set_record( $record ) {
		$this->record = $record;
	}
	/**
	 * Gets the db record for this query.
	 */
	public function get_record() {
		return $this->record;
	}
	/**
	 * Checks if the filter has been init properly (parent class functions were called)
	 *
	 * @since    3.0.0
	 */
	protected function has_init() {
		return $this->has_init;
	}
	/**
	 * Get the json data
	 *
	 * @since    3.0.0
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
	 * @return mixed The attribute value or false if no attribute found.
	 */
	public function get_attribute( $attribute_name, $unfiltered = false ) {
		$attributes = $this->attributes;
		if ( ! $unfiltered ) {
			$attributes = $this->get_attributes();
		}

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
	public function delete_attribute( $attribute_name ) {
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
	public function set_attribute( $attribute_name, $attribute_value ) {
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
	public function save( $extra_args = array() ) {
		// TODO: figure out some good permissions.
		// Need to create new permissions for:
			// - edit_search_filter_fields.
			// - edit_search_filter_queries.
			// - edit_search_filter_styles.
			// - manage_search_filter.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Prepare the record.
		$record = array(
			'name'       => $this->get_name(),
			'status'     => $this->get_status(),
			// Use `get_attributes( true )` with a true param so we don't save
			// the filtered data in the database.
			'attributes' => wp_json_encode( (object) $this->get_attributes( true ) ),
		);

		// Add extra args.
		$record = wp_parse_args( $record, $extra_args );

		// Grab db instance.
		$static_class  = static::class;
		$records_class = $static_class::$records_class;
		$query         = new $records_class();

		do_action( 'search-filter/record/pre_save', $this, static::$record_store );

		// Add or update the record.
		$is_new = $this->id === 0;
		if ( $this->id !== 0 ) {
			$result = $query->update_item( $this->id, $record );
		} else {
			$this->id = $query->add_item( $record );
		}

		// TODO - we need to update the local record and the Data_Store..
		$this->record = $query->get_item( $this->id );

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
	 * @return Query The new instance of the query class.
	 */
	public static function create( $attributes = array() ) {
		$static_class = static::class;
		$new_query    = new $static_class();
		$new_query->set_attributes( $attributes );
		return $new_query;
	}

	/**
	 * Creates a new instance of the query from a database record.
	 *
	 * @param StdClass $item The database record.
	 *
	 * @return Query The new instance of the query class.
	 */
	public static function create_from_record( $item ) {
		$static_class = static::class;
		$new_record   = new $static_class();
		$new_record->load_record( $item );
		return $new_record;
	}

	/**
	 * Find a query by conditions
	 *
	 * @param array  $conditions Column name => value pairs.
	 * @param string $return_type Whether to return the object or the record.
	 *
	 * @return static|WP_Error
	 * @throws Exception If conditions are not an array.
	 */
	public static function find( $conditions, $return_type = 'object' ) {
		// If conditions are not an array then throw an exception.
		if ( ! is_array( $conditions ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new Exception( esc_html( 'Conditions must be an array.' ), SEARCH_FILTER_EXCEPTION_BAD_FIND_CONDITIONS );
		}

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
		$item = self::create_from_record( $query->items[0] );
		return $item;
	}

	/**
	 * Deletes a query by ID
	 *
	 * @param int $id The ID of the query to delete.
	 *
	 * @return bool True if deleted, false if not.
	 */
	public static function destroy( $id ) {
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
	public static function add_meta( $id, $meta_key, $meta_value, $unique = false ) {
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
	 * @param string $prev_value  The previous value to update - leave empty to update all.
	 */
	public static function update_meta( $id, $meta_key, $meta_value, $prev_value = '' ) {
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
	 * @param string $meta_value  The value of the meta data to delete.
	 */
	public static function delete_meta( $id, $meta_key, $meta_value = '' ) {
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
	 * @param string $single    Whether to retreive a single instance or all.
	 */
	public static function get_meta( $id, $meta_key, $single = false ) {
		if ( empty( $meta_key ) ) {
			return false;
		}

		return get_metadata( static::$meta_table, $id, $meta_key, $single );
	}
}
