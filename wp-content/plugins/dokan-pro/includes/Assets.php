<?php

namespace WeDevs\DokanPro;

use Automattic\WooCommerce\Internal\Admin\WCAdminAssets;

/**
 * Scripts and Styles Class
 */
class Assets {

    private $script_version;

    private $suffix;

    public function __construct() {
        list( $this->suffix, $this->script_version ) = dokan_get_script_suffix_and_version();

        if ( is_admin() ) {
            add_action( 'admin_enqueue_scripts', [ $this, 'register' ], 5 );
            add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_analytics_scripts' ], 25 );
            add_action( 'dokan-vue-admin-scripts', [ $this, 'enqueue_admin_scripts' ] );
            add_filter( 'dokan_admin_localize_script', [ $this, 'add_localized_data' ], 5 );
            add_filter( 'dokan_admin_dashboard_localize_scripts', [ $this, 'add_dashboard_localized_data' ], 5 );
        } else {
            add_action( 'wp_enqueue_scripts', [ $this, 'register' ], 5 );
            add_action( 'dokan_enqueue_scripts', [ $this, 'enqueue_frontend_scripts' ], 5 );
            add_filter( 'dokan_localized_args', [ $this, 'add_i18_localized_data' ], 5 );
        }
    }

    /**
     * Enqueue admin scripts
     *
     * @return void
     */
    public function enqueue_admin_scripts() {
        global $wp_version;

        wp_enqueue_style( 'dokan-pro-vue-admin' );
        wp_enqueue_script( 'dokan-pro-vue-admin' );

        if ( version_compare( $wp_version, '5.3', '<' ) ) {
            wp_enqueue_style( 'dokan-pro-wp-version-before-5-3' );
        }
    }

    /**
     * Enqueue admin analytics scripts
     *
     * @param string $hook
     *
     * @return void
     */
    public function enqueue_admin_analytics_scripts( $hook ) {
		// Enqueue the scripts for seller filter in analytics report.
		wp_enqueue_script( 'dokan-pro-react-admin' );
        wp_enqueue_style( 'dokan-pro-tailwind' );

		if ( 'dokan_page_dokan-dashboard' === $hook ) {
			wp_enqueue_script( 'dokan-pro-admin-dashboard' );
		}

        // Whitelisted admin pages for enqueueing support ticket scripts.
        $admin_pages = apply_filters(
            'dokan_admin_support_ticket_enqueue_pages',
            [
                'toplevel_page_dokan',
                'dokan_page_dokan-dashboard',
            ]
        );

        // Support ticket modal — enqueue on all Dokan admin pages.
        if ( in_array( $hook, $admin_pages, true ) ) {
            wp_enqueue_script( 'dokan-admin-support-ticket' );
            wp_enqueue_style( 'dokan-admin-support-ticket' );
        }
    }

    /**
     * This method will enqueue dokan pro localize data
     *
     * @since 3.1.1
     * @param array $data
     * @return array
     */
    public function add_localized_data( $data ) {
        $data['dokan_pro_i18n'] = array( 'dokan' => dokan_get_jed_locale_data( 'dokan', DOKAN_PRO_DIR . '/languages/' ) );
        $data['current_plan']   = dokan_pro()->license->get_plan();
        $data['active_modules'] = dokan_pro()->module->get_active_modules();
        $data['pro_has_license_key'] = dokan_pro()->license->has_license_key();
        return $data;
    }

    /**
     * Add localized data for the new React admin dashboard.
     *
     * @since DOKAN_PRO
     *
     * @param array $data Existing localized data.
     *
     * @return array
     */
    public function add_dashboard_localized_data( $data ) {
        $data['active_modules'] = dokan_pro()->module->get_active_modules();

        return $data;
    }


    /**
     * Get social asset config
     *
     * @since 4.0.4
     *
     * @return mixed|null
     */
    private function get_social_asset_config() {
		$social_asset = DOKAN_PRO_DIR . '/assets/js/dokan-settings-social.asset.php';
		return file_exists( $social_asset ) ? include $social_asset : null;
	}

