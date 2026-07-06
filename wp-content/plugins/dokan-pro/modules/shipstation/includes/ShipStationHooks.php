<?php

namespace WeDevs\DokanPro\Modules\ShipStation;

class ShipStationHooks {

    /**
     * Class constructor
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function __construct() {
        add_action( 'woocommerce_api_wc_shipstation', array( $this, 'init_shipstation_api' ) );
    }

    /**
     * Init ShipStation API
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function init_shipstation_api() {
        new ShipStationApi();
    }
}
