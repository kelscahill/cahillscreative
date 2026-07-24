<?php
/**
 * Conversion Rate Goal popover.
 *
 * @since 2.0.0
 *
 * @var string $learn_more_url Filterable Learn More target URL.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="wpforms-analytics-goal-popover" aria-hidden="true" hidden>
	<span class="wpforms-analytics-goal-popover-title"><?php esc_html_e( 'Conversion Rate', 'wpforms' ); ?></span>

	<div class="wpforms-analytics-goal-popover-field">
		<input
			type="number"
			class="wpforms-analytics-goal-input"
			min="0.1"
			max="100"
			step="0.1"
			inputmode="decimal"
			aria-label="<?php esc_attr_e( 'Conversion rate goal', 'wpforms' ); ?>"
		/>
		<span class="wpforms-analytics-goal-input-suffix" aria-hidden="true">%</span>
	</div>

	<p class="wpforms-analytics-goal-popover-desc">
		<?php esc_html_e( 'Set a goal to quickly see if your form is hitting its target. Without a benchmark, it’s hard to know if your conversion rate is good or needs attention.', 'wpforms' ); ?>
		<a href="<?php echo esc_url( $learn_more_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Learn More', 'wpforms' ); ?></a>
	</p>

	<p class="wpforms-analytics-goal-popover-error" role="alert" hidden></p>

	<div class="wpforms-analytics-goal-popover-footer">
		<button type="button" class="wpforms-btn wpforms-btn-sm wpforms-btn-blue-outline wpforms-analytics-goal-save">
			<?php esc_html_e( 'Save Changes', 'wpforms' ); ?>
		</button>
	</div>
</div>
