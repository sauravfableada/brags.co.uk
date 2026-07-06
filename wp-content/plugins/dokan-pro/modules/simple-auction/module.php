<?php

namespace WeDevs\DokanPro\Modules\Auction;

use DokanAuctionCache;
use WC_Product_Auction;
use WeDevs\Dokan\Product\Hooks as ProductHooks;
use WP_Roles;
use WP_User;
use WeDevs\Dokan\ProductCategory\Helper as CategoryHelper;

/**
 * Dokan_Auction class
 *
 * @class Dokan_Auction The class that holds the entire Dokan_Auction plugin
 */
class Module {

    /**
     * Module version
     *
     * @since 3.2.2
     *
     * @var string
     */
    public $version = DOKAN_PRO_PLUGIN_VERSION;

    /**
     * Script suffix.
     *
     * @var null
     */
    public $suffix = '';

    /**
     * Constructor for the Dokan_Auction class
     *
     * Sets up all the appropriate hooks and actions
     * within our plugin.
     *
     * @uses register_activation_hook()
     * @uses register_deactivation_hook()
     * @uses is_admin()
     * @uses add_action()
     */
    public function __construct() {

        if ( ! defined( 'DOKAN_AUCTION_DIR' ) ) {
            define( 'DOKAN_AUCTION_DIR', dirname( __FILE__ ) );
        }

        // flush rewrite rules
        add_action( 'woocommerce_flush_rewrite_rules', [ $this, 'flush_rewrite_rules' ] );
        // flush rewrite rules after wooCommerce simple auction plugin is installed
        add_action( 'activated_plugin', [ $this, 'after_plugins_activated' ] );

        include_once DOKAN_AUCTION_DIR . '/includes/DependencyNotice.php';

        $dependency = new DependencyNotice();

        if ( $dependency->is_missing_dependency() ) {
            return;
        }

        $this->includes();

        // Hooking all caps
        add_filter( 'dokan_get_all_cap', array( $this, 'add_capabilities' ) );
        add_filter( 'dokan_get_all_cap_labels', array( $this, 'add_caps_labels' ) );

        // insert auction product type
        add_filter( 'dokan_get_product_types', array( $this, 'insert_auction_product_type' ) );

        // Loads frontend scripts and styles
        add_action( 'init', array( $this, 'register_scripts' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'dokan_seller_meta_fields', array( $this, 'add_admin_user_options' ) );
        add_action( 'dokan_process_seller_meta_fields', array( $this, 'save_admin_user_option' ) );
        add_filter( 'dokan_get_dashboard_nav', array( $this, 'add_auction_dashboard_menu' ), 20 );
        add_filter( 'dokan_settings_selling_option_vendor_capability', array( $this, 'add_auction_dokan_settings' ) );
        add_filter( 'dokan_query_var_filter', array( $this, 'add_dokan_auction_endpoint' ) );
        add_filter( 'dokan_set_template_path', array( $this, 'load_auction_templates' ), 10, 3 );
        add_action( 'dokan_load_custom_template', array( $this, 'load_dokan_auction_template' ) );
        add_action( 'dokan_auction_before_general_options', [ $this, 'load_downloadable_virtual_option' ] );
        add_action( 'user_register', array( $this, 'dokan_admin_user_register_enable_auction' ), 16 );
        add_action( 'dokan_product_listing_exclude_type', array( $this, 'product_listing_exclude_auction' ), 11 );

        add_filter( 'dokan_dashboard_nav_active', array( $this, 'dashboard_auction_active_menu' ) );

        // WooCommerce Simple Auction vendor restriction
        add_filter( 'woocommerce_product_add_to_cart_text', [ $this, 'bid_now_button' ], 11, 2 );
        add_filter( 'wc_get_template', [ $this, 'override_single_page_bidding_form' ], 10, 2 );

        // send bid email to admin and vendor
        add_filter( 'woocommerce_email_recipient_bid_note', array( $this, 'send_bid_email' ), 99, 2 );

        add_filter( 'dokan_localized_args', array( $this, 'set_localized_args' ) );

        add_action( 'dokan_activated_module_auction', array( $this, 'activate' ) );

        add_filter( 'dokan_get_edit_product_url', [ $this, 'modify_edit_product_url' ], 10, 2 );

        add_action( 'wp_footer', [ $this, 'load_add_category_modal' ] );
        add_action( 'woocommerce_process_product_meta', array( $this, 'save_per_product_commission_options' ), 15, 1 );

        // REST API.
        add_filter( 'dokan_rest_api_class_map', [ $this, 'register_rest_controller' ] );

        // Copy thumbnail when duplicating auction products.
        add_action( 'dokan_product_duplicate_after_save', [ $this, 'copy_auction_thumbnail_on_duplicate' ], 10, 2 );

        // React vendor dashboard.
        add_filter( 'dokan_react_frontend_localized_args', [ $this, 'add_auction_listing_localized_args' ] );
    }

    /**
     * Save per product commission options
     *
     * @since 3.16.0
     *
     * @param integer $post_id
     *
     * @return void
     */
    public static function save_per_product_commission_options( $post_id ) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $product_type = empty( $_POST['product-type'] ) ? 'simple' : sanitize_title( wp_unslash( $_POST['product-type'] ) );

        if ( $product_type !== 'auction' ) {
            return;
        }

        ProductHooks::save_per_product_commission_options( $post_id );
    }

