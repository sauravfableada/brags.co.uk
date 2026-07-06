<?php

namespace WeDevs\DokanPro\REST;

use Exception;
use WC_Data_Store;
use WC_Order;
use WeDevs\Dokan\REST\DokanBaseVendorController;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Dokan REST API Order Actions Controller
 *
 * Handles requests to the /orders/<id>/actions endpoint
 *
 * @since 4.0.0
 */
class ManualOrderActionsController extends DokanBaseVendorController {

    /**
     * Endpoint namespace
     *
     * @var string
     */
    protected $namespace = 'dokan/v1';

    /**
     * Route base
     *
     * @var string
     */
    protected $rest_base = 'manual-orders/(?P<id>[\d]+)/actions';

    /**
     * Register routes
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace, '/' . $this->rest_base, array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_item' ),
                    'permission_callback' => array( $this, 'get_item_permissions_check' ),
                ),
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'handle_order_action_item' ),
                    'permission_callback' => array( $this, 'handle_order_action_item_permissions_check' ),
                    'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
                ),
            )
        );
    }

    /**
     * Check if a given request has access to get items
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function get_item_permissions_check( $request ) {
        if ( ! $this->check_permission() ) {
            return new WP_Error(
                'dokan_rest_cannot_view',
                esc_html__( 'Sorry, you cannot list resources.', 'dokan' ),
                array( 'status' => rest_authorization_required_code() )
            );
        }

        return $this->check_order_authorization( $request['id'] );
    }

    /**
     * Check if a given request has access to create items
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function handle_order_action_item_permissions_check( $request ) {
        if ( ! $this->check_permission() ) {
            return new WP_Error(
                'dokan_rest_cannot_create',
                esc_html__( 'Sorry, you are not allowed to create resources.', 'dokan' ),
                array( 'status' => rest_authorization_required_code() )
            );
        }

        return $this->check_order_authorization( $request['id'] );
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
     * Check if the current user has authorization for a specific order.
     *
     * @param int $order_id The order ID to check authorization for.
     * @return bool|WP_Error True if authorized, WP_Error if not.
     */
    protected function check_order_authorization( int $order_id ) {
        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return new WP_Error(
                'dokan_rest_invalid_order_id',
                esc_html__( 'Invalid order ID.', 'dokan' ),
                array( 'status' => 404 )
            );
        }

        if ( $order->get_meta( 'has_sub_order' ) ) {
            return new WP_Error(
                'dokan_rest_invalid_order',
                esc_html__( 'Sorry, this is a parent order', 'dokan' ),
                array( 'status' => 404 )
            );
        }

        $vendor_id = dokan_get_seller_id_by_order( $order_id );
        if ( $vendor_id !== dokan_get_current_user_id() ) {
            return new WP_Error(
                'dokan_rest_unauthorized_order',
                esc_html__( 'You do not have permission to access this order', 'dokan' ),
                array( 'status' => 403 )
            );
        }

