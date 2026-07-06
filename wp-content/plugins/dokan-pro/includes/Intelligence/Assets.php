<?php

namespace WeDevs\DokanPro\Intelligence;

use WeDevs\Dokan\Intelligence\Manager;
use WeDevs\Dokan\Intelligence\Services\Model;

class Assets {

    public function __construct() {
        add_action( 'init', [ $this, 'register_all_scripts' ], 99 );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_ai_assets' ], 99 );
    }

    /**
     * Register all scripts
     *
     * @return void
     */
    public function register_all_scripts() {
        $ai_asset_file = plugin_dir_path( DOKAN_PRO_FILE ) . 'assets/js/vendor-dashboard/intelligence/index.asset.php';
        if ( file_exists( $ai_asset_file ) ) {
            $ai_asset = include $ai_asset_file;

            wp_register_script(
                'dokan-pro-ai',
                DOKAN_PRO_PLUGIN_ASSEST . '/js/vendor-dashboard/intelligence/index.js',
                array_merge( $ai_asset['dependencies'], [ 'dokan-react-frontend', 'dokan-hooks' ] ),
                $ai_asset['version'],
                true
            );

            wp_register_style(
                'dokan-pro-ai',
                DOKAN_PRO_PLUGIN_ASSEST . '/js/vendor-dashboard/intelligence/style-index.css',
                [],
                $ai_asset['version']
            );

            wp_set_script_translations( 'dokan-pro-ai', 'dokan' );
        }
    }

    /**
     * Enqueue AI assets
     *
     * @phpcs:disable WordPress.Security.NonceVerification.Recommended
     *
     * @return void
     */
    public function enqueue_ai_assets() {
        global $wp;

        $manager = dokan()->get_container()->get( Manager::class );
        $is_configured = $manager->is_configured( Model::SUPPORTS_IMAGE );
        $is_text_configured = $manager->is_configured();
        $is_enabled = dokan_get_option( 'dokan_ai_image_gen_availability', 'dokan_ai', 'off' ) === 'on';

        $inline_data = [
            'textConfigured' => $is_text_configured,
        ];

        // Load on any product/auction editor; the new React dashboard sets no query var, so also match its layout.
        $is_ai_image_page = isset( $wp->query_vars['products'] )
            || isset( $wp->query_vars['auction'] )
            || isset( $wp->query_vars['new-auction-product'] )
            || dokan_get_option( 'vendor_layout_style', 'dokan_appearance', 'legacy' ) === 'latest';

        if (
            dokan_is_seller_dashboard()
            && $is_ai_image_page
            && $is_configured
            && $is_enabled
        ) {
            wp_enqueue_script( 'dokan-pro-ai' );
            wp_enqueue_style( 'dokan-pro-ai' );
            wp_set_script_translations( 'dokan-pro-ai', 'dokan' );
            wp_add_inline_script( 'dokan-pro-ai', 'const dokanProAi = ' . wp_json_encode( $inline_data ), 'before' );
        }
    }
}
