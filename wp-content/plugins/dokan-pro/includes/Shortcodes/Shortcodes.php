<?php

namespace WeDevs\DokanPro\Shortcodes;

// don't call the file directly
use WeDevs\Dokan\Traits\ChainableContainer;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Shortcodes {

    use ChainableContainer;

    /**
     * Shortcodes container
     *
     * @since 3.7.25
     */
    public function __construct() {
        $this->set_controllers();
    }

    /**
     * Set controllers
     *
     * @since 3.7.25
     * @since 3.16.1 `[dokan-customer-migration]` shortcode migrated to lite.
     *
     * @return void
     */
    private function set_controllers() {}
}
