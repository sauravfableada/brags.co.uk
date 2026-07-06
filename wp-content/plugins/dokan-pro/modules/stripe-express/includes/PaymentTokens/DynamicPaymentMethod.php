<?php

namespace WeDevs\DokanPro\Modules\StripeExpress\PaymentTokens;

use WC_Payment_Token;
use WeDevs\DokanPro\Modules\StripeExpress\Support\Helper;

defined( 'ABSPATH' ) || exit; // Exit if called directly

/**
 * Dynamic Payment Token for Stripe Express.
 *
 * A flexible payment token class that supports multiple Stripe payment methods dynamically.
 *
 * @since   4.3.0
 * @package WeDevs\DokanPro\Modules\StripeExpress\PaymentTokens
 */
class DynamicPaymentMethod extends WC_Payment_Token {

    /**
     * Stores payment type (e.g., card, sepa_debit, amazon_pay, etc.).
     *
     * @var string
     */
    protected $type = 'dokan_stripe_express_dynamic_payment_method';

    /**
     * Stores payment token data specific to the payment method.
     *
     * @var array
     */
    protected $extra_data = [
        'payment_method_type' => '',
        'brand'               => '',
        'card_type'           => '',
        'expiry_year'         => '',
        'expiry_month'        => '',
        'last4'               => '',
        'email'               => '',
        'bank_name'           => '',
        'account_type'        => '',
        'fingerprint'         => '',
        'additional_data'     => '',
    ];

    /**
     * Get type to display to user.
     *
     * @param string $deprecated Deprecated since WooCommerce 3.0
     *
     * @return string
     */
    public function get_display_name( $deprecated = '' ) {
        $last4         = $this->get_last4();
        $email_address = $this->get_email();
        $bank_name     = $this->get_bank_name();
        $account_type  = $this->get_account_type();
        $method_type   = $this->get_payment_method_type();
        $method_label  = Helper::get_method_label( $method_type );

        // Case 1: Bank account with account type
        if ( ! empty( $last4 ) && ! empty( $account_type ) && ! empty( $bank_name ) ) {
            /* translators: %1$s is account type (checking, savings), %2$s is last 4 digits, %3$s is bank name */
            return sprintf( __( '%1$s account ending in %2$s (%3$s)', 'dokan' ), ucfirst( $account_type ), $last4, $bank_name );
        }

        // Case 2: Card or payment method with last4 digits
        if ( ! empty( $last4 ) ) {
            $brand = ! empty( $bank_name ) ? $bank_name : $this->get_brand();

            // If no brand is available, fall back to method label
            if ( empty( $brand ) ) {
                return $method_label;
            }

            /* translators: %1$s is brand/bank name, %2$s is last 4 digits */
            return sprintf( __( '%1$s ending in %2$s', 'dokan' ), $brand, $last4 );
        }

        // Case 3: Payment method with bank name
        if ( ! empty( $bank_name ) ) {
            /* translators: %1$s is method label, %2$s is bank name */
            return sprintf( __( '%1$s (%2$s)', 'dokan' ), $method_label, $bank_name );
        }

        // Case 4: Payment method with email
        if ( ! empty( $email_address ) ) {
            /* translators: %1$s is method label, %2$s is email */
            return sprintf( __( '%1$s (%2$s)', 'dokan' ), $method_label, $email_address );
        }

        // Default: Just return the method label
        return $method_label;
    }

    /**
     * Hook prefix.
     *
     * @return string
     */
    protected function get_hook_prefix() {
        return "{$this->type}_get_";
    }

    /**
     * Get brand for card payments.
     *
     * @param string $context Context (view or edit).
     *
     * @return string
     */
    public function get_brand( $context = 'view' ) {
        return $this->get_prop( 'brand', $context );
    }

    /**
     * Set brand for card payments.
     *
     * @param string $brand
     */
    public function set_brand( $brand ) {
        $this->set_prop( 'brand', $brand );
    }

    /**
     * Returns the card type (mastercard, visa, ...).
     *
     * @param string $context What the value is for. Valid values are view and edit.
     *
     * @return string Card type
     */
    public function get_card_type( $context = 'view' ) {
        return $this->get_prop( 'card_type', $context );
    }

    /**
     * Set the card type (mastercard, visa, ...).
     *
     * @param string $type Credit card type (mastercard, visa, ...).
     */
    public function set_card_type( $type ) {
        $this->set_prop( 'card_type', $type );
    }

