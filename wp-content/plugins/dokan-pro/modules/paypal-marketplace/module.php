<?php

namespace WeDevs\DokanPro\Modules\PayPalMarketplace;

use WeDevs\Dokan\Traits\ChainableContainer;
use WeDevs\DokanPro\Modules\PayPalMarketplace\BackgroundProcess\DelayDisburseFund;
use WeDevs\DokanPro\Modules\PayPalMarketplace\Blocks\BlockHooks;
use WeDevs\DokanPro\Modules\PayPalMarketplace\Cart\CartHandler;
use WeDevs\DokanPro\Modules\PayPalMarketplace\Order\OrderController;
use WeDevs\DokanPro\Modules\PayPalMarketplace\Order\OrderManager;
use WeDevs\DokanPro\Modules\PayPalMarketplace\PaymentMethods\PayPal;
use WeDevs\DokanPro\Modules\PayPalMarketplace\Gateways\RegisterGateways;
use WeDevs\DokanPro\Modules\PayPalMarketplace\REST\V1\PayPalController;
use WeDevs\DokanPro\Modules\PayPalMarketplace\Subscriptions\VendorSubscription;
use WeDevs\DokanPro\Modules\PayPalMarketplace\WithdrawMethods\RegisterWithdrawMethods;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class Module
 *
 * @since   3.3.0
 * @package WeDevs\Dokan\Gateways
 *
 * @see     https://developer.paypal.com/docs/platforms/ For Related API's
 *
 * @property-read RegisterGateways $register_gateways Register payment gateway
 * @property-read WebhookHandler $webhook Register webhook
 * @property-read PayPal $gateway_paypal PayPal gateway
 * @property-read OrderManager $order_manager Order manager
 * @property-read RegisterWithdrawMethods $withdraw_methods Register withdraw methods
 * @property-read CartHandler $cart_handler Cart handler
 * @property-read OrderController $order_controller Order controller
 * @property-read Refund $refund Refund controller
 * @property-read DelayDisburseFund $delay_disburse_bg Delay disburse fund background process
 * @property-read VendorSubscription $vendor_subscription Vendor subscription
 * @property-read Hooks $hooks Hooks class
 * @property-read ReverseWithdrawal $reverse_withdrawal Reverse withdrawal
 */
class Module {

    use ChainableContainer;

    /**
     * @var string
     */
    private static $class_name; // @phpstan-ignore-line

    /**
     * Manager constructor.
     *
     * @since 3.3.0
     * @return void
     */
    public function __construct() {
        $this->define_constants();
        $this->set_controllers();

        // Activation and Deactivation hook
        add_action( 'dokan_activated_module_paypal_marketplace', [ $this, 'activate' ], 10, 1 );
        add_action( 'dokan_deactivated_module_paypal_marketplace', [ $this, 'deactivate' ], 10, 1 );
        add_action( 'rest_api_init', [ $this, 'register_rest_route' ], 10 );
    }

    public function register_rest_route() {
        if ( ! isset( $this->container['paypal_controller'] ) ) {
            return;
        }

        $this->container['paypal_controller']->register_routes();
    }

    /**
     * Define module constants
     *
     * @since 3.3.0
     *
     * @return void
     */
    private function define_constants() {
        define( 'DOKAN_PAYPAL_MP_FILE', __FILE__ );
        define( 'DOKAN_PAYPAL_MP_PATH', dirname( DOKAN_PAYPAL_MP_FILE ) );
        define( 'DOKAN_PAYPAL_MP_ASSETS', plugin_dir_url( DOKAN_PAYPAL_MP_FILE ) . 'assets/' );
        define( 'DOKAN_PAYPAL_MP_TEMPLATE_PATH', dirname( DOKAN_PAYPAL_MP_FILE ) . '/templates/' );
    }

    /**
     * Set controllers
     *
     * @since 3.3.0
     *
     * @return void
     */
    private function set_controllers() {
        $this->container['register_gateways'] = new RegisterGateways();
        $this->container['webhook']           = new WebhookHandler();

        // return if payment gateway is not enabled
        if ( ! Helper::is_enabled() ) {
            return;
        }

        $this->container['gateway_paypal']      = new PayPal();
        $this->container['order_manager']       = new OrderManager();
        $this->container['withdraw_methods']    = new RegisterWithdrawMethods();
        $this->container['cart_handler']        = new CartHandler();
        $this->container['order_controller']    = new OrderController();
        $this->container['refund']              = new Refund();
        $this->container['delay_disburse_bg']   = new DelayDisburseFund();
        $this->container['vendor_subscription'] = new VendorSubscription();
        $this->container['hooks']               = new Hooks();
        $this->container['reverse_withdrawal']  = new ReverseWithdrawal();
        $this->container['block_hooks']         = new BlockHooks();
        $this->container['paypal_controller']   = new PayPalController();
    }

    /**
     *
     * @since 3.3.0
     */
    public function activate( $instance ) {
        $instance->container['webhook']->register_webhook();

        if ( ! wp_next_scheduled( 'dokan_paypal_mp_daily_schedule' ) ) {
            wp_schedule_event( time(), 'daily', 'dokan_paypal_mp_daily_schedule' );
        }
    }

    /**
     *
     * @since 3.3.0
     */
    public function deactivate( $instance ) {
        $instance->container['webhook']->deregister_webhook();

        // clear scheduled task
        wp_clear_scheduled_hook( 'dokan_paypal_mp_daily_schedule' );

        // delete transient used for this module
        delete_transient( '_dokan_paypal_marketplace_access_token' );
        delete_transient( '_dokan_paypal_marketplace_client_token' );
    }
}