    /**
     * Enqueue forntend scripts
     *
     * @since 2.8.0
     *
     * @return void
     */
    public function enqueue_frontend_scripts() {
        global $wp;

        $continents        = WC()->countries->get_continents();
        $allowed_countries = WC()->countries->get_allowed_countries();
        $continents_data   = array();
        $vendor            = dokan()->vendor->get( dokan_get_current_user_id() );

        if ( $continents && is_array( $continents ) ) {
            foreach ( $continents as $continent => $countries ) {
                if ( isset( $countries['countries'] ) && isset( $countries['name'] ) && is_array( $countries['countries'] ) ) {
                    $continents_data[ $continent ]['name'] = $countries['name'];
                    $countries_data = array();

                    foreach ( $countries['countries'] as $country ) {
                        if ( array_key_exists( $country, $allowed_countries ) ) {
                            $countries_data[] = $country;
                        }
                    }
                    $continents_data[ $continent ]['countries'] = $countries_data;
                }
            }
        }

	    $disable_woo_shipping    = get_option( 'woocommerce_ship_to_countries' );
	    $dokan_shipping_settings = get_option( 'woocommerce_dokan_product_shipping_settings' );
	    $enabled_dokan_shipping  = $dokan_shipping_settings['enabled'] ?? 'yes';
        $localize_array          = array(
            'nonce'                 => wp_create_nonce( 'dokan_shipping_nonce' ),
            'allowed_countries'     => WC()->countries->get_allowed_countries(),
            'continents'            => ! empty( $continents_data ) ? $continents_data : $continents,
            'states'                => WC()->countries->get_states(),
            'shipping_class'        => WC()->shipping->get_shipping_classes(),
            'processing_time'       => dokan_get_shipping_processing_times(),
            'dashboardUrl'          => dokan_get_navigation_url(),
            'vendorShopUrl'         => $vendor->get_shop_url() ?? '',
	        'legacy_shipping_url'   => dokan_get_navigation_url( 'settings/regular-shipping' ),
	        'enable_woo_shipping'   => $disable_woo_shipping !== 'disabled',
	        'enable_dokan_shipping' => $enabled_dokan_shipping !== 'no',
        );

        if ( dokan_is_seller_dashboard() ) {
            wp_enqueue_style( 'dokan-pro-frontend-shipping' );
            wp_enqueue_script( 'dokan-pro-frontend-shipping' );
            wp_localize_script( 'dokan-pro-frontend-shipping', 'dokanShippingHelper', $localize_array );
        }
        if ( isset( $wp->query_vars['settings'] ) && $wp->query_vars['settings'] === 'shipping' ) {
            wp_enqueue_style( 'dokan-vue-bootstrap' );
            wp_enqueue_style( 'dokan-pro-vue-frontend-shipping' );
            wp_enqueue_script( 'dokan-pro-vue-frontend-shipping' );
            wp_localize_script( 'dokan-pro-vue-frontend-shipping', 'dokanShipping', $localize_array );
        }

        // Load dokan store times assets in store page.
        if ( isset( $wp->query_vars['settings'] ) && $wp->query_vars['settings'] === 'store' ) {
            wp_enqueue_style( 'dokan-pro-store-times' );
        }

        if ( dokan_is_seller_dashboard() ) {
            // Enqueue the scripts for seller filter in analytics report.
            wp_enqueue_script( 'dokan-pro-react-vendor-analytics' );

            wp_enqueue_script( 'dokan-pro-withdraw' );
            wp_enqueue_style( 'dokan-pro-tailwind' );
            wp_enqueue_style( 'dokan-pro-withdraw' );
            wp_enqueue_script( 'dokan-dashboard-product-reviews' );
            // Add localized data for the product reviews.
            $vendor_review_change = dokan_get_option( 'seller_review_manage', 'dokan_selling', 'on' );
            wp_localize_script(
                'dokan-dashboard-product-reviews',
                'dokanReviewsSettings',
                array(
                    'vendor_review_manage' => $vendor_review_change,
				)
            );
            // Product list actions (duplicate, etc.).
            $this->enqueue_product_list_actions_script();

            // Product editor variations — vendor dashboard.
            $this->enqueue_product_editor_vendor_dashboard_script();

            // Load dokan pro announcement assets in store page.
            wp_enqueue_script( 'dokan-pro-announcement' );
            wp_set_script_translations( 'dokan-pro-announcement', 'dokan' );

            // store seo
            wp_enqueue_script( 'dokan-pro-store-seo' );
            wp_set_script_translations( 'dokan-pro-store-seo', 'dokan' );
            wp_enqueue_script( 'dokan-vendor-dashboard-coupon' );
            wp_enqueue_style( 'dokan-vendor-dashboard-coupon' );
            wp_set_script_translations( 'dokan-vendor-dashboard-coupon', 'dokan' );

            wp_enqueue_script( 'dokan-settings-social' );
            wp_set_script_translations( 'dokan-settings-social', 'dokan' );
            $social_profile_fields = dokan_get_social_profile_fields();
            wp_localize_script(
                'dokan-settings-social', 'dokanSocialProfile', [
					'fields' => $social_profile_fields,
				]
            );

            $coupon_types = dokan_get_coupon_types();
            if ( ! dokan_validate_boolean( dokan_is_single_seller_mode_enable() ) ) {
                unset( $coupon_types['fixed_cart'] );
            }
            $localize_coupon_data = array(
                'coupon_types'      => $coupon_types,
            );
            wp_localize_script( 'dokan-vendor-dashboard-coupon', 'dokanCoupon', $localize_coupon_data );
        }
    }

