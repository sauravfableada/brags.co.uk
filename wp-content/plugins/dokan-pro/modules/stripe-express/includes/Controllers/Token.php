<?php

namespace WeDevs\DokanPro\Modules\StripeExpress\Controllers;

defined( 'ABSPATH' ) || exit; // Exit if called directly

use Stripe\PaymentMethod;
use Stripe\StripeObject;
use WC_Payment_Token;
use WC_Payment_Tokens;
use WeDevs\DokanPro\Modules\StripeExpress\Support\Helper;
use WeDevs\DokanPro\Modules\StripeExpress\Processors\Customer;
use WeDevs\DokanPro\Modules\StripeExpress\PaymentTokens\DynamicPaymentMethod;

/**
 * Handles and process WC payment tokens API.
 * Seen in checkout page and my account->add payment method page.
 *
 * @since 3.6.1
 *
 * @package WeDevs\DokanPro\Modules\StripeExpress\Controllers
 */
class Token {

    /**
     * Class constructor.
     *
     * @since 3.6.1
     */
    public function __construct() {
        add_action( 'init', [ $this, 'hooks' ] );
    }

    /**
     * Registers all necessary hooks.
     *
     * @since 3.6.1
     *
     * @return void
     */
    public function hooks() {
        add_filter( 'woocommerce_payment_methods_types', [ $this, 'payment_methods_types' ] );
        add_filter( 'woocommerce_payment_token_class', [ $this, 'get_payment_token_class' ], 10, 2 );
        add_filter( 'woocommerce_payment_methods_list_item', [ $this, 'get_account_saved_payment_method' ], 10, 2 );
        add_action( 'woocommerce_payment_token_deleted', [ $this, 'payment_token_deleted' ], 10, 2 );
        add_action( 'woocommerce_payment_token_set_default', [ $this, 'payment_token_set_default' ] );
        add_action( 'dokan_stripe_express_attach_payment_method', [ $this, 'create_token_from_payment_method_for_user' ], 10, 2 );
    }

    /**
     * Retrieves payment method types supported for tokenization.
     *
     * @since 3.6.1
     *
     * @todo: need to update the reusable payment methods to include
     *
     * @return array<string,string> Array of payment method types and their labels
     */
    public static function payment_methods_types( array $existing_types ): array {
        $labels = Helper::get_payment_method_labels();

        // Merge existing types with the labels.
        $existing_types = array_merge( $existing_types, $labels );

        /**
         * Filter the payment method types supported for tokenization.
         *
         * @since 3.6.1
         *
         * @param array<string,string> $payment_methods List of payment method types and labels
         */
        return apply_filters( 'dokan_stripe_express_payment_methods_types', $existing_types );
    }

    /**
     * Modifies the payment token class while adding/changing
     * payment method to add support for custom token types.
     *
     * @since 4.3.0
     *
     * @param string $token_class The default token class generated
     * @param string $token_type  Type of the token being processed
     *
     * @return string
     */
    public function get_payment_token_class( string $token_class, string $token_type ): string {
        if ( 'dokan_stripe_express_dynamic_payment_method' !== $token_type ) {
            return $token_class;
        }

        /**
         * Filter the payment token class for a given payment method type.
         *
         * @since 4.3.0
         *
         * @param string $class Fully qualified class name
         * @param string $type  Payment method type
         */
        return apply_filters( 'dokan_stripe_express_payment_method_token_class', DynamicPaymentMethod::class, $token_type );
    }

