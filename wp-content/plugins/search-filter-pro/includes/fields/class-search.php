<?php
/**
 * Handles the frontend display of the fields
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 */

namespace Search_Filter_Pro\Fields;

use Search_Filter\Fields\Search as Search_Base;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * New Base Search class to support additional data types and functionality.
 */
class Search extends Search_Base {

	/**
	 * The type of the field.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public static $type = 'search';

	/**
	 * Apply the query_args for regular WP queries.
	 *
	 * @since 3.0.0
	 *
	 * @param    array $query_args    The WP query args to update.
	 * @return   array    The updated WP query args.
	 */
	public function apply_wp_query_args( $query_args = array() ) {

		if ( ! $this->has_init() ) {
			return $this->return_apply_wp_query_args( $query_args );
		}

		// Only set post_type if a value is selected.
		if ( ! $this->has_values() ) {
			return $this->return_apply_wp_query_args( $query_args );
		}
		// Handle the built in data types.
		$data_type = $this->get_attribute( 'dataType' );
		$value     = $this->get_value();
		if ( $data_type === 'post_attribute' || empty( $data_type ) ) {
			$attribute_data_type = $this->get_attribute( 'dataPostAttribute' );
			if ( ( $attribute_data_type === 'default' ) || ( $attribute_data_type === '' ) ) {
				$query_args['s'] = $this->get_value();

				// If we have ordering by relevance set in the query, then override the orderby
				// completely.  Relevance doesn't work when combined with multiple order parameter,
				// and it should be the only one set when searching.
				if ( ! isset( $query_args['orderby'] ) || ! is_array( $query_args['orderby'] ) ) {
					$query_args['orderby'] = array();
				}

				$should_set_relevance = false;
				foreach ( $query_args['orderby'] as $order_by => $order_dir ) {
					if ( $order_by === 'relevance' ) {
						$should_set_relevance = true;
						break;
					}
				}
				if ( $should_set_relevance ) {
					$query_args['orderby'] = 'relevance';
					if ( isset( $query_args['order'] ) ) {
						unset( $query_args['order'] );
					}
				}
			} elseif ( $attribute_data_type === 'post_type' ) {
				$post_types              = $this->search_post_type_labels( $value );
				$query_args['post_type'] = $post_types;
				// An empty post type will default to `post` so we need to make the query return 0 results.
				if ( empty( $post_types ) ) {
					$query_args = $this->add_fail_query_args( $query_args );
				}
			} elseif ( $attribute_data_type === 'post_status' ) {
				$post_stati                = $this->search_post_stati_labels( $value );
				$query_args['post_status'] = $post_stati;
				// An empty post status will default to `publish` so we need to make the query return 0 results.
				if ( empty( $post_stati ) ) {
					$query_args = $this->add_fail_query_args( $query_args );
				}
			}
		} elseif ( $data_type === 'taxonomy' ) {
			$taxonomy_name  = $this->get_attribute( 'dataTaxonomy' );
			$taxonomy_terms = $this->search_taxonomy_term_labels( $value, $taxonomy_name, 'slug' );
			if ( ! isset( $query_args['tax_query'] ) ) {
				$query_args['tax_query'] = array();
			}
			if ( ! empty( $taxonomy_terms ) ) {
				$query_args['tax_query'][] = array(
					'taxonomy' => $taxonomy_name,
					'field'    => 'slug',
					'terms'    => $taxonomy_terms,
				);
			} else {
				$query_args = $this->add_fail_query_args( $query_args );
			}
		} elseif ( $data_type === 'custom_field' ) {
			if ( ! isset( $query_args['meta_query'] ) ) {
				$query_args['meta_query'] = array();
			}
			$custom_field               = $this->get_attribute( 'dataCustomField' );
			$query_args['meta_query'][] = array(
				'key'     => $custom_field,
				'value'   => $value,
				'compare' => 'LIKE',
			);
			return $query_args;
		}
		return parent::apply_wp_query_args( $query_args );
	}


	/**
	 * Add the query args to generate a "failed" query (one with no results).
	 *
	 * @since 3.0.0
	 *
	 * @param    array $query_args    The query args to update.
	 * @return   array    The updated query args.
	 */
	public function add_fail_query_args( $query_args ) {
		$query_args['post__in'] = array( 0 );
		return $query_args;
	}

	/**
	 * Search the post titles.
	 *
	 * @since 3.0.0
	 *
	 * @param    string $search_term    The search term to search for.
	 * @return   array    The matched post titles.
	 */
	public function search_post_titles( $search_term ) {
		$matched_post_titles = array();

		$query = \Search_Filter\Queries\Query::find(
			array(
				'id' => $this->get_query_id(),
			)
		);

		if ( is_wp_error( $query ) ) {
			return $matched_post_titles;
		}

		$query_post_types = $query->get_attribute( 'postTypes' );

		$search_query = new \WP_Query(
			array(
				'post_type'      => $query_post_types,
				's'              => $search_term,
				'search_columns' => array( 'post_name', 'post_title' ),
				'posts_per_page' => 10, // TODO - make this configurable.
				'page'           => 1,
			)
		);

		foreach ( $search_query->posts as $post ) {
			$matched_post_titles[] = $post->post_title;
		}

		return $matched_post_titles;
	}

