<?php

namespace threewp_broadcast\maintenance\checks\broadcast_data\traits\steps;

trait step_results_fail_missing_post
{
	public function step_results_fail_missing_post( $o )
	{
		if ( count( $this->data->missing_post ) < 1 )
			return;

		$button = $o->form->primary_button( 'missing_post' )
			->value( 'Delete the broadcast data objects in the database' );

		if ( $button->pressed() )
		{
			// Delete missing post broadcast data in batches to avoid memory spikes.
			$batch_size = 200;
			$deleted = 0;
			foreach( $this->data->missing_post as $id => $info )
			{
				$o->bc->sql_delete_broadcast_data( $id );
				$this->data->missing_post->forget( $id );
				$deleted++;
				if ( $deleted >= $batch_size )
					break;
			}
			$remaining = count( $this->data->missing_post );
			if ( $remaining > 0 )
			{
				$o->bc->message( sprintf( 'Deleted %d broadcast data objects. %d remaining.', $deleted, $remaining ) );
				$o->r .= $this->next_step( 'results' );
			}
			else
			{
				$o->bc->message( 'The broadcast data objects without existing posts have been deleted.' );
			}
			return;
		}

		$o->r .= $o->bc->h3( 'Missing posts' );

		$o->r .= $o->bc->p( 'The following broadcast data objects belong to posts that no longer exist.' );
		$table = $o->bc->table();
		$row = $table->head()->row();
		$row->th()->text( 'Broadcast data row ID' );
		$row->th()->text( 'Belonging to post' );

		foreach( $this->data->missing_post as $id => $info )
		{
			$row = $table->body()->row();
			$row->td()->text_( $id );
			$row->td()->text_( 'Post %s on %s',
				$info[ 'post_id' ],
				$this->blogname( $info[ 'blog_id' ] )
			);
		}

		$o->r .= $table;
		$o->r .= $o->bc->p( $button->display_input() );
	}
}