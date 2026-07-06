<?php

namespace WeDevs\DokanPro\Modules\PayPalMarketplace\Order;

use WeDevs\DokanPro\Modules\PayPalMarketplace\Helper;
use WeDevs\DokanPro\Modules\PayPalMarketplace\Utilities\Processor;
use WeDevs\Dokan\Cache;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

//todo: send some task to background process

/**
 * Class OrderManager
 * @package WeDevs\Dokan\Gateways\PayPal
 *
 * @since 3.3.0
 * @author weDevs
 */
class OrderManager {

    /**
     * Get currencies that don't support decimals
     * @since 4.0.8
     * @return array
     */
    protected static function no_decimal_currencies(): array {
        $currencies = array(
            'huf', // Hungarian Forint - Zero-digit currency
            'jpy', // Japanese Yen - Zero-digit currency
            'twd', // New Taiwan Dollar - Zero-digit currency
        );

        /**
         * Filter currencies that don't support decimals for PayPal Marketplace
         *
         * @since 4.0.8
         *
         * @param array $currencies Array of currency codes (lowercase) that don't support decimals
         */
        return apply_filters( 'dokan_paypal_no_decimal_currencies', $currencies );
    }

    /**
     * Format amount based on currency
     * @since 4.0.8
     * @param float $amount
     * @param string|null $currency
     * @return string
     */
    protected static function format_amount_for_currency( float $amount, ?string $currency = null ): string {
        if ( ! $currency ) {
            $currency = get_woocommerce_currency();
        }

        $currency = strtolower( $currency );
        $dp = 2;
        // For currencies that don't support decimals, round to whole numbers
        if ( in_array( $currency, self::no_decimal_currencies(), true ) ) {
            $dp = 0;
            $amount = round( $amount );
        }

        // Format the amount with the determined number of decimal places with filters applied
        return apply_filters( 'dokan_paypal_formatted_amount', wc_format_decimal( $amount, $dp ), $amount, $currency, $dp );
    }

    /**
     * This method will check if charged is captured via payment gateway
     *
     * @param \WC_Order $order
     *
     * @return bool
     */
    public static function is_charge_captured( $order ) {
        // check order is instance of WC_Order class
        if ( ! $order instanceof \WC_Order && is_numeric( $order ) ) {
            $order = wc_get_order( $order );
        }

        if ( ! $order ) {
            return false;
        }

        // check if this is suborder
        $order_id = $order->get_parent_id() ? $order->get_parent_id() : $order->get_id();
        $parent_order = wc_get_order( $order_id );
        if ( ! $parent_order ) {
            return false;
        }

        //using get_post_meta is intentional
        return wc_string_to_bool( $parent_order->get_meta( '_dokan_paypal_payment_charge_captured', true ) );
    }

    /**
     * Adjusts precission of items subtotal to match total.
     *
     * @since 4.0.4
     *
     * @param array $purchase_units
     *
     * @return array $purchase_units
     */
    protected static function adjust_purchase_units_subtotal( $purchase_units ) {
        $total = floatval( $purchase_units['amount']['breakdown']['item_total']['value'] );
        $items = $purchase_units['items'];
        $sum_subtotals = 0.0;
        $currency = $purchase_units['amount']['currency_code'];

        // Sum all item values (subtotals)
        foreach ( $items as $item ) {
            $sum_subtotals += round( floatval( $item['unit_amount']['value'] ), 2 );
        }

        $diff = round( $total - $sum_subtotals, 2 );
        if ( $diff === 0.00 ) {
            return $purchase_units;
        }

        // Find first non-zero item to apply the difference
        foreach ( $items as $index => $item ) {
            $value = floatval( $item['unit_amount']['value'] );
            if ( $value > 0.0 ) {
                $adjusted_value = $value + $diff;
                $items[ $index ]['unit_amount']['value'] = static::format_amount_for_currency( $adjusted_value, $currency );
                break;
            }
        }

        $purchase_units['items'] = $items; // update the array if needed
        return $purchase_units;
    }

