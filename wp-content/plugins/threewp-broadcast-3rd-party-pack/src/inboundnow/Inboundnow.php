<?php

namespace threewp_broadcast\premium_pack\inboundnow;

/**
	@brief			Adds support for the <a href="https://www.inboundnow.com/">Inboundnow Marketing Suite</a>.
	@plugin_group	3rd party compatability
	@since			2018-03-09 09:53:14
**/
class Inboundnow
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		Constructor.
		@since		2018-03-15 20:09:55
	**/
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_get_post_types' );
		new CTA();
		new Forms();
	}

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2018-03-15 20:09:55
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( $bcd->post->post_type == 'automation' )
			$this->restore_automation( $bcd );
		if ( $bcd->post->post_type == 'inbound-forms' )
			$this->restore_form( $bcd );
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2018-03-15 20:09:55
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( $bcd->post->post_type == 'automation' )
			$this->save_automation( $bcd );
	}

	/**
		@brief		Add all of the plugin's post types.
		@since		2018-03-15 20:09:55
	**/
	public function threewp_broadcast_get_post_types( $action )
	{
		$action->add_type( 'automation' );
		$action->add_type( 'inbound-email' );
		$action->add_type( 'inbound-forms' );
		$action->add_type( 'landing-page' );
		$action->add_type( 'wp-call-to-action' );
	}

	/**
		@brief		Save the automation data.
		@since		2018-03-15 20:09:55
	**/
	public function save_automation( $bcd )
	{
		$meta_key = 'inbound_rule';
		$rule = $bcd->custom_fields()->get_single( $meta_key );
		$rule = maybe_unserialize( $rule );
		if ( ! is_array( $rule ) )
			return;
		if ( ! isset( $rule[ 'action_blocks' ] ) )
			return;
		$this->debug( 'Current inbound rules: %s', $rule );
		$this->preparse_trigger_filters( $bcd, $rule[ 'trigger_filters' ] );
	}

	/**
		@brief		Restore the automation data.
		@since		2018-03-15 20:09:55
	**/
	public function restore_automation( $bcd )
	{
		$meta_key = 'inbound_rule';
		$rule = $bcd->custom_fields()->get_single( $meta_key );
		$rule = maybe_unserialize( $rule );
		if ( ! is_array( $rule ) )
			return;
		if ( ! isset( $rule[ 'action_blocks' ] ) )
			return;
		$rule[ 'action_blocks' ] = $this->parse_action_block( $bcd, $rule[ 'action_blocks' ] );
		$rule[ 'trigger_filters' ] = $this->parse_trigger_filters( $bcd, $rule[ 'trigger_filters' ] );

		// Save the new rule.
		$this->debug( 'Saving new inbound rule: %s', $rule );
		$bcd->custom_fields()
			->child_fields()
			->update_meta( $meta_key, $rule );
	}

	/**
		@brief		Restore the form data.
		@since		2018-03-15 20:09:55
	**/
	public function restore_form( $bcd )
	{
		$bcd->custom_fields()
			->child_fields()
			->update_meta( 'inbound_form_created_on', $bcd->new_post( 'ID' ) );

		$shortcode = $bcd->custom_fields()->get_single( 'inbound_shortcode' );
		$shortcode = str_replace( '"' . $bcd->post->ID . '"', '"' . $bcd->new_post( 'ID' ) . '"', $shortcode );
		$bcd->custom_fields()
			->child_fields()
			->update_meta( 'inbound_shortcode', $shortcode );
	}

	/**
		@brief		Parse the action block array.
		@since		2018-03-15 20:09:55
	**/
	public function parse_action_block( $bcd, $array )
	{
		foreach( $array as $key => $value )
			if ( is_array( $value ) )
				$array[ $key ] = $this->parse_action_block( $bcd, $value );
			else
			{
				switch( $key )
				{
					case 'email_id':
						$this->debug( 'Broadcasting email: %d', $value );
						$blog_id = get_current_blog_id();
						switch_to_blog( $bcd->parent_blog_id );
						$new_email_bcd = ThreeWP_Broadcast()->api()->broadcast_children( $value, [ $blog_id ] );
						restore_current_blog();
						$new_email_id = $new_email_bcd->new_post->ID;
						$this->debug( 'New e-mail ID for %d is %d', $value, $new_email_id );
						$array[ $key ] = $new_email_id;
					break;
					case 'target_rule_id':
						$this->debug( 'Setting target_rule_id to ourself.' );
						$array[ $key ] = $bcd->new_post( 'ID' );
					break;
				}
			}
		return $array;
	}

	/**
		@brief		Parse the trigger filters array.
		@since		2018-03-15 20:09:55
	**/
	public function parse_trigger_filters( $bcd, $array )
	{
		foreach( $array as $key => $value )
			if ( is_array( $value ) )
				$array[ $key ] = $this->parse_trigger_filters( $bcd, $value );
			else
			{
				switch( $key )
				{
					// Gravity Forms
					case 'trigger_filter_key':
						if ( $value != 'lead_data:form_id' )
							break;
						// Find the form ID.
						$form_id = $array[ 'trigger_filter_value' ];
						$this->debug( 'Gravity form ID is: %d', $form_id );

						if ( ! isset( $bcd->fake_shortcodes ) )
							break;
						$id = $key . $form_id;
						// Find the fake shortcode that matches.
						foreach( $bcd->fake_shortcodes as $fake_shortcode )
						{
							if ( $fake_shortcode->id !== $id )
								continue;

							// And now process to get the item broadcasted.
							$action = ThreeWP_Broadcast()->new_action( 'parse_content' );
							$action->broadcasting_data = $bcd;
							$action->content = $fake_shortcode->content;
							$action->id = $fake_shortcode->id;
							$action->execute();

							// $action->content will contain the new shortcode.
							// We need to extract the ID attribute from it.
							// Remove the []
							$action->content = trim( $action->content, '[]' );
							// And break it out into an array.
							$atts = shortcode_parse_atts( $action->content );

							$item_key = $bcd->fake_shortcode->item_key;
							$new_form_id = $atts[ 'id' ];
							$this->debug( 'Assigning new Gravity Form ID %d to %s', $new_form_id, $key );
							$array[ 'trigger_filter_value' ] = $new_form_id;
						}
					break;
				}
			}
		return $array;
	}

	/**
		@brief		Preparse the trigger filters array.
		@since		2018-03-15 20:09:55
	**/
	public function preparse_trigger_filters( $bcd, $array )
	{
		foreach( $array as $key => $value )
			if ( is_array( $value ) )
				$array[ $key ] = $this->preparse_trigger_filters( $bcd, $value );
			else
			{
				switch( $key )
				{
					// Gravity Forms
					case 'trigger_filter_key':
						if ( $value != 'lead_data:form_id' )
							break;
						// Find the form ID.
						$form_id = $array[ 'trigger_filter_value' ];
						$this->debug( 'Gravity form ID is: %d', $form_id );

						if ( ! isset( $bcd->fake_shortcodes ) )
							$bcd->fake_shortcodes = [];
						$fake_shortcode = (object)[];
						$fake_shortcode->content = sprintf( '[gravityform id="%d"]', $form_id );
						$fake_shortcode->id = $key . $form_id;
						$fake_shortcode->item_key = $item_key;
						$bcd->fake_shortcodes []= $fake_shortcode;

						$this->debug( 'Fake shortcode %s', $bcd->fake_shortcode );

						// Tell all Broadcast add-ons to process this shortcode.
						$action = ThreeWP_Broadcast()->new_action( 'preparse_content' );
						$action->broadcasting_data = $bcd;
						$action->content = $fake_shortcode->content;
						$action->id = $fake_shortcode->id;
						$action->execute();
					break;
				}
			}
		return $array;
	}
}
