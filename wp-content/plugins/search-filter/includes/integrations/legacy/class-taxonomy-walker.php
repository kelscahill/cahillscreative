<?php
/**
 * Taxonomy Walker Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter/Integrations/Legacy
 */

namespace Search_Filter\Integrations\Legacy;

/**
 * Taxonomy Walker Class
 *
 * @since 3.0.0
 */
class Taxonomy_Walker extends \Walker_Category {

	/**
	 * The type of walker.
	 *
	 * @var string
	 */
	private $type = '';
	/**
	 * The multi depth value.
	 *
	 * @var int
	 */
	private $multidepth = 0;
	/**
	 * The multi last ID value.
	 *
	 * @var int
	 */
	private $multilastid = 0;
	/**
	 * The multi last depth change value.
	 *
	 * @var int
	 */
	private $multilastdepthchange = 0;

	/**
	 * Constructor.
	 *
	 * @param string $type The type.
	 */
	public function __construct( $type = 'checkbox' ) {
		$this->type = $type;
	}

	/**
	 * Start the element.
	 *
	 * @param string $output The output.
	 * @param object $category The category.
	 * @param int    $depth The depth.
	 * @param array  $args The arguments.
	 * @param int    $id The id.
	 */
	public function start_el( &$output, $category, $depth = 0, $args = array(), $id = 0 ) {

		$defaults = array(
			'sf_name'            => '',
			'use_desc_for_title' => 0,
			'feed_image'         => '',
			'feed'               => '',
			'feed_type'          => '',
			'show_count'         => 0,
			'style'              => 'list',
			'current_category'   => 0,
			'defaults'           => array(),
		);
		$args     = wp_parse_args( $args, $defaults );

		if ( $this->type === 'list' ) {
			$cat_name = esc_attr( $args['sf_name'] );
			$cat_name = apply_filters( 'list_cats', $cat_name, $category );
			$link     = '<a href="' . esc_url( get_term_link( $category ) ) . '" ';
			// TODO - see if we can change for strict comparison.
			if ( $args['use_desc_for_title'] === 0 || empty( $category->description ) ) {
				/* translators: %s: category name */
				$link .= 'title="' . esc_attr( sprintf( __( 'View all posts filed under %s' ), $cat_name ) ) . '"';
			} else {
				$link .= 'title="' . esc_attr( wp_strip_all_tags( apply_filters( 'category_description', $category->description, $category ) ) ) . '"';
			}
			$link .= '>';
			$link .= $cat_name . '</a>';

			if ( ! empty( $args['feed_image'] ) || ! empty( $args['feed'] ) ) {
				$link .= ' ';

				if ( empty( $args['feed_image'] ) ) {
					$link .= '(';
				}

				$link .= '<a href="' . esc_url( get_term_feed_link( $category->term_id, $category->taxonomy, $args['feed_type'] ) ) . '"';
				$alt   = '';
				$title = '';
				$name  = '';
				if ( empty( $args['feed'] ) ) {
					/* translators: %s: category name */
					$alt = ' alt="' . esc_attr( sprintf( __( 'Feed for all posts filed under %s' ), $cat_name ) ) . '"';
				} else {
					$title = ' title="' . esc_attr( $args['feed'] ) . '"';
					$alt   = ' alt="' . esc_attr( $args['feed'] ) . '"';
					$name  = $args['feed'];
					$link .= $title;
				}

				$link .= '>';

				if ( empty( $args['feed_image'] ) ) {
					$link .= $name;
				} else {
					$link .= "<img src=' " . $args['feed_image'] . "'" . $alt . $title . ' />';
				}

				$link .= '</a>';

				if ( empty( $args['feed_image'] ) ) {
					$link .= ')';
				}
			}

			if ( ! empty( $args['show_count'] ) ) {
				$link .= ' (' . intval( $category->count ) . ')';
			}

			if ( 'list' === $args['style'] ) {
				$output .= "\t<li";
				$class   = 'cat-item cat-item-' . $category->term_id;
				if ( ! empty( $args['current_category'] ) ) {
					$_current_category = get_term( $args['current_category'], $category->taxonomy );
					if ( (int) $category->term_id === (int) $args['current_category'] ) {
						$class .= ' current-cat';
					} elseif ( (int) $category->term_id === (int) $_current_category->parent ) {
						$class .= ' current-cat-parent';
					}
				}
				$output .= ' class="' . esc_attr( $class ) . '"';
				$output .= ">$link\n";
			} else {
				$output .= "\t$link<br />\n";
			}
		} elseif ( ( $this->type === 'checkbox' ) || ( $this->type === 'radio' ) ) {

			$cat_name = esc_attr( $category->name );
			$cat_id   = $category->term_id;
			$cat_name = apply_filters( 'list_cats', $cat_name, $category );

			// Check a default has been set.
			$checked = '';

			$noselected = count( $args['defaults'] );

			if ( $noselected > 0 ) {
				foreach ( $args['defaults'] as $defaultid ) {
					if ( (int) $defaultid === (int) $cat_id ) {
						$checked = ' checked="checked"';
					}
				}
			}

			$link = "<label><input type='" . esc_attr( $this->type ) . "' name='" . esc_attr( $args['sf_name'] ) . "[]' value='" . esc_attr( $cat_id ) . "'" . $checked . ' /> ' . $cat_name;
			if ( ! empty( $args['show_count'] ) ) {
				$link .= ' (' . intval( $category->count ) . ')';
			}

			$link .= '</label>';

			if ( 'list' === $args['style'] ) {
				$output .= "\t<li";
				$class   = 'cat-item cat-item-' . $category->term_id;
				if ( ! empty( $args['current_category'] ) ) {
					$_current_category = get_term( $args['current_category'], $category->taxonomy );
					if ( (int) $category->term_id === (int) $args['current_category'] ) {
						$class .= ' current-cat';
					} elseif ( (int) $category->term_id === (int) $_current_category->parent ) {
						$class .= ' current-cat-parent';
					}
				}
				$output .= ' class="' . esc_attr( $class ) . '"';
				$output .= ">$link\n";
			} else {
				$output .= "\t$link<br />\n";
			}
		} elseif ( $this->type === 'multiselect' ) {

			$cat_name = esc_attr( $category->name );
			$cat_id   = $category->term_id;
			$cat_name = apply_filters( 'list_cats', $cat_name, $category );

			// Check a default has been set.
			$checked = '';

			$noselected = count( $args['defaults'] );

			if ( $noselected > 0 ) {
				foreach ( $args['defaults'] as $defaultid ) {
					if ( (int) $defaultid === (int) $cat_id ) {
						$checked = ' selected="selected"';
					}
				}
			}

			$catogory_parent = absint( $category->parent );

			// Custom  depth calculations.
			if ( $catogory_parent === 0 ) {
				// Then this has no parent so reset depth.
				$this->multidepth = 0;
			} elseif ( $catogory_parent === absint( $this->multilastid ) ) {
				++$this->multidepth;
				$this->multilastdepthchange = $this->multilastid;
			// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedElseif -- Intentionally empty, condition checked but no action needed.
			} elseif ( $catogory_parent === absint( $this->multilastdepthchange ) ) {
				// Then this is also a child with the same parent so don't change depth.
			} elseif ( $this->multidepth > 0 ) {
				// Then this has a different parent so must be lower depth.
				--$this->multidepth;
			}

			$pad  = str_repeat( '&nbsp;', $this->multidepth * 3 );
			$link = '<option class="level-' . esc_attr( (string) $this->multidepth ) . "\" value='" . $cat_id . "'$checked >" . esc_html( $pad . $cat_name );

			if ( ! empty( $args['show_count'] ) ) {
				$link .= '&nbsp;&nbsp;(' . intval( $category->count ) . ')';
			}

			$link   .= '</option>';
			$output .= "\t$link\n";

			$this->multilastid = (int) $cat_id;
		}
	}

