<?php

namespace WeDevs\DokanPro;

use WP_Error;
use WeDevs\Dokan\ProductCategory\Helper;

/**
 * Dokan Pro Product Class
 *
 * @since 2.4
 *
 * @package dokan
 */
class Products {

    /**
     * Holds the inline edit options
     *
     * @var array
     */
    private $inline_edit_options = array();

    /**
     * Load automatically when class initiate
     *
     * @since 2.4
     *
     * @uses actions
     * @uses filters
     */
    public function __construct() {
        add_action( 'dokan_product_edit_after_inventory_variants', array( $this, 'load_shipping_tax_content' ), 10, 2 );
        add_action( 'dokan_product_edit_after_inventory_variants', array( $this, 'load_linked_product_content' ), 15, 2 );
        add_action( 'dokan_product_edit_after_inventory_variants', array( $this, 'load_variations_content' ), 20, 2 );
        add_action( 'dokan_dashboard_wrap_after', array( $this, 'load_variations_js_template' ), 10, 2 );
        add_action( 'dokan_render_new_product_template', array( $this, 'render_new_product_template' ), 10 );
        add_action( 'dokan_new_product_added', array( $this, 'set_product_tags' ), 10, 2 );
        add_action( 'dokan_product_updated', array( $this, 'set_product_tags' ) );
        add_action( 'dokan_new_product_added', array( $this, 'set_product_type' ), 99 );
        add_action( 'dokan_product_updated', array( $this, 'set_product_type' ), 99 );
        add_action( 'dokan_new_product_added', array( $this, 'save_pro_product_data' ), 12, 2 );
        add_action( 'dokan_product_updated', array( $this, 'save_pro_product_data' ), 12, 2 );
        add_action( 'dokan_product_updated', array( $this, 'updated_product_email' ), 20, 2 );
        add_action( 'template_redirect', array( $this, 'handle_duplicate_product' ), 10 );
        add_action( 'dokan_product_dashboard_errors', array( $this, 'display_duplicate_message' ), 10 );
        add_action( 'dokan_product_list_table_after_row', array( $this, 'add_product_inline_edit_form' ), 10, 2 );
        add_action( 'wp_ajax_dokan_product_inline_edit', array( $this, 'product_inline_edit' ) );

        add_filter( 'dokan_product_row_actions', array( $this, 'product_row_action' ), 10, 2 );
        add_filter( 'dokan_update_product_post_data', array( $this, 'change_product_status' ), 10 );
        add_filter( 'dokan_product_types', array( $this, 'set_default_product_types' ), 10 );

        add_action( 'dokan_after_linked_product_fields', array( $this, 'group_product_content' ), 10, 2 );
        add_action( 'dokan_product_edit_after_title', array( $this, 'external_product_content' ), 10, 2 );
        add_filter( 'woocommerce_duplicate_product_exclude_meta', array( $this, 'remove_unwanted_meta' ) );

        add_filter( 'dokan_localized_args', array( $this, 'dokan_pro_localized_args' ) );
        add_filter( 'dokan_react_frontend_localized_args', array( $this, 'add_product_listing_duplicate_args' ) );

        //Prevent Duplicate SKU for multiple save from various vendor
        add_action( 'woocommerce_product_duplicate_before_save', [ $this, 'prevent_duplicate_sku' ], 10 );
        add_filter( 'dokan_post_status', [ $this, 'set_product_status' ], 3, 2 );
        add_filter( 'dokan_post_edit_default_status', [ $this, 'post_edit_default_status' ], 10, 2 );
        if ( version_compare( DOKAN_PLUGIN_VERSION, '3.8.1', '<=' ) ) {
            add_filter( 'dokan_get_new_post_status', [ $this, 'new_product_status' ], 10, 1 );
        } else {
            add_filter( 'dokan_get_default_product_status', [ $this, 'new_product_status' ], 10, 1 );
        }

        add_action( 'dokan_product_quick_edit_updated', 'dokan_trigger_product_create_email', 10, 1 );
    }

    /**
     * Render New Product Template
     *
     * @since 2.4
     *
     * @param  array $query_vars
     *
     * @return void
     */
    public function render_new_product_template( $query_vars ) {
        if ( isset( $query_vars['new-product'] ) ) {
            dokan_get_template_part( 'products/new-product' );
        }
    }

    /**
     * Load Variation Content
     *
     * @since 2.4
     *
     * @param  object $post
     * @param  integer $post_id
     *
     * @return void
     */
    public function load_variations_content( $post, $post_id ) {
        $_has_attribute       = get_post_meta( $post_id, '_has_attribute', true );
        $_create_variations   = get_post_meta( $post_id, '_create_variation', true );
        $product_attributes   = get_post_meta( $post_id, '_product_attributes', true );
        $attribute_taxonomies = wc_get_attribute_taxonomies();

        dokan_get_template_part(
            'products/product-variation',
            '',
            array(
                'pro'                  => true,
                'post_id'              => $post_id,
                '_has_attribute'       => $_has_attribute,
                '_create_variations'   => $_create_variations,
                'product_attributes'   => $product_attributes,
                'attribute_taxonomies' => $attribute_taxonomies,
            )
        );
    }

