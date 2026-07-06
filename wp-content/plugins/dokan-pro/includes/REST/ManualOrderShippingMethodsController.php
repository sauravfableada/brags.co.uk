<?php

namespace WeDevs\DokanPro\REST;

use WC_Order;
use WC_Shipping;
use WC_Shipping_Method;
use WeDevs\Dokan\REST\DokanBaseVendorController;
use WeDevs\DokanPro\Shipping\ShippingZone;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Dokan REST API Shipping Methods controller class.
 *
 * @since 4.0.0
 *
 * @package WeDevs\DokanPro\REST
 */
class ManualOrderShippingMethodsController extends DokanBaseVendorController {

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'manual-orders/(?P<order_id>[\d]+)/shipping_methods';

    /**
     * Register the route for /manual-orders/<id>/shipping_methods
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace, '/' . $this->rest_base,
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_items' ),
                    'permission_callback' => array( $this, 'get_items_permissions_check' ),
                    'args'                => $this->get_collection_params(),
                ),
                'schema' => array( $this, 'get_public_item_schema' ),
            )
        );
    }

    /**
     * Check if a given request has access to read shipping methods.
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_Error|boolean
     */
    public function get_items_permissions_check( $request ) {
        if ( ! $this->check_permission() ) {
            return new WP_Error(
                'dokan_rest_cannot_view',
                esc_html__( 'Sorry, you cannot list resources.', 'dokan' ),
                array( 'status' => rest_authorization_required_code() )
            );
        }

        return true;
    }

    /**
     * Check permission for the request
     *
     * @return bool
     */
    public function check_permission(): bool {
        return user_can( dokan_get_current_user_id(), 'dokan_manage_manual_order' );
    }

    /**
     * Get shipping methods.
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function get_items( $request ) {
        $data     = array();
        $order_id = $request->get_param( 'order_id' );

        $order = wc_get_order( $order_id );
        if ( ! $order instanceof WC_Order ) {
            return new WP_Error(
                'dokan_rest_order_invalid',
                esc_html__( 'Invalid order ID.', 'dokan' ),
                array( 'status' => 404 )
            );
        }

        $wc_shipping = WC_Shipping::instance();
        $wc_methods  = $wc_shipping->get_shipping_methods();

        $vendor_id        = dokan_get_seller_id_by_order( $order );
        $shipping_zone    = ShippingZone::get_shipping_zone_by_order( $order, $vendor_id );
        $shipping_methods = ShippingZone::get_shipping_methods( $shipping_zone->get_id(), $vendor_id );

        // Map shipping method data to actual WC_Shipping_Method objects
        $shipping_methods = array_map(
            static function ( $shipping_method ) use ( $wc_methods ) {
                $method_id    = $shipping_method['method_id'] ?? $shipping_method['id'] ?? '';
                $method_class = isset( $wc_methods[ $method_id ] ) ? get_class( $wc_methods[ $method_id ] ) : null;

                if ( ! $method_class ) {
                    return $shipping_method;
                }

                // Get the instance ID
                $instance_id = $shipping_method['instance_id'] ?? 0;

                // Create instance of the actual shipping method class
                $method = new $method_class( $instance_id );
                if ( ! $method instanceof WC_Shipping_Method ) {
                    return $shipping_method;
                }

                // Update the method properties
                $method->settings = array_merge(
                    $method->get_option( 'instance_settings', array() ),
                    $shipping_method['settings'] ?? array()
                );

                return $method;
            },
            $shipping_methods
        );

        foreach ( $shipping_methods as $shipping_method ) {
            $method = $this->prepare_item_for_response( $shipping_method, $request );
            $method = $this->prepare_response_for_collection( $method );
            $data[] = $method;
        }

        $total    = count( $data );
        $response = rest_ensure_response( $data );

        return $this->format_collection_response( $response, $request, $total );
    }

    /**
     * Prepare a shipping method for response.
     *
     * @param  WC_Shipping_Method $method   Shipping method object.
     * @param  WP_REST_Request    $request  Request object.
     * @return WP_REST_Response   $response Response data.
     */
    public function prepare_item_for_response( $method, $request ) {
        $data = array(
            'id'          => $method->id,
            'instance_id' => $method->instance_id,
            'title'       => $method->settings['title'] ?? $method->method_title,
            'description' => $method->settings['description'] ?? $method->method_description,
        );

        $context = ! empty( $request['context'] ) ? $request['context'] : 'view';
        $data    = $this->add_additional_fields_to_object( $data, $request );
        $data    = $this->filter_response_by_context( $data, $context );

        // Wrap the data in a response object.
        $response = rest_ensure_response( $data );

        $response->add_links( $this->prepare_links( $method, $request ) );

        /**
         * Filter shipping methods object returned from the REST API.
         *
         * @param WP_REST_Response   $response The response object.
         * @param WC_Shipping_Method $method   Shipping method object used to create response.
         * @param WP_REST_Request    $request  Request object.
         */
        return apply_filters( 'dokan_rest_prepare_shipping_method', $response, $method, $request );
    }

    /**
     * Prepare links for the request.
     *
     * @since  4.0.0
     * @see woocommerce/includes/rest-api/Controllers/Version1/class-wc-rest-order-notes-v1-controller.php:371
     *
     * @param WC_Shipping_Method $method Shipping method object or array.
     * @param WP_REST_Request $request Request object.
     *
     * @return array Links for the given shipping method.
     */
    protected function prepare_links( $method, $request ) {
        $order_id  = $request->get_param( 'order_id' );
        $base = str_replace( '(?P<order_id>[\d]+)', $order_id, $this->rest_base );

        return array(
            'self' => array(
                'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $base, $method->instance_id ) ),
            ),
            'collection' => array(
                'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $base ) ),
            ),
            'up' => array(
                'href' => rest_url( sprintf( '/%s/manual-orders/%d', $this->namespace, $order_id ) ),
            ),
        );
    }

    /**
     * Get any query params needed.
     *
     * @since  4.0.0
     *
     * @return array
     */
    public function get_collection_params() {
        return array(
            'context' => $this->get_context_param( array( 'default' => 'view' ) ),
        );
    }
}
