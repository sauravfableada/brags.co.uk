<?php

namespace WeDevs\DokanPro\Modules\VSP;

use \WeDevs\DokanPro\Products;
use WeDevs\Dokan\Product\Hooks as ProductHooks;

/**
 * Dokan Vendor Subscription Product Module
 *
 * @since 3.8.0 HPOS support added
 */
class Module {

    /**
     * Constructor for the Dokan_VSP class
     *
     * Sets up all the appropriate hooks and actions
     * within our plugin.
     *
     * @uses is_admin()
     * @uses add_action()
     */
    public function __construct() {
        $this->define();

        include_once DOKAN_VSP_DIR_INC_DIR . '/DependencyNotice.php';

        $dependency = new DependencyNotice();

        if ( $dependency->is_missing_dependency() ) {
            return;
        }

        $this->includes();

        $this->initiate();

        $this->hooks();
    }

    /**
     * Hooks
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function define() {
        define( 'DOKAN_VSP_DIR', __DIR__ );
        define( 'DOKAN_VSP_DIR_INC_DIR', DOKAN_VSP_DIR . '/includes' );
        define( 'DOKAN_VSP_DIR_ASSETS_DIR', plugins_url( 'assets', __FILE__ ) );
    }

    /**
    * Get plugin path
    *
    * @since 1.5.1
    *
    * @return void
    **/
    public function plugin_path() {
        return untrailingslashit( plugin_dir_path( __FILE__ ) );
    }

    /**
     * Includes all necessary class a functions file
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function includes() {
        // Load all helper functions
        require_once DOKAN_VSP_DIR_INC_DIR . '/functions.php';

        // Load classes
        require_once DOKAN_VSP_DIR_INC_DIR . '/class-vendor-product.php';
        require_once DOKAN_VSP_DIR_INC_DIR . '/class-user-subscription.php';
        require_once DOKAN_VSP_DIR_INC_DIR . '/SubscriptionCompatibility.php';
        require_once DOKAN_VSP_DIR_INC_DIR . '/ProductEditorFields.php';

        if ( class_exists( 'WC_REST_Subscriptions_Controller' ) ) {
            require_once DOKAN_VSP_DIR_INC_DIR . '/class-rest-api.php';
        }

        if ( class_exists( 'WC_REST_Subscription_Notes_Controller' ) ) {
            require_once DOKAN_VSP_DIR_INC_DIR . '/class-notes-rest-api.php';
        }
    }

    /**
     * Initiate all classes
     *
     * @return void
     */
    public function initiate() {
        new \Dokan_VSP_Product();
        new \Dokan_VSP_User_Subscription();
        new \WeDevs\DokanPro\Modules\VSP\SubscriptionCompatibility();
        new \WeDevs\DokanPro\Modules\VSP\ProductEditorFields();
    }

    /**
     * Init all hooks
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function hooks() {
        // Module activation hook
        add_action( 'dokan_activated_module_vsp', [ $this, 'activate' ] );

        add_action( 'wp_enqueue_scripts', [ $this, 'load_scripts' ] );
        add_filter( 'dokan_set_template_path', [ $this, 'load_subcription_product_templates' ], 10, 3 );
        add_filter( 'woocommerce_order_item_needs_processing', [ $this, 'order_needs_processing' ], 10, 2 );
        add_filter( 'dokan_get_product_types', [ $this, 'add_subscription_type_product' ] );

        add_action( 'init', [ $this, 'register_scripts' ] );

        if ( method_exists( ProductHooks::class, 'save_per_product_commission_options' ) ) {
            $product_class = ProductHooks::class;
        } else {
            $product_class = Products::class;
        }
        // store subscription type product, per product commission.
        add_action( 'woocommerce_process_product_meta_subscription', [ $product_class, 'save_per_product_commission_options' ], 15 );
        add_action( 'woocommerce_process_product_meta_variable-subscription', [ $product_class, 'save_per_product_commission_options' ], 15 );

        // flush rewrite rules
        add_action( 'woocommerce_flush_rewrite_rules', [ $this, 'flush_rewrite_rules' ] );

        add_action( 'rest_api_init', [ $this, 'add_rest_api_routes' ] );
        add_filter( 'wcs_rest_subscription_orders_response', [ $this, 'add_vendor_order_link_to_subscription_orders_response' ], 10, 2 );
    }

    /**
     * Tell WC that we don't need any processing
     *
     * @param  bool $needs_processing
     * @param  \WC_Product $product
     * @return bool
     */
    public function order_needs_processing( $needs_processing, $product ) {
        if ( $product->get_type() === 'subscription' || $product->get_type() === 'variable-subscription' || $product->get_type() === 'subscription_variation' ) {
            $needs_processing = false;
        }

        return $needs_processing;
    }

    /**
     * Add subscription product for vendor subscription allowd categories
     *
     * @since 3.0.8
     *
     * @param $product_type
     *
     * @return array
     */
    public function add_subscription_type_product( $product_type ) {
        $product_type['subscription']          = __( 'Simple Subscription Product', 'dokan' );
        $product_type['variable-subscription'] = __( 'Variable Subscription Product', 'dokan' );

        return $product_type;
    }

