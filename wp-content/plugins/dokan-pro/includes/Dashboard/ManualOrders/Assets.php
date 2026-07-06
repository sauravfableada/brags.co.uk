<?php

namespace WeDevs\DokanPro\Dashboard\ManualOrders;

/**
 * Class Assets
 *
 * Handles scripts and styles for manual orders functionality
 *
 * @since 4.0.0
 */
class Assets {
    /**
     * Constructor.
     *
     * Sets up hooks for scripts.
     */
    public function __construct() {
        add_action( 'dokan_register_scripts', [ $this, 'register_scripts' ] );
        add_action( 'dokan_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

        add_action( 'dokan_order_status_filter_before', [ $this, 'add_edit_order_button' ] );
    }

    /**
     * Registers scripts and styles for the manual order functionality.
     *
     * @since 4.0.0
     *
     * @return void
     */
    public function register_scripts() {
        $script_assets_path = DOKAN_PRO_DIR . '/assets/js/dokan-manual-order.asset.php';
        if ( ! file_exists( $script_assets_path ) ) {
            return;
        }

        $vendor_asset = require $script_assets_path;
        $dependencies = $vendor_asset['dependencies'] ?? [];
        $version      = $vendor_asset['version'] ?? '';

        wp_register_style(
            'dokan-manual-order',
            DOKAN_PRO_PLUGIN_ASSEST . '/js/dokan-manual-order.css',
            [ 'dokan-react-components' ],
            $version
        );

        wp_register_script(
            'dokan-manual-order',
            DOKAN_PRO_PLUGIN_ASSEST . '/js/dokan-manual-order.js',
            array_merge( $dependencies, [ 'dokan-react-frontend', 'dokan-react-components', 'dokan-utilities', 'moment' ] ),
            $version,
            true
        );

        // Add localization for the script.
        wp_set_script_translations( 'dokan-manual-order', 'dokan' );

        $hide_customer  = 'on' === dokan_get_option( 'hide_customer_info', 'dokan_selling', 'off' );
        $order_statuses = wc_get_order_statuses();

        // Format order statuses for frontend use
        $manual_order_statuses = array_map(
            function ( $status ) use ( $order_statuses ) {
                return [
                    'value' => str_replace( 'wc-', '', $status ),
                    'label' => $order_statuses[ $status ],
                ];
            },
            array_keys( $order_statuses )
        );

        /**
         * Filter the manual order statuses
         *
         * @since 4.0.0
         *
         * @param array $manual_order_statuses Order statuses
         */
        $manual_order_statuses = apply_filters( 'dokan_manual_order_statuses', $manual_order_statuses );

        // Add manual order data to window object
        wp_add_inline_script(
            'dokan-manual-order',
            'window.dokanManualOrder = ' . wp_json_encode(
                [
					'coupon_enabled'     => wc_coupons_enabled(),
					'tax_enabled'        => wc_tax_enabled(),
                    'shipping_enabled'   => wc_shipping_enabled(),
					'orders_page_url'    => dokan_get_navigation_url( 'orders' ),
					'order_statuses'     => $manual_order_statuses,
					'hide_customer_info' => $hide_customer,
				]
            ),
            'before'
		);
    }

    /**
     * Enqueues scripts and styles for the manual order functionality.
     *
     * @since 4.0.0
     *
     * @return void
     */
    public function enqueue_scripts() {
        if ( ! dokan_is_seller_dashboard() ) {
            return;
        }

        wp_enqueue_style( 'dokan-manual-order' );
        wp_enqueue_script( 'dokan-manual-order' );
    }

    /**
     * Add edit order button to the order status filter.
     *
     * @since 4.0.0
     *
     * @param int $order_id Order ID.
     *
     * @return void
     */
    public function add_edit_order_button( $order_id ) {
        if ( ! $order_id ) {
            return;
        }

        $can_edit  = false;
        $vendor_id = dokan_get_current_user_id();

        // Check if order creation enabled by vendor.
        if ( dokan_pro()->manual_orders->is_enabled_for_vendor( $vendor_id ) ) {
            $order = wc_get_order( $order_id );
            if ( $order instanceof \WC_Order ) {
                $can_edit = dokan_pro()->manual_orders->is_created_by_vendor( $order, $vendor_id ) && $order->is_editable();
            }
        }

        if ( $can_edit ) {
            printf(
                '<a href="%s" class="dokan-btn dokan-btn-theme dokan-btn-sm dokan-right dokan-edit-order-button">%s</a>',
                esc_url( dokan_get_navigation_url( 'new/#/orders/edit/' . $order_id ) ),
                esc_html__( 'Edit Order', 'dokan' )
            );
        }
    }
}
