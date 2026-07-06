<?php

namespace WeDevs\DokanPro\Modules\Printful;

use WeDevs\Dokan\Traits\ChainableContainer;
use WeDevs\DokanPro\Modules\Printful\Auth\UserMetaTokenStore;
use WeDevs\DokanPro\Modules\Printful\Vendor\Connect;
use WeDevs\DokanPro\Modules\Printful\Vendor\Disconnect;
use WeDevs\DokanPro\Modules\Printful\Vendor\Settings as VendorSettings;
use WeDevs\DokanPro\Modules\Printful\Admin\Settings as AdminSettings;

defined( 'ABSPATH' ) || exit;

/**
 * Class Vendor
 *
 * Responsible for vendor specific functionality.
 *
 * @since 3.13.0
 *
 * @property Settings $settings Settings class instance.
 *
 * @package WeDevs\DokanPro\Modules\Printful
 */
class Vendor {
    use ChainableContainer;

    /**
     * Class constructor.
     */
    public function __construct() {
        if ( ! $this->is_printful_ready() ) {
            return;
        }

        $this->container['settings']   = new VendorSettings();
        $this->container['connect']    = new Connect();
        $this->container['disconnect'] = new Disconnect();

        add_filter( 'dokan_get_all_cap', [ $this, 'add_capabilities' ] );
        add_filter( 'dokan_get_dashboard_nav', [ $this, 'register_printful_menu' ], 20 );
    }

    /**
     * Add capabilities to vendor.
     *
     * @param array $capabilities Array of capabilities.
     *
     * @return array
     */
    public function add_capabilities( array $capabilities ): array {
        $capabilities['menu']['dokan_view_store_printful_menu'] = __( 'View Printful menu', 'dokan' );
        $capabilities['menu']['dokan_view_store_printful_settings_menu'] = __( 'View Printful settings menu', 'dokan' );

        return $capabilities;
    }

    /**
     * Registers Printful Menu on Vendor Dashboard.
     *
     * @since 3.13.0
     *
     * @param array $urls Menu URL data
     *
     * @return array $urls
     */
    public function register_printful_menu( $urls ) {
        $vendor_id         = dokan_get_current_user_id();
        $printful_store_id = get_user_meta( $vendor_id, UserMetaTokenStore::KEY_STORE_ID, true );

		if ( ! $printful_store_id ) {
			return $urls;
		}

        $icon = '<svg width="22" height="11" viewBox="0 0 22 11" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M6.29945 0.988281L0.78125 10.459H5.12793L8.47827 4.72183L6.29945 0.988281Z" fill="white"/>
            <path d="M10.7447 10.459H11.8177L11.2812 9.53926L10.7447 10.459Z" fill="white"/>
            <path d="M8.79578 5.26923L5.76296 10.459H10.1206L10.9636 9.00277L8.79578 5.26923Z" fill="white"/>
            <path d="M11.2813 8.45537L13.4601 4.72183L11.2813 0.988281L9.11347 4.72183L11.1281 8.1926L11.2813 8.45537Z" fill="white"/>
            <path d="M11.5992 9.00277L12.4532 10.459H16.7999L13.778 5.26923L11.5992 9.00277Z" fill="white"/>
            <path d="M14.0952 4.72183L17.4346 10.459H21.7813L16.263 0.988281L14.0952 4.72183Z" fill="white"/>
        </svg>';

        $urls['printful-dashboard'] = [
            'title'  => __( 'Printful', 'dokan' ),
            'icon'   => $icon,
            'url'    => sprintf( 'https://www.printful.com/dashboard/sync?store=%s', $printful_store_id ),
            'pos'    => 52,
            'target' => '_blank',
        ];

        return $urls;
    }

    /**
     * Is Printful Ready.
     *
     * @since 3.13.0
     *
     * @return bool
     */
    public function is_printful_ready() {
        if ( empty( dokan_get_option( 'app_id', 'dokan_printful', '' ) ) ) {
            return false;
        }


        if ( empty( dokan_get_option( 'app_secret', 'dokan_printful', '' ) ) ) {
            return false;
        }


        $admin_settings = new AdminSettings();

        if ( ! ( $admin_settings->shipping_fee_recipient_is_seller() && $admin_settings->shipping_tax_fee_recipient_is_seller() ) ) {
            return false;
        }

        if ( ! $admin_settings->is_printful_supported_currency( get_woocommerce_currency() ) ) {
            return false;
        }

        return true;
    }
}
