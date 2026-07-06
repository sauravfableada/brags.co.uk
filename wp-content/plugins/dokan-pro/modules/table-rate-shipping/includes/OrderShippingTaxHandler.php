<?php

namespace WeDevs\DokanPro\Modules\TableRate;

use Automattic\WooCommerce\Enums\ProductTaxStatus;
use WC_Shipping_Zones;
use WC_Tax;

/**
 * @since 4.0.4
 * 
 * Override the tax calculation for the shipping item if the shipping method is a table rate shipping method.
 * It will use the tax status of the vendor to calculate the taxes.
 */
class OrderShippingTaxHandler {

    /**
     * Constructor.
     *
     * @since 4.0.4
     */
    public function __construct() {
        // Hook into the action to calculate taxes for shipping items.
        add_action( 'woocommerce_order_item_shipping_after_calculate_taxes', array( $this, 'calculate_taxes' ), 10, 2 );
    }

    /**
     * Calculate item taxes.
     *
     * @since 4.0.4
     * 
     * @param \WC_Order_Item_Shipping $item Shipping item to calculate taxes for.
     * @param array $calculate_tax_for Location data to get taxes for. Required.
     * @return bool True if taxes were calculated.
     */
    public function calculate_taxes( $item, $calculate_tax_for = array() ) {
        if ( ! isset( $calculate_tax_for['country'], $calculate_tax_for['state'], $calculate_tax_for['postcode'], $calculate_tax_for['city'], $calculate_tax_for['tax_class'] ) ) {
            return false;
        }

        if ( wc_tax_enabled() && ProductTaxStatus::TAXABLE === $this->get_tax_status( $item ) ) {
            $tax_rates = WC_Tax::find_shipping_rates( $calculate_tax_for );
            $taxes     = WC_Tax::calc_tax( $item->get_total(), $tax_rates, false );
            $item->set_taxes( array( 'total' => $taxes ) );
        } else {
            $item->set_taxes( false );
        }

        return true;
    }

    /**
     * Get the tax status for the shipping item.
     *
     * @since 4.0.4
     * 
     * @param \WC_Order_Item_Shipping $item Shipping item to get tax status for.
     * @return string Tax status.
     */
    protected function get_tax_status( $item ) {
        $vendor_id = $item->get_meta( 'seller_id' );

        $shipping_method = WC_Shipping_Zones::get_shipping_method( $item->get_instance_id() );

        $zone = WC_Shipping_Zones::get_zone_by( 'instance_id', $item->get_instance_id() );

        if ($shipping_method instanceof Method) {
            return $shipping_method->get_tax_status_by_vendor( $vendor_id, $zone->get_id() );
        }

        return $item->get_tax_status();
    }
}