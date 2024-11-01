<?php
/**
 * Registration settings template
 *
 * @package SeattleWebCo\WPZoom
 */

?>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate="novalidate">
	<table class="form-table" role="presentation">
		<tbody>
			<?php
			foreach ( wp_zoom_get_settings_fields( 'registration' ) as $field => $args ) {
				wp_zoom_render_settings_field( $field, $args );
			}
			?>
		</tbody>
	</table>

	<input type="hidden" name="action" value="wp_zoom_settings" />
	<input type="hidden" name="tab" value="registration" />
	<?php wp_nonce_field( 'wp-zoom-settings' ); ?>
	<?php submit_button(); ?>
</form>
