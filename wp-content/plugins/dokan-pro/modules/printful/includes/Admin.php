<?php

namespace WeDevs\DokanPro\Modules\Printful;

use WeDevs\Dokan\Traits\ChainableContainer;
use WeDevs\DokanPro\Modules\Printful\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Class Admin.
 *
 * Responsible for admin related functionality.
 *
 * @since 3.13.0
 *
 * @property Settings $settings Settings class instance.
 *
 * @package WeDevs\DokanPro\Modules\Printful
 */
class Admin {
    use ChainableContainer;

    /**
     * Class constructor.
     */
    public function __construct() {
        $this->container['settings'] = new Settings();
    }
}
