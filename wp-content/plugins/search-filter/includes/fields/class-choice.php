<?php
/**
 * Choice Filter base class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter
 * @subpackage Search_Filter/Fields/Filter
 */

namespace Search_Filter\Fields;

use Search_Filter\Core\Deprecations;
use Search_Filter\Fields\Field;
use Search_Filter\Core\WP_Data;
use Search_Filter\Fields\Data\Taxonomy_Options;
use Search_Filter\Queries\Query;
use Search_Filter\Query\Template_Data;
use Search_Filter\Util;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Handles things a field with choices with need - such
 * as a list of options.
 */
class Choice extends Field {

	/**
	 * Get the list of options based on data attributes
	 *
	 * @return array
	 */
	public function get_options() {
		if ( ! parent::has_options() ) {
			$this->create_options();
		}
		if ( empty( $this->get_attribute( 'dataTotalNumberOfOptions' ) ) ) {
			return $this->options;
		}
		return array_slice( $this->options, 0, absint( $this->get_attribute( 'dataTotalNumberOfOptions' ) ) );
	}

	/**
	 * Get an attribute from the fields query.
	 *
	 * @param string $attribute The attribute name.
	 * @return mixed
	 */
	private function get_query_attribute( $attribute ) {
		// If no post type is chosen, then choose all available from the query attributes.
		$query = Query::get_instance( $this->get_query_id() );
		if ( ! is_wp_error( $query ) ) {
			return $query->get_attribute( $attribute );
		}
		return null;
	}
	/**
	 * Get the list of options based on data attributes
	 *
	 * TODO - we should be able to expect some of these attributes
	 * to be set rather than checking them all.
	 *
	 * This is partly related to the create_field endpoint, it is
	 * hit while fields are being initialised and as a result the
	 * attributes are malformed.
	 *
	 * 1. We need a reliable way to init fields with the correct
	 *    attributes & sensible defaults.
	 * 2. Resolve the settings in PHP before loading the page,
	 *    use it as the default state for the JS so we don't send
	 *    the wrong data to the server when creating new fields.
	 */
	public function create_options() {

		if ( ! $this->has_init() ) {
			return;
		}

		// Legacy support for incorrectly named action.
		Deprecations::add_action( 'search-filter/fields/filter/choice/create_options/start', '3.2.0', 'search-filter/fields/choice/create_options/start' );
		do_action( 'search-filter/fields/filter/choice/create_options/start', $this );

		// Before create options hook.
		do_action( 'search-filter/fields/choice/create_options/start', $this );

		$options = array();

		$values = $this->get_values();
		// Now check things like data type and data source, to figure out which options should be generated
		// get the options for the field - used by all choice decendants.
		if ( 'post_attribute' === $this->get_attribute( 'dataType' ) ) {
			$data_source = $this->get_attribute( 'dataPostAttribute' );
			if ( 'post_type' === $data_source ) {

				$post_type_options = $this->get_attribute( 'dataPostTypes' );

				if ( empty( $post_type_options ) ) {
					// If no post type is chosen, then choose all available from the query attributes.
					$post_type_options = $this->get_query_attribute( 'postTypes' );
				}

				if ( ! is_array( $post_type_options ) ) {
					// TODO - throw error.
					return;
				}

				$all_post_types = WP_Data::get_post_types( array( 'publicly_queryable' => true ), 'or' );

				// Collect all the valid options.
				foreach ( $all_post_types as $post_type ) {
					if ( in_array( $post_type->name, $post_type_options, true ) ) {
						self::add_option_to_array(
							$options,
							array(
								'value' => $post_type->name,
								'label' => html_entity_decode( $post_type->label ),
							),
							$this->get_id()
						);

						if ( in_array( $post_type->name, $values, true ) ) {
							$this->value_labels[ $post_type->name ] = $post_type->label;
						}
					}
				}
			} elseif ( 'post_status' === $data_source ) {
				if ( empty( $this->get_attribute( 'dataPostStati' ) ) ) {
					return;
				}
				if ( ! is_array( $this->get_attribute( 'dataPostStati' ) ) ) {
					return;
				}

				$post_stati = WP_Data::get_post_stati();

				foreach ( $post_stati as $post_status ) {
					if ( in_array( $post_status->name, $this->get_attribute( 'dataPostStati' ), true ) ) {

						self::add_option_to_array(
							$options,
							array(
								'value' => $post_status->name,
								'label' => html_entity_decode( $post_status->label ),
							),
							$this->get_id()
						);

						if ( in_array( $post_status->name, $values, true ) ) {
							$this->value_labels[ $post_status->name ] = $post_status->label;
						}
					}
				}
			}

			// Sort according to the order direction.
			$order           = $this->get_attribute( 'inputOptionsOrder' ) ? $this->get_attribute( 'inputOptionsOrder' ) : 'label';
			$order_direction = $this->get_attribute( 'inputOptionsOrderDir' ) ? $this->get_attribute( 'inputOptionsOrderDir' ) : 'asc';
			if ( $order === 'label' ) {
				$options = Util::sort_assoc_array_by_property( $options, $order, 'alphabetical', $order_direction );
			} elseif ( $order === 'count' ) {
				$options = Util::sort_assoc_array_by_property( $options, 'count', 'numerical', $order_direction );
			}
		} elseif ( 'taxonomy' === $this->get_attribute( 'dataType' ) ) {

			$data_source = $this->get_attribute( 'dataTaxonomy' );
			$order_dir   = '';
			if ( $this->get_attribute( 'taxonomyOrderDir' ) === 'asc' ) {
				$order_dir = 'ASC';
			} elseif ( $this->get_attribute( 'taxonomyOrderDir' ) === 'desc' ) {
				$order_dir = 'DESC';
			}
			$args = array(
				'order_by'            => $this->get_attribute( 'taxonomyOrderBy' ) !== 'default' ? $this->get_attribute( 'taxonomyOrderBy' ) : '',
				'order_dir'           => $order_dir,
				'terms_conditions'    => $this->get_attribute( 'taxonomyTermsConditions' ),
				'terms'               => $this->get_attribute( 'taxonomyTerms' ),
				'is_hierarchical'     => $this->get_attribute( 'taxonomyHierarchical' ) === 'yes',
				'limit_depth'         => $this->get_attribute( 'limitTaxonomyDepth' ) === 'yes',
				'show_count'          => $this->get_attribute( 'showCount' ) === 'yes',
				'show_count_brackets' => $this->get_attribute( 'showCountBrackets' ) === 'yes',
				'hide_empty'          => $this->get_attribute( 'hideEmpty' ) === 'yes',
			);

			// We need to disable hide_empty for non WP_Query types to work around an issue searching
			// the media library.  WP Doesn't recognise taxonomies properly assigned to them so this setting
			// by default hides terms which our indexer would otherwise show.

			$args    = apply_filters( 'search-filter/fields/choice/create_options/get_terms_args', $args, $this );
			$options = $this->get_taxonomy_options( $data_source, $args );

			// TODO - try to avoid the extra loop here and set this in one of the other passes.
			foreach ( $options as $option ) {
				if ( in_array( $option['value'], $values, true ) ) {
					$this->value_labels[ $option['value'] ] = $option['label'];
				}
			}
			// Even though we probably order the options ok with count above, better do it
			// this way by count to support extending it via the indexer (so the options are
			// reshuffled according the currently highest count value).
			$order_by_count = $this->get_attribute( 'taxonomyOrderBy' ) === 'count';
			if ( $order_by_count ) {
				$options = Util::sort_assoc_array_by_property( $options, 'count', 'numerical', $this->get_attribute( 'taxonomyOrderDir' ) );
			}
		}

		// Legacy support for incorrectly named action, since 3.2.0.
		Deprecations::add_filter( 'search-filter/field/choice/options', '3.2.0', 'search-filter/fields/choice/options' );
		$options = apply_filters( 'search-filter/field/choice/options', $options, $this );

		// Legacy support for deprecated filter, deprecated in 3.2.0.
		Deprecations::add_filter( 'search-filter/field/choice/options_data', '3.2.0', 'search-filter/fields/choice/options' );
		$options_data = array(
			'options' => $options,
			'labels'  => array(),
		);
		$options_data = apply_filters( 'search-filter/field/choice/options_data', $options_data, $this );
		$options      = $options_data['options'];

		// Filter the options array.
		$options = apply_filters( 'search-filter/fields/choice/options', $options, $this );

		// Legacy support for incorrectly named action.
		Deprecations::add_action( 'search-filter/fields/filter/choice/create_options/finish', '3.2.0', 'search-filter/fields/choice/create_options/finish' );
		do_action( 'search-filter/fields/filter/choice/create_options/finish', $this );
		// After create options hook.
		do_action( 'search-filter/fields/choice/create_options/finish', $this );

		$this->set_options( $options );
	}