    /**
     * Returns the card expiration year (YYYY).
     *
     * @param string $context What the value is for. Valid values are view and edit.
     *
     * @return string Expiration year
     */
    public function get_expiry_year( $context = 'view' ) {
        return $this->get_prop( 'expiry_year', $context );
    }

    /**
     * Set the expiration year for the card (YYYY format).
     *
     * @param string $year Credit card expiration year.
     */
    public function set_expiry_year( $year ) {
        $this->set_prop( 'expiry_year', $year );
    }

    /**
     * Returns the card expiration month (MM).
     *
     * @param string $context What the value is for. Valid values are view and edit.
     *
     * @return string Expiration month
     */
    public function get_expiry_month( $context = 'view' ) {
        return $this->get_prop( 'expiry_month', $context );
    }

    /**
     * Set the expiration month for the card (formats into MM format).
     *
     * @param string $month Credit card expiration month.
     */
    public function set_expiry_month( $month ) {
        $this->set_prop( 'expiry_month', str_pad( $month, 2, '0', STR_PAD_LEFT ) );
    }

    /**
     * Get last four digits for debit methods.
     *
     * @param string $context Context (view or edit).
     *
     * @return string
     */
    public function get_last4( $context = 'view' ) {
        return $this->get_prop( 'last4', $context );
    }

    /**
     * Set last four digits for debit methods.
     *
     * @param string $last4
     */
    public function set_last4( $last4 ) {
        $this->set_prop( 'last4', $last4 );
    }

    /**
     * Get email for methods like Amazon Pay, PayPal, Link.
     *
     * @param string $context Context (view or edit).
     *
     * @return string
     */
    public function get_email( $context = 'view' ) {
        return $this->get_prop( 'email', $context );
    }

    /**
     * Set email for methods like Amazon Pay, PayPal, Link.
     *
     * @param string $email
     */
    public function set_email( $email ) {
        $this->set_prop( 'email', $email );
    }

    /**
     * Get bank name for methods like ACSS Debit, US Bank Account.
     *
     * @param string $context Context (view or edit).
     *
     * @return string
     */
    public function get_bank_name( $context = 'view' ) {
        return $this->get_prop( 'bank_name', $context );
    }

    /**
     * Set bank name for methods like ACSS Debit, US Bank Account.
     *
     * @param string $bank_name
     */
    public function set_bank_name( $bank_name ) {
        $this->set_prop( 'bank_name', $bank_name );
    }

    /**
     * Get the account type.
     *
     * @param string $context What the value is for. Valid values are view and edit.
     *
     * @return string
     */
    public function get_account_type( $context = 'view' ) {
        return $this->get_prop( 'account_type', $context );
    }

    /**
     * Set the account type.
     *
     * @param string $account_type
     */
    public function set_account_type( $account_type ) {
        $this->set_prop( 'account_type', $account_type );
    }

    /**
     * Get payment method type.
     *
     * @param string $context Context (view or edit).
     *
     * @return string
     */
    public function get_payment_method_type( $context = 'view' ) {
        return $this->get_prop( 'payment_method_type', $context );
    }

    /**
     * Set payment method type.
     *
     * @param string $type
     */
    public function set_payment_method_type( $type ) {
        $this->set_prop( 'payment_method_type', $type );
    }

    /**
     * Returns the token fingerprint (unique identifier).
     *
     * @param string $context What the value is for. Valid values are view and edit.
     *
     * @return string Fingerprint
     */
    public function get_fingerprint( $context = 'view' ) {
        return $this->get_prop( 'fingerprint', $context );
    }

    /**
     * Set the token fingerprint (unique identifier).
     *
     * @param string $fingerprint The fingerprint.
     */
    public function set_fingerprint( string $fingerprint ) {
        $this->set_prop( 'fingerprint', $fingerprint );
    }

    /**
     * Returns additional payment token data.
     *
     * @param string $context What the value is for. Valid values are view and edit.
     *
     * @return mixed Additional data
     */
    public function get_additional_data( $context = 'view' ) {
        return $this->get_prop( 'additional_data', $context );
    }

    /**
     * Set additional payment token data.
     *
     * @param mixed $additional_data Additional data to store.
     */
    public function set_additional_data( $additional_data ) {
        $this->set_prop( 'additional_data', $additional_data );
    }
}
