<?php // phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query, Universal.NamingConventions.NoReservedKeywordParameterNames.objectFound

namespace WeDevs\DokanPro\REST;

use WC_Data;
use WC_Data_Exception;
use WC_Order;
use WC_REST_Exception;
use WC_REST_Orders_Controller;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST API Orders controller for Dokan
 *
 * Handles requests to the /manual-orders endpoint.
 *
 * @since 4.0.0
 */
class ManualOrdersController extends WC_REST_Orders_Controller {

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
    protected $rest_base = 'manual-orders';

    /**
     * Check if a given request has access to perform an action.
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_Error|boolean
     */
    public function get_items_permissions_check( $request ) {
        return $this->check_permission( $request, 'view' );
    }

    /**
     * Check if a given request has access to perform an action.
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_Error|boolean
     */
    public function create_item_permissions_check( $request ) {
        return $this->check_permission( $request, 'create' );
    }

    /**
     * Check if a given request has access to perform an action.
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_Error|boolean
     */
    public function get_item_permissions_check( $request ) {
        $result = $this->check_order_authorization( (int) $request['id'] );
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return $this->check_permission( $request, 'view' );
    }

    /**
     * Check if a given request has access to perform an action.
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_Error|boolean
     */
    public function update_item_permissions_check( $request ) {
        $result = $this->check_order_authorization( (int) $request['id'] );
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return $this->check_permission( $request, 'edit' );
    }

    /**
     * Check if a given request has access to perform an action.
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_Error|boolean
     */
    public function delete_item_permissions_check( $request ) {
        $result = $this->check_order_authorization( (int) $request['id'] );
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return $this->check_permission( $request, 'delete' );
    }

    /**
     * Get formatted item data.
     *
     * @since 4.0.0
     *
     * @param WC_Order $order WC_Data instance.
     *
     * @return array
     * @see   \WC_REST_Orders_V2_Controller::get_formatted_item_data
     * @see   \WC_Order::get_formatted_billing_address
     * @see   \WC_Order::get_formatted_shipping_address
     */
    protected function get_formatted_item_data( $order ) {
        $item_data = parent::get_formatted_item_data( $order );

        $billing_address  = $order->get_formatted_billing_address();
        $shipping_address = $order->get_formatted_shipping_address();

        $item_data['formatted_billing_address']  = $billing_address;
        $item_data['formatted_shipping_address'] = $shipping_address;

        return $item_data;
    }

    /**
     * Get all orders
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_REST_Response
     */
    public function get_items( $request ): WP_REST_Response {
        return $this->perform_vendor_action(
            function () use ( $request ) {
                return parent::get_items( $request );
            }
        );
    }

    /**
     * Get a single item.
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function get_item( $request ) {
        $order_id = (int) $request['id'];
        $result   = $this->check_order_authorization( $order_id );
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return $this->perform_vendor_action(
            function () use ( $request ) {
                return parent::get_item( $request );
            }
        );
    }

    /**
     * Create a single order.
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
     * Update a single post.
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function update_item( $request ) {
        $order_id = (int) $request['id'];
        $result   = $this->check_order_authorization( $order_id );
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return $this->perform_vendor_action(
            function () use ( $request ) {
                return parent::update_item( $request );
            }
        );
    }

    /**
     * Delete a single item.
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function delete_item( $request ) {
        $order_id = (int) $request['id'];
        $result   = $this->check_order_authorization( $order_id );
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return $this->perform_vendor_action(
            function () use ( $request ) {
                return parent::delete_item( $request );
            }
        );
    }

    /**
     * Prepare a single order for create or update.
     *
     * @param WP_REST_Request $request  Request object.
     * @param bool            $creating If is creating a new object.
     *
     * @return WP_Error|WC_Data
     * @throws WC_REST_Exception When an invalid parameter is found.
     * @throws WC_Data_Exception When an invalid parameter is found.
     */
    protected function prepare_object_for_database( $request, $creating = false ) {
        $order = parent::prepare_object_for_database( $request, $creating );

        if ( is_wp_error( $order ) ) {
            return $order;
        }

        if ( ! $order instanceof WC_Order ) {
            return new WP_Error( 'dokan_rest_invalid_order', esc_html__( 'Invalid order.', 'dokan' ), [ 'status' => 400 ] );
        }

        if ( $creating && ! $order->meta_exists( '_dokan_vendor_id' ) ) {
            $order->update_meta_data( '_dokan_vendor_id', dokan_get_current_user_id() );
            $order->update_meta_data( '_wc_order_attribution_source_type', 'vendor' );
            $order->update_meta_data( '_wc_order_attribution_utm_source', 'typein' );
        }

        return apply_filters( "dokan_rest_pre_insert_{$this->post_type}_object", $order, $request, $creating );
    }