	/**
	 * Get the taxonomy options.
	 *
	 * @param string $taxonomy_name The taxonomy name.
	 * @param array  $args          The arguments.
	 *
	 * @return array
	 */
	public function get_taxonomy_options( $taxonomy_name, $args = array() ) {

		if ( empty( $taxonomy_name ) ) {
			return array();
		}

		$defaults = array(
			'taxonomy'            => $taxonomy_name,
			'order_by'            => '',
			'order_dir'           => '',
			'hide_empty'          => '',
			'terms_conditions'    => '',
			'terms'               => array(),
			'is_hierarchical'     => false,
			'limit_depth'         => false,
			'show_count'          => true,
			'show_count_brackets' => true,
		);
		$args     = wp_parse_args( $args, $defaults );
		$options  = array();

		$data_tax_depth = 0;
		if ( $args['is_hierarchical'] && $args['limit_depth'] && $this->get_attribute( 'taxonomyDepth' ) ) {
			$data_tax_depth = absint( $this->get_attribute( 'taxonomyDepth' ) );
		}

		$term_query_args = array(
			'taxonomy'   => $taxonomy_name,
			'orderby'    => $args['order_by'],
			'order'      => $args['order_dir'],
			'hide_empty' => $args['hide_empty'],
		);

		if ( $args['terms_conditions'] === 'include_terms' ) {
			$term_query_args['include'] = $args['terms'];
		} elseif ( $args['terms_conditions'] === 'exclude_terms' ) {
			if ( $args['is_hierarchical'] ) {
				$term_query_args['exclude_tree'] = $args['terms'];
			} else {
				$term_query_args['exclude'] = $args['terms'];
			}
		}
		$taxonomy_terms   = WP_Data::get_terms( $term_query_args );
		$taxonomy_options = new Taxonomy_Options();

		$taxonomy_options->init( $taxonomy_name, $taxonomy_terms, $this->get_id(), $args['show_count'], $args['show_count_brackets'] );
		if ( $args['is_hierarchical'] ) {
			if ( $this->get_attribute( 'inputType' ) === 'checkbox' || $this->get_attribute( 'inputType' ) === 'radio' ) {
				$options = $taxonomy_options->get_hierarchical_term_options( 'nested', $data_tax_depth );
			} else {
				$options = $taxonomy_options->get_hierarchical_term_options( 'flat', $data_tax_depth );
			}
		} else {
			$options = $taxonomy_options->get_term_options();
		}

		$this->update_connected_data( 'taxonomyParents', $taxonomy_options->get_all_term_parents() );
		$this->update_connected_data( 'termIdentifiers', (array) $taxonomy_options->get_all_term_identifiers() );

		$navigates_taxonomy_archive = $this->navigates_taxonomy_archive();
		if ( $navigates_taxonomy_archive ) {
			$this->update_connected_data( 'navigatesTaxonomyArchive', $navigates_taxonomy_archive );
		}

		return $options;
	}