    /**
     * Make purchase unit data
     *
     * @param \WC_Order $order
     *
     * @see https://developer.paypal.com/docs/api/orders/v2/#definition-purchase_unit_request
     *
     * @return array
     */
    public static function make_purchase_unit_data( \WC_Order $order ) {
        $subtotal       = $order->get_subtotal();
        $subtotal       += $order->get_total_fees();
        $tax_total      = static::get_tax_amount( $order );
        $shipping_total = (float) $order->get_shipping_total();

        $seller_id     = dokan_get_seller_id_by_order( $order->get_id() );
        $merchant_id   = apply_filters( 'dokan_paypal_marketplace_purchase_unit_merchant_id', Helper::get_seller_merchant_id( $seller_id ), $order );
        $platform_fee  = dokan()->commission->get_earning_by_order( $order, 'admin' );

        // Ensure platform fee is not negative
        $platform_fee = max( 0, (float) $platform_fee );

        $product_items = static::get_product_items( $order );

        // calculate discounts
        $total_discount = $order->get_total_discount() + static::get_lot_discount( $order ) + static::get_minimum_order_discount( $order );

        $currency = get_woocommerce_currency();

        $purchase_units = [
            'reference_id'        => $order->get_order_key(),
            'amount'              => [
                'currency_code' => $currency,
                'value'         => static::format_amount_for_currency( (float) $order->get_total(), $currency ),
                'breakdown'     => [
                    'item_total'        => [
                        'currency_code' => $currency,
                        'value'         => static::format_amount_for_currency( (float) $subtotal, $currency ),
                    ],
                    'tax_total'         => [
                        'currency_code' => $currency,
                        'value'         => static::format_amount_for_currency( (float) $tax_total, $currency ),
                    ],
                    'shipping'          => [
                        'currency_code' => $currency,
                        'value'         => static::format_amount_for_currency( $shipping_total, $currency ),
                    ],
                    'handling'          => [
                        'currency_code' => $currency,
                        'value'         => static::format_amount_for_currency( 0.0, $currency ),
                    ],
                    'shipping_discount' => [
                        'currency_code' => $currency,
                        'value'         => static::format_amount_for_currency( 0.0, $currency ),
                    ],
                    'discount' => [
                        'currency_code' => $currency,
                        'value'         => static::format_amount_for_currency( (float) $total_discount, $currency ),
                    ],
                    'insurance'         => [
                        'currency_code' => $currency,
                        'value'         => static::format_amount_for_currency( 0.0, $currency ),
                    ],
                ],
            ],
            'payee'               => [
                'merchant_id' => $merchant_id,
            ],
            'items'               => $product_items,
            'shipping'            => static::get_shipping_address( $order ),
            'payment_instruction' => [
                'disbursement_mode' => Helper::get_disbursement_mode() !== 'INSTANT' ? 'DELAYED' : 'INSTANT',
                'platform_fees'     => [
                    [
                        'amount' => [
                            'currency_code' => $currency,
                            'value'         => static::format_amount_for_currency( (float) $platform_fee, $currency ),
                        ],
                    ],
                ],
            ],
            'invoice_id'          => $order->get_parent_id() ? $order->get_parent_id() : $order->get_id(),
            'custom_id'           => $order->get_id(),
        ];

        // Check if order has negative fees. If yes, remove the line items information.
        if ( self::is_order_has_negative_fees( $order ) ) {
            unset( $purchase_units['items'] );
        }
        $adjusted_purchase_units = self::adjust_purchase_units_subtotal( $purchase_units );

        /**
         * Filter to modify PayPal purchase unit data.
         *
         * @since 4.0.4
         *
         * @param array  $purchase_units Purchase unit data.
         * @param object $order          WooCommerce order object.
         */
        return apply_filters( 'dokan_paypal_make_purchase_unit_data', $adjusted_purchase_units, $purchase_units, $order );
    }
    /**
     * Make vendor subscription purchase unit data
     *
     * @param \WC_Order $order
     *
     * @see https://developer.paypal.com/docs/api/orders/v2/#definition-purchase_unit_request
     *
     * @since 3.3.7
     *
     * @return array
     */