    /**
     * Load Variation popup content when edit product
     *
     * @since 2.4
     *
     * @param  object $post
     * @param  integer $post_id
     *
     * @return void
     */
    public function load_variations_js_template( $post, $post_id ) {
        dokan_get_template_part(
            'products/edit/tmpl-add-attribute',
            '',
            array(
                'pro' => true,
                'post_id' => $post_id,
            )
        );
    }

    /**
     * Load Shipping and tax content
     *
     * @since 2.4
     *
     * @param  object $post
     * @param  integer $post_id
     *
     * @return void
     */
    public function load_shipping_tax_content( $post, $post_id ) {
        $user_id                 = dokan_get_current_user_id();
        $processing_time         = dokan_get_shipping_processing_times();
        $_required_tax           = get_post_meta( $post_id, '_required_tax', true );
        $_disable_shipping       = ( get_post_meta( $post_id, '_disable_shipping', true ) ) ? get_post_meta( $post_id, '_disable_shipping', true ) : 'no';
        $_additional_price       = get_post_meta( $post_id, '_additional_price', true );
        $_additional_qty         = get_post_meta( $post_id, '_additional_qty', true );
        $_processing_time        = get_post_meta( $post_id, '_dps_processing_time', true );
        $dps_shipping_type_price = get_user_meta( $user_id, '_dps_shipping_type_price', true );
        $dps_additional_qty      = get_user_meta( $user_id, '_dps_additional_qty', true );
        $dps_pt                  = get_user_meta( $user_id, '_dps_pt', true );
        $classes_options         = $this->get_tax_class_option();
        $porduct_shipping_pt     = ( $_processing_time ) ? $_processing_time : $dps_pt;
        $is_shipping_disabled    = false;

        if ( 'sell_digital' === dokan_pro()->digital_product->get_selling_product_type() ) {
            $is_shipping_disabled = true;
        }

        dokan_get_template_part(
            'products/product-shipping-content',
            '',
            array(
                'pro'                     => true,
                'post'                    => $post,
                'post_id'                 => $post_id,
                'user_id'                 => $user_id,
                'processing_time'         => $processing_time,
                '_required_tax'           => $_required_tax,
                '_disable_shipping'       => $_disable_shipping,
                '_additional_price'       => $_additional_price,
                '_additional_qty'         => $_additional_qty,
                '_processing_time'        => $_processing_time,
                'dps_shipping_type_price' => $dps_shipping_type_price,
                'dps_additional_qty'      => $dps_additional_qty,
                'dps_pt'                  => $dps_pt,
                'classes_options'         => $classes_options,
                'porduct_shipping_pt'     => $porduct_shipping_pt,
                'is_shipping_disabled'    => $is_shipping_disabled,
            )
        );
    }

    /**
     * Render linked product content
     *
     * @since 2.6.6
     *
     * @return void
     */
    public function load_linked_product_content( $post, $post_id ) {
        $upsells_ids = get_post_meta( $post_id, '_upsell_ids', true );
        $crosssells_ids = get_post_meta( $post_id, '_crosssell_ids', true );

        dokan_get_template_part(
            'products/linked-product-content',
            '',
            array(
                'pro'            => true,
                'post'           => $post,
                'post_id'        => $post_id,
                'upsells_ids'    => $upsells_ids,
                'crosssells_ids' => $crosssells_ids,
            )
        );
    }

    /**
     * Get taxes options value
     *
     * @since 2.4
     *
     * @return array
     */
    public function get_tax_class_option() {
        if ( class_exists( 'WC_Tax' ) ) {
            $tax_classes = \WC_Tax::get_tax_classes();
        } else {
            $tax_classes = array_filter( array_map( 'trim', explode( "\n", get_option( 'woocommerce_tax_classes' ) ) ) );
        }

        $classes_options     = [];
        $classes_options[''] = __( 'Standard', 'dokan' );

        if ( $tax_classes ) {
            foreach ( $tax_classes as $class ) {
                $classes_options[ sanitize_title( $class ) ] = esc_html( $class );
            }
        }

        return $classes_options;
    }