	/**
	 * Add an option to the options array.
	 *
	 * Allows for short circuiting and bypassing the option.
	 *
	 * @since 3.0.0
	 *
	 * @param array $options The existing options.
	 * @param array $option  The option to add.
	 * @param int   $field_id The field ID.
	 */
	public static function add_option_to_array( &$options, $option, $field_id ) {

		// Legacy support for incorrectly named action.
		Deprecations::add_filter( 'search-filter/field/choice/option', '3.2.0', 'search-filter/fields/choice/option' );
		$option = apply_filters( 'search-filter/field/choice/option', $option, $field_id );

		// Filter a single choice option.
		$option = apply_filters( 'search-filter/fields/choice/option', $option, $field_id );

		if ( isset( $option['indexValue'] ) ) {
			unset( $option['indexValue'] );
		}
		if ( ! empty( $option ) ) {
			$options[] = $option;
		}
	}

	/**
	 * Gets the URL name for the field.
	 *
	 * @return string
	 */
	public function get_url_name() {

		if ( ! $this->get_attribute( 'dataType' ) ) {
			return parent::get_url_name();
		}

		$url_name = '';
		if ( 'post_attribute' === $this->get_attribute( 'dataType' ) ) {
			$data_source = $this->get_attribute( 'dataPostAttribute' );
			$url_name    = $data_source;
		} elseif ( 'taxonomy' === $this->get_attribute( 'dataType' ) ) {
			$data_source = $this->get_attribute( 'dataTaxonomy' );
			$url_name    = $data_source;
		} else {
			return parent::get_url_name();
		}
		// Legacy support for incorrectly named filter.
		Deprecations::add_filter( 'search-filter/field/url_name', '3.2.0', 'search-filter/fields/field/url_name' );
		$url_name = apply_filters( 'search-filter/field/url_name', $url_name, $this );
		// Filter the URL name.
		$url_name = apply_filters( 'search-filter/fields/field/url_name', $url_name, $this );
		return $url_name;
	}