    /**
     * Register our app scripts and styles
     *
     * @return void
     */
    public function register() {
        $this->register_scripts( $this->get_scripts() );
        $this->register_styles( $this->get_styles() );
    }

    /**
     * Register scripts
     *
     * @param  array $scripts
     *
     * @return void
     */
    private function register_scripts( $scripts ) {
        foreach ( $scripts as $handle => $script ) {
            $deps      = isset( $script['deps'] ) ? $script['deps'] : false;
            $in_footer = isset( $script['in_footer'] ) ? $script['in_footer'] : false;
            $version   = isset( $script['version'] ) ? $script['version'] : DOKAN_PRO_PLUGIN_VERSION;

            wp_register_script( $handle, $script['src'], $deps, $version, $in_footer );
            wp_set_script_translations( $handle, 'dokan', plugin_dir_path( DOKAN_PRO_FILE ) . 'languages' );
        }
    }

    /**
     * Register styles
     *
     * @param  array $styles
     *
     * @return void
     */
    public function register_styles( $styles ) {
        foreach ( $styles as $handle => $style ) {
            $deps    = isset( $style['deps'] ) ? $style['deps'] : false;
            $version = isset( $style['version'] ) ? $style['version'] : DOKAN_PRO_PLUGIN_VERSION;

            wp_register_style( $handle, $style['src'], $deps, $version );
        }
    }