    /**
     * Load global scripts
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function load_scripts() {
        global $wp;

        // Vendor product edit page.
        if ( isset( $wp->query_vars['products'] ) && isset( $_GET['product_id'] ) && ! empty( $_GET['action'] ) && 'edit' === sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) { // phpcs:ignore
            $this->enqueue_scripts();
        }

        if ( isset( $wp->query_vars['user-subscription'] ) && ! empty( $_GET['subscription_id'] ) ) { // phpcs:ignore
            $this->enqueue_scripts();
        }

        if ( isset( $wp->query_vars['coupons'] ) && ! empty( $_GET['post'] ) ) { // phpcs:ignore
            $this->enqueue_scripts();
        }

        // If user-subscription list page and not a single user-subscription page.
        if ( isset( $wp->query_vars['user-subscription'] ) && ! isset( $_GET['subscription_id'] ) ) {
            wp_enqueue_style( 'dokan-vsp-subscription-list-style' );
        }

        if ( dokan_is_seller_dashboard() ) {
            wp_enqueue_script( 'user-subscription' );
            wp_enqueue_style( 'user-subscription' );

            wp_set_script_translations( 'user-subscription', 'dokan' );
        }
    }

    /**
     * Register scripts
     *
     * @since 3.7.4
     */
    public function register_scripts() {
        [ $suffix, $script_version ] = dokan_get_script_suffix_and_version();

        wp_register_style( 'dokan-vsp-style', DOKAN_VSP_DIR_ASSETS_DIR . '/css/style.css', false, $script_version, 'all' );
        wp_register_style( 'dokan-vsp-subscription-list-style', DOKAN_VSP_DIR_ASSETS_DIR . '/css/order-list.css', false, $script_version, 'all' );
        wp_register_script( 'dokan-vsp-script', DOKAN_VSP_DIR_ASSETS_DIR . '/js/scripts.js', array( 'jquery' ), $script_version, true );

        $billing_period_strings = \WC_Subscriptions_Synchroniser::get_billing_period_ranges();

        $params = [
            'productType'               => \WC_Subscriptions::$name,
            'trialPeriodSingular'       => wcs_get_available_time_periods(),
            'trialPeriodPlurals'        => wcs_get_available_time_periods( 'plural' ),
            'subscriptionLengths'       => wcs_get_subscription_ranges(),
            'syncOptions'               => [
                'week'  => $billing_period_strings['week'],
                'month' => $billing_period_strings['month'],
            ],
        ];

        wp_localize_script( 'jquery', 'dokanVPS', apply_filters( 'wc_vps_params', $params ) );
        $script_assets = plugin_dir_path( __FILE__ ) . 'assets/js/user-subscription.asset.php';

        if ( file_exists( $script_assets ) ) {
            $assets = include $script_assets;

            wp_register_script(
                'user-subscription',
                DOKAN_VSP_DIR_ASSETS_DIR . '/js/user-subscription.js',
                array_merge( $assets['dependencies'], [ 'moment', 'dokan-util-helper', 'dokan-accounting', 'dokan-react-components', 'wc-components' ] ),
                $assets['version'],
                true
            );

            $localize_data = [
                'currencySymbols' => get_woocommerce_currency_symbols(),
            ];

            wp_localize_script( 'user-subscription', 'dokanProductSubscription', $localize_data );
        }
    }

    /**
     * Enqueue scripts
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function enqueue_scripts() {
        wp_enqueue_style( 'dokan-vsp-style' );
        wp_enqueue_script( 'dokan-vsp-script' );
    }

    /**
     * Set subscription html templates directory
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function load_subcription_product_templates( $template_path, $template, $args ) {
        if ( isset( $args['is_subscription_product'] ) && $args['is_subscription_product'] ) {
            return $this->plugin_path() . '/templates';
        }

        return $template_path;
    }

    /**
     * This method will load during module activation
     *
     * @since 3.3.1
     *
     * @return void
     */
    public function activate() {
        // flash rewrite rules
        $this->flush_rewrite_rules();
    }

    /**
     * Flush rewrite rules
     *
     * @since 3.3.1
     *
     * @return void
     */
    public function flush_rewrite_rules() {
        dokan()->rewrite->register_rule();
        flush_rewrite_rules();
    }

    /**
     * Initiate rest api here.
     *
     * @since 4.0.0
     *
     * @return void
     */
    public function add_rest_api_routes() {
        if ( class_exists( 'WC_REST_Subscriptions_Controller' ) ) {
            $rest_api = new \Dokan_Vendor_Subscription_Product_Rest_Api();
            $rest_api->register_routes();
        }

        if ( class_exists( 'WC_REST_Subscription_Notes_Controller' ) ) {
            $note_rest_api = new \Dokan_VSP_Notes_Rest_Api();
            $note_rest_api->register_routes();
        }
    }

    /**
     * Add vendor order link to subscription orders response
     *
     * @since 4.0.0
     *
     * @param \WP_REST_Response $response
     * @param \WP_REST_Request  $request
     *
     * @return \WP_REST_Response $response
     */
    public function add_vendor_order_link_to_subscription_orders_response( $response, $request ) {
        $orders = $response->get_data();
        $orders = array_map(
            function ( $order ) {
                $order['vendor_dashboard_order_link'] = esc_url( wp_nonce_url( add_query_arg( array( 'order_id' => $order['id'] ), dokan_get_navigation_url( 'orders' ) ), 'dokan_view_order' ) );

                return $order;
            },
            $orders
        );

        $response->set_data( $orders );

        return $response;
    }
}