    /**
     * Controls the output for payment methods on the My Account page.
     *
     * @since 3.6.1
     *
     * @param array            $item          Individual list item from woocommerce_saved_payment_methods_list.
     * @param WC_Payment_Token $payment_token The payment token associated with this method entry.
     *
     * @return array Filtered item for display.
     */
    public function get_account_saved_payment_method( array $item, $payment_token ): array {
        // Only process tokens from our gateway
        if ( Helper::get_gateway_id() !== $payment_token->get_gateway_id() ) {
            Helper::log(
                sprintf(
                    'Skipping token %s: Gateway mismatch (expected %s, got %s)',
                    $payment_token->get_id(),
                    Helper::get_gateway_id(),
                    $payment_token->get_gateway_id()
                ),
            );
            return $item;
        }

        // Set basic token information
        $item['token_id'] = $payment_token->get_id();
        $item['default']  = $payment_token->is_default();
        $item['method']['gateway'] = $payment_token->get_gateway_id();

        $method_type = 'unknown';
        if ( $payment_token instanceof DynamicPaymentMethod ) {
            // Determine payment method type
            $method_type = $payment_token->get_payment_method_type();

            // Set display name as the initial brand
			$item['method']['brand'] = $payment_token->get_display_name();

            // Override brand with more specific information if available
            if ( ! empty( $payment_token->get_brand() ) ) {
                $item['method']['brand'] = $payment_token->get_brand();
            } elseif ( ! empty( $payment_token->get_bank_name() ) ) {
                $item['method']['brand'] = $payment_token->get_bank_name();
            }

            // Add last4 digits if available
            if ( ! empty( $payment_token->get_last4() ) ) {
                $item['method']['last4'] = $payment_token->get_last4();
            }

            // Add email if available
            if ( ! empty( $payment_token->get_email() ) ) {
                $item['method']['email'] = $payment_token->get_email();
            }

            // Add an expiration date if available
            if ( ! empty( $payment_token->get_expiry_month() ) && ! empty( $payment_token->get_expiry_year() ) ) {
                $item['expires'] = sprintf(
                // translators: expire month, expire year
                    __( '%1$s/%2$s', 'dokan' ),
                    $payment_token->get_expiry_month(),
                    $payment_token->get_expiry_year()
                );
            }
        }

        /**
         * Filter the list item containing the payment method data for displaying on My Account page.
         *
         * @since 4.3.0
         *
         * @param array                $item          Array containing payment method display data
         * @param WC_Payment_Token     $payment_token The payment token object
         * @param string               $method_type   The type of payment method
         */
        return apply_filters( 'dokan_stripe_express_account_saved_payment_methods_list_item', $item, $payment_token, $method_type );
    }

    /**
     * Delete token from Stripe.
     *
     * @since 3.6.1
     *
     * @param string           $token_id
     * @param WC_Payment_Token $token
     *
     * @return void
     */
    public function payment_token_deleted( $token_id, $token ) {
        if ( Helper::is_gateway_ready() && Helper::get_gateway_id() === $token->get_gateway_id() ) {
            $customer = Customer::set( get_current_user_id() );
            if ( $customer instanceof Customer ) {
                $customer->detach_payment_method( $token->get_token() );
            }
        }
    }

    /**
     * Set as default in Stripe.
     *
     * @since 3.6.1
     *
     * @param string|int $token_id
     *
     * @return void
     */
    public function payment_token_set_default( $token_id ) {
        if ( ! Helper::is_gateway_ready() ) {
            return;
        }

        $token = WC_Payment_Tokens::get( $token_id );
        if ( $token && Helper::get_gateway_id() === $token->get_gateway_id() ) {
            $customer = Customer::set( get_current_user_id() );
            if ( $customer instanceof Customer ) {
                $customer->set_default_payment_method( $token->get_token() );
            }
        }
    }

