<?php

namespace WeDevs\DokanPro\Modules\Printful\Providers;

use WC_Shipping;
use WC_Shipping_Rate;
use WeDevs\DokanPro\Modules\Printful\Processors\PrintfulOrderProcessor;
use WeDevs\DokanPro\Modules\Printful\Shipping\PrintfulShippingMethod;
use WeDevs\DokanPro\Modules\Printful\Shipping\PrintfulShippingPackageSplitter;

/**
 * Shipping service provider.
 *
 * @since 3.13.0
 */
class ShippingProvider {

    /**
     * Constructor.
     *
     * @since 3.13.0
     */
    public function __construct() {
        add_filter( 'dokan_cart_shipping_packages', [ $this, 'split_packages_for_pod_services' ] );
        add_action( 'woocommerce_load_shipping_methods', [ $this, 'load_shipping_method' ] );
        add_filter( 'woocommerce_package_rates', [ $this, 'remove_default_shipping_rates_when_needed' ], 99999, 2 );
        add_filter( 'dokan_delivery_time_skip_pickup_package', [ $this, 'skip_printful_package_from_store_pickup' ], 10, 2 );

        // Handle vendor printful shipment.
        add_filter( 'dokan_localized_args', [ $this, 'add_printful_shipment_overlay_info' ] );
        add_filter( 'dokan_order_after_shippable_product_name', [ $this, 'render_printful_shipment_badge' ] );
        add_filter( 'woocommerce_admin_html_order_item_class', [ $this, 'add_printful_class_to_order_item' ], 10, 2 );
        add_filter( 'dokan_order_validate_shipment_items', [ $this, 'prevent_shipment_creation_for_printful_products' ], 10, 3 );
    }

    /**
     * Renders the Printful shipment badge for a given product.
     *
     * This method checks if the given product or its parent (if the product is a variation)
     * is a Printful product. If it is, a Printful shipment badge is rendered.
     *
     * @since 3.13.0
     *
     * @param \WC_Product $product The product object to check and render the badge for.
     *
     * @return void
     */
    public function render_printful_shipment_badge( $product ) {
        // Check if the product is a variation and get the parent product if necessary.
        $product = $product->get_type() !== 'variation' ? $product : wc_get_product( $product->get_parent_id() );

        // Return early if the product is not a Printful product.
        if ( ! ProductProvider::is_printful_product( $product ) ) {
            return;
        }

        // Context for the Printful badge template.
        $context = [
            'tooltip'     => false,
            'is_printful' => true,
        ];

        dokan_get_template_part( 'printful', 'badge', $context ); // Render Printful badge on shipment.
    }

    /**
     * Split packages for POD services.
     *
     * @since 3.13.0
     *
     * @param array $packages
     *
     * @return array
     */
    public function split_packages_for_pod_services( array $packages ): array {
        $printful_splitter = new PrintfulShippingPackageSplitter();
        $splitter          = apply_filters( 'dokan_pro_printful_shipping_splitter', $printful_splitter );

        return $splitter->split( $packages );
    }

    /**
     * Load shipping method.
     *
     * @since 3.13.0
     *
     * @param array $package Package.
     *
     * @return void
     */
    public function load_shipping_method( $package ) {
        if ( ! $this->is_printful_package( $package ) ) {
            return;
        }

        if ( 'yes' !== get_user_meta( $package['seller_id'], 'dokan_printful_shipping_enabled', true ) ) {
            return;
        }

        WC_Shipping::instance()->shipping_methods['dokan_pro_printful_shipping_method'] = new PrintfulShippingMethod();
    }

    /**
     * Remove default shipping rates when needed.
     *
     * @since 3.13.0
     *
     * @param WC_Shipping_Rate[] $rates Rates.
     * @param array              $package Package.
     *
     * @return array
     */
    public function remove_default_shipping_rates_when_needed( array $rates, array $package ): array {
        if ( ! $this->is_printful_package( $package ) || ! $this->need_remove_vendor_or_admin_shipping_methods( $package ) ) {
            return $rates;
        }

        // We need to check for the Printful method or unset it.
        foreach ( $rates as $id => $data ) {
            if ( false === stripos( $id, 'dokan_printful_shipping' ) ) {
                unset( $rates[ $id ] ); // remove it
            }
        }
        return $rates;
    }