    /**
     * Register activation hook
     *
     * @since  1.5.2
     *
     * @return void
     */
    public function activate() {
        global $wp_roles;

        if ( class_exists( 'WP_Roles' ) && ! isset( $wp_roles ) ) {
            // @codingStandardsIgnoreLine
            $wp_roles = new WP_Roles();
        }

        $all_cap = array(
            'dokan_view_auction_menu',
            'dokan_add_auction_product',
            'dokan_edit_auction_product',
            'dokan_delete_auction_product',
            'dokan_duplicate_auction_product',
        );

        foreach ( $all_cap as $cap ) {
            $wp_roles->add_cap( 'seller', $cap );
            $wp_roles->add_cap( 'administrator', $cap );
            $wp_roles->add_cap( 'shop_manager', $cap );
        }

        // flush rewrite rules after plugin is activated
        $this->flush_rewrite_rules();
    }

    /**
     * @param $plugin
     *
     * @return void
     */
    public function after_plugins_activated( $plugin ) {
        if ( 'woocommerce-simple-auctions/woocommerce-simple-auctions.php' !== $plugin ) {
            return;
        }

        // flush rewrite rules after adding capabilities.
        $this->activate();
    }

    /**
     * Flush rewrite rules
     *
     * @since 3.3.1
     *
     * @return void
     */
    public function flush_rewrite_rules() {
        if ( function_exists( 'dokan' ) ) {
            add_filter( 'dokan_query_var_filter', array( $this, 'add_dokan_auction_endpoint' ) );
            dokan()->rewrite->register_rule();
        }

        flush_rewrite_rules();
    }

    /**
     * Add capabilities
     *
     * @since 1.0.0
     *
     * @param array $capabilities
     *
     * @return array
     */
    public function add_capabilities( array $capabilities ): array {
        $capabilities['menu']['dokan_view_auction_menu'] = __( 'View auction menu', 'dokan' );

        $capabilities['auction'] = array(
            'dokan_add_auction_product'    => __( 'Add auction product', 'dokan' ),
            'dokan_edit_auction_product'   => __( 'Edit auction product', 'dokan' ),
            'dokan_delete_auction_product' => __( 'Delete auction product', 'dokan' ),
            'dokan_duplicate_auction_product' => __( 'Duplicate auction product', 'dokan' ),
        );

        return $capabilities;
    }

    /**
     * Add caps labels
     *
     * @since 3.0.0
     *
     * @param array $caps
     *
     * @return array
     */
    public function add_caps_labels( array $caps ): array {
        $caps['auction'] = __( 'Auction', 'dokan' );

        return $caps;
    }

    /**
     * Copy the featured image from the original auction product to the duplicate.
     *
     * @since 5.0.0
     *
     * @param \WC_Product $duplicate The saved duplicate product.
     * @param \WC_Product $original  The original product.
     *
     * @return void
     */
    public function copy_auction_thumbnail_on_duplicate( $duplicate, $original ) {
        if ( 'auction' !== $original->get_type() ) {
            return;
        }

        $image_id = (int) get_post_thumbnail_id( $original->get_id() );

        if ( $image_id && $duplicate->get_id() ) {
            set_post_thumbnail( $duplicate->get_id(), $image_id );
        }
    }

    /**
     * Register the Auction REST controller into Dokan's REST manager.
     *
     * @since 5.0.0
     *
     * @param array $class_map Existing class map.
     *
     * @return array
     */
    public function register_rest_controller( array $class_map ): array {
        $class[ DOKAN_AUCTION_DIR . '/includes/REST/AuctionController.php' ] =
            '\WeDevs\DokanPro\Modules\Auction\REST\AuctionController';

        return array_merge( $class_map, $class );
    }

