<?php

namespace WeDevs\DokanPro\Upgrade\Upgraders\BackgroundProcesses;

use WeDevs\Dokan\Abstracts\DokanBackgroundProcesses;

/**
 * Dokan remove store categories upgrader class.
 *
 * @since 3.8.0
 */
class V_3_8_0 extends DokanBackgroundProcesses {

    /**
     * Action
     *
     * Override this action in your processor class
     *
     * @since 3.8.0
     *
     * @var string
     */
    protected $action = 'dokan_pro_bg_action_3_8_0';

    /**
     * Remove store categories.
     *
     * @since 3.8.0
     *
     * @param \WC_Subscription[] $subscriptions An array of WC_Subscription objects
     *
     * @return bool
     */
    public function task( $subscriptions ) {
        foreach ( $subscriptions as $subscription ) {
            $vendor_id = $subscription->get_meta( '_dokan_vendor_id' );
            if ( ! $vendor_id ) {
                $parent_order = $subscription->get_parent();
                $vendor_id    = $parent_order ? $parent_order->get_meta( '_dokan_vendor_id' ) : '';
            }
            if ( ! $vendor_id ) {
                continue;
            }

            // get all related orders
            $orders = $subscription->get_related_orders();
            foreach ( $orders as $order_id ) {
                $order = wc_get_order( $order_id );
                if ( $order ) {
                    if ( ! $order->get_meta( '_dokan_vendor_id' ) ) {
                        $order->update_meta_data( '_dokan_vendor_id', $vendor_id );
                        $order->save();
                    }
                }
            }
        }

        return false;
    }
}
