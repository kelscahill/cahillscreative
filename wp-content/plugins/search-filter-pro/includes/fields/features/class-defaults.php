<?php
/**
 * Handles the frontend display of the fields
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 */

namespace Search_Filter_Pro\Fields\Features;

use Search_Filter\Fields\Field;
use Search_Filter\Queries;
use Search_Filter\Queries\Query;
use Search_Filter_Pro\Fields;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * A controller for managing all thigns to do with fields
 */
class Defaults {

	/**
	 * The query tracking for defaults.
	 *
	 * Before we update the query values based on defaults, we need to track
	 * the query ID to make sure it matches.
	 *
	 * @var integer
	 */
	private static $defaults_query_tracking = 0;

	/**
	 * Init the fields.
	 *
	 * @since 3.0.0
	 */
	public static function init() {

		// Handle indexer queries.
		add_filter( 'search-filter-pro/indexer/query/init/start', array( __CLASS__, 'apply_defaults_to_query' ), 10, 1 );
		add_filter( 'search-filter-pro/indexer/query/init/finish', array( __CLASS__, 'remove_defaults_from_query' ), 10, 1 );
		// Handle wp_query queries.
		add_filter( 'search-filter/query/apply_query/start', array( __CLASS__, 'apply_defaults_to_query' ), 10, 1 );
		add_filter( 'search-filter/query/apply_query/finish', array( __CLASS__, 'remove_defaults_from_query' ), 10, 1 );
		// Add default values to the fields.
		add_filter( 'search-filter/fields/field/render_data', array( __CLASS__, 'add_default_values' ), 20, 2 );
	}

	/**
	 * Apply defaults to the fields when a query is run.
	 *
	 * @since 3.0.0
	 *
	 * @param Query $query The query object.
	 */
	public static function apply_defaults_to_query( $query ) {
		self::$defaults_query_tracking = $query->get_id();
		add_filter( 'search-filter/fields/field/values', array( __CLASS__, 'set_default_values_for_query' ), 10, 2 );
	}

	/**
	 * Remove defaults from the fields when a query is finished.
	 *
	 * @since 3.0.0
	 */
	public static function remove_defaults_from_query() {
		self::$defaults_query_tracking = 0;
		remove_filter( 'search-filter/fields/field/values', array( __CLASS__, 'set_default_values_for_query' ), 10 );
	}

	/**
	 * Check if query can apply at current location.
	 *
	 * @since 3.0.0
	 *
	 * @param Query $query The query object.
	 * @return bool Whether query can apply at current location.
	 */
	public static function query_can_apply_at_current_location( $query ) {
		// Remove the current filter when checking for active fields & values as we
		// don't wanth the defaults to get applied.
		self::remove_defaults_from_query();
		$can_apply_at_location = false;
		if ( method_exists( $query, 'can_apply_at_current_location' ) ) {
			$can_apply_at_location = $query->can_apply_at_current_location();
		}
		self::apply_defaults_to_query( $query );
		return $can_apply_at_location;
	}

	/**
	 * Set default values for query fields.
	 *
	 * @since 3.0.0
	 *
	 * @param array $values The field values.
	 * @param Field $field  The field object.
	 * @return array Modified field values.
	 */
	public static function set_default_values_for_query( $values, $field ) {

		// If the query ID doesn't match, then we don't want to apply the defaults.
		// Note - this check might not be necessary if we're filtering a specific field
		// then it's already likely being called by its own query via `get_fields()`.
		if ( $field->get_query_id() !== self::$defaults_query_tracking ) {
			return $values;
		}

		// If the field already has values, then don't override them.
		if ( ! empty( $values ) ) {
			return $values;
		}

		// Check if the field is setup to use a default value.
		if ( ! self::can_use_default_value( $field ) ) {
			return $values;
		}

		$field_query = Fields::get_field_query( $field );
		if ( ! $field_query ) {
			return $values;
		}

		// If the field is not set to apply to the query, and we're on a location with the query then bail.
		if ( $field->get_attribute( 'defaultValueApplyToQuery' ) !== 'yes' && self::query_can_apply_at_current_location( $field_query ) ) {
			return $values;
		}

		// If the query param is applied, it means we don't want to set a default
		// because the query has been interacted with already.
		$query_param = '~' . $field->get_query_id();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public query parameter check, no data modification.
		if ( isset( $_GET[ $query_param ] ) ) {
			return $values;
		}

		// Otherwise, set the default value.
		$default_value = self::get_default_value( $field );
		if ( $default_value === null ) {
			return $values;
		}
		return array( $default_value );
	}

