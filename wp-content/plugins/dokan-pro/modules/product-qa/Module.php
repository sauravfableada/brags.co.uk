<?php

namespace WeDevs\DokanPro\Modules\ProductQA;

use WeDevs\Dokan\Traits\ChainableContainer;
use WeDevs\DokanPro\Modules\ProductQA\Admin\AdminMenu;
defined( 'ABSPATH' ) || exit;

/**
 * Class Module.
 * Dokan Pro Product QA Module.
 *
 * @since 3.11.0
 *
 * @property Vendor $vendor Vendor.
 * @property Ajax   $ajax   Ajax Class.
 * @property Emails $emails Emails Class.
 * @property Frontend $frontend Frontend Class.
 * @property Cache  $cache  Cache.
 *
 * @package WeDevs\DokanPro\Modules\ProductQA
 */
class Module {
    use ChainableContainer;

    /**
     * Cloning is forbidden.
     *
     * @since 3.11.0
     */
    public function __clone() {
        $message = ' Backtrace: ' . wp_debug_backtrace_summary();
        _doing_it_wrong( __METHOD__, $message . __( 'Cloning is forbidden.', 'dokan' ), DOKAN_PRO_PLUGIN_VERSION );
    }

    /**
     * Serializing instances of this class is forbidden.
     *
     * @since 3.11.0
     */
    public function __wakeup() {
        $message = ' Backtrace: ' . wp_debug_backtrace_summary();
        _doing_it_wrong( __METHOD__, $message . __( 'serializing instances of this class is forbidden.', 'dokan' ), DOKAN_PRO_PLUGIN_VERSION );
    }

    /**
     * Manager constructor.
     *
     * @since 3.11.0
     *
     * @return void
     */
    public function __construct() {
        $this->define_constants();
        $this->set_controllers();
        $this->init_hooks();

        // Activation and Deactivation hook
        add_action( 'dokan_activated_module_product_qa', [ $this, 'activate' ] );
        add_action( 'dokan_deactivated_module_product_qa', [ $this, 'deactivate' ] );
    }

    /**
     * Define module constants
     *
     * @since 3.11.0
     *
     * @return void
     */
    private function define_constants() {
        define( 'DOKAN_PRODUCT_QA_FILE', __FILE__ );
        define( 'DOKAN_PRODUCT_QA_DIR', dirname( DOKAN_PRODUCT_QA_FILE ) );
        define( 'DOKAN_PRODUCT_QA_INC', DOKAN_PRODUCT_QA_DIR . '/includes/' );
        define( 'DOKAN_PRODUCT_QA_ASSETS', plugins_url( 'assets', DOKAN_PRODUCT_QA_FILE ) );
        define( 'DOKAN_PRODUCT_QA_TEMPLATE_PATH', DOKAN_PRODUCT_QA_DIR . '/templates/' );
    }

    /**
     * Set controllers
     *
     * @since 3.11.0
     *
     * @return void
     */
    private function set_controllers() {
        $this->container['api']        = new Api();
        $this->container['assets']     = new Assets();
        $this->container['cache']      = new Cache();
        $this->container['vendor']     = new Vendor();
        $this->container['admin']      = new Admin();
        $this->container['ajax']       = new Ajax();
        $this->container['emails']     = new Emails();
        $this->container['frontend']   = new Frontend();
        $this->container['admin_list'] = new AdminMenu();
    }

    /**
     * Call all hooks here
     *
     * @since 3.11.0
     *
     * @return void
     */
    public function init_hooks() {
        // set template path
        add_filter( 'dokan_set_template_path', [ $this, 'load_templates' ], 10, 3 );
    }

    /**
     * Set template path for Product Advertisement module
     *
     * @since 3.11.0
     *
     * @return string
     */
    public function load_templates( $template_path, $template, $args ): string {
        if ( ! empty( $args['is_product_qa'] ) ) {
            return untrailingslashit( DOKAN_PRODUCT_QA_TEMPLATE_PATH );
        }

        return $template_path;
    }

    /**
     * This method will be called during module activation
     *
     * @since 3.11.0
     */
    public function activate( $instance ) {
        new Installer();
        $this->flush_rewrite_rules();
    }

    /**
     * This method will be called during module deactivation
     *
     * @since 3.11.0
     */
    public function deactivate( $instance ) {
        $this->flush_rewrite_rules();
    }

    /**
     * Flush rewrite rules.
     *
     * @since 3.11.0
     *
     * @return void
     */
    public function flush_rewrite_rules() {
        dokan()->rewrite->register_rule();
        flush_rewrite_rules();
    }
}