    public static function make_subscription_purchase_unit_data( \WC_Order $order ) {
        $subtotal       = $order->get_subtotal();
        $tax_total      = static::get_tax_amount( $order );

        // calculate discounts
        $total_discount = $order->get_total_discount() + static::get_lot_discount( $order ) + static::get_minimum_order_discount( $order );

        $currency = get_woocommerce_currency();

        $purchase_units = [
            'reference_id'        => $order->get_order_key(),
            'amount'              => [
                'currency_code' => $currency,
                'value'         => static::format_amount_for_currency( (float) $order->get_total(), $currency ),
                'breakdown'     => [
                    'item_total'        => [
                        'currency_code' => $currency,
                        'value'         => static::format_amount_for_currency( (float) $subtotal, $currency ),
                    ],
                    'tax_total'         => [
                        'currency_code' => $currency,
                        'value'         => static::format_amount_for_currency( (float) $tax_total, $currency ),
                    ],
                    'shipping'          => [
                        'currency_code' => $currency,
                        'value'         => static::format_amount_for_currency( 0.0, $currency ),
                    ],
                    'handling'          => [
                        'currency_code' => $currency,
                        'value'         => static::format_amount_for_currency( 0.0, $currency ),
                    ],
                    'shipping_discount' => [
                        'currency_code' => $currency,
                        'value'         => static::format_amount_for_currency( 0.0, $currency ),
                    ],
                    'discount' => [
                        'currency_code' => $currency,
                        'value'         => static::format_amount_for_currency( (float) $total_discount, $currency ),
                    ],
                    'insurance'         => [
                        'currency_code' => $currency,
                        'value'         => static::format_amount_for_currency( 0.0, $currency ),
                    ],
                ],
            ],
            'payee'               => [
                'merchant_id' => Helper::get_partner_id(),
            ],
            'items'               => static::get_product_items( $order ),
            'invoice_id'          => $order->get_parent_id() ? $order->get_parent_id() : $order->get_id(),
            'custom_id'           => $order->get_id(),
        ];

        $adjusted_purchase_units = self::adjust_purchase_units_subtotal( $purchase_units );

        /**
         * Filter to modify PayPal subscription purchase unit data.
         *
         * @since 4.0.4
         *
         * @param array  $purchase_units Purchase unit data.
         * @param object $order          WooCommerce order object.
         */
        return apply_filters( 'dokan_paypal_make_subscription_purchase_unit_data', $adjusted_purchase_units, $purchase_units, $order );
    }

    /**
     * Get shipping address as paypal format
     *
     * @param \WC_Order $order
     *
     * @param bool $payer
     *
     * @return array
     */
    public static function get_shipping_address( \WC_Order $order, $payer = false ) {
        $address = [
            'address' => [
                'name'           => [
                    'given_name' => $order->get_billing_first_name(),
                    'surname'    => $order->get_billing_last_name(),
                ],
                'address_line_1' => $order->get_billing_address_1(),
                'address_line_2' => $order->get_billing_address_2(),
                'admin_area_2'   => $order->get_billing_city(),
                'admin_area_1'   => $order->get_billing_state(),
                'postal_code'    => $order->get_billing_postcode(),
                'country_code'   => $order->get_billing_country(),
            ],
        ];

        if ( $payer ) {
            $address['name'] = [
                'given_name' => $order->get_billing_first_name(),
                'surname'    => $order->get_billing_last_name(),
            ];
        }

        return $address;
    }