    /**
     * Get all registered scripts
     *
     * @return array
     */
    public function get_scripts() {
	    // Register WooCommerce Admin Assets for the React-base Dokan Vendor ler dashboard.
	    if ( ! function_exists( 'get_current_screen' ) ) {
		    require_once ABSPATH . '/wp-admin/includes/screen.php';
	    }

	    add_filter( 'doing_it_wrong_trigger_error', [ $this, 'disable_doing_it_wrong_error' ] );

        $wc_instance = WCAdminAssets::get_instance();
        $wc_instance->register_scripts();

	    remove_filter( 'doing_it_wrong_trigger_error', [ $this, 'disable_doing_it_wrong_error' ] );

        $react_assets = include DOKAN_PRO_DIR . '/assets/js/admin-react.asset.php';
        $react_deps = $react_assets['dependencies'] ?? [];

        // Analytics report scripts.
        $react_vendor_analytics_assets = include DOKAN_PRO_DIR . '/assets/js/vendor-dashboard/reports/index.asset.php';
        $react_vendor_analytics_deps   = $react_vendor_analytics_assets['dependencies'] ?? [];
        $report_version = $react_vendor_analytics_assets['version'] ?? $this->script_version;

        // Inject wc-data store as a dependency.
        $react_deps[]                  = 'wc-store-data';
        $react_vendor_analytics_deps[] = 'vendor_analytics_script';

        $react_assets = include DOKAN_PRO_DIR . '/assets/js/admin-react.asset.php';
        $react_deps = $react_assets['dependencies'] ?? [];

        // Inject wc-data store as a dependency.
        array_push( $react_deps, 'wc-store-data' );

        $chunks = $this->get_analytics_chunks();
        foreach ( $chunks as $chunk_handle => $chunk ) {
            wp_register_script(
                $chunk_handle,
                DOKAN_PRO_PLUGIN_ASSEST . '/js/' . $chunk . '.js',
                $react_vendor_analytics_deps,
                $report_version,
                true
            );
            wp_set_script_translations( $chunk_handle, 'dokan', plugin_dir_path( DOKAN_PRO_FILE ) . 'languages' );
        }

        $scripts = [
            'dokan-pro-vue-admin' => [
                'src'       => DOKAN_PRO_PLUGIN_ASSEST . '/js/vue-pro-admin' . $this->suffix . '.js',
                'deps'      => [ 'jquery', 'wp-i18n', 'dokan-vue-vendor', 'dokan-vue-bootstrap', 'selectWoo' ],
                'version'   => $this->script_version,
                'in_footer' => true,
            ],
            'dokan-pro-vue-frontend-shipping' => [
                'src'       => DOKAN_PRO_PLUGIN_ASSEST . '/js/vue-pro-frontend-shipping' . $this->suffix . '.js',
                'deps'      => [ 'jquery', 'wp-i18n', 'dokan-vue-vendor', 'dokan-vue-bootstrap', 'underscore', 'dokan-jquery-blockui' ],
                'version'   => $this->script_version,
                'in_footer' => true,
            ],

            'dokan-pro-react-admin' => [
                'src'       => DOKAN_PRO_PLUGIN_ASSEST . '/js/admin-react' . $this->suffix . '.js',
                'deps'      => $react_deps,
                'version'   => $react_assets['version'] ?? $this->script_version,
                'in_footer' => true,
            ],
            'dokan-pro-react-vendor-analytics' => [
                'src'       => DOKAN_PRO_PLUGIN_ASSEST . '/js/vendor-dashboard/reports/index' . $this->suffix . '.js',
                'deps'      => array_merge( $react_vendor_analytics_deps, array_keys( $chunks ) ),
                'version'   => $report_version,
                'in_footer' => true,
            ],
        ];

        $coupon_assets_path = plugin_dir_path( DOKAN_PRO_FILE ) . '/assets/js/dokan-vendor-dashboard-coupon.asset.php';
        if ( file_exists( $coupon_assets_path ) ) {
            $coupon_assets = include $coupon_assets_path;
            $scripts['dokan-vendor-dashboard-coupon'] = [
                'src'       => DOKAN_PRO_PLUGIN_ASSEST . '/js/dokan-vendor-dashboard-coupon.js',
                'deps'      => array_merge( $coupon_assets['dependencies'], [ 'dokan-react-frontend' ] ),
                'version'   => $coupon_assets['version'],
                'in_footer' => true,
            ];
        }

        $shipping_asset_file = DOKAN_PRO_DIR . '/assets/js/pro-frontend-shipping.asset.php';
        if ( file_exists( $shipping_asset_file ) ) {
            $shipping_asset                   = require $shipping_asset_file;
            $shipping_asset['dependencies'][] = 'dokan-react-components';

            $scripts['dokan-pro-frontend-shipping'] = [
                'src'     => DOKAN_PRO_PLUGIN_ASSEST . '/js/pro-frontend-shipping.js',
                'deps'    => $shipping_asset['dependencies'],
                'version' => $shipping_asset['version'],
                'in_footer' => true,
            ];
        }

        $script_assets = plugin_dir_path( DOKAN_PRO_FILE ) . 'assets/js/dokan-pro-withdraw.asset.php';
        if ( file_exists( $script_assets ) ) {
            $assets = include $script_assets;

            $scripts['dokan-pro-withdraw'] = [
                'src'       => DOKAN_PRO_PLUGIN_ASSEST . '/js/dokan-pro-withdraw.js',
                'deps'      => array_merge( $assets['dependencies'], [ 'dokan-react-frontend' ] ),
                'version'   => $assets['version'],
                'in_footer' => true,
            ];
        }
        $seo_asset = DOKAN_PRO_DIR . '/assets/js/dokan-pro-store-seo.asset.php';
        if ( file_exists( $seo_asset ) ) {
            $seo_asset = include $seo_asset;
            $scripts['dokan-pro-store-seo'] = [
                'src'       => DOKAN_PRO_PLUGIN_ASSEST . '/js/dokan-pro-store-seo.js',
                'deps'      => $seo_asset['dependencies'],
                'version'   => $seo_asset['version'],
                'in_footer' => true,
            ];
        }

        $announcement = plugin_dir_path( DOKAN_PRO_FILE ) . 'assets/js/dokan-pro-announcement.asset.php';
        if ( file_exists( $announcement ) ) {
            $announcement_asset = include $announcement;

            $scripts['dokan-pro-announcement'] = [
                'src'       => DOKAN_PRO_PLUGIN_ASSEST . '/js/dokan-pro-announcement.js',
                'deps'      => array_merge( $announcement_asset['dependencies'], [ 'dokan-react-frontend' ] ),
                'version'   => $announcement_asset['version'],
                'in_footer' => true,
            ];
        }

        $social = $this->get_social_asset_config();
        if ( $social ) {
            $scripts['dokan-settings-social'] = [
                'src'       => DOKAN_PRO_PLUGIN_ASSEST . '/js/dokan-settings-social.js',
                'deps'      => $social['dependencies'],
                'version'   => $social['version'],
                'in_footer' => true,
            ];
        }

        $admin_dashboard_asset = DOKAN_PRO_DIR . '/assets/js/dokan-pro-admin-dashboard.asset.php';
        if ( file_exists( $admin_dashboard_asset ) ) {
            $dashboard_asset = include $admin_dashboard_asset;
            $scripts['dokan-pro-admin-dashboard'] = [
                'src'       => DOKAN_PRO_PLUGIN_ASSEST . '/js/dokan-pro-admin-dashboard.js',
                'deps'      => array_merge( $dashboard_asset['dependencies'], [ 'dokan-admin-dashboard' ] ),
                'version'   => $dashboard_asset['version'],
                'in_footer' => true,
            ];
        }

        $support_ticket_asset = DOKAN_PRO_DIR . '/assets/js/dokan-admin-support-ticket.asset.php';
        if ( file_exists( $support_ticket_asset ) ) {
            $support_asset = include $support_ticket_asset;
            $scripts['dokan-admin-support-ticket'] = [
                'src'       => DOKAN_PRO_PLUGIN_ASSEST . '/js/dokan-admin-support-ticket.js',
                'deps'      => array_merge( $support_asset['dependencies'], [ 'dokan-admin-panel-header' ] ),
                'version'   => $support_asset['version'],
                'in_footer' => true,
            ];
        }

        $reviews_script_file = DOKAN_PRO_DIR . '/assets/js/dashboard-reviews.asset.php';
        if ( file_exists( $reviews_script_file ) ) {
            $dashboard_product_reviews = require $reviews_script_file;
            $dependencies = $dashboard_product_reviews['dependencies'];
            $component_handle = 'dokan-react-components';
            $dependencies[] = $component_handle;
            $scripts['dokan-dashboard-product-reviews'] = [
                'src'       => DOKAN_PRO_PLUGIN_ASSEST . '/js/dashboard-reviews.js',
                'deps'      => $dependencies,
                'version'   => $dashboard_product_reviews['version'],
                'in_footer' => true,
            ];
        }

        /**
         * To allow add/remove js that registers vue these filter
         *
         * @since 3.3.9
         *
         * @args array $scripts
         */
        return apply_filters( 'dokan_pro_scripts', $scripts );
    }

