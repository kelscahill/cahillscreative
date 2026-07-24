<?php
/**
 * Analytics stat card template.
 *
 * @since 2.0.0
 *
 * @var string $card_key Data-card identifier for JS targeting.
 * @var string $label    Card label (e.g., "Views", "Conversion Rate").
 * @var string $content  Card value HTML (pre-formatted, may include links).
 * @var string $tooltip  Help-tooltip text shown when hovering the info icon.
 *
 * @package WPForms
 */

defined( 'ABSPATH' ) || exit;

$tooltip = $tooltip ?? '';
?>

<div class="wpforms-analytics-stat-card" data-card="<?php echo esc_attr( $card_key ); ?>">
	<span class="wpforms-analytics-stat-card-label"><?php echo esc_html( $label ); ?></span>
	<div class="wpforms-analytics-stat-card-value"><?php echo wp_kses_post( $content ); ?></div>
	<?php if ( $tooltip !== '' ) : ?>
		<i class="wpforms-analytics-stat-info-icon wpforms-help-tooltip" title="<?php echo esc_attr( $tooltip ); ?>"></i>
	<?php endif; ?>
</div>
