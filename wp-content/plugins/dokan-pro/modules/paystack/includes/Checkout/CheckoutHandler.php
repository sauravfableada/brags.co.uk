<?php

namespace WeDevs\DokanPro\Modules\Paystack\Checkout;

use WC_Order;
use WeDevs\DokanPro\Modules\Paystack\Support\Helper;
use WP_Error;

class CheckoutHandler {
    public function __construct() {
        // Checkout and Cart Validation
        add_action( 'woocommerce_after_checkout_validation', [ $this, 'validate_checkout' ], 15, 2 );
        add_action( 'woocommerce_checkout_validate_order_before_payment', [ $this, 'validate_checkout' ], 15, 2 );
        add_filter( 'woocommerce_available_payment_gateways', [ $this, 'checkout_filter_gateway' ], 1 );

        add_action( 'wc_ajax_dokan_paystack_order_pay', [ $this, 'handle_order_pay' ] );
    }

    /**
     * Handle Paystack order pay via AJAX.
     *
     * @since 5.0.1
     */
    public function handle_order_pay() {

        // verify nonce
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'dokan_paystack_order_pay' ) ) {
            wp_send_json(
                [
					'result' => 'failure',
					'messages' => __( 'Invalid nonce', 'dokan' ),
				]
            );
            return;
        }

        $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_send_json(
                [
					'result' => 'failure',
					'messages' => __( 'Invalid order', 'dokan' ),
				]
            );
            return;
        }

        // Initialize payment gateways to load Paystack class
        $gateways = WC()->payment_gateways()->payment_gateways();
        $gateway_id = Helper::get_gateway_id();

        if ( ! isset( $gateways[ $gateway_id ] ) ) {
            wp_send_json(
                [
					'result' => 'failure',
					'messages' => __( 'Invalid gateway', 'dokan' ),
				]
            );
            return;
        }

        $response = $gateways[ $gateway_id ]->process_payment( $order_id );
        if ( is_wp_error( $response ) ) {
            wp_send_json(
                [
					'result' => 'failure',
					'messages' => $response->get_error_message(),
				]
            );
            return;
        }

        wp_send_json( $response );
    }

    /**
     * Validation after checkout.
     *
     * @since 4.1.1
     *
     * @param array|WC_Order $data
     * @param WP_Error $errors
     *
     * @return void
     */
    public function validate_checkout( $data, $errors ) {
        $payment_method = '';
        if ( $data instanceof WC_Order ) {
            $payment_method = $data->get_payment_method();
        } elseif ( is_object( WC()->cart ) ) {
            $payment_method = $data['payment_method'];
        }

        // Don't validate if Paystack is not selected
        if ( ! is_object( WC()->cart ) || Helper::get_gateway_id() !== $payment_method ) {
            return;
        }

        $available_vendors = [];
        foreach ( WC()->cart->get_cart() as $item ) {
            $product_id = $item['data']->get_id();
            $product = wc_get_product( $product_id );
            if ( Helper::should_skip_product( $product_id ) ) {
                continue;
            }
            $vendor_id = dokan_get_vendor_by_product( $product, true );
            $available_vendors[ $vendor_id ][] = $product;
        }

        foreach ( array_keys( $available_vendors ) as $vendor_id ) {
            if ( ! Helper::is_seller_enable_for_receive_payment( $vendor_id ) ) {
                $vendor_products = [];
                foreach ( $available_vendors[ $vendor_id ] as $product ) {
                    $vendor_products[] = sprintf( '<a href="%s">%s</a>', $product->get_permalink(), $product->get_name() );
                }

                $errors->add(
                    'paystack-not-configured',
                    wp_kses(
                        sprintf(
                            /* translators: 1: Vendor products */
                            __( '<strong>Error!</strong> Remove product %s and continue checkout, this product/vendor is not eligible to be paid with Paystack', 'dokan' ),
                            implode( ', ', $vendor_products )
                        ),
                        [
                            'strong' => [],
                        ]
                    )
                );
            }
        }
    }

    /**
     * Filter available payment gateways on checkout page.
     *
     * @param $gateways
     *
     * @return mixed
     */
    public function checkout_filter_gateway( $gateways ) {
        if ( ! Helper::is_ready() ) {
            return $gateways;
        }

        if ( ! isset( $gateways[ Helper::get_gateway_id() ] ) ) {
            return $gateways;
        }

        if ( empty( WC()->cart->cart_contents ) ) {
            return $gateways;
        }

        // If we find any subscription product in cart, we're not showing gateway
        foreach ( WC()->cart->cart_contents as $values ) {
            if ( Helper::is_vendor_subscription_product( $values['data']->get_id() ) ) {
                unset( $gateways[ Helper::get_gateway_id() ] );
                break;
            }
        }

        return $gateways;
    }
}
