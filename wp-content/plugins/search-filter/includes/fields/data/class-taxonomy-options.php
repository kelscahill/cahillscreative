<?php
/**
 * Taxonomy Options class.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter
 * @subpackage Search_Filter/Fields/Data
 */

namespace Search_Filter\Fields\Data;

use Search_Filter\Fields\Choice;
use Search_Filter\Query\Template_Data;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Single option class.
 *
 * @since 3.0.0
 */
class Option {
	/**
	 * The data for the option.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	private $data = array();

	/**
	 * Construct an option.
	 *
	 * @since 3.0.0
	 *
	 * @param array $args The data for the option.
	 */
	public function __construct( $args ) {
		$this->data = $args;
	}
	/**
	 * Set the data for the option.
	 *
	 * @since 3.0.0
	 *
	 * @param array $args The data for the option.
	 */
	public function set( $args ) {
		$this->data = $args;
	}
	/**
	 * Update the data for the option.
	 *
	 * @since 3.0.0
	 *
	 * @param array $args The data for the option.
	 */
	public function update( $args ) {
		$this->data = wp_parse_args( $args, $this->data );
	}
	/**
	 * Get the data for this option as an array
	 *
	 * @return array
	 */
	public function get() {
		return $this->data;
	}
}

/**
 * Handles the options for a taxonomy field.
 *
 * @since 3.0.0
 */
class Taxonomy_Options {

	/**
	 * The taxonomy name.
	 *
	 * @var string
	 */
	private $taxonomy_name = '';
	/**
	 * The taxonomy terms.
	 *
	 * @var array
	 */
	private $taxonomy_terms = array();

	/**
	 * The terms by ID.
	 *
	 * @var array
	 */
	private $terms_by_id = array();

	/**
	 * The terms by parent.
	 *
	 * @var array
	 */
	private $terms_by_parent = array();

	/**
	 * The term parents.
	 *
	 * @var array
	 */
	private $term_parents = array();

	/**
	 * The options by ID.
	 *
	 * @var array
	 */
	private $options_by_id = array();

	/**
	 * The term identifiers.
	 *
	 * @var array
	 */
	private $term_identifiers = array();

	/**
	 * The field ID.
	 *
	 * @var int
	 */
	private $field_id = 0;

	/**
	 * Show the count.
	 *
	 * @var bool
	 */
	private $show_count = false;

	/**
	 * Show the count brackets.
	 *
	 * @var bool
	 */
	private $show_count_brackets = false;

	/**
	 * The option values with corresponding labels.
	 *
	 * Necessary when using hierarchical taxonomies to avoid
	 * traversing nested options multiple times.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	private $options_labels = array();
	/**
	 * Initialise the class.
	 *
	 * This is called from the field class.
	 *
	 * @param array $taxonomy_terms The taxonomy terms.
	 * @param int   $field_id       The field ID.
	 */
	public function init( $taxonomy_name, $taxonomy_terms, $field_id, $show_count = false, $show_count_brackets = false ) {

		$this->taxonomy_name       = $taxonomy_name;
		$this->taxonomy_terms      = $taxonomy_terms;
		$this->field_id            = $field_id;
		$this->show_count          = $show_count;
		$this->show_count_brackets = $show_count_brackets;
	}
	/**
	 * Prepare the data needed for generating term options.
	 */
	public function prepare_hierarchical_terms_data() {
		foreach ( $this->taxonomy_terms as $term ) {
			if ( ! isset( $this->terms_by_parent[ $term->parent ] ) ) {
				$this->terms_by_parent[ $term->parent ] = array();
			}
			$this->terms_by_parent[ $term->parent ][] = $term->term_id;
			$this->terms_by_id[ $term->term_id ]      = $term;
			$this->options_by_id[ $term->term_id ]    = $this->create_term_option( $term );

			// We need to expose these for our JS app to use.
			$this->term_parents[ $term->term_id ] = array();
			$this->add_term_identifier( $term );
			$this->add_option_label( $term );
		}
	}

	public function add_term_identifier( $term ) {
		$this->term_identifiers[] = $this->get_term_identifiers( $term );
	}

	public function get_all_term_parents() {
		return $this->term_parents;
	}

	public function get_all_term_identifiers() {
		return $this->term_identifiers;
	}

	public function get_options_labels() {
		return $this->options_labels;
	}

