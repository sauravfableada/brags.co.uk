<?php

namespace WeDevs\DokanPro\Modules\DeliveryTime\Blocks\CartCheckoutBlockSupport;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use WC_Order;
use WC_Shipping_Rate;
use WeDevs\DokanPro\Modules\DeliveryTime\StorePickup\Helper as StorePickupHelper;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit();

/**
 * Excludes a vendor's shipping charges when the customer selects "Store
 * Pickup" for that vendor via the Dokan Delivery Time widget, in both block
 * and classic checkout.
 *
 * The per-vendor delivery type selection reaches the server differently per
 * checkout: the block syncs it through the Store API `cart/extensions` update
 * callback into the WooCommerce session, while classic checkout posts it with
 * the checkout form (directly on order placement, or inside `post_data` on the
 * `update_order_review` AJAX). In either case the vendor's shipping package
 * rates are replaced with a single zero-cost "Store Pickup" rate so the order
 * total reflects only vendors with "Delivery" selected.
 *
 * @since 5.0.5
 */
class StorePickupShipping {

    /**
     * WC session key holding the vendor ids with "Store Pickup" selected.
     *
     * @var string
     */
    const SESSION_KEY = 'dokan_delivery_time_block_pickup_vendors';

    /**
     * Method id used for the zero-cost replacement shipping rate.
     *
     * @var string
     */
    const METHOD_ID = 'dokan_store_pickup';

    /**
     * Class constructor.
     *
     * @since 5.0.5
     */
    public function __construct() {
        add_action( 'woocommerce_blocks_loaded', [ $this, 'register_update_callback' ] );
        add_filter( 'woocommerce_cart_shipping_packages', [ $this, 'flag_pickup_packages' ], 20 );
        add_filter( 'woocommerce_package_rates', [ $this, 'replace_pickup_package_rates' ], 100, 2 );
        add_action( 'woocommerce_store_api_checkout_update_order_from_request', [ $this, 'validate_pickup_selections' ], 5, 2 );
        add_action( 'woocommerce_cart_emptied', [ $this, 'clear_pickup_selections' ] );
        add_action( 'woocommerce_before_checkout_form', [ $this, 'clear_pickup_selections' ] );
    }

    /**
     * Registers the Store API update callback used by the checkout block to
     * sync the per-vendor delivery type selection into the WC session.
     *
     * @since 5.0.5
     *
     * @return void
     */
    public function register_update_callback() {
        woocommerce_store_api_register_update_callback(
            [
                'namespace' => 'dokan_delivery_time',
                'callback'  => [ $this, 'update_pickup_selections' ],
            ]
        );
    }

    /**
     * Stores the vendor ids with "Store Pickup" selected into the WC session.
     *
     * Only vendors that actually have store pickup locations enabled are
     * accepted, so shipping cannot be zeroed out for arbitrary vendors.
     *
     * @since 5.0.5
     *
     * @param array $data Update callback data: [ 'vendor_delivery_types' => [ vendor_id => 'delivery'|'pickup' ] ].
     *
     * @return void
     */
    public function update_pickup_selections( $data ) {
        if ( ! WC()->session ) { // @phpstan-ignore-line -- session is null until initialized.
            return;
        }

        $delivery_types = isset( $data['vendor_delivery_types'] ) && is_array( $data['vendor_delivery_types'] ) ? $data['vendor_delivery_types'] : [];

        WC()->session->set( self::SESSION_KEY, $this->filter_pickup_vendor_ids( $delivery_types ) );
    }

    /**
     * Flags the shipping packages of vendors with "Store Pickup" selected.
     *
     * Pickup vendors come from the WC session on block (Store API) requests and
     * from the posted checkout data on classic requests. A changed flag busts
     * WooCommerce's per-package shipping rate session cache, forcing a live
     * recalculation on toggle.
     *
     * @since 5.0.5
     *
     * @param array $packages Cart shipping packages, already split per vendor by Dokan.
     *
     * @return array
     */
    public function flag_pickup_packages( $packages ) {
        if ( ! is_array( $packages ) ) {
            return $packages;
        }

        $pickup_vendors = $this->get_request_pickup_vendor_ids();

        if ( empty( $pickup_vendors ) ) {
            return $packages;
        }

        foreach ( $packages as $key => $package ) {
            /**
             * Filters whether a shipping package is skipped from store pickup exclusion.
             *
             * Lets fulfillment integrations that ship their own packages (e.g. Printful)
             * opt those packages out of the store pickup shipping exclusion.
             *
             * @since 5.0.5
             *
             * @param bool  $skip    Whether to skip this package. Default false.
             * @param array $package The shipping package.
             */
            if ( apply_filters( 'dokan_delivery_time_skip_pickup_package', false, $package ) ) {
                continue;
            }

            $seller_id = ! empty( $package['seller_id'] ) ? absint( $package['seller_id'] ) : 0;

            if ( $seller_id && in_array( $seller_id, $pickup_vendors, true ) ) {
                $packages[ $key ]['dokan_store_pickup_selected'] = true;
                // ship_via points at an unregistered method id so WooCommerce skips rate calculation for this package.
                $packages[ $key ]['ship_via'] = [ self::METHOD_ID ];
            }
        }

        return $packages;
    }

