<?php

namespace threewp_broadcast\premium_pack\siteorigin_page_builder;

/**
	@brief				Adds support for <a href="https://wordpress.org/plugins/siteorigin-panels/">SiteOrigin's Page Builder</a>.
	@plugin_group		3rd party compatability
	@since				2015-12-14 10:32:56
**/
class SiteOrigin_Page_Builder
	extends \threewp_broadcast\premium_pack\base
{
	use \threewp_broadcast\premium_pack\classes\parse_and_preparse_content_trait;

	/**
		@brief		Which widget keys contain text that needs to be parsed?
		@since		2018-11-07 12:07:32
	**/
	public static $widget_texts = [ 'content', 'text' ];

	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
	}

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2015-12-14 10:34:10
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;
		if ( ! isset( $bcd->siteorigin_page_builder ) )
			return;

		$spb = $bcd->siteorigin_page_builder;
		$panels_data = $spb->get( 'panels_data' );

		foreach( $panels_data[ 'widgets' ] as $index => $widget )
		{
			if ( isset( $widget[ 'image' ] ) )
			{
				$id = $widget[ 'image' ];
				$new_id = $bcd->copied_attachments()->get( $id );
				$this->debug( 'Modifying image %s with %s', $id, $new_id );
				$panels_data[ 'widgets' ][ $index ][ 'image' ] = $new_id;
			}

			if ( isset( $widget[ 'features' ] ) )
			{
				foreach( $widget[ 'features' ] as $feature_index => $feature )
				{
					if ( $feature[ 'icon_image' ] > 0 )
					{
						$id = $feature[ 'icon_image' ];
						$this->debug( 'Found feature icon image %s', $id );
						$new_id = $bcd->copied_attachments()->get( $id );
						$this->debug( 'Modifying feture icon image %s with %s', $id, $new_id );
						$panels_data[ 'widgets' ][ $index ][ 'features' ][ $feature_index ][ 'icon_image' ] = $new_id;
					}
				}
			}

			foreach( static::$widget_texts as $type )
			{
				if ( isset( $widget[ $type ] ) )
				{
					$panels_data[ 'widgets' ][ $index ][ $type ] = $this->parse_content( [
						'broadcasting_data' => $bcd,
						'content' => $widget[ $type ],
						'id' => 'siteorigin_widget_' . $type . $index,
					] );
				}
			}
		}

		$this->debug( 'Updating panels_data: %s', $panels_data );
		$bcd->custom_fields()->child_fields()->update_meta( 'panels_data', $panels_data );
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2015-12-14 10:33:59
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! $bcd->custom_fields()->has( 'panels_data' ) )
			return $this->debug( 'No SiteOrigin Panel Builder data found.' );

		$panels_data = $bcd->custom_fields()->get_single( 'panels_data' );
		$panels_data = maybe_unserialize( $panels_data );

		$this->debug( 'Panels data is: %s', $panels_data );

		foreach( $panels_data[ 'widgets' ] as $index => $widget )
		{
			if ( isset( $widget[ 'image' ] ) )
			{
				$id = $widget[ 'image' ];
				if ( $bcd->try_add_attachment( $id ) )
					$this->debug( 'Found image %s', $id );
			}

			if ( isset( $widget[ 'features' ] ) )
			{
				foreach( $widget[ 'features' ] as $feature_index => $feature )
				{
					if ( $feature[ 'icon_image' ] > 0 )
					{
						$id = $feature[ 'icon_image' ];
						$this->debug( 'Found feature icon image %s', $id );
						$bcd->try_add_attachment( $id );
					}
				}
			}

			foreach( static::$widget_texts as $type )
			{
				if ( isset( $widget[ $type ] ) )
				{
					$panels_data[ 'widgets' ][ $index ][ $type ] = $this->preparse_content( [
						'broadcasting_data' => $bcd,
						'content' => $widget[ $type ],
						'id' => 'siteorigin_widget_' . $type . $index,
					] );
				}
			}
		}

		$spb = ThreeWP_Broadcast()->collection();
		$bcd->siteorigin_page_builder = $spb;
		$spb->set( 'panels_data', $panels_data );
	}
}
