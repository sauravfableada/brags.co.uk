<?php
/**
 * Customer RMA request list template
 *
 * @package dokan
 *
 * @since 1.0.0
 */
?>
<header>
    <h2><?php esc_html_e( 'All Requests', 'dokan' ); ?></h2>
</header>

<br>

<?php wc_print_notices(); ?>

<table class="shop_table my_account_orders table table-striped">

    <thead>
        <tr>
            <th class="rma-order-id">
                <span class="nobr"><?php esc_html_e( 'Order ID', 'dokan' ); ?></span>
            </th>
            <th class="rma-vendor">
                <span class="nobr"><?php esc_html_e( 'Vendor', 'dokan' ); ?></span>
            </th>
            <th class="rma-details">
                <span class="nobr"><?php esc_html_e( 'Type', 'dokan' ); ?></span>
            </th>
            <th class="rma-status">
                <span class="nobr"><?php esc_html_e( 'Status', 'dokan' ); ?></span>
            </th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php
    if ( ! empty( $requests ) ) {
        foreach ( $requests as $request ) {
            $order_exist = ! empty( $request['order_id'] ) ? wc_get_order( $request['order_id'] ) : '';
            if ( $order_exist ) {
                ?>
                    <tr class="order">
                        <td class="order-number">
                            <?php
                                printf(
                                    /* translators: 1) RMA Request Endpoint, 2) Hash String, 3) Request Id, 4) On string, 5) Order URI, 6) Order String, 7) Order number. */
                                    '<a href="%1$s">%2$s%3$s</a> %4$s <a href="%5$s">%6$s %2$s%7$s</a>',
                                    esc_url( wc_get_account_endpoint_url( 'view-rma-requests' ) ) . $request['id'],
                                    _x( '#', 'hash before request number', 'dokan' ),
                                    $request['id'],
                                    esc_html__( 'on', 'dokan' ),
                                    $order_exist->get_view_order_url(),
                                    esc_html__( 'Order', 'dokan' ),
                                    $order_exist->get_order_number()
                                );
                            ?>
                        </td>
                        <td class="rma-vendor">
                            <a href="<?php echo $request['vendor']['store_url']; ?>"><?php echo $request['vendor']['store_name']; ?></a>
                        </td>
                        <td class="rma-type">
                            <?php echo dokan_warranty_request_type( $request['type'] ); ?>
                        </td>
                        <td class="rma-status" style="text-align:left; white-space:nowrap;">
                            <?php echo dokan_warranty_request_status( $request['status'] ); ?>
                        </td>
                        <td>
                            <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'view-rma-requests' ) ) . $request['id']; ?>" class="woocommerce-button button view">
                                <?php esc_html_e( 'View', 'dokan' ); ?>
                            </a>
                        </td>
                    </tr>
                <?php
            } else {
                ?>
                <tr class="order">
                <td class="order-number">

                    <?php
                    printf(
                    /* translators: 1) RMA Request Endpoint, 2) Hash String, 3) Request Id, 4) On string, 5) Order String, 6) Order number. */
                        '<strong>%1$s%2$s</strong> %3$s <span>%4$s %1$s%5$s</span>',
                        _x( '#', 'hash before request number', 'dokan' ),
                        $request['id'],
                        esc_html__( 'on', 'dokan' ),
                        esc_html__( 'Order', 'dokan' ),
                        $request['order_id']
                    );
                    ?>

                </td>
                <td class="rma-vendor">
                    <a href="<?php echo $request['vendor']['store_url']; ?>"><?php echo $request['vendor']['store_name']; ?></a>
                </td>
                <td class="rma-type">
                    <?php echo dokan_warranty_request_type( $request['type'] ); ?>
                </td>
                <td class="rma-status rma-deleted-status">
                    <?php echo dokan_warranty_request_status( $request['status'] ); ?>
                </td>
                <td>

                </td>
                </tr>
                <?php
            }
        }
    } else {
        ?>
        <tr>
            <td colspan="5">
                <?php esc_html_e( 'No request found', 'dokan' ); ?>
            </td>
        </tr>
        <?php
    }
    ?>
    </tbody>
</table>

<?php echo ! empty( $pagination_html ) ? $pagination_html : ''; ?>
