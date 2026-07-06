<?php

namespace WeDevs\DokanPro\Modules\RMA;

use WC_Coupon;
use WC_Order;
use DateTimeZone;

/**
 * Utility class for RMA functionality
 *
 * @since 4.0.0
 */
class Utils {

    /**
     * Create coupon for the refund amount
     *
     * @since 4.0.0
     *
     * @param WC_Order $order
     * @param array $data
     *
     * @return void
     */
    public static function create_coupon( WC_Order $order, array $data ) {
        $refund_amount = wc_format_decimal( $data['refund_total_amount'] );
        $vendor_id     = dokan_get_current_user_id();

        $coupon = new WC_Coupon();
        $coupon->set_code( dokan_rma_generate_coupon_code() );
        $coupon->set_amount( $refund_amount );
        $coupon->set_date_created( dokan_current_datetime()->setTimezone( new DateTimeZone( 'UTC' ) )->getTimestamp() );
        $coupon->set_date_expires( null );
        $coupon->set_discount_type( 'fixed_cart' );
        $coupon->set_description( '' );
        $coupon->set_usage_count( 0 );
        $coupon->set_individual_use( false );
        $coupon->set_excluded_product_ids( [] );
        $coupon->set_usage_limit( '1' );
        $coupon->set_usage_limit_per_user( '1' );
        $coupon->set_limit_usage_to_x_items( null );
        $coupon->set_free_shipping( false );
        $coupon->set_product_categories( [] );
        $coupon->set_excluded_product_categories( [] );
        $coupon->set_exclude_sale_items( false );
        $coupon->set_minimum_amount( '' );
        $coupon->set_maximum_amount( '' );
        $coupon->set_email_restrictions( [ $order->get_billing_email() ] );
        $coupon->set_used_by( [] );
        $coupon->set_virtual( false );

        /*
         * We need to include all the product IDs
         * of the vendor so that the coupon can be
         * used for the vendor's products.
         */
        $product_ids = dokan_coupon_get_seller_product_ids( $vendor_id );
        $coupon->set_product_ids( $product_ids );
        $coupon->save();

        $coupon_id = $coupon->get_id();
        wp_update_post(
            [
                'ID'          => $coupon_id,
                'post_author' => $vendor_id,
            ]
        );

        /*
         * This will allow the coupon to be used for new products
         * that will be created in future as well.
         */
        update_post_meta( $coupon_id, 'apply_new_products', 'yes' );

        dokan_update_warranty_request_status( absint( $data['request_id'] ), 'completed' );

        /**
         * Dokan Warranty Request Completed Action
         *
         * @since 3.4.0
         *
         * @param int $request_id The warranty request ID
         * @param int $vendor_id The vendor id of the requested product
         */
        do_action( 'dokan_send_coupon_to_customer', $coupon, $data );
    }
}
