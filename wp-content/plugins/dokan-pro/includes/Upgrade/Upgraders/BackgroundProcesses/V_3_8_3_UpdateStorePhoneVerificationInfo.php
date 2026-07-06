<?php

namespace WeDevs\DokanPro\Upgrade\Upgraders\BackgroundProcesses;

use WeDevs\Dokan\Abstracts\DokanBackgroundProcesses;

/**
 * Dokan Store Phone Verification Info Updater Class.
 *
 * @since 3.8.3
 */
class V_3_8_3_UpdateStorePhoneVerificationInfo extends DokanBackgroundProcesses {

    /**
     * Perform Updates.
     *
     * @since 3.8.3
     *
     * @param mixed $item
     *
     * @return mixed
     */
    public function task( $item ) {
        if ( empty( $item ) ) {
            return false;
        }

        if ( 'store_phone_verification_info' === $item['updating'] ) {
            return $this->update_store_phone_verification_info( $item['paged'] );
        }

        return false;
    }

    /**
     * Update Store Phone Verification Info.
     *
     * @since 3.8.3
     *
     * @return array|bool
     */
    private function update_store_phone_verification_info( $paged ) {
        $limit = 50;
        $count = $limit * $paged;

        $query_args = [
            'number' => $limit,
            'offset' => $count,
        ];

        $vendors = dokan()->vendor->all( $query_args );

        if ( ! $vendors ) {
            return false;
        }

        foreach ( $vendors as $vendor ) {
            $vendor_profile = $vendor->get_shop_info();

            // If no phone number found for the vendor.
            if ( empty( $vendor_profile['dokan_verification']['info']['phone_no'] ) ) {
                continue;
            }

            // If phone number is not verified.
            if ( empty( $vendor_profile['dokan_verification']['info']['phone_status'] ) || 'verified' !== $vendor_profile['dokan_verification']['info']['phone_status'] ) {
                continue;
            }

            $vendor_profile['dokan_verification']['verified_info']['phone'] = $vendor_profile['dokan_verification']['info']['phone_no'];

            // Update phone number to the verified info.
            update_user_meta( $vendor->id, 'dokan_profile_settings', $vendor_profile );
        }

        return [
            'updating' => 'store_phone_verification_info',
            'paged'    => ++$paged,
        ];
    }
}
