<?php
/**
 * Product markup hooks
 *
 * @package SeattleWebCo\WPZoom
 */

use SeattleWebCo\WPZoom\Cache;

/**
 * Render webinars associated with a product and conditionally display occurrence select date and time
 *
 * @return void
 */
function wp_zoom_single_product_summary() {
	global $webinars;

	if ( ! empty( $webinars ) && is_array( $webinars ) ) {
		?>

		<?php foreach ( $webinars as $webinar ) { ?>

			<div class="wp-zoom-webinar-group">
				<div class="wp-zoom-webinar-field">
					<label>
						<?php echo esc_html( $webinar['topic'] ); ?>
					</label>
					<div class="wp-zoom-webinar-field-date">
						<?php
						wp_zoom_render_field_webinar(
							$webinar,
							array(
								'name'     => esc_attr( '_wp_zoom_webinars_occurrences[' . $webinar['id'] . ']' ),
								'selected' => array( intval( $_REQUEST['occurrence_id'] ?? 0 ) ),
							)
						);
						?>
					</div>
				</div>
			</div>

		<?php } ?>

		<?php
	}

}
add_action( 'woocommerce_before_add_to_cart_button', 'wp_zoom_single_product_summary', 11 );

/**
 * Add webinar information to AJAX get variation response
 *
 * @param array                $values Variation data.
 * @param WC_Product           $product Product the variation is associated with.
 * @param WC_Product_Variation $variation The variation.
 * @return array
 */
function wp_zoom_woocommerce_available_variation( $values, $product, $variation ) {
	$values['webinars'] = get_post_meta( $variation->get_id(), '_wp_zoom_webinars', true );

	return $values;
}
add_filter( 'woocommerce_available_variation', 'wp_zoom_woocommerce_available_variation', 10, 3 );

/**
 * Populate global variable containing webinar data for current product
 *
 * @param WP_Post  $post Current post.
 * @param WP_Query $wp_query Current query.
 * @return void
 */
function wp_zoom_prepare_webinar_data( $post, $wp_query ) {
	if ( $wp_query->is_main_query() && ! isset( $GLOBALS['webinars'] ) ) {
		$product = wc_get_product();

		$GLOBALS['webinars'] = wp_zoom_get_webinars( $post );

		if ( $product && is_a( $product, 'WC_Product_Grouped' ) ) {
			foreach ( $product->get_children() as $child ) {
				$GLOBALS['webinars'] = array_merge( $GLOBALS['webinars'], wp_zoom_get_webinars( $child ) );
			}

			$GLOBALS['webinars'] = array_unique( $GLOBALS['webinars'], SORT_REGULAR );
		}
	}
}
add_action( 'the_post', 'wp_zoom_prepare_webinar_data', 5, 2 );

/**
 * Add webinar registrant information to cart item data
 *
 * @param array   $cart_item_data Current cart item data.
 * @param integer $product_id Product ID added to cart.
 * @param integer $variation_id Variation ID added to cart.
 * @return array
 */
function wp_zoom_add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
	$webinars = wp_zoom_get_webinars( ! empty( $variation_id ) ? $variation_id : $product_id );

	if ( empty( $webinars ) ) {
		return $cart_item_data;
	}

	$cart_item_data['wp_zoom_webinars']             = $webinars;
	$cart_item_data['wp_zoom_webinars_occurrences'] = array();

	foreach ( $webinars as $webinar ) {
		// phpcs:ignore
		$occurrence_id = intval( $_POST['_wp_zoom_webinars_occurrences'][ $webinar['id'] ] ?? '' );

		if ( empty( $occurrence_id ) ) {
			$occurrence_id = intval( $_REQUEST['occurrence_id'] ?? 0 );
		}

		$cart_item_data['wp_zoom_webinars_occurrences'][ $webinar['id'] ] = wp_zoom_get_available_webinar_occurrence( $webinar, (string) $occurrence_id );
	}

	return $cart_item_data;
}
add_filter( 'woocommerce_add_cart_item_data', 'wp_zoom_add_cart_item_data', 10, 3 );

/**
 * Verify occurrence selected is available.
 *
 * @param boolean $passed Whether validation was passed.
 * @param integer $product_id Product added to cart.
 * @param integer $quantity Quantity of product added to cart.
 * @param integer $variation_id Optional variation added to cart.
 * @return boolean
 */
