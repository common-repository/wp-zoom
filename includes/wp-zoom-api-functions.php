<?php
/**
 * Functions regarding the API
 *
 * @package SeattleWebCo\WPZoom
 */

/**
 * Notify administrator when the Zoom API is disconnected
 *
 * @param Exception $e Exception object.
 * @return void
 */
function wp_zoom_api_disconnected( $e ) {
	ob_start();
	?>

	<?php esc_html_e( 'This is a notification to inform you that the WordPress for Zoom connection to the Zoom API has been disconnected.', 'wp-zoom' ); ?>
	<?php /* translators: URL to reconnect to Zoom API */ ?>
	<?php printf( esc_html__( 'Please visit %s to reconnect to the Zoom API.', 'wp-zoom' ), esc_url( admin_url( 'options-general.php?page=wp-zoom' ) ) ); ?>

	<?php
	$body = ob_get_clean();

	wp_mail( get_bloginfo( 'admin_email' ), esc_html__( 'Zoom API Disconnected', 'wp-zoom' ), $body );
}
add_action( 'wp_zoom_disconnected', 'wp_zoom_api_disconnected' );
