<?php
namespace WeDevs\DokanPro\Modules\SellerBadge;

use WeDevs\Dokan\Traits\ChainableContainer;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // exit if accessed directly
}

/**
 * Class for Seller Badge module integration.
 *
 * @since 3.7.14
 */
class Module {

    use ChainableContainer;

    /**
     * Cloning is forbidden.
     *
     * @since 3.7.14
     */
    public function __clone() {
        $message = ' Backtrace: ' . wp_debug_backtrace_summary();
        _doing_it_wrong( __METHOD__, $message . __( 'Cloning is forbidden.', 'dokan' ), DOKAN_PRO_PLUGIN_VERSION );
    }

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 3.7.14
     */
    public function __wakeup() {
        $message = ' Backtrace: ' . wp_debug_backtrace_summary();
        _doing_it_wrong( __METHOD__, $message . __( 'Unserializing instances of this class is forbidden.', 'dokan' ), DOKAN_PRO_PLUGIN_VERSION );
    }

    /**
     * Class constructor.
     *
     * @since 3.7.14
     */
    public function __construct() {
        $this->define_constants();
        $this->initiate();

        add_filter( 'dokan_rest_api_class_map', [ $this, 'add_rest_controller' ] );
        add_filter( 'dokan_set_template_path', [ $this, 'load_templates' ], 20, 3 );

        // Activation and Deactivation hook
        add_action( 'dokan_activated_module_seller_badge', [ $this, 'activate' ] );
        add_action( 'dokan_deactivated_module_seller_badge', [ $this, 'deactivate' ] );

        // flush rewrite rules
        add_action( 'woocommerce_flush_rewrite_rules', [ $this, 'flush_rewrite_rules' ] );

        add_filter( 'dokan_widgets', [ $this, 'register_widgets' ] );

        add_action( 'init', [ $this, 'register_scripts' ] );
        add_action( 'init', [ $this, 'register_badge_events' ], 1 );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_scripts' ] );
    }

    /**
     * Add module REST Controller
     *
     * @since 3.7.14
     *
     * @param array $class_map
     */
    public function add_rest_controller( $class_map ) {
        $class_map[ DOKAN_SELLER_BADGE_INCLUDES . '/REST/SellerBadgeController.php' ] = 'WeDevs\\DokanPro\\Modules\\SellerBadge\\REST\\SellerBadgeController';

        return $class_map;
    }

    /**
     * Module constants
     *
     * @since 3.7.14
     *
     * @return void
     */
    private function define_constants() {
        define( 'DOKAN_SELLER_BADGE_DIR', __DIR__ );
        define( 'DOKAN_SELLER_BADGE_ASSETS_DIR', plugins_url( 'assets', __FILE__ ) );
        define( 'DOKAN_SELLER_BADGE_FILE', __FILE__ );
        define( 'DOKAN_SELLER_BADGE_PATH', dirname( DOKAN_SELLER_BADGE_FILE ) );
        define( 'DOKAN_SELLER_BADGE_INCLUDES', DOKAN_SELLER_BADGE_PATH . '/includes' );
        define( 'DOKAN_SELLER_BADGE_URL', plugins_url( '', DOKAN_SELLER_BADGE_FILE ) );
        define( 'DOKAN_SELLER_BADGE_ASSETS', DOKAN_SELLER_BADGE_URL . '/assets' );
        define( 'DOKAN_SELLER_BADGE_TEMPLATE_PATH', DOKAN_SELLER_BADGE_PATH . '/templates/' );
    }

    /**
     * Initiate all classes
     *
     * @since 3.7.14
     *
     * @return void
     */
    public function initiate() {
        $this->container['hooks'] = new Hooks();
        $this->container['cache'] = new Cache();
        $this->container['frontend_hooks'] = new Frontend\Hooks();

        if ( is_admin() ) {
            $this->container['admin_hooks'] = new Admin\Hooks();
        }
    }

    /**
     * Register Badge Event Hooks
     *
     * @since 3.7.14
     *
     * @return void
     */
    public function register_badge_events() {
        RegisterBadgeEvents::instance();
    }

