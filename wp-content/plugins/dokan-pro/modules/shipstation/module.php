<?php

namespace WeDevs\DokanPro\Modules\ShipStation;

class Module {

    /**
     * Module version
     *
     * @var string
     *
     * @since 1.0.0
     */
    public $version = '1.0.0';

    /**
     * Class constructor
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function __construct() {
        $this->define_constants();
        $this->includes();
        $this->instances();
        $this->init_hooks();
    }

    /**
     * Module constants
     *
     * @since 1.0.0
     *
     * @return void
     */
    private function define_constants() {
        define( 'DOKAN_SHIPSTATION_VERSION', $this->version );
        define( 'DOKAN_SHIPSTATION_PATH', __DIR__ );
        define( 'DOKAN_SHIPSTATION_INCLUDES', DOKAN_SHIPSTATION_PATH . '/includes' );
        define( 'DOKAN_SHIPSTATION_URL', plugins_url( '', __FILE__ ) );
        define( 'DOKAN_SHIPSTATION_ASSETS', DOKAN_SHIPSTATION_URL . '/assets' );
        define( 'DOKAN_SHIPSTATION_VIEWS', DOKAN_SHIPSTATION_PATH . '/views' );
        define( 'DOKAN_SHIPSTATION_EXPORT_LIMIT', 100 );
    }

    /**
     * Include module related PHP files
     *
     * @since 1.0.0
     *
     * @return void
     */
    private function includes() {
        require_once DOKAN_SHIPSTATION_INCLUDES . '/functions.php';
    }

    /**
     * Create module related class instances
     *
     * @since 1.0.0
     *
     * @return void
     */
    private function instances() {
        new ShipStationHooks();
        new Settings();
        new ConflictResolution();
    }

    /**
     * Call all hooks here.
     *
     * @since 3.14.4
     *
     * @return void
     */
    public function init_hooks() {
        // Include rest api class.
        add_filter( 'dokan_rest_api_class_map', [ $this, 'rest_api_class_map' ] );
    }

    /**
     * Rest api class map
     *
     * @param array $classes
     *
     * @since 3.14.4
     *
     * @return array
     */
    public function rest_api_class_map( $classes ) {
        $classes[ DOKAN_SHIPSTATION_INCLUDES . '/REST/VendorCredentialsApi.php' ] = '\WeDevs\DokanPro\Modules\ShipStation\REST\VendorCredentialsApi';
        $classes[ DOKAN_SHIPSTATION_INCLUDES . '/REST/VendorOrderStatusApi.php' ] = '\WeDevs\DokanPro\Modules\ShipStation\REST\VendorOrderStatusApi';
        $classes[ DOKAN_SHIPSTATION_INCLUDES . '/REST/VendorOrdersController.php' ] = '\WeDevs\DokanPro\Modules\ShipStation\REST\VendorOrdersController';
        $classes[ DOKAN_SHIPSTATION_INCLUDES . '/REST/VendorInventoryController.php' ] = '\WeDevs\DokanPro\Modules\ShipStation\REST\VendorInventoryController';
        $classes[ DOKAN_SHIPSTATION_INCLUDES . '/REST/VendorDiagnosticsController.php' ] = '\WeDevs\DokanPro\Modules\ShipStation\REST\VendorDiagnosticsController';

        return $classes;
    }
}
