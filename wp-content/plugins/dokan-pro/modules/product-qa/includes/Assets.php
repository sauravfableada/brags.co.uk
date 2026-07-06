<?php

namespace WeDevs\DokanPro\Modules\ProductQA;

defined( 'ABSPATH' ) || exit;

/**
 * Assets related class.
 *
 * @since 3.11.0
 */
class Assets {
    /**
     * Constructor.
     */
    public function __construct() {
        // register script and styles
        add_action( 'init', [ $this, 'register' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'load' ] );
        add_action( 'dokan-vue-admin-scripts', [ $this, 'load_admin' ] );
    }

    public function register_dashboard_scripts() {
        $script_assets = DOKAN_PRODUCT_QA_DIR . '/assets/js/dokan-product-qa.asset.php';

        if ( file_exists( $script_assets ) ) {
            $assets                   = include $script_assets;
            $component_handler        = 'dokan-react-frontend';
            $assets['dependencies'][] = $component_handler;

            wp_register_script(
                'dokan-product-qa-react', DOKAN_PRODUCT_QA_ASSETS . '/js/dokan-product-qa.js',
                $assets['dependencies'],
                $assets['version'],
                true
            );
            $product_url = dokan_get_navigation_url( 'products' );
            wp_localize_script(
                'dokan-product-qa-react',
                'dokanProductQa',
                [
					'productUrl' => $product_url,
				]
            );
        }
    }

    /**
     * Register all scripts
     *
     * @since 3.11.0
     * @return void
     */
    public function register() {
        list( $suffix, $version ) = dokan_get_script_suffix_and_version();

        // Register all js
        wp_register_script(
            'dokan-product-qa-vendor', DOKAN_PRODUCT_QA_ASSETS . '/js/vendor.js',
            [ 'jquery', 'dokan-sweetalert2', 'dokan-frontend', 'dokan-select2-js' ], $version, true
        );
        $script_assets = DOKAN_PRODUCT_QA_DIR . '/assets/js/product.asset.php';

        if ( file_exists( $script_assets ) ) {
            $assets                   = include $script_assets;
            $assets['dependencies'] = array_merge(
                $assets['dependencies'],
                [
                    'dokan-sweetalert2',
                    'wp-core-data',
                    'react',
                    'react-dom',
                ]
            );

            wp_register_script(
                'dokan-product-qa-frontend',
                DOKAN_PRODUCT_QA_ASSETS . '/js/product.js',
                $assets['dependencies'],
                $assets['version'],
                true
            );
        }

        wp_localize_script(
            'dokan-product-qa-frontend',
            'productQAGlobal',
            [
                'url'              => get_rest_url(),
                'nonce'            => wp_create_nonce( 'wp_rest' ),
                'locale'           => str_replace( '_', '-', get_user_locale() ), // We need local as `bd-BD` format instead of `bn_BD`.
                'account_endpoint' => esc_url( trailingslashit( wc_get_account_endpoint_url( 'dashboard' ) ) ),
            ]
        );

        // register all css
        wp_register_style(
            'dokan-product-qa-vendor', DOKAN_PRODUCT_QA_ASSETS . '/css/vendor.css',
            [ 'dokan-select2-css' ], $version
        );

        wp_register_style(
            'dokan-product-qa-admin-vue',
            DOKAN_PRODUCT_QA_ASSETS . '/js/admin.css',
            [],
            $version
        );
        wp_register_script(
            'dokan-product-qa-admin-vue',
            DOKAN_PRODUCT_QA_ASSETS . '/js/admin.js',
            [ 'jquery', 'dokan-vue-vendor', 'dokan-vue-bootstrap' ],
            $version,
            true
        );

        // Add translations for frontend.
        wp_set_script_translations(
            'dokan-product-qa-frontend',
            'dokan',
            plugin_dir_path( DOKAN_PRO_FILE ) . 'languages'
        );

        wp_set_script_translations(
            'dokan-product-qa-admin-vue',
            'dokan',
            plugin_dir_path( DOKAN_PRO_FILE ) . 'languages'
        );
        $this->register_dashboard_scripts();
    }

    /**
     * Load scripts and styles.
     *
     * @return void
     */
    public function load() {
        global $wp;
        $dashboard = dokan_is_seller_dashboard();

        if ( $dashboard ) {
            // Enqueue frontend scripts and styles.
            wp_enqueue_script( 'dokan-product-qa-react' );
            wp_set_script_translations( 'dokan-product-qa-react', 'dokan' );
        }

        if ( ! $dashboard || ! isset( $wp->query_vars[ Vendor::QUERY_VAR ] ) ) {
            return;
        }

        wp_enqueue_script( 'dokan-product-qa-vendor' );
        wp_enqueue_style( 'dokan-product-qa-vendor' );
    }

    /**
     * Load scripts and styles for admin.
     *
     * @return void
     */
    public function load_admin() {

        if ( ! is_admin() ) {
            return;
        }

        wp_enqueue_script( 'dokan-product-qa-admin-vue' );
        wp_enqueue_style( 'dokan-product-qa-admin-vue' );
    }
}
