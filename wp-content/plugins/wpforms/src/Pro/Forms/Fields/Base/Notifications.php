<?php

namespace WPForms\Pro\Forms\Fields\Base;

use RuntimeException;
use WPForms\Emails\Notifications as EmailNotifications;

/**
 * Base notifications class.
 *
 * @since 1.9.3
 */
abstract class Notifications {

	/**
	 * Email type (Plain or HTML).
	 *
	 * @since 1.9.3
	 *
	 * @var string
	 */
	private $email_type;

	/**
	 * Current field notification type.
	 *
	 * @since 1.9.3
	 *
	 * @var string
	 */
	protected $field_type;

	/**
	 * Field data.
	 *
	 * @since 1.9.3
	 *
	 * @var array
	 */
	protected $field;

	/**
	 * Fields data.
	 *
	 * @since 1.9.3
	 *
	 * @var array
	 */
	protected $fields;

	/**
	 * Form data.
	 *
	 * @since 1.9.3
	 *
	 * @var array
	 */
	protected $form_data;

	/**
	 * Email notification object.
	 *
	 * @since 1.9.3
	 *
	 * @var EmailNotifications
	 */
	private $notifications;

	/**
	 * Whether to display empty fields in the email.
	 *
	 * @since 1.9.3
	 *
	 * @var bool
	 */
	private $show_empty_fields;

	/**
	 * List of field types.
	 *
	 * @since 1.9.3
	 *
	 * @var array
	 */
	private $other_fields;

	/**
	 * Initialize.
	 *
	 * @since 1.9.3
	 *
	 * @throws RuntimeException When the `$field_type` property is not defined.
	 */
	public function init() {

		if ( empty( $this->field_type ) ) {
			throw new RuntimeException( 'Please define the `$field_type` property.' );
		}

		$this->hooks();
	}

	/**
	 * Hooks.
	 *
	 * @since 1.9.3
	 */
	private function hooks() {

		add_filter( 'wpforms_emails_notifications_field_message_plain', [ $this, 'get_field_plain' ], 10, 6 );
		add_filter( 'wpforms_emails_notifications_field_message_html', [ $this, 'get_field_html' ], 10, 7 );
		add_filter( 'wpforms_emails_notifications_field_ignored', [ $this, 'notifications_field_ignored' ], 10, 3 );
	}

	/**
	 * Check if the field is a layout field.
	 *
	 * @since 1.9.3
	 *
	 * @param array $field Field data.
	 *
	 * @return bool
	 */
	private function is_current_field( array $field ): bool {

		return isset( $field['type'] ) && $field['type'] === $this->field_type;
	}

	/**
	 * Get the layout field HTML markup.
	 *
	 * @since 1.9.3
	 *
	 * @param string|mixed       $message           Field message.
	 * @param array              $field             Field data.
	 * @param bool               $show_empty_fields Whether to display empty fields in the email.
	 * @param array              $other_fields      List of field types.
	 * @param array              $form_data         Form data.
	 * @param array              $fields            List of submitted fields.
	 * @param EmailNotifications $notifications     Notifications instance.
	 *
	 * @return string
	 */
	public function get_field_html( $message, array $field, bool $show_empty_fields, array $other_fields, array $form_data, array $fields, EmailNotifications $notifications ): string {

		$message = (string) $message;

		if ( ! $this->is_current_field( $field ) ) {
			return $message;
		}

		$this->email_type        = 'html';
		$this->field             = $field;
		$this->form_data         = $form_data;
		$this->notifications     = $notifications;
		$this->show_empty_fields = $show_empty_fields;
		$this->other_fields      = $other_fields;
		$this->fields            = $fields;

		if ( $notifications->get_current_template() === 'compact' ) {
			return $this->get_plain_message();
		}

		return $this->get_html_message();
	}

	/**
	 * Get the current email template.
	 *
	 * @since 1.9.8
	 *
	 * @return string
	 */
	protected function get_template(): string {

		return $this->notifications->get_current_template();
	}

	/**
	 * Get the layout field plain text markup.
	 *
	 * @since 1.9.3
	 *
	 * @param string|mixed       $message           Field message.
	 * @param array              $field             Field data.
	 * @param bool               $show_empty_fields Whether to display empty fields in the email.
	 * @param array              $form_data         Form data.
	 * @param array              $fields            List of submitted fields.
	 * @param EmailNotifications $notifications     Notifications instance.
	 *
	 * @return string
	 */
	public function get_field_plain( $message, array $field, bool $show_empty_fields, array $form_data, array $fields, EmailNotifications $notifications ): string {

		$message = (string) $message;

		if ( ! $this->is_current_field( $field ) ) {
			return $message;
		}

		$this->email_type        = 'plain';
		$this->field             = $field;
		$this->form_data         = $form_data;
		$this->notifications     = $notifications;
		$this->show_empty_fields = $show_empty_fields;
		$this->fields            = $fields;

		return $this->get_plain_message();
	}

	/**
	 * Get layout subfield markup for email.
	 *
	 * @since 1.9.3
	 *
	 * @param array $field Field data.
	 *
	 * @return string
	 */
	protected function get_subfield_message( array $field ): string {

		return $this->email_type === 'html' ?
			$this->notifications->get_field_html( $field, $this->show_empty_fields, $this->other_fields ) :
			$this->notifications->get_field_plain( $field, $this->show_empty_fields );
	}

	/**
	 * Get field header.
	 *
	 * @since 1.9.3
	 *
	 * @param string $label Field label.
	 *
	 * @return string
	 */
	protected function get_header( string $label ): string {

		if ( ! empty( $this->field['label_hide'] ) || ! isset( $this->field['label'] ) || wpforms_is_empty_string( $this->field['label'] ) ) {
			return '';
		}

		if ( $this->email_type === 'html' ) {
			return '<tr><td class="field-repeater-name field-name"><strong>' . esc_html( $label ) . '</strong></td><td class="field-value"></td></tr>';
		}

		// In plain email all HTML tags deleted automatically before sending, so we can skip escaping at all.
		return '--- ' . esc_html( $label ) . " ---\r\n\r\n";
	}

	/**
	 * Check if the field should be ignored in the email.
	 *
	 * @since 1.9.3
	 *
	 * @param bool  $ignore    Whether to ignore the field.
	 * @param array $field     Field data.
	 * @param array $form_data Form data.
	 *
	 * @return bool
	 */
	abstract public function notifications_field_ignored( $ignore, array $field, array $form_data ): bool;

	/**
	 * Get field markup for plain email template.
	 *
	 * @since 1.9.3
	 *
	 * @return string
	 */
	abstract protected function get_plain_message(): string;

	/**
	 * Get field markup for an HTML email template.
	 *
	 * @since 1.9.3
	 *
	 * @return string
	 */
	abstract protected function get_html_message(): string;
}