    /**
     * Gets the vendor ids with "Store Pickup" selected for the current request.
     *
     * Block (Store API) requests read the selection synced into the WC session;
     * classic checkout requests read it from the posted checkout data.
     *
     * @since 5.0.5
     *
     * @return int[]
     */
    protected function get_request_pickup_vendor_ids() {
        if ( $this->is_store_api_request() ) {
            return self::get_pickup_vendor_ids();
        }

        return $this->get_classic_pickup_vendor_ids();
    }

    /**
     * Filters a vendor delivery-type map down to the vendors that selected an
     * active store pickup, so shipping is never zeroed for arbitrary vendors.
     *
     * @since 5.0.5
     *
     * @param array $delivery_types Map of vendor id => selected delivery type.
     *
     * @return int[]
     */
    protected function filter_pickup_vendor_ids( array $delivery_types ) {
        $pickup_vendors = [];

        foreach ( $delivery_types as $vendor_id => $delivery_type ) {
            $vendor_id     = absint( $vendor_id );
            $delivery_type = is_string( $delivery_type ) ? sanitize_text_field( $delivery_type ) : '';

            // A vendor is a pickup vendor only when it chose pickup and has store pickup enabled.
            if ( ! $vendor_id || ! in_array( $delivery_type, [ 'pickup', 'store-pickup' ], true ) ) {
                continue;
            }

            if ( ! StorePickupHelper::is_store_pickup_location_active( $vendor_id ) ) {
                continue;
            }

            $pickup_vendors[] = $vendor_id;
        }

        return array_values( array_unique( $pickup_vendors ) );
    }

