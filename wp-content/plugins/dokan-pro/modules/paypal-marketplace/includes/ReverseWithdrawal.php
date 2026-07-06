<?php
namespace WeDevs\DokanPro\Modules\PayPalMarketplace;

use WeDevs\Dokan\ReverseWithdrawal\SettingsHelper;
use WeDevs\Dokan\ReverseWithdrawal\Manager as ReverseWithdrawalManager;
use WeDevs\Dokan\ReverseWithdrawal\Helper as ReverseWithdrawalHelper;

/**
 * Class ReverseWithdrawal
 *
 * @since 3.6.1
 *
 * @package WeDevs\DokanPro\Modules\PayPalMarketplace
 */
class ReverseWithdrawal {
    /**
     * Class constructor
     *
     * @since 3.6.1
     */
    public function __construct() {
        // check if version match
        if ( ! class_exists( ReverseWithdrawalManager::class ) ) {
            return;
        }
        // call hooks
        $this->init_hooks();
    }

    /**
     * Initialize the hooks
     *
     * @since 3.6.1
     *
     * @return void
     */
    private function init_hooks() {
        // remove paypal marketplace checkout validations
        add_filter( 'dokan_paypal_marketplace_escape_after_checkout_validation', [ $this, 'remove_gateway_validations' ], 10, 2 );
        add_filter( 'dokan_paypal_marketplace_merchant_id', [ $this, 'get_merchant_id' ], 10, 2 );
        add_filter( 'dokan_paypal_marketplace_purchase_unit_merchant_id', [ $this, 'purchase_unit_merchant_id' ], 10, 2 );
    }

    /**
     * Remove PayPal Marketplace Checkout Validations
     *
     * @since 4.0.0
     *
     * @param bool $escape
     * @param array $cart_item
     *
     * @return bool
     */
    public function remove_gateway_validations( $escape, $cart_item ) {
        if ( true === wc_string_to_bool( $escape ) ) {
            return $escape;
        } elseif ( ReverseWithdrawalHelper::has_reverse_withdrawal_payment_in_cart() ) {
            return true;
        }
        return $escape;
    }

    /**
     * Get admin partner id
     *
     * @since 4.0.0
     *
     * @param string $merchant_id
     * @param int $product_id
     *
     * @return string
     */
    public function get_merchant_id( $merchant_id, $product_id ) {
        // check if this is a recurring subscription product
        if ( ReverseWithdrawalHelper::has_reverse_withdrawal_payment_in_cart() ) {
            return Helper::get_partner_id();
        }

        return $merchant_id;
    }

    /**
     * Get admin partner id
     *
     * @since 4.0.0
     *
     * @param string $merchant_id
     * @param \WC_Abstract_Order $order
     *
     * @return string
     */
    public function purchase_unit_merchant_id( $merchant_id, $order ) {
        // check if this is a recurring subscription product
        if ( ReverseWithdrawalHelper::has_reverse_withdrawal_payment_in_cart( $order ) ) {
            return Helper::get_partner_id();
        }

        return $merchant_id;
    }
}
