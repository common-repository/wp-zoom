<?php
/**
 * General settings template
 *
 * @package SeattleWebCo\WPZoom
 */

global $wp_zoom;

$me = $wp_zoom->get_me();

if ( empty( $me['id'] ) ) {
	?>

	<p>
		<a class="button zoom-button" href="<?php echo esc_url( $wp_zoom->provider->getAuthorizationUrl() ); ?>">
			<?php esc_html_e( 'Authorize with', 'wp-zoom' ); ?> 
			<span class="zoom-icon"></span>
		</a>
	</p>

	<?php
} else {
	?>

	<p>
		<?php
			/* translators: 1: Account user name */
			printf( esc_html__( 'Connected to account: %s', 'wp-zoom' ), esc_html( $me['first_name'] . ' ' . $me['last_name'] ) );
		?>
	</p>
	<p>
		<a class="disconnect-wp-zoom button" href="<?php echo esc_url( \wp_nonce_url( admin_url( 'admin-post.php?action=wp_zoom_revoke' ), 'wp-zoom-revoke' ) ); ?>">
			<?php esc_html_e( 'Revoke Zoom Authorization', 'wp-zoom' ); ?>
		</a> 
		<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'purge_wp_zoom_cache' => 1 ) ), 'wp-zoom-purge-cache' ) ); ?>" class="button">
			<?php esc_html_e( 'Purge Zoom API Cache', 'wp-zoom' ); ?>
		</a>
	</p>

	<?php
}
