<?php
use WeDevs\Dokan\Cache;
/**
 * Class Dokan_WC_Booking_Helper
 *
 * @since 3.3.6
 */
class Dokan_WC_Booking_Helper {

    /**
     * Gets vendor booking products
     *
     * @since 3.3.6
     *
     * @return array
     */
    public static function get_vendor_booking_products() {
        $vendor_id = dokan_get_current_user_id();

        if ( ! dokan_is_user_seller( $vendor_id ) ) {
            return [];
        }

        add_filter( 'get_booking_products_args', [ __CLASS__, 'filter_vendor_booking_products' ], 10, 1 );

        $booking_products = WC_Bookings_Admin::get_booking_products();

        remove_filter( 'get_booking_products_args', [ __CLASS__, 'filter_vendor_booking_products' ], 10 );

        return $booking_products;
    }

    /**
     * Filters vendor booking products
     *
     * @since 3.3.6
     *
     * @param array $args
     *
     * @return array
     */
    public static function filter_vendor_booking_products( $args ) {
        $args['author'] = dokan_get_current_user_id();
        return $args;
    }

    /**
     * Checks if global addon rma is active
     *
     * @since 3.3.6
     *
     * @return bool
     */
    public static function is_global_addon_rma_active() {
        if ( ! dokan_pro()->module->is_active( 'rma' ) ) {
            return false;
        }

        $global_warranty = get_user_meta( dokan_get_current_user_id(), '_dokan_rma_settings', true );

        $type = isset( $global_warranty['type'] ) ? $global_warranty['type'] : '';

        if ( 'addon_warranty' === $type ) {
            return true;
        }

        return false;
    }

    /**
     * This method will return booking status counts by seller id
     *
     * @since 3.7.1 moved this function here from module.php
     *
     * @param $seller_id
     *
     * @return object
     */
    public static function get_booking_status_counts_by( $seller_id ) {
        global $wpdb;

        $statuses = array_unique( array_merge( get_wc_booking_statuses(), get_wc_booking_statuses( 'user' ), get_wc_booking_statuses( 'cancel' ) ) );
        $statuses = array_fill_keys( array_keys( array_flip( $statuses ) ), 0 );
        $counts   = $statuses + [ 'total' => 0 ];

        $cache_group = "bookings_{$seller_id}";
        $cache_key   = 'bookings_count';
        $results     = Cache::get( $cache_key, $cache_group );

        if ( false === $results ) {
            $meta_key = '_booking_seller_id';

            $sql = "Select post_status
            From $wpdb->posts as p
            LEFT JOIN $wpdb->postmeta as pm ON p.ID = pm.post_id
            WHERE
            pm.meta_key = %s AND
            pm.meta_value = %d AND
            p.post_status != 'trash' ";

            // @codingStandardsIgnoreLine
            $results = $wpdb->get_results( $wpdb->prepare( $sql, $meta_key, $seller_id ) );

            Cache::set( $cache_key, $results, $cache_group );
        }

        foreach ( $results as $status ) {
            if ( isset( $counts[ $status->post_status ] ) ) {
                $counts[ $status->post_status ] += 1;
                $counts['total']                += 1;
            }
        }

        return (object) $counts;
    }
}