	/**
	 * End the element.
	 *
	 * @param string $output The output.
	 * @param object $page The page.
	 * @param int    $depth The depth.
	 * @param array  $args The arguments.
	 */
	public function end_el( &$output, $page, $depth = 0, $args = array() ) {
		if ( $this->type === 'list' ) {
			if ( 'list' !== $args['style'] ) {
				return;
			}
			$output .= "</li>\n";
		} elseif ( ( $this->type === 'checkbox' ) || ( $this->type === 'radio' ) ) {
			if ( 'list' !== $args['style'] ) {
				return;
			}
			$output .= "</li>\n";
		} elseif ( $this->type === 'multiselect' ) {
			if ( 'list' !== $args['style'] ) {
				return;
			}
			$output .= "</option>\n";
		}
	}

	/**
	 * Start the level.
	 *
	 * @param string $output The output.
	 * @param int    $depth The depth.
	 * @param array  $args The arguments.
	 */
	public function start_lvl( &$output, $depth = 0, $args = array() ) {
		if ( $this->type === 'list' ) {
			if ( 'list' !== $args['style'] ) {
				return;
			}

			$indent  = str_repeat( "\t", $depth );
			$output .= "$indent<ul class='children'>\n";
		} elseif ( ( $this->type === 'checkbox' ) || ( $this->type === 'radio' ) ) {
			if ( 'list' !== $args['style'] ) {
				return;
			}

			$indent  = str_repeat( "\t", $depth );
			$output .= "$indent<ul class='children'>\n";
		}
	}

	/**
	 * End the level.
	 *
	 * @param string $output The output.
	 * @param int    $depth The depth.
	 * @param array  $args The arguments.
	 */
	public function end_lvl( &$output, $depth = 0, $args = array() ) {
		if ( $this->type === 'list' ) {
			if ( 'list' !== $args['style'] ) {
				return;
			}
			$indent  = str_repeat( "\t", $depth );
			$output .= "$indent</ul>\n";
		} elseif ( ( $this->type === 'checkbox' ) || ( $this->type === 'radio' ) ) {
			if ( 'list' !== $args['style'] ) {
				return;
			}

			$indent  = str_repeat( "\t", $depth );
			$output .= "$indent</ul>\n";
		}
	}
}
