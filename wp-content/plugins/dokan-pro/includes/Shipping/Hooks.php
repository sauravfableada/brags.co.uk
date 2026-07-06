<?php

namespace WeDevs\DokanPro\Shipping;

use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\StoreApi;
use Automattic\WooCommerce\Utilities\NumberUtil;
use WC_Countries;
use WC_Shipping_Free_Shipping;
use WeDevs\DokanPro\Shipping\Blocks\CheckoutBlockSupport;
use WeDevs\DokanPro\Shipping\Blocks\ExtendEndpoint;
use WeDevs\DokanPro\Shipping\Methods\ProductShipping;
use WeDevs\DokanPro\Shipping\Methods\VendorShipping;

/**
 * Dokan Shipping Class
 *
 * @author weDevs
 */
class Hooks {

    /**
     * Load automatically when class inistantiate
     *
     * @since 2.4
     *
     * @uses actions|filter hooks
     */
    public function __construct() {
        add_filter( 'woocommerce_shipping_methods', [ $this, 'register_shipping' ] );
        add_filter( 'dokan_settings_selling_option_vendor_capability', [ $this, 'add_settings_shipping_tab' ], 10 );
        add_action( 'wp_footer', [ $this, 'set_shipping_asset_in_cart_checkout_page' ] );
        add_action( 'woocommerce_product_tabs', [ $this, 'register_product_tab' ] );
        add_action( 'woocommerce_after_checkout_validation', [ $this, 'validate_country' ] );
        add_action( 'template_redirect', [ $this, 'handle_shipping' ] );
        add_filter( 'woocommerce_package_rates', [ $this, 'calculate_shipping_tax' ], 10, 2 );
        add_filter( 'woocommerce_shipping_packages', [ $this, 'filter_packages' ] );
        add_action( 'woocommerce_delete_shipping_zone', [ $this, 'delete_shipping_zone_data' ], 35, 1 );
        add_action( 'woocommerce_after_shipping_zone_object_save', [ $this, 'vendor_zone_data_sync' ] );
        add_filter( 'woocommerce_shipping_free_shipping_is_available', [ $this, 'handle_free_shipping_validity' ], 10, 3 );
        add_filter( 'dokan_shipping_package_name', [ $this, 'display_free_shipping_remaining_amount' ], 10, 3 );
        add_filter( 'dokan_shipping_package_name', [ $this, 'display_free_shipping_remaining_amount_for_vendor_shipping' ], 10, 3 );
        add_action(
            'woocommerce_blocks_checkout_block_registration',
            function ( $integration_registry ) {
                $integration_registry->register( new CheckoutBlockSupport() );
            }
        );
    }

    /**
     * Disable product shipping tab
     *
     * @since 3.3.0
     *
     * @param array $settings_fields
     *
     * @return array
     */
    public function add_settings_shipping_tab( $settings_fields ) {
        $settings_fields['disable_shipping_tab'] = [
            'name'               => 'disable_shipping_tab',
            'label'              => __( 'Disable Shipping Tab', 'dokan' ),
            'desc'               => __( 'Disable shipping tab on single product page', 'dokan' ),
            'type'               => 'switcher',
            'default'            => 'off',
            'refresh_after_save' => true,
            'is_lite'            => false,
        ];

        return $settings_fields;
    }

    /**
     * Register shipping method
     *
     * @since 2.0
     *
     * @param array $methods
     *
     * @return array
     */
    public function register_shipping( $methods ) {
        if ( 'sell_digital' === dokan_get_option( 'disable_shipping_tab', 'dokan_selling', 'off' ) ) {
            return $methods;
        }

        $methods['dokan_product_shipping'] = ProductShipping::class;
        $methods['dokan_vendor_shipping']  = VendorShipping::class;

        return $methods;
    }

