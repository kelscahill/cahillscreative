<?php

namespace WPForms\Pro\Forms\Fields\Layout;

use WPForms\Pro\Forms\Fields\Layout\Helpers as LayoutHelpers;
use WPForms\Emails\Notifications as EmailNotifications;

/**
 * Layout field's Notifications class.
 *
 * @since 1.9.0
 */
class Notifications extends \WPForms\Pro\Forms\Fields\Base\Notifications {

	/**
	 * Current field notification type.
	 *
	 * @since 1.9.3
	 *
	 * @var string
	 */
	protected $field_type = 'layout';

	/**
	 * Ignore the field if it is part of the layout field.
	 *
	 * @since 1.9.1
	 *
	 * @param bool|mixed $ignore    Whether to ignore the field.
	 * @param array      $field     Field data.
	 * @param array      $form_data Form data.
	 *
	 * @return bool
	 */
	public function notifications_field_ignored( $ignore, array $field, array $form_data ): bool {

		$ignore = (bool) $ignore;

		if ( ( empty( $field['id'] ) && ! is_numeric( $field['id'] ) ) || strpos( $field['id'], '_' ) ) {
			return $ignore;
		}

		if ( empty( $form_data['fields'] ) ) {
			return $ignore;
		}

		if ( wpforms_conditional_logic_fields()->field_is_hidden( $form_data, $field['id'] ) ) {
			return true;
		}

		$layout_fields = LayoutHelpers::get_layout_fields( $form_data['fields'] );

		foreach ( $layout_fields as $layout_field ) {
			$fields = LayoutHelpers::get_layout_all_field_ids( $layout_field );

			if ( in_array( (int) $field['id'], $fields, true ) ) {
				return true;
			}
		}

		return $ignore;
	}

	/**
	 * Get the layout field HTML markup.
	 *
	 * @since 1.9.0
	 * @deprecated 1.9.3
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
	public function get_layout_field_html( $message, array $field, bool $show_empty_fields, array $other_fields, array $form_data, array $fields, EmailNotifications $notifications ): string {

		_deprecated_function( __METHOD__, '1.9.3 of the WPForms plugin', __CLASS__ . '::get_field_html()' );

		return $this->get_field_html( $message, $field, $show_empty_fields, $other_fields, $form_data, $fields, $notifications );
	}

	/**
	 * Get the layout field plain text markup.
	 *
	 * @since 1.9.0
	 * @deprecated 1.9.3
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
	public function get_layout_field_plain( $message, array $field, bool $show_empty_fields, array $form_data, array $fields, EmailNotifications $notifications ): string {

		_deprecated_function( __METHOD__, '1.9.3 of the WPForms plugin', __CLASS__ . '::get_field_plain()' );

		return $this->get_field_plain( $message, $field, $show_empty_fields, $form_data, $fields, $notifications );
	}

	/**
	 * Get field markup for an email.
	 *
	 * @since 1.9.3
	 *
	 * @return string
	 */
	protected function get_html_message(): string {

		$layout_content = isset( $this->field['display'] ) && $this->field['display'] === 'rows'
			? $this->get_rows()
			: $this->get_columns();

		if ( wpforms_is_empty_string( $layout_content ) ) {
			return '';
		}

		if ( ! isset( $this->field['label'] ) ) {
			return $layout_content;
		}

		return $this->get_header( $this->field['label'] ) . $layout_content;
	}

	/**
	 * Get field markup for an email in compact mode.
	 *
	 * @since 1.9.3
	 *
	 * @return string
	 */
	protected function get_plain_message(): string {

		$layout_content = isset( $this->field['display'] ) && $this->field['display'] === 'rows'
			? $this->get_plain_rows()
			: $this->get_plain_columns();

		if ( wpforms_is_empty_string( $layout_content ) ) {
			return '';
		}

		if ( ! isset( $this->field['label'] ) ) {
			return $layout_content;
		}

		return $this->get_header( $this->field['label'] ) . $layout_content;
	}

