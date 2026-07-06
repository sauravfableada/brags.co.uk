<?php

namespace WeDevs\DokanPro\BackgroundProcess;

defined( 'ABSPATH' ) || exit;

use Wedevs\Dokan\Traits\ChainableContainer;

/**
 * Background Process Manager Class.
 *
 * @since 3.9.3
 *
 * @property SyncVendorZoneData        $sync_vendor_zone_data       Instance of WeDevs\DokanPro\BackgroundProcess\SyncVendorZoneData class
 * @property RegenerateOrderCommission $regenerate_order_commission Instance of WeDevs\DokanPro\BackgroundProcess\RegenerateOrderCommission class
 */
class Manager {

    use ChainableContainer;

    /**
     * Class constructor.
     *
     * @since 3.9.3
     */
    public function __construct() {
        $this->init_classes();
        $this->init_hooks();
    }

    /**
     * Initialize classes to chainable container.
     *
     * @since 3.9.3
     *
     * @return void
     */
    public function init_classes() {
        $this->container['sync_vendor_zone_data']       = new SyncVendorZoneData();
        $this->container['regenerate_order_commission'] = new RegenerateOrderCommission();

        $this->container = apply_filters( 'dokan_pro_background_process_container', $this->container );
    }

    /**
     * Initialize hooks.
     *
     * @since 3.9.3
     *
     * @return void
     */
    public function init_hooks() {
        add_filter( 'dokan_admin_notices', [ $this, 'show_regenerate_order_commission_updated_notice' ], 10, 1 );
    }

    /**
     * Show variable products author updated notice.
     *
     * @since 3.9.3
     *
     * @param array $notices
     *
     * @return array $notices
     */
    public function show_regenerate_order_commission_updated_notice( $notices ) {
        if ( empty( get_transient( 'dokan_regenerate_order_commission_updated' ) ) ) {
            return $notices;
        }

        // Remove the cache for showing the notice only once.
        delete_transient( 'dokan_regenerate_order_commission_updated' );

        $notices[] = [
            'type'        => 'success',
            'title'       => __( 'Order Commission Regenerated', 'dokan' ),
            'description' => __( 'Order commissions have been successfully regenerated.', 'dokan' ),
            'priority'    => 0,
        ];

        return $notices;
    }
}