    /**
     * Set cart page title fix script in cart checkout page.
     *
     * @since 3.10.1
     *
     * @return void
     */
    public function set_shipping_asset_in_cart_checkout_page() {
        if ( ! ( is_cart() || is_checkout() ) ) {
            return;
        }
        ob_start();
        ?>
        <script>
            (function($){
                $(document).ready(function(){
                    function format_html() {
                        // Fix Free shipping remaining amount html display issue on mobile screen.
                        let shippingTdElements = $( 'tr.woocommerce-shipping-totals.shipping td' );
                        let discountTdElements = $( 'tr.cart-discount td' );
                        let tdElements = $.merge(  shippingTdElements, discountTdElements );

                        tdElements.each( function ( index ) {
                            let title = $(this).data('title');

                            if ( typeof title === 'undefined' ) {
                                return;
                            }

                            let html = $.parseHTML(title);
                            let text = $(html).text();

                            $(this).attr( 'data-title', text );
                        } );
                    }
                    // run initially.
                    format_html();
                    // run on added to cart and removed from cart.
                    $(document.body).on('added_to_cart removed_from_cart updated_cart_totals', format_html );
                });

            })(jQuery);
        </script>
        <?php
        echo ob_get_clean();
    }

    /**
     * Validate the shipping area
     *
     * @since 2.0
     *
     * @param  array $posted
     *
     * @return void
     */
    public function validate_country( $posted ) {
        $shipping_method = WC()->session->get( 'chosen_shipping_methods' );

        // per product shipping was not chosen
        if ( ! is_array( $shipping_method ) || ! in_array( 'dokan_product_shipping', $shipping_method, true ) ) {
            return;
        }

        if ( isset( $posted['ship_to_different_address'] ) && $posted['ship_to_different_address'] === '1' ) {
            $shipping_country = $posted['shipping_country'];
        } else {
            $shipping_country = $posted['billing_country'];
        }

        // echo $shipping_country;
        $packages = WC()->shipping->get_packages();

        reset( $packages );

        if ( ! isset( $packages[0]['contents'] ) ) {
            return;
        }

        $products = array();

        foreach ( $packages as $package ) {
            array_push( $products, $package['contents'] );
        }

        $destination_country = isset( $packages[0]['destination']['country'] ) ? $packages[0]['destination']['country'] : '';
        $destination_state   = isset( $packages[0]['destination']['state'] ) ? $packages[0]['destination']['state'] : '';

        // hold all the errors
        $errors = array();

        foreach ( $products as $key => $product ) {
            $dokan_regular_shipping = new ProductShipping();

            foreach ( $product as $product_obj ) {
                $seller_id = get_post_field( 'post_author', $product_obj['product_id'] );

                if ( ! $dokan_regular_shipping->is_method_enabled() ) {
                    continue;
                }

                if ( ! ProductShipping::is_shipping_enabled_for_seller( $seller_id ) ) {
                    continue;
                }

                if ( ProductShipping::is_product_disable_shipping( $product_obj['product_id'] ) ) {
                    continue;
                }

                $dps_country_rates = get_user_meta( $seller_id, '_dps_country_rates', true );
                $dps_state_rates   = get_user_meta( $seller_id, '_dps_state_rates', true );

                $has_found   = false;
                $dps_country = ( isset( $dps_country_rates ) ) ? $dps_country_rates : array();
                $dps_state   = ( isset( $dps_state_rates[ $destination_country ] ) ) ? $dps_state_rates[ $destination_country ] : array();

                if ( array_key_exists( $destination_country, $dps_country ) ) {
                    if ( $dps_state ) {
                        if ( array_key_exists( $destination_state, $dps_state ) ) {
                            $has_found = true;
                        } elseif ( array_key_exists( 'everywhere', $dps_state ) ) {
                            $has_found = true;
                        }
                    } else {
                        $has_found = true;
                    }
                } elseif ( array_key_exists( 'everywhere', $dps_country ) ) {
                        $has_found = true;
                }

                if ( ! $has_found ) {
                    $errors[] = sprintf( '<a href="%s">%s</a>', get_permalink( $product_obj['product_id'] ), get_the_title( $product_obj['product_id'] ) );
                }
            }
        }

        if ( $errors ) {
            if ( count( $errors ) === 1 ) {
                // translators: Error message.
                $message = sprintf( __( 'This product does not ship to your chosen location: %s', 'dokan' ), implode( ', ', $errors ) );
            } else {
                // translators: Error message.
                $message = sprintf( __( 'These products do not ship to your chosen location.: %s', 'dokan' ), implode( ', ', $errors ) );
            }

            wc_add_notice( $message, 'error' );
        }
    }