function wp_zoom_add_to_cart_validation( $passed, $product_id, $quantity, $variation_id = null ) {
	if ( empty( $variation_id ) ) {
		$webinars = wp_zoom_get_webinars( $product_id );

		foreach ( $webinars as $webinar ) {
			if ( empty( $webinar['occurrences'] ) ) {
				continue;
			}

            // phpcs:ignore
			$occurrence_id = (string) intval( $_POST['_wp_zoom_webinars_occurrences'][ $webinar['id'] ] ?? '' );

			if ( empty( $occurrence_id ) ) {
				$occurrence_id = intval( $_REQUEST['occurrence_id'] ?? 0 );
			}

			if ( empty( $occurrence_id ) ) {
				wc_add_notice( esc_html__( 'Please select a date and time for each webinar.', 'wp-zoom' ), 'error' );
				$passed = false;
			} else {
				$available = wp_zoom_occurrence_available( $webinar, $occurrence_id );

				if ( ! $available ) {
					wc_add_notice( esc_html__( 'Selected date and time is not available.', 'wp-zoom' ), 'error' );
					$passed = false;
				}
			}
		}
	}

	return $passed;
}
add_filter( 'woocommerce_add_to_cart_validation', 'wp_zoom_add_to_cart_validation', 10, 4 );

/**
 * Render webinar registrant information in cart table
 *
 * @param array $item_data Current item data.
 * @param array $cart_item_data All cart item data.
 * @return array
 */
function wp_zoom_get_item_data( $item_data, $cart_item_data ) {
	if ( ! empty( $cart_item_data['wp_zoom_webinars'] ) ) {
		foreach ( $cart_item_data['wp_zoom_webinars'] as $webinar ) {
			$start_time = $webinar['start_time'] ?? null;
			$occurrence = $cart_item_data['wp_zoom_webinars_occurrences'][ $webinar['id'] ] ?? array();

			// Check if webinar still exists; e.g. webinar could of been deleted and old data cached.
			if ( ! isset( $webinar['topic'] ) ) {
				continue;
			}

			$date_display = null;

			if ( $start_time ) {
				$date_display = wp_zoom_format_date_time( $start_time );
			} elseif ( ! empty( $occurrence ) ) {
				$date_display = wp_zoom_format_date_time( $occurrence['start_time'] );
			}

			$item_data[] = array(
				'key'       => esc_html( $webinar['topic'] ),
				'value'     => esc_html( $date_display ?? __( 'Webinar has no fixed time.', 'wp-zoom' ) ),
			);
		}
	}

	return $item_data;
}
add_filter( 'woocommerce_get_item_data', 'wp_zoom_get_item_data', 10, 2 );

/**
 * Check if cart items and their webinars are still valid
 *
 * @return boolean|WP_Error
 */
function wp_zoom_check_cart_items() {
	$return = true;

	foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
		if ( ! isset( $values['wp_zoom_webinars'] ) ) {
			continue;
		}

		$product = $values['data'];

		$webinars    = wp_zoom_get_webinars( $product->get_id() );
		$webinar_ids = wp_list_pluck( $webinars, 'id' );

		foreach ( $values['wp_zoom_webinars'] as $webinar ) {
            // phpcs:ignore
			if ( ! in_array( $webinar['id'], $webinar_ids ) ) {
				WC()->cart->set_quantity( $cart_item_key, 0 );
				wc_add_notice( __( 'A product in your cart contains a webinar which has been modified or no longer exists, therefore the product was removed from your cart.', 'wp-zoom' ), 'error' );

				$return = false;
			}
		}
	}

	return $return;
}
add_action( 'woocommerce_check_cart_items', 'wp_zoom_check_cart_items' );

/**
 * Add order line item meta
 *
 * @param WC_Order_Item_Product $item Current product while looping through order items.
 * @param string                $cart_item_key Cart product item key.
 * @param array                 $values Cart item data.
 * @param WC_Order              $order Current order.
 * @return void
 */