	/**
	 * If the setting is enabled, supply a URL for the field
	 * when its a taxonomy.
	 *
	 * @return array
	 */
	public function get_url_template() {
		if ( ! $this->has_init() ) {
			return parent::get_url_template();
		}

		if ( ! $this->get_attribute( 'dataType' ) ) {
			return parent::get_url_template();
		}

		if ( $this->get_attribute( 'dataType' ) === 'taxonomy' ) {
			$taxonomy_name = $this->get_attribute( 'dataTaxonomy' );

			if ( empty( $taxonomy_name ) ) {
				return parent::get_url_template();
			}

			$query = Query::get_instance( $this->get_query_id() );
			if ( is_wp_error( $query ) ) {
				return parent::get_url_template();
			}

			// Check the connected query is set to archive and taxonomy.
			if ( $query->get_attribute( 'integrationType' ) !== 'archive' ) {
				return parent::get_url_template();
			}

			// If the query is a post type archive, then we need to check the setting for filtering taxonomy archives.
			if ( $query->get_attribute( 'archiveType' ) === 'post_type' ) {
				// Check the setting for filtering taxonomy archives.
				$archive_filter_taxonomies = $query->get_attribute( 'archiveFilterTaxonomies' );
				if ( ! $archive_filter_taxonomies || $archive_filter_taxonomies === 'none' ) {
					return parent::get_url_template();
				}

				// If using custom taxonomy filtering, ensure one of those matches the taxonomy in question.
				if ( $archive_filter_taxonomies === 'custom' ) {
					$archive_post_type_taxonomies = $query->get_attribute( 'archivePostTypeTaxonomies' ) ?? array();
					if ( ! in_array( $taxonomy_name, $archive_post_type_taxonomies, true ) ) {
						return parent::get_url_template();
					}
				}
				// Ensure query has filtering taxonomy archives enabled.
				if ( $this->get_attribute( 'taxonomyNavigatesArchive' ) !== 'yes' ) {
					return parent::get_url_template();
				}
			} elseif ( $query->get_attribute( 'archiveType' ) === 'taxonomy' ) {
				if ( ! $this->navigates_taxonomy_archive() ) {
					return parent::get_url_template();
				}
			}

			// Now check the post types of the taxonomy.
			if ( Template_Data::taxonomy_term_has_multiple_post_types( $taxonomy_name ) ) {
				return parent::get_url_template();
			}

			// Get taxonomy URL.
			return Template_Data::get_term_template_link( $taxonomy_name );
		}
		return parent::get_url_template();
	}

