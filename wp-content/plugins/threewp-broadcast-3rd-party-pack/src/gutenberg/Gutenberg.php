<?php

namespace threewp_broadcast\premium_pack\gutenberg;

/**
	@brief			Support for various Gutenberg things.
	@plugin_group	3rd party compatability
	@since			2024-06-17 19:42:37
**/
class Gutenberg
	extends \threewp_broadcast\premium_pack\base
{
	use \threewp_broadcast\premium_pack\classes\broadcast_generic_post_ui_trait;

	/**
		@brief		Query loop block.
		@since		2024-08-22 11:09:30
	**/
	public $query_block;

	/**
		@brief		Constructor.
		@since		2024-06-17 20:14:05
	**/
	public function _construct()
	{
		//$this->query_block = new Query_Block();
		$this->add_action( 'threewp_broadcast_menu' );
	}

	/**
		@brief		threewp_broadcast_menu
		@since		2020-05-13 22:00:22
	**/
	public function threewp_broadcast_menu( $action )
	{
		$action->menu_page
			->submenu( 'broadcast_gutenberg' )
			->callback_this( 'admin_tabs' )
			->menu_title( 'Gutenberg' )
			->page_title( 'Broadcast Gutenberg' );
	}

	/**
		@brief		Add our tabs to the menu.
		@since		2020-05-13 22:01:21
	**/
	public function admin_tabs( $action )
	{
		$tabs = $this->tabs();

		$tabs->tab( 'bc_gb_patterns' )
			->callback_this( 'bc_gb_patterns' )
			->heading( 'Patterns' )
			->name( 'Patterns' );

		echo $tabs->render();
	}

	/**
		@brief		Broadcast patterns.
		@since		2024-08-22 11:25:30
	**/
	public function bc_gb_patterns()
	{
		echo $this->broadcast_generic_post_ui( [
			'post_type' => 'wp_block',
			'label_plural' => 'patterns',
			'label_singular' => 'pattern',
		] );
	}

}
