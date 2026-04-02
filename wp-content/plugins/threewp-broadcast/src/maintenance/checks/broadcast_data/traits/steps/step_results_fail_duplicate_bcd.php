<?php

namespace threewp_broadcast\maintenance\checks\broadcast_data\traits\steps;

trait step_results_fail_duplicate_bcd
{
	public function step_results_fail_duplicate_bcd( $o )
	{
		if ( count( $this->data->duplicate_bcd ) < 1 )
			return;

		$button = $o->form->primary_button( 'duplicate_bcd' )
			->value( 'Delete the duplicate broadcast data objects in the database' );

		if ( $button->pressed() )
		{
			$batch_size = 200;
			$deleted = 0;
			foreach( $this->data->duplicate_bcd as $id => $ignore )
			{
				$o->bc->sql_delete_broadcast_data( $id );
				$this->data->duplicate_bcd->forget( $id );
				$deleted++;
				if ( $deleted >= $batch_size )
					break;
			}
			$remaining = count( $this->data->duplicate_bcd );
			if ( $remaining > 0 )
			{
				$o->bc->message( sprintf( 'Deleted %d duplicate broadcast data objects. %d remaining.', $deleted, $remaining ) );
				$o->r .= $this->next_step( 'results' );
			}
			else
			{
				$o->bc->message( 'The duplicate broadcast data objects have been deleted.' );
			}
			return;
		}

		$o->r .= $o->bc->h3( 'Duplicate broadcast data' );

		$o->r .= $o->bc->p( 'The following broadcast data already has an associated blog and post.' );
		$table = $o->bc->table();
		$row = $table->head()->row();
		$row->th()->text( 'Broadcast data row ID' );
		$row->th()->text( 'Belonging to post' );

		foreach( $this->data->duplicate_bcd as $id => $ignore )
		{
			$bcd = $this->data->broadcast_data->get( $id );
			if ( ! $bcd )
				continue;
			$row = $table->body()->row();
			$row->td()->text_( $id );
			$row->td()->text_( $this->blogpost( $bcd->blog_id, $bcd->post_id ) );
		}

		$o->r .= $table;
		$o->r .= $o->bc->p( $button->display_input() );
	}
}
