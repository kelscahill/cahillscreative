<?php
/**
 * File Protected template.
 *
 * @since 1.9.4
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php esc_html_e( 'Access Denied', 'wpforms' ); ?></title>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
	<main>
		<div class="wpforms-file-download-content">
			<img src="<?php echo esc_url( WPFORMS_PLUGIN_URL . 'assets/images/frontend/file-download/lock-alt.svg' ); ?>" alt="<?php esc_attr_e( 'File Protected', 'wpforms' ); ?>">
			<h1><?php esc_html_e( 'File Protected', 'wpforms' ); ?></h1>
			<p><?php esc_html_e( 'Sorry, you don’t have access to this file.', 'wpforms' ); ?></p>
		</div>
	</main>
	<?php wp_footer(); ?>
</body>
</html>
