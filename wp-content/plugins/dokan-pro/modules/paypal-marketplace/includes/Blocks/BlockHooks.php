<?php

namespace WeDevs\DokanPro\Modules\PayPalMarketplace\Blocks;

/**
 * Dokan PayPal Marketplace Woocommerce block support class.
 *
 * @since 3.15.0
 */
class BlockHooks {

    /**
     * Class constructor.
     *
     * @since 3.15.0
     *
     * @return void
     */
    public function __construct() {
        add_action( 'woocommerce_blocks_loaded', [ $this, 'checkout_blocks_support' ] );
    }

    /**
     * Register cart checkout block support class.
     * As mentioned in woocommerce docs the class needs to be hooked in 'woocommerce_blocks_loaded` hook.
     *
     * @since 3.15.0
     *
     * @return void
     */
    public function checkout_blocks_support() {
        if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
            return;
        }

        add_action( 'woocommerce_blocks_payment_method_type_registration', [ $this, 'register_block_support_class' ] );
    }

    /**
     * Registering the block support class.
     *
     * @param \Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry
     *
     * @return void
     */
    public function register_block_support_class( $payment_method_registry ) {
        $payment_method_registry->register( new CartCheckoutBlockSupport() );
    }
}
