<?php

namespace WeDevs\DokanPro\Modules\OrderMinMax;

defined( 'ABSPATH' ) || exit;

use WeDevs\DokanPro\Modules\OrderMinMax\DataSource\ProductMinMaxSettings;

/**
 * OrderMinMax Class.
 *
 * @since 3.5.0
 */
class Frontend {

	/**
	 * OrderMinMax Class Constructor.
	 *
	 * @since 3.5.0
	 */
	public function __construct() {
		// To show cart table min max error.
		add_filter( 'woocommerce_cart_item_quantity', array( $this, 'cart_item_quantity_min_max_quantity_check' ), 10, 3 );
		add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'add_to_cart_link' ), 10, 2 );

		// If we have errors, make sure those are shown on the checkout page
		add_action( 'woocommerce_cart_has_errors', array( $this, 'output_errors' ) );
	}

	/**
	 * Cart item quantity min max check.
	 *r
	 * @since 3.5.0
	 *
	 * @param int|string $product_quantity Product quantity, default is 1.
	 * @param string $cart_item_key   Cart item key.
	 * @param mixed  $cart_item      Cart item data.
	 *
	 * @return int|string
	 */
	public function cart_item_quantity_min_max_quantity_check( $product_quantity, string $cart_item_key, $cart_item ) {
		$product_id = $cart_item['product_id'];
		if ( $cart_item['variation_id'] ) {
			$product_id = $cart_item['variation_id'];
		}

		$product_quantity_settings = new ProductMinMaxSettings( $product_id );
		$cart_quantity             = $cart_item['quantity'];
		$error_message             = '';
		if (
			! empty( $product_quantity_settings->min_quantity() )
			&& $cart_quantity < $product_quantity_settings->min_quantity()
		) {
			remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20 );
			// Translators: 1. Required minimum product quantity
			$error_message = sprintf( esc_html__( 'Minimum %d required', 'dokan' ), $product_quantity_settings->min_quantity() );
		} elseif (
			! empty( $product_quantity_settings->max_quantity() )
			&& $cart_quantity > $product_quantity_settings->max_quantity()
		) {
			remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20 );
			// Translators: 1. Allowed maximum product quantity
			$error_message = sprintf( esc_html__( 'Maximum %d allowed', 'dokan' ), $product_quantity_settings->max_quantity() );
		}
		if ( ! empty( $error_message ) ) {
			$product_quantity = "{$product_quantity} <div class='required'>{$error_message}</div>";
		}
		return $product_quantity;
	}

	/**
	 * Add quantity property to add to cart button on shop loop for simple products.
	 *
	 * @since 3.5.0
	 *
	 * @param string      $html    Add to cart link.
	 * @param \WC_Product $product Product object.
	 *
	 * @return string
	 */
	public function add_to_cart_link( string $html, \WC_Product $product ): string {
		if ( 'variable' !== $product->get_type() ) {
			$html = $this->add_to_cart_minimum_quantity_restriction( $html, $product );
		}
		return $html;
	}

	/**
	 * Add quantity property to add to cart button on shop loop for simple products.
	 *
	 * @param string      $html    Add to cart link.
	 * @param \WC_Product $product Product object.
	 *
	 * @return array|string|string[]
	 */
	public function add_to_cart_minimum_quantity_restriction( string $html, \WC_Product $product ) {
		$product_quantity_settings = new ProductMinMaxSettings( $product );
		$minimum_quantity          = $product_quantity_settings->min_quantity();
		if ( ! empty( $minimum_quantity ) ) {
			$html = str_replace( '<a ', '<a data-quantity="' . $minimum_quantity . '" ', $html );
		}
		return $html;
	}

	/**
	 * Returns all queued notices, optionally filtered by a notice type.
	 *
	 * @since  3.5.0
	 *
	 * @param string $notice_type Optional. The singular name of the notice type - either error, success or notice.
	 *
	 * @return array|void
	 */
	public function wc_get_notices( string $notice_type = '' ) {
		if ( ! did_action( 'woocommerce_init' ) ) {
			wc_doing_it_wrong( __FUNCTION__, esc_html__( 'This function should not be called before woocommerce_init.', 'dokan' ), '2.3' );

			return;
		}

		$all_notices = WC()->session->get( 'wc_notices', array() );

		if ( empty( $notice_type ) ) {
			$notices = $all_notices;
		} elseif ( isset( $all_notices[ $notice_type ] ) ) {
			$notices = $all_notices[ $notice_type ];
		} else {
			$notices = array();
		}

		return $notices;
	}

	/**
	 * Output any plugin specific error messages
	 *
	 * We use this instead of wc_print_notices, so we
	 * can remove any error notices that aren't from us.
	 *
	 * @since  3.5.0
	 *
	 * @return void
	 */
	public function output_errors(): void {
		$notices = $this->wc_get_notices( 'error' );
		ob_start();

		wc_get_template(
			'notices/error.php',
			array(
				'notices' => array_filter( $notices ),
			)
		);

		echo wc_kses_notice( ob_get_clean() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
