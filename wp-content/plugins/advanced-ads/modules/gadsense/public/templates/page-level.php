<?php
/**
 * Output auto ads enabled code in head
 *
 * @var bool   $privacy_enabled Whether to wait for user consent.
 * @var bool   $npa_enabled     Whether to show non-personalized ads.
 * @var string $client_id       The Google AdSense client ID.
 * @var bool   $top_anchor      AdSense anchor ad on top of pages.
 * @var string $top_anchor_code The code for top anchor ads.
 * @var string $script_src      AdSense script url.
 */

if ( $privacy_enabled ) : ?>
	<script>
		(function () {
			var scriptDone = false;
			document.addEventListener('advanced_ads_privacy', function (event) {
				if (
					(event.detail.state !== 'accepted' && event.detail.state !== 'not_needed' && !advads.privacy.is_adsense_npa_enabled())
					|| scriptDone
				) {
					return;
				}
				// google adsense script can only be added once.
				scriptDone = true;

				var script = document.createElement('script'),
					first = document.getElementsByTagName('script')[0];

				script.async = true;
				script.src = '<?php echo esc_url( $script_src ); ?>';
				<?php
				if ( $top_anchor ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- relevant user input has already been escaped.
					echo $top_anchor_code;
				} else {
					printf( 'script.dataset.adClient = "%s";', esc_attr( $client_id ) );
				}
				?>

				first.parentNode.insertBefore(script, first);
			});
		})();
	</script>
	<?php
	return;
endif;

// Privacy not enabled.
// phpcs:disable WordPress.WP.EnqueuedResources
if ( $top_anchor ) {
	printf(
		'<script async src="%s"></script><script>%s</script>',
		esc_attr( $script_src ),
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the snippet has already been escaped.
		$top_anchor_code
	);
} else {
	printf(
		'<script data-ad-client="%s" async src="%s"></script>',
		esc_attr( $client_id ),
		esc_url( $script_src )
	);
}
// phpcs:enable