    /**
     * Register seller badge scripts
     *
     * @since 3.7.14
     *
     * @return void
     */
    public function register_scripts() {
        // Initialize Admin menu similar to Vendor Support
        $menu = new AdminMenu();
        $menu->register_hooks();

        // Use minified libraries if SCRIPT_DEBUG is turned off
        [ $suffix, $version ] = dokan_get_script_suffix_and_version();

        wp_register_script(
            'dokan-seller-badge-admin',
            DOKAN_SELLER_BADGE_ASSETS . '/js/dokan-seller-badge-admin' . $suffix . '.js',
            [ 'jquery', 'dokan-vue-vendor', 'dokan-vue-bootstrap' ],
            $version,
            true
        );
        wp_set_script_translations( 'dokan-seller-badge-admin', 'dokan', plugin_dir_path( DOKAN_PRO_FILE ) . 'languages' );

        wp_register_style(
            'dokan-seller-badge-admin',
            DOKAN_SELLER_BADGE_ASSETS . '/js/dokan-seller-badge-admin' . $suffix . '.css',
            [],
            $version,
            'all'
        );

        // Legacy Vue frontend script (kept for backward compatibility)
        wp_register_script(
            'dokan-seller-badge-frontend',
            DOKAN_SELLER_BADGE_ASSETS . '/js/dokan-seller-badge-frontend' . $suffix . '.js',
            [ 'jquery', 'dokan-vue-vendor', 'dokan-vue-bootstrap' ],
            $version,
            true
        );
        wp_set_script_translations( 'dokan-seller-badge-frontend', 'dokan', plugin_dir_path( DOKAN_PRO_FILE ) . 'languages' );

        // React vendor dashboard badge script
        $badge_dashboard_asset_path = plugin_dir_path( DOKAN_PRO_FILE ) . 'assets/js/dokan-vendor-dashboard-badge.asset.php';
        if ( file_exists( $badge_dashboard_asset_path ) ) {
            $badge_dashboard_asset = include $badge_dashboard_asset_path;
            wp_register_script(
                'dokan-vendor-dashboard-badge',
                DOKAN_PRO_PLUGIN_ASSEST . '/js/dokan-vendor-dashboard-badge.js',
                array_merge( $badge_dashboard_asset['dependencies'], [ 'dokan-react-frontend' ] ),
                $badge_dashboard_asset['version'],
                true
            );
            wp_register_style(
                'dokan-vendor-dashboard-badge',
                DOKAN_PRO_PLUGIN_ASSEST . '/js/dokan-vendor-dashboard-badge.css',
                [ 'dokan-react-components' ],
                $badge_dashboard_asset['version']
            );
        }

        $script_assets_path = DOKAN_SELLER_BADGE_PATH . '/assets/js/dokan-seller-badge-admin-vendor.asset.php';
        if ( file_exists( $script_assets_path ) ) {
            $vendor_asset = require $script_assets_path;
            $dependencies = $vendor_asset['dependencies'] ?? [];
            $version      = $vendor_asset['version'] ?? '';

            wp_register_script(
                'dokan-seller-badge-admin-vendor',
                DOKAN_SELLER_BADGE_ASSETS . '/js/dokan-seller-badge-admin-vendor' . $suffix . '.js',
                $dependencies,
                $version,
                true
            );
        }

        wp_register_style(
            'dokan-seller-badge-frontend',
            DOKAN_SELLER_BADGE_ASSETS . '/js/dokan-seller-badge-frontend' . $suffix . '.css',
            [],
            $version,
            'all'
        );

        wp_localize_script(
            'dokan-seller-badge-admin',
            'DokanSellerBadgesAdmin',
            [
                'assetsUrl' => DOKAN_SELLER_BADGE_ASSETS,
            ]
        );

	    $vendor_tab_file = DOKAN_SELLER_BADGE_DIR . '/assets/js/dokan-seller-badge-vendor-tab.asset.php';
	    if ( file_exists( $vendor_tab_file ) ) {
		    $vendor_tab_script = require $vendor_tab_file;
		    $dependencies      = $vendor_tab_script['dependencies'] ?? [];
		    $dependencies[]    = 'dokan-admin-dashboard';
		    $version           = $vendor_tab_script['version'] ?? '';

		    wp_register_script(
			    'dokan-seller-badge-vendor-tab',
			    DOKAN_SELLER_BADGE_ASSETS_DIR . '/js/dokan-seller-badge-vendor-tab' . $suffix . '.js',
			    [ 'dokan-admin-dashboard' ],
			    $version,
			    true
		    );

		    wp_set_script_translations(
			    'dokan-seller-badge-vendor-tab',
			    'dokan',
			    plugin_dir_path( DOKAN_PRO_FILE ) . 'languages'
		    );
	    }
    }

    /**
     * Enqueue frontend scripts for vendor dashboard.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function enqueue_frontend_scripts() {
        if ( ! dokan_is_seller_dashboard() ) {
            return;
        }

        wp_enqueue_script( 'dokan-vendor-dashboard-badge' );
        wp_enqueue_style( 'dokan-vendor-dashboard-badge' );
        wp_set_script_translations( 'dokan-vendor-dashboard-badge', 'dokan', plugin_dir_path( DOKAN_PRO_FILE ) . 'languages' );
    }

    /**
     * Set template path for Request Quote
     *
     * @since 3.7.14
     *
     * @return string
     */
    public function load_templates( $template_path, $template, $args ) {
        if ( ( isset( $args['seller_badge_list_template'] ) && $args['seller_badge_list_template'] ) ) {
            return DOKAN_SELLER_BADGE_TEMPLATE_PATH;
        }

        return $template_path;
    }

    /**
     * Install all tables when module is activated.
     *
     * @since 3.7.14
     *
     * @return void
     */
    public function activate() {
        new Installer();
        $this->flush_rewrite_rules();
    }

    /**
     * Flush rewrite rules
     *
     * @since 3.7.14
     *
     * @return void
     */
    public function flush_rewrite_rules() {
        dokan()->rewrite->register_rule();
        flush_rewrite_rules( true );
    }

    /**
     * This method will be called during module deactivation
     *
     * @since 3.7.14
     */
    public function deactivate() {
        if ( function_exists( 'as_unschedule_all_actions' ) ) {
            $hook = 'dokan_seller_badge_daily_at_midnight_cron';
            as_unschedule_all_actions( $hook );
        }

        // flush rewrite rules
        $this->flush_rewrite_rules();
    }

    /**
     * Register widgets.
     *
     * @since 3.7.14
     *
     * @param array $widgets
     *
     * @return array
     */
    public function register_widgets( $widgets ) {
        $widgets['seller_badge'] = Widgets\SellerBadge::class;

        return $widgets;
    }
}
