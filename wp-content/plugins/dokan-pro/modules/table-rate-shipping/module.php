<?php

namespace WeDevs\DokanPro\Modules\TableRateShipping;

use WeDevs\DokanPro\Modules\TableRate\Models\DistanceRateShipping;

class Module {

    /**
     * Constructor for the Dokan Table Rate Shipping class
     *
     * Sets up all the appropriate hooks and actions
     * within our plugin.
     *
     * @uses is_admin()
     * @uses add_action()
     */
    public function __construct() {
        add_action( 'dokan_activated_module_table_rate_shipping', [ $this, 'activate' ] );
        add_action( 'plugins_loaded', [ $this, 'init' ] );
    }

    /**
     * Init the modules
     *
     * @since 3.4.0
     *
     * @return void
     */
    public function init() {
        $this->define();
        $this->initiate();
        $this->hooks();
    }

    /**
     * Defined
     *
     * @since 3.4.0
     *
     * @return void
     */
    public function define() {
        define( 'DOKAN_TABLE_RATE_SHIPPING_DIR', __DIR__ );
        define( 'DOKAN_TABLE_RATE_SHIPPING_INC_DIR', DOKAN_TABLE_RATE_SHIPPING_DIR . '/includes' );
        define( 'DOKAN_TABLE_RATE_SHIPPING_ASSETS_DIR', plugins_url( 'assets', __FILE__ ) );
    }

    /**
     * Initiate all classes
     *
     * @since 3.4.0
     *
     * @return void
     */
    public function initiate() {
        new \WeDevs\DokanPro\Modules\TableRate\Notices();
        new \WeDevs\DokanPro\Modules\TableRate\Method();
        new \WeDevs\DokanPro\Modules\TableRate\DistanceRateMethod();
        new \WeDevs\DokanPro\Modules\TableRate\Hooks();
        new \WeDevs\DokanPro\Modules\TableRate\TemplateHooks();
        new \WeDevs\DokanPro\Modules\TableRate\DistanceTemplateHooks();
        new \WeDevs\DokanPro\Modules\TableRate\OrderShippingTaxHandler();
    }

    /**
     * Init all hooks
     *
     * @since 3.4.0
     *
     * @return void
     */
    public function hooks() {
        add_action( 'init', [ $this, 'register_scripts' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'load_scripts' ] );
        add_filter( 'dokan_set_template_path', [ $this, 'load_product_trs_templates' ], 10, 3 );
        add_filter( 'woocommerce_shipping_methods', [ $this, 'register_shipping' ] );
        add_action( 'wp_ajax_dokan_table_rate_delete', [ $this, 'table_rate_delete' ] );
        add_action( 'wp_ajax_dokan_distance_rate_delete', [ $this, 'distance_rate_delete' ] );
        add_filter( 'dokan_rest_api_class_map', [ $this, 'rest_api_class_map' ] );
	    add_filter( 'dokan_get_dashboard_nav_template_dependency', [ $this, 'add_dashboard_settings_template_dependency' ] );
    }

    /**
     * Get plugin path
     *
     * @since 3.4.0
     *
     * @return string
     **/
    public function plugin_path() {
        return untrailingslashit( plugin_dir_path( __FILE__ ) );
    }

    /**
     * Register shipping method
     *
     * @since 3.4.0
     *
     * @param array $methods
     *
     * @return array $methods
     */
    public function register_shipping( $methods ) {
        if ( 'sell_digital' === dokan_get_option( 'global_digital_mode', 'dokan_general', 'sell_both' ) ) {
            return $methods;
        }

        $methods['dokan_table_rate_shipping']    = \WeDevs\DokanPro\Modules\TableRate\Method::class;
        $methods['dokan_distance_rate_shipping'] = \WeDevs\DokanPro\Modules\TableRate\DistanceRateMethod::class;

        return $methods;
    }

