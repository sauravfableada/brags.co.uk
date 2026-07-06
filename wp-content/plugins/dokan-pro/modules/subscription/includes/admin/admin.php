<?php

use DokanPro\Modules\Subscription\Helper;
use WeDevs\Dokan\Utilities\OrderUtil;

/**
 * Admin related functions
 *
 * @package Dokan
 * @subpackage Subscription
 */
class DPS_Admin {

    public function __construct() {
        add_action( 'init', array( $this, 'register_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
        add_action( 'dokan-vue-admin-scripts', array( $this, 'vue_admin_enqueue_scripts' ) );

        // add product area in admin panel
        add_filter( 'product_type_selector', [ __CLASS__, 'add_product_type' ], 1 );
        add_action( 'woocommerce_product_options_general_product_data', [ __CLASS__, 'general_fields' ] );
        add_action( 'woocommerce_process_product_meta', [ __CLASS__, 'general_fields_save' ], 99 );

        add_action( 'dokan_admin_menu', [ __CLASS__, 'add_submenu_in_dokan_dashboard' ], 15 );
        add_filter( 'dokan-admin-routes', [ __CLASS__, 'vue_admin_routes' ] );

        // settings section
        add_filter( 'dokan_settings_sections', [ __CLASS__, 'add_new_section_admin_panael' ] );
        add_filter( 'dokan_settings_fields', [ __CLASS__, 'add_new_setting_field_admin_panael' ], 12, 1 );

        //add dropdown field with subscription packs
        add_action( 'dokan_seller_meta_fields', [ __CLASS__, 'add_subscription_packs_dropdown' ], 10, 1 );

        //save user meta
        add_action( 'dokan_process_seller_meta_fields', [ __CLASS__, 'save_meta_fields' ], 99 );

        // related orders metabox
        add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ], 99, 10, 2 );
        add_action( 'dokan_vendor_subscription_related_orders_meta_box_rows', [ $this, 'render_subscriptions_related_order' ], 10, 1 );

        if ( dokan_pro_is_hpos_enabled() ) {
            // Add a column that indicates whether an order is parent or renewal for a subscription
            add_filter( 'manage_woocommerce_page_wc-orders_columns', [ $this, 'add_contains_subscription_column' ], 8, 1 );
            add_action( 'manage_woocommerce_page_wc-orders_custom_column', [ $this, 'add_contains_subscription_column_content' ], 8, 2 );
        } else {
            // Add a column that indicates whether an order is parent or renewal for a subscription
            add_filter( 'manage_edit-shop_order_columns', [ $this, 'add_contains_subscription_column' ], 8, 1 );
            add_action( 'manage_shop_order_posts_custom_column', [ $this, 'add_contains_subscription_column_content' ], 8, 2 );
        }

        // remove sub-order class
        add_filter( 'post_class', [ $this, 'admin_shop_order_row_classes' ], 20, 1 ); // no need to add hpos support for this filter
        add_filter( 'dokan_manage_shop_order_custom_columns_order_number', [ $this, 'remove_suborder_notes' ], 10, 2 );

        add_action( 'woocommerce_product_data_tabs', [ $this, 'add_commission_tab_in_product' ] );
        add_action( 'woocommerce_product_data_panels', [ $this, 'product_pack_commission_html' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );

        // Add a vendor subscription filter option.
        add_filter( 'dokan_order_type_filter_options', [ $this, 'add_vendor_subscription_filter_option' ] );
        add_filter( 'dokan_order_type_filter_query_args', [ $this, 'filter_vendor_subscription_orders' ], 10, 2 );
    }

    /**
     * Remove sub-order text from Order list items
     *
     * @param string $output
     * @param WC_Order $order
     *
     * @since 3.3.7
     *
     * @return string
     */
    public function remove_suborder_notes( $output, $order ) {
        if ( Helper::is_vendor_subscription_order( $order ) ) {
            return '';
        }
        return $output;
    }

    /**
     * Remove dokan css classes on admin shop order table
     *
     * @global WP_Post $post
     *
     * @param array $classes
     *
     * @since 3.3.7
     *
     * @return array
     */
    public function admin_shop_order_row_classes( $classes ) {
        global $post;

        if ( $post->post_type === 'shop_order' && $post->post_parent !== 0 && Helper::is_vendor_subscription_order( $post->ID ) ) {
            $class = 'sub-order parent-' . $post->post_parent;
            $item_index = array_search( $class, $classes, true );
            if ( false !== $item_index ) {
                unset( $classes[ $item_index ] );
            }
        }

        return $classes;
    }

    /**
     * Add a column to the WooCommerce -> Orders admin screen to indicate whether an order is a
     * parent of a subscription, a renewal order for a subscription, or a regular order.
     *
     * @param array $columns The current list of columns
     *
     * @since 3.3.7
     *
     * @return array
     */
    public function add_contains_subscription_column( $columns ) {
        if ( class_exists( 'WC_Subscriptions_Order' ) ) {
            return $columns;
        }

        $column_header = '<span class="subscription_head tips" data-tip="' . esc_attr__( 'Vendor Subscription Relationship', 'dokan' ) . '">' . esc_attr__( 'Subscription Relationship', 'dokan' ) . '</span>';

        $new_columns = Helper::array_insert_after( 'shipping_address', $columns, 'subscription_relationship', $column_header );

        return $new_columns;
    }

    /**
     * Add column content to the WooCommerce -> Orders admin screen to indicate whether an
     * order is a parent of a subscription, a renewal order for a subscription, or a
     * regular order.
     *
     * This method will reuse column added by wcs, if wcs is enabled we are handling values provided by
     * wcs by our end, we are also deresitering hooks of wcs
     *
     * @param string $column The string of the current column
     *
     * @since 3.3.7
     *
     * @return void
     */
    public static function add_contains_subscription_column_content( $column, $post_id ) {
        // return if user doesn't have access
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        // check if post_id is an order
        if ( ! dokan_pro_is_order( $post_id ) ) {
            return;
        }

        // early return if not subscription_relationship column
        if ( 'subscription_relationship' !== $column ) {
            return;
        }

        if ( class_exists( 'WC_Subscriptions_Order' ) ) {
            // remove wc subscription hooks to render this column content
            remove_action( 'manage_shop_order_posts_custom_column', 'WC_Subscriptions_Order::add_contains_subscription_column_content', 10 );
            add_filter( 'manage_edit-shop_order_columns', 'WC_Subscriptions_Order::add_contains_subscription_column' );
            add_action( 'manage_shop_order_posts_custom_column', 'WC_Subscriptions_Order::add_contains_subscription_column_content', 10, 1 );

            // populate wc subscription field data
            $output = '';
            if ( wcs_order_contains_subscription( $post_id, 'renewal' ) ) {
                $output = '<span class="subscription_renewal_order tips" data-tip="' . esc_attr__( 'Renewal Order', 'woocommerce-subscriptions' ) . '"></span>'; //phpcs:ignore
            } elseif ( wcs_order_contains_subscription( $post_id, 'resubscribe' ) ) {
                $output = '<span class="subscription_resubscribe_order tips" data-tip="' . esc_attr__( 'Resubscribe Order', 'woocommerce-subscriptions' ) . '"></span>'; //phpcs:ignore
            } elseif ( wcs_order_contains_subscription( $post_id, 'parent' ) ) {
                $output = '<span class="subscription_parent_order tips" data-tip="' . esc_attr__( 'Parent Order', 'woocommerce-subscriptions' ) . '"></span>';  //phpcs:ignore
            }

            // early return if its wc subscription order
            if ( ! empty( $output ) ) {
                echo $output;
                return;
            }
        }

        // get order
        $order = wc_get_order( $post_id );
        // check if vendor subscription order
        if ( ! Helper::is_vendor_subscription_order( $order ) ) {
            echo '<span class="normal_order">&ndash;</span>';
            return;
        }

        // check if recurring order
        if ( 0 !== $order->get_parent_id() ) {
            // this is a recurring order
            echo '<span class="dokan_vs_renew_order tips" data-tip="' . esc_attr__( 'Vendor Subscription Renewal Order', 'dokan' ) . '"></span>';
            return;
        }

        // determine if order is recurring or non-recurring
        $product = Helper::get_vendor_subscription_product_by_order( $order );

        if ( ! $product ) {
            // maybe product has been deleted
            echo '<span class="normal_order">&ndash;</span>';
            return;
        }

        // check if recurring subscription order
        if ( Helper::is_recurring_pack( $product->get_id() ) ) {
            // this is a recurring pack
            echo '<span class="dokan_vs_recurring_order tips" data-tip="' . esc_attr__( 'Vendor Subscription Recurring Order', 'dokan' ) . '"></span>';
        } else {
            // this is a non-recurring pack
            echo '<span class="dokan_vs_non_recurring_order tips" data-tip="' . esc_attr__( 'Vendor Subscription Non Recurring Order', 'dokan' ) . '"></span>';
        }
    }

    /**
     * Add WC Meta boxes
     *
     * @since 3.3.7
     *
     * @return void
     */
    public function add_meta_boxes( $post_type, $post ) {
        $screen = dokan_pro_is_hpos_enabled()
            ? wc_get_page_screen_id( 'shop-order' )
            : 'shop_order';

        if ( $screen !== $post_type ) {
            return;
        }

        $order_id = OrderUtil::get_post_or_order_id( $post );

        // Only display the meta box if an order relates to a subscription
        if ( ! Helper::is_vendor_subscription_order( $order_id ) ) {
            return;
        }

        //remove woocommerce subscription metaox
        $subscription_screen_id = dokan_pro_is_hpos_enabled() ? wc_get_page_screen_id( 'shop_subscription' ) : 'shop_subscription';
        if ( ! empty( $subscription_screen_id ) ) {
            remove_meta_box( 'woocommerce-order-data', $subscription_screen_id, 'normal' );
        }

        // remove delivery time metabox
        remove_meta_box( 'dokan_delivery_time_fields', $screen, 'side' );

        // add subscription metabox
        add_meta_box( 'dokan_vendor_subscription_renewal_orders', __( 'Vendor Subscriptions Related Orders', 'dokan' ), [ $this, 'subscription_metabox_content' ], $screen, 'normal', 'high' );
    }

    /**
     * Render Subscription Metabox Content
     *
     * @param $post
     *
     * @since 3.3.7
     *
     * @return void
     */
    public function subscription_metabox_content( $post ) {
        $order_id = OrderUtil::get_post_or_order_id( $post );

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        dokan_get_template_part( 'admin/related-orders-table', '', [ 'is_subscription' => true, 'post' => $post ] );

        /**
         * @since 3.3.7
         * @args WC_Order $order
         * @args Post $post
         */
        do_action( 'dokan_vendor_subscription_related_orders_meta_box', $order, $post );
    }

    /**
     * Render Related Order Data
     *
     * @param $post
     *
     * @since 3.3.7
     *
     * @return void
     * @throws Exception
     */
    public function render_subscriptions_related_order( $post ) {
        $order_id = OrderUtil::get_post_or_order_id( $post );
        $order    = wc_get_order( $order_id );

        if ( ! $order ) {
            dokan_get_template_part( 'admin/related-orders-empty-row', '', [ 'is_subscription' => true ] );
            return;
        }

        $parent_order      = null;
        $orders_to_display = [];

        // get parent order
        if ( $order->get_parent_id() === 0 ) {
            // this is the parent order
            $parent_order = $order;
        } else {
            $parent_order = wc_get_order( $order->get_parent_id() );
        }

        // check if order is main order or recurring order
        $args = [
            'parent'  => $parent_order->get_id(),
            'limit'   => -1,
            'orderby' => 'date',
            'order'   => 'DESC',
            'type'    => 'shop_order',
        ];

        $query = new WC_Order_Query( $args );
        $orders_to_display = $query->get_orders();

        // check if we got renewal orders
        if ( empty( $orders_to_display ) ) {
            dokan_get_template_part( 'admin/related-orders-empty-row', '', [ 'is_subscription' => true ] );
            return;
        }

        //include current order
        if ( $parent_order->get_id() !== $order->get_id() ) {
            $orders_to_display[] = $parent_order;
        }

        $orders_to_display = apply_filters( 'dokan_vendor_subscription_admin_related_orders_to_display', $orders_to_display, $parent_order, $post );

        foreach ( $orders_to_display as $order ) {
            // Skip the order being viewed.
            if ( $order->get_id() === $order_id ) {
                continue;
            }

            dokan_get_template_part( 'admin/related-orders-row', '', [ 'is_subscription' => true, 'order' => $order ] );
        }
    }

    /**
     * Register Scripts
     *
     * @since 3.7.4
     */
    public function register_scripts() {
        [ $suffix, $version ] = dokan_get_script_suffix_and_version();

        wp_register_style( 'dps-custom-style', DPS_URL . '/assets/css/style' . $suffix . '.css', false, $version );
        wp_register_script( 'dps-custom-admin-js', DPS_URL . '/assets/js/admin-script' . $suffix . '.js', array( 'jquery' ), $version, true );
        wp_register_style( 'dps-subscription', DPS_URL . '/assets/js/subscription' . $suffix . '.css', [], $version );
        wp_register_script( 'dps-subscription', DPS_URL . '/assets/js/subscription' . $suffix . '.js', array( 'jquery', 'dokan-vue-vendor', 'dokan-vue-bootstrap' ), $version, true );
        wp_register_script( 'dps-product-commission', DPS_URL . '/assets/js/product-commission' . $suffix . '.js', array( 'jquery' ), $version, true );
        wp_register_style( 'dps-product-commission', DPS_URL . '/assets/js/style-product-commission' . $suffix . '.css', [], $version );

        $script_assets_path = DPS_PATH . '/assets/js/dokan-subscription-admin-vendor.asset.php';
        if ( file_exists( $script_assets_path ) ) {
            $vendor_asset = require $script_assets_path;
            $dependencies = $vendor_asset['dependencies'] ?? [];
            $version      = $vendor_asset['version'] ?? '';
            wp_register_script(
                'dokan-subscription-admin-vendor',
                DPS_URL . '/assets/js/dokan-subscription-admin-vendor' . $suffix . '.js',
                $dependencies,
                $version,
                true
            );
        }
    }

    public function admin_enqueue_scripts( $hook ) {
        wp_enqueue_style( 'dps-custom-style' );
        wp_enqueue_script( 'dps-custom-admin-js' );
        // Vendor list filter (React) similar to Seller Badge module
        wp_enqueue_script( 'dokan-subscription-admin-vendor' );

        wp_localize_script(
            'dps-custom-admin-js', 'dokanSubscription', array(
                'ajaxurl'               => admin_url( 'admin-ajax.php' ),
                'subscriptionLengths'   => Helper::get_subscription_ranges(),
                'isSubscriptionEnabled' => Helper::is_vendor_subscription_enabled(),
                'exclusiveForAdminOnly' => __( 'This subscription is set to recurring and exclusive for Admins only will be only available for non recurring subscription.', 'dokan' ),
            )
        );

        $screen = dokan_pro_is_hpos_enabled() ? wc_get_page_screen_id( 'shop_order' ) : 'shop_order';
        if ( $screen === $hook || $screen === get_current_screen()->post_type ) {
            add_action( 'admin_head', [ $this, 'load_admin_order_page_css' ], 10 );
        }
    }

    public function vue_admin_enqueue_scripts() {
        wp_enqueue_style( 'dps-subscription' );
        wp_enqueue_script( 'dps-subscription' );
    }

    /**
     * WooCommerce Orders admin table css for vendor subscription relation
     *
     * @since 3.3.7
     *
     * @return void
     */
    public function load_admin_order_page_css() {
        ?>
        <style>
            table.wp-list-table .column-subscription_relationship {
                width: 48px;
                text-align: center;
            }

            table.wp-list-table span.normal_order {
                color: #999;
            }

            table.wp-list-table .subscription_head:after,
            table.wp-list-table .dokan_vs_recurring_order:after,
            table.wp-list-table .dokan_vs_non_recurring_order:after,
            table.wp-list-table .dokan_vs_renew_order:after
            {
                font-weight: 400;
                margin: 0;
                text-indent: 0;
                position: absolute;
                width: 100%;
                height: 100%;
                text-align: center;
                line-height: 16px;
                top: 0;
                speak: none;
                font-variant: normal;
                text-transform: none;
                -webkit-font-smoothing: antialiased;
                left: 0;
            }

            table.wp-list-table .subscription_head,
            table.wp-list-table .dokan_vs_recurring_order,
            table.wp-list-table .dokan_vs_non_recurring_order,
            table.wp-list-table .dokan_vs_renew_order {
                display: block;
                text-indent: -9999px;
                position: relative;
                height: 1em;
                margin: 0 auto;
            }

            table.wp-list-table .subscription_head {
                width: 1em;
            }

            table.wp-list-table .subscription_head:after {
                font-family: WooCommerce;
                content: "\e014";
            }

            table.wp-list-table .dokan_vs_recurring_order:after {
                font-family: Dashicons;
                font-size: 20px;
                line-height: 20px;
                content: "\f113";
                color: #3ba0aa;
            }

            table.wp-list-table .dokan_vs_non_recurring_order:after {
                font-family: Dashicons;
                font-size: 20px;
                line-height: 20px;
                content: "\f469";
                color: #8a9da8;
            }

            table.wp-list-table .dokan_vs_renew_order:after {
                font-family: Dashicons;
                font-size: 20px;
                line-height: 20px;
                content: "\f321";
                color: #62baf4;
            }

            table.wp-list-table .subscription_parent_order,
            table.wp-list-table .subscription_resubscribe_order,
            table.wp-list-table .subscription_renewal_order {
                font-size: 18px;
            }

            @media only screen and (max-width: 782px) {
                table.wp-list-table .dokan_vs_renew_order,
                table.wp-list-table .dokan_vs_non_recurring_order,
                table.wp-list-table .dokan_vs_recurring_order {
                    margin: 0;
                }
                table.wp-list-table .column-subscription_relationship {
                    text-align: inherit;
                }
            }
        </style>
        <?php
    }

    /**
     * Add woocommerce extra product type
     *
     * @param array $types
     * @param array $product_type
     *
     * @return array
     */
    public static function add_product_type( $types ) {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return $types;
        }

        $types['product_pack'] = __( 'Dokan Subscription', 'dokan' );

        return $types;
    }