    /**
     * Save extra product data
     *
     * @since  2.5.3
     *
     * @param  integer $post_id
     *
     * @return void
     */
    public function save_pro_product_data( $post_id, $post_data = [] ) {
        // phpcs:disable WordPress.Security.NonceVerification.Missing
        if ( ! $post_id ) {
            return;
        }
        $post_data = ! empty( $post_data ) ? $post_data : wp_unslash( $_POST );

        $is_virtual   = isset( $post_data['_virtual'] ) ? 'yes' : 'no';
        $product_type = empty( $post_data['product_type'] ) ? 'simple' : $post_data['product_type'];

        // Dimensions
        if ( 'no' === $is_virtual ) {
            if ( isset( $post_data['_weight'] ) ) {
                update_post_meta( $post_id, '_weight', ( '' === $post_data['_weight'] ) ? '' : wc_format_decimal( $post_data['_weight'] ) );
            }

            if ( isset( $post_data['_length'] ) ) {
                update_post_meta( $post_id, '_length', ( '' === $post_data['_length'] ) ? '' : wc_format_decimal( $post_data['_length'] ) );
            }

            if ( isset( $post_data['_width'] ) ) {
                update_post_meta( $post_id, '_width', ( '' === $post_data['_width'] ) ? '' : wc_format_decimal( $post_data['_width'] ) );
            }

            if ( isset( $post_data['_height'] ) ) {
                update_post_meta( $post_id, '_height', ( '' === $post_data['_height'] ) ? '' : wc_format_decimal( $post_data['_height'] ) );
            }
        } else {
            update_post_meta( $post_id, '_weight', '' );
            update_post_meta( $post_id, '_length', '' );
            update_post_meta( $post_id, '_width', '' );
            update_post_meta( $post_id, '_height', '' );
        }

        //Save shipping meta data
        update_post_meta( $post_id, '_disable_shipping', isset( $post_data['_disable_shipping'] ) ? $post_data['_disable_shipping'] : 'no' );

        if ( isset( $post_data['_overwrite_shipping'] ) && $post_data['_overwrite_shipping'] === 'yes' ) {
            update_post_meta( $post_id, '_overwrite_shipping', $post_data['_overwrite_shipping'] );
        } else {
            update_post_meta( $post_id, '_overwrite_shipping', 'no' );
        }

        update_post_meta( $post_id, '_additional_price', isset( $post_data['_additional_price'] ) ? $post_data['_additional_price'] : '' );
        update_post_meta( $post_id, '_additional_qty', isset( $post_data['_additional_qty'] ) ? $post_data['_additional_qty'] : '' );
        update_post_meta( $post_id, '_dps_processing_time', isset( $post_data['_dps_processing_time'] ) ? $post_data['_dps_processing_time'] : '' );

        // Save shipping class
        $product_shipping_class = ( isset( $post_data['product_shipping_class'] ) && $post_data['product_shipping_class'] > 0 && 'external' !== $product_type ) ? absint( $post_data['product_shipping_class'] ) : '';
        wp_set_object_terms( $post_id, $product_shipping_class, 'product_shipping_class' );

        // Cross sells and upsells
        $upsells    = isset( $post_data['upsell_ids'] ) ? array_map( 'intval', $post_data['upsell_ids'] ) : array();
        $crosssells = isset( $post_data['crosssell_ids'] ) ? array_map( 'intval', $post_data['crosssell_ids'] ) : array();

        update_post_meta( $post_id, '_upsell_ids', $upsells );
        update_post_meta( $post_id, '_crosssell_ids', $crosssells );

        // Skip legacy variation saving when the Product Editor module handles
        // variations via REST API (indicated by 'product_editor' key in post data).
        if ( 'variable' === $product_type && ! isset( $post_data['product_editor'] ) ) {
            dokan_save_variations( $post_id );
        }

        // Save external
        if ( 'external' === $product_type ) {
            update_post_meta( $post_id, '_product_url', isset( $post_data['_product_url'] ) ? $post_data['_product_url'] : '' );
            update_post_meta( $post_id, '_button_text', isset( $post_data['_button_text'] ) ? $post_data['_button_text'] : '' );
        }

        if ( 'grouped' === $product_type ) {
            $product = new \WC_Product_Grouped( $post_id );
            $group_product_ids = isset( $post_data['grouped_products'] ) ? array_filter( array_map( 'intval', (array) $post_data['grouped_products'] ) ) : array();
            $product->set_children( $group_product_ids );
            $product->save();
        }
    }

    /**
     * Set new product tags
     *
     * @since 2.8.4
     *
     * @param int   $product_id
     * @param array $posted_data
     *
     * @return void
     */
    public function set_product_tags( $product_id, $posted_data = [] ) {
        if ( empty( $posted_data ) && ! empty( $_POST ) ) {
            $posted_data = $_POST;
        }

        if ( ! isset( $posted_data['product_tag'] ) || ! is_array( $posted_data['product_tag'] ) ) {
            return;
        }

        // Newly added tags will be string typed data
        $tags = array_filter(
            $posted_data['product_tag'],
            function ( $tag ) {
                return ! absint( $tag );
            }
        );

        if ( ! empty( $tags ) ) {
            $tags_ids = array();

            foreach ( $tags as $tag ) {
                $new_tag = wp_insert_term( $tag, 'product_tag' );

                if ( ! is_wp_error( $new_tag ) ) {
                    $tags_ids[] = $new_tag['term_id'];
                }
            }

            if ( ! empty( $tags_ids ) ) {
                wp_set_object_terms( $product_id, $tags_ids, 'product_tag', true );
            }
        }
    }

