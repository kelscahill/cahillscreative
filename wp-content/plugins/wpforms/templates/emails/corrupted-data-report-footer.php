<?php
/**
 * Corrupted Data Report footer template.
 *
 * This template can be overridden by copying it to yourtheme/wpforms/emails/corrupted-data-report-footer.php.
 *
 * @since 1.9.8
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

									</td>
								</tr>
								<tr>
									<td align="<?php echo is_rtl() ? 'right' : 'left'; ?>" valign="top" class="footer">
										<?php
										printf(
											wp_kses( /* translators: %1$s - site URL. */
												__( 'This email was auto-generated and sent from %1$s.', 'wpforms-lite' ),
												[
													'a' => [
														'href' => [],
													],
												]
											),
											'<a href="' . esc_url( home_url() ) . '">' . esc_html( wp_specialchars_decode( get_bloginfo( 'name' ) ) ) . '</a>'
										);
										?>
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</div>
		</td>
		<td><!-- Deliberately empty to support consistent sizing and layout across multiple email clients. --></td>
	</tr>
</table>
</body>
</html>