	/**
	 * Sort a string by length.
	 *
	 * @since 3.0.0
	 *
	 * @param  string $a The first string to compare.
	 * @param  string $b The second string to compare.
	 * @return int
	 */
	public static function sort_string_by_length( $a, $b ) {
		return strlen( $b ) - strlen( $a );
	}


	/**
	 * Add the options to the json data object.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public function get_json_data() {

		if ( ! $this->has_init() ) {
			return array();
		}

		$json_data = parent::get_json_data();

		$json_data['options'] = $this->get_options();

		return $json_data;
	}

	/**
	 * Gets the WP_Query args based on the field value.
	 *
	 * @since 3.0.0
	 *
	 * @param array $query_args The query args to apply.
	 * @return array
	 */
	public function apply_wp_query_args( $query_args = array() ) {
		if ( ! $this->has_init() ) {
			return $this->return_apply_wp_query_args( $query_args );
		}

		// Only set post_type if a value is selected.
		if ( ! $this->has_values() ) {
			return $this->return_apply_wp_query_args( $query_args );
		}

		$values = $this->get_values();

		$query_values = array();
		// Now check things like data type and data source, to figure out which part of the query should be updated.
		if ( 'post_attribute' === $this->get_attribute( 'dataType' ) ) {
			$data_source = $this->get_attribute( 'dataPostAttribute' );

			if ( 'post_type' === $data_source ) {
				$post_type_options = $this->get_attribute( 'dataPostTypes' );
				// If no post type is selected, then get the available post types
				// from the query.
				if ( empty( $post_type_options ) ) {
					// If no post type is chosen, then choose all available from the query attributes.
					$post_type_options = $this->get_query_attribute( 'postTypes' );
				}
				// If there are still no post types (error), then return early.
				if ( empty( $post_type_options ) ) {
					return $this->return_apply_wp_query_args( $query_args );
				}

				// Only allow filtering by a valid post type.
				foreach ( $values as $post_type ) {
					// Make sure the post type selected is a valid option chosen in dataPostTypes attribute.
					if ( in_array( $post_type, $post_type_options, true ) ) {
						$query_values[] = $post_type;
					}
				}
				$query_args['post_type'] = $query_values;

				return $this->return_apply_wp_query_args( $query_args );

			} elseif ( 'post_status' === $data_source ) {
				if ( empty( $this->get_attribute( 'dataPostStati' ) ) ) {
					return $this->return_apply_wp_query_args( $query_args );
				}
				if ( ! is_array( $this->get_attribute( 'dataPostStati' ) ) ) {
					return $this->return_apply_wp_query_args( $query_args );
				}

				foreach ( $values as $post_status ) {
					// Make sure the post type selected is a valid option chosen in dataPostTypes attribute.
					if ( in_array( $post_status, $this->get_attribute( 'dataPostStati' ), true ) ) {
						$query_values[] = $post_status;
					}
				}
				$query_args['post_status'] = $query_values;
			}
		} elseif ( 'taxonomy' === $this->get_attribute( 'dataType' ) ) {
			$taxonomy_name = $this->get_attribute( 'dataTaxonomy' );
			foreach ( $values as $tax_term ) {
				if ( term_exists( $tax_term, $taxonomy_name ) ) {
					$query_values[] = $tax_term;
				}
			}
			if ( empty( $query_values ) ) {
				return $this->return_apply_wp_query_args( $query_args );
			}
			if ( ! isset( $query_args['tax_query'] ) ) {
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required for taxonomy filtering.
				$query_args['tax_query'] = array();
			}

			// TODO - figure out how to handle this in relation to other taxonomies being set
			// in the query already (ie via the loop block).
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required for taxonomy filtering.
			$query_args['tax_query']['relation'] = 'AND';

			$compare_type = 'IN';
			if ( $this->get_attribute( 'multipleMatchMethod' ) ) {
				$compare_type = $this->get_attribute( 'multipleMatchMethod' ) === 'all' ? 'AND' : 'IN';
			} elseif ( $this->get_attribute( 'taxonomyCompare' ) ) {
				$compare_type = $this->get_attribute( 'taxonomyCompare' );
			}
			if ( $compare_type === 'AND' ) {
				$sub_tax_query = array(
					'relation' => 'AND',
				);
				foreach ( $query_values as $value ) {
					$sub_tax_query[] = array(
						'taxonomy' => $taxonomy_name,
						'field'    => 'slug',
						'terms'    => array( $value ),
					);
				}
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required for taxonomy filtering.
				$query_args['tax_query'][] = $sub_tax_query;
			} else {
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required for taxonomy filtering.
				$query_args['tax_query'][] = array(
					array(
						'taxonomy' => $taxonomy_name,
						'field'    => 'slug',
						'compare'  => 'IN',
						'terms'    => $query_values,
					),
				);
			}
		}
		return $this->return_apply_wp_query_args( $query_args );
	}