    /**
     * Get registered styles
     *
     * @return array
     */
    public function get_styles() {
        $styles = [
            'dokan-pro-vue-admin' => [
                'src'     => DOKAN_PRO_PLUGIN_ASSEST . '/js/vue-pro-admin' . $this->suffix . '.css',
                'version' => $this->script_version,
                'deps'    => [ 'dokan-pro-tailwind' ],
            ],
            'dokan-pro-vue-frontend-shipping' => [
                'src'     => DOKAN_PRO_PLUGIN_ASSEST . '/js/vue-pro-frontend-shipping' . $this->suffix . '.css',
                'version' => $this->script_version,
            ],
            'dokan-pro-store-times' => [
                'src'     => DOKAN_PRO_PLUGIN_ASSEST . '/js/dokan-pro-store-times.css',
                'version' => $this->script_version,
            ],
            'dokan-pro-wp-version-before-5-3' => [
                'src'     => DOKAN_PRO_PLUGIN_ASSEST . '/js/wp-version-before-5-3.css',
                'version' => $this->script_version,
            ],
            'dokan-pro-tailwind' => [
                'src'     => DOKAN_PRO_PLUGIN_ASSEST . '/js/dokan-tailwind.css',
                'version' => $this->script_version,
            ],
            'dokan-pro-frontend-shipping' => [
                'deps'    => [ 'dokan-react-components', 'dokan-react-frontend' ],
                'src'     => DOKAN_PRO_PLUGIN_ASSEST . '/js/pro-frontend-shipping.css',
                'version' => $this->script_version,
            ],
        ];

        if ( file_exists( DOKAN_PRO_DIR . '/assets/js/dokan-admin-support-ticket.css' ) ) {
            $styles['dokan-admin-support-ticket'] = [
                'src'     => DOKAN_PRO_PLUGIN_ASSEST . '/js/dokan-admin-support-ticket.css',
                'version' => DOKAN_PRO_PLUGIN_VERSION,
            ];
        }

        $coupon_assets_path = plugin_dir_path( DOKAN_PRO_FILE ) . '/assets/js/dokan-vendor-dashboard-coupon.asset.php';
        if ( file_exists( $coupon_assets_path ) ) {
            $coupon_assets = include $coupon_assets_path;
            $styles['dokan-vendor-dashboard-coupon'] = [
                'src'     => DOKAN_PRO_PLUGIN_ASSEST . '/js/dokan-vendor-dashboard-coupon.css',
                'version' => $coupon_assets['version'],
                'deps'    => [ 'dokan-react-components' ],
            ];
        }

        $script_assets = plugin_dir_path( DOKAN_PRO_FILE ) . 'assets/js/dokan-pro-withdraw.asset.php';

        if ( file_exists( $script_assets ) ) {
            $assets = include $script_assets;

            $styles['dokan-pro-withdraw'] = [
                'src'     => DOKAN_PRO_PLUGIN_ASSEST . '/js/dokan-pro-withdraw.css',
                'version' => $assets['version'],
            ];
        }
        return $styles;
    }