	public function add_option_label( $term ) {
		$this->options_labels[ $term->slug ] = $term->name;
	}

	/**
	 * Get the IDs of child terms for a given term.
	 */
	private function get_child_terms_ids( $term_id ) {
		return isset( $this->terms_by_parent[ $term_id ] ) ? $this->terms_by_parent[ $term_id ] : array();
	}

	public function get_hierarchical_term_options( $type = 'flat', $max_depth = 0 ) {
		$this->prepare_hierarchical_terms_data();
		return $this->get_ordered_term_options_recursive( $type, 0, 0, $max_depth );
	}

	public function get_term_options( $max_depth = 0 ) {
		$options = array();
		foreach ( $this->taxonomy_terms as $term ) {
			$this->add_term_identifier( $term );
			$this->add_option_label( $term );
			$option = $this->create_term_option( $term, 0 );
			do_action( 'search-filter/fields/data/taxonomy/update_option', $option, null );
			Choice::add_option_to_array( $options, $option->get(), $this->field_id );
		}
		return $options;
	}

	public function get_term_identifiers( $term ) {
		return array(
			'id'   => $term->term_id,
			'slug' => $term->slug,
		);
	}
	/**
	 * From an array of terms, create an array of options and ensure their children are
	 * added to the array in the correct order (directly below their parents).
	 *
	 * Additionally adds a depth attribute to each option so we know the level of nesting.
	 *
	 * @param [type]  $terms
	 * @param [type]  $terms_by_parent
	 * @param [type]  $term_id
	 * @param integer $depth
	 * @return array
	 */
	public function get_ordered_term_options_recursive( $type, $term_id, $depth = 0, $max_depth = 0 ) {
		$options_ordered = array();
		$child_terms_ids = $this->get_child_terms_ids( $term_id );

		if ( $max_depth > 0 && $depth >= $max_depth ) {
			return $options_ordered;
		}

		foreach ( $child_terms_ids as $child_term_id ) {
			$term = $this->terms_by_id[ $child_term_id ];

			// We want to know all the parents a child term has, in order.
			if ( $term_id !== 0 ) {
				// Merge parent terms with the parent terms of the current term.
				$this->term_parents[ $child_term_id ] = array_merge( array( $this->get_term_identifiers( $this->terms_by_id[ $term_id ] ) ), $this->term_parents[ $term_id ] );
			}

			$parent_option = null;
			if ( $term->parent > 0 ) {
				$parent_option = $this->options_by_id[ $term->parent ]; // Note the reference.
			}

			Template_Data::set_taxonomy_template( $this->taxonomy_name, $depth, $term );

			$this->options_by_id[ $term->term_id ]->update( array( 'depth' => $depth ) );

			$child_options_ordered = $this->get_ordered_term_options_recursive( $type, $child_term_id, $depth + 1, $max_depth );

			if ( $type === 'flat' ) {
				// Allow the option to be modified inside the loop to avoid having to loop through the options again later.
				Choice::add_option_to_array( $options_ordered, $this->options_by_id[ $term->term_id ]->get(), $this->field_id );
				$options_ordered = array_merge( $options_ordered, $child_options_ordered );
			} elseif ( $type === 'nested' ) {
				$this->options_by_id[ $term->term_id ]->update( array( 'options' => $child_options_ordered ) );
				// Allow the option to be modified inside the loop to avoid having to loop through the options again later.
				Choice::add_option_to_array( $options_ordered, $this->options_by_id[ $term->term_id ]->get(), $this->field_id );
			}
		}
		return $options_ordered;
	}
	/**
	 * Create an option from a taxonomy term.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_Term $term The term to create the option from.
	 * @param integer  $depth The depth of the term.
	 *
	 * @return Option The option.
	 */
	private function create_term_option( $term, $depth = 0 ) {
		$option = array(
			'value'     => $term->slug,
			'label'     => html_entity_decode( $term->name ), // TODO - check if this is a good place to do this.
			'depth'     => $depth,
			'id'        => $term->term_id,
			'parent_id' => $term->parent,
		);

		$option['count'] = $term->count;
		if ( $this->show_count ) {
			if ( $this->show_count_brackets ) {
				$option['countLabel'] = '(' . $term->count . ')';
			} else {
				$option['countLabel'] = $term->count;
			}
		}
		return new Option( $option );
	}
}