	/**
	 * Return the WP_Query args after applying the filters.
	 *
	 * @since 3.0.0
	 *
	 * @param array $query_args The query args to apply.
	 * @return array
	 */
	private function return_apply_wp_query_args( $query_args ) {
		// Legacy support for incorrectly named filter.
		Deprecations::add_filter( 'search-filter/field/choice/wp_query_args', '3.2.0', 'search-filter/fields/choice/wp_query_args' );
		$query_args = apply_filters( 'search-filter/field/choice/wp_query_args', $query_args, $this );

		// Filter the field query args.
		$query_args = apply_filters( 'search-filter/fields/choice/wp_query_args', $query_args, $this );
		return parent::apply_wp_query_args( $query_args );
	}

	/**
	 * Figure out if we are using a single select input type.
	 *
	 * TODO - this is mostly used to determine things related to
	 * taxonomy archives, but if we add more field types dynamically
	 * there is no good way to add more field types to this logic.
	 * Extending the class and overwriting the method is one approach
	 * maybe we should include a hook too.
	 */
	public function is_single_select() {
		$input_type = $this->get_attribute( 'inputType' );
		$multiple   = $this->get_attribute( 'multiple' );

		if ( $input_type === 'checkbox' ) {
			return false;
		} elseif ( $input_type === 'select' && $multiple === 'yes' ) {
			return false;
		}
		return true;
	}


	/**
	 * Parses a value from the URL or archive.
	 */
	public function parse_url_value() {

		if ( $this->get_attribute( 'dataType' ) !== 'taxonomy' ) {
			parent::parse_url_value();
			return;
		}

		if ( ! $this->is_single_select() ) {
			parent::parse_url_value();
			return;
		}

		if ( ! $this->navigates_taxonomy_archive() ) {
			parent::parse_url_value();
			return;
		}

		$taxonomy = $this->get_attribute( 'dataTaxonomy' );

		if ( ! Template_Data::is_taxonomy_archive( $taxonomy ) ) {
			parent::parse_url_value();
			return;
		}

		$term = get_queried_object();

		// Check if $term is a term object.
		if ( ! is_a( $term, 'WP_Term' ) ) {
			parent::parse_url_value();
			return;
		}

		global $wp_query;

		/*
		 * We want to make sure that we don't detect anything here
		 * if the archive has multiple terms, eg: yoursite.com/category/term1+term2
		 */
		if ( ! isset( $wp_query->tax_query->queried_terms[ $taxonomy ] ) ) {
			parent::parse_url_value();
			return;
		}

		if ( count( $wp_query->tax_query->queried_terms[ $taxonomy ]['terms'] ) !== 1 ) {
			parent::parse_url_value();
			return;
		}

		$term_slugs = array( $term->slug );
		$this->set_values( $term_slugs );
	}

