<?php

namespace WPForms\Emails\Templates;

/**
 * Email Corrupted Data Reports email template class.
 *
 * @since 1.9.8
 */
class CorruptedDataReport extends Summary {

	/**
	 * Template slug.
	 *
	 * @since 1.9.8
	 *
	 * @var string
	 */
	public const TEMPLATE_SLUG = 'corrupted-data-report';

	/**
	 * Retrieves the slug of the parent template.
	 *
	 * @since 1.9.8
	 *
	 * @return string Returns the slug of the parent template.
	 */
	public function get_parent_slug() {

		return parent::TEMPLATE_SLUG;
	}
}
