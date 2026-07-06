<?php
namespace WeDevs\DokanPro\Modules\VendorVerification;

use WeDevs\Dokan\Cache as DokanCache;
use WeDevs\DokanPro\Modules\VendorVerification\Models\VerificationMethod;
use WeDevs\DokanPro\Modules\VendorVerification\Models\VerificationRequest;

/**
 * Vendor Verification Cache class.
 *
 * Manage all caches related to vendor verifications.
 *
 * @since 3.4.2
 *
 * @see \WeDevs\Dokan\Cache
 */
class Cache {

    /**
     * Class Constructor.
     */
    public function __construct() {
        add_action( 'dokan_after_address_verification_added', [ $this, 'clear_vendor_verification_cache' ], 10 );
        add_action( 'dokan_verification_summitted', [ $this, 'clear_vendor_verification_cache' ], 10 );
        add_action( 'dokan_verification_status_change', [ $this, 'clear_vendor_verification_cache' ], 10 );
        add_action( 'dokan_id_verification_cancelled', [ $this, 'clear_vendor_verification_cache' ], 10 );
        add_action( 'dokan_address_verification_cancel', [ $this, 'clear_vendor_verification_cache' ], 10 );
        add_action( 'dokan_company_verification_submitted', [ $this, 'clear_vendor_verification_cache' ], 10 );
        add_action( 'dokan_company_verification_cancelled', [ $this, 'clear_vendor_verification_cache' ], 10 );
        add_action( 'dokan_pro_vendor_verification_method_created', [ $this, 'clear_vendor_verification_method_cache' ] );
        add_action( 'dokan_pro_vendor_verification_method_updated', [ $this, 'clear_vendor_verification_method_cache' ] );
        add_action( 'dokan_pro_vendor_verification_method_deleted', [ $this, 'clear_vendor_verification_method_cache' ] );
        add_action( 'dokan_pro_vendor_verification_method_deleted', [ $this, 'clear_vendor_verification_request_cache' ] );
        add_action( 'dokan_pro_vendor_verification_request_created', [ $this, 'clear_vendor_verification_request_cache' ] );
        add_action( 'dokan_pro_vendor_verification_request_updated', [ $this, 'clear_vendor_verification_request_cache' ] );
        add_action( 'dokan_pro_vendor_verification_request_deleted', [ $this, 'clear_vendor_verification_request_cache' ] );
    }

    /**
     * Clear Vendor Verification caches.
     *
     * @since 3.4.2
     *
     * @param int $seller_id
     *
     * @return void
     */
    public function clear_vendor_verification_cache( $seller_id = null ) {
        DokanCache::invalidate_group( 'verifications' );
    }

    /**
     * Clear Vendor Verification methods caches.
     *
     * @since 3.11.1
     *
     * @param int $id Document type identifier.
     *
     * @return void
     */
    public function clear_vendor_verification_method_cache( $id = 0 ) {
        $cache_group = ( new VerificationMethod() )->get_cache_group() . '_query';
        DokanCache::invalidate_group( $cache_group );
    }

    /**
     * Clear Vendor Verification requests caches.
     *
     * @since 3.11.1
     *
     * @return void
     */
    public function clear_vendor_verification_request_cache() {
        $cache_group = ( new VerificationRequest() )->get_cache_group() . '_query';
        DokanCache::invalidate_group( $cache_group );
    }
}