    /**
     * Get product items as PayPal format
     *
     * NB: PayPal check all the values with item qty and item value and match with the main order value.
     * So if the shipping and tax fee recipient is admin then
     * we are dividing the tax total and shipping total based on no of items in a order.
     *
     * @param $order
     *
     * @return array
     */
    public static function get_product_items( \WC_Order $order ) {
        $items = [];
        $currency = get_woocommerce_currency();

        foreach ( $order->get_items( 'line_item' ) as $key => $line_item ) {
            $product  = wc_get_product( $line_item->get_product_id() );
            $category = $product->is_downloadable() || $product->is_virtual() || Helper::is_vendor_subscription_product( $product ) ? 'DIGITAL_GOODS' : 'PHYSICAL_GOODS';

            $items[] = [
                'name'        => $line_item->get_name(),
                'sku'         => $product->get_sku(),
                'category'    => $category,
                'unit_amount' => [
                    'currency_code' => $currency,
                    'value'         => static::format_amount_for_currency( (float) $line_item->get_subtotal(), $currency ),
                ],
                'quantity'    => 1,
            ];
        }

        if ( ! empty( $order->get_total_fees() ) ) {
            foreach ( $order->get_fees() as $fee ) {
                $items[] = [
                    'name'        => $fee->get_name(),
                    'unit_amount' => [
                        'currency_code' => $currency,
                        'value'         => static::format_amount_for_currency( (float) $fee->get_total(), $currency ),
                    ],
                    'quantity'    => 1,
                ];
            }
        }

        return $items;
    }

    /**
     * Get tax amount
     *
     * @param $order
     *
     * @return float
     */
    public static function get_tax_amount( \WC_Order $order ) {
        $tax_amount = 0;
        foreach ( $order->get_taxes() as $tax ) {
            $tax_amount += $tax['tax_total'] + $tax['shipping_tax_total'];
        }

        return $tax_amount;
    }