        return true;
    }

    /**
     * Get available order actions
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_item( $request ) {
        $order_id = $request['id'];
        $auth_check = $this->check_order_authorization( $order_id );
        if ( is_wp_error( $auth_check ) ) {
            return $auth_check;
        }

        $order = wc_get_order( $order_id );
        $actions = $this->get_available_order_actions_for_order( $order );

        $data = array(
            'order_id' => $order_id,
            'actions'  => $actions,
        );
        $response = $this->prepare_item_for_response( $data, $request );

        return rest_ensure_response( $response );
    }

    /**
     * Create an order action
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     * @throws Exception If the action is invalid
     */
    public function handle_order_action_item( $request ) {
        $order_id = $request['id'];
        $auth_check = $this->check_order_authorization( $order_id );
        if ( is_wp_error( $auth_check ) ) {
            return $auth_check;
        }

        $action = $request['action'];
        $order = wc_get_order( $order_id );

        $result = $this->process_order_action( $order, $action );
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response(
            array(
                'message' => esc_html__( 'Order action applied successfully.', 'dokan' ),
                'action'  => $action,
            )
        );
    }

    /**
     * Get available order actions for a given order
     *
     * @param WC_Order $order
     * @return array
     */
    protected function get_available_order_actions_for_order( WC_Order $order ): array {
        $actions = array(
            'send_order_details'              => esc_html__( 'Send order details to customer', 'dokan' ),
            'send_order_details_admin'        => esc_html__( 'Resend new order notification', 'dokan' ),
            'regenerate_download_permissions' => esc_html__( 'Regenerate download permissions', 'dokan' ),
        );

        /**
         * Filters the list of available order actions.
         *
         * This filter allows you to add or remove order actions for the Dokan vendor dashboard.
         *
         * @since 4.0.0
         *
         * @param array    $actions The list of available order actions.
         * @param WC_Order $order   The order object.
         */
        return apply_filters( 'woocommerce_order_actions', $actions, $order );
    }

    /**
     * Process the order action
     *
     * Ref: woocommerce/includes/admin/meta-boxes/class-wc-meta-box-order-actions.php:124
     *
     * @param WC_Order $order
     * @param string $action
     * @return bool|WP_Error
     * @throws Exception If the action is invalid
     */
    protected function process_order_action( WC_Order $order, string $action ) {
        switch ( $action ) {
            case 'send_order_details':
                /**
                 * Fires before resending order emails.
                 *
                 * This action is triggered before resending various types of order emails.
                 *
                 * @since 4.0.0
                 *
                 * @param WC_Order $order The order object for which emails are being resent.
                 * @param string   $email_type The type of email being resent (e.g., 'customer_invoice', 'new_order').
                 */
                do_action( 'woocommerce_before_resend_order_emails', $order, 'customer_invoice' );

                WC()->payment_gateways();
                WC()->shipping();
                WC()->mailer()->customer_invoice( $order );
                $order->add_order_note( __( 'Order details manually sent to customer.', 'dokan' ), 0, true );

                /**
                 * Fires after resending an order email.
                 *
                 * This action is triggered after an order email has been resent.
                 *
                 * @since 4.0.0
                 *
                 * @param WC_Order $order The order object for which an email was resent.
                 * @param string   $email_type The type of email that was resent (e.g., 'customer_invoice', 'new_order').
                 */
                do_action( 'woocommerce_after_resend_order_email', $order, 'customer_invoice' );
                break;

            case 'send_order_details_admin':
                /**
                 * Fires before resending order emails.
                 *
                 * This action is triggered before resending various types of order emails.
                 *
                 * @since 4.0.0
                 *
                 * @param WC_Order $order The order object for which emails are being resent.
                 * @param string   $email_type The type of email being resent (e.g., 'customer_invoice', 'new_order').
                 */
                do_action( 'woocommerce_before_resend_order_emails', $order, 'new_order' );

                WC()->payment_gateways();
                WC()->shipping();

                add_filter( 'woocommerce_new_order_email_allows_resend', '__return_true' );
                WC()->mailer()->emails['WC_Email_New_Order']->trigger( $order->get_id(), $order, true );
                remove_filter( 'woocommerce_new_order_email_allows_resend', '__return_true' );

                /**
                 * Fires after resending an order email.
                 *
                 * This action is triggered after an order email has been resent.
                 *
                 * @since 4.0.0
                 *
                 * @param WC_Order $order The order object for which an email was resent.
                 * @param string   $email_type The type of email that was resent (e.g., 'customer_invoice', 'new_order').
                 */
                do_action( 'woocommerce_after_resend_order_email', $order, 'new_order' );
                break;

            case 'regenerate_download_permissions':
                $data_store = WC_Data_Store::load( 'customer-download' );
                $data_store->delete_by_order_id( $order->get_id() );
                wc_downloadable_product_permissions( $order->get_id(), true );
                break;

            default:
                if ( ! did_action( 'woocommerce_order_action_' . sanitize_title( $action ) ) ) {
                    /**
                     * Fires when a custom order action is being processed.
                     *
                     * This hook allows third-party plugins to add custom order actions
                     * and define their behavior when triggered through the API.
                     *
                     * @since 4.0.0
                     *
                     * @param WC_Order $order The order object for which the action is being performed.
                     *
                     * @hook woocommerce_order_action_{$action}
                     */
                    do_action( 'woocommerce_order_action_' . sanitize_title( $action ), $order );
                } else {
                    return new WP_Error(
                        'dokan_rest_invalid_order_action',
                        esc_html__( 'Invalid order action.', 'dokan' ),
                        array( 'status' => 400 )
                    );
                }
        }

        return true;
    }

    /**
     * Prepare the order actions for the REST response
     *
     * @param array           $actions The order actions data.
     * @param WP_REST_Request $request Request object.
     *
     * @return WP_REST_Response The prepared response.
     */
    public function prepare_item_for_response( $actions, $request ): WP_REST_Response {
        $this->request = $request;

        $response = rest_ensure_response( $actions );
        $response->add_links( $this->prepare_links( $actions, $request ) );

        /**
         * Filter the order actions data for the REST response.
         *
         * @since 4.0.0
         *
         * @param array           $data    The prepared order actions data.
         * @param array           $actions The original order actions data.
         * @param WP_REST_Request $request The request object.
         */
        return apply_filters( 'dokan_rest_prepare_order_actions', $response, $actions, $request );
    }

    /**
     * Prepare links for the request.
     *
     * @since  4.0.0
     * @@see woocommerce/includes/rest-api/Controllers/Version1/class-wc-rest-order-notes-v1-controller.php:371
     *
     * @param array           $actions  Object data.
     * @param WP_REST_Request $request  Request object.
     *
     * @return array                   Links for the given post.
     */
    protected function prepare_links( array $actions, WP_REST_Request $request ): array {
        $order_id  = $request->get_param( 'order_id' );
        $order_id  = (string) $request->get_param( 'order_id' );
        $base     = str_replace( '(?P<order_id>[\d]+)', $order_id, $this->rest_base );

        return array(
            'collection' => array(
                'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $base ) ),
            ),
            'up' => array(
                'href' => rest_url( sprintf( '/%s/manual-orders/%d', $this->namespace, $order_id ) ),
            ),
        );
    }

    /**
     * Get the Order Actions schema, conforming to JSON Schema
     *
     * @return array
     */
    public function get_item_schema(): array {
        $schema = array(
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'order_action',
            'type'       => 'object',
            'properties' => array(
                'action' => array(
                    'description' => esc_html__( 'Order action to perform.', 'dokan' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit' ),
                    'required'    => true,
                ),
            ),
        );

        return $this->add_additional_fields_schema( $schema );
    }
}
