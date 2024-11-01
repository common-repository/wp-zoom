<?php
/**
 * Conditionally load integrations
 *
 * @package SeattleWebCo\WPZoom
 */

/**
 * Load integrations
 *
 * @return void
 */
function wp_zoom_load_integrations() {
	if ( function_exists( 'WC' ) ) {
		require_once WP_ZOOM_DIR . 'includes/integrations/woocommerce/wp-zoom-woocommerce-markup-products.php';
		require_once WP_ZOOM_DIR . 'includes/integrations/woocommerce/wp-zoom-woocommerce-product-meta-boxes.php';
	}
}
add_action( 'plugins_loaded', 'wp_zoom_load_integrations' );