    /**
     * Add extra custom field in woocommerce product type
     */
    public static function general_fields() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        global $woocommerce, $post;

        echo '<div class="options_group show_if_product_pack">';

        woocommerce_wp_text_input(
            array(
                'id'                => '_no_of_product',
                'label'             => __( 'Number of Products', 'dokan' ),
                'placeholder'       => __( 'Put -1 for unlimited products', 'dokan' ),
                'description'       => __( 'Enter the no of product you want to give this package.', 'dokan' ),
                'type'              => 'number',
                'custom_attributes' => array(
                    'step' => 'any',
                    'min'  => '-1',
                ),
            )
        );

        woocommerce_wp_text_input(
            array(
                'id'                => '_pack_validity',
                'label'             => __( 'Pack Validity', 'dokan' ),
                'placeholder'       => 'Put -1 for unlimited days',
                'description'       => '<span id="_pack_validity_desc" style="color:#666;">' . esc_html__( 'Enter no of validity days you want to give this pack', 'dokan' ) . '</span>',
                'type'              => 'number',
                'custom_attributes' => array(
                    'step'             => 'any',
                    'min'              => '-1',
                    'data-msg-invalid' => __( '⚠ Value 0 is not allowed.', 'dokan' ),
                    'data-msg-default' => __( 'Enter no of validity days you want to give this pack', 'dokan' ),
                    'oninput'          => 'onPackValidityInput(this)',
                ),
            )
        );

