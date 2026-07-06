<?php

namespace WeDevs\DokanPro\Modules\RMA;

use WeDevs\DokanPro\Modules\RMA\Traits\RMACommon;
use WP_Error;

/**
* Warranty Request class
*
* @package dokan
*
* @since 1.0.0
*/
class WarrantyRequest {

    use RMACommon;

    /**
     * Get all request
     *
     * @since 1.0.0
     *
     * @param array $data
     *
     * @return array
     */
    public function all( array $data = [] ): array {
        $results  = [];
        $requests = dokan_get_warranty_request( $data );

        if ( $requests ) {
            foreach ( $requests as $request ) {
                $warranty_request_data = $this->transform_warranty_requests( $request );
                if ( ! is_wp_error( $warranty_request_data ) ) {
                    $results[] = $warranty_request_data;
                }
            }
        }

        return $results;
    }

    /**
     * Get a single request
     *
     * @since 1.0.0
     *
     * @param int $id Request id to get the data
     *
     * @return array|WP_Error
     */
    public function get( int $id = 0 ) {
        if ( ! $id ) {
            return new WP_Error( 'no-id', __( 'No request id found', 'dokan' ) );
        }

        $results  = [];
        $request = dokan_get_warranty_request( [ 'id' => $id ] );

        if ( $request ) {
            $results = $this->transform_warranty_requests( $request );
        }

        return $results;
    }

    /**
     * Save warranty request data
     *
     * @since 1.0.0
     *
     * @param array $data Request data
     *
     * @return int|\WP_Error
     */
    public function create( array $data = [] ) {
        return dokan_save_warranty_request( $data );
    }

    /**
     * Update status
     *
     * @since 1.0.0
     *
     * @param array $data Request data
     *
     * @return int|WP_Error
     */
    public function update( array $data = [] ) {
        return dokan_update_warranty_request( $data );
    }

    /**
     * Update status
     *
     * @since 4.0.0
     *
     * @param int $id Request id
     * @param string $status Request status
     *
     * @return int|WP_Error
     */
    public function update_status( int $id = 0, string $status = '' ) {
        return dokan_update_warranty_request_status( $id, $status );
    }

    /**
     * Delete warranty request
     *
     * @param int $id Request id
     * @param int $vendor_id Vendor id
     *
     * @since 3.0.7
     *
     * @return bool|WP_Error
     */
    public function delete( int $id = 0, int $vendor_id = 0 ) {
        if ( ! $id ) {
            return new WP_Error( 'no-id', __( 'No request id found', 'dokan' ) );
        }

        if ( ! $vendor_id ) {
            return new WP_Error( 'no-vendor-id', __( 'No vendor id found', 'dokan' ) );
        }

        return dokan_delete_warranty_request( $id, $vendor_id );
    }
}
