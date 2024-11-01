<?php
/**
 * Calendar shortcode
 *
 * @package SeattleWebCo\WPZoom
 */

/**
 * Render the calendar.
 *
 * @param array  $atts Shortcode args.
 * @param string $content Do nothing.
 * @return string
 */
function wp_zoom_calendar_shortcode( $atts, $content = '' ) {
	$atts = shortcode_atts(
		array(
			'initialView'         => 'dayGridMonth',
			'headerToolbarLeft'   => 'today prev,next',
			'headerToolbarCenter' => 'title',
			'headerToolbarRight'  => 'timeGridWeek,dayGridMonth,listWeek',
		),
		$atts
	);

	return sprintf( '<div id="wp-zoom-calendar" data-args="%s"></div>', esc_attr( wp_json_encode( $atts ) ) );
}
add_shortcode( 'wp-zoom-calendar', 'wp_zoom_calendar_shortcode' );
add_shortcode( 'wp_zoom_calendar', 'wp_zoom_calendar_shortcode' );
