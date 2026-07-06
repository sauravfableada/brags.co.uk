<?php

namespace WeDevs\DokanPro\Modules\Geolocation;

class Module {

    /**
     * Checks admin has set google map api key
     *
     * @since 1.0.0
     *
     * @var bool
     */
    public $has_map_api_key = false;

    /**
     * Class constructor
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function __construct() {
        $dokan_appearance = get_option( 'dokan_appearance', array() );

        if ( ! empty( $dokan_appearance['gmap_api_key'] ) && 'google_maps' === $dokan_appearance['map_api_source'] ) {
            $this->has_map_api_key = true;
        } elseif ( ! empty( $dokan_appearance['mapbox_access_token'] ) && 'mapbox' === $dokan_appearance['map_api_source'] ) {
            $this->has_map_api_key = true;
            add_action( 'wp_footer', array( $this, 'render_mapbox_script' ), 30 );
        }

        $this->define_constants();
        $this->includes();
        $this->hooks();
        $this->instances();

        add_action( 'dokan_activated_module_geolocation', array( $this, 'activate' ) );
    }

    /**
     * Module constants
     *
     * @since 1.0.0
     *
     * @return void
     */
    private function define_constants() {
        define( 'DOKAN_GEOLOCATION_VERSION', DOKAN_PRO_PLUGIN_VERSION );
        define( 'DOKAN_GEOLOCATION_PATH', __DIR__ );
        define( 'DOKAN_GEOLOCATION_URL', plugins_url( '', __FILE__ ) );
        define( 'DOKAN_GEOLOCATION_ASSETS', DOKAN_GEOLOCATION_URL . '/assets' );
        define( 'DOKAN_GEOLOCATION_VIEWS', DOKAN_GEOLOCATION_PATH . '/views' );
    }

    /**
     * Add action and filter hooks
     *
     * @since 1.0.0
     *
     * @return void
     */
    private function hooks() {
        if ( $this->has_map_api_key ) {
            add_action( 'init', array( $this, 'register_scripts' ) );
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
            add_action( 'dokan_widgets', array( $this, 'register_widget' ) );
            add_action( 'dokan_new_seller_created', array( $this, 'set_default_geolocation_data' ), 35 );
            add_action( 'woocommerce_product_import_inserted_product_object', array( $this, 'set_product_geo_location_meta_on_import' ), 10, 2 );
            add_action( 'dokan_store_profile_saved', array( $this, 'handle_store_profile_saved' ), 10, 3 );
        } else {
            add_filter( 'dokan_admin_notices', [ $this, 'admin_notices' ] );
        }
    }

    /**
     * Include module related files
     *
     * @since 1.0.0
     *
     * @return void
     */
    private function includes() {
        require_once DOKAN_GEOLOCATION_PATH . '/functions.php';
        require_once DOKAN_GEOLOCATION_PATH . '/class-geolocation-admin-settings.php';

        if ( $this->has_map_api_key ) {
            require_once DOKAN_GEOLOCATION_PATH . '/class-dokan-geolocation-scripts.php';
            require_once DOKAN_GEOLOCATION_PATH . '/class-dokan-geolocation-shortcode.php';
            require_once DOKAN_GEOLOCATION_PATH . '/class-dokan-geolocation-widget-filters.php';
            require_once DOKAN_GEOLOCATION_PATH . '/class-dokan-geolocation-widget-product-location.php';
            require_once DOKAN_GEOLOCATION_PATH . '/class-dokan-geolocation-vendor-dashboard.php';
            require_once DOKAN_GEOLOCATION_PATH . '/class-dokan-geolocation-vendor-query.php';
            require_once DOKAN_GEOLOCATION_PATH . '/class-dokan-geolocation-vendor-view.php';
            require_once DOKAN_GEOLOCATION_PATH . '/class-dokan-geolocation-product-query.php';
            require_once DOKAN_GEOLOCATION_PATH . '/class-dokan-geolocation-product-view.php';
            require_once DOKAN_GEOLOCATION_PATH . '/class-dokan-geolocation-product-single.php';
            require_once DOKAN_GEOLOCATION_PATH . '/class-dokan-geolocation-product-import.php';
        }
    }

