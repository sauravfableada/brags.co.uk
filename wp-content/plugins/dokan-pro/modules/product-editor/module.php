<?php

namespace WeDevs\DokanPro\Modules\ProductEditor;

use WeDevs\Dokan\Traits\ChainableContainer;
use WeDevs\DokanPro\Modules\ProductEditor\Admin\FormSettings;
use WeDevs\DokanPro\Modules\ProductEditor\REST\ProductEditorController;

defined( 'ABSPATH' ) || exit;

/**
 * Class Module.
 *
 * @since 5.0.0
 *
 * @package WeDevs\DokanPro\Modules\ProductEditor
 */
final class Module {

    use ChainableContainer;

    /**
     * Cloning is forbidden.
     *
     * @since 5.0.0
     */
    public function __clone() {
        $message = ' Backtrace: ' . wp_debug_backtrace_summary();
        _doing_it_wrong( __METHOD__, $message . __( 'Cloning is forbidden.', 'dokan' ), DOKAN_PRO_PLUGIN_VERSION );
    }

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 5.0.0
     */
    public function __wakeup() {
        $message = ' Backtrace: ' . wp_debug_backtrace_summary();
        _doing_it_wrong( __METHOD__, $message . __( 'Unserializing instances of this class is forbidden.', 'dokan' ), DOKAN_PRO_PLUGIN_VERSION );
    }

    /**
     * Class Constructor.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function __construct() {
        $this->define_constants();
        $this->set_controllers();
        // register REST routes.
        add_filter( 'dokan_rest_api_class_map', [ $this, 'register_rest_routes' ] );

        // Activation and Deactivation Hooks.
        add_action( 'dokan_activated_module_product_editor', [ $this, 'activate' ], 10, 1 );
        add_action( 'dokan_deactivated_module_product_editor', [ $this, 'deactivate' ], 10, 1 );
    }

    /**
     * Define module constants.
     *
     * @since 5.0.0
     *
     * @return void
     */
    private function define_constants() {
        define( 'DOKAN_PRODUCT_EDITOR_FILE', __FILE__ );
        define( 'DOKAN_PRODUCT_EDITOR_DIR', dirname( DOKAN_PRODUCT_EDITOR_FILE ) );
        define( 'DOKAN_PRODUCT_EDITOR_INC', DOKAN_PRODUCT_EDITOR_DIR . '/includes/' );
        define( 'DOKAN_PRODUCT_EDITOR_ASSETS', plugins_url( 'assets', DOKAN_PRODUCT_EDITOR_FILE ) );
        define( 'DOKAN_PRODUCT_EDITOR_TEMPLATE_PATH', DOKAN_PRODUCT_EDITOR_DIR . '/templates/' );
    }

    /**
     * Set controllers.
     *
     * @since 5.0.0
     *
     * @return void
     */
    private function set_controllers() {
        $this->container['form_settings'] = new FormSettings();
    }

    /**
     * Register module REST routes.
     *
     * @since 5.0.0
     *
     * @param array $classes Existing class map.
     *
     * @return array
     */
    public function register_rest_routes( array $classes ): array {
        $classes[ DOKAN_PRODUCT_EDITOR_INC . 'REST/ProductEditorController.php' ] = ProductEditorController::class;

        return $classes;
    }

    /**
     * This method will be called during module activation.
     *
     * @since 5.0.0
     */
    public function activate() {
        // Activation tasks.
    }

    /**
     * This method will be called during module deactivation.
     *
     * @since 5.0.0
     */
    public function deactivate() {
        // Deactivation tasks.
    }
}
