<?php
namespace WeDevs\DokanPro\MenuManager;

defined( 'ABSPATH' ) || exit;

use WeDevs\Dokan\Traits\ChainableContainer;

/**
 * Menu Manager Controller
 *
 * @since 3.10.0
 */
class Controller {
    use ChainableContainer;

    /**
     * Class constructor
     *
     * @since 3.10.0
     *
     * @return void
     */
    public function __construct() {
        if ( version_compare( dokan()->version, '3.10.0', '>=' ) ) {
            $this->init_classes();
        }
    }

    /**
     * Initializes all classes
     *
     * @since 3.10.0
     *
     * @return void
     */
    protected function init_classes() {
        $this->container['admin_settings']    = new Admin\Settings();
        $this->container['admin_data_source'] = new Admin\DataSource();
        $this->container['hooks']             = new Hooks();
    }
}
