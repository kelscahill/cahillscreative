<p>
	<a href="<?php echo esc_url( ADVADS_URL ) . 'manual/how-to-install-an-add-on/#utm_source=advanced-ads&utm_medium=link&utm_campaign=settings-licenses-install-add-ons'; ?>" target="_blank">
		<?php echo esc_attr__( 'How to install and activate an add-on.', 'advanced-ads' ); ?>
	</a>
<?php
printf(
	wp_kses(
	// translators: %s is a URL.
		__( 'See also <a href="%s" target="_blank">Issues and questions about licenses</a>.', 'advanced-ads' ),
		array(
			'a' => array(
				'href'   => array(),
				'target' => array(),
			),
		)
	),
	esc_url( ADVADS_URL . 'manual/purchase-licenses/#utm_source=advanced-ads&utm_medium=link&utm_campaign=settings-licenses' )
);
?>
</p>
<input type="hidden" id="advads-licenses-ajax-referrer" value="<?php echo esc_attr( wp_create_nonce( 'advads_ajax_license_nonce' ) ); ?>"/>
