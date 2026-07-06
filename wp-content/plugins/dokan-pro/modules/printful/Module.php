<?php

namespace WeDevs\DokanPro\Modules\Printful;


use WeDevs\Dokan\Traits\ChainableContainer;
use WeDevs\DokanPro\Modules\Printful\Providers\OrderProvider;
use WeDevs\DokanPro\Modules\Printful\Providers\ProductProvider;
use WeDevs\DokanPro\Modules\Printful\Providers\ShippingProvider;
use WeDevs\DokanPro\Modules\Printful\Providers\WebhookProvider;

defined( 'ABSPATH' ) || exit;

/**
 * Class Module.
 * Dokan Pro Printful Module.
 *
 * @since 3.13.0
 *
 * @property Assets          $assets  Assets.
 * @property Ajax            $ajax    Ajax.
 * @property Vendor          $vendor  Vendor.
 * @property Admin           $admin   Admin.
 * @property WebhookProvider $webhook Webhook Provider.
 * @property OrderProvider   $order   Order Provider.
 * @property ProductProvider $product Product Provider.
 *
 * @package WeDevs\DokanPro\Modules\Printful
 */
class Module {
    use ChainableContainer;

    /**
     * Manager constructor.
     *
     * @since 3.13.0
     *
     * @return void
     */
    public function __construct() {
        $this->define_constants();
        $this->set_controllers();
        $this->init_hooks();

        // Activation and Deactivation hook
        add_action( 'dokan_activated_module_printful', [ $this, 'activate' ] );
        add_action( 'dokan_deactivated_module_printful', [ $this, 'deactivate' ] );
    }

    /**
     * Define module constants
     *
     * @since 3.13.0
     *
     * @return void
     */
    private function define_constants() {
        define( 'DOKAN_PRINTFUL_FILE', __FILE__ );
        define( 'DOKAN_PRINTFUL_DIR', dirname( DOKAN_PRINTFUL_FILE ) );
        define( 'DOKAN_PRINTFUL_INC', DOKAN_PRINTFUL_DIR . '/includes/' );
        define( 'DOKAN_PRINTFUL_ASSETS', plugins_url( 'assets', DOKAN_PRINTFUL_FILE ) );
        define( 'DOKAN_PRINTFUL_TEMPLATE_PATH', DOKAN_PRINTFUL_DIR . '/templates/' );
    }

    /**
     * Set controllers
     *
     * @since 3.13.0
     *
     * @return void
     */
    private function set_controllers() {
        $this->container['assets']   = new Assets();
        $this->container['ajax']     = new Ajax();
        $this->container['vendor']   = new Vendor();
        $this->container['admin']    = new Admin();
        $this->container['webhook']  = new WebhookProvider();
        $this->container['order']    = new OrderProvider();
        $this->container['product']  = new ProductProvider();
        $this->container['shipping'] = new ShippingProvider();
    }

    /**
     * Call all hooks here
     *
     * @since 3.13.0
     *
     * @return void
     */
    public function init_hooks() {
        // set template path
        add_filter( 'dokan_set_template_path', [ $this, 'set_template_path' ], 10, 3 );
    }

    /**
     * Set template path for Product Advertisement module
     *
     * @since 3.13.0
     *
     * @param string $template_path Current Template path.
     * @param string $template      Current Template.
     * @param array $args           Arguments.
     *
     * @return string
     */
    public function set_template_path( $template_path, $template, $args ): string {
        if ( ! empty( $args['is_printful'] ) ) {
            return untrailingslashit( DOKAN_PRINTFUL_TEMPLATE_PATH );
        }

        return $template_path;
    }

    /**
     * This method will be called during module activation
     *
     * @since 3.13.0
     */
    public function activate( $instance ) {
        new Installer();
        $this->flush_rewrite_rules();
    }

    /**
     * This method will be called during module deactivation
     *
     * @since 3.13.0
     */
    public function deactivate( $instance ) {
        $this->flush_rewrite_rules();
    }

    /**
     * Flush rewrite rules.
     *
     * @since 3.13.0
     *
     * @return void
     */
    public function flush_rewrite_rules() {
        dokan()->rewrite->register_rule();
        flush_rewrite_rules();
    }
}
