<?php

namespace threewp_broadcast\premium_pack\classes\generic_items;

/**
	@brief		Replace attachment IDs.
	@since		2019-06-20 22:06:13
**/
trait Replace_Attachments_Trait
{
	/**
		@brief		Add the attachment(s).
		@since		2016-07-14 13:41:10
	**/
	public function parse_find( $bcd, $find )
	{
		foreach( $find->value as $attribute => $id )
		{
			$id = intval( $id );
			if ( $bcd->try_add_attachment( $id ) )
				$this->debug( 'Adding single attachment %s', $id );
		}

		foreach( $find->values as $attribute => $array )
				foreach( $array as $ids )
				{
					foreach( $ids as $id )
						if ( $bcd->try_add_attachment( intval( $id ) ) )
							$this->debug( 'Adding one of several attachments %s', $id );
				}
	}

	/**
		@brief		Replace the old ID with a new one.
		@since		2016-07-14 14:21:21
	**/
	public function replace_id( $broadcasting_data, $find, $old_id )
	{
		$new_id = $broadcasting_data->copied_attachments()->get( $old_id );
		if ( $new_id < 1 )
			$new_id = 0;
		$this->debug( 'Replacing attachment %s with %s', $old_id, $new_id );
		return $new_id;
	}
}