function wp_zoom_create_order_line_item( $item, $cart_item_key, $values, $order ) {
	if ( isset( $values['wp_zoom_webinars'] ) ) {
		foreach ( $values['wp_zoom_webinars'] as $webinar ) {
			$webinar_id = $webinar['id'] ?? '';
			$start_time = $webinar['start_time'] ?? null;
			$occurrence = $values['wp_zoom_webinars_occurrences'][ $webinar['id'] ] ?? array();

			// Check if webinar still exists; e.g. webinar could of been deleted and old data cached.
			if ( ! isset( $webinar['topic'] ) ) {
				continue;
			}

			$date_display = null;

			if ( $start_time ) {
				$date_display = wp_zoom_format_date_time( $start_time );
			} elseif ( ! empty( $occurrence ) ) {
				$date_display = wp_zoom_format_date_time( $occurrence['start_time'] );

				$item->add_meta_data( 'zoom_webinar_occurrence_id', $occurrence['occurrence_id'] );
			}

			$item->add_meta_data( 'zoom_webinar_id', $webinar_id );
			$item->add_meta_data( 'zoom_webinar_topic', $webinar['topic'] );
			$item->add_meta_data( 'zoom_webinar_datetime', $date_display ?? esc_html__( 'Webinar has no fixed time.', 'wp-zoom' ) );
		}
	}
}
add_action( 'woocommerce_checkout_create_order_line_item', 'wp_zoom_create_order_line_item', 10, 4 );



/**
 * Register the user to purchased webinars
 *
 * @param integer  $order_id ID or order paid for.
 * @param string   $from Status transitioning from.
 * @param string   $to Status transitioning to.
 * @param WC_Order $order The order that was paid for.
 * @return void
 */
function wp_zoom_payment_complete( $order_id, $from, $to, $order ) {
	global $wp_zoom;

	if ( ! in_array( $to, wc_get_is_paid_statuses(), true ) ) {
		return;
	}

	foreach ( $order->get_items() as $item ) {
		if ( $item->is_type( 'line_item' ) ) {
			$webinar_id    = null;
			$occurrence_id = null;
			$topic         = null;
			$datetime      = null;

			foreach ( $item->get_meta_data( 'wp_zoom_webinars' ) as $meta_data ) {
				if ( $meta_data->key === 'zoom_webinar_id' ) {
					$webinar_id = $meta_data->value;
				} elseif ( $meta_data->key === 'zoom_webinar_occurrence_id' ) {
					$occurrence_id = $meta_data->value;
				} elseif ( $meta_data->key === 'zoom_webinar_topic' ) {
					$topic = $meta_data->value;
				} elseif ( $meta_data->key === 'zoom_webinar_datetime' ) {
					$datetime = $meta_data->value;
				}
			}

			if ( ! $webinar_id ) {
				continue;
			}

			// Delete cache.
			Cache::delete( 'wp_zoom_webinar_' . $webinar_id );

			$custom_questions  = array();
			$_custom_questions = (array) get_post_meta( $order->get_id(), '_wp_zoom_webinars_custom_questions', true );

			foreach ( $_custom_questions as $key => $question ) {
				$custom_questions[] = array(
					'title'     => $key,
					'value'     => is_array( $question ) ? implode( ', ', $question ) : $question,
				);
			}

			$registrant_data = array(
				'email'            => $order->get_billing_email(),
				'first_name'       => $order->get_billing_first_name(),
				'last_name'        => $order->get_billing_last_name(),
				'address'          => $order->get_billing_address_1(),
				'city'             => $order->get_billing_city(),
				'country'          => $order->get_billing_country(),
				'zip'              => $order->get_billing_postcode(),
				'state'            => $order->get_billing_state(),
				'phone'            => $order->get_billing_phone(),
				'org'              => $order->get_billing_company(),
				'custom_questions' => $custom_questions,
			);

			$registration = $wp_zoom->add_webinar_registrant( $webinar_id, $registrant_data, $occurrence_id );

				// An error occurred.
			if ( isset( $registration['registrant_id'] ) ) {
				/* translators: 1: Webinar topic 2: Webinar date and time */
				$order->add_order_note( sprintf( esc_html__( 'User successfully registered for %1$s (%2$s)', 'wp-zoom' ), $topic, $datetime ) );

				add_post_meta(
					$order_id,
					'_zoom_webinar_registration',
					$registration
				);

			} else {
				/* translators: 1: Webinar topic */
				$order->add_order_note( sprintf( esc_html__( 'An error occurred while registering customer for %1$s', 'wp-zoom' ), $topic ) );

			}
		}
	}
}
add_action( 'woocommerce_order_status_changed', 'wp_zoom_payment_complete', 10, 4 );

