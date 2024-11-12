<?php

namespace threewp_broadcast\actions;

/**
	@brief		Populate the maintenance controller with various checks (tools).
	@since		2022-09-19 20:50:46
**/
class maintenance_populate_checks
	extends action
{
	/**
		@brief		IN/OUT: The data's checks container.
		@details	Convenience variable. You could have reached this via $controller->data->checks.
		@since		2022-09-19 20:51:38
	**/
	public $checks;
	/**
		@brief		IN: The maintenance controller object.
		@since		2022-09-19 20:51:07
	**/
	public $controller;

	/**
		@brief		IN: The controller's data object.
		@details	Convenience variable. You could have reached this via $controller->data.
		@since		2022-09-19 20:51:24
	**/
	public $data;
}