    /**
     * Excludes Printful packages from the Delivery Time store pickup shipping exclusion.
     *
     * Printful ships its own packages, so a vendor's "Store Pickup" selection
     * must never zero out the Printful shipping rate.
     *
     * @since 5.0.5
     *
     * @param bool  $skip    Whether to skip this package from store pickup exclusion.
     * @param array $package Shipping package.
     *
     * @return bool
     */
    public function skip_printful_package_from_store_pickup( bool $skip, array $package ): bool {
        return $skip || $this->is_printful_package( $package );
    }

    /**
     * Check is this package created by printful or not.
     *
     * @since 3.13.0
     *
     * @param array $package Shipping Package.
     *
     * @return bool
     */
    protected function is_printful_package( array $package ): bool {
        return ! empty( $package[ PrintfulShippingPackageSplitter::PACKAGE_KEY ] );
    }

    /**
     * Check if the default shipping method need to be removed.
     *
     * @since 3.13.0
     *
     * @param array $package Shipping Package.
     *
     * @return bool
     */
    protected function need_remove_vendor_or_admin_shipping_methods( array $package ) {
        if ( 'no' === get_user_meta( $package['seller_id'], 'dokan_printful_enable_marketplace_rates', true ) ) {
            return true;
        }

        return false;
    }

    /**
     * Adds Printful shipment overlay information to localized arguments.
     *
     * This method is used to include a localized message in the data array indicating
     * that the shipment for this item will be handled by Printful. It enhances the user
     * experience by providing specific shipment handling information.
     *
     * @since 3.13.0
     *
     * @param array $data The data array to be localized.
     *
     * @return array The modified data array with Printful shipment overlay information.
     */
    public function add_printful_shipment_overlay_info( $data ) {
        // Add localized message to the data array for Printful shipments.
        $data['printful_shipment_overlay_info'] = esc_html__( 'This item will be shipped by Printful.', 'dokan' );

        return $data;
    }

    /**
     * Adds a Printful class to the order item if the product is from Printful.
     *
     * This method appends the class 'printful-product' to the existing class name if
     * the product associated with the order item is identified as a Printful product.
     * This allows for specific styling or handling in the admin interface based on
     * product type.
     *
     * @since 3.13.0
     *
     * @param string          $class_name  The current class name of the order item.
     * @param \WC_Order_Item  $order_item  The order item object containing product information.
     *
     * @return string The modified class name with 'printful-product' appended if applicable.
     */
    public function add_printful_class_to_order_item( $class_name, \WC_Order_Item $order_item ) {
        $variant_id = $order_item->get_meta( PrintfulOrderProcessor::META_KEY_VARIANT_ID, true );

        // Check if the order item is a Printful product.
        if ( ! empty( $variant_id ) ) {
            $class_name .= ' printful-product'; // Append 'printful-product' class if the product is from Printful.
        }

        return $class_name;
    }

    /**
     * Prevents shipment creation if Printful products are found in the order.
     *
     * This method checks the provided list of item IDs against the order to see if any
     * of them are Printful products. If a Printful product is detected, it returns true,
     * which prevents the creation of a shipment for these items. This helps in handling
     * shipments that should be processed differently due to specific product handling
     * requirements by Printful.
     *
     * @since 3.13.0
     *
     * @param bool  $valid     The current validity status of shipment creation.
     * @param int   $order_id  The ID of the order.
     * @param array $item_list List of item IDs to check for Printful products.
     *
     * @return bool Returns true if a Printful product is found, otherwise returns the original validity status.
     */
    public function prevent_shipment_creation_for_printful_products( bool $valid, int $order_id, array $item_list ): bool {
        if ( ! $item_list ) {
            return $valid;
        }

        // Retrieve the order object.
        $order = wc_get_order( $order_id );

        // Iterate through each shipment order item in the list.
        foreach ( $item_list as $item_id ) {
            $order_item = $order->get_item( $item_id );
            $product_id = $order_item['product_id'] ?? 0;
            $product    = dokan()->product->get( $product_id );

            // Check if the product is a Printful product.
            if ( ProductProvider::is_printful_product( $product ) ) {
                return true; // Prevent shipment creation for Printful products.
            }
        }

        return $valid;
    }
}
