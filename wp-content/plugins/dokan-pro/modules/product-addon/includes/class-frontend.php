<?php

use WeDevs\Dokan\ProductCategory\Categories;

/**
* Frontend vendor product addons
*/
class Dokan_Product_Addon_Frontend {

    /**
     * Load automatically when class initiate
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_filter( 'dokan_get_dashboard_settings_nav', [ $this, 'add_settings_menu' ] );
        add_filter( 'dokan_dashboard_settings_heading_title', [ $this, 'load_settings_header' ], 11, 2 );
        add_filter( 'dokan_dashboard_settings_helper_text', [ $this, 'load_helper' ], 10, 2 );
        add_action( 'dokan_render_settings_content', [ $this, 'render_settings_content' ], 10 );
        add_action( 'pre_get_posts', [ $this, 'render_vendor_global_addons' ], 99 );
        add_action( 'template_redirect', [ $this, 'handle_addon_formdata' ], 10 );
        add_action( 'wp_ajax_dokan_pao_get_addon_options', [ $this, 'ajax_get_addon_options' ], 8 );
    }

    /**
     * Initializes the Dokan_Product_Addon_Frontend() class
     *
     * Checks for an existing Dokan_Product_Addon_Frontend() instance
     * and if it doesn't find one, creates it.
     */
    public static function init() {
        static $instance = false;

        if ( ! $instance ) {
            $instance = new Dokan_Product_Addon_Frontend();
        }

        return $instance;
    }

    /**
     * Add settings menu for global addons with React route.
     *
     * @since 1.0.0
     *
     * @param array $settings_tab Settings nav tabs.
     *
     * @return array
     */
    public function add_settings_menu( $settings_tab ) {
        $settings_tab['product-addon'] = [
            'title'       => __( 'Addons', 'dokan' ),
            'icon'        => '<i class="fas fa-puzzle-piece" aria-hidden="true"></i>',
            'url'         => dokan_get_navigation_url( 'settings/product-addon' ),
            'pos'         => 40,
            'react_route' => 'settings/product-addon',
        ];

        return $settings_tab;
    }

    /**
     * Load product addon settings header
     *
     * @since 1.0.0
     *
     * @param string $header The setting header text.
     * @param string $query_vars The query vars.
     *
     * @return string
     */
    public function load_settings_header( $header, $query_vars ) {
        if ( $query_vars === 'product-addon' ) {
            $header = __( 'Product Addons', 'dokan' );
        }

        return $header;
    }

    /**
     * Load Helper Text for addon contents
     *
     * @since 1.0.0
     *
     * @param string $helper_txt The helper text.
     * @param string $query_var The query var.
     *
     * @return string
     */
    public function load_helper( $helper_txt, $query_var ) {
        if ( $query_var === 'product-addon' ) {
            $helper_txt = __( 'Set your field type for product addons which is applicable for all product or specific product category globally. You can control this setting seperately from individual products', 'dokan' );
        }

        return $helper_txt;
    }