    /**
     * Load global scripts
     *
     * @since 3.4.0
     *
     * @return void
     */
    public function load_scripts() {
        global $wp;

        if ( dokan_is_seller_dashboard() ) {
            // Vendor dashboard app scripts for table rate shipping.
            wp_enqueue_script( 'dokan-table-rate-shipping-app' );
            wp_set_script_translations( 'dokan-table-rate-shipping-app', 'dokan' );

            if ( isset( $wp->query_vars['settings'] ) ) {
                $this->enqueue_scripts();
            }
        }

        if ( dokan_is_product_edit_page() ) {
            $this->enqueue_scripts();
        }

        if ( isset( $wp->query_vars['products'] ) && ! empty( $_GET['product_id'] ) && ! empty( $_GET['action'] ) && 'edit' === $_GET['action'] ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $this->enqueue_scripts();
        }
    }

    /**
     * Enqueue scripts
     *
     * @since 3.4.0
     *
     * @return void
     */
    public function enqueue_scripts() {
        wp_enqueue_style( 'dokan-table-rate-shipping-style' );
    }

    /**
     * Register scripts
     *
     * @since 3.7.4
     */
    public function register_scripts() {
        [ $suffix, $version ] = dokan_get_script_suffix_and_version();

        wp_register_style( 'dokan-table-rate-shipping-style', DOKAN_TABLE_RATE_SHIPPING_ASSETS_DIR . '/css/main-style.css', [], $version, 'all' );
        wp_register_script( 'dokan-shipping-table-rate-rows', DOKAN_TABLE_RATE_SHIPPING_ASSETS_DIR . '/js/table-rate-shipping.js', [ 'jquery', 'wp-util' ], $version, true );

        $script_assets = DOKAN_TABLE_RATE_SHIPPING_DIR . '/assets/js/table-rate-shipping-vendor.asset.php';
        if ( file_exists( $script_assets ) ) {
            $vendor_asset     = require $script_assets;
            $dependencies     = $vendor_asset['dependencies'] ?? [];
            $version          = $vendor_asset['version'] ?? '';
	        $dokan_appearance = get_option( 'dokan_appearance', [] );

            wp_register_script(
                'dokan-table-rate-shipping-app',
                DOKAN_TABLE_RATE_SHIPPING_ASSETS_DIR . '/js/table-rate-shipping-vendor.js',
                $dependencies,
                $version,
                true
            );

            wp_localize_script(
                'dokan-table-rate-shipping-app',
                'dokanTableRateShippingHelper',
                [
	                'enable_woo_shipping' => wc_shipping_enabled(),
                    'shipping_class'      => WC()->shipping->get_shipping_classes(),
                    'weight_unit'         => get_option( 'woocommerce_weight_unit' ),
	                'map_api_key'         => $dokan_appearance['gmap_api_key'] ?? '',
                ]
            );
        }

        $params = [
            'ajax_url'           => admin_url( 'admin-ajax.php' ),
            'delete_rates_nonce' => wp_create_nonce( 'dokan-delete-table-rate' ),
            'i18n' => [
                'order'        => __( 'Order', 'dokan' ),
                'item'         => __( 'Item', 'dokan' ),
                'line_item'    => __( 'Line Item', 'dokan' ),
                'class'        => __( 'Class', 'dokan' ),
                'delete_rates' => __( 'Delete the selected rates?', 'dokan' ),
                'dupe_rates'   => __( 'Duplicate the selected rates?', 'dokan' ),
            ],
        ];

        wp_localize_script( 'dokan-shipping-table-rate-rows', 'dokan_trs_params', apply_filters( 'dokan_trs_params', $params ) );
    }

    /**
     * Load table rate shipping templates
     *
     * @since 3.4.0
     *
     * @return string
     **/
    public function load_product_trs_templates( $template_path, $template, $args ) {
        if ( isset( $args['is_table_rate_shipping'] ) && $args['is_table_rate_shipping'] ) {
            return $this->plugin_path() . '/templates';
        }

        return $template_path;
    }

    /**
     * Activates the module
     *
     * @since 3.4.0
     *
     * @return void
     */
    public function activate() {
        $this->create_tables();
        $this->flush_rewrite_rules();
    }

    /**
     * Flush rewrite rules
     *
     * @since 3.4.0
     *
     * @return void
     */
    public function flush_rewrite_rules() {
        dokan()->rewrite->register_rule();
        flush_rewrite_rules( true );
    }

