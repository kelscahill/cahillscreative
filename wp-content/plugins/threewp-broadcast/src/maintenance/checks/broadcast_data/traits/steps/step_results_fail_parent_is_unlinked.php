<?php

namespace threewp_broadcast\maintenance\checks\broadcast_data\traits\steps;

trait step_results_fail_parent_is_unlinked
{
	public function step_results_fail_parent_is_unlinked( $o )
	{
		if ( count( $this->data->parent_is_unlinked ) < 1 )
			return;

		$button = $o->form->primary_button( 'parent_is_unlinked' )
			->value( 'Link the parent posts to their child posts' );

		if ( $button->pressed() )
		{
			$batch_size = 200;
			$processed = 0;
			foreach( $this->data->parent_is_unlinked as $id => $blog_post )
			{
				$bcd = $this->data->broadcast_data->get( $id );
				if ( ! $bcd )
				{
					$this->data->parent_is_unlinked->forget( $id );
					continue;
				}
				$parent_blog_id = key( $blog_post );
				$parent_post_id = reset( $blog_post );
				$parent_bcd = $o->bc->get_post_broadcast_data( $parent_blog_id, $parent_post_id );
				$parent_bcd->add_linked_child( $bcd->blog_id, $bcd->post_id );
				$o->bc->set_post_broadcast_data( $parent_blog_id, $parent_post_id, $parent_bcd );
				$this->data->parent_is_unlinked->forget( $id );
				$processed++;
				if ( $processed >= $batch_size )
					break;
			}
			$remaining = count( $this->data->parent_is_unlinked );
			if ( $remaining > 0 )
			{
				$o->bc->message( sprintf( 'Linked %d parents. %d remaining.', $processed, $remaining ) );
				$o->r .= $this->next_step( 'results' );
			}
			else
			{
				$o->bc->message( 'The parents now have links back to the children.' );
			}
			return;
		}

		$o->r .= $o->bc->h3( 'Unlinked parents' );

		$o->r .= $o->bc->p( 'The following parent posts are not linked back to the child.' );
		$table = $o->bc->table();
		$row = $table->head()->row();
		$row->th()->text( 'Broadcast data row ID' );
		$row->th()->text( 'Belonging to post' );
		$row->th()->text( 'Parent without link' );

		foreach( $this->data->parent_is_unlinked as $id => $blog_post )
		{
			$bcd = $this->data->broadcast_data->get( $id );
			$parent_blog_id = key( $blog_post );
			$parent_post_id = reset( $blog_post );
			$row = $table->body()->row();
			$row->td()->text_( $id );
			$row->td()->text_( $this->blogpost( $bcd->blog_id, $bcd->post_id ) );
			$row->td()->text_( $this->blogpost( $parent_blog_id, $parent_post_id ) );
		}

		$o->r .= $table;
		$o->r .= $o->bc->p( $button->display_input() );
	}
}
