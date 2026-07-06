<?php

namespace WeDevs\DokanPro\Dashboard\ManualOrders;

use WeDevs\Dokan\Vendor\Vendor;

/**
 * Class OrderAttribution
 *
 * Handles order attribution functionality for manual orders
 *
 * @since 4.0.0
 */
class OrderAttribution {
    /**
     * Constructor.
     *
     * Sets up hooks for order attribution.
     */
    public function __construct() {
        add_filter( 'wc_order_attribution_origin_formatted_source', [ $this, 'order_attribution_formatted_source' ] );
    }

    /**
     * Filters the formatted source for order attribution.
     *
     * @since 4.0.0
     *
     * @param string $formatted_source The formatted source.
     *
     * @return string Modified formatted source.
     */
    public function order_attribution_formatted_source( string $formatted_source ): string {
        $order = wc_get_order();
        if ( ! $order instanceof \WC_Order ) {
            return $formatted_source;
        }

        $source_type = strtolower( $order->get_meta( '_wc_order_attribution_source_type', true ) );
        if ( 'vendor' === $source_type && $order->meta_exists( '_dokan_vendor_id' ) ) {
            $vendor_id = $order->get_meta( '_dokan_vendor_id', true );
            $vendor    = dokan()->vendor->get( $vendor_id );

            if ( $vendor instanceof Vendor ) {
                $name = $vendor->get_shop_name();

                return empty( $name ) ? esc_html__( '(No Name)', 'dokan' ) : $name;
            }
        }

        return $formatted_source;
    }
}