    /**
     * Add auction listing data to the dokanFrontend localized object.
     * Passes button visibility, URLs, and subscription info so the React
     * auction list can render without a PHP round-trip.
     *
     * @since 5.0.0
     *
     * @param array $args Existing localized args.
     *
     * @return array
     */
    public function add_auction_listing_localized_args( array $args ): array {
        if ( ! is_user_logged_in() ) {
            return $args;
        }

        $user_id = dokan_get_current_user_id();

        if ( ! dokan_is_seller_enabled( $user_id ) ) {
            return $args;
        }

        $new_product_url = dokan_get_navigation_url( 'new-auction-product' );

        $activity_url = dokan_get_navigation_url( 'new' ) . '#/auction-activity';

        $auction_url = str_replace( 'new-auction-product', 'auction', $new_product_url );

        $args['auction_listing'] = [
            'can_add_product'        => dokan_is_seller_enabled( $user_id ) && current_user_can( 'dokan_add_auction_product' ),
            'new_product_url'        => esc_url_raw( $new_product_url ),
            'activity_url'           => esc_url_raw( $activity_url ),
            'auction_url'            => esc_url_raw( $auction_url ),
            'can_see_customer_info'  => 'off' === dokan_get_option( 'hide_customer_info', 'dokan_selling', 'off' ),
        ];

        return $args;
    }

    /**
     * Insert auction product type
     *
     * @param  array $types
     *
     * @return array
     */
    public function insert_auction_product_type( array $types ): array {
        $types['auction'] = __( 'Auction Product', 'dokan' );

        return $types;
    }

    /**
    * Include files
    *
    * @since 1.5.0
    *
    * @return void
    **/
    public function includes() {
        require_once dirname( __FILE__ ) . '/classes/class-auction.php';
        require_once dirname( __FILE__ ) . '/includes/dokan-auction-functions.php';

        // Init Cache for Auction module
        require_once dirname( __FILE__ ) . '/includes/DokanAuctionCache.php';
        new DokanAuctionCache();
    }

    /**
     * Register Scripts
     *
     * @since 3.7.4
     *
     * @return void
     */
    public function register_scripts() {
        list( $this->suffix, $this->version ) = dokan_get_script_suffix_and_version();
        wp_register_script( 'dokan-auctiondasd-timepicker', plugins_url( 'assets/js/jquery-ui-timepicker.js', __FILE__ ), array( 'jquery' ), $this->version, true );
        wp_register_script( 'auction-product', plugins_url( 'assets/js/auction-product.js', __FILE__ ), [ 'jquery', 'dokan-script', 'dokan-pro-script' ], $this->version, true );
        wp_register_style( 'dokan-auction-styles', plugins_url( 'assets/css/dokan-auction-style.css', __FILE__ ), false, $this->version );

        // React vendor dashboard bundle (auction list page).
        $asset_file = DOKAN_AUCTION_DIR . '/assets/js/vendor-dashboard.asset.php';
        $asset      = file_exists( $asset_file ) ? require $asset_file : [
            'dependencies' => [],
            'version' => $this->version,
        ];

        // Must depend on dokan-react-frontend so our domReady filter callback
        // is always registered before the SPA's getRoutes() re-runs after the
        // initial skeleton/loading phase resolves. Mirrors vendor-staff pattern.
        $asset['dependencies'][] = 'dokan-react-frontend';

        wp_register_script(
            'dokan-auction-vendor-dashboard',
            plugins_url( 'assets/js/vendor-dashboard.js', __FILE__ ),
            array_unique( $asset['dependencies'] ),
            $asset['version'],
            true
        );
    }

