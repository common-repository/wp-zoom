<?php
/**
 * Plugin Name:     Zoom for WordPress
 * Description:     Simple Zoom integration with WordPress makes anything possible
 * Version:         1.5.4
 * Author:          Seattle Web Co.
 * Author URI:      https://seattlewebco.com
 * Text Domain:     wp-zoom
 * Domain Path:     /languages/
 * Contributors:    seattlewebco, dkjensen
 * Requires PHP:    7.0.0
 *
 * @package SeattleWebCo\WPZoom
 */

namespace SeattleWebCo\WPZoom;

define( 'WP_ZOOM_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_ZOOM_URL', plugin_dir_url( __FILE__ ) );
define( 'WP_ZOOM_VER', function_exists( 'get_plugin_data' ) ? get_plugin_data( __FILE__ )['Version'] : '1.0.0' );
define( 'WP_ZOOM_DB_VER', '1.0.0' );
define( 'WP_ZOOM_BASE', __FILE__ );

require_once WP_ZOOM_DIR . 'vendor/autoload.php';

require_once WP_ZOOM_DIR . 'includes/wp-zoom-enqueue-scripts.php';
require_once WP_ZOOM_DIR . 'includes/wp-zoom-api-functions.php';
require_once WP_ZOOM_DIR . 'includes/wp-zoom-markup-functions.php';
require_once WP_ZOOM_DIR . 'includes/wp-zoom-helper-functions.php';
require_once WP_ZOOM_DIR . 'includes/integrations/wp-zoom-integrations.php';
require_once WP_ZOOM_DIR . 'includes/shortcodes/wp-zoom-calendar-shortcode.php';
require_once WP_ZOOM_DIR . 'includes/shortcodes/wp-zoom-list-shortcode.php';
require_once WP_ZOOM_DIR . 'includes/wp-zoom-ajax.php';
require_once WP_ZOOM_DIR . 'includes/wp-zoom-settings.php';

if ( defined( 'WP_ZOOM_CLIENT_ID' ) && defined( 'WP_ZOOM_CLIENT_SECRET' ) ) {
	$wp_zoom_provider = new \SeattleWebCo\WPZoom\Provider\Zoom(
		array(
			'redirectUri'   => admin_url( 'options-general.php?page=wp-zoom' ),
			'clientId'      => constant( 'WP_ZOOM_CLIENT_ID' ),
			'clientSecret'  => constant( 'WP_ZOOM_CLIENT_SECRET' ),
		)
	);
} else {
	$wp_zoom_provider = new \SeattleWebCo\WPZoom\Provider\ZoomForWp(
		array(
			'redirectUri' => admin_url( 'options-general.php?page=wp-zoom' ),
		)
	);
}

$GLOBALS['wp_zoom'] = new Api( $wp_zoom_provider );

/**
 * Activation hook
 */
function wp_zoom_activation() {
	if ( version_compare( PHP_VERSION, '7.0.0', '<' ) ) {
		deactivate_plugins( basename( __FILE__ ) );
		wp_die(
			esc_html__( 'This plugin requires a minimum PHP version of 7.0.0', 'wp-zoom' ),
			esc_html__( 'Plugin activation error', 'wp-zoom' ),
			array(
				'response'  => 200,
				'back_link' => true,
			)
		);
	}

	delete_option( 'rewrite_rules' );

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, '\\SeattleWebCo\\WPZoom\\wp_zoom_activation' );
