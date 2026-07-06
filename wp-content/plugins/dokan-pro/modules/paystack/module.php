<?php

namespace WeDevs\DokanPro\Modules\Paystack;

defined( 'ABSPATH' ) || exit; // Exit if called directly

use WeDevs\Dokan\Traits\ChainableContainer;
use WeDevs\DokanPro\Modules\Paystack\Checkout\CheckoutHandler;
use WeDevs\DokanPro\Modules\Paystack\Orders\OrderController;
use WeDevs\DokanPro\Modules\Paystack\Orders\OrderManager;
use WeDevs\DokanPro\Modules\Paystack\Gateway\RegisterGateway;
use WeDevs\DokanPro\Modules\Paystack\Gateway\WebhookHandler;
use WeDevs\DokanPro\Modules\Paystack\Refund\RefundHandler;
use WeDevs\DokanPro\Modules\Paystack\REST\PaystackController;
use WeDevs\DokanPro\Modules\Paystack\Withdraw\WithdrawMethod;

/**
 * Main class for Paystack module
 *
 * @since 4.1.1
 *
 * @package WeDevs\DokanPro\Modules\Paystack
 */
class Module {

    use ChainableContainer;

    /**
     * Class constructor
     *
     * @since 4.1.1
     *
     * @return void
     */
    public function __construct() {
        $this->constants();
        $this->init_classes();
        $this->hooks();
    }

    /**
     * Define module constants
     *
     * @since 4.1.1
     *
     * @return void
     */
    private function constants() {
        define( 'DOKAN_PAYSTACK_FILE', __FILE__ );
        define( 'DOKAN_PAYSTACK_PATH', dirname( DOKAN_PAYSTACK_FILE ) );
        define( 'DOKAN_PAYSTACK_ASSETS', plugin_dir_url( DOKAN_PAYSTACK_FILE ) . 'assets/' );
        define( 'DOKAN_PAYSTACK_TEMPLATE_PATH', dirname( DOKAN_PAYSTACK_FILE ) . '/templates/' );
    }

    /**
     * Sets all the required classes for the module.
     *
     * @since 4.1.1
     *
     * @return void
     */
    private function init_classes() {
        $this->container['assets']          = new Assets();
        $this->container['withdraw']        = new WithdrawMethod();
        $this->container['gateway']         = new RegisterGateway();
        $this->container['webhook']         = new WebhookHandler();
        $this->container['checkout']        = new CheckoutHandler();
        $this->container['order']           = new OrderController();
        $this->container['order_manager']   = new OrderManager();
        $this->container['refund']          = new RefundHandler();
    }

    /**
     * Registers required hooks.
     *
     * @since 4.1.1
     *
     * @return void
     */
    private function hooks() {
        // Activation and Deactivation hook
        add_action( 'dokan_activated_module_paystack', [ $this, 'activate' ] );
        add_action( 'dokan_deactivated_module_paystack', [ $this, 'deactivate' ] );
        add_filter( 'dokan_rest_api_class_map', [ $this, 'register_routes' ] );
    }

    /**
     * Performs actions upon module activation
     *
     * @since 4.1.1
     *
     * @return void
     */
    public function activate( $instance ) {
        // activate the module
    }

    /**
     * Performs actions upon module deactivation
     *
     * @since 4.1.1
     *
     * @return void
     */
    public function deactivate( $instance ) {
        // deactivate the module
    }

    /**
     * Registers REST API routes for the module.
     *
     * @since 4.1.1
     *
     * @return void
     */
    public function register_routes( $classes ) {
        $classes[ DOKAN_PAYSTACK_PATH . '/includes/REST/PaystackController.php' ] = PaystackController::class;

        return $classes;
    }
}
