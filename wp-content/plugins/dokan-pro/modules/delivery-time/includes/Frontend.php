<?php

namespace WeDevs\DokanPro\Modules\DeliveryTime;

use WC_Order;
use WeDevs\DokanPro\Modules\DeliveryTime\Blocks\CartCheckoutBlockSupport\BlockSupport;
use WeDevs\DokanPro\Modules\DeliveryTime\StorePickup\Helper as StorePickupHelper;

/**
 * Class Frontend
 *
 * @since 3.3.0
 *
 * @package WeDevs\DokanPro\Modules\DeliveryTime
 */
class Frontend {

    /**
     * Delivery time Frontend constructor
     *
     * @since 3.3.0
     */
    public function __construct() {
        // Hooks
        add_action( 'woocommerce_review_order_before_payment', [ $this, 'render_delivery_time_template' ], 10 );
        add_action( 'wp_enqueue_scripts', [ $this, 'load_scripts' ], 20 );
        add_action( 'dokan_checkout_update_order_meta', [ $this, 'save_delivery_time_args' ], 20, 2 );
        add_action( 'woocommerce_order_details_before_order_table_items', [ $this, 'render_delivery_time_wc_order_details' ], 20, 1 );

        // Specific day delivery slots
        add_action( 'wp_ajax_nopriv_dokan_get_delivery_time_slot', [ $this, 'get_vendor_delivery_time_slot' ] );
        add_action( 'wp_ajax_dokan_get_delivery_time_slot', [ $this, 'get_vendor_delivery_time_slot' ] );
        add_action( 'woocommerce_after_checkout_validation', [ $this, 'validate_delivery_time_slot_args' ], 20, 2 );

        add_filter( 'dokan_localized_args', [ $this, 'add_i18n_date_format_localized_variable' ] );

	    // Add Delivery time cart checkout block support.
        if ( version_compare( wc()->version, '8.4.0', '>=' ) ) {
	        new BlockSupport();
        }
	}

    /**
     * Renders Delivery time box to checkout page
     *
     * @since 3.3.0
     *
     * @return void
     */
    public function render_delivery_time_template() {
        $vendor_infos = Helper::get_vendor_delivery_time_info();

        if ( empty( $vendor_infos ) ) {
            return;
        }

        dokan_get_template_part(
            'delivery-time-box', '', [
                'is_delivery_time' => true,
                'vendor_infos'     => $vendor_infos,
            ]
        );
    }

    /**
     * Loads scripts
     *
     * @since 3.3.0
     *
     * @return void
     */
    public function load_scripts() {
        if ( is_checkout() ) {
            wp_enqueue_script( 'dokan-delivery-time-flatpickr-script' );
            wp_enqueue_script( 'dokan-delivery-time-main-script' );

            wp_enqueue_style( 'dokan-delivery-time-flatpickr-style' );
            wp_enqueue_style( 'dokan-delivery-time-vendor-style' );
        }

        if ( is_order_received_page() || is_view_order_page() ) {
            wp_enqueue_style( 'dokan-delivery-time-vendor-style' );
        }

        $this->add_inline_scripts(); // Load inline data for delivery scripts.
    }

    /**
     * Add vendor delivery time information as an inline script.
     *
     * @since 3.12.2
     *
     * @return void
     */
    protected function add_inline_scripts() {
        if ( ! is_checkout() ) { // Only proceed if the current page is the checkout page.
            return;
        }

        // Collect vendor delivery time infos & return if empty.
        $vendor_infos = Helper::get_vendor_delivery_time_info();
        if ( ! $vendor_infos ) {
            return;
        }

        $json_infos = wp_json_encode(
            [
				'vendor_data' => $vendor_infos,
				'default_date' => dokan_current_datetime()->format( 'Y-m-d' ),
			]
        );
        wp_add_inline_script( 'dokan-delivery-time-main-script', "var dokan_delivery_time_infos = {$json_infos}", 'before' );
    }

    /**
     * Get delivery time data.
     *
     * @since 3.15.0
     *
     * @param \WC_Order $order
     *
     * @return array|mixed|string
     */
    private function has_delivery_time_to_save( WC_Order $order ) {
        $data = [];
        $parent_order_id = $order->get_parent_id();

        /**
         * @see https://github.com/weDevsOfficial/dokan-pro/issues/2144
         */
        if ( isset( $_POST['woocommerce-process-checkout-nonce'] ) && isset( $_POST['vendor_delivery_time'] ) ) { // phpcs:ignore
            // Getting delivery time data from classic checkout page
            $data = wc_clean( wp_unslash( $_POST['vendor_delivery_time'] ) ); // phpcs:ignore
        } elseif ( ! empty( $parent_order_id ) ) {
            // Getting delivery time data from block checkout page.
            $parent_order = wc_get_order( $parent_order_id );
            $data = $parent_order->get_meta( 'dokan_cart_checkout_block_delivery_time' );
        } else {
            $data = $order->get_meta( 'dokan_cart_checkout_block_delivery_time' );
        }

        $data = empty( $data ) ? [] : $data;

        return $data;
    }