    /**
     * Gets the vendor ids with "Store Pickup" selected from a classic checkout request.
     *
     * The delivery type selection is posted with the checkout form: directly in
     * `vendor_delivery_time` on order placement, or inside the serialized
     * `post_data` on the `update_order_review` AJAX.
     *
     * @since 5.0.5
     *
     * @return int[]
     */
    protected function get_classic_pickup_vendor_ids() {
        $posted = [];

        // phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce verifies the request nonce before shipping is calculated.
        if ( isset( $_POST['vendor_delivery_time'] ) && is_array( $_POST['vendor_delivery_time'] ) ) {
            $posted = wc_clean( wp_unslash( $_POST['vendor_delivery_time'] ) );
        } elseif ( isset( $_POST['post_data'] ) ) {
            // The update_order_review AJAX serializes the form into post_data; parse the raw string so the percent-encoded keys survive.
            parse_str( wp_unslash( $_POST['post_data'] ), $parsed ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- raw parse keeps the percent-encoded keys; values sanitized per field below.
            $posted = isset( $parsed['vendor_delivery_time'] ) && is_array( $parsed['vendor_delivery_time'] ) ? wc_clean( $parsed['vendor_delivery_time'] ) : [];
        }
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        $delivery_types = [];

        // The classic checkout posts one entry per vendor; reduce it to a vendor id => type map.
        foreach ( $posted as $delivery_time ) {
            if ( is_array( $delivery_time ) && isset( $delivery_time['vendor_id'], $delivery_time['selected_delivery_type'] ) ) {
                $delivery_types[ $delivery_time['vendor_id'] ] = $delivery_time['selected_delivery_type'];
            }
        }

        return $this->filter_pickup_vendor_ids( $delivery_types );
    }

    /**
     * Replaces a flagged package's shipping rates with a zero-cost "Store Pickup" rate.
     *
     * A replacement rate (instead of an empty rate list) keeps the checkout
     * placeable and shows "Store Pickup" among the selected shipping methods
     * in the order summary.
     *
     * @since 5.0.5
     *
     * @param array $rates   Calculated shipping rates for the package.
     * @param array $package The shipping package.
     *
     * @return array
     */
    public function replace_pickup_package_rates( $rates, $package ) {
        if ( empty( $package['dokan_store_pickup_selected'] ) || empty( $package['seller_id'] ) ) {
            return $rates;
        }

        $seller_id = absint( $package['seller_id'] );
        $rate_id   = self::METHOD_ID . ':' . $seller_id;

        $pickup_rate = new WC_Shipping_Rate(
            $rate_id,
            StorePickupHelper::get_formatted_delivery_type( 'store-pickup' ),
            0,
            [],
            self::METHOD_ID,
            $seller_id
        );

        /**
         * Filters the zero-cost rate replacing a vendor's shipping rates when
         * the customer selects store pickup for that vendor in block or classic
         * checkout.
         *
         * @since 5.0.5
         *
         * @param WC_Shipping_Rate $pickup_rate The replacement rate.
         * @param array            $package     The vendor's shipping package.
         * @param array            $rates       The original shipping rates.
         */
        $pickup_rate = apply_filters( 'dokan_delivery_time_store_pickup_shipping_rate', $pickup_rate, $package, $rates );

        return [ $rate_id => $pickup_rate ];
    }

    /**
     * Blocks checkout when a vendor's shipping was zeroed out for store
     * pickup but the submitted delivery types don't confirm that selection.
     *
     * The order's shipping lines are the source of truth for which vendors
     * got the zero-cost pickup rate, so stale sessions, removed cart items or
     * vendors without shipping packages can never block checkout falsely.
     *
     * @since 5.0.5
     *
     * @param WC_Order        $order   Order being processed.
     * @param WP_REST_Request $request Checkout request.
     *
     * @throws RouteException When a pickup-rated vendor is submitted without a pickup delivery type.
     *
     * @return void
     */
    public function validate_pickup_selections( $order, $request ) {
        if ( ! $order instanceof WC_Order || $order->get_parent_id() ) {
            return;
        }

        // Only the final order placement (POST) confirms pickup selections; draft syncs (PUT) carry no extension data.
        if ( 'POST' !== $request->get_method() ) {
            return;
        }

        // Collect the vendors whose shipping the order actually replaced with the pickup rate.
        $pickup_rated_vendors = [];

        foreach ( $order->get_shipping_methods() as $shipping_item ) {
            if ( self::METHOD_ID !== $shipping_item->get_method_id() ) {
                continue;
            }

            $seller_id = absint( $shipping_item->get_meta( 'seller_id' ) );

            if ( $seller_id ) {
                $pickup_rated_vendors[ $seller_id ] = true;
            }
        }

        if ( empty( $pickup_rated_vendors ) ) {
            return;
        }

        // Read the delivery types the customer submitted with this order.
        $extension = $request['extensions']['dokan_delivery_time']['vendor_delivery_time'] ?? [];
        $submitted = is_array( $extension ) ? $extension :[];

        // Clear every vendor that confirmed pickup; any vendor left was rated for pickup without choosing it.
        foreach ( $submitted as $delivery_time ) {
            $vendor_id     = isset( $delivery_time['vendor_id'] ) ? absint( $delivery_time['vendor_id'] ) : 0;
            $delivery_type = isset( $delivery_time['selected_delivery_type'] ) ? $delivery_time['selected_delivery_type'] : '';
            $chose_pickup  = in_array( $delivery_type, [ 'pickup', 'store-pickup' ], true );

            if ( $vendor_id && $chose_pickup ) {
                unset( $pickup_rated_vendors[ $vendor_id ] );
            }
        }

        if ( empty( $pickup_rated_vendors ) ) {
            return;
        }

        throw new RouteException(
            'dokan_delivery_time_pickup_mismatch',
            esc_html__( 'Your delivery type selection has changed. Please review your delivery options and try placing the order again.', 'dokan' ),
            400
        );
    }

    /**
     * Clears the pickup selections from the WC session.
     *
     * Runs after checkout empties the cart and on classic checkout render so
     * a stale block checkout selection can never leak into other flows.
     *
     * @since 5.0.5
     *
     * @return void
     */
    public function clear_pickup_selections() {
        if ( WC()->session ) { // @phpstan-ignore-line -- session is null until initialized.
            WC()->session->__unset( self::SESSION_KEY );
        }
    }

    /**
     * Gets the vendor ids with "Store Pickup" selected from the WC session.
     *
     * @since 5.0.5
     *
     * @return int[]
     */
    public static function get_pickup_vendor_ids() {
        if ( ! WC()->session ) { // @phpstan-ignore-line -- session is null until initialized.
            return [];
        }

        $vendor_ids = WC()->session->get( self::SESSION_KEY, [] );

        if ( ! is_array( $vendor_ids ) ) {
            return [];
        }

        return array_values( wp_parse_id_list( $vendor_ids ) );
    }

    /**
     * Whether the current request is a Store API request.
     *
     * Mirrors WC()->is_store_api_request(), which only exists since
     * WooCommerce 9.0 while Dokan Pro still supports 8.5 — switch to the core
     * method once the minimum WooCommerce version reaches 9.0. Additionally
     * matches plain-permalink requests (?rest_route=/wc/store/...).
     *
     * @since 5.0.5
     *
     * @return bool
     */
    protected function is_store_api_request() {
        if ( empty( $_SERVER['REQUEST_URI'] ) ) {
            return false;
        }

        $request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );

        if ( false !== strpos( $request_uri, trailingslashit( rest_get_url_prefix() ) . 'wc/store/' ) ) {
            return true;
        }

        return false !== strpos( $request_uri, 'rest_route=' ) && false !== strpos( $request_uri, '/wc/store/' );
    }
}