	/**
	 * Get the default value for a field.
	 *
	 * @since 3.0.0
	 *
	 * @param Field $field The field object.
	 * @return mixed The default value.
	 */
	public static function get_default_value( $field ) {

		// Use custom value.
		if ( $field->get_attribute( 'defaultValueType' ) === 'custom' ) {

			// Use the custom value that was set in the field.
			return $field->get_attribute( 'defaultValueCustom' );

		} elseif ( $field->get_attribute( 'defaultValueType' ) === 'inherit' ) {
			// Inherit the values from the current location.

			// Get the queried object.
			$queried_object = get_queried_object();

			// Ensure the connected query is ready otherwise return early.
			$query = Fields::get_field_query( $field );
			if ( ! $query ) {
				return null;
			}

			$field_type = $field->get_attribute( 'type' );
			// Note - we don't check to see the option is available in the field before setting
			// the default.  It could be very expensive to generate all optins (especially if a field
			// has a restrcition on the number of options) but it might cause issue in the future.
			if ( $field->get_attribute( 'defaultValueInheritSearch' ) === 'yes' && is_search() ) {
				return get_search_query( false );
			} elseif ( is_home() && $field->get_attribute( 'defaultValueInheritArchive' ) === 'yes' ) {
				// Special case when the archive is a blog and the we're inheriting the post type.
				if ( $field->get_attribute( 'dataType' ) === 'post_attribute' ) {
					$data_post_attribute = $field->get_attribute( 'dataPostAttribute' );
					if ( $data_post_attribute === 'post_type' ) {
						return 'post';
					}
				}
			} elseif ( is_archive() && $field->get_attribute( 'defaultValueInheritArchive' ) === 'yes' ) {
				// Check to see what type of archive the field would apply to.
				$data_type = $field->get_attribute( 'dataType' );
				if ( $data_type === 'taxonomy' && is_a( $queried_object, 'WP_Term' ) ) {
					// Check that the taxonomy matches the field taxonomy.
					$taxonomy = $field->get_attribute( 'dataTaxonomy' );
					if ( $taxonomy !== $queried_object->taxonomy ) {
						return null;
					}
					if ( $field_type === 'search' ) {
						return $queried_object->name;
					}
					return $queried_object->slug;
				} elseif ( $data_type === 'post_attribute' ) {
					$data_post_attribute = $field->get_attribute( 'dataPostAttribute' );

					if ( $data_post_attribute === 'post_type' && is_a( $queried_object, 'WP_Post_Type' ) ) {
						$query_post_types = self::get_post_types_from_query( $query );
						if ( ! $query_post_types ) {
							return null;
						}
						if ( in_array( $queried_object->name, $query_post_types, true ) ) {
							if ( $field_type === 'search' ) {
								return $queried_object->label;
							}
							return $queried_object->name;
						}
						return null;
					} elseif ( $data_post_attribute === 'post_author' && is_a( $queried_object, 'WP_User' ) ) {
						return $queried_object->user_nicename;
					}
					// TODO - support date archives.
				}
			} elseif ( is_singular() && $field->get_attribute( 'defaultValueInheritPost' ) === 'yes' ) {
				// If we're only a single post, extract the values from the post to potentially
				// use a default value.

				$data_type = $field->get_attribute( 'dataType' );
				if ( ! is_a( $queried_object, 'WP_Post' ) ) {
					return null;
				}
				// Get the post ID.
				$post_id = $queried_object->ID;

				// Lets make sure the post author and post type matches the current post.
				$query = Fields::get_field_query( $field );
				if ( ! $query ) {
					return null;
				}

				$post_type   = $queried_object->post_type;
				$post_status = $queried_object->post_status;

				if ( $data_type === 'taxonomy' ) {
					$taxonomy = $field->get_attribute( 'dataTaxonomy' );
					$terms    = get_the_terms( $post_id, $taxonomy );
					if ( ! is_array( $terms ) ) {
						return null;
					}
					if ( count( $terms ) === 0 ) {
						return null;
					}
					if ( $field_type === 'search' ) {
						return $terms[0]->name;
					}
					// Pick the first term and return its slug.
					return $terms[0]->slug;

				} elseif ( $data_type === 'post_attribute' ) {

					$data_post_attribute = $field->get_attribute( 'dataPostAttribute' );

					if ( $data_post_attribute === 'post_type' ) {
						$query_post_types = $query->get_attribute( 'postTypes' );
						if ( ! $query_post_types ) {
							return null;
						}

						if ( ! in_array( $post_type, $query_post_types, true ) ) {
							return null;
						}
						if ( $field_type === 'search' ) {
							$post_type_object = get_post_type_object( $post_type );
							if ( ! $post_type_object ) {
								return null;
							}
							return $post_type_object->label;
						}
						return $post_type;

					} elseif ( $data_post_attribute === 'post_status' ) {
						$query_post_statuses = $query->get_attribute( 'postStatus' );
						if ( ! $query_post_statuses ) {
							return null;
						}

						if ( ! in_array( $post_status, $query_post_statuses, true ) ) {
							return null;
						}
						if ( $field_type === 'search' ) {
							$post_status_object = get_post_status_object( $post_status );
							if ( ! $post_status_object ) {
								return null;
							}
							return $post_status_object->label;
						}
						return $post_status;

					} elseif ( $data_post_attribute === 'post_author' ) {
						$post_author_id = (int) $queried_object->post_author;

						if ( $field_type === 'search' ) {
							return get_the_author_meta( 'display_name', $post_author_id );
						}

						return get_the_author_meta( 'user_nicename', $post_author_id );
					}
					// TODO - need to support date archives.
				}
			}
		}

		return apply_filters( 'search-filter-pro/fields/get_default_value', null, $field );
	}