    /**
     * Added duplicate row action
     *
     * @since 2.6.3
     *
     * @param array $row_action List of row actions for product.
     * @param \WC_Product $post Product object.
     *
     * @return array
     */
    public function product_row_action( $row_action, $post ) {
        if ( empty( $post->ID ) ) {
            return $row_action;
        }

        if ( current_user_can( 'dokan_edit_product' ) ) {
            $row_action['quick-edit'] = array(
                'title' => __( 'Quick Edit', 'dokan' ),
                'url'   => '#quick-edit',
                'class' => 'item-inline-edit editline',
                'other' => 'data-product-id="' . $post->ID . '"',
            );
        }

        $can_duplicate_product = apply_filters( 'dokan_can_duplicate_product', current_user_can( 'dokan_duplicate_product' ) );
        $vendor_can_duplicate_product = dokan_get_option( 'vendor_duplicate_product', 'dokan_selling', 'on' );

        if ( $can_duplicate_product && 'on' === $vendor_can_duplicate_product ) {
            $row_action['duplicate'] = array(
                'title' => __( 'Duplicate', 'dokan' ),
                'url'   => wp_nonce_url( add_query_arg( array( 'action' => 'dokan-duplicate-product', 'product_id' => $post->ID, ), dokan_get_navigation_url('products') ), 'dokan-duplicate-product' ), // phpcs:ignore
                'class' => 'duplicate',
            );
        }

        return $row_action;
    }

