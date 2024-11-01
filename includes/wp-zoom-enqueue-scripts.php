<?php
/**
 * Enqueue assets
 *
 * @package SeattleWebCo\WPZoom
 */

/**
 * Frontend assets
 *
 * @return void
 */
function wp_zoom_enqueue_scripts() {
	wp_enqueue_style( 'wp-zoom-frontend', WP_ZOOM_URL . 'assets/css/frontend.css', array(), WP_ZOOM_VER );
	wp_register_script( 'wp-zoom-frontend', WP_ZOOM_URL . 'assets/js/frontend.js', array( 'jquery', 'wc-add-to-cart-variation' ), WP_ZOOM_VER, true );

	wp_localize_script(
		'wp-zoom-frontend',
		'wp_zoom',
		array(
			'ajax_url'      => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce(),
		)
	);

	wp_enqueue_script( 'wp-zoom-frontend' );

	wp_register_script( 'wp-zoom-calendar', WP_ZOOM_URL . 'assets/js/calendar.js', array( 'jquery' ), WP_ZOOM_VER, true );

	wp_localize_script(
		'wp-zoom-calendar',
		'wp_zoom',
		array(
			'ajax_url'      => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce(),
		)
	);

	wp_enqueue_script( 'wp-zoom-calendar' );
}
add_action( 'wp_enqueue_scripts', 'wp_zoom_enqueue_scripts' );

/**
 * Backend assets
 *
 * @return void
 */
function wp_zoom_admin_enqueue_scripts() {
	wp_enqueue_style( 'wp-zoom', WP_ZOOM_URL . 'assets/css/admin.css', array(), WP_ZOOM_VER );
	wp_enqueue_script( 'wp-zoom', WP_ZOOM_URL . 'assets/js/admin.js', array( 'jquery', 'selectWoo' ), WP_ZOOM_VER, true );

	wp_localize_script(
		'wp-zoom',
		'wp_zoom',
		array(
			'ajax_url'      => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce(),
		)
	);

	wp_enqueue_script( 'wp-zoom' );
}
add_action( 'admin_enqueue_scripts', 'wp_zoom_admin_enqueue_scripts' );