        do_action( 'dps_subscription_product_fields_after_pack_validity' );
        woocommerce_wp_checkbox(
            array(
                'id'          => '_exclusive_for_admin_only',
                'label'       => __( 'Exclusive for Admins only', 'dokan' ),
                'description' => __( 'This subscription is exclusive for Admins only and will be only visible to Admin', 'dokan' ),
            )
        );

        // vendor allowed product types
        echo '<p class="form-field dokan_subscription_allowed_product_types">';
        echo '<label for="dokan_subscription_allowed_product_types">' . __( 'Allowed Product Types', 'dokan' ) . '</label>';
        echo '<select multiple="multiple" data-placeholder=" ' . __( 'Any product types', 'dokan' ) . '" class="wc-enhanced-select" id="_vendor_allowed_product_type" name="dokan_subscription_allowed_product_types[]" style="width: 350px;">';
        Helper::get_product_types_options();
        echo '</select>';
        echo '<span class="description">' . __( 'Select product type for this package. Leave empty to allow any product type.', 'dokan' ) . '</span>';
        echo '</p>';

        // vendor allowed categories
        echo '<p class="form-field _vendor_allowed_categories">';
        $selected_cat = get_post_meta( $post->ID, '_vendor_allowed_categories', true );
        echo '<label for="_vendor_allowed_categories">' . __( 'Allowed categories', 'dokan' ) . '</label>';
        echo '<select multiple="multiple" data-placeholder=" ' . __( 'Any categories', 'dokan' ) . '" class="wc-enhanced-select" id="_vendor_allowed_categories" name="_vendor_allowed_categories[]" style="width: 350px;">';
        $r = array();
        $r['pad_counts']    = 1;
        $r['hierarchical']  = 1;
        $r['hide_empty']    = 0;
        $r['value']         = 'id';
        $r['selected']      = ! empty( $selected_cat ) ? array_map( 'absint', $selected_cat ) : '';
        $r['orderby']       = 'name';