    /**
     * Handle duplicate product action
     *
     * @since 2.6.3
     *
     * @return void
     */
    public function handle_duplicate_product() {
        if ( ! isset( $_GET['action'] ) || 'dokan-duplicate-product' !== $_GET['action'] ) {
            return;
        }

        $product_id = isset( $_GET['product_id'] ) ? (int) $_GET['product_id'] : 0;
        $redirect_url = dokan_get_navigation_url( 'products' );

        if ( ! $product_id ) {
            wp_safe_redirect( add_query_arg( array( 'message' => 'error' ), $redirect_url ) );
            return;
        }

        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'dokan-duplicate-product' ) ) {
            wp_safe_redirect( add_query_arg( array( 'message' => 'error' ), $redirect_url ) );
            return;
        }

        $clone_product = $this->duplicate_product( $product_id );

        if ( is_wp_error( $clone_product ) ) {
            wp_safe_redirect( add_query_arg( array( 'message' => 'error' ), $redirect_url ) );
            return;
        }

        $clone_product_id = $clone_product->get_id();

        if ( isset( $_GET['product_type'] ) && 'booking' === $_GET['product_type'] ) {
            $redirect_url = dokan_get_navigation_url( 'booking' );
        }

        wp_safe_redirect(
            add_query_arg(
                array( 'message' => 'product_duplicated' ),
                apply_filters( 'dokan_redirect_after_product_duplicating', $redirect_url, $product_id, $clone_product_id )
            )
        );
        exit;
    }

    /**
     * Duplicates a product.
     *
     * @since 3.7.14
     *
     * @param int $product_id The ID of the product to be duplicated
     *
     * @return \WC_Product|WP_Error
     */
    public function duplicate_product( $product_id ) {
        $no_permission_error = new WP_Error(
            'dokan-no-permission',
            __( 'You do not have permission to perform this action', 'dokan' )
        );

        $user_id = dokan_get_current_user_id();

        if ( empty( $user_id ) || ! dokan_is_user_seller( $user_id ) ) {
            return $no_permission_error;
        }

        if ( dokan_get_option( 'vendor_duplicate_product', 'dokan_selling', 'on' ) === 'off' ) {
            return $no_permission_error;
        }

        if ( ! apply_filters( 'dokan_vendor_can_duplicate_product', true ) ) {
            return $no_permission_error;
        }

        $product = wc_get_product( $product_id );

        if ( ! $product ) {
            return new WP_Error(
                'dokan-no-such-product',
                __( 'No such product found!', 'dokan' )
            );
        }

        if ( ! dokan_is_product_author( $product_id ) ) {
            return $no_permission_error;
        }

        $wc_duplicator = new \WC_Admin_Duplicate_Product();
        $clone_product = $wc_duplicator->product_duplicate( $product );
        $clone_product->update_meta_data( '_dokan_new_product_email_sent', 'no' );

        // Make the newly created product status to "draft", before saving.
        $clone_product->set_status( 'draft' );
        $clone_product->save();

        do_action( 'dokan_product_duplicate_after_save', $clone_product, $product );

        return $clone_product;
    }

    /**
     * Show duplicate success message
     *
     * @since 2.6.3
     *
     * @return void
     */
    public function display_duplicate_message( $type ) {
        if ( 'product_duplicated' === $type ) {
            dokan_get_template_part(
                'global/dokan-success',
                '',
                array(
                    'deleted' => true,
                    'message' => __( 'Product succesfully duplicated', 'dokan' ),
                )
            );
        }
    }

    /**
     * Set product type
     *
     * @since 2.5.3
     *
     * @param integer $post_id
     */
    public function set_product_type( $post_id ) {
        $post_data = wp_unslash( $_POST );
        if ( isset( $post_data['product_type'] ) ) {
            wp_set_object_terms( $post_id, $post_data['product_type'], 'product_type' );
        }
    }

    /**
     * Set Additional product Post Data
     *
     * @since 2.6.3
     *
     * @param array $data Product post data
     *
     * @return array
     */
    public function change_product_status( $data ) {
        $seller_id = dokan_get_current_user_id();

        if ( empty( $seller_id ) && ! empty( $data['ID'] ) ) {
            $seller_id = dokan_get_vendor_by_product( $data['ID'], true );
        }

        if ( dokan_is_seller_trusted( $seller_id ) ) {
            return $data;
        }

        $product = wc_get_product( $data['ID'] );
        if ( ! $product ) {
            return $data;
        }

        //update product status to pending-review if set by admin
        if ( 'publish' === $data['post_status'] ) {
            $data['post_status'] = dokan_get_default_product_status( $seller_id );
        }

        return $data;
    }

    /**
     * Set default product types
     *
     * @since 2.6
     *
     * @param array $product_types
     *
     * @return array
     */
    public function set_default_product_types( $product_types ) {
        $product_types = array(
            'simple'   => __( 'Simple', 'dokan' ),
            'variable' => __( 'Variable', 'dokan' ),
            'external' => __( 'External/Affiliate product', 'dokan' ),
        );
        $product_types['grouped'] = __( 'Group Product', 'dokan' );
        return $product_types;
    }

    /**
     * Send email to admin once a product is updated
     *
     * @since 2.6.5
     *
     * @param int $product_id
     * @param array $post_data
     *
     * @return void
     */
    public function updated_product_email( $product_id, $post_data ) {
        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return;
        }

        // check if product status is pending
        if ( 'pending' !== $product->get_status() ) {
            return;
        }

        // do not send multiple email notification for pending product
        if ( isset( $post_data['post_status'] ) && $post_data['post_status'] === $product->get_status() ) {
            return;
        }

        // get seller by product
        $seller = dokan_get_vendor_by_product( $product );
        // get category
        $category = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) );

        do_action( 'dokan_edited_product_pending_notification', $product, $seller, $category );
    }

    /**
     * Group product content
     *
     * @since 2.6.6
     *
     * @return void
     */
    public function group_product_content( $post, $post_id ) {
        dokan_get_template_part(
            'products/group-product',
            '',
            array(
                'pro'     => true,
                'post'    => $post,
                'post_id' => $post_id,
                'product' => wc_get_product( $post_id ),
            )
        );
    }

    /**
     * External product content
     *
     * @since 3.2.1
     *
     * @return void
     */
    public function external_product_content( $post, $post_id ) {
        $product_types = apply_filters( 'dokan_product_types', 'simple' );

        if ( ! array_key_exists( 'external', $product_types ) ) {
            return;
        }

        dokan_get_template_part(
            'products/external-product',
            '',
            array(
                'pro'     => true,
                'post'    => $post,
                'post_id' => $post_id,
                'product' => wc_get_product( $post_id ),
            )
        );
    }

    /**
     * Remove unwanted meta_keys while duplicating product
     *
     * @param  array $meta_keys
     *
     * @since 2.7.6
     *
     * @return array $meta_keys
     */
    public function remove_unwanted_meta( $meta_keys ) {
        $meta_keys[] = 'pageview';

        return $meta_keys;
    }

    /**
     * Add Dokan Pro localized vars
     *
     * @since 2.8.4
     *
     * @param array $args
     *
     * @return array
     */
    public function dokan_pro_localized_args( $args ) {
        $dokan_pro_args = array(
            'product_vendors_can_create_tags' => dokan_get_option( 'product_vendors_can_create_tags', 'dokan_selling' ),
            'product_inline_edit_nonce'       => wp_create_nonce( 'product-inline-edit' ),
            'is_vendor_enabled'               => dokan_is_seller_enabled( dokan_get_current_user_id() ),
            'not_enable_message'              => __( 'Error! Your account is not enabled for selling, please contact the admin', 'dokan' ),
        );

        return array_merge( $args, $dokan_pro_args );
    }

    /**
     * Whether the current vendor is allowed to duplicate a product.
     *
     * Combines the capability check, the admin-side "vendor_duplicate_product"
     * toggle, and the dokan_vendor_can_duplicate_product filter so every
     * listing page (product, auction, booking) reads from one source.
     *
     * @since 5.0.0
     *
     * @return bool
     */
    public static function can_vendor_duplicate_product() {
        return current_user_can( 'dokan_duplicate_product' )
            && dokan_get_option( 'vendor_duplicate_product', 'dokan_selling', 'on' ) === 'on'
            && apply_filters( 'dokan_vendor_can_duplicate_product', true );
    }

    /**
     * Add duplicate product permission to the React frontend localized args
     * for both the product and auction listing pages.
     *
     * @since 5.0.0
     *
     * @param array $args Existing localized args.
     *
     * @return array
     */
    public function add_product_listing_duplicate_args( $args ) {
        if ( ! is_user_logged_in() || ! dokan_is_seller_dashboard() ) {
            return $args;
        }

        $can_duplicate = self::can_vendor_duplicate_product();

        $args['product_listing'] = array_merge(
            $args['product_listing'] ?? [],
            [
                'can_duplicate_product' => $can_duplicate,
            ]
        );

        $args['auction_listing'] = array_merge(
            $args['auction_listing'] ?? [],
            [
                'can_duplicate_product' => $can_duplicate,
            ]
        );

        return $args;
    }

    /**
     * Inline edit form
     *
     * @since 2.9.0
     *
     * @param WC_Product $product
     * @param WP_Post    $post
     *
     * @return void
     */
    public function add_product_inline_edit_form( $product, $post ) {
        $options = $this->get_inline_edit_options();

        $wp_cats = get_the_terms( $post->ID, 'product_cat' );
        $cats    = ! empty( $wp_cats ) && ! is_wp_error( $wp_cats ) ? wp_list_pluck( $wp_cats, 'term_id' ) : '';

        if ( $options['using_single_category_style'] && ! empty( $cats ) ) {
            $cats = array_pop( $cats );
        }

        $tags = get_the_terms( $post->ID, 'product_tag' );

        $args = array(
            'pro'     => true,
            'id'      => 'dokan-product-list-table',
            'options' => $this->get_inline_edit_options(),

            // product informations
            'product_id'        => $product->get_id(),
            'post_title'        => $product->get_title(),
            'product_cat'       => (array) $cats,
            'product_tag'       => $tags,
            'product_type'      => $product->get_type(),
            'is_virtual'        => $product->is_virtual(),
            'reviews_allowed'   => $product->get_reviews_allowed(),
            'post_status'       => $post->post_status,
            'sku'               => $product->get_sku(),
            '_regular_price'    => $product->get_regular_price(),
            '_sale_price'       => $product->get_sale_price(),
            'weight'            => $product->get_weight(),
            'length'            => $product->get_length(),
            'width'             => $product->get_width(),
            'height'            => $product->get_height(),
            'shipping_class_id' => $product->get_shipping_class_id(),
            '_visibility'       => $product->get_catalog_visibility(),
            'manage_stock'      => $product->get_manage_stock(),
            'stock_quantity'    => $product->get_stock_quantity(),
            'stock_status'      => $product->get_stock_status(),
            'backorders'        => $product->get_backorders(),
            'selling_type'      => dokan_pro()->digital_product->get_selling_product_type(),
        );

        $args['options']['post_statuses'] = dokan_get_available_post_status( $product->get_id() );

        dokan_get_template_part( 'products/edit/product-list-table-inline-edit-form', '', $args );
    }

    /**
     * Reusable inline edit options
     *
     * @since 2.9.0
     *
     * @return array
     */
    private function get_inline_edit_options() {
        if ( ! empty( $this->inline_edit_options ) ) {
            return $this->inline_edit_options;
        }

        $args = apply_filters(
            'dokan_product_cat_dropdown_args', [
                'taxonomy'   => 'product_cat',
                'number'     => false,
                'orderby'    => 'name',
                'order'      => 'asc',
                'hide_empty' => false,
            ]
        );

        $categories = get_terms( $args );

        $using_single_category_style = ( 'single' === dokan_get_option( 'product_category_style', 'dokan_selling', 'single' ) );

        $args = array(
            'taxonomy'   => 'product_tag',
            'number'     => 0,
            'orderby'    => 'name',
            'order'      => 'asc',
            'hide_empty' => false,
        );

        $tags = get_terms( $args );

        $this->inline_edit_options = array(
            'using_single_category_style' => $using_single_category_style,
            'categories' => $categories,
            'tags'  => $tags,
            'is_sku_enabled' => wc_product_sku_enabled(),
            'is_weight_enabled' => wc_product_weight_enabled(),
            'is_dimensions_enabled' => wc_product_dimensions_enabled(),
            'shipping_classes' => WC()->shipping->get_shipping_classes(),
            'visibilities' => dokan_get_product_visibility_options(),
            'can_manage_stock' => get_option( 'woocommerce_manage_stock' ),
            'stock_statuses' => array(
                'instock'    => __( 'In Stock', 'dokan' ),
                'outofstock' => __( 'Out of Stock', 'dokan' ),
            ),
            'backorder_options' => array(
                'no'     => __( 'Do not allow', 'dokan' ),
                'notify' => __( 'Allow but notify customer', 'dokan' ),
                'yes'    => __( 'Allow', 'dokan' ),
            ),
        );

        return $this->inline_edit_options;
    }

    /**
     * Save quick edit product data
     *
     * @since 2.9.0
     *
     * @return void|WP_Error
     */
    public function product_inline_edit() {
        if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['security'] ) ), 'product-inline-edit' ) ) {
            wp_send_json_error( __( 'Invalid nonce', 'dokan' ) );
        }

        try {
            if ( empty( $_POST['data'] ) ) {
                throw new \RuntimeException( esc_html__( 'No data found', 'dokan' ) );
            }

            /**
             * Filter the product data before saving on inline edit
             *
             * @since 3.11.3
             *
             * @param array $data The product data to be saved
             */
            $product_data = apply_filters( 'dokan_update_product_post_data', wc_clean( wp_unslash( $_POST['data'] ) ) );

            if ( empty( $product_data['ID'] ) ) {
                throw new \RuntimeException( esc_html__( 'Product ID field is required', 'dokan' ) );
            }

            if ( empty( $product_data['chosen_product_cat'] ) ) {
                throw new \RuntimeException( esc_html__( 'Please select a category', 'dokan' ) );
            }

            if ( isset( $product_data['chosen_product_cat'] ) ) {
                $product_data['chosen_product_cat'] = array_filter( (array) $product_data['chosen_product_cat'] );
            }

            $is_single_cat = Helper::product_category_selection_is_single();
            $chosen_cat    = $is_single_cat ? array( absint( reset( $product_data['chosen_product_cat'] ) ) ) : $product_data['chosen_product_cat'];

            if ( $is_single_cat && $chosen_cat[0] === 0 ) {
                throw new \RuntimeException( esc_html__( 'Please select a category', 'dokan' ) );
            }

            if ( ! empty( $product_data['sku'] ) && ! wc_product_has_unique_sku( $product_data['ID'], $product_data['sku'] ) ) {
                throw new \RuntimeException( esc_html__( 'Invalid or duplicated SKU.', 'dokan' ) );
            }

            $data = array(
                'id'                 => $product_data['ID'],
                'name'               => $product_data['post_title'],
                'type'               => $product_data['product_type'] ?? '',
                'reviews_allowed'    => $product_data['reviews_allowed'] ?? '',
                'status'             => $product_data['post_status'] ?? '',
                'sku'                => $product_data['sku'] ?? '',
                'regular_price'      => $product_data['_regular_price'] ?? '',
                'sale_price'         => $product_data['_sale_price'] ?? '',
                'dimensions'         => array(
                    'width'  => $product_data['width'] ?? '',
                    'length' => $product_data['length'] ?? '',
                    'height' => $product_data['height'] ?? '',
                ),
                'weight'             => $product_data['weight'] ?? '',
                'shipping_class'     => $product_data['shipping_class_id'] ?? '',
                'catalog_visibility' => $product_data['_visibility'] ?? '',
                'manage_stock'       => $product_data['manage_stock'] ?? '',
                'stock_quantity'     => $product_data['stock_quantity'] ?? '',
                'stock_status'       => $product_data['stock_status'] ?? '',
                'backorders'         => $product_data['backorders'] ?? '',
            );

            $data['categories'] = [];

            if ( isset( $product_data['product_tag'] ) ) {
                /**
                 * Filter for vendor product tags select maximum length.
                 *
                 * @since 3.3.7
                 *
                 * @param int $maximum_tags_select_length Maximum tags select length, default -1.
                 */
                $maximum_tags_select_length = apply_filters( 'dokan_product_tags_select_max_length', -1 );

                // Setting limitation for how many product tags that vendor can input.
                if ( $maximum_tags_select_length !== -1 && count( $product_data['product_tag'] ) > $maximum_tags_select_length ) {
                    /* translators: %s: maximum tag length */
                    throw new \RuntimeException( sprintf( __( 'You can only select %s tags', 'dokan' ), number_format_i18n( $maximum_tags_select_length ) ) );
                }

                $tags = [];
                foreach ( (array) $product_data['product_tag'] as $tag ) {
                    if ( is_numeric( $tag ) ) {
                        $tags[] = $tag;
                        continue;
                    }

                    // Insert new tag.
                    $new_tag = wp_insert_term( $tag, 'product_tag' );
                    if ( ! is_wp_error( $new_tag ) ) {
                        $tags[] = $new_tag['term_id'];
                    }
                }

                $data['tags'] = $tags;
            }

            /**
             * Filter the product data before saving on inline edit
             *
             * @since 3.11.3
             *
             * @param array $data The product data to be saved
             */
            $data    = apply_filters( 'dokan_update_product_quick_edit_data', $data );
            $product = dokan()->product->update( $data );

            if ( $product instanceof \WP_Error ) {
                throw new \RuntimeException( esc_html( $product->get_error_message() ) );
            }

            if ( ! $product instanceof \WC_Product ) {
                throw new \RuntimeException( esc_html__( 'Error updating product data', 'dokan' ) );
            }

            Helper::set_object_terms_from_chosen_categories( $product->get_id(), $chosen_cat );

            /**
             * Run when product data update in quick edit.
             *
             * @param int $product_id Product id.
             * @param array $data Data of the updated product.
             *
             * @since 3.2.1
             */
            do_action( 'dokan_product_quick_edit_updated', $product->get_id(), $data );

            ob_start();

            $post = get_post( $product->get_id() );

            dokan_get_template_part(
                'products/products-listing-row', '', array(
					'post' => $post,
					'product' => $product,
					'tr_class' => ( $post->post_status === 'pending' ) ? 'danger' : '',
					'row_actions' => dokan_product_get_row_action( $post ),
                )
            );

            $html = ob_get_clean();

            wp_send_json_success(
                array(
					'message' => esc_html__( 'Product updated successfully', 'dokan' ),
					'row' => $html,
                )
            );
        } catch ( \Exception $e ) {
            wp_send_json_error( $e->getMessage(), 422 );
        }
    }

    /**
     * Prevent duplicate sku when multiple vendor add same product
     *
     * @param $duplicate
     *
     * @return void
     */
    public function prevent_duplicate_sku( $duplicate ) {
        $sku        = $duplicate->get_sku( 'edit' );
        $unique_sku = $this->get_unique_sku( $sku );
        $duplicate->set_sku( $unique_sku );
    }

    /**
     * Check recursively if sku exist
     *
     * @param $sku
     *
     * @return mixed
     */
    public function get_unique_sku( $sku ) {
        $unique_sku = $sku;

        // If SKU is already empty, we don't need to create a new SKU
        if ( empty( $unique_sku ) ) {
            return $unique_sku;
        }

        global $wpdb;
        $result = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}postmeta WHERE meta_key='_sku' AND meta_value =%s ", $sku ) );
        if ( $result >= 1 ) {
            if ( strpos( $sku, '-' ) !== false ) {
                $arr                      = explode( '-', $sku );
                $arr[ count( $arr ) - 1 ] = ( (int) $arr[ count( $arr ) - 1 ] ) + 1;
                $unique_sku               = implode( '-', $arr );
            } else {
                $unique_sku = $sku . '-1';
            }

            return $this->get_unique_sku( $unique_sku );
        } else {
            return $unique_sku;
        }
    }

    /**
     * Set product edit status
     *
     * @since 3.8.3
     *
     * @param array $all_statuses
     * @param int $product_id
     *
     * @return array
     */
    public function set_product_status( $all_statuses, $product_id ) {
        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return $all_statuses;
        }

        $seller_id          = dokan_get_vendor_by_product( $product_id, true );
        $new_product_status = dokan_get_new_post_status( $seller_id );
        if ( 'publish' === $new_product_status ) {
            $all_statuses['publish'] = dokan_get_post_status( 'publish' );
            if ( 'pending' !== $product->get_status() ) {
                unset( $all_statuses['pending'] );
            }
        }

        return $all_statuses;
    }

    /**
     * Set default product status for new product
     *
     * @since 3.8.3
     *
     * @param $current_status
     * @param $product
     *
     * @return mixed|string|void
     */
    public function post_edit_default_status( $current_status, $product ) {
        if ( 'auto-draft' !== $product->get_status() ) {
            return $current_status;
        }

        $seller_id          = dokan_get_vendor_by_product( $product->get_id(), true );
        $new_product_status = dokan_get_new_post_status( $seller_id );
        if ( 'publish' === $new_product_status ) {
            $current_status = 'publish';
        } elseif ( 'pending' === $new_product_status ) {
            $current_status = 'pending';
        } else {
            $current_status = 'draft';
        }

        return $current_status;
    }

    /**
     * Set new product status based on admin settings
     *
     * @since 3.8.3
     *
     * @param string $status
     *
     * @return string
     */
    public function new_product_status( $status ) {
        if ( 'publish' === $status ) {
            return $status;
        }

        return dokan_get_option( 'product_status', 'dokan_selling', 'pending' );
    }
}
