<?php

namespace WeDevs\DokanPro\Dashboard\ManualOrders;

use WC_Order;
use WeDevs\Dokan\Traits\ChainableContainer;

/**
 * Class Manager
 *
 * Manager class for Manual Orders functionality
 *
 * @since 4.0.0
 *
 * @property Settings         $settings          Instance of Settings class.
 * @property OrderAttribution $order_attribution Instance of OrderAttribution class.
 * @property Capabilities     $capabilities      Instance of Capabilities class.
 * @property Assets           $assets            Instance of Assets class when feature is enabled.
 * @property Menus            $menus             Instance of Menus class when feature is enabled.
 */
class Manager {

    use ChainableContainer;

    /**
     * Constructor.
     *
     * Sets up component initialization.
     */
    public function __construct() {
        $this->init_components();
    }

    /**
     * Initialize all component classes
     *
     * @since 4.0.0
     *
     * @return void
     */
    private function init_components() {
        // Initialize required base components first
        $this->container['settings']          = new Settings();
        $this->container['order_attribution'] = new OrderAttribution();
        $this->container['capabilities']      = new Capabilities();

        // Load feature components only if enabled for current vendor
        if ( $this->is_enabled_for_vendor( dokan_get_current_user_id() ) ) {
            $this->container['assets'] = new Assets();
            $this->container['menus']  = new Menus();
        }

        /**
         * Action hook to initialize custom components
         *
         * @since 4.0.0
         *
         * @param Manager $this Current instance of the Manager class
         */
        do_action( 'dokan_manual_orders_init', $this );
    }

    /**
     * Check if manual orders are enabled for a specific vendor
     *
     * @since 4.0.0
     *
     * @param int $vendor_id Vendor ID.
     *
     * @return boolean
     */
    public function is_enabled_for_vendor( int $vendor_id ): bool {
        $vendor = dokan()->vendor->get( $vendor_id );

        if ( ! $vendor ) {
            return false;
        }

        // Get vendor capability from user meta, fallback to global setting if not set
        $capability = $vendor->get_meta( $this->settings->get_meta_key(), true );

        // If capability value exists, use it; otherwise fallback to global setting
        if ( '' !== $capability ) {
            $is_enabled = wc_string_to_bool( $capability );
        } else {
            $is_enabled = $this->is_enabled_globally();
        }

        /**
         * Filter to modify if manual orders are enabled for a vendor
         *
         * @since 4.0.0
         *
         * @param boolean $is_enabled Whether manual orders are enabled for the vendor
         * @param int     $vendor_id  Vendor ID
         * @param Manager $manager    Current instance of the Manager class
         */
        return apply_filters( 'dokan_manual_orders_is_enabled', $is_enabled, $vendor_id, $this );
    }

    /**
     * Check if manual orders feature is globally enabled
     *
     * @since 4.0.0
     *
     * @return boolean
     */
    public function is_enabled_globally(): bool {
        $is_enabled = 'on' === dokan_get_option( 'allow_vendor_create_manual_order', 'dokan_selling', 'off' );

        /**
         * Filter to modify if manual orders are globally enabled
         *
         * @since 4.0.0
         *
         * @param boolean $is_enabled Whether manual orders are globally enabled
         */
        return apply_filters( 'dokan_manual_orders_enabled_globally', $is_enabled );
    }

    /**
     * Check if the order is created by the vendor
     *
     * @since 4.0.0
     *
     * @param WC_Order $order     Order object.
     * @param int      $vendor_id Vendor ID.
     *
     * @return boolean
     */
    public function is_created_by_vendor( WC_Order $order, int $vendor_id ): bool {
        $order_source_type = strtolower( $order->get_meta( '_wc_order_attribution_source_type', true ) );
        $order_vendor_id   = absint( $order->get_meta( '_dokan_vendor_id', true ) );

        // Check if the order is attributed to a vendor and the current user is the vendor
        return ( 'vendor' === $order_source_type ) && ( $order_vendor_id === $vendor_id );
    }
}
