<?php
/**
 * Analytics page first-visit onboarding banner.
 *
 * Three-feature dismissible card shown at the top of the analytics page until
 * the user closes it. Dismiss state lives in user meta wpforms_dismissed under
 * the 'edu-analytics-onboarding-banner' key (the wpforms-dismiss-container
 * pattern adds the 'edu-' prefix automatically — data-section omits it).
 *
 * @since 2.0.0
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="wpforms-analytics-onboarding wpforms-dismiss-container">
	<button type="button"
			class="wpforms-dismiss-button"
			title="<?php esc_attr_e( 'Dismiss this banner', 'wpforms' ); ?>"
			data-section="analytics-onboarding-banner"></button>

	<div class="wpforms-analytics-onboarding-heading">
		<h2><?php esc_html_e( 'Form Analytics', 'wpforms' ); ?></h2>
		<p><?php esc_html_e( 'Monitor form views, conversions, and field-level activity to optimize performance.', 'wpforms' ); ?></p>
	</div>

	<hr class="wpforms-analytics-onboarding-divider">

	<div class="wpforms-analytics-onboarding-features">

		<div class="wpforms-analytics-onboarding-feature">
			<div class="wpforms-analytics-onboarding-feature-head">
				<div class="wpforms-analytics-onboarding-icon wpforms-analytics-onboarding-icon-chart"></div>
				<h3><?php esc_html_e( "Understand Your Form's Performance", 'wpforms' ); ?></h3>
			</div>
			<div class="wpforms-analytics-onboarding-feature-body">
				<p><?php esc_html_e( "See how users interact with your forms at a glance. Track views, conversions, abandonments, and errors to understand what's working.", 'wpforms' ); ?></p>
			</div>
		</div>

		<div class="wpforms-analytics-onboarding-feature">
			<div class="wpforms-analytics-onboarding-feature-head">
				<div class="wpforms-analytics-onboarding-icon wpforms-analytics-onboarding-icon-goals"></div>
				<h3><?php esc_html_e( 'Set Goals and Track Your Progress Over Time', 'wpforms' ); ?></h3>
			</div>
			<div class="wpforms-analytics-onboarding-feature-body">
				<p><?php esc_html_e( "Set a conversion rate goal for each form and monitor whether you're hitting your target over time.", 'wpforms' ); ?></p>
			</div>
		</div>

		<div class="wpforms-analytics-onboarding-feature">
			<div class="wpforms-analytics-onboarding-feature-head">
				<div class="wpforms-analytics-onboarding-icon wpforms-analytics-onboarding-icon-ai"></div>
				<h3><?php esc_html_e( 'Get Actionable Insights Powered by WPForms AI', 'wpforms' ); ?></h3>
			</div>
			<div class="wpforms-analytics-onboarding-feature-body">
				<p><?php esc_html_e( "Ask questions about your form's performance and get actionable recommendations to improve conversions.", 'wpforms' ); ?></p>
			</div>
		</div>

	</div>
</div>
