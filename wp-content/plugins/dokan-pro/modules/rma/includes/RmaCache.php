<?php

namespace WeDevs\DokanPro\Modules\RMA;

use WeDevs\Dokan\Cache;

/**
 * RMA Cache class.
 *
 * Manage all caches for RMA module.
 *
 * @since 3.4.2
 *
 * @see \WeDevs\Dokan\Cache
 */
class RmaCache {

    public function __construct() {
        add_action( 'dokan_rma_save_warranty_request', [ $this, 'clear_rma_cache' ] );
        add_action( 'dokan_warranty_request_updated', [ $this, 'clear_rma_cache' ] );
        add_action( 'dokan_warranty_request_updated_status', [ $this, 'clear_rma_cache' ] );
        add_action( 'dokan_warranty_request_deleted', [ $this, 'clear_rma_cache' ] );
    }

    /**
     * Clear RMA caches.
     *
     * @since 3.4.2
     *
     * @return void
     */
    public static function clear_rma_cache() {
        Cache::invalidate_group( 'rma' );
    }
}
