<?php

namespace WeDevs\DokanPro\Modules\RMA;

use Exception;
use WC_Order;
use WC_Order_Item_Product;

class Hooks {
    public function __construct() {
        add_filter( 'woocommerce_rest_prepare_shop_order_object', [ $this, 'prepare_rma_order_object' ], 10, 2 );
    }

    /**
     * Add Warranty data into order object
     *
     * @param mixed $response
     * @param WC_Order $order
     *
     * @return mixed
     * @throws Exception
     */
    public function prepare_rma_order_object( $response, WC_Order $order ) {
        $allowed_status                = dokan_get_option( 'rma_order_status', 'dokan_rma', 'wc-completed' );
        $data                          = $response->get_data();
        $data['eligible_for_warranty'] = ! ( $allowed_status !== 'wc-' . $order->get_status() );
        $response->set_data( $data );

        return $response;
    }
}
