<?php

namespace threewp_broadcast\premium_pack\gravity_forms;

/**
 *	@brief	Convenience class for handling of form data.
 *	@since	2025-02-21 11:10:59
 **/
class Forms_Data
	extends \plainview\sdk_broadcast\collections\Collection
{
	/**
	 * Collect the forms on this blog.
	 *
	 * @since		2025-02-21 11:12:09
	 **/
	public function collect_forms()
	{
		$blog_id = get_current_blog_id();

		global $wpdb;
		$source_prefix = $wpdb->prefix;

		$key = 'initial_collect_forms';
		if ( ! $this->has( $key ) )
			$this->set( $key, $blog_id );

		$bc_gf = broadcast_gravity_forms();
		$table = Gravity_Forms::rg_gf_table( 'form', $source_prefix );

		if ( $bc_gf->database_table_exists( $table ) )
		{
			$query = sprintf( "SELECT * FROM `%s`", $table );
			$bc_gf->debug( $query );
			$results = $wpdb->get_results( $query );
			foreach( $results as $result )
				$this->collection( 'forms' )
					->collection( $blog_id )
					->set( $result->id, $result );
		}
	}

	/**
	 * Return the equivalent form SQL row.
	 *
	 * Uses the form stored using remember_form()
	 *
	 * @since		2025-02-21 11:17:44
	 **/
	public function get_equivalent_form( $blog_id = null, $form_id = null )
	{
		$forms_collection = $this->collection( 'forms' );

		if ( ! $blog_id )
			$form_row = $this->get( 'rememebered_form' );
		else
		{
			$form_row = $forms_collection
				->collection( $blog_id )
				->get( $form_id );
		}

		$current_blog_id = get_current_blog_id();

		if ( ! $forms_collection->has( $current_blog_id ) )
			$this->collect_forms();

		foreach( $forms_collection->collection( $current_blog_id ) as $form_id => $target_form_row )
		{
			if ( $form_row->title == $target_form_row->title )
				return $target_form_row;
		}

		return false;
	}

	/**
	 * Convenience method to find the ID of the equivalent form.
	 *
	 * @since		2025-02-24 17:15:42
	 **/
	public function get_equivalent_form_id( $blog_id = null, $form_id = null )
	{
		$form = $this->get_equivalent_form( $blog_id, $form_id );
		if ( ! $form )
			return 0;

		return $form->id;
	}

	/**
	 * Use this form row as a working form, from which to compare things.
	 *
	 * Such as when asking for get_equivalent_form.
	 *
	 * @since		2025-02-21 11:24:45
	 **/
	public function remember_form( $form_id )
	{
		$blog_id = get_current_blog_id();
		$form_row = $this->collection( 'forms' )
			->collection( $blog_id )
			->get( $form_id );
		$this->set( 'rememebered_form', $form_row );

		return $this;
	}
}
