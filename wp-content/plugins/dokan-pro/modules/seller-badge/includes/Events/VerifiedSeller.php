<?php

namespace WeDevs\DokanPro\Modules\SellerBadge\Events;

use WeDevs\Dokan\Vendor\Vendor;
use WeDevs\DokanPro\Modules\SellerBadge\Manager;
use WeDevs\DokanPro\Modules\SellerBadge\Abstracts\BadgeEvents;
use WeDevs\DokanPro\Modules\VendorVerification\Models\VerificationMethod;
use WeDevs\DokanPro\Modules\VendorVerification\Models\VerificationRequest;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // exit if accessed directly
}

/**
 * Class verified seller count badge
 *
 * @since   3.7.14
 *
 * @package WeDevs\DokanPro\Modules\SellerBadge\Events
 */
class VerifiedSeller extends BadgeEvents {

    /**
     * Class constructor
     *
     * @since 3.7.14
     *
     * @param string $event_type
     */
    public function __construct( $event_type ) {
        parent::__construct( $event_type );
        // return in case of error
        if ( is_wp_error( $this->badge_event ) ) {
            return;
        }
        add_action( 'dokan_verification_status_change', [ $this, 'process_hook' ], 10, 1 );
        add_action( 'dokan_pro_vendor_verification_request_updated', [ $this, 'on_verification_request_updated' ] );
    }

    /**
     * Process hooks related to this badge
     *
     * @since 3.7.14
     *
     * @param int $vendor_id
     *
     * @return void
     */
    public function process_hook( $vendor_id ) {
        if ( false === $this->set_badge_and_badge_level_data() ) {
            return;
        }

        // if badge status is draft, no need to update vendor badges
        if ( 'published' !== $this->badge_data['badge_status'] ) {
            return;
        }

        $this->run( $vendor_id );
    }

    /**
     * On verification request updated.
     *
     * @since 3.14.1
     *
     * @param int $request_id Verification request id.
     *
     * @return void
     */
    public function on_verification_request_updated( int $request_id ) {
        $verification_request = new VerificationRequest( $request_id );

        if ( VerificationRequest::STATUS_APPROVED !== $verification_request->get_status() ) {
            return;
        }

        if ( false === $this->set_badge_and_badge_level_data() ) {
            return;
        }

        // if badge status is draft, no need to update vendor badges
        if ( 'published' !== $this->badge_data['badge_status'] ) {
            return;
        }

        $vendor_id = $verification_request->get_vendor_id();
        $this->run( $vendor_id );
    }

    /**
     * Get current compare data
     *
     * @since 3.7.14
     *
     * @param int $vendor_id
     *
     * @return false|string[]
     */
    protected function get_current_data( $vendor_id ) {
        /**
         * @var Vendor $vendor
         */
        $vendor = dokan()->vendor->get( $vendor_id );
        if ( ! $vendor->get_id() ) {
            return false;
        }

        $shop_info = $vendor->get_shop_info();

        $verification_methods = ( new VerificationMethod() )->query(
            [
				'status' => VerificationMethod::STATUS_ENABLED,
			]
        );

        $verification_requests = new VerificationRequest();
        $approved_methods = array_unique(
            $verification_requests->query_field(
                [
					'field'     => 'method_id',
					'vendor_id' => $vendor_id,
					'status'    => VerificationRequest::STATUS_APPROVED,
                ]
            )
        );

        if ( ! $shop_info || empty( $approved_methods ) ) {
            return false;
        }

        // To make sure the array is not empty.
        $defaults = [
            'phone_verification' => 'pending',
            'social_profiles'    => 'pending',
        ];

        foreach ( $verification_methods as $method ) {
            $defaults[ $method->get_id() ] = in_array( $method->get_id(), $approved_methods, true ) ? 'approved' : 'pending';
        }

        return ! empty( $shop_info['dokan_verification'] ) ? wp_parse_args( $shop_info['dokan_verification']['info'], $defaults ) : $defaults;
    }

    /**
     * Run the event job
     *
     * @since 3.7.14
     *
     * @param int $vendor_id single vendor id.
     *
     * @return void
     */
    public function run( $vendor_id ) {
        $manager = new Manager();

        if ( ! is_numeric( $vendor_id ) ) {
            return;
        }

        if ( ! dokan_is_user_seller( $vendor_id ) ) {
            return;
        }

        $current_data = $this->get_current_data( $vendor_id );
        if ( false === $current_data ) {
            return;
        }

        $acquired_levels = $this->get_acquired_level_data( $vendor_id );
        if ( empty( $acquired_levels ) ) {
            return;
        }
        $approved_methods = array_unique(
            ( new VerificationRequest() )->query_field(
                [
                    'field'     => 'method_id',
                    'vendor_id' => $vendor_id,
                    'status'    => VerificationRequest::STATUS_APPROVED,
                ]
            )
        );

        foreach ( $acquired_levels as &$acquired_level ) {
            $acquired_level['acquired_status'] = 'draft';
            $acquired_level['acquired_data']   = $acquired_level['level_condition'];

            // more than, less than, equal to
            switch ( $acquired_level['level_condition'] ) {
                case 'id_verification':
                    // if level data is less than current earning, user got this level
                    if ( 'approved' === $current_data['dokan_v_id_status'] ) {
                        $acquired_level['acquired_status'] = 'published';
                    }
                    break;

                case 'company_verification':
                    if ( 'approved' === $current_data['company_v_status'] ) {
                        $acquired_level['acquired_status'] = 'published';
                    }
                    break;

                case 'address_verification':
                    if ( 'approved' === $current_data['store_address']['v_status'] ) {
                        $acquired_level['acquired_status'] = 'published';
                    }
                    break;

                case 'phone_verification':
                    if ( 'approved' === $current_data['phone_verification'] ) {
                        $acquired_level['acquired_status'] = 'published';
                    }
                    break;

                case 'social_profiles':
                    if ( 'approved' === $current_data['social_profiles'] ) {
                        $acquired_level['acquired_status'] = 'published';
                    }
                    break;
                default:
                    if ( in_array( $acquired_level['level_condition'], $approved_methods, true ) ) {
                        $acquired_level['acquired_status'] = 'published';
                    }
            }

            // user got this level
            if ( empty( $acquired_level['id'] ) && 'published' === $acquired_level['acquired_status'] ) {
                // this is the first time user getting this level
                $acquired_level['badge_seen'] = 0;
                $acquired_level['created_at'] = time();
            }
        }

        // now save acquired badge data
        $inserted = $manager->update_vendor_acquired_badge_levels_data( $acquired_levels );
        if ( is_wp_error( $inserted ) ) {
            dokan_log(
                sprintf(
                    'Dokan Vendor Badge: update acquired badge level failed. \n\rFile: %s \n\rLine: %s \n\rError: %s,',
                    __FILE__, __LINE__, $inserted->get_error_message()
                )
            );
        }
    }
}
