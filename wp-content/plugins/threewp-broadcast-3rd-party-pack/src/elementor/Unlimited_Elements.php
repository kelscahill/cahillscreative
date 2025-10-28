<?php

namespace threewp_broadcast\premium_pack\elementor;

/**
	@brief			Adds support for the Unlimited Elements plugin.
	@since			2022-04-19 21:14:27
**/
class Unlimited_Elements
	extends \threewp_broadcast\premium_pack\base
{
	use \threewp_broadcast\premium_pack\classes\database_trait;
	use \threewp_broadcast\premium_pack\classes\files_trait;

	/**
		@brief		Constructor.
		@since		2022-04-19 21:27:25
	**/
	public function _construct()
	{
		$this->add_action( 'admin_menu' );
	}

	/**
		@brief		Add our menu item.
		@since		2022-04-19 21:33:13
	**/
	public function admin_menu( $action )
	{
		add_submenu_page(
			'unlimitedelements',
			'Broadcast',
			'Broadcast',
			'manage_options',
			'bc_unlimited_elements',
			[ $this, 'ui_copy_installed_elements' ]
		);
	}

	/**
		@brief		Copy the installed elements from this blog to the specified blog.
		@since		2022-04-19 21:41:25
	**/
	public function copy_installed_elements_to( $blog_id )
	{
		global $wpdb;

		$wp_upload_dir = wp_upload_dir();
		$ac_assets_source_directory = $wp_upload_dir[ 'basedir' ] . DIRECTORY_SEPARATOR . 'ac_assets';
		$source_tables = static::collect_table_names();
		switch_to_blog( $blog_id );
		$target_tables = static::collect_table_names();
		$wp_upload_dir = wp_upload_dir();
		$ac_assets_target_directory = $wp_upload_dir[ 'basedir' ] . DIRECTORY_SEPARATOR . 'ac_assets';
		restore_current_blog();

		// Check that all necessary tables exist.
		foreach( $target_tables as $target_table )
			if ( ! $this->database_table_exists( $target_table ) )
				return $this->debug( 'Table %s does not exist. Skipping this blog.', $target_table );

		foreach( $source_tables as $index => $source_table )
		{
			$target_table = $target_tables[ $index ];

			$query = sprintf( "TRUNCATE TABLE `%s`", $target_table );
			$this->debug( $query );
			$wpdb->get_results( $query );

			$query = sprintf( "INSERT INTO `%s` SELECT * FROM `%s`", $target_table, $source_table );
			$this->debug( $query );
			$wpdb->get_results( $query );
		}

		// Copy the images.
		$this->debug( 'Copying the ac_assets images.' );
		static::copy_recursive( $ac_assets_source_directory, $ac_assets_target_directory );
	}

	/**
		@brief		UI for copying the installed library.
		@since		2022-04-19 21:37:32
	**/
	public function ui_copy_installed_elements()
	{
		$form = $this->form2();
		$r = '';

		$r .= $this->p_( 'This tool will copy the installed elements from this blog to other blogs. The target blogs will have their installed elements overwritten.' );

		$blogs_select = $this->add_blog_list_input( [
			// Blog selection input description
			'description' => __( 'Select one or more blogs to which to copy the installed elements.', 'threewp_broadcast' ),
			'form' => $form,
			// Blog selection input label
			'label' => __( 'Blogs', 'threewp_broadcast' ),
			'multiple' => true,
			'required' => true,
			'name' => 'blogs',
		] );

		$submit = $form->primary_button( 'copy' )
			->value( 'Copy' );

		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			$blog_ids = $blogs_select->get_post_value();
			foreach( $blog_ids as $blog_id )
			{
				// Don't copy to ourselves.
				if ( $blog_id == get_current_blog_id() )
					continue;
				static::copy_installed_elements_to( $blog_id );
			}

			$r .= $this->info_message_box()->_( 'The installed elements have been copied to the selected blogs.' );
		}


		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		// Page heading
		echo $this->wrap( $r, __( 'Broadcast installed elements', 'threewp_broadcast' ) );
	}

	/**
		@brief		Generate the table names to copy.
		@since		2022-04-19 21:50:41
	**/
	public function collect_table_names()
	{
		global $wpdb;

		$r = [];
		foreach( [
			'addonlibrary_addons',
			'addonlibrary_categories',
		] as $table )
			$r []= $wpdb->prefix . $table;
		return $r;
	}
}
