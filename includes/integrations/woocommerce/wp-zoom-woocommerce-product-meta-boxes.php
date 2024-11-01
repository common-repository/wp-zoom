<?php
/**
 * Product metabox settings
 *
 * @package SeattleWebCo\WPZoom
 */

/**
 * Zoom product data tab for simple products
 *
 * @param array $tabs Tabs to modify.
 * @return array
 */
function wp_zoom_product_data_tab( $tabs ) {
	$tabs['wp-zoom'] = array(
		'label'    => __( 'Zoom', 'wp-zoom' ),
		'target'   => 'wp_zoom_product_data',
		'class'    => array( 'show_if_virtual' ),
		'priority' => 65,
	);

	return $tabs;
}
add_filter( 'woocommerce_product_data_tabs', 'wp_zoom_product_data_tab' );

/**
 * Webinar dropdown for simple products
 *
 * @return void
 */
function wp_zoom_product_data_tab_content() {
	global $post;

	$webinars     = (array) get_post_meta( $post->ID, '_wp_zoom_webinars', true );
	$purchase_url = get_post_meta( $post->ID, '_wp_zoom_purchase_url', true );
	?>

	<div id="wp_zoom_product_data" class="panel woocommerce_options_panel">
		<div class="options_group">
			<p class="form-field _wp_zoom_webinars_field">
				<label for="_wp_zoom_webinars"><?php esc_html_e( 'Webinars', 'wp-zoom' ); ?></label>
				<?php
				wp_zoom_render_field_select_webinars(
					array(
						'selected'      => $webinars,
						'multiple'      => true,
						'placeholder'   => esc_attr__( 'Select', 'wp-zoom' ),
					)
				);
				?>
			</p>
			<p class="form-field _wp_zoom_purchase_url">
				<label for="_wp_zoom_purchase_url">
					<?php esc_html_e( 'Set as purchase URL', 'wp-zoom' ); ?>
				</label>
				<input type="checkbox" <?php checked( $purchase_url, 'yes' ); ?> class="checkbox" name="_wp_zoom_purchase_url" id="_wp_zoom_purchase_url" value="yes" /> 
				<span class="description"><?php esc_html_e( 'Enable this to set as purchase URL for the webinars specified above.', 'wp-zoom' ); ?></span>
				<span class="wp-zoom-purchase-url-notice"></span>
			</p>
		</div>
	</div>

	<?php
}
add_action( 'woocommerce_product_data_panels', 'wp_zoom_product_data_tab_content' );

/**
 * Save product webinars
 *
 * @param integer $id Current product ID.
 * @param WP_Post $post Current product.
 * @return void
 */
function wp_zoom_product_data_save( $id, $post ) {
	// phpcs:ignore
	$webinars = array_map( 'intval', $_POST['_wp_zoom_webinars'] ?? array() );
	// phpcs:ignore
	$purchase_url = sanitize_text_field( $_POST['_wp_zoom_purchase_url'] ?? '' );

	if ( null !== $webinars ) {
		update_post_meta( $id, '_wp_zoom_webinars', array_filter( (array) $webinars ) );
		update_post_meta( $id, '_wp_zoom_purchase_url', $purchase_url );
	}
}
add_action( 'woocommerce_process_product_meta', 'wp_zoom_product_data_save', 10, 2 );

/**
 * Webinar dropdown field for variations
 *
 * @param integer              $loop Current variation index.
 * @param array                $variation_data Variation data.
 * @param WC_Product_Variation $variation Current variation.
 * @return void
 */
function wp_zoom_variable_product_fields( $loop, $variation_data, $variation ) {
	$selected = (array) get_post_meta( $variation->ID, '_wp_zoom_webinars', true );
	?>

	<div class="show_if_variation_virtual" style="display: none;">
		<p class="form-row form-row-full">
			<label><?php esc_html_e( 'Webinars', 'wp-zoom' ); ?></label>
			<?php
			wp_zoom_render_field_select_webinars(
				array(
					'selected'      => $selected,
					'name'          => '_wp_zoom_webinars_variations[' . $variation->ID . ']',
					'multiple'      => true,
					'placeholder'   => esc_attr__( 'Select', 'wp-zoom' ),
				)
			);
			?>
		</p>
	</div>

	<?php
}
add_action( 'woocommerce_product_after_variable_attributes', 'wp_zoom_variable_product_fields', 10, 3 );

/**
 * Save variation webinars
 *
 * @param integer $variation_id Variation ID to save onto.
 * @return void
 */
function wp_zoom_save_variation( $variation_id ) {
	// phpcs:ignore
	$webinars = array_map( 'intval', $_POST['_wp_zoom_webinars_variations'][ $variation_id ] ?? array() );

	if ( null !== $webinars ) {
		update_post_meta( $variation_id, '_wp_zoom_webinars', array_filter( (array) $webinars ) );
	}
}
add_action( 'woocommerce_save_product_variation', 'wp_zoom_save_variation' );