	/**
	 * Get the layout field rows markup in plain text mode.
	 *
	 * @since 1.9.3
	 *
	 * @return string
	 */
	private function get_plain_rows(): string {

		$rows = isset( $this->field['columns'] ) && is_array( $this->field['columns'] ) ? LayoutHelpers::get_row_data( $this->field ) : [];

		if ( empty( $rows ) ) {
			return '';
		}

		$fields_message = '';

		foreach ( $rows as $row ) {
			foreach ( $row as $column ) {
				if ( isset( $column['field'], $this->form_data['fields'][ $column['field'] ] ) ) {
					$fields_message .= $this->get_subfield_message( $this->form_data['fields'][ $column['field'] ] );
				}
			}
		}

		return $fields_message;
	}

	/**
	 * Get the layout field columns markup in plain text mode.
	 *
	 * @since 1.9.3
	 *
	 * @return string
	 */
	private function get_plain_columns(): string {

		if ( ! isset( $this->field['columns'] ) ) {
			return '';
		}

		$fields_message = '';

		foreach ( $this->field['columns'] as $column ) {
			if ( ! isset( $column['fields'] ) ) {
				continue;
			}

			foreach ( $column['fields'] as $child_field_id ) {
				if ( isset( $this->form_data['fields'][ $child_field_id ] ) ) {
					$fields_message .= $this->get_subfield_message( $this->form_data['fields'][ $child_field_id ] );
				}
			}
		}

		return $fields_message;
	}

	/**
	 * Get the layout field rows markup.
	 *
	 * @since 1.9.3
	 *
	 * @return string
	 */
	private function get_rows(): string { // phpcs:ignore Generic.Metrics.NestingLevel.MaxExceeded

		$rows = isset( $this->field['columns'] ) && is_array( $this->field['columns'] ) ? LayoutHelpers::get_row_data( $this->field ) : [];

		if ( empty( $rows ) ) {
			return '';
		}

		$is_layout_empty = true;

		ob_start();

		?>
		<tr class="wpforms-layout-table wpforms-layout-table-display-columns">
			<td>
				<table class="wpforms-layout-table-row">
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<?php foreach ( $row as $column ) : ?>
								<td style="width: <?php echo esc_attr( wpforms_get_column_width( $column ) ); ?>%">
									<?php if ( isset( $column['field'], $this->form_data['fields'][ $column['field'] ] ) ) : ?>
										<table class="wpforms-layout-table-cell wpforms-width-<?php echo esc_attr( $column['width_preset'] ?? 50 ); ?>">
											<?php
												$field_message = $this->get_subfield_message( $this->form_data['fields'][ $column['field'] ] );

												if ( ! wpforms_is_empty_string( $field_message ) ) {
													$is_layout_empty = false;
												}

												echo $field_message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											?>
										</table>
									<?php endif; ?>
								</td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</table>
			</td>
		</tr>
		<?php

		if ( $is_layout_empty ) {
			ob_end_clean();

			return '';
		}

		return ob_get_clean();
	}

	/**
	 * Get the layout field columns markup.
	 *
	 * @since 1.9.3
	 *
	 * @return string
	 */
	private function get_columns(): string { // phpcs:ignore Generic.Metrics.NestingLevel.MaxExceeded

		if ( ! isset( $this->field['columns'] ) ) {
			return '';
		}

		$is_layout_empty = true;

		ob_start();

		?>
		<tr class="wpforms-layout-table wpforms-layout-table-display-columns">
			<td>
				<table class="wpforms-layout-table-row">
					<tr>
						<?php foreach ( $this->field['columns'] as $column ) : ?>
							<td style="width: <?php echo esc_attr( wpforms_get_column_width( $column ) ); ?>%">
								<?php if ( isset( $column['fields'] ) ) : ?>
									<table class="wpforms-layout-table-cell wpforms-width-<?php echo esc_attr( $column['width_preset'] ?? 50 ); ?>">
										<?php foreach ( $column['fields'] as $child_field_id ) : ?>
											<?php if ( isset( $this->form_data['fields'][ $child_field_id ] ) ) : ?>
												<?php
												$field_message = $this->get_subfield_message( $this->form_data['fields'][ $child_field_id ] );

												if ( ! wpforms_is_empty_string( $field_message ) ) {
													$is_layout_empty = false;
												}

												echo $field_message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
												?>
											<?php endif; ?>
										<?php endforeach; ?>
									</table>
								<?php endif; ?>
							</td>
						<?php endforeach; ?>
					</tr>
				</table>
			</td>
		</tr>
		<?php

		if ( $is_layout_empty ) {
			ob_end_clean();

			return '';
		}

		return ob_get_clean();
	}
}
