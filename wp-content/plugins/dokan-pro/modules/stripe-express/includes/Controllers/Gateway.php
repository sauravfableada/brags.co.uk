<?php

namespace WeDevs\DokanPro\Modules\StripeExpress\Controllers;

defined( 'ABSPATH' ) || exit; // Exit if called directly

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use WeDevs\DokanPro\Modules\StripeExpress\Blocks\Supports;
use WeDevs\DokanPro\Modules\StripeExpress\PaymentGateways\ApplePay;
use WeDevs\DokanPro\Modules\StripeExpress\PaymentGateways\StripeBlock;
use Automattic\WooCommerce\StoreApi\StoreApi;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use WeDevs\DokanPro\Modules\StripeExpress\Subscriptions\ExtendCartEndpoint;

/**
 * Class for managing Stripe gateway
 *
 * @since 3.6.1
 *
 * @package WeDevs\DokanPro\Modules\StripeExpress\Controllers
 */
class Gateway {

    /**
     * Class constructor
     *
     * @since 3.6.1
     */
    public function __construct() {
        $this->init_classes();
        $this->hooks();
    }

    /**
     * Instantiates necessary classes.
     *
     * @since 3.6.1
     *
     * @return void
     */
    private function init_classes() {
        new ApplePay();
    }

    /**
     * Registers necessary hooks
     *
     * @since 3.6.1
     * @see https://developer.woocommerce.com/docs/cart-and-checkout-payment-method-integration-for-the-checkout-block/#4-server-side-integration
     *
     * @return void
     */
    public function hooks() {
        // Registers Stripe payment gateway
        add_filter( 'woocommerce_payment_gateways', [ $this, 'register_gateway' ] );
        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function ( PaymentMethodRegistry $payment_method_registry ) {
                $payment_method_registry->register( new StripeBlock() );
            }
        );
        $extend = StoreApi::container()->get( ExtendSchema::class );
        ExtendCartEndpoint::init( $extend );
    }

    /**
     * Registers payment gateway
     *
     * @since 3.6.1
     *
     * @param array $gateways
     *
     * @return array
     */
    public function register_gateway( $gateways ) {
        $gateways[] = '\WeDevs\DokanPro\Modules\StripeExpress\PaymentGateways\Stripe';

        return $gateways;
    }
}