    /**
     *  Handle Shipping post submit
     *
     *  @since  2.0
     *
     *  @return void
     */
    public function handle_shipping() {
        if ( ! is_user_logged_in() ) {
            return;
        }

        if ( ! dokan_is_user_seller( get_current_user_id() ) ) {
            return;
        }

        if ( ! isset( $_POST['dokan_shipping_form_field_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dokan_shipping_form_field_nonce'] ) ), 'dokan_shipping_form_field' ) ) {
            return;
        }

        if ( ! current_user_can( 'dokan_view_store_shipping_menu' ) ) {
            wp_die( __( 'You have no access to save this shipping options', 'dokan' ) );
        }

        $user_id = dokan_get_current_user_id();
        $s_rates = array();
        $rates   = array();

        // Additional extra code

        if ( isset( $_POST['dps_enable_shipping'] ) ) {
            update_user_meta( $user_id, '_dps_shipping_enable', sanitize_text_field( wp_unslash( $_POST['dps_enable_shipping'] ) ) );
        }

        if ( isset( $_POST['dokan_shipping_type'] ) ) {
            update_user_meta( $user_id, '_dokan_shipping_type', sanitize_text_field( wp_unslash( $_POST['dokan_shipping_type'] ) ) );
        }

        if ( isset( $_POST['dps_shipping_type_price'] ) ) {
            update_user_meta( $user_id, '_dps_shipping_type_price', sanitize_text_field( wp_unslash( $_POST['dps_shipping_type_price'] ) ) );
        }

        if ( isset( $_POST['dps_additional_product'] ) ) {
            update_user_meta( $user_id, '_dps_additional_product', sanitize_text_field( wp_unslash( $_POST['dps_additional_product'] ) ) );
        }

        if ( isset( $_POST['dps_additional_qty'] ) ) {
            update_user_meta( $user_id, '_dps_additional_qty', sanitize_text_field( wp_unslash( $_POST['dps_additional_qty'] ) ) );
        }

        if ( isset( $_POST['dps_pt'] ) ) {
            update_user_meta( $user_id, '_dps_pt', sanitize_text_field( wp_unslash( $_POST['dps_pt'] ) ) );
        }

        if ( isset( $_POST['dps_ship_policy'] ) ) {
            update_user_meta( $user_id, '_dps_ship_policy', wp_kses_post( wp_unslash( $_POST['dps_ship_policy'] ) ) );
        }

        if ( isset( $_POST['dps_refund_policy'] ) ) {
            update_user_meta( $user_id, '_dps_refund_policy', wp_kses_post( wp_unslash( $_POST['dps_refund_policy'] ) ) );
        }

        if ( isset( $_POST['dps_form_location'] ) ) {
            update_user_meta( $user_id, '_dps_form_location', sanitize_text_field( wp_unslash( $_POST['dps_form_location'] ) ) );
        }

        if ( isset( $_POST['dps_country_to'] ) ) {
            $dps_country_to = wc_clean( wp_unslash( $_POST['dps_country_to'] ) );
            $dps_country_to_price = isset( $_POST['dps_country_to_price'] ) ? wc_clean( wp_unslash( $_POST['dps_country_to_price'] ) ) : 0;
            foreach ( $dps_country_to as $key => $value ) {
                $country = $value;
                $c_price = wc_format_decimal( $dps_country_to_price[ $key ] );

                if ( ! $c_price && empty( $c_price ) ) {
                    $c_price = 0;
                }

                if ( ! empty( $value ) ) {
                    $rates[ $country ] = $c_price;
                }
            }
        }

        update_user_meta( $user_id, '_dps_country_rates', $rates );

        if ( isset( $_POST['dps_state_to'] ) ) {
            $dps_state_to = wc_clean( wp_unslash( $_POST['dps_state_to'] ) );
            foreach ( $dps_state_to as $country_code => $states ) {
                foreach ( $states as $key_val => $name ) {
                    $country_c = $country_code;
                    $state_code = $name;
                    $s_price = isset( $_POST['dps_state_to_price'][ $country_c ][ $key_val ] ) ? sanitize_text_field( wp_unslash( $_POST['dps_state_to_price'][ $country_c ][ $key_val ] ) ) : 0;
                    $s_price = wc_format_decimal( $s_price );

                    if ( ! $s_price || empty( $s_price ) ) {
                        $s_price = 0;
                    }

                    if ( ! empty( $name ) ) {
                        $s_rates[ $country_c ][ $state_code ] = $s_price;
                    }
                }
            }
        }

        update_user_meta( $user_id, '_dps_state_rates', $s_rates );

        do_action( 'dokan_after_shipping_options_updated', $rates, $s_rates );

        $shipping_url = dokan_get_navigation_url( 'settings/regular-shipping' );
        wp_safe_redirect( add_query_arg( array( 'message' => 'shipping_saved' ), $shipping_url ) );
        exit();
    }

    /**
     * Adds a seller tab in product single page
     *
     * @since 2.0
     *
     * @param array $tabs
     *
     * @return array
     */
    public function register_product_tab( $tabs ) {
        if ( 'on' === dokan_get_option( 'disable_shipping_tab', 'dokan_selling', 'off' ) ) {
            return $tabs;
        }

        global $post;

        if ( get_post_meta( $post->ID, '_disable_shipping', true ) === 'yes' ) {
            return $tabs;
        }

        if ( get_post_meta( $post->ID, '_downloadable', true ) === 'yes' ) {
            return $tabs;
        }

        if ( 'yes' !== get_option( 'woocommerce_calc_shipping' ) ) {
            return $tabs;
        }

        if ( 'sell_digital' === dokan_pro()->digital_product->get_selling_product_type() ) {
            return $tabs;
        }

        $tabs['shipping'] = array(
            'title'    => __( 'Shipping', 'dokan' ),
            'priority' => 12,
            'callback' => array( $this, 'shipping_tab' ),
        );

        return $tabs;
    }

    /**
     * Callback for Register_prouduct_tab function
     *
     * @since 2.0
     *
     * @return void
     */
    public function shipping_tab() {
        global $wpdb, $post;

        $vendor_id = $post->post_author;

        $shipping_zone = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT locations.zone_id, locations.seller_id, locations.location_type as vendor_location_type, locations.location_code as vendor_location_code, wc_zones.location_code, wc_zones.location_type FROM {$wpdb->prefix}dokan_shipping_zone_locations as locations INNER JOIN {$wpdb->prefix}woocommerce_shipping_zone_locations as wc_zones ON locations.zone_id = wc_zones.zone_id INNER JOIN {$wpdb->prefix}dokan_shipping_zone_methods as dokan_methods ON dokan_methods.zone_id = locations.zone_id AND dokan_methods.seller_id = locations.seller_id WHERE locations.seller_id =%d AND locations.location_type != 'postcode' ORDER BY wc_zones.zone_id ASC", $vendor_id
            ), ARRAY_A
        );

        $_overwrite_shipping     = get_post_meta( $post->ID, '_overwrite_shipping', true );
        $dps_processing          = get_user_meta( $vendor_id, '_dps_pt', true );
        $from                    = get_user_meta( $vendor_id, '_dps_form_location', true );
        $dps_country_rates       = get_user_meta( $vendor_id, '_dps_country_rates', true );
        $shipping_policy         = get_user_meta( $vendor_id, '_dps_ship_policy', true );
        $refund_policy           = get_user_meta( $vendor_id, '_dps_refund_policy', true );
        $product_processing_time = get_post_meta( $post->ID, '_dps_processing_time', true );
        $processing_time         = $dps_processing;

        if ( 'yes' === $_overwrite_shipping ) {
            $processing_time = ( $product_processing_time ) ? $product_processing_time : $dps_processing;
        }

        $country_obj = new WC_Countries();
        $countries   = $country_obj->countries;
        $states      = $country_obj->states;
        $continents  = $country_obj->get_continents();

        $shipping_countries  = '';
        $shipping_states     = '';
        $shipping_continents = '';
        $location_code       = '';
        $check_countries     = array();
        $check_states        = array();
        $check_continents    = array();

        if ( $shipping_zone ) {
            foreach ( $shipping_zone as $zone ) {
                $location_code = $zone['vendor_location_code'];

                if ( $zone['vendor_location_type'] === 'state' ) {
                    $location_codes = explode( ':', $location_code );
                    $country_code   = isset( $location_codes[0] ) ? $location_codes[0] : '';
                    $state_code     = isset( $location_codes[1] ) ? $location_codes[1] : '';

                    if ( isset( $states[ $country_code ][ $state_code ] ) && isset( $countries[ $country_code ] ) && ! in_array( $states[ $country_code ][ $state_code ], $check_states, true ) ) {
                        $get_state_name = $states[ $country_code ][ $state_code ];

                        $check_states[ $get_state_name ] = $get_state_name;
                        $shipping_states                .= $get_state_name . ' (' . $countries[ $country_code ] . '), ';
                    }
                }

                if ( $zone['vendor_location_type'] === 'country' && $countries[ $location_code ] && ! in_array( $countries[ $location_code ], $check_countries, true ) ) {
                    $location_code                     = $countries[ $location_code ];
                    $check_countries[ $location_code ] = $location_code;
                    $shipping_countries               .= $location_code . ', ';
                }

                if ( $zone['vendor_location_type'] === 'continent' && $continents[ $location_code ] && ! in_array( $continents[ $location_code ]['name'], $check_continents, true ) ) {
                    $location_code                      = $continents[ $location_code ]['name'];
                    $check_continents[ $location_code ] = $location_code;
                    $shipping_continents               .= $location_code . ', ';
                }
            }
        }
        ?>

        <?php if ( $shipping_continents ) { ?>
            <p>
                <?php esc_html_e( 'Shipping Continents', 'dokan' ); ?>:
                <strong><?php echo rtrim( $shipping_continents, ', ' ); ?></strong>
            </p>
            <hr>
        <?php } ?>

        <?php if ( $shipping_countries ) { ?>
            <p>
                <?php esc_html_e( 'Shipping Countries', 'dokan' ); ?>:
                <strong><?php echo rtrim( $shipping_countries, ', ' ); ?></strong>
            </p>
            <hr>
        <?php } ?>

        <?php if ( $shipping_states ) { ?>
            <p>
                <?php esc_html_e( 'Shipping States', 'dokan' ); ?>:
                <strong><?php echo rtrim( $shipping_states, ', ' ); ?></strong>
            </p>
            <hr>
        <?php } ?>

        <?php if ( $processing_time ) { ?>
            <p>
                <strong>
                    <?php esc_html_e( 'Ready to ship in', 'dokan' ); ?> <?php echo dokan_get_processing_time_value( $processing_time ); ?>

                    <?php
                    if ( $from ) {
                        echo __( 'from', 'dokan' ) . ' ' . $countries[ $from ];
                    }
                    ?>
                </strong>
            </p>
            <hr>
        <?php } ?>

        <?php if ( $shipping_policy ) { ?>
            <p>&nbsp;</p>
            <strong><?php esc_html_e( 'Shipping Policy', 'dokan' ); ?></strong>
            <hr>
            <?php echo wpautop( $shipping_policy ); ?>
        <?php } ?>

        <?php if ( $refund_policy ) { ?>
            <hr>
            <p>&nbsp;</p>
            <strong><?php esc_html_e( 'Refund Policy', 'dokan' ); ?></strong>
            <hr>
            <?php echo wpautop( $refund_policy ); ?>
        <?php } ?>
        <?php
    }

    /**
     * WooCommerce calculate taxes cart wise (cart as a whole), not vendor wise.
     * So if there is any tax for non-taxable product, lets remove that tax
     *
     * @since 3.0.3
     *
     * @see https://github.com/weDevsOfficial/dokan/issues/820
     * @see https://github.com/woocommerce/woocommerce/issues/20600
     *
     * @param \WC_Shipping_Rate $package_rates Shipping rates
     * @param array $package Shipping package
     *
     * @return \WC_Shipping_Rate
     */
    public function calculate_shipping_tax( $package_rates, $package ) {
        if ( ! isset( $package['contents'] ) ) {
            return $package_rates;
        }

        foreach ( $package['contents'] as $pack ) {
            if ( ! isset( $pack['data'] ) || ! is_callable( [ $pack['data'], 'get_tax_status' ] ) ) {
                return $package_rates;
            }

            if ( 'none' !== $pack['data']->get_tax_status() ) {
                continue;
            }

            // so it's a non taxable shipping, lets remove the taxes
            foreach ( $package_rates as $shipping_rate ) {
                $rfc = new \ReflectionClass( $shipping_rate );

                if ( ! $rfc->hasProperty( 'data' ) ) {
                    return $package_rates;
                }

                $data = $rfc->getProperty( 'data' );
                $data->setAccessible( true );
                $data->setValue(
                    $shipping_rate,
                    array_merge(
                        $data->getValue( $shipping_rate ),
                        [
                            'taxes' => [],
                        ]
                    )
                );
            }
        }

        return $package_rates;
    }

    /**
     * Filter pakcages, remove shipping data from cart if no shipping is required.
     *
     * Vendor A sales digital product with no shipping, but Vendor B sales physical product with shipping.
     * When Vendor A’s product is added in the cart, there is no shipping as expected.
     * But on adding Vendor B’s product, shipping is shown for both products.
     *
     * We'll remove package only if all the products of a vendor are non-shippable
     *
     * @since 3.0.3
     * @since DOKAN_PRO_VERSION Remove packages those are not have contents
     *
     * @param array $packages
     *
     * @return array
     */
    public function filter_packages( $packages ) {
        $package_to_keep   = [];
        $package_to_remove = [];

        foreach ( $packages as $key => $package ) {
            if ( empty( $package['contents'] ) ) {
                $package_to_remove[] = $key;
                continue;
            }

            $p_seller_id = isset( $package['seller_id'] ) ? (int) $package['seller_id'] : 0;

            foreach ( $package['contents'] as $content ) {
                $product = ! empty( $content['product_id'] ) ? wc_get_product( $content['product_id'] ) : '';
                if ( $product && $product->needs_shipping() ) {
                    $seller_id = dokan_get_vendor_by_product( $product->get_id(), true );

                    if ( $p_seller_id !== $seller_id ) {
                        $packages[ $key ]['seller_id'] = $seller_id;
                    }

                    $package_to_keep[] = $key;

                    // check if we already added same vendor under removed package
                    $item_exists_on_remove_package = array_search( $key, $package_to_remove, true );
                    if ( false !== $item_exists_on_remove_package ) {
                        unset( $package_to_remove[ $item_exists_on_remove_package ] );
                    }
                }

                if ( $product && ! $product->needs_shipping() && ! in_array( $key, $package_to_keep, true ) ) {
                    $package_to_remove[] = $key;
                }
            }
        }

        foreach ( $package_to_remove as $package ) {
            unset( $packages[ $package ] );
        }

        /**
         * @since 3.5.0
         */
        return apply_filters( 'dokan_shipping_packages', $packages, $package_to_keep );
    }

    /**
     * Delete shipping data when zone deleted from admin
     *
     * @since 3.2.2
     *
     * @param int $zone_id Shipping zone id
     *
     * @return void
     */
    public function delete_shipping_zone_data( $zone_id ) {
        global $wpdb;

        // Delete dokan shipping data when deleted zone from admin area
        $wpdb->delete( $wpdb->prefix . 'dokan_shipping_zone_locations', array( 'zone_id' => $zone_id ) );
        $wpdb->delete( $wpdb->prefix . 'dokan_shipping_zone_methods', array( 'zone_id' => $zone_id ) );

        do_action( 'dokan_delete_shipping_zone_data', $zone_id );
    }

    /**
     * Vendors shipping data syncronize when zone update by admin
     *
     * @since 3.2.2
     *
     * @param \WC_Shipping_Zone $zone Shipping zone.
     *
     * @return void
     */
    public function vendor_zone_data_sync( $zone ) {
        if ( empty( $zone->get_id() ) ) {
            return;
        }

        $zone_data      = $zone->get_data();
        $zone_locations = $zone_data['zone_locations'];

        if ( empty( $zone_locations ) ) {
            return;
        }

        $all_vendors = dokan()->vendor->get_vendors( [ 'number' => -1, 'fields' => 'ID' ] ); //phpcs:ignore
        foreach ( $all_vendors as $vendor_id ) {
            $args = [
                'seller_id'         => $vendor_id,
                'zone'              => $zone,
                'zone_locations'    => $zone_locations,
            ];
            dokan_pro()->bg_process->sync_vendor_zone_data->push_to_queue( $args );
        }

        dokan_pro()->bg_process->sync_vendor_zone_data->save()->dispatch();
    }

    /**
     * Handle free shipping availability for vendors.
     *
     * @since 3.7.16
     *
     * @param bool $is_available Is available.
     * @param array $package Package to check with.
     * @param WC_Shipping_Free_Shipping $shipping_instance Shipping instance.
     *
     * @return bool
     */
    public function handle_free_shipping_validity( bool $is_available, array $package, WC_Shipping_Free_Shipping $shipping_instance ): bool {
        if ( ! $is_available ) {
            return false;
        }

        // Get dokan free shipping method instance.
        $free_shipping = new FreeShippingHandler( $shipping_instance, $package );

        // Return free shipping availability.
        return $free_shipping->is_free_shipping_available();
    }

    /**
     * Display Free shipping information.
     *
     * @since 3.7.27
     *
     * @param string $shipping_label Existing shipping label.
     * @param int $i Index.
     * @param array $package Package.
     *
     * @return string
     */
    public function display_free_shipping_remaining_amount( $shipping_label, $i, $package ): string {
		$shipping_methods        = \WC_Shipping_Zones::get_zone_matching_package( $package )->get_shipping_methods( true );
        $free_shipping_instance  = null;

        foreach ( $shipping_methods as $shipping_method ) {
            if ( 'free_shipping' === $shipping_method->id ) {
                $free_shipping_instance = $shipping_method;
                break;
            }
        }

        // Use FreeShippingHandler for availability; $package['rates'] isn't populated when WC 10.x builds the package name.
        if ( empty( $free_shipping_instance ) || ! is_a( $free_shipping_instance, WC_Shipping_Free_Shipping::class ) ) {
            return $shipping_label;
        }

        // Get dokan free shipping method instance & collect relevant values.
        $free_shipping     = new FreeShippingHandler( $free_shipping_instance, $package );
        $remains           = $free_shipping->get_remaining_amount();
        $is_available      = $free_shipping->is_free_shipping_available();
        $needs_coupon_only = $free_shipping->needs_coupon_for_free_shipping();

        if ( $is_available || $needs_coupon_only ) {
            return $shipping_label;
        }

        // translators: 1. Original Shipping title, 2. Remaining amount to avail free shipping, 3. Free shipping method display title.
        return sprintf( __( '%1$s <br /><small style="color: #b8860b;">(Only %2$s away from <strong>%3$s</strong>)</small>', 'dokan' ), $shipping_label, wc_price( $remains ), $free_shipping_instance->get_title() );
    }
    /**
     * Display Free shipping information.
     *
     * @since 3.15.0
     *
     * @param array $package Package.
     *
     * @return string
     */
    public function display_free_shipping_remaining_amount_block( $package, $store_name ): string {
		$shipping_methods        = \WC_Shipping_Zones::get_zone_matching_package( $package )->get_shipping_methods( true );
        $free_shipping_instance  = null;

        foreach ( $shipping_methods as $shipping_method ) {
            if ( 'free_shipping' === $shipping_method->id ) {
                $free_shipping_instance = $shipping_method;
                break;
            }
        }

        if ( empty( $free_shipping_instance ) || ! is_a( $free_shipping_instance, WC_Shipping_Free_Shipping::class ) ) {
            return $store_name;
        }

        // Get dokan free shipping method instance & collect relevant values.
        $free_shipping     = new FreeShippingHandler( $free_shipping_instance, $package );
        $remains           = $free_shipping->get_remaining_amount();
        $is_available      = $free_shipping->is_free_shipping_available();
        $needs_coupon_only = $free_shipping->needs_coupon_for_free_shipping();

        if ( $is_available || $needs_coupon_only ) {
            return $store_name;
        }

        // translators: 1. Original Shipping title, 2. Remaining amount to avail free shipping, 3. Free shipping method display title.
        return sprintf( __( '<strong>%1$s:</strong> (Only %2$s away from <strong>%3$s</strong>)', 'dokan' ), $store_name, wc_price( $remains ), $free_shipping_instance->get_title() );
    }

    /**
     * Display Free shipping information for vendor shipping.
     *
     * @since 3.7.27
     *
     * @param array $package Package.
     * @param string $store_name Existing shipping label.
     *
     * @return string
     */
    public function display_free_shipping_remaining_amount_for_vendor_shipping_block( $package, $store_name ): string {
        if ( empty( $package['seller_id'] ) ) {
            return $store_name;
        }

        $shipping_methods        = ShippingZone::get_shipping_methods( ShippingZone::get_zone_matching_package( $package )->get_id(), $package['seller_id'] );
        $free_shipping_instance  = null;
        $free_shipping_available = false;

        foreach ( $shipping_methods as $shipping_method ) {
            if ( 'yes' !== $shipping_method['enabled'] ) {
                continue;
            }

            if ( 'free_shipping' === $shipping_method['id'] ) {
                $free_shipping_instance = $shipping_method;
                break;
            }
        }

        if ( empty( $free_shipping_instance ) ) {
            return $store_name;
        }

        // Get dokan free shipping method instance & collect relevant values.
        $free_shipping     = new FreeShippingHandler( $free_shipping_instance, $package );
        $is_available      = $free_shipping->is_free_shipping_available();
        $remains           = $free_shipping->get_remaining_amount();
        $needs_coupon_only = $free_shipping->needs_coupon_for_free_shipping();

        // Coupon-only free shipping has no spend threshold to count down to, so skip the notice.
        if ( $is_available || $needs_coupon_only ) {
            return $store_name;
        }

        // translators: 1. Original Shipping title, 2. Remaining amount to avail free shipping, 3. Free shipping method display title.
        return sprintf( __( '<strong>%1$s</strong> (Only %2$s away from <strong>%3$s</strong>)', 'dokan' ), $store_name, wc_price( $remains ), $free_shipping_instance['title'] );
    }

    /**
     * Display Free shipping information for vendor shipping.
     *
     * @since 3.7.27
     *
     * @param string $shipping_label Existing shipping label.
     * @param int $i Index.
     * @param array $package Package.
     *
     * @return string
     */
    public function display_free_shipping_remaining_amount_for_vendor_shipping( $shipping_label, $i, $package ): string {
        if ( empty( $package['seller_id'] ) ) {
            return $shipping_label;
        }

        $shipping_methods        = ShippingZone::get_shipping_methods( ShippingZone::get_zone_matching_package( $package )->get_id(), $package['seller_id'] );
        $free_shipping_instance  = null;

        foreach ( $shipping_methods as $shipping_method ) {
            if ( 'yes' !== $shipping_method['enabled'] ) {
                continue;
            }

            if ( 'free_shipping' === $shipping_method['id'] ) {
                $free_shipping_instance = $shipping_method;
                break;
            }
        }

        // Use FreeShippingHandler for availability; $package['rates'] isn't populated when WC 10.x builds the package name.
        if ( empty( $free_shipping_instance ) ) {
            return $shipping_label;
        }

        // Get dokan free shipping method instance & collect relevant values.
        $free_shipping     = new FreeShippingHandler( $free_shipping_instance, $package );
        $is_available      = $free_shipping->is_free_shipping_available();
        $remains           = $free_shipping->get_remaining_amount();
        $needs_coupon_only = $free_shipping->needs_coupon_for_free_shipping();

        // Coupon-only free shipping has no spend threshold to count down to, so skip the notice.
        if ( $is_available || $needs_coupon_only ) {
            return $shipping_label;
        }

        // translators: 1. Original Shipping title, 2. Remaining amount to avail free shipping, 3. Free shipping method display title.
        return sprintf( __( '%1$s <br /><small style="color: #b8860b;">(Only %2$s away from <strong>%3$s</strong>)</small>', 'dokan' ), $shipping_label, wc_price( $remains ), $free_shipping_instance['title'] );
    }
}