    /**
     * Create a payment token for a user from a Stripe payment method.
     *
     * @since 4.3.0
     *
     * @param int           $user_id        WooCommerce user ID.
     * @param PaymentMethod $payment_method Stripe payment method object.
     *
     * @return WC_Payment_Token|null
     */
    public function create_token_from_payment_method_for_user( int $user_id, PaymentMethod $payment_method ) {
        // Check if WC_Payment_Token class exists
        if ( ! class_exists( WC_Payment_Token::class ) ) {
            dokan_log(
                sprintf(
                    'Cannot create token. WC_Payment_Token class not found. Payment method: %s (type: %s) for user: %s',
                    $payment_method->id,
                    $payment_method->type,
                    $user_id
                )
            );
            return null;
        }

        // Check if token already exists for this payment method to prevent duplicates
        // (e.g. when webhook and redirect both try to save the same payment method)
        $existing_tokens = WC_Payment_Tokens::get_customer_tokens( $user_id, Helper::get_gateway_id() );
        foreach ( $existing_tokens as $existing_token ) {
            if ( $existing_token->get_token() === $payment_method->id ) {
                // Token already exists, return it instead of creating a duplicate
                return $existing_token;
            }
        }

        // Check if same payment method already saved (user re-entered same card/account details)
        // Stripe fingerprint identifies the same physical method across different PaymentMethod IDs
        $method_data = isset( $payment_method->{$payment_method->type} ) ? $payment_method->{$payment_method->type} : null;
        if ( $method_data instanceof StripeObject ) {
            $new_fingerprint = $method_data->fingerprint ?? null;
            $new_last4       = $method_data->iban_last4 ?? $method_data->last4 ?? null;
            $new_exp_month   = $method_data->exp_month ?? null;
            $new_exp_year    = $method_data->exp_year ?? null;
            $new_brand       = $method_data->brand ?? null;

            foreach ( $existing_tokens as $existing_token ) {
                if ( ! $existing_token instanceof DynamicPaymentMethod ) {
                    continue;
                }
                // Match by fingerprint (most reliable for cards)
                if ( ! empty( $new_fingerprint ) && $existing_token->get_fingerprint() === $new_fingerprint ) {
                    return $existing_token;
                }
                // Fallback: match by last4 + expiry + brand (same card, different fingerprint edge case)
                $new_exp_month_padded = null !== $new_exp_month ? str_pad( (string) $new_exp_month, 2, '0', STR_PAD_LEFT ) : null;
                if (
                    ! empty( $new_last4 ) &&
                    ! empty( $new_exp_month_padded ) &&
                    ! empty( $new_exp_year ) &&
                    $existing_token->get_last4() === $new_last4 &&
                    $existing_token->get_expiry_month() === $new_exp_month_padded &&
                    $existing_token->get_expiry_year() === (string) $new_exp_year &&
                    ( empty( $new_brand ) || $existing_token->get_brand() === $new_brand )
                ) {
                    return $existing_token;
                }
            }
        }

        try {
            // Create and initialize the token with basic information
            $token = new DynamicPaymentMethod();
            $token->set_gateway_id( Helper::get_gateway_id() );
            $token->set_user_id( $user_id );
            $token->set_token( $payment_method->id );
            $token->set_payment_method_type( $payment_method->type );

            // Process payment method specific data if available
            if ( isset( $payment_method->{$payment_method->type} ) ) {
                $method_data = $payment_method->{$payment_method->type};
                if ( $method_data instanceof StripeObject ) {
                    $token->set_additional_data( $method_data );

                    // Set token properties based on available method data
                    $this->set_token_properties( $token, $method_data );
                }
            }

            $token->save();

            return $token;
        } catch ( \Exception $e ) {
            Helper::log( $e->getMessage(), 'error' );
            if ( ! defined( 'REST_REQUEST' ) ) {
                wc_add_notice( $e->getMessage(), 'error' );
            }
            return null;
        }
    }

    /**
     * Set token properties based on payment method data.
     *
     * @since 4.3.0
     *
     * @param DynamicPaymentMethod $token       The payment token.
     * @param StripeObject         $method_data The payment method data.
     *
     * @return void
     */
    private function set_token_properties( DynamicPaymentMethod $token, $method_data ): void {
        // Set fingerprint if available
        if ( isset( $method_data->fingerprint ) ) {
            $token->set_fingerprint( $method_data->fingerprint );
        }

        // Set last 4 digits
        if ( isset( $method_data->iban_last4 ) ) {
            $token->set_last4( $method_data->iban_last4 );
        } elseif ( isset( $method_data->last4 ) ) {
            $token->set_last4( $method_data->last4 );
        }

        // Set card details
        if ( isset( $method_data->brand ) ) {
            $token->set_brand( $method_data->brand );
        }
        if ( isset( $method_data->card_type ) ) {
            $token->set_card_type( $method_data->card_type );
        }
        if ( isset( $method_data->exp_month ) ) {
            $token->set_expiry_month( $method_data->exp_month );
        }
        if ( isset( $method_data->exp_year ) ) {
            $token->set_expiry_year( $method_data->exp_year );
        }

        // Set bank details
        if ( isset( $method_data->bank_name ) ) {
            $token->set_bank_name( $method_data->bank_name );
        }
        if ( isset( $method_data->account_type ) ) {
            $token->set_account_type( $method_data->account_type );
        }

        // Set email address
        if ( isset( $method_data->payer_email ) ) {
            $token->set_email( $method_data->payer_email );
        } elseif ( isset( $method_data->email ) ) {
            $token->set_email( $method_data->email );
        }
    }
}
