<?php

namespace WeDevs\DokanPro\Modules\SellerVacation;

use WeDevs\DokanPro\Modules\SellerVacation\SettingsApi\Store;

class Module {

    /**
     * Constructor for the Dokan_Seller_Vacation class
     *
     * Sets up all the appropriate hooks and actions
     * within our plugin.
     *
     * @since 2.9.10
     *
     * @return void
     */
    public function __construct() {
        $this->define_constants();
        $this->includes();
        $this->instances();

        add_action( 'dokan_store_profile_frame_after', array( $this, 'show_vacation_message' ), 5, 2 );
        add_action( 'woocommerce_before_single_product_summary', array( $this, 'show_vacation_message_on_product_page' ), 5 );
        add_action( 'template_redirect', array( $this, 'remove_product_from_cart_for_closed_store' ) );
        add_filter( 'woocommerce_is_purchasable', [ $this, 'hide_add_to_cart_button' ], 10, 2 );
        add_filter( 'dokan_request_a_quote_apply_rules', [ $this, 'apply_quote_rules' ], 10, 2 );
        add_filter( 'dokan_rest_admin_dashboard_vendor_metrics_data', [ $this, 'load_vendors_on_vacation_count' ] );
    }

    /**
     * Module constants
     *
     * @since 2.9.10
     *
     * @return void
     */
    private function define_constants() {
        define( 'DOKAN_SELLER_VACATION_FILE', __FILE__ );
        define( 'DOKAN_SELLER_VACATION_PATH', dirname( DOKAN_SELLER_VACATION_FILE ) );
        define( 'DOKAN_SELLER_VACATION_INCLUDES', DOKAN_SELLER_VACATION_PATH . '/includes' );
        define( 'DOKAN_SELLER_VACATION_URL', plugins_url( '', DOKAN_SELLER_VACATION_FILE ) );
        define( 'DOKAN_SELLER_VACATION_ASSETS', DOKAN_SELLER_VACATION_URL . '/assets' );
        define( 'DOKAN_SELLER_VACATION_VIEWS', DOKAN_SELLER_VACATION_PATH . '/views' );
    }

    /**
     * Include module related files
     *
     * @since 2.9.10
     *
     * @return void
     */
    private function includes() {
        require_once DOKAN_SELLER_VACATION_INCLUDES . '/functions.php';
        require_once DOKAN_SELLER_VACATION_INCLUDES . '/class-dokan-seller-vacation-store-settings.php';
        require_once DOKAN_SELLER_VACATION_INCLUDES . '/class-dokan-seller-vacation-ajax.php';
        require_once DOKAN_SELLER_VACATION_INCLUDES . '/SettingsApi/Store.php';
    }

    /**
     * Create module related class instances
     *
     * @since 2.9.10
     *
     * @return void
     */
    private function instances() {
        new \Dokan_Seller_Vacation_Store_Settings();
        new \Dokan_Seller_Vacation_Ajax();
        new Store();
    }

    /**
     * Show vacation message on product single page.
     *
     * @since 3.9.3
     *
     * @return void
     */
    public function show_vacation_message_on_product_page() {
        global $product;

        $vendor = dokan_get_vendor_by_product( $product );
        $this->show_vacation_message( $vendor->data, $vendor->get_shop_info() );
    }

    /**
     * Show Vacation message in store page
     * @param  \WP_User $store_user
     * @param  array $store_info
     * @return void
     */
    public function show_vacation_message( $store_user, $store_info, $raw_output = false ) {
        $vendor = dokan()->vendor->get( $store_user->ID );

        if ( dokan_seller_vacation_is_seller_on_vacation( $vendor->get_id() ) ) {
            $shop_info = $vendor->get_shop_info();

            $message = '';

            if ( 'datewise' !== $shop_info['settings_closing_style'] ) {
                $message = $store_info['setting_vacation_message'];
            } else {
                $schedules    = dokan_seller_vacation_get_vacation_schedules( $shop_info );
                $current_time = date( 'Y-m-d', current_time( 'timestamp' ) ); // phpcs:ignore

                foreach ( $schedules as $schedule ) {
                    $from = $schedule['from'];
                    $to   = $schedule['to'];

                    if ( $from <= $current_time && $current_time <= $to ) {
                        $message = $schedule['message'];
                        break;
                    }
                }
            }
            $message = apply_filters(
                'dokan_get_vendor_vacation_message',
                $message,
                $store_user->ID
            );
            if ( $raw_output ) {
                echo esc_html( $message );
            } else {
                dokan_seller_vacation_get_template(
                    'vacation-message', array(
						'message' => $message,
                    )
                );
            }
        }
    }

    /**
     * Remove product from cart for closed store
     * @param  null
     * @return void
     */
    public function remove_product_from_cart_for_closed_store() {
        if ( is_cart() || is_checkout() ) {
            foreach ( WC()->cart->cart_contents as $item ) {
                $product_id = ( isset( $item['variation_id'] ) && $item['variation_id'] !== 0 ) ? $item['variation_id'] : $item['product_id'];

                if ( empty( $product_id ) ) {
                    continue;
                }

                $vendor_id = get_post_field( 'post_author', $product_id );

                if ( empty( $vendor_id ) ) {
                    continue;
                }

                if ( dokan_seller_vacation_is_seller_on_vacation( $vendor_id ) ) {
                    $product_cart_id = isset( $item['key'] ) ? $item['key'] : WC()->cart->generate_cart_id( $product_id );
                    WC()->cart->remove_cart_item( $product_cart_id );
                }
            }
        }
    }

    /**
     * Hide Add to Cart Button.
     *
     * @since 3.9.0
     *
     * @param bool        $purchasable Is Purchasable
     * @param \WC_Product $product     Product object
     *
     * @return void
     */
    public function hide_add_to_cart_button( $purchasable, $product ) {
        $vendor_id = dokan_get_vendor_by_product( $product, true );

        // If seller vacation enabled by the vendor.
        if ( dokan_seller_vacation_is_seller_on_vacation( $vendor_id ) ) {
            $purchasable = false;
        }

        return $purchasable;
    }

    /**
     * Apply Quote Rules.
     *
     * @since 3.9.0
     *
     * @param bool        $applicable Is Applicable
     * @param \WC_Product $product    Product Object
     *
     * @return bool
     */
    public function apply_quote_rules( $applicable, $product ) {
        $vendor_id = dokan_get_vendor_by_product( $product, true );

        // If seller vacation enabled by the vendor.
        if ( dokan_seller_vacation_is_seller_on_vacation( $vendor_id ) ) {
            $applicable = false;
        }

        return $applicable;
    }

    /**
     * Load vendors on vacation count in the admin dashboard vendor metrics data.
     *
     * @since 4.1.0
     *
     * @param array $data The existing vendor metrics data.
     *
     * @return array The modified vendor metrics data with vendors on vacation count.
     */
    public function load_vendors_on_vacation_count( array $data ): array {
        $data['vendors_on_vacation'] = [
            'icon'     => 'TreePalm',
            'count'    => dokan_seller_vacation_get_vendors_on_vacation_count(),
            'title'    => esc_html__( 'Vendor on Vacation', 'dokan' ),
            'tooltip'  => esc_html__( 'Vendors who are currently on vacation', 'dokan' ),
            'position' => 4,
        ];

        return $data;
    }
}
