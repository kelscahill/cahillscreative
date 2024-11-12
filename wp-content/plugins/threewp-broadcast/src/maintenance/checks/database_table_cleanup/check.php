<?php

namespace threewp_broadcast\maintenance\checks\database_table_cleanup;

/**
	@brief		Remove orphaned database tables.
	@since		2024-04-16 22:06:04
**/
class check
	extends \threewp_broadcast\maintenance\checks\check
{
	public function get_description()
	{
		return 'Remove orphaned site tables from the database.';
	}

	public function get_name()
	{
		return 'Database table cleanup';
	}

	public function step_start()
	{
		$o = new \stdClass;
		$o->inputs = new \stdClass;
		$o->form = $this->broadcast()->form2();
		$o->r = '';

		$orphaned_tables = $this->find_orphaned_tables();

		$table = ThreeWP_Broadcast()->table();
		$row = $table->head()->row();
		$table->bulk_actions()
			->form( $o->form )
			->add( 'Delete', 'delete' )
			->cb( $row );

		$th = $row->th( 'table' )->text( 'Table name' );

		$o->r .= wpautop( sprintf( '%s tables found.', count( $orphaned_tables->collection( 'tables' ) ) ) );

		foreach( $orphaned_tables->collection( 'tables' ) as $orphaned_table )
		{
			$row = $table->body()->row();
			$table->bulk_actions()->cb( $row, $orphaned_table );
			$row->td( 'table' )->text( $orphaned_table );
		}

		$delete_selected_tables = $o->form->secondary_button( 'delete_selected_tables' )
			->value( 'Delete selected tables' );

		if ( $o->form->is_posting() )
		{
			$o->form->post()->use_post_value();

			if ( $table->bulk_actions()->pressed() )
			{
				switch ( $table->bulk_actions()->get_action() )
				{
					case 'delete':
						$ids = $table->bulk_actions()->get_rows();
						global $wpdb;


						foreach( $ids as $id )
						{
							$query = sprintf( "DROP TABLE `%s`", $id );
							$this->broadcast()->debug( $query );
							$wpdb->query( $query );
						}

						$o->r .= $this->broadcast()->info_message_box()
							->_( __( 'The selected tables have been deleted. Please reload the page.', 'threewp_broadcast' ) );
					break;
				}
			}
		}

		$o->r .= $o->form->open_tag();
		$o->r .= $table;
		$o->r .= $o->form->close_tag();
		return $o->r;
	}

	/**
		@brief		Find all unused tables.
		@since		2024-04-16 22:15:22
	**/
	public function find_orphaned_tables()
	{
		global $wpdb;
		$bc = ThreeWP_Broadcast();
		$r = $bc->collection();

		$query = sprintf( "SHOW TABLES" );
		$bc->debug( $query );
		$tables = $wpdb->get_col( $query );

		$query = sprintf( "SELECT `blog_id` FROM `%s`", $wpdb->blogs );
		$bc->debug( $query );
		$ids = $wpdb->get_col( $query );
		$ids = array_combine( $ids, $ids );

		$base_prefix = $wpdb->base_prefix;

		foreach( $tables as $table )
		{
			$blog_id = str_replace( $base_prefix, '', $table );
			$blog_id = preg_replace( '/_.*/', '', $blog_id );

			// Ignore base tables.
			if( intval( $blog_id ) < 1 )
				continue;

			if ( isset( $ids[ $blog_id ] ) )
				continue;

			$string_blog_id = intval( $blog_id ) . '';

			// Ignore base tables that contain numbers.
			if ( strlen( $string_blog_id ) != strlen( $blog_id ) )
				continue;

			$r->collection( 'tables' )
				->append( $table );
			$r->collection( 'blogs' )
				->collection( $blog_id )
				->append( $table );
		}

		return $r;
	}
}
