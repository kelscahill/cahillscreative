<?php

namespace WPForms\Pro\Forms\Fields\FileUpload;

use WPForms\Forms\Fields\Traits\FileEntriesEditTrait;

/**
 * Editing field entries.
 *
 * @since 1.6.6
 */
class EntriesEdit extends \WPForms\Pro\Forms\Fields\Base\EntriesEdit {

	use FileEntriesEditTrait;

	/**
	 * Constructor.
	 *
	 * @since 1.6.6
	 */
	public function __construct() {

		parent::__construct( 'file-upload' );
	}
}