        $categories = get_terms( 'product_cat', $r );
        include_once WC()->plugin_path() . '/includes/walkers/class-product-cat-dropdown-walker.php';

        echo wc_walk_category_dropdown_tree( $categories, 0, $r );
        echo '</select>';
        echo '<span class="description">' . __( 'Select specific product category for this package. Leave empty to select all categories.', 'dokan' ) . '</span>';

        echo '</p>';

        woocommerce_wp_checkbox(
            array(
                'id'          => '_enable_gallery_restriction',
                'label'       => __( 'Restrict Gallery Image Upload', 'dokan' ),
                'description' => __( 'Please check this if you want to restrict gallery image uploading.', 'dokan' ),
            )
        );

        woocommerce_wp_text_input(
            array(
                'id'                => '_gallery_image_restriction_count',
                'label'             => __( 'Maximum Image', 'dokan' ),
                'placeholder'       => 'Put -1 for unlimited image',
                'description'       => __( 'Max Image vendor can upload', 'dokan' ),
                'type'              => 'number',
                'custom_attributes' => array(
                    'step' => 'any',
                    'min'  => '-1',
                ),
            )
        );

        ?>

        <script type="text/javascript">
            ;(function () {
                var image_enable = document.querySelector('#_enable_gallery_restriction');
                var image_count = document.querySelector('._gallery_image_restriction_count_field');
                if (image_enable.checked === true) {
                    image_count.style.display = '';
                } else {
                    image_count.style.display = 'none';
                }
                image_enable.addEventListener('click', function () {
                    if (image_enable.checked === true) {
                        image_count.style.display = '';
                    } else {
                        image_count.style.display = 'none';
                    }
                })
            })();
        </script>

        <?php

        woocommerce_wp_checkbox(
            array(
                'id'          => '_enable_recurring_payment',
                'label'       => __( 'Recurring Payment', 'dokan' ),
                'description' => __( 'Please check this if you want to enable recurring payment system', 'dokan' ),
            )
        );
        echo '</div>';

        // Set month as the default billing period
        if ( ! $subscription_period = get_post_meta( $post->ID, '_dokan_subscription_period', true ) ) { // phpcs:ignore
            $subscription_period = 'day';
        }