    /**
     * Get table rate shipping info
     *
     * @since 3.4.0
     *
     * @param int $id
     * @param int $seller_id
     *
     * @return array|null|object
     */
    public function get_table_rate_info( $id = 0, $seller_id = 0 ) {
        global $wpdb;

        $seller_id = empty( $seller_id ) ? dokan_get_current_user_id() : $seller_id;

        if ( $id ) {
            $results = $wpdb->get_results( $wpdb->prepare( "SELECT * from {$wpdb->prefix}dokan_table_rate_shipping WHERE id = %d AND seller_id = %d", $id, $seller_id ) );

            return $results;
        }

        $results = $wpdb->get_results( $wpdb->prepare( "SELECT * from {$wpdb->prefix}dokan_table_rate_shipping WHERE seller_id = %d", $seller_id ) );

        return $results;
    }

    /**
     * Get distance rate shipping info
     *
     * @since 3.4.2
     *
     * @param int $id
     * @param int $seller_id
     *
     * @return array $results
     */
    public function get_distance_rate_info( $id = 0, $seller_id = 0 ) {
        // Get the rate info by seller id.
        $seller_id = empty( $seller_id ) ? dokan_get_current_user_id() : $seller_id;

        return DistanceRateShipping::get_rate_info_by_seller( $id, $seller_id );
    }

    /**
     * Get Shipping Method for a method
     *
     * @since 3.4.0
     *
     * @param int $instance_id
     *
     * @return void
     */
    public function get_shipping_method( $instance_id ) {
        if ( empty( $instance_id ) ) {
            return;
        }

        global $wpdb;

        $result = $wpdb->get_row( $wpdb->prepare( "SELECT * from {$wpdb->prefix}dokan_shipping_zone_methods WHERE instance_id = %d", $instance_id ) );
        $method = array();

        if ( empty( $result ) ) {
            return;
        }

        $default_settings = array(
            'title'       => __( 'Table Rate Shipping', 'dokan' ),
            'description' => __( 'Lets you charge a rate for shipping', 'dokan' ),
            'cost'        => '0',
            'tax_status'  => 'none',
        );

        $settings = ! empty( $result->settings ) ? maybe_unserialize( $result->settings ) : array();
        $settings = wp_parse_args( $settings, $default_settings );

        $method['instance_id'] = $result->instance_id;
        $method['id']          = $result->method_id;
        $method['enabled']     = ( $result->is_enabled ) ? 'yes' : 'no';
        $method['title']       = $settings['title'];
        $method['settings']    = array_map( 'stripslashes_deep', maybe_unserialize( $settings ) );

        return $method;
    }

    /**
     * Get Shipping Method for a method
     *
     * @since 3.4.0
     *
     * @param int $instance_id
     *
     * @return array|void
     */
    public function get_normalized_shipping_rates( int $instance_id = 0 ) {
        $instance_id = isset( $_GET['instance_id'] ) ? intval( wp_unslash( $_GET['instance_id'] ) ) : $instance_id; // phpcs:ignore

        if ( ! $instance_id ) {
            return;
        }

        $shipping_rates    = $this->get_shipping_rates( ARRAY_A, $instance_id );
        $decimal_separator = wc_get_price_decimal_separator();

        $normalize_keys = array(
            'rate_cost',
            'rate_cost_per_item',
            'rate_cost_per_weight_unit',
            'rate_cost_percent',
            'rate_max',
            'rate_min',
        );

        foreach ( $shipping_rates as $index => $shipping_rate ) {
            foreach ( $normalize_keys as $key ) {
                if ( ! isset( $shipping_rate[ $key ] ) ) {
                    continue;
                }

                $shipping_rates[ $index ][ $key ] = str_replace( '.', $decimal_separator, $shipping_rates[ $index ][ $key ] );
            }
        }

        return $shipping_rates;
    }

    /**
     * Get raw shipping rates from the DB.
     *
     * Optional filter helper for integration with other plugins.
     *
     * @param string $output Output format.
     * @return mixed
     */
    public function get_shipping_rates( $output = OBJECT, $instance_id = null ) {
        global $wpdb;

        $rates = $wpdb->get_results( $wpdb->prepare( "SELECT * from {$wpdb->prefix}dokan_table_rate_shipping WHERE instance_id = %d ORDER BY rate_order ASC", $instance_id ), $output );

        return apply_filters( 'dokan_table_rate_get_shipping_rates', $rates );
    }