/**
 * Display label of order item
 *
 * @param string             $display_key Display label.
 * @param WC_Order_Item_Meta $meta Order item meta.
 * @param WC_Order_Item      $item Order item object.
 * @return string
 */
function wp_zoom_order_item_display_label( $display_key, $meta, $item ) {
	switch ( $display_key ) {
		case 'zoom_webinar_id':
			$display_key = esc_html__( 'Zoom Webinar ID', 'wp-zoom' );
			break;
		case 'zoom_webinar_occurrence_id':
			$display_key = esc_html__( 'Zoom Webinar Occurrence ID', 'wp-zoom' );
			break;
		case 'zoom_webinar_topic':
			$display_key = esc_html__( 'Zoom Webinar Topic', 'wp-zoom' );
			break;
		case 'zoom_webinar_datetime':
			$display_key = esc_html__( 'Zoom Webinar Date & Time', 'wp-zoom' );
			break;
	}

	return $display_key;
}
add_filter( 'woocommerce_order_item_display_meta_key', 'wp_zoom_order_item_display_label', 10, 3 );

/**
 * Add custom checkout fields from webinar custom questions
 *
 * @param array $fields Existing fields to append to.
 * @return array
 */
function wp_zoom_woocommerce_checkout_fields( $fields ) {
	global $wp_zoom;

	$custom_fields = array();

	foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
		if ( ! isset( $values['wp_zoom_webinars'] ) ) {
			continue;
		}

		$product = $values['data'];

		$webinars    = wp_zoom_get_webinars( $product->get_id() );
		$webinar_ids = wp_list_pluck( $webinars, 'id' );

		foreach ( $webinar_ids as $webinar_id ) {
			$custom_fields[] = $wp_zoom->get_webinar_registrant_questions( $webinar_id );
		}
	}

	foreach ( $custom_fields as $webinar_fields ) {
		if ( ! empty( $webinar_fields['custom_questions'] ) ) {
			foreach ( $webinar_fields['custom_questions'] as $custom_question ) {
				if ( isset( $fields['order'][ 'wp_zoom_webinars_custom_questions[' . $custom_question['title'] . ']' ] ) ) {
					continue;
				}

				$field = array(
					'type'        => 'text',
					'label'       => esc_html( $custom_question['title'] ),
					'required'    => $custom_question['required'],
					'placeholder' => esc_html( $custom_question['title'] ),
				);

				switch ( $custom_question['type'] ) {
					case 'short':
						$field['type'] = 'text';
						break;
					case 'single_radio':
						$field['type']    = 'radio';
						$field['options'] = array_combine( $custom_question['answers'], $custom_question['answers'] );
						break;
					case 'single_dropdown':
						$field['type']    = 'select';
						$field['options'] = array_combine( $custom_question['answers'], $custom_question['answers'] );
						break;
					case 'multiple':
						$field['type']    = 'checkboxes';
						$field['options'] = array_combine( $custom_question['answers'], $custom_question['answers'] );
						break;
				}

				$fields['order'][ 'wp_zoom_webinars_custom_questions[' . $custom_question['title'] . ']' ] = apply_filters( 'wp_zoom_woocommerce_checkout_field', $field, $custom_question );
			}
		}
	}

	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'wp_zoom_woocommerce_checkout_fields' );

/**
 * Saves custom webinar registrant questions to order
 *
 * @param integer $order_id The order ID.
 * @return void
 */
function wp_zoom_woocommerce_checkout_update_order_meta( $order_id ) {
	// phpcs:ignore
	$custom_questions = array_map( 'wp_zoom_sanitize_recursive', $_POST['wp_zoom_webinars_custom_questions'] ?? array() );

	// phpcs:ignore
	if ( ! empty( $custom_questions ) ) {
		// phpcs:ignore
		update_post_meta( $order_id, '_wp_zoom_webinars_custom_questions', $custom_questions );
	}
}
add_action( 'woocommerce_checkout_update_order_meta', 'wp_zoom_woocommerce_checkout_update_order_meta' );

/**
 * Properly brings across custom webinar registrant questions
 *
 * @param array $data POSTed data.
 * @return array
 */