        echo '<div class="options_group show_if_product_pack dokan_subscription_pricing">';
        // Subscription Period Interval
        echo '<div class="dokan-billing-cycle-wrap">';
        woocommerce_wp_select(
            array(
                'id'      => '_dokan_subscription_period_interval',
                'class'   => 'wc_input_subscription_period_interval',
                'label'   => __( 'Billing cycle', 'dokan' ),
                'options' => Helper::get_subscription_period_interval_strings(),
            )
        );

        // Billing Period
        woocommerce_wp_select(
            array(
                'id'          => '_dokan_subscription_period',
                'class'       => 'wc_input_subscription_period',
                'label'       => '',
                'options'     => Helper::get_subscription_period_strings(),
            )
        );

        echo '</div>';

        echo '<div class="dokan-billing-cyle-clear"></div>';

        // Subscription Length
        woocommerce_wp_select(
            array(
                'id'          => '_dokan_subscription_length',
                'class'       => 'wc_input_subscription_length',
                'label'       => __( 'Billing cycle stop', 'dokan' ),
                'options'     => Helper::get_subscription_ranges( $subscription_period ),

            )
        );

        woocommerce_wp_checkbox(
            array(
                'id'          => 'dokan_subscription_enable_trial',
                'label'       => __( 'Enable Trial', 'dokan' ),
                'description' => __( 'Please check this if you want to allow trial subscirption.', 'dokan' ),
            )
        );

        echo '<p class="form-field dokan_subscription_trial_period">';
        echo '<label for="dokan_subscription_trial_period">' . __( 'Trial Period', 'dokan' ) . '</label>';

        Helper::get_trial_period_options();

        echo '<span class="description">' . __( 'Define the trial period', 'dokan' ) . '</span>';
        echo '</p>';
        echo '</div>';

        wp_nonce_field( 'dps_product_fields_nonce', 'dps_product_pack' );