    /**
     * Save an object data.
     *
     * @param WP_REST_Request $request  Full details about the request.
     * @param bool            $creating If is creating a new object.
     *
     * @return WC_Data|WP_Error
     * @throws WC_REST_Exception When an invalid parameter is found.
     */
    protected function save_object( $request, $creating = false ) {
        $order = parent::save_object( $request, $creating );

        if ( is_wp_error( $order ) ) {
            return $order;
        }

        $vendor_id = $order->get_meta( '_dokan_vendor_id' );
        if ( ! $vendor_id || dokan_get_current_user_id() !== (int) $vendor_id ) {
            return new WP_Error(
                'dokan_rest_cannot_edit_order',
                esc_html__( 'Sorry, you are not allowed to edit this resource.', 'dokan' ),
                [ 'status' => 403 ]
            );
        }

        if ( $creating && 'dokan-rest-api' !== $order->get_created_via() ) {
            $order->set_created_via( 'dokan-rest-api' );
            $order->save();
        }

        $this->sync_order_and_balance( $order );

        /**
         * Fires after an order is saved via the REST API.
         *
         * @since 4.0.0
         *
         * @param WC_Order        $order    The order object.
         * @param WP_REST_Request $request  The request object.
         * @param bool            $creating If is creating a new object.
         */
        do_action( 'woocommerce_process_shop_order_meta', $order->get_id(), $order );

        return $order;
    }

    /**
     * Prepare objects query.
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return array
     */
    protected function prepare_objects_query( $request ): array {
        $args = parent::prepare_objects_query( $request );
        $user = dokan_get_current_user_id();

        if ( ! dokan_is_user_seller( $user ) ) {
            return $args;
        }

        $args['meta_query']   = $args['meta_query'] ?? [];
        $args['meta_query'][] = [
            'key'     => '_dokan_vendor_id',
            'value'   => $user,
            'compare' => '=',
        ];

        return apply_filters( 'dokan_rest_orders_prepare_object_query', $args, $request );
    }

    /**
     * Prepare a single order output for response.
     *
     * @param WC_Data         $object  Object data.
     * @param WP_REST_Request $request Request object.
     *
     * @return WP_REST_Response
     */

    /**
     * Sync order and balance
     *
     * @param WC_Data|WP_Error $order
     *
     * @return void
     */
    private function sync_order_and_balance( $order ): void {
        if ( is_wp_error( $order ) ) {
            return;
        }

        $vendor_id = $order->get_meta( '_dokan_vendor_id' );
        $vendor    = dokan()->vendor->get( $vendor_id );

        if ( ! $vendor ) {
            return;
        }

        dokan_sync_insert_order( $order->get_id() );
    }

    public function prepare_object_for_response( $object, $request ): WP_REST_Response {
        $response = parent::prepare_object_for_response( $object, $request );

        if ( ! $object instanceof \WC_Order ) {
            return $response;
        }

        $response->data['subtotal']  = (string) $object->get_subtotal();
        $response->data['fee_total'] = (string) $object->get_total_fees();

        return $response;
    }

    /**
     * Check if a given request has access to perform an action.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @param string          $action  The action to check (view, create, edit, delete).
     *
     * @return WP_Error|boolean
     */
    protected function check_permission( WP_REST_Request $request, string $action ) {
        if ( ! $this->check_vendor_permission() ) {
            $messages = [
                'view'   => esc_html__( 'Sorry, you cannot list resources.', 'dokan' ),
                'create' => esc_html__( 'Sorry, you are not allowed to create resources.', 'dokan' ),
                'edit'   => esc_html__( 'Sorry, you are not allowed to edit this resource.', 'dokan' ),
                'delete' => esc_html__( 'Sorry, you are not allowed to delete this resource.', 'dokan' ),
            ];

            return new WP_Error( "dokan_rest_cannot_$action", $messages[ $action ], [ 'status' => rest_authorization_required_code() ] );
        }

        return true;
    }

    /**
     * Check if the current user has authorization for a specific order.
     *
     * @param int $order_id The order ID to check authorization for.
     *
     * @return bool|WP_Error True if authorized, WP_Error if not.
     */
    protected function check_order_authorization( int $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order instanceof \WC_Order || $order->get_status() === 'trash' ) {
            return new WP_Error(
                'dokan_rest_invalid_order',
                esc_html__( 'Invalid order.', 'dokan' ),
                [ 'status' => 404 ]
            );
        }

        $vendor_id = dokan_get_seller_id_by_order( $order_id );
        if ( $vendor_id !== dokan_get_current_user_id() ) {
            return new WP_Error(
                'dokan_rest_unauthorized_order',
                esc_html__( 'You do not have permission to this order', 'dokan' ),
                array( 'status' => rest_authorization_required_code() )
            );
        }

        return true;
    }

    /**
     * Check if the current user has vendor permissions.
     *
     * @return bool
     */
    public function check_vendor_permission(): bool {
        return user_can( dokan_get_current_user_id(), 'dokan_manage_manual_order' );
    }

    /**
     * Perform an action with vendor permission check.
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
}
