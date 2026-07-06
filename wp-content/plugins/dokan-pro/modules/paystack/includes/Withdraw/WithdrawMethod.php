<?php

namespace WeDevs\DokanPro\Modules\Paystack\Withdraw;

use Exception;
use WeDevs\DokanPro\Modules\Paystack\Api\SubAccount;
use WeDevs\DokanPro\Modules\Paystack\Support\Helper;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class WithdrawMethod {

    /**
     * Class constructor
     *
     * @since 4.1.1
     */
    public function __construct() {
        add_filter( 'dokan_withdraw_methods', [ $this, 'register_method' ] );
        add_filter( 'dokan_withdraw_method_settings_title', [ $this, 'get_heading' ], 10, 2 );
        add_filter( 'dokan_withdraw_method_icon', [ $this, 'get_icon' ], 10, 2 );
        add_filter( 'dokan_is_seller_connected_to_payment_method', [ $this, 'is_seller_connected' ], 10, 3 );
        add_filter( 'dokan_vendor_to_array', [ $this, 'add_to_vendor_profile_data' ] );
    }

    /**
     * Register Withdraw method.
     *
     * @param array $methods
     *
     * @since 4.1.1
     *
     * @return array
     */
    public function register_method( array $methods ): array {
        // check if admin provided all the required api keys

        $methods['dokan_paystack'] = [
            'title'    => __( 'Paystack', 'dokan' ),
            'callback' => [ $this, 'paystack_connect_button' ],
        ];

        return $methods;
    }

    public function paystack_connect_button( $store_settings ) {
        global $current_user;

        $email = isset( $store_settings['payment']['dokan_paystack']['email'] ) ? esc_attr( $store_settings['payment']['dokan_paystack']['email'] ) : $current_user->user_email;

        $is_seller_enabled = Helper::is_seller_enable_for_receive_payment( get_current_user_id() );

        try {
            $account_code = Helper::get_seller_account_id( dokan_get_current_user_id() );
            SubAccount::get( $account_code );
        } catch ( Exception $e ) {
            $account_code = '';
            $is_seller_enabled = false;
        }

        wp_enqueue_script( 'dokan-paystack-vendor' );
        wp_enqueue_style( 'dokan-paystack-style' );
        wp_localize_script(
            'dokan-paystack-vendor', 'dokanPaystack', array(
				'email'             => $email,
				'is_seller_enabled' => $is_seller_enabled,
				'account_code'      => $account_code,
            )
        );
        wp_set_script_translations( 'dokan-paystack-vendor', 'dokan' );

        Helper::get_template( 'vendor-settings-payment' );
    }

    /**
     * Get the Withdrawal method icon
     *
     * @since 4.1.1
     *
     * @param string $method_icon
     * @param string $method_key
     *
     * @return string
     */
    public function get_icon( string $method_icon, string $method_key ): string {
        if ( Helper::get_gateway_id() === $method_key ) {
            $method_icon = DOKAN_PAYSTACK_ASSETS . 'images/paystack.svg';
        }

        return $method_icon;
    }
    /**
     * Get the heading for this payment's settings page
     *
     * @since 4.1.1
     *
     * @param string $heading
     * @param string $slug
     *
     * @return string
     */
    public function get_heading( string $heading, string $slug ): string {
        if ( false !== strpos( $slug, Helper::get_gateway_id() ) ) {
            $heading = __( 'Paystack Settings', 'dokan' );
        }

        return $heading;
    }

    /**
     * Get if a seller is connected to this payment method
     *
     * @since 4.1.1
     *
     * @param bool $connected
     * @param string $payment_method_id
     * @param int $seller_id
     *
     * @return bool
     */
    public function is_seller_connected( bool $connected, string $payment_method_id, int $seller_id ): bool {
        if ( Helper::get_gateway_id() === $payment_method_id && Helper::is_seller_enable_for_receive_payment( $seller_id ) ) {
            $connected = true;
        }

        return $connected;
    }

    /**
     * Returns true if vendor enabled paystack
     *
     * @since 4.1.1
     *
     * @param $data
     *
     * @return array
     */
    public function add_to_vendor_profile_data( $data ): array {
        $vendor_id = ! empty( $data['id'] ) ? absint( $data['id'] ) : 0;

        if ( ! current_user_can( 'manage_woocommerce' ) && $vendor_id !== dokan_get_current_user_id() ) {
            return $data;
        }

        $data['payment']['dokan_paystack'] = $this->is_seller_connected( false, 'dokan_paystack', $vendor_id );

        return $data;
    }
}
