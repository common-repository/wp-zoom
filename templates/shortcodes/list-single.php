<?php
/**
 * Single view of object for List shortcode template
 *
 * @package SeattleWebCo\WPZoom
 */

?>

<div class="wp-zoom-list-item" data-id="<?php echo esc_attr( $args['id'] ); ?>">
	<div class="wp-zoom-list-item--date">
		<div class="wp-zoom-list-item--calendar">
			<div class="wp-zoom-list-item--calendar-month"><?php echo esc_html( wp_zoom_format_date_time( $args['start_time'], '', 'M' ) ); ?></div>
			<div class="wp-zoom-list-item--calendar-day"><?php echo esc_html( wp_zoom_format_date_time( $args['start_time'], '', 'j' ) ); ?></div>
			<div class="wp-zoom-list-item--calendar-weekday"><?php echo esc_html( wp_zoom_format_date_time( $args['start_time'], '', 'l' ) ); ?></div>
		</div>
	</div>
	<div class="wp-zoom-list-item--info">
		<h3 class="wp-zoom-list-item--info-topic">
		<?php
		if ( $args['product'] ) {
			 printf( '<a href="%s">%s</a>', esc_url( get_permalink( $args['product'] ) ), esc_html( $args['topic'] ) );
		} else {
			echo esc_html( $args['topic'] );
		}
		?>
		</h3>
		<div class="wp-zoom-list-item--info-date"><?php echo esc_html( wp_zoom_format_date_time( $args['start_time'] ) ); ?></div>

		<?php do_action( 'wp_zoom_list_after_info', $args ); ?>

		<div class="wp-zoom-list-item--info-actions">
			<?php do_action( 'wp_zoom_list_after_info_actions', $args ); ?>
		</div>
	</div>
</div>
