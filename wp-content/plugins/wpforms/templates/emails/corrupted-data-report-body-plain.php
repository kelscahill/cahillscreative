<?php
/**
 * Email Corrupted Data Report body template (Plain Text).
 *
 * This template can be overridden by copying it to yourtheme/wpforms/emails/corrupted-data-report-body-plain.php.
 *
 * @since 1.9.8
 *
 * @var array $reports Array of corrupted data reports grouped by form ID.
 * @var string $doc_page_url WPForms.com Documentation page URL.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$add_line_break = static function ( int $lines = 1 ) {
	$line_breaks = [];

	for ( $i = 0; $i < $lines; $i++ ) {
		$line_breaks[] = "\n";
	}

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo implode( '', $line_breaks );
};

// phpcs:ignore WPForms.PHP.UseStatement.UnusedUseStatement
$print_section_separator = static function ( int $lines_after = 2 ) use ( $add_line_break ) {
	echo '=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=';
	$add_line_break( $lines_after );
};

esc_html_e( 'This is an automated technical alert regarding your WPForms submissions.', 'wpforms-lite' );
$add_line_break( 2 );

printf(
	/* translators: %1$s: Total corrupted submissions count. */
	esc_html__( 'For the 24-hour period ending at midnight, WPForms detected %1$s submissions flagged with an "Attempt to submit corrupted post data." error. This typically occurs when AJAX form submission is enabled, but the main wpforms.js script fails to load or execute correctly.', 'wpforms-lite' ),
	esc_html( array_sum( array_column( $reports, 'count' ) ) ),
	esc_html( current_time( 'F j, Y' ) )
);
$add_line_break( 2 );

esc_html_e( 'The affected forms are:', 'wpforms-lite' );
$add_line_break( 2 );

// Section separator.
$print_section_separator();

esc_html_e( 'CORRUPTED DATA REPORT', 'wpforms-lite' );

$row_format    = '%1$s: %2$s';
$count_repots  = count( $reports );
$current_index = 1;

foreach ( $reports as $form_id => $report_data ) :

	$add_line_break( 2 );
	printf(
		$row_format, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		esc_html__( 'Form Title', 'wpforms-lite' ),
		esc_html( $report_data['form_title'] )
	);
	$add_line_break();

	printf(
		$row_format, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		esc_html__( 'Total Reports', 'wpforms-lite' ),
		absint( $report_data['count'] )
	);
	$add_line_break();

	$prepared_urls = [];

	foreach ( $report_data['page_urls'] as $url => $count ) {
		$prepared_urls[] = sprintf(
			/* translators: %1$s: URL, %2$d: Count. */
			esc_html__( '%1$s (%2$d occurrences)', 'wpforms-lite' ),
			esc_html( $url ),
			absint( $count )
		);
	}

	printf(
		$row_format, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		esc_html__( 'Page URLs', 'wpforms-lite' ),
		implode( ', ', $prepared_urls ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
	$add_line_break( 2 );

	if ( $report_data['has_more_urls'] ) {
		esc_html_e( '...and more URLs', 'wpforms-lite' );
		$add_line_break( 2 );
	}

	if ( $current_index < $count_repots ) {
		echo '---------------------------------';
	}

	++$current_index;

endforeach;

// Section separator.
$print_section_separator();

esc_html_e( 'RECOMMENDED DEBUGGING STEPS', 'wpforms-lite' );
$add_line_break( 2 );


esc_html_e( 'We recommend investigating the pages where these forms are located:', 'wpforms-lite' );
$add_line_break( 2 );

esc_html_e( '1. Check the Browser Console:', 'wpforms-lite' );
$add_line_break();
esc_html_e( 'Open the page with the form and check the browser\'s Developer Console (F12) for any JavaScript errors, especially any related to wpforms.js.', 'wpforms-lite' );
$add_line_break( 2 );

esc_html_e( '2 Test Caching Plugins:', 'wpforms-lite' );
$add_line_break();
esc_html_e( 'Temporarily disable JavaScript optimization in your caching plugin (e.g., Breeze, Speed Optimizer) or deactivate the plugin entirely to see if form submissions start working correctly.', 'wpforms-lite' );
$add_line_break( 2 );

esc_html_e( '3. Review Custom Code:', 'wpforms-lite' );
$add_line_break();
esc_html_e( 'If you have custom scripts that interact with forms, ensure they are using the correct trigger(\'submit\') event on the jQuery object.', 'wpforms-lite' );
$add_line_break( 2 );

esc_html_e( '4. Confirm Entry Data:', 'wpforms-lite' );
$add_line_break();
esc_html_e( 'You can view the corrupted submissions in your WordPress dashboard under WPForms → Tools → Logs.', 'wpforms-lite' );
$add_line_break( 2 );

printf(
	/* translators: %s: - URL to log page. */
	esc_html__( 'View the logs: %s', 'wpforms-lite' ),
	esc_url( admin_url( 'admin.php?page=wpforms-tools&view=logs' ) )
);
$add_line_break( 2 );
printf(
	/* translators: %1$s - Documentation page URL. */
	esc_html__( 'Learn more about corrupted submissions: %s', 'wpforms-lite' ),
	esc_url( $doc_page_url )
);
