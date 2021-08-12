<?php

namespace threewp_broadcast\premium_pack\gravity_forms
{

/**
	@brief			Adds support for the <a href="http://www.gravityforms.com/">Gravity Forms</a> plugin.
	@plugin_group	3rd party compatability
	@since			2017-05-01 18:38:26
**/
class Gravity_Forms
	extends \threewp_broadcast\premium_pack\classes\Shortcode_Preparser
{
	use \threewp_broadcast\premium_pack\classes\copy_options_trait;
	use \threewp_broadcast\premium_pack\classes\database_trait;

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Inherited
	// --------------------------------------------------------------------------------------------
	/**
		@brief		Constructor.
		@since		2017-04-27 22:46:42
	**/
	public function _construct()
	{
		parent::_construct();
		$this->add_action( 'threewp_broadcast_menu' );
		$this->add_action( 'broadcast_gf_modify_form_meta' );
		$this->gravity_views = new Gravity_Views();
	}

	public function copy_item( $bcd, $item )
	{
		global $wpdb;

		switch_to_blog( $bcd->parent_blog_id );

		$source_prefix = $wpdb->prefix;

		$table = $this->rg_gf_table( 'form', $source_prefix );
		$this->database_table_must_exist( $table );
		// Retrieve the form.
		$query = sprintf( "SELECT * FROM `%s`", $table );
		$this->debug( $query );
		$results = $wpdb->get_results( $query );
		$source_forms = [];
		foreach( $results as $result )
			$source_forms[ $result->id ] = $result;
		$form = $source_forms[ $item->id ];

		// Gravityflow stores the feed order in an OPTION! Why not use the order column in the feeds table?
		$gravityflow_feed_order = get_option( 'gravityflow_feed_order_' . $item->id, true );
		if ( $gravityflow_feed_order )
			$this->debug( 'Current gravityflow feed order: %s', $gravityflow_feed_order );

		restore_current_blog();

		$target_prefix = $wpdb->prefix;

		// No form? Invalid shortcode. Too bad.
		if ( ! $form )
			throw new \Exception( 'No form found.' );

		$table = $this->rg_gf_table( 'form', $target_prefix );
		$this->database_table_must_exist( $table );

		$query = sprintf( "SELECT * FROM `%s`", $table );
		$this->debug( $query );
		$results = $wpdb->get_results( $query );
		$target_forms = [];
		foreach( $results as $result )
			$target_forms[ $result->id ] = $result;

		if ( defined( 'BROADCAST_GRAVITY_FORMS_USE_ID' ) )
			// Find a form with the same ID.
			$result = $target_forms[ $item->id ];
		else
			$result = $this->find_equivalent_form( [
				'source_forms' => $source_forms,
				'target_forms' => $target_forms,
				'key' => 'id',
				'value' => $item->id,
			] );

		$new_form = false;
		if ( ! $result )
		{
			$columns = '`title`, `date_created`, `is_active`, `is_trash`';
			// Force a specific ID?
			if ( defined( 'BROADCAST_GRAVITY_FORMS_USE_ID' ) )
				$columns = '`id`, ' . $columns;
			$query = sprintf( "INSERT INTO `%s` ( %s ) ( SELECT %s FROM `%s` WHERE `id` ='%s' )",
				$this->rg_gf_table( 'form', $target_prefix ),
				$columns,
				$columns,
				$this->rg_gf_table( 'form', $source_prefix ),
				$item->id
			);
			$this->debug( $query );
			$wpdb->get_results( $query );
			$new_form_id = $wpdb->insert_id;
			$new_form = true;
			$this->debug( 'Using new form %s', $new_form_id );
		}
		else
		{
			$new_form_id = $result->id;
			$this->debug( 'Using existing form %s', $new_form_id );
		}

		// Tell others that we've found the equivalent child form on the child blog.
		$action = $this->new_action( 'child_form_located' );
		$action->broadcasting_data = $bcd;
		$action->form_id = $new_form_id;
		$action->new_form = $new_form;
		$action->execute();

		// Update active and trash status.
		// The title can be changed when using the ID define.
		$query = sprintf( "UPDATE `%s` SET `title` = '%s', `is_active` = '%d', `is_trash` = '%d' WHERE `id` = '%s'",
			$this->rg_gf_table( 'form', $target_prefix ),
			$form->title,
			$form->is_active,
			$form->is_trash,
			$new_form_id
		);
		$this->debug( $query );
		$result = $wpdb->get_results( $query );

		// Delete the current form meta.
		$table = $this->rg_gf_table( 'form_meta', $target_prefix );
		$this->database_table_must_exist( $table );
		$query = sprintf( "DELETE FROM `%s` WHERE `form_id` = '%s'",
			$table,
			$new_form_id
		);
		$this->debug( $query );
		$wpdb->query( $query );

		// And reinsert the fresh data.
		$table = $this->rg_gf_table( 'form_meta' );
		$columns = $this->get_database_table_columns_string( $table, [ 'except' => [ 'form_id' ] ] );
		$query = sprintf( "INSERT INTO `%s` ( `form_id`, %s ) ( SELECT %d, %s FROM `%s` WHERE `form_id` ='%s' )",
			$this->rg_gf_table( 'form_meta' , $target_prefix ),
			$columns,
			$new_form_id,
			$columns,
			$this->rg_gf_table( 'form_meta' , $source_prefix ),
			$form->id
		);
		$this->debug( $query );
		$wpdb->get_results( $query );

		// Form feeds
		$table = $this->rg_gf_table( 'addon_feed' );
		$columns = $this->get_database_table_columns_string( $table, [ 'except' => [ 'form_id' ] ] );
		// Old => New
		$feed_ids = [];
		if ( $this->database_table_exists( $table ) )
		{
			// Delete the old ones
			$query = sprintf( "DELETE FROM `%s` WHERE `form_id` = '%s'",
				$this->rg_gf_table( 'addon_feed', $target_prefix ),
				$new_form_id
			);
			$wpdb->query( $query );

			$query = sprintf( "SELECT * FROM `%s` WHERE `form_id` ='%s'",
				$this->rg_gf_table( 'addon_feed', $source_prefix ),
				$form->id
			);
			$this->debug( $query );
			$results = $wpdb->get_results( $query );

			foreach( $results as $result )
			{
				$result_meta = json_decode( $result->meta );
				if ( $result_meta )
				{
					if ( isset( $result_meta->target_form_id ) )
						$result_meta->target_form_id = $this->find_equivalent_form( [
							'source_forms' => $source_forms,
							'target_forms' => $target_forms,
							'key' => 'id',
							'value' => $result_meta->target_form_id,
						] )->id;
					$result->meta = json_encode( $result_meta );
				}
				$result_id = $result->id;
				unset( $result->id );
				$result->form_id = $new_form_id;
				$this->debug( 'Inserting meta %s', $result );
				$wpdb->insert( $table, (array)$result );
				$feed_ids[ $result_id ] = $wpdb->insert_id;
			}
		}

		if ( $gravityflow_feed_order )
		{
			$new_feed_order = [];
			foreach( $gravityflow_feed_order as $old_feed_id )
				$new_feed_order []= $feed_ids[ $old_feed_id ];
			$this->debug( 'Updating gravityflow feed order: %s', $new_feed_order );
			update_option( 'gravityflow_feed_order_' . $new_form_id, $new_feed_order );
		}

		// START: modify form meta.

		$table = $this->rg_gf_table( 'form_meta' );
		$query = sprintf( "SELECT * FROM `%s` WHERE `form_id` = '%s'",
			$table,
			$new_form_id
		);
		$meta = $wpdb->get_row( $query );
		unset( $meta->form_id );

		$action = $this->new_action( 'modify_form_meta' );
		$action->broadcasting_data = $bcd;
		$action->meta = $meta;
		$action->form_id = $new_form_id;
		$action->source_forms = $source_forms;
		$action->target_forms = $target_forms;
		$action->execute();
		$this->debug( 'Updating form meta to %s', $action->meta );
		$wpdb->update( $table, (array)$action->meta, [ 'form_id' => $new_form_id ] );

		// FINISH: modify form meta.

		return $new_form_id;
	}

	/**
		@brief		Return an array of the options to copy.
		@details	This really is a mess. I'm guessing that Gravity Forms has changed developers a couple of times during the years.
		@since		2017-05-01 22:48:56
	**/
	public function get_options_to_copy()
	{
		return [
			'gf_*',
			'gform_*',
			'gravityformsaddon_*',
			'rg_gforms_*',
			'rg_form_*',
		];
	}

	/**
		@brief		Return the name of the shortcode we are looking for.
		@since		2017-01-11 23:03:36
	**/
	public function get_shortcode_name()
	{
		return 'gravityform';
	}

	/**
		@brief		show_copy_options
		@since		2017-05-01 22:47:16
	**/
	public function show_copy_settings()
	{
		echo $this->generic_copy_options_page( [
			'plugin_name' => 'Gravity Forms',
		] );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Add ourselves into the menu.
		@since		2016-01-26 14:00:24
	**/
	public function threewp_broadcast_menu( $action )
	{
		if ( ! is_super_admin() )
			return;

		$action->menu_page
			->submenu( 'threewp_broadcast_gravity_forms' )
			->callback_this( 'show_copy_settings' )
			->menu_title( 'Gravity Forms' )
			->page_title( 'Gravity Forms Broadcast' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Modify the form meta for this form.
		@since		2017-11-20 19:46:36
	**/
	public function broadcast_gf_modify_form_meta( $action )
	{
		$confirmations = json_decode( $action->meta->confirmations );
		if ( $confirmations != false )
		{
			// Handle any page redirects.
			foreach( $confirmations as $confirmation_id => $confirmation )
			{
				if ( $confirmation->type == 'page' )
				{
					$confirmation->pageId = $action->broadcasting_data
						->equivalent_posts()
						->get_or_broadcast( $action->broadcasting_data->parent_blog_id, $confirmation->pageId, get_current_blog_id() );
					$this->debug( 'Setting notification page to %s', $confirmation->pageId );
					$confirmations->$confirmation_id = $confirmation;
				}
			}
			$action->meta->confirmations = json_encode( $confirmations );
		}

		$display_meta = json_decode( $action->meta->display_meta );
		if ( $display_meta != false )
		{
			foreach( $display_meta->fields as $index => $field )
				$display_meta->fields[ $index ]->formId = $action->form_id;
			$display_meta->id = $action->form_id;
			$action->meta->display_meta = json_encode( $display_meta );
		}
	}

	/**
		@brief		Find the equivalent form given a key+value pair from the original form.
		@since		2020-09-01 22:22:27
	**/
	public function find_equivalent_form( $options )
	{
		$options = (object) $options;

		$key = $options->key; // We can't use a double reference, so we extract the key here.

		$found = false;
		foreach( $options->source_forms as $source_form )
			if ( $source_form->$key == $options->value )
				$found = $source_form;
		if ( ! $found )
			return false;

		// We use the title to find the equivalent form.
		$title = $found->title;
		foreach( $options->target_forms as $target_form )
		{
			if ( $target_form->title == $title )
				return $target_form;
		}
		return false;
	}

	/**
		@brief		Decide which table name to return depending on GF version.
		@since		2018-05-07 16:44:38
	**/
	public function rg_gf_table( $table, $prefix = null )
	{
		if ( ! $prefix )
		{
			global $wpdb;
			$prefix = $wpdb->prefix;
		}
		$rg_form_version = get_option( 'rg_form_version' );
		if ( version_compare( $rg_form_version, '2.3' ) > 0 )
			$version = 'gf_';
		else
			$version = 'rg_';
		return sprintf( '%s%s%s',
			$prefix,
			$version,
			$table
		);
	}

}

} // namespace threewp_broadcast\premium_pack\gravity_forms

namespace
{
	/**
		@brief		Return an instance of the gravity forms add-on.
		@since		2017-11-22 19:37:45
	**/
	function broadcast_gravity_forms()
	{
		return \threewp_broadcast\premium_pack\gravity_forms\Gravity_Forms::instance();
	}
}
