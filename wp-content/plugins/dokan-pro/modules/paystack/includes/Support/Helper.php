<?php

namespace WeDevs\DokanPro\Modules\Paystack\Support;

use DokanPro\Modules\Subscription\SubscriptionPack;
use WC_Order;
use WC_Product;
use WeDevs\DokanPro\Modules\Paystack\Gateway\Paystack;
use WeDevs\DokanPro\Modules\ProductAdvertisement\Helper as ProductAdvertisementHelper;
use WeDevs\Dokan\ReverseWithdrawal\Helper as ReverseWithdrawalHelper;
use WC_Order_Item_Product;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Helper {

    /**
     * Get Paystack gateway id.
     *
     * @since 4.1.1
     *
     * @return string
     */
    public static function get_gateway_id(): string {
        // do not change this value ever, otherwise this will cause inconsistency while retrieving data
        return 'dokan_paystack';
    }

    /**
     * Get Paystack gateway title.
     *
     * @since 4.1.1
     *
     * @return string
     */
    public static function get_gateway_title(): string {
        $title = static::get_settings( 'title' );
        if ( empty( $title ) ) {
            $title = __( 'Dokan Paystack', 'dokan' );
        }
        return wp_kses(
            stripslashes( $title ),
            [
                'br'   => true,
                'p'    => [
                    'class' => true,
                ],
                'span' => [
                    'class' => true,
                    'title' => true,
                ],
            ]
        );
    }

    public static function get_settings( $key = null ) {
        $settings = get_option( 'woocommerce_' . static::get_gateway_id() . '_settings', [] );

        if ( isset( $key ) && isset( $settings[ $key ] ) ) {
            return $settings[ $key ];
        }

        return $settings;
    }

    /**
     * Check whether it's enabled or not.
     *
     * @since 4.1.1
     *
     * @return bool
     */
    public static function is_enabled(): bool {
        $settings = static::get_settings();

        return ! empty( $settings['enabled'] ) && 'yes' === $settings['enabled'];
    }

    /**
     * Check whether the gateway in test mode or not.
     *
     * @since 4.1.1
     *
     * @return bool
     */
    public static function is_test_mode(): bool {
        $settings = static::get_settings();

        return ! empty( $settings['test_mode'] ) && 'yes' === $settings['test_mode'];
    }

    /**
     * Get Paystack Key I'd.
     *
     * @since 4.1.1
     *
     * @return string
     */
    public static function get_public_api_key(): string {
        $key      = static::is_test_mode() ? 'test_api_key' : 'live_api_key';
        $settings = static::get_settings();

        return ! empty( $settings[ $key ] ) ? $settings[ $key ] : '';
    }

    /**
     * Get Paystack Key Secret.
     *
     * @since 4.1.1
     *
     * @return string
     */
    public static function get_api_secret(): string {
        $key      = static::is_test_mode() ? 'test_api_secret' : 'live_api_secret';
        $settings = static::get_settings();

        return ! empty( $settings[ $key ] ) ? $settings[ $key ] : '';
    }
    /**
     * Get the key for storing seller's marketplace settings.
     *
     * @since 4.1.1
     *
     * @return bool
     */
    public static function is_ready(): bool {
        if ( ! static::is_enabled() ||
            empty( static::get_public_api_key() ) ||
            empty( static::get_api_secret() )
        ) {
            return false;
        }
        return true;
    }

    /**
     * Get Seller Account ID Key.
     *
     * @since 4.1.1
     *
     * @return string
     */
    public static function get_seller_account_id_key(): string {
        return static::is_test_mode() ? '_dokan_paystack_test_account_id' : '_dokan_paystack_account_id';
    }
    /**
     * Get the gateway fee paid by setting.
     *
     * @since 4.1.1
     *
     * @return bool
     */
    public static function seller_pays_gateway_fee(): bool {
        $settings = self::get_settings();

        return isset( $settings['seller_pays_the_processing_fee'] ) && $settings['seller_pays_the_processing_fee'] === 'yes';
    }


    /**
     * Include module template.
     *
     * @since 4.1.1
     *
     * @param string $name template file name
     * @param array  $args
     *
     * @return void
     */
    public static function get_template( string $name, array $args = [] ) {
        $name = sanitize_text_field( wp_unslash( $name ) );
        dokan_get_template( "$name.php", $args, '', trailingslashit( DOKAN_PAYSTACK_TEMPLATE_PATH ) );
    }

    public static function get_supported_currencies() {
        return apply_filters( 'dokan_paystack_supported_currencies', [ 'NGN', 'USD', 'ZAR', 'GHS', 'KES', 'XOF', 'EGP', 'RWF' ] );
    }

    /**
     * Get Seller Account ID for paystack.
     *
     * @since 4.1.1
     *
     * @param int $seller_id
     *
     * @return string
     */
    public static function get_seller_account_id( int $seller_id ): string {
        return get_user_meta( $seller_id, static::get_seller_account_id_key(), true );
    }

    /**
     * Check Seller Enable for receiver payment or not.
     *
     * @since 4.1.1
     *
     * @param int $seller_id
     *
     * @return string
     */
    public static function get_seller_enabled_for_received_payment( int $seller_id ): string {
        return get_user_meta( $seller_id, static::get_seller_enabled_for_received_payment_key(), true );
    }

    /**
     * Get seller enabled a received payment key.
     *
     * @since 4.1.1
     *
     * @return string
     */
    public static function get_seller_enabled_for_received_payment_key(): string {
        return static::is_test_mode() ? '_dokan_paystack_test_enable_for_receive_payment' : '_dokan_paystack_enable_for_receive_payment';
    }

    /**
     * Check if the seller is enabled for receiver paystack payment.
     *
     * @since 4.1.1
     *
     * @param int $seller_id
     *
     * @return bool
     */
    public static function is_seller_enable_for_receive_payment( int $seller_id ): bool {
        return static::get_seller_account_id( $seller_id ) && static::get_seller_enabled_for_received_payment( $seller_id );
    }
    /**
     * Get order ID from metadata or reference.
     *
     * Extract order ID from reference (format: order_{order_id}_{random})
     *
     * @param string $reference Reference string that may contain order ID.
     * @param array  $metadata  Metadata array containing order ID.
     *
     * @since 4.1.1
     *
     * @return int Order ID.
     */
    public static function get_order_id( string $reference, array $metadata = [] ): int {
        $order_id = $metadata['order_id'] ?? 0;

        if ( empty( $order_id ) && ! empty( $reference ) ) {
            // Try to extract order ID from reference
            if ( strpos( $reference, 'order_' ) === 0 ) {
                $parts = explode( '_', $reference );
                if ( count( $parts ) >= 2 ) {
                    $order_id = intval( $parts[1] );
                }
            }
        }
        return $order_id;
    }

    /**
     * This function converts the amount to the format required by Paystack.
     *
     * @param float $amount
     * @param $base
     *
     * @return float|int|string
     */
    public static function format_paystack_amount( float $amount, $base = false ) {
        // If base is true, return the total with base currency conversion
        if ( $base ) {
            return wc_format_decimal( $amount / 100 );
        }
        // Use round() to ensure correct rounding before converting to integer,
        // For example, 18.65 * 100 = 1865.0 which should be 1865, not 1864
        return absint( round( floatval( wc_format_decimal( $amount, 2 ) ) * 100 ) );
    }

    public static function generate_reference( WC_Order $order, $force = false ): string {
        $ref = $order->get_meta( '_dokan_paystack_reference' );
        if ( ! empty( $ref ) && ! $force ) {
            return $ref; // Return existing reference if available
        }
        $order_id = $order->get_id();
        // Generate a unique reference for the order
        return 'order_' . $order_id . '_' . wp_generate_password( 6, false );
    }

    public static function get_return_url( $order = null ): string {
		return ( new Paystack() )->get_return_url( $order );
	}

    /**
     * Check if the order contains a vendor subscription product.
     *
     * @since 4.1.1
     *
     * @param WC_Order|int $order Order object or order ID.
     *
     * @return bool True if it contains a vendor subscription product, false otherwise.
     */
    public static function should_skip_payment_split( $order ): bool {
        if ( ! is_a( $order, 'WC_Abstract_Order' ) ) {
            $order = wc_get_order( $order );
        }

        if ( ! $order ) {
            return false;
        }

        foreach ( $order->get_items() as $item ) {
            /** @var WC_Order_Item_Product $item */
            $product = $item->get_product();
            $is_skip = self::should_skip_product( $product );

            if ( $is_skip ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the product is a vendor subscription product.
     *
     * @since 4.1.1
     *
     * @param mixed $product Product ID or WC_Product object.
     *
     * @return bool True if it's a vendor subscription product, false otherwise.
     */
    public static function is_vendor_subscription_product( $product ): bool {
        if ( is_int( $product ) ) {
            $product = wc_get_product( $product );
        }

        if ( ! $product ) {
            return false;
        }

        $has_vendor_subscription = function_exists( 'dokan_pro' ) && dokan_pro()->module->is_active( 'product_subscription' );

        if ( ! $has_vendor_subscription ) {
            return false;
        }
        // Check if the product is a subscription pack and is recurring
        $subscription = new SubscriptionPack( $product->get_id() );
        if ( 'product_pack' === $product->get_type() && $subscription->is_recurring() ) {
            return true;
        }

        return false;
    }

    /**
     * Check if the product is an advanced product.
     *
     * @since 4.1.1
     *
     * @param int $product_id Product ID.
     *
     * @return bool True if it's an advanced product, false otherwise.
     */
    public static function is_adv_product( $product_id ): bool {
        $has_product_advertising = function_exists( 'dokan_pro' ) && dokan_pro()->module->is_active( 'product_advertising' );
        if ( ! $has_product_advertising ) {
            return false;
        }
        $adv_product_id = get_option( ProductAdvertisementHelper::get_advertisement_base_product_option_key() );

        return (int) $adv_product_id === (int) $product_id;
    }

    /**
     * Check if the product is a reverse withdrawal product.
     *
     * @since 4.1.1
     *
     * @param int $product_id Product ID.
     *
     * @return bool True if it's a reverse withdrawal product, false otherwise.
     */
    public static function is_reverse_withdrawal_product( $product_id ): bool {
        $reverse_withdrawal_product_id = get_option( ReverseWithdrawalHelper::get_base_product_option_key() );

        return (int) $reverse_withdrawal_product_id === (int) $product_id;
    }

    /**
     * Check if the product is a subscription pack or an advanced product.
     *
     * @since 4.1.1
     *
     * @param WC_Product|int $product Product object or ID.
     *
     * @return bool True if it's a subscription pack or an advanced product, false otherwise.
     */
    public static function should_skip_product( $product ): bool {
        if ( is_numeric( $product ) ) {
            $product = wc_get_product( $product );
        }
        $product_id = $product->get_id();
        return 'product_pack' === $product->get_type() || self::is_adv_product( $product_id ) || self::is_reverse_withdrawal_product( $product_id );
    }
}
