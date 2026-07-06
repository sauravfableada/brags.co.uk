<?php
/**
 * Dashboard Subscription Order Listing Template.
 *
 * @since 3.13.0
 *
 * @var array $subscription_orders Subscription Orders.
 */
?>
<div class="dokan-subscription-content">
<?php
    if ( ! empty( $subscription_orders['orders'] ) ) :
        ?>
        <table class="shop_table shop_table_responsive my_account_orders dokan-subscription-order-listing-table">
            <thead>
            <tr>
                <th class="order-number"><span><?php esc_html_e( 'Order', 'dokan' ); ?></span></th>
                <th class="order-date"><span><?php esc_html_e( 'Date', 'dokan' ); ?></span></th>
                <th class="order-status"><span><?php esc_html_e( 'Status', 'dokan' ); ?></span></th>
                <th class="order-total"><span><?php esc_html_e( 'Total', 'dokan' ); ?></span></th>
                <th class="order-actions"><span><?php esc_html_e( 'Actions', 'dokan' ); ?></span></th>
            </tr>
            </thead>

            <tbody>
            <?php
            foreach ( $subscription_orders['orders'] as $order ) :
                ?>
                <tr class="order">
                    <td class="order-number" data-title="<?php esc_attr_e( 'Order Number', 'dokan' ); ?>">
                        <a href="<?php echo esc_url( $order->get_view_order_url() ); ?>">
                            <?php echo esc_html(  $order->get_order_number() ); ?>
                        </a>
                    </td>
                    <td class="order-date" data-title="<?php esc_attr_e( 'Date', 'dokan' ); ?>">
                        <?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?>
                    </td>
                    <td class="order-status" data-title="<?php esc_attr_e( 'Status', 'dokan' ); ?>">
                        <?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?>
                    </td>
                    <td class="order-total" data-title="<?php esc_attr_e( 'Total', 'dokan' ); ?>">
                        <?php echo wp_kses_post( $order->get_formatted_order_total() ); ?>
                    </td>
                    <td class="order-actions">
                        <?php
                        // Display order action buttons
                        $actions = wc_get_account_orders_actions( $order );
                        if ( ! empty( $actions ) ) {
                            foreach ( $actions as $key => $action ) {
                                echo '<a href="' . esc_url( $action['url'] ) . '" class="button ' . sanitize_html_class( $key ) . '" target="_blank">' . esc_html( $action['name'] ) . '</a>';
                            }
                        }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php
        $total_pages  = $subscription_orders['total_pages'];
        $current_page = $subscription_orders['current_page'];
        $base_url     = dokan_get_navigation_url( 'subscription' );

        if ( $total_pages > 1 ) :
            echo '<div class="pagination-wrap">';
            $page_links = paginate_links(
                [
                    'current'   => $current_page,
                    'total'     => $total_pages,
                    'base'      => $base_url. '%_%',
                    'format'    => '?pagenum=%#%',
                    'add_args'  => false,
                    'type'      => 'array',
                ]
            );

            echo "<ul class='pagination'>\n\t<li>";
            echo join( "</li>\n\t<li>", $page_links );
            echo "</li>\n</ul>\n";
            echo '</div>';
        endif;
    else :
        esc_html_e( 'No subscription orders has been found!', 'dokan' );
    endif;
    ?>
</div>
<?php
