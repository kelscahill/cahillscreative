<?php
/**
 * Email Corrupted Data Report body template.
 *
 * This template can be overridden by copying it to yourtheme/wpforms/emails/corrupted-data-report-body.php.
 *
 * @since 1.9.8
 *
 * @var array $reports Array of corrupted data reports grouped by form ID.
 * @var string $doc_page_url WPForms.com Documentation page URL.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<table class="corrupted-data-report-container" border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation">
	<tbody>
	<tr>
		<td class="corrupted-data-report-content" bgcolor="#ffffff">
			<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation">
				<tbody>
				<tr>
					<td><!-- Deliberately empty to support consistent sizing and layout across multiple email clients. --></td>
					<td class="corrupted-data-report-content-inner">
						<div class="corrupted-data-report-header" width="100%">
							<p>
								<?php esc_html_e( 'This is an automated technical alert regarding your WPForms submissions.', 'wpforms-lite' ); ?>
							</p>
							<p>
								<?php
								printf(
									/* translators: %1$s: Total corrupted submissions count. */
									esc_html__( 'For the 24-hour period ending at midnight, WPForms detected %1$s submissions flagged with an "Attempt to submit corrupted post data." error. This typically occurs when AJAX form submission is enabled, but the main wpforms.js script fails to load or execute correctly.', 'wpforms-lite' ),
									'<strong>' . esc_html( array_sum( array_column( $reports, 'count' ) ) ) . '</strong>',
									'<strong>' . esc_html( current_time( 'F j, Y' ) ) . '</strong>'
								);
								?>
							</p>
							<p>
								<?php esc_html_e( 'The affected forms are:', 'wpforms-lite' ); ?>
							</p>
						</div>

						<div class="corrupted-data-report-table" width="100%">
							<table border="1" cellpadding="10" cellspacing="0" width="100%">
								<thead>
									<tr>
										<th>
											<?php esc_html_e( 'Form Title', 'wpforms-lite' ); ?>
										</th>
										<th>
											<?php esc_html_e( 'Total Reports', 'wpforms-lite' ); ?>
										</th>
										<th>
											<?php esc_html_e( 'Page URLs', 'wpforms-lite' ); ?>
										</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $reports as $form_id => $report_data ) : ?>
										<tr>
											<td>
												<?php echo esc_html( $report_data['form_title'] ); ?>
											</td>
											<td class="corrupted-data-reports-count">
												<strong>
													<?php echo esc_html( $report_data['count'] ); ?>
												</strong>
											</td>
											<td class="corrupted-data-reports-urls">
												<?php if ( ! empty( $report_data['page_urls'] ) ) : ?>
													<?php foreach ( $report_data['page_urls'] as $url => $count ) : ?>
														<div class="corrupted-data-report-url-wrapper">
															<div class="corrupted-data-report-url">
																<?php echo esc_html( $url ); ?>
															</div>
															<div class="corrupted-data-report-url-count">
																<?php
																printf(
																	/* translators: %d: Number of occurrences. */
																	esc_html__( '%d occurrences', 'wpforms-lite' ),
																	absint( $count )
																);
																?>
															</div>
														</div>
													<?php endforeach; ?>

													<?php if ( $report_data['has_more_urls'] ) : ?>
														<div class="corrupted-data-report-urls-more">
															<?php esc_html_e( '...and more URLs', 'wpforms-lite' ); ?>
														</div>
													<?php endif; ?>
												<?php else : ?>
													<em class="corrupted-data-report-urls-empty">
														<?php esc_html_e( 'No URLs recorded', 'wpforms-lite' ); ?>
													</em>
												<?php endif; ?>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>

						<div class="corrupted-data-report-recommendations" width="100%">
							<h6>
								<?php esc_html_e( 'Recommended Debugging Steps', 'wpforms-lite' ); ?>
							</h6>
							<p>
								<?php esc_html_e( 'We recommend investigating the pages where these forms are located:', 'wpforms-lite' ); ?>
							</p>
							<ol>
								<li>
									<strong><?php esc_html_e( 'Check the Browser Console:', 'wpforms-lite' ); ?></strong>
									<?php esc_html_e( ' Open the page with the form and check the browser\'s Developer Console (F12) for any JavaScript errors, especially any related to wpforms.js.', 'wpforms-lite' ); ?>
								</li>
								<li>
									<strong><?php esc_html_e( 'Test Caching Plugins:', 'wpforms-lite' ); ?></strong>
									<?php esc_html_e( ' Temporarily disable JavaScript optimization in your caching plugin (e.g., Breeze, Speed Optimizer) or deactivate the plugin entirely to see if form submissions start working correctly.', 'wpforms-lite' ); ?>
								</li>
								<li>
									<strong><?php esc_html_e( 'Review Custom Code:', 'wpforms-lite' ); ?></strong>
									<?php esc_html_e( ' If you have custom scripts that interact with forms, ensure they are using the correct trigger(\'submit\') event on the jQuery object.', 'wpforms-lite' ); ?>
								</li>
								<li>
									<strong><?php esc_html_e( 'Confirm Entry Data:', 'wpforms-lite' ); ?></strong>
									<?php esc_html_e( ' You can view the corrupted submissions in your WordPress dashboard under WPForms → Tools → Logs.', 'wpforms-lite' ); ?>
								</li>
							</ol>
							<p>
								<?php
								printf(
									wp_kses( /* translators: %1$s: URL to the Logs page, %2$s - WPForms.com doc page URL. */
										__( '<a href="%1$s" target="_blank" rel="noopener noreferrer">View the logs</a> or <a href="%2$s" target="_blank" rel="noopener noreferrer">learn more about corrupted submissions</a>.', 'wpforms-lite' ),
										[
											'a' => [
												'href'   => [],
												'rel'    => [],
												'target' => [],
											],
										]
									),
									esc_url( admin_url( 'admin.php?page=wpforms-tools&view=logs' ) ),
									esc_url( $doc_page_url )
								);
								?>
							</p>
						</div>
					</td>
					<td><!-- Deliberately empty to support consistent sizing and layout across multiple email clients. --></td>
				</tr>
				</tbody>
			</table>
		</td>
	</tr>
	</tbody>
</table>
