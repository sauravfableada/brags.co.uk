<?php

use WeDevs\Dokan\Cache;

/**
 * Include Dokan ShipStation template
 *
 * @since 1.0.0
 *
 * @param string $name
 * @param array  $args
 *
 * @return void
 */
function dokan_shipstation_get_template( $name, $args = [] ) {
    dokan_get_template( "$name.php", $args, DOKAN_SHIPSTATION_VIEWS, trailingslashit( DOKAN_SHIPSTATION_VIEWS ) );
}

/**
 * Get Order data for a seller
 *
 * @since 1.0.0
 *
 * @param int   $seller_id
 * @param array $args
 *
 * @return array
 */
function dokan_shipstation_get_orders( $seller_id, $args = array() ) {
    wc_deprecated_function( 'dokan_shipstation_get_orders', '3.8.0', 'dokan_pro()->order->all()' );
    return [];
}
