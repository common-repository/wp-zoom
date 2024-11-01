<?php
/**
 * AJAX endpoints
 *
 * @package SeattleWebCo\WPZoom
 */

/**
 * Get all webinars
 *
 * @return void
 */
function wp_zoom_ajax_get_webinars() {
	global $wp_zoom;

    // phpcs:ignore
	if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'] ?? '' ) ) {
		wp_send_json_error( esc_html__( 'Invalid nonce', 'wp-zoom' ) );
	}

	$webinars = $wp_zoom->get_webinars();

	wp_send_json_success( $webinars );
}
add_action( 'wp_ajax_wp_zoom_get_webinars', 'wp_zoom_ajax_get_webinars' );

/**
 * Get all webinars for calendar display
 *
 * @return void
 */
function wp_zoom_ajax_get_calendar_webinars() {
	global $wp_zoom;

    // phpcs:ignore
	if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'] ?? '' ) ) {
		wp_send_json_error( esc_html__( 'Invalid nonce', 'wp-zoom' ) );
	}

	$response = array();
	$webinars = $wp_zoom->get_webinars();

	foreach ( $webinars['webinars'] as $webinar ) {
		$purchase_products = wp_zoom_get_purchase_products( $webinar['id'] );
		$purchase_url      = $purchase_products ? get_permalink( current( $purchase_products ) ) : '#';

		if ( $webinar['type'] === 5 ) {
			$response[] = array(
				'id'    => $webinar['id'],
				'start' => $webinar['start_time'],
				'end'   => wp_zoom_format_end_date_time( $webinar['start_time'], $webinar['duration'] ),
				'title' => $webinar['topic'],
				'url'   => $purchase_url,
			);
		} elseif ( $webinar['type'] === 9 ) {
			$webinar = $wp_zoom->get_webinar( $webinar['id'] );

			foreach ( $webinar['occurrences'] as $occurrence ) {
				if ( $occurrence['status'] !== 'available' ) {
					continue;
				}

				$response[] = array(
					'id'    => $occurrence['occurrence_id'],
					'start' => $occurrence['start_time'],
					'end'   => wp_zoom_format_end_date_time( $occurrence['start_time'], $occurrence['duration'] ),
					'title' => $webinar['topic'],
					'url'   => $purchase_url,
				);
			}
		}
	}

	wp_send_json_success( $response );
}
add_action( 'wp_ajax_wp_zoom_get_calendar_webinars', 'wp_zoom_ajax_get_calendar_webinars' );

/**
 * Get purchase products for given webinars
 *
 * @return void
 */
function wp_zoom_ajax_get_purchase_url_products() {
	// phpcs:ignore
	if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'] ?? '' ) ) {
		wp_send_json_error( esc_html__( 'Invalid nonce', 'wp-zoom' ) );
	}

	$response         = '';
	$grouped_products = array();
	$webinars         = array_map( 'intval', $_REQUEST['webinars'] ?? array() );
	$current_post     = intval( $_REQUEST['current_post'] ?? 0 );

	foreach ( $webinars as $webinar ) {
		$grouped_products = array_merge( $grouped_products, (array) wp_zoom_get_purchase_products( $webinar ) );
	}

	$grouped_products = array_filter( $grouped_products );

	if ( $current_post ) {
		// phpcs:ignore
		$current_post_index = array_search( $current_post, $grouped_products );

		if ( $current_post_index !== false ) {
			unset( $grouped_products[ $current_post_index ] );
		}
	}

	if ( $grouped_products ) {
		$response = esc_html__( 'The following product(s) contain conflicting purchase URLs to the webinars above', 'wp-zoom' ) . ":<br>\n";

		foreach ( $grouped_products as $product ) {
			$response .= sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( get_edit_post_link( $product ) ), esc_html( get_the_title( $product ) ) ) . "<br>\n";
		}
	}

	// phpcs:ignore
	echo $response;
	exit;
}
add_action( 'wp_ajax_wp_zoom_get_purchase_url_products', 'wp_zoom_ajax_get_purchase_url_products' );
