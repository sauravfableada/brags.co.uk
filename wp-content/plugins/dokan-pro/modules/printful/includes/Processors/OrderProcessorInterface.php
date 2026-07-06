<?php

namespace WeDevs\DokanPro\Modules\Printful\Processors;

use WC_Order;

interface OrderProcessorInterface {

    public function create( WC_Order $order );
    public function update( WC_Order $order );
    public function delete( $order );

}