	/**
	 * Check if the field is a taxonomy field.
	 *
	 * @return bool True if the field is a taxonomy field, false otherwise.
	 */
	public function is_taxonomy_field() {
		return $this->get_attribute( 'dataType' ) === 'taxonomy';
	}

	/**
	 * Checks if the field is a taxonomy archive field.
	 *
	 * A taxonomy archive field is one that changes the taxonomy archive
	 * when interacted with, changing the url like yoursite.com/category/term1
	 * to yoursite.com/category/term2.
	 *
	 * @return bool
	 */
	public function navigates_taxonomy_archive() {

		// Only taxonomy fields can be tax archive filters.
		if ( $this->get_attribute( 'dataType' ) !== 'taxonomy' ) {
			return apply_filters( 'search-filter/fields/choice/navigates_taxonomy_archive', false, $this );
		}

		// If the field is not set to use the archive value, then it can't be a tax archive filter.
		if ( $this->get_attribute( 'taxonomyNavigatesArchive' ) !== 'yes' ) {
			return apply_filters( 'search-filter/fields/choice/navigates_taxonomy_archive', false, $this );
		}

		// Only single select fields can be tax archive filters (for now).
		if ( ! $this->is_single_select() ) {
			return apply_filters( 'search-filter/fields/choice/navigates_taxonomy_archive', false, $this );
		}

		// A field can be a tax archive filter when the query is  post type with tax filtering enabled, or
		// if its a tax archive query and the field taxonomy matches the query taxonomy.
		$query_id = $this->get_query_id();

		$query = Query::get_instance( $query_id );

		if ( is_wp_error( $query ) ) {
			return apply_filters( 'search-filter/fields/choice/navigates_taxonomy_archive', false, $this );
		}

		// If the query is not an archive then the field can't be a tax archive filter.
		if ( $query->get_attribute( 'integrationType' ) !== 'archive' ) {
			return apply_filters( 'search-filter/fields/choice/navigates_taxonomy_archive', false, $this );
		}

		// Not strictly necessary but its worth doing a sanity check in case a query was updated
		// without the field updating after (so it retains archive settings when it shouldn't).
		$field_taxonomy = $this->get_attribute( 'dataTaxonomy' );

		$archive_type = $query->get_attribute( 'archiveType' );
		if ( $archive_type === 'post_type' ) {
			// We need to check that the the taxonomy is enabled in the query settings.
			$archive_filter_taxonomies   = $query->get_attribute( 'archiveFilterTaxonomies' );
			$can_filter_taxonomy_archive = false;
			if ( $archive_filter_taxonomies === 'all' ) {
				$can_filter_taxonomy_archive = true;
			} elseif ( $archive_filter_taxonomies === 'custom' ) {
				$archive_post_type_taxonomies = $query->get_attribute( 'archivePostTypeTaxonomies' ) ?? array();
				if ( in_array( $field_taxonomy, $archive_post_type_taxonomies, true ) ) {
					$can_filter_taxonomy_archive = true;
				}
			}

			// If the query is a post type archive, and all taxonomies has been selected, or thee current taxonomy
			// was specifically selected, then the field can be a taxonomy archive filter.
			if ( $can_filter_taxonomy_archive ) {
				return apply_filters( 'search-filter/fields/choice/navigates_taxonomy_archive', $field_taxonomy, $this );
			}
		}

		$query_taxonomy = $query->get_attribute( 'archiveTaxonomy' );
		if ( $archive_type === 'taxonomy' && $query_taxonomy === $field_taxonomy ) {
			return apply_filters( 'search-filter/fields/choice/navigates_taxonomy_archive', $field_taxonomy, $this );
		}

		return apply_filters( 'search-filter/fields/choice/navigates_taxonomy_archive', false, $this );
	}
}