    /**
     * Enqueue admin scripts
     * Allows plugin assets to be loaded.
     *
     * @uses wp_enqueue_script()
     * @uses wp_localize_script()
     * @uses wp_enqueue_style
     *
     * @return void
     */
    public function enqueue_scripts() {
        global $wp;

        if ( isset( $wp->query_vars['auction'] ) || isset( $wp->query_vars['new-auction-product'] ) || isset( $wp->query_vars['auction-activity'] ) ) {
            wp_enqueue_style( 'dokan-auction-styles' );
        }

        if ( isset( $wp->query_vars['new-auction-product'] ) || isset( $wp->query_vars['auction-activity'] ) ) {
            wp_enqueue_script( 'jquery' );
            wp_enqueue_script( 'dokan-form-validate' );
            wp_enqueue_script( 'jquery-ui' );
            wp_enqueue_script( 'jquery-ui-datepicker' );
            wp_enqueue_script( 'dokan-auctiondasd-timepicker' );
            wp_enqueue_script( 'auction-product' );
            wp_enqueue_media();
        }

        // @codingStandardsIgnoreLine
        if ( isset( $wp->query_vars['auction'] ) && isset( $_GET['action'] ) && $_GET['action'] == 'edit' ) {
            wp_enqueue_script( 'jquery' );
            wp_enqueue_script( 'jquery-ui' );
            wp_enqueue_script( 'jquery-ui-datepicker' );
            wp_enqueue_script( 'dokan-auctiondasd-timepicker' );
            wp_enqueue_media();
        }

        if ( isset( $wp->query_vars['auction'] ) || isset( $wp->query_vars['auction-activity'] ) ) {
            wp_enqueue_style( 'dokan-date-range-picker' );
            wp_enqueue_script( 'dokan-date-range-picker' );
        }

        // Multi-step category box scripts.
        if ( ( dokan_is_seller_dashboard() && isset( $wp->query_vars['new-auction-product'] ) ) || ( dokan_is_seller_dashboard() && isset( $wp->query_vars['auction'] ) ) ) { // phpcs:ignore
            CategoryHelper::enqueue_and_localize_dokan_multistep_category();
        }

        // Enqueue the React auction list bundle on the main vendor dashboard page
        if ( dokan_is_seller_dashboard() ) {
            wp_enqueue_script( 'dokan-auction-vendor-dashboard' );
        }
    }

    /**
    * Get plugin path
    *
    * @since 1.5.1
    *
    * @return string
    **/
    public function plugin_path(): string {
        return untrailingslashit( plugin_dir_path( __FILE__ ) );
    }

    /**
     * Show auction action in user profile
     *
     * @since 1.0.0
     *
     * @param WP_User $user
     *
     * @return void
     */
    public function add_admin_user_options( WP_User $user ) {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        if ( ! user_can( $user, 'dokandar' ) ) {
            return;
        }

        $auction = get_user_meta( $user->ID, 'dokan_disable_auction', true );
        ?>
        <tr>
            <th><?php esc_html_e( 'Auction', 'dokan' ); ?></th>
            <td>
                <label for="dokan_disable_auction">
                    <input type="hidden" name="dokan_disable_auction" value="no">
                    <input name="dokan_disable_auction" type="checkbox" id="dokan_disable_auction" value="yes" <?php checked( $auction, 'yes' ); ?> />
                    <?php esc_html_e( 'Disable Auction', 'dokan' ); ?>
                </label>

                <p class="description"><?php esc_html_e( 'Disable auction capability for this vendor', 'dokan' ); ?></p>
            </td>
        </tr>
        <?php
    }

    /**
     * Save admin user profile options
     *
     * @since  1.0.0
     *
     * @param  int $user_id
     *
     * @return void
     */
    public function save_admin_user_option( int $user_id ) {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        // @codingStandardsIgnoreLine
        if ( ! isset( $_POST['dokan_enable_selling'] ) ) {
            return;
        }

        // @codingStandardsIgnoreLine
        $selling = wc_clean( wp_unslash( $_POST['dokan_disable_auction'] ) );
        update_user_meta( $user_id, 'dokan_disable_auction', $selling );
    }

    /**
     * Add auction settings in dokan settings
     *
     * @since 1.0.0
     *
     * @param array $settings_fields
     *
     * @return array
     */
    public function add_auction_dokan_settings( array $settings_fields ): array {
        $settings_fields['new_seller_enable_auction'] = array(
            'name'    => 'new_seller_enable_auction',
            'label'   => __( 'New vendor Enable Auction', 'dokan' ),
            'desc'    => __( 'Make auction status enable for new registered vendor', 'dokan' ),
            'type'    => 'switcher',
            'default' => 'on',
        );

        return $settings_fields;
    }

    /**
     * Show dashboard auction menu
     *
     * @since 1.0.0
     *
     * @param array $urls
     *
     * @return array
     */
    public function add_auction_dashboard_menu( array $urls ): array {
        $menu = [
            'title'      => esc_html__( 'Auction', 'dokan' ),
            'icon'       => '<i class="fas fa-gavel"></i>',
            'url'        => dokan_get_navigation_url( 'auction' ),
            'pos'        => 185,
            'icon_name'  => 'Gavel',
            'permission' => 'dokan_view_auction_menu',
            'react_route' => 'auction',
        ];

        if ( dokan_is_seller_enabled( get_current_user_id() ) && ! dokan_is_seller_auction_disabled( get_current_user_id() ) ) {
            $urls['auction'] = $menu;
        }

        return $urls;
    }

