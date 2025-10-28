<?php

namespace threewp_broadcast\premium_pack\classes;

/**
	@brief		Make cache purges play nice with the queue.
	@details	To install, in the constructor:

				$this->init_cache_purge_trait();

				Create the function purge_cache() that does the purging.
	@since		2021-01-23 17:18:43
**/
trait cache_purge_trait
{
	/**
		@brief		Delay the purging of the cache?
		@details	This is to prevent the cache being cleared after each broadcast, instead of when queue processing is finished.
		@since		2021-01-22 20:55:48
	**/
	public $delay_cache_purge = false;

	/**
		@brief		Initialize the trait.
		@since		2021-01-23 17:19:27
	**/
	public function init_cache_purge_trait()
	{
		$this->add_filter( 'broadcast_queue_finished_processing' );
		$this->add_filter( 'broadcast_queue_started_processing' );
		$this->add_filter( 'threewp_broadcast_broadcasting_finished' );
	}

	/**
		@brief		Queue finished, clear the cache.
		@since		2021-01-22 20:57:54
	**/
	public function broadcast_queue_finished_processing( $action )
	{
		$this->purge_cache();
	}

	/**
		@brief		broadcast_queue_started_processing
		@since		2021-01-22 20:57:33
	**/
	public function broadcast_queue_started_processing( $action )
	{
		$this->debug( 'Processing via queue.' );
		$this->delay_cache_purge = true;
	}

	/**
		@brief		threewp_broadcast_broadcasting_finished
		@since		2021-01-22 20:09:20
	**/
	public function threewp_broadcast_broadcasting_finished( $action )
	{
		if ( $this->delay_cache_purge )
		{
			$this->debug( 'Waiting for the queue to finish processing.' );
			return;
		}
		$this->purge_cache();
	}

	/**
		@brief		Purge the cache.
		@since		2021-01-23 17:20:41
	**/
	public function purge_cache()
	{
		throw new \Exception( 'Please create your own custom purge_cache() function.' );
	}
}
