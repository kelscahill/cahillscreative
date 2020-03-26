<?php

namespace threewp_broadcast\premium_pack\classes\gutenberg_items;

/**
	@brief		Base class for handling things that appear in Gutenberg blocks.
	@since		2019-06-18 21:53:17
**/
abstract class Gutenberg_Items
	extends \threewp_broadcast\premium_pack\classes\generic_items\Generic_Items
{
	/**
		@brief		Get the data for the type of generic handler.
		@since		2019-06-19 22:02:02
	**/
	public function get_generic_data()
	{
		return (object) [
			'delimiters' => false,
			'singular' => 'block',
			'plural' => 'blocks',
			'Singular' => 'Block',
			'Plural' => 'Blocks',
			'option_name' => 'blocks',
		];
	}

	/**
		@brief		Replace the IDs in the values.
		@since		2019-07-19 21:29:06
	**/
	public function parse_values( $bcd, $item, $find, $array )
	{
		foreach( $array as $key => $value )
		{
			if ( is_array( $value ) )
				$array[ $key ] = $this->parse_values( $bcd, $item, $find, $value );

			foreach( $find->value as $attribute => $old_id )
			{
				if ( $key !== $attribute )
					continue;
				$new_id = $this->replace_id( $bcd, $find, $old_id );
				if ( $new_id )
					$array[ $attribute ] = $new_id;
			}

			foreach( $find->values as $attribute => $data )
			{
				if ( $key !== $attribute )
					continue;
				$old_ids = $value;
				$new_ids = [];
				foreach( $old_ids as $old_id )
				{
					$new_id = $this->replace_id( $bcd, $find, $old_id );
					if ( $new_id )
						$new_ids[] = $new_id;
				}

				$array[ $attribute ] = $new_ids;
			}
		}
		return $array;
	}

	/**
		@brief		Preparse the values arrays.
		@since		2019-07-19 21:11:20
	**/
	public function preparse_values( $find, $item, $array )
	{
		foreach( $array as $key => $value )
		{
			if ( is_array( $value ) )
				$this->preparse_values( $find, $item, $value );

			foreach( $item->value as $attribute => $ignore )
			{
				if ( ! isset( $array[ $attribute ] ) )
					continue;
				if ( $find->value->has( $attribute ) )
					continue;
				$this->debug( 'Found single attribute %s: %s', $attribute, $array[ $attribute ] );
				$find->value->set( $attribute, $array[ $attribute ] );
			}

			foreach( $item->values as $attribute => $ignore )
			{
				if ( ! isset( $array[ $attribute ] ) )
					continue;
				if ( $find->values->has( $attribute ) )
					continue;
				$this->debug( 'Found multiple attribute %s: %s', $attribute, $array[ $attribute ] );
				// Save the IDs in the find.
				$find->values->set( $attribute, $array[ $attribute ] );
			}
		}
	}

	/**
		@brief		Return the Shared_Finds object.
		@since		2020-01-31 08:15:43
	**/
	public function shared_finds()
	{
		$bc = ThreeWP_Broadcast();
		if ( isset( $bc->gutenberg_items_shared_finds ) )
			return $bc->gutenberg_items_shared_finds;
		$bc->gutenberg_items_shared_finds = new Shared_Finds();
		return $bc->gutenberg_items_shared_finds;
	}

	/**
		@brief		Parse the content, replacing item.
		@since		2019-06-19 22:02:02
	**/
	public function threewp_broadcast_parse_content( $action )
	{
		$bcd = $action->broadcasting_data;		// Convenience.

		$slug = $this->get_class_slug() . '_preparse';

		if ( ! isset( $bcd->$slug ) )
			return;

		$generic_data = $this->get_generic_data();

		$finds = $bcd->$slug->get( $action->id, [] );

		foreach( $finds as $find )
		{
			$shared_find_collection = $this->shared_finds()->get_find_collection( $find );
			$shared_find = $shared_find_collection->get( 'find' );
			$unmodified_find = $shared_find;
			$item = $shared_find->original;
			//$item = $find->original;

			$replace_id_action = new actions\replace_id();
			$replace_id_action->called_class = get_called_class();
			$replace_id_action->broadcasting_data = $bcd;
			$replace_id_action->find = $find;
			$replace_id_action->item = $item;
			$replace_id_action->execute();

			if ( $replace_id_action->is_finished() )
			{
				$item = $replace_id_action->item;
				$this->debug( 'Replacing %s <em><pre><%s</pre></em> with <em><pre><%s</pre></em>', $generic_data->singular, htmlspecialchars( $find->original ), htmlspecialchars( $item ) );
				$action->content = str_replace( render_block( $find->original ), render_block( $item ), $action->content );
				continue;
			}

			$item[ 'attrs' ] = $this->parse_values( $bcd, $item, $find, $item[ 'attrs' ] );

			// Update the shared find.
			$shared_find->original[ 'attrs' ] = $item[ 'attrs' ];
			$shared_find_collection->decrease_counter();

			if ( ! $shared_find_collection->can_be_replaced() )
			{
				$this->debug( 'Cannot update this shared find yet.' );
				continue;
			}

			// Do not modify if there is nothing to modify, since the GB replacement function does not produce a 1-1 regeneration.
			if ( json_encode( $unmodified_find ) == json_encode( $item ) )
				continue;

			$this->debug( 'Replacing %s <em><pre>%s</pre></em><br>with<br><em><pre>%s</pre></em>',
				$generic_data->singular,
				htmlspecialchars( $find->original[ 'original' ] ),
				htmlspecialchars( ThreeWP_Broadcast()->gutenberg()->render_block( $item ) )
			);

			// Using the original text is the safest way to guarantee that the block text is replaced.
			$action->content = ThreeWP_Broadcast()->gutenberg()->replace_text_with_block( $find->original[ 'original' ], $item, $action->content );
		}
	}

	/**
		@brief		Preparse some content.
		@since		2019-06-19 22:02:02
	**/
	public function threewp_broadcast_preparse_content( $action )
	{
		$bcd = $action->broadcasting_data;		// Convenience.
		$content = $action->content;			// Also very convenient.

		$slug = $this->get_class_slug() . '_preparse';

		// In case another preparse hasn't asked for this already.
		if ( ! isset( $bcd->$slug ) )
			$bcd->$slug = ThreeWP_Broadcast()->collection();

		$items = $this->items();

		$finds = [];

		//$blocks = parse_blocks( $content );

		$blocks = ThreeWP_Broadcast()->gutenberg()->parse_blocks( $content );

		if ( count( $blocks ) < 1 )
			return;

		$this->debug( 'Blocks: %s', $blocks );

		foreach( $items as $item )
		{
			$this->debug( 'Looking for item: %s', $item->get_slug() );

			foreach( $blocks as $block )
			{
				if ( $block[ 'blockName' ] != $item->get_slug() )
					continue;

				// We've found a block we care about!
				$find = ThreeWP_Broadcast()->collection();
				$find->value = ThreeWP_Broadcast()->collection();
				$find->values = ThreeWP_Broadcast()->collection();
				$find->original = $block;

				$this->debug( 'Found item %s as <pre>%s</pre>', $item->get_slug(), htmlspecialchars( ThreeWP_Broadcast()->gutenberg()->render_block( $find->original ) ) );

				$this->preparse_values( $find, $item, $block[ 'attrs' ] );

				$parse_find_action = new actions\parse_find();
				$parse_find_action->called_class = get_called_class();
				$parse_find_action->broadcasting_data = $bcd;
				$parse_find_action->find = $find;
				$parse_find_action->execute();

				if ( ! $parse_find_action->is_finished() )
					$this->parse_find( $bcd, $find );

				// No values to save? Foggedaboutit.
				$value_count = $find->value->count() + $find->values->count();
				if ( $value_count < 1 )
					continue;

				$find_collection = $this->shared_finds()->add_find( $find );
				$this->debug( 'Adding this find to the array x %s: %s', $find_collection->get_counter(), $find );

				$finds []= $find;
			}

		}

		if ( count( $finds ) < 1 )
			return;

		$bcd->$slug->set( $action->id, $finds );
	}
}
