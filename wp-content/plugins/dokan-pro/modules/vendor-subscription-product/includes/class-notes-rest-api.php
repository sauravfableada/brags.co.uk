<?php

class Dokan_VSP_Notes_Rest_Api extends WC_REST_Subscription_Notes_Controller {

    /**
     * Route base.
     *
     * @since 4.0.0
     *
     * @var string
     */
    protected $rest_base = 'product-subscriptions/(?P<order_id>[\d]+)/notes';

    /**
     * Endpoint namespace.
     *
     * @since 4.0.0
     *
     * @var string
     */
    protected $namespace = 'dokan/v1';

    /**
     * Post type.
     *
     * @since 4.0.0
     *
     * @var string
     */
    protected $post_type = 'shop_subscription';

    /**
     * Get order notes from an order.
     *
     * @since 4.0.0
     *
     * @param WP_REST_Request $request
     *
     * @return array|WP_Error
     */
    public function get_items( $request ) {
        return $this->perform_vendor_action(
            function () use ( $request ) {
                return parent::get_items( $request );
            }
        );
    }

    /**
     * Create a single order note.
     *
     * @since 4.0.0
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function create_item( $request ) {
        return $this->perform_vendor_action(
            function () use ( $request ) {
                return parent::create_item( $request );
            }
        );
    }

    /**
     * Perform an action with vendor permission check.
     *
     * @since 4.0.0
     *
     * @param callable $action The action to perform.
     *
     * @return mixed The result of the action.
     */
    private function perform_vendor_action( callable $action ) {
        add_filter( 'woocommerce_rest_check_permissions', [ $this, 'check_vendor_permission' ] );
        $result = $action();
        remove_filter( 'woocommerce_rest_check_permissions', [ $this, 'check_vendor_permission' ] );
        return $result;
    }

    /**
     * Check if the current user has vendor permissions.
     *
     * @since 4.0.0
     *
     * @return bool
     */
    public function check_vendor_permission(): bool {
        return dokan_is_user_seller( dokan_get_current_user_id() );
    }

    /**
     * Check if a given request has access to read a order note.
     *
     * @since 4.0.0
     *
     * @param  WP_REST_Request $request Full details about the request.
     *
     * @return WP_Error|boolean
     */
    public function get_item_permissions_check( $request ) {
        $error = $this->check_seller_is_the_order_owner( $request );
        if ( is_wp_error( $error ) ) {
            return $error;
        }

        return parent::get_item_permissions_check( $request );
    }

    /**
     * Check if a given request has access to read items.
     *
     * @since 4.0.0
     *
     * @param  WP_REST_Request $request Full details about the request.
     * @return WP_Error|boolean
     */
    public function get_items_permissions_check( $request ) {
        $error = $this->check_seller_is_the_order_owner( $request );
        if ( is_wp_error( $error ) ) {
            return $error;
        }

        // phpcs:ignore WordPress.WP.Capabilities.Unknown
        if ( current_user_can( dokan_admin_menu_capability() ) || current_user_can( 'dokandar' ) ) {
            return true;
        }

        return new WP_Error(
            'dokan_pro_permission_failure',
            __( 'You are not allowed to do this action.', 'dokan' ),
            [
                'status' => rest_authorization_required_code(),
			]
        );
    }

    /**
     * Check if a given request has access create order notes.
     *
     * @since 4.0.0
     *
     * @param  WP_REST_Request $request Full details about the request.
     *
     * @return bool|WP_Error
     */
    public function create_item_permissions_check( $request ) {
        $error = $this->check_seller_is_the_order_owner( $request );
        if ( is_wp_error( $error ) ) {
            return $error;
        }

        return $this->perform_vendor_action(
            function () use ( $request ) {
                return parent::create_item_permissions_check( $request );
            }
        );
    }

    /**
     * Check if a given request has access delete a order note.
     *
     * @since 4.0.0
     *
     * @param  WP_REST_Request $request Full details about the request.
     *
     * @return bool|WP_Error
     */
    public function delete_item_permissions_check( $request ) {
        $error = $this->check_seller_is_the_order_owner( $request );
        if ( is_wp_error( $error ) ) {
            return $error;
        }

        return $this->perform_vendor_action(
            function () use ( $request ) {
                return parent::delete_item_permissions_check( $request );
            }
        );
    }

    /**
     * Check if a given request has access to update a order note.
     *
     * @since 4.0.0
     *
     * @param $request
     *
     * @return true|\WP_Error
     */
    public function check_seller_is_the_order_owner( $request ) {
        $order     = wc_get_order( (int) $request['order_id'] );
        $seller_id = dokan_get_current_user_id();

        if ( $order && (int) $order->get_meta( '_dokan_vendor_id', true ) !== $seller_id ) {
            return new WP_Error(
                'dokan_pro_permission_failure',
                __( 'Unauthorized order access', 'dokan' ),
                [
                    'status' => rest_authorization_required_code(),
                ]
            );
        }

        return true;
    }
}