function wp_zoom_woocommerce_checkout_posted_data( $data ) {
	foreach ( WC()->checkout->get_checkout_fields() as $fieldset ) {
		foreach ( $fieldset as $key => $field ) {
			if ( substr( $key, 0, 33 ) === 'wp_zoom_webinars_custom_questions' ) {
				$match = preg_match( '/.*\[(.*)]$/', $key, $matches );

				// phpcs:ignore
				if ( isset( $_POST['wp_zoom_webinars_custom_questions'][ $matches[1] ] ) ) {
					// phpcs:ignore
					$data[ $key ] = wp_zoom_sanitize_recursive( $_POST['wp_zoom_webinars_custom_questions'][ $matches[1] ] );
				}
			}
		}
	}

	return $data;
}
add_filter( 'woocommerce_checkout_posted_data', 'wp_zoom_woocommerce_checkout_posted_data' );

/**
 * Render multiple checkboxes field for WooCommerce.
 *
 * @param string $field Field output.
 * @param string $key Field key.
 * @param array  $args Field arguments.
 * @param mixed  $value Current value.
 * @return string
 */
function wp_zoom_woocommerce_form_field_checkboxes( $field, $key, $args, $value ) {
	$label_id        = $args['id'] . '_' . current( array_keys( $args['options'] ) );
	$sort            = $args['priority'] ? $args['priority'] : '';
	$field_container = '<p class="form-row %1$s" id="%2$s" data-priority="' . esc_attr( $sort ) . '">%3$s</p>';

	if ( $args['required'] ) {
		$args['class'][] = 'validate-required';
		$required        = '&nbsp;<abbr class="required" title="' . esc_attr__( 'required', 'wp-zoom' ) . '">*</abbr>';
	} else {
		$required = '&nbsp;<span class="optional">(' . esc_html__( 'optional', 'wp-zoom' ) . ')</span>';
	}

	if ( ! empty( $args['options'] ) ) {
		foreach ( $args['options'] as $option_key => $option_text ) {
			$field .= '<input type="checkbox" class="input-checkbox ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" value="' . esc_attr( $option_key ) . '" name="' . esc_attr( $key ) . '[]" id="' . esc_attr( $args['id'] ) . '_' . esc_attr( $option_key ) . '"' . checked( $value, $option_key, false ) . ' />';
			$field .= '<label for="' . esc_attr( $args['id'] ) . '_' . esc_attr( $option_key ) . '" class="radio ' . implode( ' ', $args['label_class'] ) . '">' . esc_html( $option_text ) . '</label>';
		}
	}

	if ( ! empty( $field ) ) {
		$field_html = '';

		if ( $args['label'] ) {
			$field_html .= '<label for="' . esc_attr( $label_id ) . '" class="' . esc_attr( implode( ' ', $args['label_class'] ) ) . '">' . $args['label'] . $required . '</label>';
		}

		$field_html .= '<span class="woocommerce-input-wrapper">' . $field;

		if ( $args['description'] ) {
			$field_html .= '<span class="description" id="' . esc_attr( $args['id'] ) . '-description" aria-hidden="true">' . wp_kses_post( $args['description'] ) . '</span>';
		}

		$field_html .= '</span>';

		$container_class = esc_attr( implode( ' ', $args['class'] ) );
		$container_id    = esc_attr( $args['id'] ) . '_field';
		$field           = sprintf( $field_container, $container_class, $container_id, $field_html );
	}

	return $field;
}
add_filter( 'woocommerce_form_field_checkboxes', 'wp_zoom_woocommerce_form_field_checkboxes', 10, 4 );

/**
 * Returns webinar field when variation selected
 *
 * @return void
 */
function wp_zoom_ajax_woocommerce_get_variation_webinars() {
	global $wp_zoom;

	// phpcs:ignore
	$webinar = $wp_zoom->get_webinar( (string) intval( $_REQUEST['webinar'] ) );

	ob_start();
	?>

	<div class="wp-zoom-variation-webinar">
		<div class="label"><label><?php echo esc_html( $webinar['topic'] ); ?></label></div>
		<div class="value">
			<?php
				wp_zoom_render_field_webinar(
					$webinar,
					array(
						'name' => esc_attr( '_wp_zoom_webinars_occurrences[' . $webinar['id'] . ']' ),
					)
				);
			?>
		</div>
	</div>

	<?php
	$html = ob_get_clean();

	wp_send_json(
		array(
			'html'  => $html,
		)
	);
}
add_action( 'wp_ajax_wp_zoom_woocommerce_get_variation_webinars', 'wp_zoom_ajax_woocommerce_get_variation_webinars' );
add_action( 'wp_ajax_nopriv_wp_zoom_woocommerce_get_variation_webinars', 'wp_zoom_ajax_woocommerce_get_variation_webinars' );