    /**
     * Saves delivery time args for single and sub orders
     *
     * @since 3.3.0
     *
     * @param /WC_Order $order
     * @param int $vendor_id
     *
     * @return void
     */
    public function save_delivery_time_args( $order, $vendor_id ) {
        $order = $order instanceof \WC_Order ? $order : wc_get_order( $order );
        $data = $this->has_delivery_time_to_save( $order );

        if ( empty( $data ) ) {
            return;
        }

        $delivery_date          = isset( $data[ $vendor_id ]['delivery_date'] ) ? sanitize_text_field( $data[ $vendor_id ]['delivery_date'] ) : '';
        $delivery_time_slot     = isset( $data[ $vendor_id ]['delivery_time_slot'] ) ? sanitize_text_field( $data[ $vendor_id ]['delivery_time_slot'] ) : '';
        $selected_delivery_type = isset( $data[ $vendor_id ]['selected_delivery_type'] ) ? sanitize_text_field( $data[ $vendor_id ]['selected_delivery_type'] ) : '';

        $data = apply_filters(
            'dokan_delivery_time_checkout_args', [
                'order'                  => $order,
                'vendor_id'              => $vendor_id,
                'delivery_date'          => $delivery_date,
                'delivery_time_slot'     => $delivery_time_slot,
                'selected_delivery_type' => $selected_delivery_type,
            ], $data, $vendor_id
        );

        Helper::save_delivery_time_date_slot( $data, $order );
        $order->save_meta_data();
        $order->save();
    }

    /**
     * Renders delivery time details on wc order details page
     *
     * @since 3.3.0
     *
     * @param \WC_Order $order
     *
     * @return void
     */
    public function render_delivery_time_wc_order_details( $order ) {
        if ( ! $order ) {
            return;
        }

        // Getting delivery date meta
        $vendor_delivery_date = $order->get_meta( 'dokan_delivery_time_date' );

        if ( ! $vendor_delivery_date ) {
            return;
        }

        $current_date       = dokan_current_datetime();
        $current_date       = $current_date->modify( $vendor_delivery_date );
        $delivery_time_date = $current_date->format( 'F j, Y' );

        $delivery_time_slot = $order->get_meta( 'dokan_delivery_time_slot' );

        if ( ! $delivery_time_slot ) {
            return;
        }

        $store_location = $order->get_meta( 'dokan_store_pickup_location' );

        if ( ! empty( $store_location ) ) {
            return;
        }

        dokan_get_template_part(
            'delivery-time-order-details', '', [
                'order'                   => $order,
                'is_delivery_time'        => true,
                'delivery_time_date_slot' => [
                    'date' => $delivery_time_date,
                    'slot' => $delivery_time_slot,
                ],
            ]
        );
    }

