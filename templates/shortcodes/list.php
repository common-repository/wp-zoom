<?php
/**
 * List shortcode template
 *
 * @package SeattleWebCo\WPZoom
 */

?>

<div class="wp-zoom-list">
	<?php
	if ( ! empty( $args['data'] ) ) {
		foreach ( $args['data'] as $object ) {
			wp_zoom_load_template(
				'shortcodes/list-single.php',
				false,
				$object
			);
		}
	} else {
		printf( '<p class="wp-zoom-no-results">%s</div>', esc_html__( 'No upcoming results', 'wp-zoom' ) );
	}
	?>

	<?php
	if ( $args['total'] > $args['atts']['per_page'] ) {
		$big = 999999999;

		echo '<div class="wp-zoom-pagination">';

		// phpcs:ignore
		echo paginate_links(
			array(
				'base'   => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
				'format' => '?paged=%#%',
				'type'   => 'list',
				'total'  => round( $args['total'] / $args['atts']['per_page'] ),
			)
		);

		echo '</div>';
	}
	?>

</div>
