<?php

namespace WPForms\Pro\Forms\Fields\Repeater;

use WPForms\Pro\Forms\Fields\Repeater\Helpers as RepeaterHelpers;
use WPForms\Emails\Notifications as EmailNotifications;

/**
 * Repeater field's Notifications class.
 *
 * @since 1.8.9
 */
class Notifications extends \WPForms\Pro\Forms\Fields\Base\Notifications {

	/**
	 * Current field notification type.
	 *
	 * @since 1.9.3
	 *
	 * @var string
	 */
	protected $field_type = 'repeater';

	/**
	 * Ignore the field if it is part of the repeater field.
	 *
	 * @since 1.9.0
	 *
	 * @param bool|mixed $ignore    Whether to ignore the field.
	 * @param array      $field     Field data.
	 * @param array      $form_data Form data.
	 *
	 * @return bool
	 */
	public function notifications_field_ignored( $ignore, array $field, array $form_data ): bool {

		$ignore = (bool) $ignore;

		$form_fields = $form_data['fields'] ?? [];

		$repeater_fields = RepeaterHelpers::get_repeater_fields( $form_fields );

		foreach ( $repeater_fields as $repeater_field ) {
			$fields = RepeaterHelpers::get_repeater_all_field_ids( $repeater_field );

			if ( in_array( $field['id'], $fields, false ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.FoundNonStrictFalse
				$ignore = true;
			}
		}

		return $ignore;
	}

	/**
	 * Get the repeater field HTML markup.
	 *
	 * @since 1.8.9
	 * @since 1.8.9.3 The $notifications parameter was added.
	 *
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
	public function get_repeater_field_html( $message, array $field, bool $show_empty_fields, array $other_fields, array $form_data, array $fields, EmailNotifications $notifications ): string {

		_deprecated_function( __METHOD__, '1.9.3 of the WPForms plugin', __CLASS__ . '::get_field_html()' );

		return $this->get_field_html( $message, $field, $show_empty_fields, $other_fields, $form_data, $fields, $notifications );
	}

	/**
	 * Get the repeater field plain text markup.
	 *
	 * @since 1.8.9
	 * @since 1.8.9.3 The $notifications parameter was added.
	 *
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
	public function get_repeater_field_plain( $message, array $field, bool $show_empty_fields, array $form_data, array $fields, EmailNotifications $notifications ): string {

		_deprecated_function( __METHOD__, '1.9.3 of the WPForms plugin', __CLASS__ . '::get_field_plain()' );

		return $this->get_field_plain( $message, $field, $show_empty_fields, $form_data, $fields, $notifications );
	}

	/**
	 * Get the plain message for the repeater field.
	 *
	 * @since 1.9.3
	 *
	 * @return string
	 */
	protected function get_plain_message(): string { // phpcs:ignore Generic.Metrics.CyclomaticComplexity, Generic.Metrics.NestingLevel.MaxExceeded

		$blocks = RepeaterHelpers::get_blocks( $this->field, $this->form_data );

		if ( empty( $blocks ) ) {
			return '';
		}

		$display = $this->field['display'] ?? 'rows';

		if ( $display === 'rows' && $this->is_compact() ) {
			$blocks = $this->prepare_compact_blocks( $blocks );
		}

		$repeater_message = '';

		foreach ( $blocks as $key => $rows ) {
			$block_number = $key >= 1 ? ' #' . ( $key + 1 ) : '';
			$divider      = $this->get_header( $this->field['label'] . $block_number );

			$fields_message = '';

			foreach ( $rows as $row_key => $row ) {
				// Add additional rows for compact display.
				if ( $this->is_compact() ) {
					$column_class = $row_key === 0 && $display === 'rows' ? 'wpforms-first-row' : '';
					$column_class = $row_key === count( $rows ) - 1 && $display === 'rows' ? 'wpforms-last-row' : $column_class;

					$fields_message .= sprintf( '<tr class="wpforms-layout-table-row wpforms-layout-table-row-display-%s %s"></tr>', sanitize_html_class( $display ), sanitize_html_class( $column_class ) );
				}

				foreach ( $row as $column ) {
					if ( ! isset( $column['field'] ) ) {
						continue;
					}

					$field_id = $column['field'];

					if ( isset( $this->form_data['fields'][ $field_id ] ) ) {
						$fields_message .= $this->get_subfield_message( $this->form_data['fields'][ $column['field'] ] );
					}
				}
			}

			if ( $fields_message ) {
				$repeater_message .= $divider . $fields_message;
			}
		}

		return $repeater_message;
	}

	/**
	 * Check if the current template is compact.
	 *
	 * @since 1.9.8
	 *
	 * @return bool
	 */
	private function is_compact(): bool {

		return $this->get_template() === 'compact';
	}

	/**
	 * Prepare blocks for compact display.
	 *
	 * @since 1.9.8
	 *
	 * @param array $blocks Blocks data.
	 *
	 * @return array
	 */
	private function prepare_compact_blocks( array $blocks ): array {

		$compact_blocks = [];

		foreach ( $blocks as $rows ) {
			foreach ( $rows as $row ) {
				foreach ( $row as $column_key => $column ) {
					$compact_blocks[ $column_key ][] = $column;
				}
			}
		}

		return [ $compact_blocks ];
	}

	/**
	 * Get field markup for an email.
	 *
	 * @since 1.9.3
	 *
	 * @return string
	 */
	protected function get_html_message(): string {

		$blocks = RepeaterHelpers::get_blocks( $this->field, $this->form_data );

		if ( empty( $blocks ) || $this->is_repeater_empty( $blocks ) ) {
			return '';
		}

		$display = $this->field['display'] ?? 'rows';

		$output = sprintf(
			'<tr class="wpforms-layout-table wpforms-layout-table-display-%s"><td>',
			sanitize_html_class( $display )
		);

		if ( $display === 'rows' ) {
			$output .= sprintf(
				'<table style="width: 100%%;">%1$s</table>',
				$this->get_header( $this->field['label'] )
			);
		}

		$output .= $this->get_block_message( $blocks, $display );

		$output .= '</td></tr>';

		return $output;
	}

	/**
	 * Check if repeater is empty.
	 *
	 * @since 1.9.3
	 *
	 * @param array $blocks Blocks data.
	 *
	 * @return bool
	 */
	private function is_repeater_empty( array $blocks ): bool { // phpcs:ignore Generic.Metrics.CyclomaticComplexity, Generic.Metrics.NestingLevel.MaxExceeded

		foreach ( $blocks as $rows ) {
			foreach ( $rows as $row ) {
				foreach ( $row as $column ) {
					$field_id    = $column['field'] ?? null;
					$field_value = $this->fields[ $field_id ]['value'] ?? null;

					if ( $field_value !== null && ! wpforms_is_empty_string( $field_value ) ) {
						return false;
					}
				}
			}
		}

		return true;
	}

	/**
	 * Render repeater blocks.
	 *
	 * @since 1.9.3
	 *
	 * @param array  $blocks  Blocks data.
	 * @param string $display Display type.
	 *
	 * @return string
	 */
	private function get_block_message( array $blocks, string $display ): string {

		ob_start();
		?>
		<?php foreach ( $blocks as $key => $rows ) : ?>
			<?php

			if ( $display === 'blocks' ) {
				$block_number = $key >= 1 ? ' #' . ( $key + 1 ) : '';

				echo '<table style="width: 100%;">' . $this->get_header( $this->field['label'] . $block_number ) . '</table>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>

			<table class="wpforms-layout-table-row <?php echo $key === 0 ? esc_attr( 'wpforms-first-row' ) : ''; ?>">
				<?php foreach ( $rows as $row ) : ?>
					<tr>
						<?php echo $this->get_columns_message( $row ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</tr>
				<?php endforeach; ?>
			</table>
		<?php endforeach; ?>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render repeater columns.
	 *
	 * @since 1.9.3
	 *
	 * @param array $row Row data.
	 *
	 * @return string
	 */
	private function get_columns_message( array $row ): string {

		ob_start();
		?>
		<?php foreach ( $row as $column ) : ?>
			<td style="width: <?php echo esc_attr( wpforms_get_column_width( $column ) ); ?>%">
				<?php if ( isset( $column['field'], $this->form_data['fields'][ $column['field'] ] ) ) : ?>
					<table class="wpforms-layout-table-cell">
						<?php echo $this->get_subfield_message( $this->form_data['fields'][ $column['field'] ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</table>
				<?php endif; ?>
			</td>
		<?php endforeach; ?>
		<?php

		return ob_get_clean();
	}
}
