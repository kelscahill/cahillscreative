<?php

namespace threewp_broadcast\premium_pack\classes;

use threewp_broadcast\actions;

/**
	@brief		Convenience methods to parse and preparse content.
	@since		2018-05-09 09:47:09
**/
trait parse_and_preparse_content_trait
{
	/**
		@brief		Preparse content.
		@since		2018-05-09 08:59:54
	**/
	public function preparse_content( $options )
	{
		if ( is_array( $options[ 'content' ] ) )
		{
			$my_options = $options;
			foreach( $options[ 'content' ] as $key => $value )
			{
				$my_options[ 'content' ] = $value;
				$my_options[ 'id' ] = $options[ 'id' ] . $key;
				$options[ 'content' ][ $key ] = $this->preparse_content( $my_options );
			}
			return $options[ 'content' ];
		}

		if ( is_object( $options[ 'content' ] ) )
		{
			$my_options = $options;
			foreach( (array) $options[ 'content' ] as $key => $value )
			{
				$my_options[ 'content' ] = $value;
				$my_options[ 'id' ] = $options[ 'id' ] . $key;
				$options[ 'content' ]->$key = $this->preparse_content( $my_options );
			}
			return $options[ 'content' ];
		}

		$preparse_content = new actions\preparse_content();
		$preparse_content->broadcasting_data = $options[ 'broadcasting_data' ];
		$preparse_content->content = $options[ 'content' ];
		$preparse_content->id = $options[ 'id' ];
		$preparse_content->execute();

		return $options[ 'content' ];
	}

	/**
		@brief		Parse the content.
		@since		2018-05-09 09:22:03
	**/
	public function parse_content( $options )
	{
		if ( is_array( $options[ 'content' ] ) )
		{
			$my_options = $options;
			foreach( $options[ 'content' ] as $key => $value )
			{
				$my_options[ 'content' ] = $value;
				$my_options[ 'id' ] = $options[ 'id' ] . $key;
				$options[ 'content' ][ $key ] = $this->parse_content( $my_options );
			}
			return $options[ 'content' ];
		}

		if ( is_object( $options[ 'content' ] ) )
		{
			$my_options = $options;
			foreach( (array) $options[ 'content' ] as $key => $value )
			{
				$my_options[ 'content' ] = $value;
				$my_options[ 'id' ] = $options[ 'id' ] . $key;
				$options[ 'content' ]->$key = $this->parse_content( $my_options );
			}
			return $options[ 'content' ];
		}

		$parse_content = new actions\parse_content();
		$parse_content->broadcasting_data = $options[ 'broadcasting_data' ];
		$parse_content->content = $options[ 'content' ];
		$parse_content->id = $options[ 'id' ];
		$parse_content->execute();

		return $parse_content->content;
	}
}
