<?php

namespace WeDevs\DokanPro\REST;

use WC_REST_Order_Notes_Controller;
use WP_Comment;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_User;

/**
 * REST API Order Notes controller for Dokan
 *
 * Handles requests to the /manual-orders/<id>/notes endpoint.
 *
 * @since   4.0.0
 *
 * @package dokan
 */
class ManualOrderNotesController extends WC_REST_Order_Notes_Controller {

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
    protected $rest_base = 'manual-orders/(?P<order_id>[\d]+)/notes';

    /**
     * Check if the current user has authorization for a specific order.
     *
     * @param int $order_id The order ID to check authorization for.
     *
     * @return bool|WP_Error True if authorized, WP_Error if not.
     */
    protected function check_order_authorization( int $order_id ) {
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
     * Check if a given request has access to read items.
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

        return $this->check_order_authorization( $request['order_id'] );
    }

    /**
     * Check if a given request has access to create an item.
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_Error|boolean
     */
    public function create_item_permissions_check( $request ) {
        if ( ! $this->check_permission() ) {
            return new WP_Error(
                'dokan_rest_cannot_create',
                esc_html__( 'Sorry, you are not allowed to create resources.', 'dokan' ),
                array( 'status' => rest_authorization_required_code() )
            );
        }

        return $this->check_order_authorization( $request['order_id'] );
    }

    /**
     * Check if a given request has access to read an item.
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_Error|boolean
     */
    public function get_item_permissions_check( $request ) {
        if ( ! $this->check_permission() ) {
            return new WP_Error(
                'dokan_rest_cannot_view',
                esc_html__( 'Sorry, you cannot view this resource.', 'dokan' ),
                array( 'status' => rest_authorization_required_code() )
            );
        }

        return $this->check_order_authorization( $request['order_id'] );
    }

    /**
     * Check if a given request has access delete a order note.
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return bool|WP_Error
     */
    public function delete_item_permissions_check( $request ) {
        if ( ! $this->check_permission() ) {
            return new WP_Error(
                'dokan_rest_cannot_delete',
                esc_html__( 'Sorry, you are not allowed to delete this resource.', 'dokan' ),
                array( 'status' => rest_authorization_required_code() )
            );
        }

        return $this->check_order_authorization( $request['order_id'] );
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
     * Create a single order note.
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function create_item( $request ) {
        $response = parent::create_item( $request );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        if ( $response->get_status() === 201 ) {
            $note_id     = $response->get_data()['id'];
            $note_object = get_comment( $note_id );

            $note_object = $this->update_note_author( $note_object, $request, true );

            $response = $this->prepare_item_for_response( $note_object, $request );
            $response->set_status( 201 );
        }

        return $response;
    }

    /**
     * Update a single order note.
     *
     * @param WP_Comment      $note     Order note object.
     * @param WP_REST_Request $request  Full details about the request.
     * @param bool            $creating If the request is creating a new note.
     *
     * @return WP_Comment
     */
    public function update_note_author( WP_Comment $note, WP_REST_Request $request, bool $creating ): WP_Comment {
        $user_id   = dokan_get_current_user_id();
        $user_data = get_user_by( 'id', $user_id );

        if ( $creating && $user_data instanceof WP_User && dokan_is_user_seller( $user_id ) ) {
            wp_update_comment(
                array(
                    'comment_ID'           => $note->comment_ID,
                    'user_id'              => $user_id,
                    'comment_author'       => $user_data->display_name,
                    'comment_author_email' => $user_data->user_email,
                )
            );

            // Update comment meta to indicate it's a vendor note
            update_comment_meta( (int) $note->comment_ID, 'dokan_vendor_note', 1 );
        }

        // refresh the note object
        return get_comment( $note->comment_ID );
    }
}
