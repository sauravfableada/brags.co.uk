<?php
defined( 'ABSPATH' ) || exit;

use WeDevs\DokanPro\Modules\RequestForQuotation\Model\Quote;

$is_seller_dashboard = dokan_is_seller_dashboard();
$count_vendor_quotes = count( (array) $vendor_all_quotes );
$is_trash_page       = ! empty( $_GET['quote_status'] ) && Quote::STATUS_TRASH === sanitize_key( $_GET['quote_status'] ); // phpcs:ignore
?>
<table class="shop_table shop_table_responsive cart my_account_orders my_account_quotes">
    <thead>
        <tr>
            <?php if ( $is_seller_dashboard ) : ?>
                <th id="cb" class="manage-column column-cb check-column">
                    <div class="bulk-selection-column">
                        <label for="cb-select-all"></label>
                        <input id="cb-select-all" class="dokan-checkbox" type="checkbox">
                    </div>
                </th>
            <?php endif; ?>
            <th><?php esc_html_e( 'Quote #', 'dokan' ); ?></th>
            <th><?php esc_html_e( 'Status', 'dokan' ); ?></th>
            <th>
                <?php
                if ( $is_seller_dashboard ) :
                    esc_html_e( 'Customer', 'dokan' );
                else :
                    esc_html_e( 'Vendor', 'dokan' );
                endif;
                ?>
            </th>
            <th><?php esc_html_e( 'Date', 'dokan' ); ?></th>
            <?php if ( $is_seller_dashboard && ! $is_trash_page ) : ?>
                <th></th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
    <?php
    // Render list of quotes if not empty.
    if ( ! empty( $count_vendor_quotes ) ) :
        foreach ( $vendor_all_quotes as $key => $quote ) :
            $customer_info     = ! empty( $quote->customer_info ) ? maybe_unserialize( $quote->customer_info ) : [];
            $quote_id          = intval( $quote->id );
            $quote_expiry_date = $quote->expiry_date ?? 0;
            $creation_time     = human_time_diff( $quote->created_at, time() ) . ' ' . __( 'ago', 'dokan' );
            ?>
            <tr>
                <?php if ( $is_seller_dashboard ) : ?>
                    <th class="dokan-product-select check-column">
                        <div class="bulk-selection-column">
                            <label for="cb-select-<?php echo esc_attr( $quote_id ); ?>"></label>
                            <input class="cb-select-items dokan-checkbox" type="checkbox" name="bulk_quotes[]" value="<?php echo esc_attr( $quote_id ); ?>">
                        </div>
                    </th>
                <?php endif; ?>
                <td>
                    <a class="quote-id" href="<?php echo esc_url( wc_get_endpoint_url( $endpoint, $quote->id ) ); ?>">
                        <?php echo esc_html__( 'Quote', 'dokan' ) . ' ' . intval( $quote->id ); ?>
                    </a>
                    <?php if ( $quote->status === 'expired' && ! empty( $quote_expiry_date ) ) : ?>
                        <span class="expiry-time">
                            <?php
                            printf(
                                /* translators: 1) Expiry date. */
                                esc_html__( '(Expired: %1$s)', 'dokan' ),
                                dokan_current_datetime()->setTimestamp( $quote_expiry_date )->format( 'jS M Y' )
                            );
                            ?>
                        </span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php
                    $quote_status = esc_html( $quote->status );
                    if ( 'approve' === $quote->status ) {
                        $quote_status = esc_html__( 'Approved', 'dokan' );
                    }
                    echo isset( $quote_status ) ? ucfirst( $quote_status ) : Quote::get_status_label( 'pending' );
                    ?>
                </td>
                <td>
                    <?php
                        $customer_info = (object) ( ! empty( $quote->customer_info ) ? maybe_unserialize( $quote->customer_info ) : '' );
                        $customer_name = $customer_info->name_field ?? $quote->quote_title;
                        echo esc_html( $customer_name );
                    ?>
                </td>
                <td>
                    <time datetime="<?php echo esc_attr( $creation_time ); ?>" title="<?php echo esc_attr( $creation_time ); ?>">
                        <?php echo esc_html( $creation_time ); ?>
                    </time>
                </td>
                <?php if ( $is_seller_dashboard && ! $is_trash_page ) : ?>
                    <td>
                        <a title="<?php esc_attr_e( 'Delete image', 'dokan' ); ?>" data-quote_id="<?php echo esc_attr( $quote->id ); ?>" data-action="<?php echo esc_attr( Quote::STATUS_TRASH ); ?>"
                            data-message="<?php esc_html_e( 'Quote Successfully Trashed', 'dokan' ); ?>" class="action-delete status-updater" href="#">
                            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" stroke="#828282" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2.901 4.955h10.8" />
                                <path d="M12.502 4.955v8.4a1.2 1.2 0 0 1-1.2 1.2h-6a1.2 1.2 0 0 1-1.2-1.2v-8.4m1.8 0v-1.2a1.2 1.2 0 0 1 1.2-1.2h2.4a1.2 1.2 0 0 1 1.2 1.2v1.2" />
                            </svg>
                        </a>
                    </td>
                <?php endif; ?>
            </tr>
            <?php
        endforeach;
        else :
            echo '<tr><td colspan="5">' . esc_html__( 'No Results Found', 'dokan' ) . '</td></tr>';
        endif;
        ?>
    </tbody>
</table>

<?php
echo ! empty( $count_vendor_quotes ) ? $pagination_html : ''; // Render quote pagination.
