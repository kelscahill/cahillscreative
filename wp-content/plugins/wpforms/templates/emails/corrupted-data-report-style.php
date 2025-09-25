<?php
/**
 * Corrupted data report style template.
 *
 * This template can be overridden by copying it to yourtheme/wpforms/emails/corrupted-data-report-style.php.
 *
 * @since 1.9.8
 *
 * @var string $email_background_color Background color for the email.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require WPFORMS_PLUGIN_DIR . '/assets/css/emails/corrupted-data-report.min.css';
?>

body, .body {
	background-color: <?php echo esc_attr( $email_background_color ); ?>;
}
