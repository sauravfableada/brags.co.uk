<?php

namespace WeDevs\DokanPro\Modules\ShipStation;

/**
 * Vendor Dashboard Order Status Settings Class.
 *
 * @since 3.14.4
 */
class VendorOrderStatusSettings {

    /**
     * Get Order Status Settings.
     *
     * @since 3.14.4
     *
     * @param int $vendor_id Vendor ID
     *
     * @return array Order Status Settings
     */
    public static function get( $vendor_id = null ): array {
        $vendor_id = $vendor_id ?: dokan_get_current_user_id();

        $order_statuses['vendor_id']       = $vendor_id;
        $order_statuses['export_statuses'] = get_user_meta( $vendor_id, 'shipstation_export_statuses', true );
        $order_statuses['shipped_status']  = get_user_meta( $vendor_id, 'shipstation_shipped_status', true );

        return apply_filters( 'dokan_shipstation_vendor_order_status_settings', $order_statuses, $vendor_id );
    }

    /**
     * Update Order Status Settings.
     *
     * @since 3.14.4
     *
     * @param int    $vendor_id       Vendor ID
     * @param array  $export_statuses Export Order Statuses
     * @param string $shipped_status  Shipped Order Status
     *
     * @return array
     */
    public static function update( $vendor_id, $export_statuses, $shipped_status ): array {
        $output = [];

        if ( ! ( $vendor_id && $export_statuses && $shipped_status ) ) {
            return $output;
        }

        update_user_meta( $vendor_id, 'shipstation_export_statuses', $export_statuses );
        update_user_meta( $vendor_id, 'shipstation_shipped_status', $shipped_status );

        $output['vendor_id']       = $vendor_id;
        $output['export_statuses'] = $export_statuses;
        $output['shipped_status']  = $shipped_status;

        do_action( 'dokan_shipstation_vendor_order_status_settings_updated', $vendor_id, $output );

        return $output;
    }

    /**
     * Remove Order Status Settings.
     *
     * @since 3.14.4
     *
     * @param int $vendor_id Vendor ID
     *
     * @return array|false
     */
    public static function remove( int $vendor_id ) {
        if ( ! $vendor_id ) {
            return false;
        }

        $order_status_settings = self::get( $vendor_id );

        if ( ! ( $order_status_settings['export_statuses'] && $order_status_settings['shipped_status'] ) ) {
            return false;
        }

        delete_user_meta( $vendor_id, 'shipstation_export_statuses' );
        delete_user_meta( $vendor_id, 'shipstation_shipped_status' );

        do_action( 'dokan_shipstation_vendor_order_status_settings_removed', $vendor_id, $order_status_settings );

        return $order_status_settings;
    }
}
