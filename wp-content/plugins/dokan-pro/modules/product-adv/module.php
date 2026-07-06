<?php
namespace WeDevs\DokanPro\Modules\ProductAdvertisement;

use WeDevs\Dokan\Traits\ChainableContainer;
use WeDevs\DokanPro\Modules\ProductAdvertisement\Admin\Admin;
use WeDevs\DokanPro\Modules\ProductAdvertisement\Admin\AdminMenu;
use WeDevs\DokanPro\Modules\ProductAdvertisement\Admin\Install;
use WeDevs\DokanPro\Modules\ProductAdvertisement\Admin\Settings;
use WeDevs\DokanPro\Modules\ProductAdvertisement\Admin\VendorSubscription;
use WeDevs\DokanPro\Modules\ProductAdvertisement\Frontend\Cart;
use WeDevs\DokanPro\Modules\ProductAdvertisement\Frontend\Order;
use WeDevs\DokanPro\Modules\ProductAdvertisement\Frontend\Product;
use WeDevs\DokanPro\Modules\ProductAdvertisement\Frontend\Shortcode;
use WeDevs\DokanPro\Modules\ProductAdvertisement\Frontend\ProductWidget;
use WeDevs\DokanPro\Modules\ProductAdvertisement\ProductEditorFields;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class Module
 *
 * @package WeDevs\DokanPro\Modules\ProductAdvertisement
 *
 * @since 3.5.0
 */
final class Module {

    use ChainableContainer;

    /**
     * Cloning is forbidden.
     *
     * @since 3.5.0
     */
    public function __clone() {
        $message = ' Backtrace: ' . wp_debug_backtrace_summary();
        _doing_it_wrong( __METHOD__, $message . __( 'Cloning is forbidden.', 'dokan' ), DOKAN_PRO_PLUGIN_VERSION );
    }

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 3.5.0
     */
    public function __wakeup() {
        $message = ' Backtrace: ' . wp_debug_backtrace_summary();
        _doing_it_wrong( __METHOD__, $message . __( 'Unserializing instances of this class is forbidden.', 'dokan' ), DOKAN_PRO_PLUGIN_VERSION );
    }

    /**
     * Manager constructor.
     *
     * @since 3.5.0
     *
     * @return void
     */
    public function __construct() {
        $this->define_constants();
        $this->set_controllers();
        $this->init_hooks();

        // Activation and Deactivation hook
        add_action( 'dokan_activated_module_product_advertising', [ $this, 'activate' ], 10, 1 );
        add_action( 'dokan_deactivated_module_product_advertising', [ $this, 'deactivate' ], 10, 1 );
    }

    /**
     * Define module constants
     *
     * @since 3.5.0
     *
     * @return void
     */
    private function define_constants() {
        define( 'DOKAN_PRODUCT_ADVERTISEMENT_FILE', __FILE__ );
        define( 'DOKAN_PRODUCT_ADVERTISEMENT_DIR', dirname( DOKAN_PRODUCT_ADVERTISEMENT_FILE ) );
        define( 'DOKAN_PRODUCT_ADV_DIR', dirname( DOKAN_PRODUCT_ADVERTISEMENT_FILE ) ); // Alias for consistency
        define( 'DOKAN_PRODUCT_ADVERTISEMENT_INC', DOKAN_PRODUCT_ADVERTISEMENT_DIR . '/includes/' );
        define( 'DOKAN_PRODUCT_ADVERTISEMENT_ASSETS', plugins_url( 'assets', DOKAN_PRODUCT_ADVERTISEMENT_FILE ) );
        define( 'DOKAN_PRODUCT_ADV_ASSETS', plugins_url( 'assets', DOKAN_PRODUCT_ADVERTISEMENT_FILE ) ); // Alias for consistency
        define( 'DOKAN_PRODUCT_ADVERTISEMENT_TEMPLATE_PATH', DOKAN_PRODUCT_ADVERTISEMENT_DIR . '/templates/' );
    }

    /**
     * Set controllers
     *
     * @since 3.5.0
     *
     * @return void
     */
    private function set_controllers() {
        $this->container['admin']         = new Admin();
        $this->container['settings']      = new Settings();
        $this->container['hooks']         = new Hooks();
        $this->container['products']      = new Product();
        $this->container['order']         = new Order();
        $this->container['cart']          = new Cart();
        $this->container['subscriptions'] = new VendorSubscription();
        $this->container['cache']         = new AdvertisementCache();
        $this->container['admin_adv']     = new AdminMenu();

        // Block data modifier
        new BlockData();

        // Product editor form fields
        new ProductEditorFields();

        if ( wp_doing_ajax() ) {
            $this->container['ajax'] = new Ajax();
        }

        if ( ! is_admin() ) {
            $this->container['shortcode'] = new Shortcode();
        }
    }

    /**
     * Call all hooks here
     *
     * @since 3.5.0
     *
     * @return void
     */
    public function init_hooks() {
        // set action hooks
        add_filter( 'dokan_rest_api_class_map', [ $this, 'rest_api_class_map' ] ); // include rest api class

        // set template path
        add_filter( 'dokan_set_template_path', [ $this, 'load_templates' ], 10, 3 );

        // register script and styles
        add_action( 'init', [ $this, 'register_scripts' ], 10 );

        // enqueue vendor dashboard scripts
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_vendor_scripts' ] );

        // register widgets
        add_action( 'dokan_widgets', [ $this, 'register_product_advertisement_widget' ] );

        add_filter( 'dokan_button_shortcodes', [ $this, 'add_product_advertisement_shortcode_to_block_list' ] );
    }