    /**
     * Is order has negative fee applied.
     *
     * @since 3.10.4
     *
     * @param \WC_Order $order Order.
     *
     * @return bool
     */
    public static function is_order_has_negative_fees( \WC_Order $order ): bool {
        foreach ( $order->get_fees() as $fee ) {
            if ( $fee->get_total() < 0 ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get Lot Discount
     *
     * @param \WC_Order $order
     *
     * @since 3.3.0
     *
     * @return float
     */
    public static function get_lot_discount( \WC_Order $order ) {
        return dokan_pro()->vendor_discount->deprecated_methods->get_product_quantity_discount( $order );
    }


    /**
     * Get Minimum Order Discount
     *
     * @param \WC_Order $order
     *
     * @since 3.3.0
     *
     * @return float
     */
    public static function get_minimum_order_discount( \WC_Order $order ) {
        return dokan_pro()->vendor_discount->deprecated_methods->get_order_discount( $order );
    }

    /**
     * @param $purchase_unit array
     * @param $paypal_order_id string
     */
    public static function handle_order_complete_status( &$purchase_units, $paypal_order_id ) {
        $order_id = isset( $purchase_units[0]['invoice_id'] ) ? absint( $purchase_units[0]['invoice_id'] ) : 0; // parent order id
        $order    = wc_get_order( $order_id );

        // return if $order is not instance of WC_Order
        if ( ! $order ) {
            dokan_log( '[Dokan PayPal Marketplace] Handle Order Complete Status Error: Invalid Order ID ' . $order_id );
            return;
        }

        // return if charge has been captured already
        if ( static::is_charge_captured( $order ) ) {
            return;
        }

        // store charge capture data to prevent same processing to happen via webhook request
        $order->update_meta_data( '_dokan_paypal_payment_charge_captured', 'yes' );
        $order->save();

        static::store_capture_payment_data( $purchase_units, $order );

        $test_mode = Helper::is_test_mode() ? __( 'PayPal Sandbox Order ID', 'dokan' ) : __( 'PayPal Order ID', 'dokan' );
        $order->add_order_note(
            sprintf(
            /* translators: 1) wc order number, 2) payment gateway title, 3) PayPal order text 4) PayPal order number. */
                __( 'Order %1$s payment is completed via %2$s (%3$s: %4$s)', 'dokan' ),
                $order->get_order_number(),
                Helper::get_gateway_title(),
                $test_mode,
                $paypal_order_id
            )
        );
    }

    /**
     * Store each order capture id
     *
     * @param array $purchase_units
     * @param \WC_Order $order
     *
     * @since 3.3.0
     *
     * @return void
     */
    protected static function store_capture_payment_data( &$purchase_units, \WC_Order $order ) {
        //add capture id to meta data
        foreach ( $purchase_units as $key => $unit ) {
            $capture_id                     = sanitize_text_field( $unit['payments']['captures'][0]['id'] );
            $seller_receivable              = $unit['payments']['captures'][0]['seller_receivable_breakdown'];
            $paypal_fee_data                = $seller_receivable['paypal_fee'];
            $paypal_processing_fee_currency = sanitize_text_field( $paypal_fee_data['currency_code'] );
            $paypal_processing_fee          = (float) $paypal_fee_data['value'];
            $platform_fee                   = isset( $seller_receivable['platform_fees'][0]['amount']['value'] ) ? (float) $seller_receivable['platform_fees'][0]['amount']['value'] : 0.00; //admin commission

            //maybe this is a suborder id. if there is no suborder then it will be the main order id
            $_order_id = intval( $unit['custom_id'] );

            //may be a suborder
            $_order = wc_get_order( $_order_id );
            // check order is a valid WC_Order class instance
            if ( ! $_order ) {
                dokan_log( '[Dokan PayPal Marketplace] Store Capture Payment Data Error: Invalid Order ID ' . $_order_id );
                continue;
            }

            $_order->add_order_note(
                /* translators: %s: paypal processing fee */
                sprintf( __( 'PayPal processing fee is %s', 'dokan' ), $paypal_processing_fee )
            );

            //add order note to parent order
            if ( $_order_id !== $order->get_id() ) {
                $order_url = $_order->get_edit_order_url();
                $order->add_order_note(
                    /* translators: 1$s: a tag with url , 2$s: paypal processing fee  */
                    sprintf( __( '%1$s: processing fee for sub order %2$s is %3$s', 'dokan' ), Helper::get_gateway_title(), "<a href='{$order_url}'>{$_order_id}</a>", $paypal_processing_fee )
                );
            }

            $_order->set_payment_method( Helper::get_gateway_id() );
            $_order->update_meta_data( '_dokan_paypal_payment_capture_id', $capture_id );
            $_order->update_meta_data( '_dokan_paypal_payment_processing_fee', $paypal_processing_fee );
            $_order->update_meta_data( '_dokan_paypal_payment_processing_currency', $paypal_processing_fee_currency );
            $_order->update_meta_data( '_dokan_paypal_payment_platform_fee', $platform_fee );
            $_order->update_meta_data( 'dokan_gateway_fee_paid_by', 'seller' );
            $_order->save();

            /**
             * Process the gateway fee for the suborder
             *
             * @param float $paypal_processing_fee
             * @param \WC_Order $_order
             *
             * @since 4.1.1
             */
            do_action( 'dokan_process_payment_gateway_fee', $paypal_processing_fee, $_order, Helper::get_gateway_id() );

            $test_mode = Helper::is_test_mode() ? __( 'PayPal Sandbox Transaction ID', 'dokan' ) : __( 'PayPal Transaction ID', 'dokan' );
            $_order->add_order_note(
                sprintf(
                    '%1$s: %2$s',
                    $test_mode,
                    $capture_id
                )
            );

            $seller_id = dokan_get_seller_id_by_order( $_order->get_id() );
            $withdraw_data = [
                'vendor_id' => $seller_id,
                'order_id'  => $_order->get_id(),
                'amount'    => (float) $seller_receivable['net_amount']['value'],
            ];

            static::handle_vendor_balance( $withdraw_data );
        }
    }

    /**
     * Handle vendor balance and withdraw request
     *
     * @param array $withdraw_data
     *
     * @since 3.3.0
     *
     * @return bool
     */
    protected static function handle_vendor_balance( &$withdraw_data ) {
        do_action( 'dokan_update_vendor_balance_for_order', $withdraw_data['order_id'], $withdraw_data['vendor_id'] );

        //insert into withdraw table
        $withdraw_data_inserted = static::insert_vendor_withdraw_balance( $withdraw_data );
        if ( is_wp_error( $withdraw_data_inserted ) ) {
            dokan_log(
                "[Dokan PayPal Marketplace] Process Seller Withdraw Error:\n" . $withdraw_data_inserted->get_error_message()
                . ', withdraw data: ' . print_r( $withdraw_data, true ), 'error'
            );
            return false;
        }

        return true;
    }

    /**
     * This method will entry vendors withdraw entries
     *
     * @param array $withdraw
     * @param bool $insert_now
     *
     * @since 3.3.0
     *
     * @return WP_Error|bool true on success, WP_Error instance otherwise
     */
    public static function insert_vendor_withdraw_balance( &$withdraw, $insert_now = false ) {
        // check if order id exists
        if ( ! $withdraw['order_id'] ) {
            return new WP_Error( 'insert_vendor_withdraw_balance_error', sprintf( '[insert_vendor_withdraw_balance] Invalid order id. data: %1$s', print_r( $withdraw, true ) ) );
        }

        $order = wc_get_order( $withdraw['order_id'] );
        if ( ! $order ) {
            return new WP_Error( 'insert_vendor_withdraw_balance_error', sprintf( '[insert_vendor_withdraw_balance] Invalid order id. data: %1$s', print_r( $withdraw, true ) ) );
        }

        // check disbursement mode
        if ( false === $insert_now && $order->get_meta( '_dokan_paypal_payment_disbursement_mode', true ) !== 'INSTANT' ) {
            // don't insert withdraw balance, store withdraw data as order meta
            $order->update_meta_data( '_dokan_paypal_payment_withdraw_data', $withdraw );
            $order->update_meta_data( '_dokan_paypal_payment_withdraw_balance_added', 'no' );
            $order->save();
            return true;
        }

        // check if withdraw data is already inserted
        if ( $order->get_meta( '_dokan_paypal_payment_withdraw_balance_added', true ) === 'yes' ) {
            return true;
        }

        global $wpdb;

        // check if already inserted
        $withdraw_data = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT 1 FROM {$wpdb->prefix}dokan_vendor_balance WHERE trn_id = %d AND trn_type = %s",
                [ $withdraw['order_id'], 'dokan_withdraw' ]
            )
        );

        if ( $withdraw_data ) {
            return true;
        }

        //insert withdraw amount as credit in dokan vendor balance table
        $inserted = $wpdb->insert(
            $wpdb->prefix . 'dokan_vendor_balance',
            [
                'vendor_id'    => $withdraw['vendor_id'],
                'trn_id'       => $withdraw['order_id'],
                'trn_type'     => 'dokan_withdraw',
                'perticulars'  => 'Paid Via ' . Helper::get_gateway_title(),
                'debit'        => 0,
                'credit'       => $withdraw['amount'],
                'status'       => 'approved',
                'trn_date'     => current_time( 'mysql' ),
                'balance_date' => current_time( 'mysql' ),
            ],
            [
                '%d',
                '%d',
                '%s',
                '%s',
                '%f',
                '%f',
                '%s',
                '%s',
                '%s',
            ]
        );
        // check for possible error
        if ( false === $inserted ) {
            return new WP_Error( 'update_dokan_vendor_balance_error', sprintf( '[insert_vendor_withdraw_balance] Error while inserting into vendor balance table data: %1$s', $wpdb->last_error ) );
        }

        //insert into withdraw table
        $ip = dokan_get_client_ip();
        $data = [
            'user_id' => $withdraw['vendor_id'],
            'amount'  => $withdraw['amount'],
            'date'    => current_time( 'mysql' ),
            'status'  => 1,
            'method'  => Helper::get_gateway_id(),
            /* translators: 1) Payment gateway title, 2) order id */
            'notes'   => sprintf( __( 'Order %1$d payment Auto paid via %2$s', 'dokan' ), $withdraw['order_id'], Helper::get_gateway_title() ),
            'ip'      => $ip,
        ];

        $withdraw_data_inserted = dokan()->withdraw->insert_withdraw( $data );
        if ( is_wp_error( $withdraw_data_inserted ) ) {
            return $withdraw_data_inserted;
        }

        $order->update_meta_data( '_dokan_paypal_payment_withdraw_balance_added', 'yes' );
        $order->save();

        //remove cache for seller earning
        $cache_key = "get_earning_from_order_table_{$withdraw['order_id']}_seller";
        Cache::delete( $cache_key );
        // remove cache for seller earning
        $cache_key = "get_earning_from_order_table_{$withdraw['order_id']}_admin";
        Cache::delete( $cache_key );

        return true;
    }

    /**
     * @param \WC_Order|int $order
     *
     * @since 3.3.0
     *
     * @return array
     */
    public static function get_refund_ids_by_order( $order ) {
        if ( is_numeric( $order ) ) {
            $order = wc_get_order( $order );
        }

        if ( ! $order instanceof \WC_Order ) {
            return [];
        }

        $refund_ids = $order->get_meta( '_dokan_paypal_refund_id' );
        if ( $refund_ids === '' ) {
            $refund_ids = [];
        } elseif ( ! is_array( $refund_ids ) && $refund_ids !== '' ) {
            $refund_ids = (array) $refund_ids;
        }

        return $refund_ids;
    }

    /**
     * Do not call this method directly
     *
     * @param \WC_Order $order
     *
     * @since 3.3.0
     *
     * @return void
     */
    public static function _disburse_payment( $order ) { //phpcs:ignore
        // check if this is a valid WC_Order object
        if ( ! $order instanceof \WC_Order ) {
            return;
        }
        // prepare data
        $data = [
            'reference_id'      => $order->get_meta( '_dokan_paypal_payment_capture_id' ),
            'reference_type'    => 'TRANSACTION_ID',
            'invoice_id'        => $order->get_id(),
        ];

        $processor = Processor::init();
        $response  = $processor->create_referenced_payout( $data );

        if ( is_wp_error( $response ) ) {
            dokan_log( '[Dokan PayPal Marketplace] Could not disbursed fund to vendor. Data: ' . print_r( $response, true ) );
            $order->add_order_note(
                // translators: 1) Payment Gateway Title, 2) Error message from gateway
                sprintf( __( '[%1$s] Could not disbursed fund to vendor. Error From gateway: %2$s', 'dokan' ), Helper::get_gateway_title(), Helper::get_error_message( $response ) )
            );
        } else {
            $withdraw_data = $order->get_meta( '_dokan_paypal_payment_withdraw_data' );
            $response      = static::insert_vendor_withdraw_balance( $withdraw_data, true );

            if ( is_wp_error( $response ) ) {
                $order->add_order_note(
                // translators: 1) Payment Gateway Title, 2) Error message from gateway
                    sprintf( __( '[%1$s] Inserting into vendor balance failed. Error Message: %2$s', 'dokan' ), Helper::get_gateway_title(), Helper::get_error_message( $response ) )
                );
            } else {
                $order->add_order_note(
                // translators: 1) Payment Gateway Title, 2) Error message from gateway
                    sprintf( __( '[%1$s] Successfully disbursed fund to the vendor.', 'dokan' ), Helper::get_gateway_title() )
                );
            }
        }
    }
}