    /**
     * Register endpoint for auction
     *
     * @since 1.0.0
     *
     * @param array $query_var
     *
     * @return array
     */
    public function add_dokan_auction_endpoint( array $query_var ): array {
        $query_var[] = 'auction';
        $query_var[] = 'new-auction-product';
        $query_var[] = 'auction-activity';

        return $query_var;
    }

    /**
     * Load dokan pro templates
     *
     * @since 1.5.1
     *
     * @param string $template_path
     * @param string $template
     * @param array $args
     *
     * @return string
     */
    public function load_auction_templates( string $template_path, string $template, array $args ): string {
        if ( isset( $args['is_auction'] ) && $args['is_auction'] ) {
            return $this->plugin_path() . '/templates';
        }

        return $template_path;
    }

    /**
     * Render auction dashboard template
     *
     * @since  1.0.0
     *
     * @param  array $query_vars
     *
     * @return void
     */
    public function load_dokan_auction_template( array $query_vars ) {
        if ( isset( $query_vars['auction'] ) ) {
            if ( ! current_user_can( 'dokan_view_auction_menu' ) ) {
                dokan_get_template_part( 'global/dokan-error', '', array(
                    'deleted' => false, 
                    'message' => __( 'You have no permission to view this auction page', 'dokan' ) 
                ) );
            } else {
                dokan_get_template_part( 'auction/template-auction', '', array( 'is_auction' => true ) );
            }
            return;
        }

        if ( isset( $query_vars['new-auction-product'] ) ) {
            if ( ! current_user_can( 'dokan_add_auction_product' ) ) {
                dokan_get_template_part(
                    'global/dokan-error',
                    '',
                    array(
                        'deleted' => false, 
                        'message' => __( 'You have no permission to view this page', 'dokan' )
                    ) 
                );
            } else {
                dokan_get_template_part(
                    'auction/new-auction-product',
                    '',
                    array( 'is_auction' => true )
                );
            }
            return;
        }

        if ( isset( $query_vars['auction-activity'] ) ) {
            if ( ! current_user_can( 'dokan_add_auction_product' ) ) {
                dokan_get_template_part(
                    'global/dokan-error',
                    '',
                    array(
                        'deleted' => false,
                        'message' => __( 'You have no permission to view this page', 'dokan' )
                    )
                );
            } else {
                // @codingStandardsIgnoreStart
                $date_from     = isset( $_GET['_auction_dates_from'] ) ? wc_clean( wp_unslash( $_GET['_auction_dates_from'] ) ) : '';
                $date_to       = isset( $_GET['_auction_dates_to'] ) ? wc_clean( wp_unslash( $_GET['_auction_dates_to'] ) ) : '';
                $search_string = isset( $_GET['auction_activity_search'] ) ? wc_clean( wp_unslash( $_GET['auction_activity_search'] ) ) : '';
                // @codingStandardsIgnoreEnd

                dokan_get_template_part( 'auction/auction-activity', '', [
                    'is_auction'    => true,
                    'date_from'     => $date_from,
                    'date_to'       => $date_to,
                    'search_string' => $search_string,
                ] );
            }
        }
    }

    /**
     * Disable selling capability by default once a seller is registered
     *
     * @since 1.0.0
     *
     * @param int $user_id
     */
    public function dokan_admin_user_register_enable_auction( int $user_id ) {
        $user = new WP_User( $user_id );
        $role = reset( $user->roles );

        if ( 'seller' === (string) $role ) {
            if ( 'off' === (string) dokan_get_option( 'new_seller_enable_auction', 'dokan_selling' ) ) {
                update_user_meta( $user_id, 'dokan_disable_auction', 'yes' );
            } else {
                update_user_meta( $user_id, 'dokan_disable_auction', 'no' );
            }
        }
    }

    /**
     * Exclude auction product from product listing
     *
     * @since 1.5.1
     *
     * @param array $product_type
     *
     * @return array
    **/
    public function product_listing_exclude_auction( array $product_type ): array {
        $product_type[] = 'auction';
        return $product_type;
    }

    /**
     * Set auction active menu in dokan dashboard
     *
     * @since  1.0.0
     *
     * @param  string $active_menu
     *
     * @return string
     */
    public function dashboard_auction_active_menu( string $active_menu ): string {
        if ( 'new-auction-product' === $active_menu || 'auction-activity' === $active_menu ) {
            $active_menu = 'auction';
        }
        return $active_menu;
    }