    /**
     * Create module related class instances
     *
     * @since 1.0.0
     *
     * @return void
     */
    private function instances() {
        new \Dokan_Geolocation_Admin_Settings();

        if ( $this->has_map_api_key ) {
            new \Dokan_Geolocation_Scripts();
            new \Dokan_Geolocation_Shortcode();
            new \Dokan_Geolocation_Vendor_Dashboard();
            new \Dokan_Geolocation_Vendor_Query();
            new \Dokan_Geolocation_Vendor_View();
            new \Dokan_Geolocation_Product_Query();
            new \Dokan_Geolocation_Product_View();
            new \Dokan_Geolocation_Product_Single();
            new \Dokan_Geolocation_Product_Import();
            new BlockData();
            new ProductEditorFields();
        }
    }

    /**
     * Run upon module activation
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function activate() {
        $item = apply_filters(
            'dokan_geolocation_activate_item',
            [
                'updating' => 'vendors',
                'paged'    => 1,
            ]
        );

        $this->push_to_queue_processor( $item );
    }

    /**
     * Handle store profile saved event.
     *
     * @since 4.1.0
     *
     * @param int   $vendor_id           The vendor user ID
     * @param array $dokan_settings      The vendor's updated Dokan settings
     * @param array $prev_dokan_settings The vendor's previous Dokan settings
     *
     * @return void
     */
    public function handle_store_profile_saved( $vendor_id, $dokan_settings, $prev_dokan_settings ) {
        $prev_location    = sanitize_text_field( wp_unslash( $prev_dokan_settings['location'] ?? '' ) );
        $current_location = sanitize_text_field( wp_unslash( $dokan_settings['location'] ?? '' ) );

        // If the location is not changed, return early.
        if ( $prev_location === $current_location ) {
            return;
        }

        $item = apply_filters(
            'dokan_geolocation_store_profile_saved_item',
            [
                'updating'  => 'vendor_products',
                'vendor_id' => $vendor_id,
                'paged'     => 1,
            ],
            $vendor_id
        );

        // Push the item to the queue.
        $this->push_to_queue_processor( $item );
    }

    /**
     * Reusable method to handle push-to-queue processing
     *
     * @since 4.1.0
     *
     * @param array $item Queue item with updating type and other parameters
     *
     * @return void
     */
    protected function push_to_queue_processor( $item ) {
        // return if dokan plugin is not active
        if ( ! function_exists( 'dokan' ) ) {
            return;
        }
        $updater_file = DOKAN_GEOLOCATION_PATH . '/class-dokan-geolocation-update-location-data.php';

        include_once $updater_file;
        $processor = new \Dokan_Geolocation_Update_Location_Data();

        $processor->push_to_queue( $item );
        $processor->save()->dispatch();
    }

    public function register_scripts() {
        [ $suffix, $version ] = dokan_get_script_suffix_and_version();

        wp_register_style( 'dokan-geolocation', DOKAN_GEOLOCATION_ASSETS . '/js/geolocation' . $suffix . '.css', array(), $version );

        $js = DOKAN_GEOLOCATION_ASSETS . '/js/geolocation-vendor-dashboard-product-google-maps' . $suffix . '.js';

        $source = dokan_get_option( 'map_api_source', 'dokan_appearance', 'google_maps' );

        if ( 'mapbox' === $source ) {
            $js = DOKAN_GEOLOCATION_ASSETS . '/js/geolocation-vendor-dashboard-product-mapbox' . $suffix . '.js';
        }

        wp_register_script( 'dokan-geolocation', $js, array( 'jquery', 'dokan-maps' ), $version, true );

        // Load the geolocation product categories script only shop page.
        $asset_file = DOKAN_GEOLOCATION_PATH . '/assets/js/dokan-geo-product-categories.asset.php';
        if ( ! file_exists( $asset_file ) ) {
            return;
        }

        $asset = require $asset_file;
        $deps = $asset['dependencies'] ?? array();

        wp_register_script( 'dokan-geo-product-categories', DOKAN_GEOLOCATION_ASSETS . '/js/dokan-geo-product-categories.js', $deps, $version, true );
        wp_register_style( 'dokan-geo-product-categories', DOKAN_GEOLOCATION_ASSETS . '/js/style-dokan-geo-product-categories.css', array( 'wp-components', 'wc-components' ), $version );

        // Define product categories data
        $category_data = array(
            'categories' => \WeDevs\Dokan\ProductCategory\Helper::get_product_categories_tree(),
        );

        wp_localize_script(
            'dokan-geo-product-categories',
            'dokanGeoProductCategories',
            apply_filters( 'dokan_geo_product_categories_data', $category_data )
        );

        wp_set_script_translations( 'dokan-geo-product-categories', 'dokan' );
    }