	/**
	 * Get post types from a query.
	 *
	 * @since 3.0.0
	 *
	 * @param Query|null $query The query object.
	 * @return array|null The post types or null.
	 */
	private static function get_post_types_from_query( $query ) {
		if ( ! $query ) {
			return null;
		}
		if ( ! $query->get_attribute( 'postTypes' ) ) {
			return null;
		}
		if ( count( $query->get_attribute( 'postTypes' ) ) === 0 ) {
			return null;
		}
		return $query->get_attribute( 'postTypes' );
	}

	/**
	 * Check if a field can use default values.
	 *
	 * @since 3.0.0
	 *
	 * @param Field $field The field object.
	 * @return bool Whether the field can use default values.
	 */
	private static function can_use_default_value( $field ) {
		if ( ! $field->get_attribute( 'defaultValueType' ) ) {
			return false;
		}
		if ( $field->get_attribute( 'defaultValueType' ) === 'none' ) {
			return false;
		}
		return true;
	}

	/**
	 * Add default values for fields that have them enabled.
	 *
	 * @param array $render_data The render data to update.
	 * @param Field $field       The field to update the render data for.
	 * @return array The updated render data.
	 */
	public static function add_default_values( $render_data, $field ) {

		if ( ! self::can_use_default_value( $field ) ) {
			return $render_data;
		}

		// If a field is set to use default values, add them to the
		// render attributes.
		$default_value = self::get_default_value( $field );
		if ( $default_value === null ) {
			return $render_data;
		}
		$render_data['defaultValues'] = array( $default_value );
		return $render_data;
	}

	/**
	 * Figure out whether a fields query is actually run at the
	 * current location.
	 *
	 * @since 3.0.0
	 *
	 * @param Field $field The field object.
	 * @return bool Whether the current location has the field query.
	 * @phpstan-ignore method.unused (Reserved for future use)
	 */
	private static function current_location_has_field_query( $field ) {

		// This is not really working.
		// We are using this after modifying the url values.
		$active_queries = Queries::get_active_query_ids();
		return in_array( $field->get_query_id(), $active_queries, true );
	}
}