    /**
     * Enqueue the product list actions script (duplicate, etc.) on the vendor dashboard.
     *
     * @since 5.0.0
     *
     * @return void
     */
    private function enqueue_product_list_actions_script(): void {
        $asset_file = DOKAN_PRO_DIR . '/assets/js/vendor-dashboard-product-list-actions.asset.php';

        if ( ! file_exists( $asset_file ) ) {
            return;
        }

        $asset = include $asset_file;

        wp_enqueue_style(
            'dokan-vendor-product-list-actions',
            DOKAN_PRO_PLUGIN_ASSEST . '/js/vendor-dashboard-product-list-actions.css',
            [ 'dokan-react-components' ],
            $asset['version'],
        );
        wp_enqueue_script(
            'dokan-vendor-product-list-actions',
            DOKAN_PRO_PLUGIN_ASSEST . '/js/vendor-dashboard-product-list-actions.js',
            $asset['dependencies'],
            $asset['version'] ?? DOKAN_PRO_PLUGIN_VERSION,
            true
        );
        wp_set_script_translations(
            'dokan-vendor-product-list-actions',
            'dokan',
        );
    }

    /**
     * Enqueue the product editor variations script on the vendor dashboard.
     *
     * @since 5.0.0
     *
     * @return void
     */
    private function enqueue_product_editor_vendor_dashboard_script(): void {
        $asset_file = DOKAN_PRO_DIR . '/assets/js/vendor-dashboard-product-editor.asset.php';

        if ( ! file_exists( $asset_file ) ) {
            return;
        }

        $asset = include $asset_file;

        // Filter out dependencies not registered in the current WordPress version
        // (e.g. wp-ui extracted from @wordpress/dataviews but not yet shipped in WP core).
        $deps = array_filter(
            $asset['dependencies'] ?? [],
            function ( $handle ) {
                return wp_script_is( $handle, 'registered' );
            }
        );

        wp_enqueue_script(
            'dokan-product-editor-vendor-dashboard',
            DOKAN_PRO_PLUGIN_ASSEST . '/js/vendor-dashboard-product-editor.js',
            $deps,
            $asset['version'] ?? DOKAN_PRO_PLUGIN_VERSION,
            true
        );
    }