    /**
     * Enqueue module scripts
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function enqueue_scripts() {
        global $wp;
        if (
            is_shop()
            || dokan_is_store_listing()
            || is_product_category()
            || is_product_tag()
            || ( isset( $wp->query_vars['products'] ) && isset( $_GET['action'] ) && 'edit' === sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) //phpcs:ignore
            || ( isset( $wp->query_vars['booking'] ) && ( ( 'edit' === $wp->query_vars['booking'] ) || ( 'new-product' === $wp->query_vars['booking'] ) ) )
            || ( isset( $wp->query_vars['auction'] ) && isset( $_GET['action'] ) && 'edit' === sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) //phpcs:ignore
        ) {
            wp_enqueue_style( 'dokan-geolocation' );
            wp_enqueue_script( 'dokan-geolocation' );
        }

        if ( is_shop()
            || dokan_is_store_listing()
            || is_product_category()
            || is_product_tag() ) {
            wp_enqueue_script( 'dokan-geo-product-categories' );
            wp_enqueue_style( 'dokan-geo-product-categories' );
        }

        if ( dokan_is_store_listing() ) {
            wp_enqueue_script( 'dokan-geo-filters-store-lists' );
        }
    }

    /**
     * Register module widgets
     *
     * @since 1.0.0
     * @since 3.10.2 Updated to comply with `dokan-lite` widget registration process
     *
     * @param array $widgets List of widgets to be registered
     *
     * @return array
     */
    public function register_widget( array $widgets ): array {
        $widgets[ \Dokan_Geolocation_Widget_Filters::INSTANCE_KEY ] = \Dokan_Geolocation_Widget_Filters::class;
        $widgets[ \Dokan_Geolocation_Widget_Product_Location::INSTANCE_KEY ] = \Dokan_Geolocation_Widget_Product_Location::class;
        return $widgets;
    }

    /**
     * Show admin notices
     *
     * @since 1.0.0
     *
     * @param array $notices
     *
     * @return array
     */
    public function admin_notices( $notices ) {
        $notices[] = [
            'type'        => 'alert',
            'title'       => __( 'Dokan Geolocation module is almost ready!', 'dokan' ),
            'description' => __( 'Dokan <strong> Geolocation Module</strong> requires Google Map API Key or Mapbox Access Token. Please set your API Key or Token in <strong>Dokan Admin Settings > Appearance</strong>.', 'dokan' ),
            'priority'    => 10,
            'actions'     => [
                [
                    'type'   => 'primary',
                    'text'   => __( 'Go to Settings', 'dokan' ),
                    'action'  => add_query_arg( array( 'page' => 'dokan#/settings' ), admin_url( 'admin.php' ) ),
                ],
            ],
        ];

        return $notices;
    }

