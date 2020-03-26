<?php

namespace threewp_broadcast\premium_pack\aqua_page_builder;

/**
	@brief			Adds support for the <a href="https://wordpress.org/plugins/aqua-page-builder/">Aqua Page Builder plugin</a>.
	@plugin_group	3rd party compatability
	@since			2017-01-04 23:20:12
**/
class Aqua_Page_Builder
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
	}

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2017-01-04 23:20:32
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		$new_post_id = $bcd->new_post( 'ID' );

		$old_key = sprintf( 'aq_template_%s', $bcd->post->ID );

		$transient_meta = $bcd->custom_fields()->get_single( $old_key );
		if ( ! $transient_meta )
			return;

		$child_fields = $bcd->custom_fields()->child_fields();
		$child_transient = maybe_unserialize( $transient_meta );

		foreach( $child_transient as $index => $block )
		{
			if ( ! isset( $block[ 'template_id' ] ) )
				continue;

			// Convert the template ID in the transient.
			$child_transient[ $index ][ 'template_id' ] = $new_post_id;

			// And now the separate meta for this blog.
			$old_meta = $bcd->custom_fields()->get_single( $index );
			$old_meta = maybe_unserialize( $old_meta );
			if ( isset( $old_meta[ 'template_id' ] ) )
			{
				$old_meta[ 'template_id' ] = $new_post_id;
				$child_fields->update_meta( $index, $old_meta );
			}
		}
		$child_fields->delete_meta( $old_key );
		$new_key = sprintf( 'aq_template_%s', $new_post_id );
		$child_fields->update_meta( $new_key, $child_transient );
	}
}
