<?php

namespace WeDevs\DokanPro\Modules\PayPalMarketplace\REST\V1;

use Exception;
use WP_Error;
use WP_REST_Controller;

/**
 * Paypal order controller for the given Woocommerce Order ID.
 */
class PayPalController extends WP_REST_Controller {
    /**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'dokan/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'paypal-marketplace';

    /**
	 * Register the routes for Dokan PayPal Marketplace payment.
	 */
    public function register_routes() {
        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/create-payment/(?P<order_id>\d+)', array(
				'methods'  => 'POST',
				'callback' => array( $this, 'create_payment' ),
				'args'     => array(
					'order_id' => array(
						'validate_callback' => function ( $param, $request, $key ) {
							return is_numeric( $param );
						},
					),
				),
				'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            $this->namespace, '/' . $this->rest_base . '/capture-payment/(?P<order_id>\d+)', array(
				'methods'  => 'POST',
				'callback' => array( $this, 'capture_payment' ),
				'args'     => array(
					'order_id' => array(
						'validate_callback' => function ( $param, $request, $key ) {
							return is_numeric( $param );
						},
					),
				),
				'permission_callback' => '__return_true',
            )
        );
    }

    /**
     * Create a PayPal order for the Woocommerce order ID.
     *
	 * @since 3.15.0
     * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
     */
    public function create_payment( $request ) {
        // Get order ID from request
        $order_id = $request['order_id'];

        try {
            $order = $this->get_order( $order_id );

            $paypal_order_id = $order->get_meta( '_dokan_paypal_order_id' );

            if ( $paypal_order_id ) {
                $process_payment = [
                    'paypal_order_id' => $paypal_order_id,
                    'id' => $order_id,
                ];
            } else {
                $dokan_paypal = dokan_pro()->module->paypal_marketplace->gateway_paypal;
                $process_payment = $dokan_paypal->process_payment( $order_id );
            }
        } catch ( Exception $ex ) {
            $error_code = 'paypal_payment';
            $status_code = 500;

            $process_payment = new WP_Error( $error_code, $ex->getMessage(), array( 'status' => $status_code ) );
        }

        return rest_ensure_response( $process_payment );
    }

    /**
     * Capture the Paypal payment for the given Woocommerce Order ID.
     *
	 * @since 3.15.0
     * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
     */
    public function capture_payment( $request ) {
        // Get order ID from request
        $order_id = $request['order_id'];

        try {
            $order = $this->get_order( $order_id );

            if ( ! $order ) {
                throw new Exception( __( 'No order is found.', 'dokan' ) );
            }

            $paypal_order_id = $order->get_meta( '_dokan_paypal_order_id' );

            if ( ! $paypal_order_id ) {
                throw new Exception( __( 'No PayPal order id found.', 'dokan' ) );
            }

            $order_controller = dokan_pro()->module->paypal_marketplace->order_controller;

            $process_payment = $order_controller->handle_capture_payment_validation( $order_id, $paypal_order_id );
        } catch ( Exception $ex ) {
            $error_code = 'paypal_capture_payment';
            $status_code = 404;

            $process_payment = new WP_Error( $error_code, $ex->getMessage(), array( 'status' => $status_code ) );
        }

        return rest_ensure_response( $process_payment );
    }

    /**
     * Get the order by order_id.
     *
	 * @since 3.15.0
     * @throws Exception if there is no Woocommerce Order by the given ID.
     * @param int $order_id The order ID.
     * @return \WC_Order
     */
    protected function get_order( $order_id ) {
        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            throw new Exception( esc_html__( 'Dokan PayPal Marketplace payment order not found in session', 'dokan' ) );
        }

        return $order;
    }
}
