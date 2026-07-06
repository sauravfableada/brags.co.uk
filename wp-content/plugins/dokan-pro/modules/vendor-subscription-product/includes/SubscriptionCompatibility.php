<?php

namespace WeDevs\DokanPro\Modules\VSP;

class SubscriptionCompatibility {
    /**
     * SubscriptionCompatibility constructor.
     *
     * @since 4.0.0
     */
    public function __construct() {
        add_action( 'dokan_order_commission_tax_refunded_before', [ $this, 'dokan_order_commission_before_refund' ] );
        add_action( 'dokan_order_commission_tax_refunded_after', [ $this, 'dokan_order_commission_after_refund' ] );
    }

    /**
     * Make compatible woocommerce subscription 7.4.0 and below.
     *
     * @since 4.0.0
     *
     * @param $subscription_orders
     *
     *@return mixed
     */
    public function map_subscription_order( $subscription_orders ) {
        foreach ( $subscription_orders as $key => $order ) {
            if ( is_numeric( $order ) ) {
                $subscription_orders[ $key ] = wc_get_order( $order );
            }
        }

        return $subscription_orders;
    }

    /**
     * Make compatible woocommerce subscription 7.4.0 and below.
     *
     * @since 4.0.0
     *
     * @param $subscription_orders
     *
     * @return mixed
     */
    public function dokan_order_commission_before_refund() {
        add_filter( 'woocommerce_subscription_related_orders', [ $this, 'map_subscription_order' ] );
    }

    /**
     * Make compatible woocommerce subscription 7.4.0 and below.
     *
     * @since 4.0.0
     *
     * @param $subscription_orders
     *
     * @return mixed
     */
    public function dokan_order_commission_after_refund() {
        remove_filter( 'woocommerce_subscription_related_orders', [ $this, 'map_subscription_order' ] );
    }
}