    /**
     * Send bid email to seller and admin
     *
     * @since 2.8.2
     *
     * @param $recipient
     * @param $object
     *
     * @return string
     */
    public function send_bid_email( $recipient, $object ): string {
        if ( ! $object ) {
            return $recipient;
        }

        $product_id = $object->get_id();

        if ( empty( $product_id ) ) {
            return $recipient;
        }

        $vendor_id    = get_post_field( 'post_author', $product_id );
        $vendor_email = dokan()->vendor->get( $vendor_id )->get_email();

        return $recipient . ',' . $vendor_email;
    }

    /**
     * Set localized args
     *
     * @since 2.8.2
     *
     * @param array $args
     *
     * @return array
     */
    public function set_localized_args( array $args ): array {
        $auction_args = [
            'datepicker' => [
                'now'         => __( 'Now', 'dokan' ),
                'done'        => __( 'Done', 'dokan' ),
                'time'        => __( 'Time', 'dokan' ),
                'hour'        => __( 'Hour', 'dokan' ),
                'minute'      => __( 'Minute', 'dokan' ),
                'second'      => __( 'Second', 'dokan' ),
                'time-zone'   => __( 'Time Zone', 'dokan' ),
                'choose-time' => __( 'Choose Time', 'dokan' ),
            ],
        ];

        return array_merge( $args, $auction_args );
    }

    /**
     * @since 3.1.4
     *
     * @param $url
     * @param $product
     *
     * @return mixed|string
     */
    public function modify_edit_product_url( $url, $product ) {
        if ( $product->get_type() === 'auction' ) {
            $url = add_query_arg(
                [
                    'product_id' => $product->get_id(),
                    'action'     => 'edit',
                ],
                dokan_get_navigation_url( 'auction' )
            );
        }
        return $url;
    }

    /**
     * Load downloadable and virtual option on product edit page
     *
     * @param int $auction_id Auction Product ID
     *
     * @return void
     */
    public function load_downloadable_virtual_option( int $auction_id ) {
        global $post;
        $is_downloadable    = 'yes' === get_post_meta( $auction_id, '_downloadable', true );
        $is_virtual         = 'yes' === get_post_meta( $auction_id, '_virtual', true );
        $digital_mode       = dokan_get_option( 'global_digital_mode', 'dokan_general', 'sell_both' );

        echo '<div class="product-edit-new-container">';
            dokan_get_template_part(
                'products/download-virtual',
                '',
                [
                    'post_id'         => $auction_id,
                    'post'            => $post,
                    'is_downloadable' => $is_downloadable,
                    'is_virtual'      => $is_virtual,
                    'digital_mode'    => $digital_mode,
                    'class'           => '',
                ]
            );
        echo '</div>';
    }

    /**
     * Returns new category select ui html elements.
     *
     * @since 3.7.5
     *
     * @return void
     */
    public function load_add_category_modal() {
        /**
         * Checking if dokan dashboard or add product page or product edit page or product list.
         * Because without those page we don't need to load category modal.
         */
        global $wp;
        if ( ( dokan_is_seller_dashboard() && isset( $wp->query_vars['new-auction-product'] ) ) || ( isset( $wp->query_vars['auction'] ) ) ) {
            dokan_get_template_part( 'products/dokan-category-ui' );
        }
    }

    /**
     * WooCommerce Simple Auction `bid now` button override
     *
     * @since 3.7.30
     *
     * @param string $text
     * @param object $auction_object
     *
     * @return string
     */
    public function bid_now_button( string $text, object $auction_object ): string {
        if(
            $auction_object instanceof WC_Product_Auction
            && ! $auction_object->is_finished() && $auction_object->is_started()
            && dokan_is_product_author( $auction_object->get_id() )
        ) {
            return __( 'Read More', 'dokan' );
        }
        return $text;
    }

    /**
     * Overrides WooCommerce Simple Auction product single page template bidding template
     *
     * @since 3.7.30
     *
     * @param string $located
     * @param string $template_name
     *
     * @return string
     */
    function override_single_page_bidding_form( string $located, string $template_name ): string {
        if(
            'single-product/auction-bid-form.php' === $template_name
            && dokan_is_product_author( get_the_ID() )
        ) {
            return $this->plugin_path() . '/templates/auction/auction-bid-restriction.php';
        }
        return $located;
    }
}