	/**
	 * Disable "doing it wrong" error
	 *
	 * @return bool
	 */
	public function disable_doing_it_wrong_error() {
		return false;
	}

    /**
     * Get the chunks for analytics scripts, it generates the chunks based on the scripts
     * that are used in the analytics section.
     * This is used to register the scripts for translations support.
     *
     * @since 4.3.2
     *
     * @return array
     */
    public function get_analytics_chunks(): array {
        return [
            'dokan_analytics-report'            => 'analytics-report',
            'dokan_analytics-report-products'   => 'analytics-report-products',
            'dokan_analytics-report-revenue'    => 'analytics-report-revenue',
            'dokan_analytics-report-orders'     => 'analytics-report-orders',
            'dokan_analytics-report-statements' => 'analytics-report-statements',
            'dokan_analytics-report-stock'      => 'analytics-report-stock',
            'dokan_analytics-report-categories' => 'analytics-report-categories',
            'dokan_analytics-report-coupons'    => 'analytics-report-coupons',
            'dokan_analytics-report-customers'  => 'analytics-report-customers',
            'dokan_analytics-report-downloads'  => 'analytics-report-downloads',
            'dokan_analytics-report-taxes'      => 'analytics-report-taxes',
            'dokan_analytics-report-variations' => 'analytics-report-variations',
        ];
    }

    /**
     * Register i18n Scripts
     *
     * @since DOKAN_PRO
     *
     * @param array $default_script
     *
     * @return array
     */
    public function add_i18_localized_data( $default_script ) {
        $localize_script = [
            'i18n_location_name'             => __( 'Please provide a location name!', 'dokan' ),
            'i18n_location_state'            => __( 'Please provide', 'dokan' ),
            'i18n_country_name'              => __( 'Please provide a country!', 'dokan' ),
            'i18n_invalid'                   => __( 'Failed! Somthing went wrong', 'dokan' ),
            'i18n_chat_message'              => __( 'Facebook SDK is not found, or blocked by the browser. Can not initialize the chat.', 'dokan' ),
            'i18n_sms_code'                  => __( 'Insert SMS code', 'dokan' ),
            'i18n_gravater'                  => __( 'Upload a Photo', 'dokan' ),
            'i18n_phone_number'              => __( 'Insert Phone No.', 'dokan' ),
            'dokan_pro_i18n'                 => array( 'dokan' => dokan_get_jed_locale_data( 'dokan', DOKAN_PRO_DIR . '/languages/' ) ),
        ];

        return array_merge( $default_script, $localize_script );
    }
}