/**
 * Remove AJAX add to cart capability for products containing Type 9 webinars
 *
 * @param array      $args Add to cart button args.
 * @param WC_Product $product The current product.
 * @return array
 */
function wp_zoom_woocommerce_loop_add_to_cart_args( $args, $product ) {
	if ( wp_zoom_has_type_9_webinar( $product->get_id() ) ) {
		$args['class'] = str_replace( ' ajax_add_to_cart', '', $args['class'] );
	}

	return $args;
}
add_filter( 'woocommerce_loop_add_to_cart_args', 'wp_zoom_woocommerce_loop_add_to_cart_args', 10, 2 );

/**
 * Change add to cart button text for products containing Type 9 webinars
 *
 * @param string     $text Add to cart text.
 * @param WC_Product $product The current product.
 * @return sring
 */
function wp_zoom_woocommerce_product_add_to_cart_text( $text, $product ) {
	if ( wp_zoom_has_type_9_webinar( $product->get_id() ) ) {
		$text = esc_html__( 'Select Date & Time', 'wp-zoom' );
	}

	return $text;
}
add_filter( 'woocommerce_product_add_to_cart_text', 'wp_zoom_woocommerce_product_add_to_cart_text', 10, 2 );

/**
 * Change add to cart URL for products containing Type 9 webinars
 *
 * @param string     $url Add to cart url.
 * @param WC_Product $product The current product.
 * @return sring
 */
function wp_zoom_woocommerce_product_add_to_cart_url( $url, $product ) {
	if ( wp_zoom_has_type_9_webinar( $product->get_id() ) ) {
		$url = $product->get_permalink();
	}

	return $url;
}
add_filter( 'woocommerce_product_add_to_cart_url', 'wp_zoom_woocommerce_product_add_to_cart_url', 10, 2 );

/**
 * Display product excerpt
 *
 * @param array $args Shortcode data and arguments.
 * @return void
 */
function wp_zoom_woocommerce_list_after_info( $args ) {
	if ( $args['product'] ) {
		$product = wc_get_product( $args['product'] );

		/* phpcs:ignore WordPress.Security.EscapeOutput */
		printf( '<p class="wp-zoom-list-item--info-excerpt">%s</p>', $product->get_short_description() );
	}
}
add_action( 'wp_zoom_list_after_info', 'wp_zoom_woocommerce_list_after_info' );

/**
 * Display buttons to register or view more details
 *
 * @param array $args Shortcode data and arguments.
 * @return void
 */
function wp_zoom_woocommerce_list_info_action( $args ) {
	if ( $args['product'] ) {
		printf(
			'<a href="%s" class="button wp-zoom-list-item--info-actions-button add_to_cart_button">%s</a>',
			esc_url(
				add_query_arg(
					array(
						'add-to-cart'   => $args['product'],
						'occurrence_id' => $args['occurrence_id'] ?? null,
					),
					get_permalink( $args['product'] )
				)
			),
			esc_html__( 'Add to cart', 'wp-zoom' )
		);
	}
}
add_action( 'wp_zoom_list_after_info_actions', 'wp_zoom_woocommerce_list_info_action' );

/**
 * Filter list occurrences by product category and populate data products
 *
 * @param array $data Array of occurrences.
 * @param array $atts Shortcode attributes.
 * @return array
 */
function wp_zoom_woocommerce_list_data( $data, $atts ) {
	$categories = $atts['category'] ? array_map( 'trim', explode( ',', $atts['category'] ) ) : array();

	foreach ( $data as $key => &$object ) {
		$object['product']  = null;
		$object['products'] = (array) wp_zoom_get_purchase_products( $object['id'] );

		if ( $categories ) {
			$keep = false;

			foreach ( $object['products'] as $product ) {
				$in_category = is_object_in_term( $product, 'product_cat', $categories );

				if ( $in_category ) {
					$object['product'] = $product;

					$keep = true;
					break;
				}
			}

			if ( ! $keep ) {
				unset( $data[ $key ] );
			}
		}

		if ( ! $object['product'] ) {
			$object['product'] = absint( current( $object['products'] ) );
		}
	}

	return $data;
}
add_filter( 'wp_zoom_list_shortcode_data', 'wp_zoom_woocommerce_list_data', 10, 2 );
