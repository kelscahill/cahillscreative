<?php
/**
 * Corrupted Data Report footer template (plain text).
 *
 * This template can be overridden by copying it to yourtheme/wpforms/emails/corrupted-data-report-footer-plain.php.
 *
 * @since 1.9.8
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo "\n---\n\n";
printf( /* translators: %s - link to the site. */
	esc_html__( 'This email was auto-generated and sent from %s.', 'wpforms-lite' ),
	esc_html( wp_specialchars_decode( get_bloginfo( 'name' ) ) )
);