    /**
     * Render settings contents
     *
     * @since 1.0.0
     *
     * @param array $query_vars The query vars.
     *
     * @return void
     */
    public function render_settings_content( $query_vars ) {
        if ( isset( $query_vars['settings'] ) && 'product-addon' === $query_vars['settings'] ) {
            if ( ! empty( $_GET['add'] ) || ! empty( $_GET['edit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended

                if ( ! empty( $_GET['edit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                    $edit_id = sanitize_text_field( wp_unslash( $_GET['edit'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

                    $global_addon = get_post( absint( $edit_id ) );
                    if ( ! $global_addon ) {
                        echo '<div class="dokan-alert dokan-alert-danger">' . esc_html__( 'Error: Add-on not found', 'dokan' ) . '</div>';
                        return;
                    }

                    $reference      = $global_addon->post_title;
                    $priority       = get_post_meta( $global_addon->ID, '_priority', true );
                    $objects        = (array) wp_get_post_terms( $global_addon->ID, apply_filters( 'woocommerce_product_addons_global_post_terms', array( 'product_cat' ) ), array( 'fields' => 'ids' ) );
                    $product_addons = array_filter( (array) get_post_meta( $global_addon->ID, '_product_addons', true ) );

                    if ( (int) get_post_meta( $global_addon->ID, '_all_products', true ) === 1 ) {
                        $objects[] = 'all';
                    }
                } else {
                    $global_addons_count = wp_count_posts( 'global_product_addon' );
                    $reference           = __( 'Add-ons Group', 'dokan' ) . ' #' . ( $global_addons_count->publish + 1 );
                    $priority            = 10;
                    $objects             = array( 'all' );
                    $product_addons      = array();
                    $edit_id             = '';
                }

                if ( ! empty( $_GET['saved'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, phpcs: WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                    echo '<div class="dokan-alert dokan-alert-success"><p>' . esc_html__( 'Add-on saved successfully', 'dokan' ) . '</p></div>';
                }

                // Get all categories
                $product_categories = new Categories();

                dokan_get_template_part(
                    'product-addon/html-global-admin-add',
                    '',
                    array(
                        'is_product_addon'    => true,
                        'global_addons_count' => $global_addons_count ?? array(),
                        'global_addon'        => ! empty( $global_addon ) ? $global_addon : array(),
                        'reference'           => $reference,
                        'priority'            => $priority,
                        'objects'             => $objects,
                        'product_addons'      => $product_addons,
                        'product_categories'  => $product_categories->get(),
                        'edit_id'             => $edit_id,
                    )
                );
            } else {
                if ( ! empty( $_GET['deleted'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, phpcs: WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                    echo '<div class="dokan-alert dokan-alert-success"><p>' . esc_html__( 'Add-on deleted successfully', 'dokan' ) . '</p></div>';
                }

                dokan_get_template_part(
                    'product-addon/html-global-admin',
                    '',
                    array(
                        'is_product_addon' => true,
                    )
                );
            }
        }
    }

    /**
     * Render vendor global addons using query filter
     *
     * @since 1.0.0
     *
     * @param WP_Query $query The WP_Query object.
     *
     * @return void
     */
    public function render_vendor_global_addons( $query ) {
        global $wp, $post, $product;

        if ( isset( $wp->query_vars['settings'] ) && 'product-addon' === $wp->query_vars['settings'] ) {
            if ( ! empty( $query->query['post_type'] ) && $query->query['post_type'] === 'global_product_addon' && ! is_admin() ) {
                // set post author for global addons
                $query->set( 'author', get_current_user_id() );
                return;
            }
        }

        if ( ! empty( $query->query['post_type'] ) && $query->query['post_type'] === 'global_product_addon' && ! is_admin() ) {
            // set post author for global addons
            if ( isset( $_REQUEST['add-to-cart'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $product_id = absint( wp_unslash( $_REQUEST['add-to-cart'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $post_author = get_post_field( 'post_author', $product_id );
                $query->set( 'author', $post_author );
                return;
            }

            if ( ! empty( $post->post_author ) ) {
                $query->set( 'author', $post->post_author );
            }

            return;
        }
    }

    /**
     * Handle redirect issue with handling form data request
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function handle_addon_formdata() {
        global $wp;

        if ( isset( $wp->query_vars['settings'] ) && 'product-addon' === $wp->query_vars['settings'] ) {
            $php_settings_url = trailingslashit( dokan_get_navigation_url( 'settings' ) ) . 'product-addon/';

            if ( ! empty( $_GET['delete'] ) && ! empty( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( wp_unslash( $_REQUEST['_wpnonce'] ), 'delete_addon' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                wp_delete_post( absint( wp_unslash( $_GET['delete'] ) ), true );
                wp_safe_redirect( add_query_arg( 'deleted', 1, $php_settings_url ) );
                exit();
            }

            if ( isset( $_POST['save_addon'] ) && wp_unslash( $_POST['save_addon'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                if ( ! empty( $_POST['dokan_pa_save_addons_nonce'] ) && wp_verify_nonce( wp_unslash( $_POST['dokan_pa_save_addons_nonce'] ), 'dokan_pa_save_addons' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                    $edit_id = $this->save_global_addons();
                    wp_safe_redirect(
                        add_query_arg(
                            [
								'saved' => 1,
								'edit' => $edit_id,
							], $php_settings_url
                        )
                    );
                    exit();
                }
            }
        }
    }

    /**
     * Save global addons
     *
     * @return int Edit id.
     */
    public function save_global_addons() {
        $edit_id        = ! empty( $_POST['edit_id'] ) ? absint( $_POST['edit_id'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $reference      = ! empty( $_POST['addon-reference'] ) ? wc_clean( wp_unslash( $_POST['addon-reference'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $priority       = isset( $_POST['addon-priority'] ) ? absint( $_POST['addon-priority'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $objects        = ! empty( $_POST['addon-objects'] ) ? array_map( 'absint', wp_unslash( $_POST['addon-objects'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $product_addons = dokan_pa_get_posted_product_addons( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

        if ( ! $reference ) {
            $global_addons_count = wp_count_posts( 'global_product_addon' );
            $reference           = __( 'Add-ons Group', 'dokan' ) . ' #' . ( $global_addons_count->publish + 1 );
        }

        if ( ! $priority && 0 !== $priority ) {
            $priority = 10;
        }

        if ( $edit_id ) {
            $edit_post               = array();
            $edit_post['ID']         = $edit_id;
            $edit_post['post_title'] = $reference;

            wp_update_post( $edit_post );
            wp_set_post_terms( $edit_id, $objects, 'product_cat', false );
            do_action( 'woocommerce_product_addons_global_edit_addons', $edit_post, $objects );
            do_action( 'dokan_pa_global_edit_addons', $edit_post, $objects );
        } else {
            $edit_id = wp_insert_post(
                apply_filters(
                    'dokan_pa_global_insert_post_args', array(
						'post_title'    => $reference,
						'post_status'   => 'publish',
						'post_type'     => 'global_product_addon',
						'tax_input'     => array(
							'product_cat' => $objects,
						),
                    ), $reference, $objects
                )
            );

            /*
                We are checking if the vendor addon is created by staff,
                if true, we are replacing the post_author with the staff's respective vendor,
                because while checkout woocommerce handles the addons by addon author matched with product author
            */
            if ( current_user_can( 'vendor_staff' ) ) {
                $vendor_id                = get_user_meta( get_current_user_id(), '_vendor_id', true );
                $edit_post['ID']          = $edit_id;
                $edit_post['post_author'] = $vendor_id;

                wp_update_post( $edit_post );

                update_post_meta( $edit_id, '_dokan_vendor_staff_addon_author', get_current_user_id() );
            }
        }

        if ( in_array( 0, $objects, true ) ) {
            update_post_meta( $edit_id, '_all_products', 1 );
        } else {
            update_post_meta( $edit_id, '_all_products', 0 );
        }

        update_post_meta( $edit_id, '_priority', $priority );
        update_post_meta( $edit_id, '_product_addons', $product_addons );

        return $edit_id;
    }

    /**
     * Get add-on options ajax override.
     *
     * @since 3.9.4
     *
     * @return void
     */
    public function ajax_get_addon_options() {
        check_ajax_referer( 'wc-pao-get-addon-options', 'security' );

        global $product_addons;

        $option = WC_Product_Addons_Admin::get_new_addon_option();
        $loop   = '{loop}';

        ob_start();
        dokan_get_template_part(
            'product-addon/html-addon-option', '', array(
				'is_product_addon'    => true,
				'option'           => $option,
				'loop'             => $loop,
				'addon'            => $product_addons,
            )
        );
        $html = ob_get_clean();

        $html = str_replace( array( "\n", "\r" ), '', str_replace( "'", '"', $html ) );

        wp_send_json( array( 'html' => $html ) );
    }
}