        do_action( 'dps_subscription_product_fields' );
    }


    /**
     * Manupulate custom filed meta data in post meta
     *
     * @param integer $post_id
     */
    public static function general_fields_save( $post_id ) {
        if ( ! isset( $_POST['dps_product_pack'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dps_product_pack'] ) ), 'dps_product_fields_nonce' ) ) {
            return;
        }

        if ( ! isset( $_POST['product-type'] ) || sanitize_text_field( wp_unslash( $_POST['product-type'] ) ) !== 'product_pack' ) {
            return;
        }

        update_post_meta( $post_id, '_virtual', 'yes' );
        update_post_meta( $post_id, '_sold_individually', 'yes' );

        // WC 3.0+ compatibility
        $visibility_term = array( 'exclude-from-search', 'exclude-from-catalog' );
        wp_set_post_terms( $post_id, $visibility_term, 'product_visibility', false );
        update_post_meta( $post_id, '_visibility', 'hidden' );

        $woocommerce_no_of_product_field = isset( $_POST['_no_of_product'] ) ? intval( wp_unslash( $_POST['_no_of_product'] ) ) : '';
        if ( $woocommerce_no_of_product_field !== '' ) {
            update_post_meta( $post_id, '_no_of_product', $woocommerce_no_of_product_field );
        }

        $woocommerce_pack_validity_field = isset( $_POST['_pack_validity'] ) ? intval( wp_unslash( $_POST['_pack_validity'] ) ) : '';
        if ( $woocommerce_pack_validity_field !== '' ) {
                if ( 0 === intval( $woocommerce_pack_validity_field ) ) {
                    $woocommerce_pack_validity_field = -1;
                }
            update_post_meta( $post_id, '_pack_validity', $woocommerce_pack_validity_field );
        }

        if ( ! empty( $_POST['dokan_subscription_allowed_product_types'] ) ) {
            update_post_meta( $post_id, 'dokan_subscription_allowed_product_types', wc_clean( wp_unslash( $_POST['dokan_subscription_allowed_product_types'] ) ) );
        } else {
            delete_post_meta( $post_id, 'dokan_subscription_allowed_product_types' );
        }

        if ( ! empty( $_POST['_vendor_allowed_categories'] ) ) {
            update_post_meta( $post_id, '_vendor_allowed_categories', wc_clean( wp_unslash( $_POST['_vendor_allowed_categories'] ) ) );
        } else {
            delete_post_meta( $post_id, '_vendor_allowed_categories' );
        }

        $woocommerce_enable_gallery_restriction = isset( $_POST['_enable_gallery_restriction'] ) ? 'yes' : 'no';
        update_post_meta( $post_id, '_enable_gallery_restriction', wc_clean( $woocommerce_enable_gallery_restriction ) );

        $gallery_image_restriction_count = isset( $_POST['_gallery_image_restriction_count'] ) && intval( $_POST['_gallery_image_restriction_count'] ) >= 0 ? intval( wp_unslash( $_POST['_gallery_image_restriction_count'] ) ) : -1;
        if ( $woocommerce_enable_gallery_restriction === 'yes' ) {
            update_post_meta( $post_id, '_gallery_image_restriction_count', $gallery_image_restriction_count );
        } elseif ( $woocommerce_enable_gallery_restriction === 'no' ) {
            delete_post_meta( $post_id, '_gallery_image_restriction_count' );
        }

        $dokan_subscription_enable_trial = isset( $_POST['dokan_subscription_enable_trial'] ) ? 'yes' : 'no';
        update_post_meta( $post_id, 'dokan_subscription_enable_trial', $dokan_subscription_enable_trial );

        $dokan_subscription_trail_range = isset( $_POST['dokan_subscription_trail_range'] ) ? intval( wp_unslash( $_POST['dokan_subscription_trail_range'] ) ) : 1;
        if ( ! empty( $dokan_subscription_trail_range ) ) {
            update_post_meta( $post_id, 'dokan_subscription_trail_range', $dokan_subscription_trail_range );
        }

        $dokan_subscription_trial_period_types = isset( $_POST['dokan_subscription_trial_period_types'] ) ? sanitize_text_field( wp_unslash( $_POST['dokan_subscription_trial_period_types'] ) ) : 'days';
        if ( ! empty( $dokan_subscription_trial_period_types ) ) {
            update_post_meta( $post_id, 'dokan_subscription_trial_period_types', $dokan_subscription_trial_period_types );
        }

        $woocommerce_enable_recurring_field = isset( $_POST['_enable_recurring_payment'] ) ? 'yes' : 'no';
        update_post_meta( $post_id, '_enable_recurring_payment', $woocommerce_enable_recurring_field );

        $woocommerce_subscription_period_interval_field = isset( $_POST['_dokan_subscription_period_interval'] ) ? intval( wp_unslash( $_POST['_dokan_subscription_period_interval'] ) ) : '';
        if ( $woocommerce_enable_recurring_field !== '' ) {
            update_post_meta( $post_id, '_dokan_subscription_period_interval', $woocommerce_subscription_period_interval_field );
        }

        $woocommerce_subscription_period_field = isset( $_POST['_dokan_subscription_period'] ) ? sanitize_text_field( wp_unslash( $_POST['_dokan_subscription_period'] ) ) : '';
        if ( $woocommerce_enable_recurring_field !== '' ) {
            update_post_meta( $post_id, '_dokan_subscription_period', $woocommerce_subscription_period_field );
        }

        $woocommerce_subscription_length_field = isset( $_POST['_dokan_subscription_length'] ) ? intval( wp_unslash( $_POST['_dokan_subscription_length'] ) ) : 0;
        update_post_meta( $post_id, '_dokan_subscription_length', $woocommerce_subscription_length_field );

        $product = wc_get_product( $post_id );
        $exclusive_for_admin_only = isset( $_POST['_exclusive_for_admin_only'] ) ? 'yes' : 'no';
        $product->update_meta_data( '_exclusive_for_admin_only', $exclusive_for_admin_only );
        $product->save();

        do_action( 'dps_process_subcription_product_meta', $post_id );
    }


    /**
     * Add new Section in admin dokan settings
     *
     * @param array $sections
     *
     * @return array
     */
    public static function add_new_section_admin_panael( $sections ) {
        $sections['dokan_product_subscription'] = [
            'id'                   => 'dokan_product_subscription',
            'title'                => __( 'Vendor Subscription', 'dokan' ),
            'icon_url'             => DPS_URL . '/assets/images/subscription.svg',
            'description'          => __( 'Manage Subscription Plans', 'dokan' ),
            'document_link'        => 'https://dokan.co/docs/wordpress/modules/how-to-install-use-dokan-subscription/',
            'settings_title'       => __( 'Vendor Subscription Settings', 'dokan' ),
            'settings_description' => __( 'Configure marketplace settings to authorize vendors to create subscription products for their stores.', 'dokan' ),
        ];

        return $sections;
    }

    /**
     * Get all Pages
     *
     * @param string  $post_type
     * @return array
     */
    public static function get_post_type( $post_type ) {
        $pages_array = array( '-1' => __( '- select -', 'dokan' ) );
        $pages = get_posts(
            array(
                'post_type' => $post_type,
                'numberposts' => -1,
            )
        );

        if ( $pages ) {
            foreach ( $pages as $page ) {
                $pages_array[ $page->ID ] = $page->post_title;
            }
        }

        return $pages_array;
    }

    /**
     * Add new Settings field in admin dashboard for selection product
     * subscription page
     *
     * @param array   $settings_fields
     * @return array
     */
    public static function add_new_setting_field_admin_panael( $settings_fields ) {
        $pages_array = self::get_post_type( 'page' );

        $settings_fields['dokan_product_subscription'] = array(
            'subscription_pack' => array(
                'name'    => 'subscription_pack',
                'label'   => __( 'Subscription', 'dokan' ),
                'type'    => 'select',
                'options' => $pages_array,
                'tooltip' => __( 'Select the page in which you want to show subscription packages.', 'dokan' ),
            ),
            'enable_pricing' => array(
                'name'  => 'enable_pricing',
                'label' => __( 'Enable Vendor Subscription', 'dokan' ),
                'desc'  => __( 'Enable subscription for vendor', 'dokan' ),
                'type'  => 'switcher',
            ),
            'enable_subscription_pack_in_reg' => [
                'name'    => 'enable_subscription_pack_in_reg',
                'label'   => __( 'Enable Subscription in Registration Form', 'dokan' ),
                'desc'    => __( 'Enable subscription pack in registration form for new vendor', 'dokan' ),
                'type'    => 'switcher',
                'default' => 'on',
                'tooltip' => __( 'If checked, vendor completes registration only after subscribing to a pack', 'dokan' ),
            ],
            'no_of_days_before_mail' => array(
                'name'    => 'no_of_days_before_mail',
                'label'   => __( 'No. of Days', 'dokan' ),
                'desc'    => __( 'Before an email will be sent to the vendor', 'dokan' ),
                'type'    => 'text',
                'size'    => 'midium',
                'default' => '2',
            ),
            'product_status_after_end' => array(
                'name'    => 'product_status_after_end',
                'label'   => __( 'Product Status', 'dokan' ),
                'desc'    => __( 'Product status when vendor pack validity will expire', 'dokan' ),
                'type'    => 'select',
                'default' => 'draft',
                'options' => array(
                    'publish' => __( 'Published', 'dokan' ),
                    'pending' => __( 'Pending Review', 'dokan' ),
                    'draft'   => __( 'Draft', 'dokan' ),
                ),
            ),
        );

        if ( dokan_pro()->module->product_subscription->is_dokan_plugin() ) {
            unset( $settings_fields['dokan_product_subscription'][0] );
        }

        return $settings_fields;
    }

    /**
     * Add submenu page in dokan Dashboard
     */
    public static function add_submenu_in_dokan_dashboard( $capability ) {
        if ( ! Helper::is_subscription_module_enabled() ) {
            return;
        }

        global $submenu;

        $slug = 'dokan';

        if ( current_user_can( 'manage_options' ) ) {
            $submenu[ $slug ][] = array( __( 'Subscriptions', 'dokan' ), $capability, 'admin.php?page=' . $slug . '#/subscriptions' );
        }
    }

    /**
     * Add subscripton route
     *
     * @param  array $routes
     *
     * @return array
     */
    public static function vue_admin_routes( $routes ) {
        $routes[] = [
            'path'      => '/subscriptions',
            'name'      => 'Subscriptions',
            'component' => 'Subscriptions',
        ];

        return $routes;
    }

    /**
     * Add subscription packs in drowpdown to let admin select a pack for the seller
     */
    public static function add_subscription_packs_dropdown( $user ) {
        $users_assigned_pack       = get_user_meta( $user->ID, 'product_package_id', true );
        $vendor_allowed_categories = get_user_meta( $user->ID, 'vendor_allowed_categories', true );

        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_type',
                    'field'    => 'slug',
                    'terms'    => 'product_pack',
                ),
            ),
            'meta_query' => array(
                array(
                    'key' => '_enable_recurring_payment',
                    'value' => 'no',
                ),
            ),
        );
        $sub_packs = get_posts( apply_filters( 'dps_get_non_recurring_pack_arg', $args ) );
        ?>
        <tr>
            <td>
                <h3><?php esc_html_e( 'Dokan Subscription', 'dokan' ); ?> </h3>
            </td>
        </tr>

        <?php if ( $users_assigned_pack ) : ?>
            <tr>
                <td><?php esc_html_e( 'Currently Activated Pack', 'dokan' ); ?></td>
                <td> <?php echo get_the_title( $users_assigned_pack ); ?> </td>
            </tr>
            <tr>
                <td><?php esc_html_e( 'Start Date :' ); ?></td>
                <td><?php echo dokan_format_date( get_user_meta( $user->ID, 'product_pack_startdate', true ) ); ?>
                </td>
            </tr>
            <tr>
                <td><?php esc_html_e( 'End Date :' ); ?></td>
                <td>
                    <?php
                    $product_pack_enddate = get_user_meta( $user->ID, 'product_pack_enddate', true );
                    if ( 'unlimited' === $product_pack_enddate ) {
                        printf( __( 'Lifetime package.', 'dokan' ) );
                    } else {
                        echo dokan_format_date( $product_pack_enddate );
                    }
                    ?>
                </td>
            </tr>
        <?php endif; ?>

        <tr>
            <?php if ( $users_assigned_pack && get_user_meta( $user->ID, '_customer_recurring_subscription', true ) === 'active' ) : ?>
                <td colspan="2"><em><?php esc_html_e( 'This user already has recurring pack assigned. Are you sure to assign a new normal pack to the user? If you do so, the existing recurring plan will be replaced with the new one', 'dokan' ); ?></em></td>
            <?php endif; ?>
        </tr>

        <tr>
            <td><?php esc_html_e( 'Allowed categories', 'dokan' ); ?></td>
            <td>
                <?php
                $selected_cat = ! empty( $vendor_allowed_categories ) ? $vendor_allowed_categories : get_post_meta( $users_assigned_pack, '_vendor_allowed_categories', true );
                echo '<select multiple="multiple" data-placeholder=" ' . __( 'Select categories&hellip;', 'dokan' ) . '" class="wc-enhanced-select" id="vendor_allowed_categories" name="vendor_allowed_categories[]" style="width: 350px;">';
                $r = array();
                $r['pad_counts']    = 1;
                $r['hierarchical']  = 1;
                $r['hide_empty']    = 0;
                $r['value']         = 'id';
                $r['orderby']       = 'name';
                $r['selected']      = ! empty( $selected_cat ) ? array_map( 'absint', $selected_cat ) : '';
                $r['parent']        = 0;

                $categories = get_terms( 'product_cat', $r );

                include_once WC()->plugin_path() . '/includes/walkers/class-product-cat-dropdown-walker.php';

                echo wc_walk_category_dropdown_tree( $categories, 0, $r );
                echo '</select>';
                ?>
                <p class="description"><?php esc_html_e( 'You can override allowed categories for this user. If empty then the predefined category for this pack will be selected', 'dokan' ); ?></p>
            </td>
        </tr>

        <tr class="dps_assign_pack">
            <td><?php esc_html_e( 'Assign Subscription Pack', 'dokan' ); ?></td>
            <td>
                <select name="_dokan_user_assigned_sub_pack">
                    <option value="" <?php selected( $users_assigned_pack, '' ); ?>><?php esc_html_e( '-- Select a pack --', 'dokan' ); ?></option>
                    <?php foreach ( $sub_packs as $pack ) : ?>
                        <option value="<?php echo $pack->ID; ?>" <?php selected( $users_assigned_pack, $pack->ID ); ?>><?php echo $pack->post_title; ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php _e( 'You can only assign non-recurring packs', 'dokan' ); ?></p>
            </td>
        </tr>
        <?php
    }

    /**
     * Save meta fields
     *
     * @param int $user_id
     *
     * @return void
     * @throws Exception
     */
    public static function save_meta_fields( $user_id ) {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        if ( ! isset( $_POST['dokan_enable_selling'] ) ) {
            return;
        }

        if ( ! isset( $_POST['_dokan_user_assigned_sub_pack'] ) ) {
            return;
        }

        $pack_id = intval( $_POST['_dokan_user_assigned_sub_pack'] );

        if ( ! $pack_id || empty( $pack_id ) ) {
            return;
        }

        if ( ! empty( $_POST['vendor_allowed_categories'] ) ) {
            $allowed_cat = wc_clean( $_POST['vendor_allowed_categories'] );
            update_user_meta( $user_id, 'vendor_allowed_categories', $allowed_cat );
        } else {
            delete_user_meta( $user_id, 'vendor_allowed_categories' );
        }

        if ( get_user_meta( $user_id, 'product_package_id', true ) == $pack_id ) {
            return;
        }

        //cancel paypal if current pack is recurring
        if ( get_user_meta( $user_id, '_customer_recurring_subscription', true ) == 'active' ) {
            $order_id = get_user_meta( $user_id, 'product_order_id', true );

            Helper::log( 'Subscription cancel check: On assign pack by admin cancel Recurring Subscription of User #' . $user_id . ' on order #' . $order_id );

            //this hook will ensure other gateway payments cancels their subscriptions
            do_action( 'dps_cancel_recurring_subscription', $order_id, $user_id, true );

            do_action( 'dokan_subscription_cancelled_by_admin', $user_id, $order_id );
            $subscriber_id = get_user_meta( $user_id, '_paypal_subscriber_ID', true );

            if ( $order_id && ! empty( $subscriber_id ) ) {
                DPS_PayPal_Standard_Subscriptions::cancel_subscription_with_paypal( $order_id, $user_id );
            }
        }

        // create a order for the subscription
        try {
            $order = new WC_Order();
            $order->add_product( wc_get_product( $pack_id ) );
            $order->set_created_via( 'dokan' );
            $order->set_customer_id( $user_id );
            $order->calculate_totals();
            $order->set_status( 'completed' );
            $order->save();
        } catch ( Exception $e ) {
            return new WP_Error( 'dokan-order-error', $e->getMessage() );
        }

        $pack_validity           = get_post_meta( $pack_id, '_pack_validity', true );
        $admin_commission        = get_post_meta( $pack_id, '_subscription_product_admin_commission', true );
        $admin_additional_fee    = get_post_meta( $pack_id, '_subscription_product_admin_additional_fee', true );
        $admin_commission_type   = get_post_meta( $pack_id, '_subscription_product_admin_commission_type', true );
        $category_admin_commission   = get_post_meta( $pack_id, '_subscription_product_admin_category_based_commission', true );

        update_user_meta( $user_id, 'product_package_id', $pack_id );
        update_user_meta( $user_id, 'product_order_id', $order->get_id() );
        update_user_meta( $user_id, 'product_no_with_pack', get_post_meta( $pack_id, '_no_of_product', true ) ); //number of products
        update_user_meta( $user_id, 'product_pack_startdate', dokan_current_datetime()->format( 'Y-m-d H:i:s' ) );

        if ( intval( $pack_validity ) > 0 ) {
           
            update_user_meta( $user_id, 'product_pack_enddate', dokan_current_datetime()->modify( "+$pack_validity days" )->format( 'Y-m-d H:i:s' ) );
        } else {

            update_user_meta( $user_id, 'product_pack_enddate', 'unlimited' );
        }

        update_user_meta( $user_id, 'can_post_product', 1 );
        update_user_meta( $user_id, '_customer_recurring_subscription', '' );

        $vendor = dokan()->vendor->get( $user_id );
        $vendor->save_commission_settings(
            [
                'percentage'           => $admin_commission,
                'type'                 => $admin_commission_type,
                'flat'                 => $admin_additional_fee,
                'category_commissions' => $category_admin_commission,
            ]
        );

        do_action( 'dokan_vendor_purchased_subscription', $user_id );
    }

    public function add_commission_tab_in_product($tabs) {
        $tabs['dokan_product_pack_commission'] = array(
            'label'    => __( 'Commission', 'dokan' ),
            'target'   => 'dokan_product_pack_commission_data',
            'class'    => array('show_if_product_pack'),
            'priority' => 1000,
        );

        return $tabs;
    }

    public function product_pack_commission_html() {
        dokan_get_template_part( 'admin/html-product-data-commission', '', [ 'is_subscription' => true ] );

        wp_enqueue_script( 'dokan-vue-bootstrap' );
        wp_localize_script( 'dokan-vue-bootstrap', 'dokan', dokan()->scripts->get_admin_localized_scripts() );
        wp_enqueue_script('dps-product-commission' );
        wp_localize_script(
            'dps-product-commission',
            'dokanCommission',
            [
                'commissionTypes' => dokan_commission_types(),
            ]
        );
        wp_enqueue_style('dps-product-commission' );
        wp_enqueue_style( 'dokan-fontawesome' );

        wp_enqueue_style(
            'dokan-category-commission',
            DOKAN_PLUGIN_ASSEST . '/css/dokan-category-commission.css',
            [],
            DOKAN_PLUGIN_VERSION
        );
    }

    /**
     * Enqueue admin scripts.
     *
     * @since 4.1.3
     *
     * @return void
     */
    public function enqueue_admin_scripts() {
        $admin_dashboard_file = DPS_PATH . '/assets/js/vendor-single.asset.php';
        if ( file_exists( $admin_dashboard_file ) ) {
            $dashboard_script = require $admin_dashboard_file;
            $dependencies     = $dashboard_script['dependencies'] ?? [];
            $version          = $dashboard_script['version'] ?? '';

            wp_enqueue_script(
                'dokan-admin-dashboard-vendor-single-subscription',
                DPS_URL . '/assets/js/vendor-single.js',
                $dependencies,
                $version,
                true
            );

        }
    }

    /**
     * Add Vendor Subscription filter option to order type filter dropdown.
     *
     * @since 4.2.1
     *
     * @param array $filter_options Array of filter options
     *
     * @return array
     */
    public function add_vendor_subscription_filter_option( $filter_options ) {
        $filter_options['vendor_subscription'] = esc_html__( 'Vendor Subscription', 'dokan' );
        return $filter_options;
    }

    /**
     * Filter orders by Vendor Subscription type.
     *
     * @since 4.2.1
     *
     * @param array  $query_args  Original query arguments.
     * @param string $filter_type The selected filter type.
     *
     * @return array|null
     */
    public function filter_vendor_subscription_orders( $query_args, $filter_type ) {
        // Only handle the vendor_subscription filter type.
        if ( 'vendor_subscription' !== $filter_type ) {
            return $query_args;
        }

        // @codingStandardsIgnoreStart
        // Filter orders by vendor subscription meta - works for both HPOS and legacy.
        $query_args['meta_query'] = $query_args['meta_query'] ?? [];
        $query_args['meta_query']['relation'] = 'OR';
        $query_args['meta_query'][] = [
            'key'     => '_dokan_vendor_subscription_order',
            'value'   => 'yes',
            'compare' => '=',
        ];
        $query_args['meta_query'][] = [
            'key'     => '_pack_validity',
            'compare' => 'EXISTS',
        ];
        // @codingStandardsIgnoreEnd

        return $query_args;
    }
}

new DPS_Admin();