    /**
     * Gets vendor delivery time slot from ajax request
     *
     * @since 3.3.0
     */
    public function get_vendor_delivery_time_slot() {
        if ( ! isset( $_POST['action'] ) || wc_clean( wp_unslash( $_POST['action'] ) ) !== 'dokan_get_delivery_time_slot' ) {
            wp_send_json_error( __( 'Something went wrong', 'dokan' ), '403' );
        }

        if ( ! isset( $_POST['nonce'] ) && ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nonce'] ) ), 'dokan_delivery_time' ) ) {
            wp_send_json_error( __( 'Invalid nonce', 'dokan' ) );
        }

        $vendor_id = ! empty( $_POST['vendor_id'] ) ? sanitize_text_field( wp_unslash( $_POST['vendor_id'] ) ) : '';
        $date      = ! empty( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';

        if ( empty( $vendor_id ) || empty( $date ) || ! strtotime( $date ) ) {
            wp_send_json_error( [ 'message' => __( 'No date or vendor id found.', 'dokan' ) ], 400 );
        }

        $delivery_time_slots = Helper::get_current_date_active_delivery_time_slots( $vendor_id, $date );
        wp_send_json_success( [ 'vendor_delivery_slots' => $delivery_time_slots ], 201 );
    }

    /**
     * Validates delivery time slot args from wc checkout
     *
     * @since 3.3.0
     *
     * @param array $wc_data
     * @param object $errors
     *
     * @return void
     */
    public function validate_delivery_time_slot_args( $wc_data, $errors ) {
        $is_time_selection_required = dokan_get_option( 'selection_required', 'dokan_delivery_time', 'on' );

        if ( 'off' === $is_time_selection_required ) {
            return;
        }

        $posted_data = isset( $_POST['vendor_delivery_time'] ) ? wp_unslash( wc_clean( $_POST['vendor_delivery_time'] ) ) : []; //phpcs:ignore

        foreach ( $posted_data as $data ) {
            if ( ! empty( $data['selected_delivery_type'] ) && ( ! empty( $data['vendor_id'] ) && empty( $data['delivery_date'] ) ) ) {
                /* translators: %1$s selected delivery type name, %2$s: store name */
                $errors->add( 'dokan_delivery_date_required_error', sprintf( __( 'Please make sure you have selected the %1$s date for %2$s.', 'dokan' ), StorePickupHelper::get_formatted_delivery_type( $data['selected_delivery_type'] ), $data['store_name'] ) );
            }

            if ( ! empty( $data['selected_delivery_type'] ) && ( ! empty( $data['vendor_id'] ) && ! empty( $data['delivery_date'] ) ) && empty( $data['delivery_time_slot'] ) ) {
                /* translators: %s: store name */
                $errors->add( 'dokan_delivery_time_slot_error', sprintf( __( 'Please make sure you have selected the delivery time slot for %1$s.', 'dokan' ), $data['store_name'] ) );
            }

            if ( ( ! empty( $data['selected_delivery_type'] ) && 'store-pickup' === $data['selected_delivery_type'] ) && ( ! empty( $data['vendor_id'] ) && ! empty( $data['delivery_date'] ) ) && empty( $data['store_pickup_location'] ) ) {
                /* translators: %s: store name */
                $errors->add( 'dokan_store_pickup_location_error', sprintf( __( 'Please make sure you have selected the store pickup location for %1$s.', 'dokan' ), $data['store_name'] ) );
            }

            // Additional validation for hour-based buffer
            if ( ! empty( $data['vendor_id'] ) && ! empty( $data['delivery_date'] ) && ! empty( $data['delivery_time_slot'] ) ) {
                $vendor_id = (int) $data['vendor_id'];
                $settings  = Helper::get_delivery_time_settings( $vendor_id );
                $unit      = isset( $settings['delivery_buffer_unit'] ) ? $settings['delivery_buffer_unit'] : 'days';

                if ( 'hours' === $unit ) {
                    $earliest     = Helper::get_earliest_delivery_datetime( $vendor_id, dokan_current_datetime() );
                    $earliest_date = $earliest->format( 'Y-m-d' );
                    $earliest_time = $earliest->format( 'g:i a' );

                    $selected_date = $data['delivery_date'];
                    $selected_slot = $data['delivery_time_slot'];
                    $parts        = explode( ' - ', $selected_slot );
                    $slot_start    = isset( $parts[0] ) ? trim( $parts[0] ) : '';

                    if ( $selected_date < $earliest_date ) {
                        /* translators: 1: store name, 2: earliest datetime */
                        $errors->add( 'dokan_delivery_time_earliest_error', sprintf( __( 'The selected time for %1$s is too soon. Earliest available is %2$s.', 'dokan' ), $data['store_name'], Helper::get_formatted_delivery_date_time_string( $earliest_date, $earliest_time . ' - ' . $earliest_time ) ) );
                    } elseif ( $selected_date === $earliest_date && ! empty( $slot_start ) ) {
                        $slot_end = isset( $parts[1] ) ? trim( $parts[1] ) : '';
                        if ( ! empty( $slot_end ) && strtotime( $slot_end ) <= strtotime( $earliest_time ) ) {
                            /* translators: 1: store name, 2: earliest time */
                            $errors->add( 'dokan_delivery_time_earliest_error', sprintf( __( 'Please select a time on or after %2$s for %1$s.', 'dokan' ), $data['store_name'], $earliest_time ) );
                        }
                    }
                }
            }
        }
    }

    /**
     * Add i18n variable to frontend
     *
     * @since 3.3.0
     *
     * @param array $args
     *
     * @return array
     */
    public function add_i18n_date_format_localized_variable( $args ) {
        $args['i18n_date_format'] = wc_date_format();
        return $args;
    }
}
