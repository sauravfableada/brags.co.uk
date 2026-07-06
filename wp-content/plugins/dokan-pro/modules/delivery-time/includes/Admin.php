<?php

namespace WeDevs\DokanPro\Modules\DeliveryTime;

use WeDevs\DokanPro\Modules\DeliveryTime\StorePickup\Helper as StorePickupHelper;

/**
 * Class Admin
 *
 * @since 3.3.0
 *
 * @package WeDevs\DokanPro\Modules\DeliveryTime
 */
class Admin {

    /**
     * Delivery time admin constructor
     *
     * @since 3.3.0
     */
    public function __construct() {
        // Hooks
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ], 20 );
        add_action( 'add_meta_boxes', [ $this, 'add_admin_delivery_time_meta_box' ], 10, 2 );
        add_action( 'woocommerce_process_shop_order_meta', [ $this, 'save_admin_delivery_time_meta_box' ], 10, 1 );
        // Active days for delivery
        add_action( 'wp_ajax_dokan_get_delivery_days', [ $this, 'get_vendor_delivery_days' ] );
        add_filter( 'dokan_get_settings_values', [ $this, 'filter_delivery_time_settings_values' ], 10, 2 );
    }

    /**
     * Enqueue scripts
     *
     * @since 3.3.0
     *
     * @return void
     */
    public function enqueue_scripts( $hook ) {
        wp_enqueue_script( 'dokan-delivery-time-admin-script' );
        if ( 'toplevel_page_dokan' !== $hook ) {
            return;
        }

        wp_enqueue_script( 'dokan-timepicker' );
        wp_enqueue_script( 'dokan-admin-delivery-time' );

        wp_enqueue_style( 'dokan-timepicker' );
        wp_enqueue_style( 'dokan-admin-delivery-time' );
    }

    /**
     * Adds admin delivery meta box to WC order details page
     *
     * @since 3.3.0
     *
     * @param string   $post_type
     * @param \WP_Post $post
     *
     * @return void
     */
    public function add_admin_delivery_time_meta_box( $post_type, $post ) {
        $screen = dokan_pro_is_hpos_enabled()
            ? wc_get_page_screen_id( 'shop-order' )
            : 'shop_order';

        if ( $screen !== $post_type ) {
            return;
        }

        $order_id = $post instanceof \WC_Abstract_Order ? $post->get_id() : $post->ID;

        $order = dokan()->order->get( $order_id );
        if ( empty( $order ) ) {
            return;
        }

        // Apply a filter to determine whether the delivery box for the admin should be rendered.
        if ( ! apply_filters( 'dokan_delivery_time_should_render_delivery_box', true, $order ) ) {
            return;
        }

        if ( $order->get_meta( 'has_sub_order' ) && $order_id !== $order->get_parent_id() ) {
            return;
        }

        // get vendor id from order
        $vendor_id = dokan_get_seller_id_by_order( $order_id );
        // get vendor delivery time settings
        $vendor_delivery_options = Helper::get_delivery_time_settings( $vendor_id );
        $is_store_pickup_active  = StorePickupHelper::is_store_pickup_location_active( $vendor_id );
        $is_delivery_time_active = isset( $vendor_delivery_options['delivery_support'] ) && 'on' === $vendor_delivery_options['delivery_support'];

        if ( ! $is_delivery_time_active && ! $is_store_pickup_active ) {
            return;
        }

        $delivery_info  = Helper::get_order_delivery_info( $vendor_id, $order_id );
        $meta_box_title = ! empty( $delivery_info ) && 'store-pickup' === $delivery_info->delivery_type ? __( 'Dokan pickup time', 'dokan' ) : __( 'Dokan delivery time', 'dokan' );
        add_meta_box(
            'dokan_delivery_time_fields',
            $meta_box_title,
            [ $this, 'render_delivery_time_meta_box' ],
            $screen,
            'side',
            'core'
        );
    }

    /**
     * Load dokan admin template
     *
     * @since 3.3.0
     *
     * @param \WP_Post $post
     *
     * @return void
     */
    public function render_delivery_time_meta_box( $post_object ) {
        $order = ( $post_object instanceof \WP_Post ) ? wc_get_order( $post_object->ID ) : $post_object;
        if ( empty( $order ) ) {
            return;
        }

        $order_id            = $order->get_id();
        $vendor_id           = dokan_get_seller_id_by_order( $order_id );
        $order_delivery_info = Helper::get_order_delivery_info( $vendor_id, $order_id );
        // get vendor delivery time settings
        $vendor_delivery_options = Helper::get_delivery_time_settings( $vendor_id );

        $vendor_selected_delivery_date = $order->get_meta( 'dokan_delivery_time_date' );
        $vendor_selected_delivery_slot = $order->get_meta( 'dokan_delivery_time_slot' );
        $store_location                = $order->get_meta( 'dokan_store_pickup_location' );

        $vendor_info = [];

        $vendor_info['vendor_id']                     = $vendor_id;
        $vendor_info['vendor_selected_delivery_date'] = '-';
        $vendor_info['vendor_selected_delivery_slot'] = '-';
        $vendor_info['vendor_delivery_slots']         = [];
        $vendor_info['vendor_delivery_options']       = $vendor_delivery_options;

        if ( ! empty( $vendor_selected_delivery_date ) && ! empty( $vendor_selected_delivery_slot ) ) {
            $current_date = dokan_current_datetime();
            $current_date = $current_date->modify( $vendor_selected_delivery_date );
            $day          = strtolower( trim( $current_date->format( 'l' ) ) );

            $vendor_order_per_slot   = (int) isset( $vendor_delivery_options['order_per_slot'][ $day ] ) ? $vendor_delivery_options['order_per_slot'][ $day ] : -1;
            $vendor_delivery_slots   = Helper::get_available_delivery_slots_by_date( $vendor_id, $vendor_order_per_slot, $vendor_selected_delivery_date );

            $vendor_info['vendor_selected_delivery_date'] = $vendor_selected_delivery_date;
            $vendor_info['vendor_selected_delivery_slot'] = $vendor_selected_delivery_slot;
            $vendor_info['store_location']                = $store_location;
            $vendor_info['vendor_delivery_slots']         = $vendor_delivery_slots;
        }

        dokan_get_template_part(
            'admin/meta-box', '', [
                'is_delivery_time' => true,
                'vendor_info'      => $vendor_info,
                'delivery_type'    => ! empty( $order_delivery_info ) ? $order_delivery_info->delivery_type : '',
            ]
        );
    }

    /**
     * Saves admin delivery time meta box args
     *
     * @since 3.3.0
     *
     * @param int $order_id
     *
     * @return void
     */
    public function save_admin_delivery_time_meta_box( $order_id ) {
        if ( ! isset( $_POST['dokan_delivery_admin_time_box_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dokan_delivery_admin_time_box_nonce'] ) ), 'dokan_delivery_admin_time_box_action' ) ) {
            return;
        }

        if ( 0 === $order_id ) {
            return;
        }

        $delivery_date                              = isset( $_POST['dokan_delivery_date'] ) ? wc_clean( wp_unslash( $_POST['dokan_delivery_date'] ) ) : '';
        $delivery_time_slot                         = isset( $_POST['dokan_delivery_time_slot'] ) ? wc_clean( wp_unslash( $_POST['dokan_delivery_time_slot'] ) ) : '';
        $vendor_selected_current_delivery_date_slot = isset( $_POST['vendor_selected_current_delivery_date_slot'] ) ? wc_clean( wp_unslash( $_POST['vendor_selected_current_delivery_date_slot'] ) ) : '-';
        $order_delivery_type                        = isset( $_POST['dokan_delivery_type_pickup'] ) ? 'store-pickup' : 'delivery';
        $vendor_id                                  = (int) dokan_get_seller_id_by_order( $order_id );
        $prev_delivery_info                         = Helper::get_order_delivery_info( $vendor_id, $order_id );
        $location_data                              = isset( $_POST['dokan_store_pickup_location'] ) ? sanitize_text_field( wp_unslash( $_POST['dokan_store_pickup_location'] ) ) : '';

        $data = [
            'order_id'                                   => $order_id,
            'delivery_date'                              => $delivery_date,
            'prev_delivery_info'                         => $prev_delivery_info,
            'delivery_time_slot'                         => $delivery_time_slot,
            'store_pickup_location'                      => StorePickupHelper::get_selected_order_pickup_location( $vendor_id, $location_data ),
            'selected_delivery_type'                     => $order_delivery_type,
            'vendor_selected_current_delivery_date_slot' => $vendor_selected_current_delivery_date_slot,
        ];

        if ( Helper::is_delivery_data_updated( $vendor_id, $order_id, $data ) ) {
            /**
             * After admin update delivery data then trigger.
             *
             * @since 3.7.8
             *
             * @param int   $vendor_id
             * @param array $data
             */
            do_action( 'dokan_after_admin_update_order_delivery_info', $vendor_id, $data );

            Helper::update_delivery_time_date_slot( $data );
        }
    }

    /**
     * Sends json response for available and disabled weekdays for a specific vendor
     *
     * @since 3.9.4
     *
     * @return void
     */
    public function get_vendor_delivery_days() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nonce'] ) ), 'dokan_delivery_time' ) ) {
            wp_send_json_error( __( 'Invalid nonce', 'dokan' ) );
        }

        $vendor_id = ! empty( $_POST['vendor_id'] ) ? absint( $_POST['vendor_id'] ) : 0;
        $date = ! empty( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';

        // Vendor & Admin Permission check
        if ( ! is_a( dokan_get_vendor( $vendor_id ), 'Vendor' ) && ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( __( 'Invalid vendor', 'dokan' ) );
        }

        $weekdays = dokan_get_translated_days();
        $delivery_slot_settings = Helper::get_delivery_slot_settings( $vendor_id, $date );
        $available_weekdays = array_keys( $delivery_slot_settings );
        $disabled_weekdays = [];
        foreach ( $weekdays as $day_key => $day_string ) {
            if ( ! in_array( $day_key, $available_weekdays, true ) ) {
                $disabled_weekdays[] = $day_key;
            }
        }

        wp_send_json_success(
            [
				'available_weekdays' => $available_weekdays,
				'disabled_weekdays'  => $disabled_weekdays,
                'default_date'       => dokan_current_datetime()->format( wc_date_format() ),
			], 201
        );
    }

    /**
     * Filters delivery time settings values for a preorder_date section.
     *
     * @since 4.1.4
     *
     * @param array  $settings   Associative array of settings values.
     * @param string $section_id Identifier for the settings section.
     *
     * @return array Modified settings values.
     */
    public function filter_delivery_time_settings_values( $settings, $section_id ) {
        if ( 'dokan_delivery_time' !== $section_id ) {
            return $settings;
        }

        $settings['preorder_date'] = Helper::get_mepped_preorder( $settings );

        return $settings;
    }
}
