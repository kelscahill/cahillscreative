<?php

namespace threewp_broadcast\maintenance\checks\view_taxonomy_info;

/**
	@brief		View taxonomy info.
	@since		2019-03-27 11:16:47
**/
class check
	extends \threewp_broadcast\maintenance\checks\check
{
	public function get_description()
	{
		// Maintenance check description
		return __( 'View taxonomy information.', 'threewp-broadcast' );
	}

	public function get_name()
	{
		// Maintenance check name
		return __( 'View taxonomy info', 'threewp-broadcast' );
	}

	public function step_start()
	{
		$o = new \stdClass;
		$o->inputs = new \stdClass;
		$o->form = $this->broadcast()->form();
		$o->r = '';

		$o->inputs->taxonomy_slug = $o->form->text( 'taxonomy_slug' )
			->description( __( 'The slug of the taxonomy to view.', 'threewp-broadcast' ) )
			->label( __( 'Taxonomy slug', 'threewp-broadcast' ) )
			->value( 'category' );

		$button = $o->form->primary_button( 'dump' )
			// Button
			->value( __( 'Find and display the info', 'threewp-broadcast' ) );

		if ( $o->form->is_posting() )
		{
			$o->form->post()->use_post_value();
			$this->view_taxonomy_info( $o );
		}

		$o->r .= $o->form->open_tag();
		$o->r .= $o->form->display_form_table();
		$o->r .= $o->form->close_tag();
		return $o->r;
	}

	/**
		@brief		Show the taxonomy info.
		@since		2019-06-21 11:55:38
	**/
	public function view_taxonomy_info( $o )
	{
		$taxonomy_slug = $o->inputs->taxonomy_slug->get_value();

		$terms = get_terms( array(
			'taxonomy' => $taxonomy_slug,
			'hide_empty' => false,
		) );

		foreach( $terms as $term )
		{
			$text = sprintf( '<pre>%s%s</pre>',
				var_export( $term, true ),
				var_export( get_term_meta( $term->term_id ), true )
				);
			$o->r .= $this->broadcast()->message( $text );
		}
	}
}
