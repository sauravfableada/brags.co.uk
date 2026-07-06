<?php

namespace WeDevs\DokanPro\Modules\Paystack\Gateway;

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use WeDevs\DokanPro\Modules\Paystack\Support\Helper;

class RegisterGateway {

    /**
     * RegisterGateways constructor.
     *
     * @since 4.1.1
     *
     * @return void
     */
    public function __construct() {
        $this->hooks();
    }

    /**
     * Init all the hooks.
     *
     * @since 4.1.1
     *
     * @return void
     */
    private function hooks() {
        add_filter( 'woocommerce_payment_gateways', [ $this, 'register_gateway' ] );
        add_action( 'woocommerce_blocks_payment_method_type_registration', [ $this, 'register_payment_method_type' ] );
    }

    /**
     * Register payment gateway.
     *
     * @since 4.1.1
     *
     * @param array $gateways
     *
     * @return array
     */
    public function register_gateway( array $gateways ): array {
        $gateways[] = Paystack::class;

        return $gateways;
    }

    public function register_payment_method_type( PaymentMethodRegistry $payment_method ) {
        $payment_method->register( new PaystackPaymentMethodType() );
    }
}
