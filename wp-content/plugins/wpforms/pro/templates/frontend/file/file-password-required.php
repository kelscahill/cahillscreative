<?php
/**
 * File Password Required template.
 *
 * @since 1.9.4
 *
 * @var string $error Error message.
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
	<title><?php esc_html_e( 'Password Required', 'wpforms' ); ?></title>
	<?php wp_head(); ?>

	<script>
		document.addEventListener( 'DOMContentLoaded', function() {
			document.querySelector( '.wpforms-file-password-form').addEventListener( 'submit', function() {
				const error = document.querySelector( '.wpforms-file-password-error' );

				if ( error ) {
					error.remove();
				}
			} );
		} );
	</script>
</head>
<body <?php body_class(); ?>>
	<main>
		<div class="wpforms-file-download-content">
			<img src="<?php echo esc_url( WPFORMS_PLUGIN_URL . 'assets/images/frontend/file-download/lock-alt.svg' ); ?>" alt="<?php esc_attr_e( 'File Protected', 'wpforms' ); ?>">
			<h1><?php esc_html_e( 'Password Required', 'wpforms' ); ?></h1>
			<p><?php esc_html_e( 'This file is protected. Enter the password to access.', 'wpforms' ); ?></p>
			<form class="wpforms-file-password-form" method="post">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpforms-file-password' ) ); ?>">
				<input type="password" name="wpforms_file_password" placeholder="<?php esc_attr_e( 'Enter Password', 'wpforms' ); ?>" required>
				<button type="submit"><?php esc_html_e( 'Submit', 'wpforms' ); ?></button>
			</form>
			<?php if ( ! empty( $error ) ) : ?>
				<p class="wpforms-file-password-error"><?php echo esc_html( $error ); ?></p>
			<?php endif; ?>
		</div>
	</main>
	<?php wp_footer(); ?>
</body>
</html>