    /**
     * Add Product Advertisement shortcode to block list.
     *
     * @since 4.0.3
     *
     * @param array $shortcodes List of shortcodes
     *
     * @return array
     */
    public function add_product_advertisement_shortcode_to_block_list( $shortcodes ) {
        $shortcodes['dokan_product_advertisement'] = [
            'title'   => __( 'Product Advertisement', 'dokan' ),
            'content' => '[dokan_product_advertisement title="" count="" vendor_id="" order="ASC" orderby="product_title"]',
        ];

        return $shortcodes;
    }

    /**
     * Register Product Advertisement Widget
     *
     * @since 3.5.0
     * @since 3.10.2 Updated to comply with `dokan-lite` widget registration process
     *
     * @param array $widgets List of widgets to be registered
     *
     * @return array
     */
    public function register_product_advertisement_widget( array $widgets ): array {
        $widgets[ ProductWidget::INSTANCE_KEY ] = ProductWidget::class;
        return $widgets;
    }

    /**
     * Rest api class map
     *
     * @param array $classes
     *
     * @since 3.5.0
     *
     * @return array
     */
    public function rest_api_class_map( $classes ) {
        $class[ DOKAN_PRODUCT_ADVERTISEMENT_INC . '/REST/AdvertisementController.php' ] = '\WeDevs\DokanPro\Modules\ProductAdvertisement\REST\AdvertisementController';

        return array_merge( $classes, $class );
    }

    /**
     * Set template path for Product Advertisement module
     *
     * @since 3.5.0
     *
     * @return string
     */
    public function load_templates( $template_path, $template, $args ) {
        if ( ! empty( $args['is_product_advertisement'] ) ) {
            return untrailingslashit( DOKAN_PRODUCT_ADVERTISEMENT_TEMPLATE_PATH );
        }

        return $template_path;
    }

    /**
     * Register all scripts
     *
     * @since 3.5.0
     *
     * @return void
     * */
    public function register_scripts() {
        list( $suffix, $version ) = dokan_get_script_suffix_and_version();

        // Register all js
        wp_register_script( 'dokan-product-adv-admin', DOKAN_PRODUCT_ADVERTISEMENT_ASSETS . '/js/admin' . $suffix . '.js', [ 'jquery', 'dokan-sweetalert2', 'dokan-vue-vendor', 'dokan-vue-bootstrap' ], $version, true );

        // register all css
        wp_register_style( 'dokan-product-adv-admin', DOKAN_PRODUCT_ADVERTISEMENT_ASSETS . '/js/admin' . $suffix . '.css', [], $version );

        // register frontend scripts
        wp_register_script( 'dokan-product-adv-purchase', DOKAN_PRODUCT_ADVERTISEMENT_ASSETS . '/js/purchase_advertisement' . $suffix . '.js', [ 'jquery', 'dokan-sweetalert2' ], $version, true );

        // Register vendor dashboard product-list column injector
        $asset_file = DOKAN_PRODUCT_ADVERTISEMENT_DIR . '/assets/js/vendor-dashboard-product-adv.asset.php';
        if ( file_exists( $asset_file ) ) {
            $asset        = include $asset_file;
            $dependencies = $asset['dependencies'] ?? [];
            $version      = $asset['version'] ?? $version;
        } else {
            $dependencies = [ 'wp-hooks', 'wp-i18n', 'wp-dom-ready' ];
        }

        wp_register_script(
            'dokan-vendor-product-adv',
            DOKAN_PRODUCT_ADVERTISEMENT_ASSETS . '/js/vendor-dashboard-product-adv.js',
            $dependencies,
            $version,
            true
        );
        wp_set_script_translations(
            'dokan-vendor-product-adv',
            'dokan',
        );
    }

    /**
     * Enqueue vendor dashboard scripts on the seller dashboard.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function enqueue_vendor_scripts() {
        if ( ! dokan_is_seller_dashboard() ) {
            return;
        }

        $colors = dokan_get_option( 'store_color_pallete', 'dokan_colors', [] );

        wp_enqueue_script( 'dokan-vendor-product-adv' );
        wp_localize_script(
            'dokan-vendor-product-adv',
            'dokan_purchase_advertisement',
            [
                'advertise_active'             => ! empty( $colors['btn_primary'] ) ? $colors['btn_primary'] : 'tomato',
                'advertise_product_nonce'      => wp_create_nonce( 'dokan_advertise_product_nonce' ),
                'on_error_message'             => esc_html__( 'Something went wrong.', 'dokan' ),
                'on_success_message'           => esc_html__( 'Success.', 'dokan' ),
                'product_not_published'        => esc_html__( 'You can not advertise this product. Products needs to be published before you can advertise.', 'dokan' ),
                'on_load_advertisement_status' => esc_html__( 'Loading advertisement data. Please wait...', 'dokan' ),
                'checkout_url'                 => wc_get_checkout_url(),
            ]
        );
    }

    /**
     * This method will be called during module activation
     *
     * @since 3.5.0
     */
    public function activate( $instance ) {
        new Install();
    }

    /**
     * This method will be called during module deactivation
     *
     * @since 3.5.0
     */
    public function deactivate( $instance ) {
        // clear schedule
        wp_clear_scheduled_hook( 'dokan_product_advertisement_daily_at_midnight_cron' );
    }
}
