<?php

namespace threewp_broadcast\premium_pack\activity_monitor;

use \plainview\sdk_broadcast\collections\collection;

/**
	@brief				Adds a Broadcast hook to the Plainview Activity Monitor, at the same time disabling post related hooks during broadcasting to prevent unnecessary logging.
	@plugin_group		3rd party compatability
	@since				2014-05-06 23:01:59
**/
class Activity_Monitor
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'plainview_activity_monitor_manifest_hooks' );
		$this->add_action( 'pvam_pdf_reports_add_report_type' );
		$this->add_action( 'pvam_pdf_reports_edit_report_type' );
		$this->add_action( 'pvam_pdf_reports_get_report_types' );
		$this->add_action( 'pvam_pdf_reports_save_report_type' );
		$this->add_action( 'threewp_broadcast_broadcasting_finished' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Tell the Activity Monitor which hooks we supply.
		@since		2014-05-06 22:34:55
	**/
	public function plainview_activity_monitor_manifest_hooks( $action )
	{
		$class = new hooks\broadcast;
		$class->register_with( $action->hooks );
	}

	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;
		if ( ! isset( $bcd->activity_monitor ) )
			return;

		$hook = $bcd->activity_monitor->hook;
		$temp_post = clone( $bcd->new_post );
		$temp_post->post_title = get_bloginfo( 'blogname' );
		$hook->html()->append( '%s', $hook->post_html( $temp_post ) );
	}

	public function threewp_broadcast_broadcasting_finished( $action )
	{
		$bcd = $action->broadcasting_data;
		if ( ! isset( $bcd->activity_monitor ) )
			return;

		// Execute the hook
		$this->debug( 'Executing the broadcast hook.' );
		$bcd->activity_monitor->hook->_log();

		// Re-enable all disabled hooks.
		foreach( $bcd->activity_monitor->disabled_hooks as $disabled_hook )
		{
			$this->debug( 'Re-enabling hook %s', get_class( $disabled_hook ) );
			$disabled_hook->disabled( false );
		}
	}

	public function threewp_broadcast_broadcasting_started( $action )
	{
		if ( ! $this->enabled() )
			return;

		$bcd = $action->broadcasting_data;
		$bcd->activity_monitor = new \stdClass;
		$hook = new hooks\broadcast();
		$bcd->activity_monitor->hook = $hook;

		$this->debug( 'Disabling hooks that are related to broadcasts.' );

		// Disable any hooks that are unnecessary doing broadcast (post updated and what not)
		$disabled_hooks = new collection;
		$bcd->activity_monitor->disabled_hooks = $disabled_hooks;

		$action = new \plainview\wordpress\activity_monitor\actions\manifest_hooks;
		$action->execute();
		foreach( $action->hooks as $manifested_hook )
		{
			// Any hooks that have to do with pages being updated and what not should be disabled.
			if ( ! is_subclass_of( $manifested_hook, 'plainview\\wordpress\\activity_monitor\\hooks\\posts' ) )
				continue;
			$this->debug( 'Disabling hook %s', get_class( $manifested_hook ) );
			$disabled_hooks->append( $manifested_hook );
			$manifested_hook->disabled( true );
		}

		// Add the parent post
		$hook->html()->append( 'Broadcasting %s', $hook->post_html( $bcd->post ) );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- AM PDF Reports
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Add the time savings table to the report.
		@since		2015-11-24 12:46:14
	**/
	public function pvam_pdf_reports_add_report_type( $action )
	{
		if ( $action->id != 'broadcast_savings_calculator' )
			return;

		// Conv.
		$pr = \plainview\wordpress\activity_monitor\plugin_pack\pdf_reports\PDF_Reports::instance();

		$html = $pr->get_pdf_css();

		$caption = $action->settings->get( 'caption', '' );
		if ( $caption != '' )
			$html .= sprintf( '<h1>%s</h1>', $caption );

		$html .= ThreeWP_Broadcast()
			->savings_data()
			->get_savings_table();

		// Because TCPDF doesn't understand padding css.
		$html = str_replace( '<table ', '<table cellpadding="2" ', $html );

		$action->pdf->new_page();
		$action->pdf->writeHTML( $html, true, false, true, false, '' );
	}

	/**
		@brief		Edit the report type.
		@since		2015-11-24 17:23:42
	**/
	public function pvam_pdf_reports_edit_report_type( $action )
	{
		if ( $action->id != 'broadcast_savings_calculator' )
			return;

		// Convenience.
		$form = $action->form;
		$report = $action->report;
		$settings = $report->settings( $action->id );

		// Allow the user to select a caption.
		$fs = $form->fieldset( 'fs_caption' );
		// Fieldset label for base settings
		$fs->legend->label( __( 'Caption', 'threewp_broadcast' ) );

		$fs->text( 'caption' )
			// Input description
			->description( __( 'An optional caption at the top of the page.', 'threewp_broadcast' ) )
			// Input label
			->label( __( 'Page caption', 'threewp_broadcast' ) )
			->size( 64, 256 )
			->set_unfiltered_value( $settings->get( 'page_caption', '' ) );
	}

	public function pvam_pdf_reports_get_report_types( $action )
	{
		// Base settings
		$type = new \plainview\wordpress\activity_monitor\plugin_pack\pdf_reports\Type();
		$type->id = 'broadcast_savings_calculator';
		$type->long = __( 'The time savings table from Broadcast.', 'threewp_broadcast' );
		$type->short = __( 'Broadcast time savings', 'threewp_broadcast' );
		$action->add( $type );
	}

	/**
		@brief		Save the report type.
		@since		2015-11-24 17:23:05
	**/
	public function pvam_pdf_reports_save_report_type( $action )
	{
		if ( $action->id != 'broadcast_savings_calculator' )
			return;

		$form = $action->form;
		$settings = $action->report->settings( $action->id );

		$settings->set( 'caption', $form->input( 'caption' )->get_filtered_post_value() );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Are we to use the activity monitor hooks and classes?
		@since		2014-05-06 22:40:35
	**/
	public function enabled()
	{
		return class_exists( '\\plainview\\wordpress\\activity_monitor\\Plainview_Activity_Monitor' );
	}
}