    /**
     * Show mapbox some extra scripts only for RTL
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function render_mapbox_script() {
        if ( is_rtl() ) {
            ?>
            <style type="text/css">
                .mapboxgl-map {
                    text-align: inherit;
                }
            </style>
            <?php
        }
    }

    /**
     * Geolocation data add when new seller
     *
     * @since 3.3.0
     *
     * @param $user_id
     */
    public function set_default_geolocation_data( $user_id ) {
        $default_locations = dokan_get_option( 'location', 'dokan_geolocation' );

        if ( ! is_array( $default_locations ) || empty( $default_locations ) ) {
            $default_locations = array(
                'latitude'  => '',
                'longitude' => '',
                'address'   => '',
            );
        }

        update_user_meta( $user_id, 'dokan_geo_latitude', $default_locations['latitude'] );
        update_user_meta( $user_id, 'dokan_geo_longitude', $default_locations['longitude'] );
        update_user_meta( $user_id, 'dokan_geo_public', 1 );
        update_user_meta( $user_id, 'dokan_geo_address', $default_locations['address'] );

        $dokan_settings   = get_user_meta( $user_id, 'dokan_profile_settings', true );
        $default_location = '';

        if ( ! empty( $default_locations['latitude'] ) && ! empty( $default_locations['longitude'] ) ) {
            $default_location = $default_locations['latitude'] . ',' . $default_locations['longitude'];
        }

        $dokan_settings['location']     = $default_location;
        $dokan_settings['find_address'] = $default_locations['address'];

        update_user_meta( $user_id, 'dokan_profile_settings', $dokan_settings );
    }

    /**
     * Set product geo location meta information on product import
     *
     * @since 3.4.1
     *
     * @param WC_Product $product
     * @param array $csv_line_item   product line item data
     *
     * @return array $product
     */
    public function set_product_geo_location_meta_on_import( $product, $csv_line_item ) {
        if ( substr( wp_get_referer(), 0, strlen( get_admin_url() ) ) === get_admin_url() ) {
            return;
        }

        if ( ! is_a( $product, 'WC_Product' ) ) {
            return;
        }

        $need_to_add_geo_data = false;

        // check if we are inserting a product, in that case, insert geo location data
        if ( empty( $csv_line_item['id'] ) ) {
            $need_to_add_geo_data = true;
        }

        // check if geo location meta exists
        if ( false === $need_to_add_geo_data && ! empty( $csv_line_item['meta_data'] ) ) {
            $meta_data = array_column( $csv_line_item['meta_data'], 'value', 'key' );
            $dokan_geo_meta = [ 'dokan_geo_latitude', 'dokan_geo_longitude' ];

            foreach ( $dokan_geo_meta as $meta_key ) {
                if ( array_key_exists( $meta_key, $meta_data ) && empty( $meta_data[ $meta_key ] ) ) {
                    // if meta key exists and is empty, we need to insert geo data
                    $need_to_add_geo_data = true;
                    break;
                }
            }
        }

        if ( ! $need_to_add_geo_data ) {
            return;
        }

        $user_id = get_post_field( 'post_author', $product->get_id() );

        //initialize vendor geo location if available
        $dokan_geo_latitude  = get_user_meta( $user_id, 'dokan_geo_latitude', true );
        $dokan_geo_longitude = get_user_meta( $user_id, 'dokan_geo_longitude', true );
        $dokan_geo_address   = get_user_meta( $user_id, 'dokan_geo_address', true );

        //if vendor geo location is not found, get from default
        if ( empty( $dokan_geo_latitude ) || empty( $dokan_geo_longitude ) ) {
            $default_locations   = dokan_geo_get_default_location();
            $dokan_geo_latitude  = $default_locations['latitude'];
            $dokan_geo_longitude = $default_locations['longitude'];
            $dokan_geo_address   = $default_locations['address'];
        }

        update_post_meta( $product->get_id(), 'dokan_geo_latitude', $dokan_geo_latitude );
        update_post_meta( $product->get_id(), 'dokan_geo_longitude', $dokan_geo_longitude );
        update_post_meta( $product->get_id(), 'dokan_geo_public', 1 );
        update_post_meta( $product->get_id(), 'dokan_geo_address', $dokan_geo_address );
    }
}
