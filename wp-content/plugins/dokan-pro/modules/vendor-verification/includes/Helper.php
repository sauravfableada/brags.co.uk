<?php

namespace WeDevs\DokanPro\Modules\VendorVerification;

use WeDevs\DokanPro\Modules\VendorVerification\Models\VerificationRequest;

defined( 'ABSPATH' ) || exit;

/**
 * Admin Class
 *
 * @since 3.11.1 Migrated to Class.
 */
class Helper {
    /**
     * Get verified status Icon list
     *
     * @since 3.11.1
     *
     * @return array
     */
    public static function get_verified_icons(): array {
        $icons = [
            'check_circle_solid'   => '<i class="fas fa-check-circle"></i>',
            'check_circle_regular' => '<i class="far fa-check-circle"></i>',
            'check_solid'          => '<i class="fas fa-check"></i>',
            'check_double_solid'   => '<i class="fas fa-check-double"></i>',
            'check_square_solid'   => '<i class="fas fa-check-square"></i>',
            'check_squire_regular' => '<i class="far fa-check-square"></i>',
            'user_check_solid'     => '<i class="fas fa-user-check"></i>',
            'certificate_solid'    => '<i class="fas fa-certificate"></i>',
        ];

        return apply_filters( 'dokan_pro_vendor_verification_verified_store_icon', $icons );
    }

    /**
     * Get a verified vendor count.
     *
     * @since 4.1.0
     *
     * @return int The count of verified vendors
     */
    public static function get_verified_vendor_count(): int {
        global $wpdb;

        // Get verified vendors count by checking user meta
        $verified_vendors = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT user_id)
                FROM {$wpdb->usermeta}
                WHERE meta_key = %s
                AND meta_value = %s",
                'dokan_verification_status',
                'approved'
            )
        );

        return (int) ( $verified_vendors ?? 0 );
    }

    /**
     * Get the translated version of approval statuses
     *
     * @since 3.5.4
     *
     * @param $status
     *
     * @return string Translated Status
     */
    public static function get_translated_status( $status ) {
        switch ( $status ) {
            case VerificationRequest::STATUS_APPROVED:
                return __( 'Approved', 'dokan' );
            case VerificationRequest::STATUS_PENDING:
                return __( 'Pending', 'dokan' );
            case VerificationRequest::STATUS_REJECTED:
                return __( 'Rejected', 'dokan' );
            default:
                return $status;
        }
    }

    /**
     * Check if a vendor is verified
     *
     * @param int $vendor_id
     *
     * @return bool
     */
    public static function is_seller_verified( $vendor_id ): bool {
        return false !== strpos( get_user_meta( $vendor_id, 'dokan_verification_status', true ), 'approved' );
    }
}
