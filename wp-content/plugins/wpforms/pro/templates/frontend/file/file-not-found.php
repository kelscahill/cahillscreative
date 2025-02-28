<?php
/**
 * File Not Found template.
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
	<title><?php esc_html_e( 'File Not Found', 'wpforms' ); ?></title>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
	<main>
		<div class="wpforms-file-download-content">
			<img src="<?php echo esc_url( WPFORMS_PLUGIN_URL . 'assets/images/frontend/file-download/triangle-exclamation.svg' ); ?>" alt="<?php esc_attr_e( 'File Protected', 'wpforms' ); ?>">
			<h1><?php esc_html_e( 'File Not Found', 'wpforms' ); ?></h1>
			<p><?php esc_html_e( 'Sorry, the file you’re looking for could not be found.', 'wpforms' ); ?></p>
		</div>
	</main>
	<?php wp_footer(); ?>
</body>
</html>
