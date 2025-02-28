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
	 * The option values with corresponding labels.
	 *
	 * Only needed when using the control -> selection field.
	 *
	 * @var array
	 */
	protected $options_labels = array();

	/**
	 * Get the default attributes for the field.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public function get_default_attributes() {
		// TODO - defaults should be set based on the fields settings.
		// We need to apply the `dependsOn`/ conditional logic to get the necessary defaults.
		// We should probably also "clean" the attributes before saving to remove settings/keys
		// that are not needed anymore by the field.
		$defaults         = \Search_Filter\Fields\Settings::get_defaults_by_context( 'admin/field/choice' );
		$defaults['type'] = 'choice';
		$defaults         = apply_filters( 'search-filter/field/default_attributes', $defaults, $this );

		return $defaults;
	}
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
	protected function create_options() {

		if ( ! $this->has_init() ) {
			return;
		}

		do_action( 'search-filter/fields/filter/choice/create_options/start', $this );
		$options_data = array(
			'options' => array(),
			'labels'  => array(),
		);

		// Now check things like data type and data source, to figure out which options should be generated
		// get the options for the field - used by all choice decendants.
		if ( 'post_attribute' === $this->attributes['dataType'] ) {
			$data_source = $this->attributes['dataPostAttribute'];
			if ( 'post_type' === $data_source ) {

				$post_type_options = $this->get_attribute( 'dataPostTypes' );

				if ( empty( $post_type_options ) ) {
					// If no post type is chosen, then choose all available from the query attributes.
					$query = Query::find( array( 'id' => $this->get_query_id() ) );
					if ( ! is_wp_error( $query ) ) {
						$post_type_options = $query->get_attribute( 'postTypes' );
					}
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
							$options_data['options'],
							array(
								'value' => $post_type->name,
								'label' => html_entity_decode( $post_type->label ),
							),
							$this->get_id()
						);
						$options_data['labels'][ $post_type->name ] = html_entity_decode( $post_type->label );
					}
				}
			} elseif ( 'post_status' === $data_source ) {
				if ( empty( $this->attributes['dataPostStati'] ) ) {
					return;
				}
				if ( ! is_array( $this->attributes['dataPostStati'] ) ) {
					return;
				}

				$post_stati = WP_Data::get_post_stati();

				foreach ( $post_stati as $post_status ) {
					if ( in_array( $post_status->name, $this->attributes['dataPostStati'], true ) ) {

						self::add_option_to_array(
							$options_data['options'],
							array(
								'value' => $post_status->name,
								'label' => html_entity_decode( $post_status->label ),
							),
							$this->get_id()
						);

						$options_data['labels'][ $post_status->name ] = html_entity_decode( $post_status->label );
					}
				}
			}

			// Sort according to the order direction.
			$order           = $this->get_attribute( 'inputOptionsOrder' ) ? $this->get_attribute( 'inputOptionsOrder' ) : 'label';
			$order_direction = $this->get_attribute( 'inputOptionsOrderDir' ) ? $this->get_attribute( 'inputOptionsOrderDir' ) : 'asc';
			if ( $order === 'label' ) {
				$options_data['options'] = Util::sort_assoc_array_by_property( $options_data['options'], $order, 'alphabetical', $order_direction );
			} elseif ( $order === 'count' ) {
				$options_data['options'] = Util::sort_assoc_array_by_property( $options_data['options'], 'count', 'numerical', $order_direction );
			}
		} elseif ( 'taxonomy' === $this->attributes['dataType'] ) {

			$data_source = $this->get_attribute( 'dataTaxonomy' );
			$order_dir   = '';
			if ( $this->get_attribute( 'taxonomyOrderDir' ) === 'asc' ) {
				$order_dir = 'ASC';
			} elseif ( $this->get_attribute( 'taxonomyOrderDir' ) === 'desc' ) {
				$order_dir = 'DESC';
			}
			$args         = array(
				'order_by'            => $this->get_attribute( 'taxonomyOrderBy' ) !== 'default' ? $this->get_attribute( 'taxonomyOrderBy' ) : '',
				'order_dir'           => $order_dir,
				'hide_empty'          => $this->get_attribute( 'hideEmpty' ) === 'yes',
				'terms_conditions'    => $this->get_attribute( 'taxonomyTermsConditions' ),
				'terms'               => $this->get_attribute( 'taxonomyTerms' ),
				'is_hierarchical'     => $this->get_attribute( 'taxonomyHierarchical' ) === 'yes',
				'limit_depth'         => $this->get_attribute( 'limitTaxonomyDepth' ) === 'yes',
				'show_count'          => $this->get_attribute( 'showCount' ) === 'yes',
				'show_count_brackets' => $this->get_attribute( 'showCountBrackets' ) === 'yes',
			);
			$options_data = $this->get_taxonomy_options_data( $data_source, $args );

			// Even though we probably order the options ok with count above, better do it
			// this way by count to support extending it via the indexer (so the options are
			// reshuffled according the currently highest count value)
			$order_by_count = $this->get_attribute( 'taxonomyOrderBy' ) === 'count';
			if ( $order_by_count === 'count' ) {
				$options_data['options'] = Util::sort_assoc_array_by_property( $options_data['options'], 'count', 'numerical', $this->get_attribute( 'taxonomyOrderDir' ) );
			}
		}

		// Legacy support for custom options, replaced by options_data.
		$options_data['options'] = apply_filters( 'search-filter/field/choice/options', $options_data['options'], $this );
		// Allow custom options + labels.
		$options_data = apply_filters( 'search-filter/field/choice/options_data', $options_data, $this );

		do_action( 'search-filter/fields/filter/choice/create_options/finish', $this );

		$this->set_options( $options_data['options'] );
		$this->set_options_labels( $options_data['labels'] );
	}

	/**
	 * Sets options labels.
	 *
	 * @param array $options_labels Array of options labels.
	 */
	public function set_options_labels( $options_labels ) {
		$this->options_labels = $options_labels;
		$this->update_connected_data( 'optionsLabels', (object) $options_labels );
	}

	/**
	 * Get the taxonomy options.
	 *
	 * @param string $taxonomy_name The taxonomy name.
	 * @param array  $args          The arguments.
	 *
	 * @return array
	 */
	public function get_taxonomy_options_data( $taxonomy_name, $args = array() ) {

		if ( empty( $taxonomy_name ) ) {
			return array(
				'options' => array(),
				'labels'  => array(),
			);
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
		if ( $args['is_hierarchical'] && $args['limit_depth'] && isset( $this->attributes['taxonomyDepth'] ) ) {
			$data_tax_depth = (int) $this->attributes['taxonomyDepth'];
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
			if ( $this->attributes['inputType'] === 'checkbox' || $this->attributes['inputType'] === 'radio' ) {
				$options = $taxonomy_options->get_hierarchical_term_options( 'nested', $data_tax_depth );
			} else {
				$options = $taxonomy_options->get_hierarchical_term_options( 'flat', $data_tax_depth );
			}
		} else {
			$options = $taxonomy_options->get_term_options();
		}

		$labels = $taxonomy_options->get_options_labels();

		$this->update_connected_data( 'taxonomyParents', $taxonomy_options->get_all_term_parents() );
		$this->update_connected_data( 'termIdentifiers', (array) $taxonomy_options->get_all_term_identifiers() );

		if ( $this->filters_taxonomy_archives() ) {
			$this->update_connected_data( 'filtersTaxonomyArchive', $this->get_attribute( 'dataTaxonomy' ) );
		}

		return array(
			'options' => $options,
			'labels'  => $labels,
		);
	}

	/**
	 * Add an option to the options array.
	 *
	 * Allows for short circuiting and bypassing the option.
	 *
	 * @since 3.0.0
	 *
	 * @param array  $options The existing options.
	 * @param array  $option  The option to add.
	 * @param string $field_id The field ID.
	 *
	 * @return array The updated options.
	 */
	public static function add_option_to_array( &$options, $option, $field_id ) {
		$option = apply_filters( 'search-filter/field/choice/option', $option, $field_id );

		if ( isset( $option['indexValue'] ) ) {
			unset( $option['indexValue'] );
		}
		if ( ! empty( $option ) ) {
			$options[] = $option;
		}
	}

	/**
	 * Calculate the hierarchy depth for parent-child relationship
	 *
	 * @param  \WP_Term $term     The term to calculate the depth for.
	 * @param  array    $term_list The list of terms to calculate the depth for.
	 * @return int
	 */
	protected function get_taxonomy_depth( $term, $term_list ) {
		if ( ! $term || $term->parent === 0 ) {
			return 1;
		}

		$find_item = function ( $id ) use ( $term_list ) {
			foreach ( $term_list as $item ) {
				if ( $id === $item->term_id ) {
					return $item;
				}
			}
		};

		$depth_level = 1;
		$parent_id   = $term->parent;

		while ( $parent = $find_item( $parent_id ) ) {
			++$depth_level;
			$parent_id = $parent->parent;
		}

		return $depth_level;
	}

	/**
	 * Gets the URL name for the field.
	 *
	 * @return string
	 */
	public function get_url_name() {
		if ( ! $this->has_init() ) {
			return parent::get_url_name();
		}

		if ( ! isset( $this->attributes['dataType'] ) ) {
			return parent::get_url_name();
		}

		$url_name = '';
		if ( 'post_attribute' === $this->attributes['dataType'] ) {
			$data_source = $this->attributes['dataPostAttribute'];
			$url_name    = $data_source;
		} elseif ( 'taxonomy' === $this->attributes['dataType'] ) {
			$data_source = isset( $this->attributes['dataTaxonomy'] ) ? $this->attributes['dataTaxonomy'] : '';
			$url_name    = $data_source;
		} else {
			return parent::get_url_name();
		}
		$url_name = apply_filters( 'search-filter/field/url_name', $url_name, $this );
		return $url_name;
	}

	/**
	 * If the setting is enabled, supply a URL for the field
	 * when its a taxonomy.
	 */
	public function get_url_template() {
		if ( ! $this->has_init() ) {
			return parent::get_url_template();
		}

		if ( ! isset( $this->attributes['dataType'] ) ) {
			return parent::get_url_template();
		}

		if ( $this->attributes['dataType'] === 'taxonomy' ) {
			$taxonomy_name = isset( $this->attributes['dataTaxonomy'] ) ? $this->attributes['dataTaxonomy'] : '';

			if ( empty( $taxonomy_name ) ) {
				return parent::get_url_template();
			}

			$query = Query::find( array( 'id' => $this->get_query_id() ) );
			if ( is_wp_error( $query ) ) {
				return parent::get_url_template();
			}

			// Check the connected query is set to archive and taxonomy.
			if ( $query->get_attribute( 'integrationType' ) !== 'archive' ) {
				return parent::get_url_template();
			}
			if ( $query->get_attribute( 'archiveType' ) === 'post_type' ) {
				// Check the setting for filtering taxonomy archives.
				if ( $query->get_attribute( 'archiveFilterTaxonomies' ) !== 'yes' ) {
					return parent::get_url_template();
				}
				if ( $this->get_attribute( 'taxonomyFilterArchive' ) !== 'yes' ) {
					return parent::get_url_template();
				}
			} elseif ( $query->get_attribute( 'archiveType' ) === 'taxonomy' ) {

				$should_use_archive_value = $this->get_attribute( 'taxonomyFilterArchive' ) === 'yes' || $this->field_is_taxonomy_archive();

				if ( ! $should_use_archive_value ) {
					return parent::get_url_template();
				}
				// Only use archive url if the archive taxonomy matches the taxonomy field.
				if ( $query->get_attribute( 'taxonomy' ) !== $taxonomy_name ) {
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
		if ( 'post_attribute' === $this->attributes['dataType'] ) {
			$data_source = $this->attributes['dataPostAttribute'];

			if ( 'post_type' === $data_source ) {
				if ( empty( $this->attributes['dataPostTypes'] ) ) {
					return $this->return_apply_wp_query_args( $query_args );
				}
				if ( ! is_array( $this->attributes['dataPostTypes'] ) ) {
					// TODO - throw error.
					return $this->return_apply_wp_query_args( $query_args );
				}
				foreach ( $values as $post_type ) {
					// Make sure the post type selected is a valid option chosen in dataPostTypes attribute.
					if ( in_array( $post_type, $this->attributes['dataPostTypes'], true ) ) {
						$query_values[] = $post_type;
					}
				}
				$query_args['post_type'] = $query_values;

				return $this->return_apply_wp_query_args( $query_args );

			} elseif ( 'post_status' === $data_source ) {
				if ( empty( $this->attributes['dataPostStati'] ) ) {
					return $this->return_apply_wp_query_args( $query_args );
				}
				if ( ! is_array( $this->attributes['dataPostStati'] ) ) {
					return $this->return_apply_wp_query_args( $query_args );
				}

				foreach ( $values as $post_status ) {
					// Make sure the post type selected is a valid option chosen in dataPostTypes attribute.
					if ( in_array( $post_status, $this->attributes['dataPostStati'], true ) ) {
						$query_values[] = $post_status;
					}
				}
				$query_args['post_status'] = $query_values;
			}
		} elseif ( 'taxonomy' === $this->attributes['dataType'] ) {
			$taxonomy_name = $this->attributes['dataTaxonomy'];
			foreach ( $values as $tax_term ) {
				if ( term_exists( $tax_term, $taxonomy_name ) ) {
					$query_values[] = $tax_term;
				}
			}
			if ( empty( $query_values ) ) {
				return $this->return_apply_wp_query_args( $query_args );
			}
			if ( ! isset( $query_args['tax_query'] ) ) {
				$query_args['tax_query'] = array();
			}

			// TODO - figure out how to handle this in relation to other taxonomies being set
			// in the query already (ie via the loop block).
			$query_args['tax_query']['relation'] = 'AND';

			$compare_type = 'IN';
			if ( isset( $this->attributes['multipleMatchMethod'] ) ) {
				$compare_type = $this->attributes['multipleMatchMethod'] === 'all' ? 'AND' : 'IN';
			} elseif ( isset( $this->attributes['taxonomyCompare'] ) ) {
				$compare_type = $this->attributes['taxonomyCompare'];
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
				$query_args['tax_query'][] = $sub_tax_query;
			} else {
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
		$query_args = \apply_filters( 'search-filter/field/choice/wp_query_args', $query_args, $this );
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

	private function field_is_taxonomy_archive() {
		// Check if the the parent query is using the taxonomy filter archive.
		// If so, then we should automatically allow.
		$query_id = $this->get_query_id();
		$query    = Query::find(
			array(
				'id'     => $query_id,
				'status' => 'enabled',
			)
		);

		if ( is_wp_error( $query ) ) {
			return false;
		}
		if ( $query->get_attribute( 'integrationType' ) !== 'archive' ) {
			return false;
		}
		if ( $query->get_attribute( 'archiveType' ) !== 'taxonomy' ) {
			return false;
		}
		$field_taxonomy = $this->get_attribute( 'dataTaxonomy' );
		$query_taxonomy = $query->get_attribute( 'taxonomy' );

		// Make sure we use the archive value wnen the query is a tax archive query,
		// and the field is linked to that taxonomy.
		if ( $field_taxonomy === $query_taxonomy ) {
			return true;
		}
		return false;
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

		$taxonomy                 = $this->get_attribute( 'dataTaxonomy' );
		$should_use_archive_value = $this->get_attribute( 'taxonomyFilterArchive' ) === 'yes' || $this->field_is_taxonomy_archive();

		if ( ! $should_use_archive_value ) {
			parent::parse_url_value();
			return;
		}

		// Check if we are on this tax archive.
		if ( ! is_tax( $taxonomy ) ) {
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

	public function filters_taxonomy_archives() {
		if ( $this->get_attribute( 'dataType' ) !== 'taxonomy' ) {
			return false;
		}

		if ( ! $this->is_single_select() ) {
			return false;
		}

		$should_use_archive_value = $this->get_attribute( 'taxonomyFilterArchive' ) === 'yes' || $this->field_is_taxonomy_archive();

		return $should_use_archive_value;
	}
}