    /**
     * Get Shipping Method for a method
     *
     * @since 3.4.2
     *
     * @param int $instance_id
     *
     * @return void
     */
    public function get_normalized_shipping_distance_rates( int $instance_id = 0 ) {
        $instance_id = isset( $_GET['instance_id'] ) ? intval( wp_unslash( $_GET['instance_id'] ) ) : $instance_id; // phpcs:ignore

        if ( ! $instance_id ) {
            return;
        }

        $shipping_rates    = $this->get_shipping_distance_rates( ARRAY_A, $instance_id );
        $decimal_separator = wc_get_price_decimal_separator();

        $normalize_keys = array(
            'rate_cost',
            'rate_cost_unit',
            'rate_fee',
            'rate_max',
            'rate_min',
        );

        foreach ( $shipping_rates as $index => $shipping_rate ) {
            foreach ( $normalize_keys as $key ) {
                if ( isset( $shipping_rate[ $key ] ) ) {
                    $shipping_rates[ $index ][ $key ] = str_replace( '.', $decimal_separator, $shipping_rates[ $index ][ $key ] );
                }
            }
        }

        return $shipping_rates;
    }

    /**
     * Get raw shipping distance rates from the DB.
     *
     * Optional filter helper for integration with other plugins.
     *
     * @param string $output Output format.
     * @return mixed
     */
    public function get_shipping_distance_rates( $output = OBJECT, $instance_id = null ) {
        return apply_filters(
            'dokan_distance_rate_get_shipping_rates',
            DistanceRateShipping::get_shipping_rates( $output, $instance_id )
        );
    }

    /**
     * Delete table rate
     *
     * @since 3.4.0
     *
     * @return void
     */
    public function table_rate_delete() {
        check_ajax_referer( 'dokan-delete-table-rate', 'security' );

        if ( isset( $_POST['rate_id'] ) && is_array( $_POST['rate_id'] ) ) {
            $rate_ids = array_map( 'intval', $_POST['rate_id'] );
        } else {
            $rate_ids = array( intval( $_POST['rate_id'] ) );
        }

        $this->delete_table_rates( $rate_ids );
    }

