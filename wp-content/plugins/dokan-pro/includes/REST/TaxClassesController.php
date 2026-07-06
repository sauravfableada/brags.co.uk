<?php

namespace WeDevs\DokanPro\REST;

use WC_REST_Tax_Classes_Controller;
use WP_Error;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * Dokan REST API Tax Classes controller class.
 *
 * @since 4.0.0
 *
 * @package WeDevs\DokanPro\REST
 */
class TaxClassesController extends WC_REST_Tax_Classes_Controller {

    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'dokan/v1';

    /**
     * Check if a given request has access to read tax classes.
     *
     * @param  WP_REST_Request $request Full details about the request.
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
     * Check if a given request has access to delete a tax class.
     *
     * @param  WP_REST_Request $request Full details about the request.
     * @return WP_Error|boolean
     */
    public function delete_item_permissions_check( $request ) {
        if ( ! $this->check_permission() ) {
            return new WP_Error(
                'dokan_rest_cannot_delete',
                esc_html__( 'Sorry, you are not allowed to delete this resource.', 'dokan' ),
                array( 'status' => rest_authorization_required_code() )
            );
        }
        return true;
    }

    /**
     * Check vendor permission.
     *
     * @return bool
     */
    protected function check_permission(): bool {
        return user_can( dokan_get_current_user_id(), 'dokan_manage_manual_order' );
    }
}
