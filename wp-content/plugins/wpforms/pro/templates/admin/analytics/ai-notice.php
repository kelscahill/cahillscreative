<?php
/**
 * WPForms AI product-education notice on the Analytics page.
 *
 * Shown at the top of the analytics content once data has been collecting for a
 * week, nudging the user toward WPForms AI. The CTA opens the WPForms AI chat
 * modal. Dismiss state lives in user meta wpforms_dismissed under the
 * 'edu-analytics-ai-notice' key (the wpforms-dismiss-container pattern adds the
 * 'edu-' prefix automatically, so data-section omits it). Dismissal is manual
 * only and the notice never returns once closed.
 *
 * @since 2.0.0
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="wpforms-analytics-ai-notice wpforms-dismiss-container">
	<div class="wpforms-analytics-ai-notice-text">
		<h3 class="wpforms-analytics-ai-notice-title"><?php esc_html_e( 'Need Help Making Sense of These Numbers?', 'wpforms' ); ?></h3>
		<p class="wpforms-analytics-ai-notice-desc"><?php esc_html_e( 'Use WPForms AI to analyze your stats and suggest ways to improve form completion.', 'wpforms' ); ?></p>
	</div>

	<button type="button" class="wpforms-btn wpforms-btn-sm wpforms-analytics-ai-notice-cta">
		<?php esc_html_e( 'Try WPForms AI', 'wpforms' ); ?>
	</button>

	<button type="button"
			class="wpforms-dismiss-button"
			title="<?php esc_attr_e( 'Dismiss this notice', 'wpforms' ); ?>"
			data-section="analytics-ai-notice"></button>
</div>