    /**
     * Delete table rates by IDs
     *
     * @param array $rate_ids
     * @param int   $zone_id     Zone ID
     * @param int   $instance_id Instance ID
     *
     * @return bool|int|null|void
     */
    public function delete_table_rates( array $rate_ids, int $zone_id = 0, int $instance_id = 0 ) {
        if ( ! $rate_ids ) {
            return;
        }

        global $wpdb;

        /**
         * Filter rate IDs before deletion
         *
         * @since 4.0.0
         *
         * @param array $rate_ids
         * @param int   $zone_id
         * @param int   $instance_id
         */
        $rate_ids = apply_filters( 'dokan_table_rate_shipping_delete_args', $rate_ids, $zone_id, $instance_id );

        // Build placeholder string based on the number of IDs
        $placeholders       = implode( ',', array_fill( 0, count( $rate_ids ), '%d' ) );
        $delete_table_rates = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}dokan_table_rate_shipping WHERE rate_id IN ({$placeholders})",
                array_map( 'intval', $rate_ids )
            )
        );

        /**
         * Fires after table rate shipping rates are deleted
         *
         * @since 4.0.0
         *
         * @param array $rate_ids    Array of deleted rate IDs
         * @param int   $zone_id     Zone ID
         * @param int   $instance_id Instance ID
         */
        do_action( 'dokan_table_rate_shipping_rates_deleted', $rate_ids, $zone_id, $instance_id );

        return $delete_table_rates;
    }

    /**
     * Delete distance rate
     *
     * @since 3.4.2
     *
     * @return void
     */
    public function distance_rate_delete() {
        check_ajax_referer( 'dokan-delete-table-rate', 'security' );

	    if ( isset( $_POST['rate_id'] ) && is_array( $_POST['rate_id'] ) ) {
		    $rate_ids = array_map( 'intval', wp_unslash( $_POST['rate_id'] ) );
	    } else {
		    $rate_ids = array( intval( wp_unslash( $_POST['rate_id'] ) ) );
	    }

	    $this->delete_distance_rates( $rate_ids );
    }

	/**
	 * Delete distance rates by IDs
	 *
	 * @since 3.4.2
	 *
	 * @param array $rate_ids
	 * @param int   $zone_id     Zone ID
	 * @param int   $instance_id Instance ID
	 *
	 * @return bool|int|null|void
	 */
	public function delete_distance_rates( array $rate_ids, int $zone_id = 0, int $instance_id = 0 ) {
		if ( ! $rate_ids ) {
			return;
		}

		/**
		 * Filter distance rate IDs before deletion
		 *
		 * @since 4.1.3
		 *
		 * @param array $rate_ids
		 * @param int   $zone_id
		 * @param int   $instance_id
		 */
		$rate_ids = apply_filters( 'dokan_distance_rate_shipping_delete_args', $rate_ids, $zone_id, $instance_id );

		// Delete distance rate shipping rates.
		$delete_distance_rates = DistanceRateShipping::delete_rates( $rate_ids );

		/**
		 * Fires after distance rate shipping rates are deleted
		 *
		 * @since 4.1.3
		 *
		 * @param array $rate_ids    Array of deleted rate IDs
		 * @param int   $zone_id     Zone ID
		 * @param int   $instance_id Instance ID
		 */
		do_action( 'dokan_distance_rate_shipping_rates_deleted', $rate_ids, $zone_id, $instance_id );

		return $delete_distance_rates;
	}

    /**
     * Validated zone data
     *
     * @since 3.4.0
     *
     * @return false|int
     */
    public function get_zone() {
        $zone_id = isset( $_GET['zone_id'] ) ? intval( wp_unslash( $_GET['zone_id'] ) ) : 0; // phpcs:ignore

        if ( empty( $zone_id ) ) {
            return 0;
        }

        global $wpdb;

        $seller_id = dokan_get_current_user_id();
        $get_zone  = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) from {$wpdb->prefix}dokan_shipping_zone_methods WHERE zone_id = %d AND seller_id = %d", $zone_id, $seller_id ) );

        return $get_zone ? $zone_id : false;
    }

    /**
     * Validated instance data
     *
     * @since 3.4.0
     *
     * @return false|int
     */
    public function get_instance() {
        $instance_id = isset( $_GET['instance_id'] ) ? intval( wp_unslash( $_GET['instance_id'] ) ) : 0; // phpcs:ignore

        if ( empty( $instance_id ) ) {
            return false;
        }

        global $wpdb;

        $seller_id    = dokan_get_current_user_id();
        $get_instance = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) from {$wpdb->prefix}dokan_shipping_zone_methods WHERE instance_id = %d AND seller_id = %d", $instance_id, $seller_id ) );

        return $get_instance ? $instance_id : false;
    }

    /**
     * Rest api class map
     *
     * @since 4.0.0
     *
     * @param array $classes
     *
     * @return array
     */
    public function rest_api_class_map( $classes ) {
        $class[ DOKAN_TABLE_RATE_SHIPPING_INC_DIR . '/REST/TableRateController.php' ]    = '\WeDevs\DokanPro\Modules\TableRate\REST\TableRateController';
        $class[ DOKAN_TABLE_RATE_SHIPPING_INC_DIR . '/REST/DistanceRateController.php' ] = '\WeDevs\DokanPro\Modules\TableRate\REST\DistanceRateController';

        $class[ DOKAN_TABLE_RATE_SHIPPING_INC_DIR . '/REST/DistanceRateSettingsController.php' ]      = '\WeDevs\DokanPro\Modules\TableRate\REST\DistanceRateSettingsController';
        $class[ DOKAN_TABLE_RATE_SHIPPING_INC_DIR . '/REST/TableRateShippingSettingsController.php' ] = '\WeDevs\DokanPro\Modules\TableRate\REST\TableRateShippingSettingsController';

        return array_merge( $classes, $class );
    }

    /**
     * Creates table rate shipping table
     *
     * @since 3.4.0
     *
     * @return void
     */
    private function create_tables() {
        global $wpdb;

        include_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $collate = $wpdb->get_charset_collate();

        $table_rate = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}dokan_table_rate_shipping` (
                  `rate_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                  `vendor_id` int(11) NOT NULL,
                  `zone_id` int(11) NOT NULL,
                  `instance_id` int(11) NOT NULL,
                  `rate_class` varchar(150) NOT NULL,
                  `rate_condition` varchar(150) NOT NULL,
                  `rate_min` varchar(50) NOT NULL,
                  `rate_max` varchar(50) NOT NULL,
                  `rate_cost` varchar(50) NOT NULL,
                  `rate_cost_per_item` varchar(50) NOT NULL,
                  `rate_cost_per_weight_unit` varchar(50) NOT NULL,
                  `rate_cost_percent` varchar(50) NOT NULL,
                  `rate_label` longtext NOT NULL,
                  `rate_priority` int(5) NOT NULL,
                  `rate_order` int(11) NOT NULL,
                  `rate_abort` int(5) NOT NULL,
                  `rate_abort_reason` longtext NOT NULL,
                  PRIMARY KEY  (`rate_id`),
                  KEY `key_vendor_id` (`vendor_id`),
                  KEY `key_zone_id` (`zone_id`),
                  KEY `key_instance_id` (`instance_id`)
                ) ENGINE=InnoDB {$collate}";

        dbDelta( $table_rate );

        $distance_rate = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}dokan_distance_rate_shipping` (
                  `rate_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                  `vendor_id` int(11) NOT NULL,
                  `zone_id` int(11) NOT NULL,
                  `instance_id` int(11) NOT NULL,
                  `rate_condition` varchar(150) NOT NULL,
                  `rate_min` varchar(50) NOT NULL,
                  `rate_max` varchar(50) NOT NULL,
                  `rate_cost` varchar(50) NOT NULL,
                  `rate_cost_unit` varchar(50) NOT NULL,
                  `rate_fee` varchar(50) NOT NULL,
                  `rate_break` int(5) NOT NULL,
                  `rate_abort` int(5) NOT NULL,
                  `rate_order` int(11) NOT NULL DEFAULT 0,
                  PRIMARY KEY  (`rate_id`),
                  KEY `key_vendor_id` (`vendor_id`),
                  KEY `key_zone_id` (`zone_id`),
                  KEY `key_instance_id` (`instance_id`)
                ) ENGINE=InnoDB {$collate}";

        dbDelta( $distance_rate );
    }

	/**
	 * Adds dashboard settings template dependencies.
	 * This method modifies the given dependencies array to include
	 * specific dependencies required for the dashboard settings template.
	 *
	 * @param array $dependencies The array of existing dependencies.
	 *
	 * @return array The modified array with additional dependencies.
	 */
	public function add_dashboard_settings_template_dependency( array $dependencies ) {
		$dependencies['settings/shipping'] = [
			[
				'slug' => 'rows-settings',
				'args' => [ 'is_table_rate_shipping' => true ],
			],
			[
				'slug' => 'classes-settings',
				'args' => [ 'is_table_rate_shipping' => true ],
			],
			[
				'slug' => 'general-settings',
				'args' => [ 'is_table_rate_shipping' => true ],
			],
			[
				'slug' => 'header-settings',
				'args' => [ 'is_table_rate_shipping' => true ],
			],
			[
				'slug' => 'rows-classes-settings',
				'args' => [ 'is_table_rate_shipping' => true ],
			],
			[
				'slug' => 'rows-settings',
				'args' => [ 'is_table_rate_shipping' => true ],
			],
			[
				'slug' => 'settings',
				'args' => [ 'is_table_rate_shipping' => true ],
			],
			[
				'slug' => 'distance-rate/settings',
				'args' => [ 'is_table_rate_shipping' => true ],
			],
			[
				'slug' => 'distance-rate/general-settings',
				'args' => [ 'is_table_rate_shipping' => true ],
			],
			[
				'slug' => 'distance-rate/rows-settings',
				'args' => [ 'is_table_rate_shipping' => true ],
			],
		];

		return $dependencies;
	}
}
