<?php

namespace WeDevs\DokanPro\Upgrade\Upgraders\BackgroundProcesses;

use Exception;
use WeDevs\Dokan\Abstracts\DokanBackgroundProcesses;
use WeDevs\Dokan\Vendor\Vendor;
use WeDevs\DokanPro\Modules\VendorVerification\Models\VerificationRequest;

/**
 * Dokan V_3_9_2 Upgrade Background Processor Class.
 *
 * @since 3.11.1
 */
class V_3_11_1 extends DokanBackgroundProcesses {

    /**
     * Action
     * Override this action in processor class
     *
     * @since 3.11.1
     * @var string
     */
    protected $action = 'dokan_pro_bg_action_3_11_1';

    /**
     * Perform Updates.
     *
     * @since 3.11.1
     *
     * @param array $item
     *
     * @return array|false
     */
    public function task( $item ) {
        if ( empty( $item ) ) {
            return false;
        }

        if ( 'create_verification_submission' === $item['task'] ) {
            return $this->process_task_queue( $item );
        }

        return false;
    }

    /**
     * Create Vendor Verification Submission for vendors.
     *
     * @since 3.11.1
     * @return array|bool
     */
    private function process_task_queue( $args ) {
        $paged    = $args['paged'] ?? 1;
        $per_page = 10;
        $offset   = ( $paged - 1 ) * $per_page;

        $vendors = dokan()->vendor->all(
            [
                'status' => 'all',
                'offset' => $offset,
                'number' => $per_page,
            ]
        );

        if ( empty( $vendors ) ) {
            return false;
        }

        foreach ( $vendors as $vendor ) {
            $this->create_verification_submission( $vendor );
        }

        $args['paged'] = ++$paged;

        return $args;
    }

    /**
     * Create Verifications Submissions.
     *
     * @since 3.11.1
     *
     * @param Vendor $vendor Vendor.
     *
     * @return void
     */
    protected function create_verification_submission( Vendor $vendor ) {
        $seller_profile = $vendor->get_shop_info();
        $method_ids     = get_option( 'dokan_vendor_verification_initial_method_ids', [] );

        if ( ! isset( $seller_profile['dokan_verification']['info'] ) || empty( $method_ids ) ) {
            return;
        }

        $statuses = array_keys( ( new VerificationRequest() )->get_statuses() ) ?? [];

        $old_verification_info = $seller_profile['dokan_verification']['info'];

        // migrate photo id verification data.
        if ( isset( $old_verification_info['dokan_v_id_status'] ) ) {
            try {
                $verification_id_type  = $old_verification_info['dokan_v_id_type'];
                $verification_photo_id = $old_verification_info['photo_id'];
                $status                = $old_verification_info['dokan_v_id_status'];
                $status                = in_array( $status, $statuses, true ) ? $status : 'pending';
                ( new VerificationRequest() )
                    ->set_status( $status )
                    ->set_vendor_id( $vendor->get_id() )
                    ->set_method_id( $this->parse_method_id( $verification_id_type ) )
                    ->set_documents( [ $verification_photo_id ] )
                    ->create();
            } catch ( Exception $exception ) {
                dokan_log( 'Error while migrating photo id verification data: ' . $exception->getMessage() . ' for vendor: ' . $vendor->get_id() . ' with data: ' . print_r( $old_verification_info, 1 ) );
            }
        }

        // migrate store address verification data.
        if ( isset( $old_verification_info['store_address'] ) ) {
            $address_info  = $old_verification_info['store_address'];
            $address_proof = attachment_url_to_postid( $address_info['proof'] );
            $status        = $address_info['v_status'];
            $status        = in_array( $status, $statuses, true ) ? $status : 'pending';

            unset( $address_info['proof'], $address_info['v_status'] );

            $note            = __( 'Address: ', 'dokan' ) . ' ' . implode( ', ', array_values( $address_info ) );
            $info['address'] = $address_info;

            try {
                ( new VerificationRequest() )
                    ->set_status( $status )
                    ->set_vendor_id( $vendor->get_id() )
                    ->set_method_id( $this->parse_method_id( 'address' ) )
                    ->set_documents( [ $address_proof ] )
                    ->set_note( $note )
                    ->set_additional_info( $info )
                    ->create();
            } catch ( Exception $exception ) {
                dokan_log( 'Error while migrating address verification data: ' . $exception->getMessage() . ' for vendor: ' . $vendor->get_id() . ' with data: ' . print_r( $old_verification_info, 1 ) );
            }
        }

        // migrate store address verified data.
        if ( isset( $old_verification_info['company_v_status'] ) ) {
            try {
                $status = $old_verification_info['company_v_status'];
                $status = in_array( $status, $statuses, true ) ? $status : 'pending';
                ( new VerificationRequest() )
                    ->set_status( $status )
                    ->set_vendor_id( $vendor->get_id() )
                    ->set_method_id( $this->parse_method_id( 'company' ) )
                    ->set_documents( $seller_profile['company_verification_files'] )
                    ->create();
            } catch ( Exception $exception ) {
                dokan_log( 'Error while migrating company verification data: ' . $exception->getMessage() . ' for vendor: ' . $vendor->get_id() . ' with data: ' . print_r( $old_verification_info, 1 ) );
            }
        }
    }

    /**
     * Parse New method id from old method type.
     *
     * @since 3.11.1
     *
     * @param string $old_type Old type.
     *
     * @throws Exception If Initial Ids not founds.
     * @return int
     */
    private function parse_method_id( string $old_type ): int {
        $method_ids = get_option( 'dokan_vendor_verification_initial_method_ids', [] );

        if ( empty( $method_ids ) ) {
            throw new Exception( esc_html__( 'Initial Verification methods ids not stored.', 'dokan' ) );
        }

        switch ( $old_type ) {
            case 'driving_license':
                $id = $method_ids['driving_license'];
                break;
            case 'passport':
                $id = $method_ids['passport'];
                break;
            case 'national_id':
                $id = $method_ids['national_id'];
                break;
            case 'address':
                $id = $method_ids['address'];
                break;
            case 'company':
                $id = $method_ids['company'];
                break;
            default:
                $id = 0;
                break;
        }

        return $id;
    }
}
