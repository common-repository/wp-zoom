<?php
/**
 * List shortcode
 *
 * @package SeattleWebCo\WPZoom
 */

/**
 * Render the list of webinars / meetings
 *
 * @param array  $atts Shortcode args.
 * @param string $content Do nothing.
 * @return string
 */
function wp_zoom_list_shortcode( $atts, $content = '' ) {
	$atts = shortcode_atts(
		array(
			'type'      => 'webinars',
			'per_page'  => 20,
			'show_past' => 0,
			'category'  => null,
		),
		$atts
	);

	if ( $atts['type'] !== 'meetings' && $atts['type'] !== 'webinars' ) {
		return esc_html__( 'Data type must be either webinars or meetings.', 'wp-zoom' );
	}

	$page     = get_query_var( 'paged', 1 );
	$per_page = intval( $atts['per_page'] );
	$data     = wp_zoom_get_occurrences( $atts['type'], (bool) $atts['show_past'] );
	$data     = apply_filters( 'wp_zoom_list_shortcode_data', $data, $atts );
	$total    = count( $data );

	if ( $page < 2 ) {
		$page = 1;
	}

	if ( $per_page > 0 ) {
		$data = array_slice( $data, ( $page - 1 ) * $per_page, $per_page );
	}

	ob_start();

	wp_zoom_load_template(
		'shortcodes/list.php',
		false,
		array(
			'data'  => $data,
			'atts'  => $atts,
			'total' => $total,
		)
	);

	return ob_get_clean();
}
add_shortcode( 'wp-zoom-list', 'wp_zoom_list_shortcode' );
add_shortcode( 'wp_zoom_list', 'wp_zoom_list_shortcode' );