	/**
	 * Search the post type labels.
	 *
	 * @since 3.0.0
	 *
	 * @param    string $search_term    The search term to search for.
	 * @param    string $return    The return type to return.
	 * @return   array    The matched post type labels.
	 */
	public function search_post_type_labels( $search_term, $return = 'name' ) {
		$matched_post_types = array();

		$query = \Search_Filter\Queries\Query::find(
			array(
				'id' => $this->get_query_id(),
			)
		);

		if ( is_wp_error( $query ) ) {
			return $matched_post_types;
		}

		$query_post_types = $query->get_attribute( 'postTypes' );
		foreach ( $query_post_types as $query_post_type ) {
			$post_type = get_post_type_object( $query_post_type );
			// Check to see if the post type label starts with the search term.
			if ( strpos( strtolower( $post_type->label ), strtolower( $search_term ) ) === 0 ) {
				if ( $return === 'name' ) {
					$matched_post_types[] = $post_type->name;
				} elseif ( $return === 'label' ) {
					$matched_post_types[] = $post_type->label;
				}
			}
		}

		return $matched_post_types;
	}

	/**
	 * Search the post stati labels.
	 *
	 * @since 3.0.0
	 *
	 * @param    string $search_term    The search term to search for.
	 * @param    string $return    The return type to return.
	 * @return   array    The matched post stati labels.
	 */
	public function search_post_stati_labels( $search_term, $return = 'name' ) {
		$matched_post_stati = array();

		$query = \Search_Filter\Queries\Query::find(
			array(
				'id' => $this->get_query_id(),
			)
		);

		if ( is_wp_error( $query ) ) {
			return $matched_post_stati;
		}

		$query_post_stati = $query->get_attribute( 'postStatus' );

		foreach ( $query_post_stati as $query_post_status ) {
			$post_status = get_post_status_object( $query_post_status );
			// Check to see if the post type label starts with the search term.
			if ( strpos( strtolower( $post_status->label ), strtolower( $search_term ) ) === 0 ) {
				if ( $return === 'name' ) {
					$matched_post_stati[] = $post_status->name;
				} elseif ( $return === 'label' ) {
					$matched_post_stati[] = $post_status->label;
				}
			}
		}
		return $matched_post_stati;
	}

	/**
	 * Search the taxonomy term labels.
	 *
	 * @since 3.0.0
	 *
	 * @param    string $search_term    The search term to search for.
	 * @param    string $taxonomy_name    The taxonomy name to search for.
	 * @param    string $return    The return type to return.
	 * @return   array    The matched taxonomy term labels.
	 */
	public function search_taxonomy_term_labels( $search_term, $taxonomy_name, $return = 'name' ) {
		$matched_terms = array();

		global $wpdb;

		$terms_conditions = $this->get_attribute( 'taxonomyTermsConditions' );
		$terms            = $this->get_attribute( 'taxonomyTerms' );

		// Enforce the terms to be numbers.
		$terms = array_map( 'absint', $terms );

		$terms_sql = '';
		if ( $terms_conditions === 'include_terms' ) {
			$terms_sql = $wpdb->prepare( " AND {$wpdb->terms}.term_id IN (%d)", implode( ',', $terms ) );
		} elseif ( $terms_conditions === 'exclude_terms' ) {
			$terms_sql = $wpdb->prepare( " AND {$wpdb->terms}.term_id NOT IN (%d)", implode( ',', $terms ) );
		}

		$search_query = $wpdb->prepare( "SELECT {$wpdb->terms}.name, {$wpdb->terms}.slug FROM {$wpdb->terms} LEFT JOIN {$wpdb->term_taxonomy} ON {$wpdb->terms}.term_id = {$wpdb->term_taxonomy}.term_id WHERE {$wpdb->term_taxonomy}.taxonomy = %s AND {$wpdb->terms}.name LIKE %s", $taxonomy_name, $search_term . '%' );
		if ( ! empty( $terms_sql ) ) {
			$search_query .= $terms_sql;
		}

		$query_result = $wpdb->get_results( $search_query );

		foreach ( $query_result as $term ) {
			if ( $return === 'name' ) {
				$matched_terms[] = $term->name;
			} elseif ( $return === 'slug' ) {
				$matched_terms[] = $term->slug;
			}
		}
		return $matched_terms;
	}

	/**
	 * Search the custom fields.
	 *
	 * @since 3.0.0
	 *
	 * @param    string $search_term    The search term to search for.
	 * @param    string $custom_field    The custom field to search for.
	 * @return   array    The matched custom fields.
	 */
	public function search_custom_fields( $search_term, $custom_field ) {
		global $wpdb;
		$matched_values = array();

		$where = '';
		if ( $search_term !== '' ) {
			$where = $wpdb->prepare( " WHERE meta_value LIKE '%s' AND meta_key='%s' ", $search_term . '%', $custom_field );
		}

		$query_result = $wpdb->get_results(
			"
			SELECT DISTINCT(`meta_value`) 
			FROM $wpdb->postmeta
			$where
			ORDER BY `meta_value` ASC
			LIMIT 0, 10
			"
		);

		foreach ( $query_result as $k => $v ) {
			$matched_values[] = $v->meta_value;
		}

		return $matched_values;
	}
}
