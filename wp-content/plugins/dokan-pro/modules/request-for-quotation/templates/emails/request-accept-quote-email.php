<?php
/**
 * Accepted Quote Email.
 *
 * An email sent to the vendor and admin when a quote is accepted by customer.
 *
 * @version 3.12.3
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WeDevs\DokanPro\Modules\RequestForQuotation\Model\Quote;

$text_align = is_rtl() ? 'right' : 'left';

/**
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email );

?>
    <div>
        <table class='shop_table order_details quote_details dokan-table order-items'>
            <tr>
                <th class='quote-number'><?php esc_html_e( 'Quote #', 'dokan' ); ?></th>
                <td class="quote-number"><?php echo esc_html( $quote_id ); ?> </td>
            </tr>
            <tr>
                <th class="quote-date"><?php esc_html_e( 'Quote Date', 'dokan' ); ?></th>
                <td class="quote-date"><?php echo esc_attr( dokan_format_datetime() ); ?> </td>
            </tr>
            <tr>
                <th class="quote-status"><?php esc_html_e( 'Quote Status', 'dokan' ); ?></th>
                <td class="quote-status"><?php echo esc_html( Quote::get_status_label( 'accepted' ) ); ?> </td>
            </tr>
            <?php if ( 'admin' === $sending_to ) : ?>
                <tr>
                    <th class="quote-status"><?php esc_html_e( 'Store name', 'dokan' ); ?></th>
                    <td class="quote-status"><?php echo esc_html( $store_info['store_name'] ?? '' ); ?> </td>
                </tr>
                <tr>
                    <th class="quote-status"><?php esc_html_e( 'Store email', 'dokan' ); ?></th>
                    <td class="quote-status"><?php echo esc_html( $seller_email ?? '' ); ?> </td>
                </tr>
            <?php endif; ?>
            <tr>
                <th class="quote-status"><?php esc_html_e( 'Customer name', 'dokan' ); ?></th>
                <td class="quote-status"><?php echo esc_html( $customer_info['name_field'] ?? '' ); ?> </td>
            </tr>
            <tr>
                <th class="quote-status"><?php esc_html_e( 'Customer email', 'dokan' ); ?></th>
                <td class="quote-status"><?php echo esc_html( $customer_info['email_field'] ?? '' ); ?> </td>
            </tr>
            <?php if ( ! empty( $customer_info['customer_additional_msg'] ) ) : ?>
                <tr>
                    <th class="customer-email"><?php esc_html_e( 'Additional Message:', 'dokan' ); ?></th>
                    <td class="customer-email"><?php echo esc_html( $customer_info['customer_additional_msg'] ); ?> </td>
                </tr>
            <?php endif; ?>
            <?php if ( ! empty( $expected_date ) ) : ?>
                <tr>
                    <th class="customer-email"><?php esc_html_e( 'Expected Delivery Date:', 'dokan' ); ?></th>
                    <td class="customer-email"><?php echo esc_html( $expected_date ); ?> </td>
                </tr>
            <?php endif; ?>
            <?php if ( ! empty( $quote_expiry ) ) : ?>
                <tr>
                    <th class="quote-expiry"><?php esc_html_e( 'Quote Expiry:', 'dokan' ); ?></th>
                    <td class="quote-expiry"><?php echo esc_html( $quote_expiry ); ?> </td>
                </tr>
            <?php endif; ?>
        </table>
        <h2><?php esc_html_e( 'Accepted Quote Details', 'dokan' ); ?></h2>
        <table cellspacing="0">
            <thead>
            <tr>
                <th class='td' scope='col' style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php esc_html_e( 'Product', 'dokan' ); ?></th>
                <th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php esc_html_e( 'Offered Price', 'dokan' ); ?></th>
                <th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php esc_html_e( 'Quantity', 'dokan' ); ?></th>
                <th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php esc_html_e( 'Offered Subtotal', 'dokan' ); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php
            $quote_subtotal = 0;
            $offered_total  = 0;
            foreach ( $quote_details as $quote_item ) {
                $offer_price = isset( $quote_item->offer_price ) ? floatval( $quote_item->offer_price ) : $price;
                $_product    = wc_get_product( $quote_item->product_id );
                $price       = $_product->get_price();
                ?>
                <tr>
                    <td class='td' scope='col'>
                        <?php
                        echo wp_kses_post( $_product->get_name() . '&nbsp;' );
                        echo '<p><strong>' . esc_html__( 'SKU:', 'dokan' ) . '</strong> ' . esc_html( $_product->get_sku() ) . '</p>';
                        ?>
                    </td>
                    <td class='td' scope='col'>
                        <?php
                        echo wp_kses_post( wc_price( $offer_price ) );
                        ?>
                    </td>
                    <td class='td' scope='col'>
                        <?php
                        $qty_display = $quote_item->quantity;
                        echo wp_kses_post( ' <strong class="product-quantity">' . sprintf( '&nbsp;%s', $qty_display ) . '</strong>' );
                        ?>
                    </td>
                    <td class='td' scope='col'>
                        <?php
                        echo wp_kses_post( wc_price( $offer_price * $qty_display ) );
                        $offered_total += ( $offer_price * $qty_display );
                        ?>
                    </td>
                </tr>
                <?php
            }
            ?>
            </tbody>
        </table>
        <div class='cart-collaterals'>
            <div class="cart_totals">
                <h2><?php esc_html_e( 'Quote totals', 'dokan' ); ?></h2>
                <table class='shop_table shop_table_responsive table_quote_totals'>
                    <?php
                    if ( $shipping_cost > 0 ) :
                        $offered_total += floatval( $shipping_cost );
                        ?>
                        <tr class="cart-subtotal offered">
                            <th><?php esc_html_e( 'Shipping Cost', 'dokan' ); ?></th>
                            <td data-title="<?php esc_attr_e( 'Shipping Cost', 'dokan' ); ?>">
                                <?php echo wp_kses_post( wc_price( $shipping_cost ) ); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <tr class="cart-subtotal offered">
                        <th><?php esc_html_e( 'Total Offered Price', 'dokan' ); ?></th>
                        <td data-title="<?php esc_attr_e( 'Total Offered Price', 'dokan' ); ?>">
                            <?php echo wp_kses_post( wc_price( $offered_total ) ); ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <table cellspacing='0' width="100%">
            <tbody>
            <tr>
                <td style="padding: 0;">
                    <h2><?php esc_html_e( 'Shipping address', 'dokan' ); ?></h2>

                    <address class="address" style="padding: 12px;color: #636363;border: 1px solid #e5e5e5">
                        <?php echo esc_html( $customer_info['name_field'] ?? '' ); ?><br>
                        <?php echo esc_html( $customer_info['phone_field'] ?? '' ); ?><br>
                        <?php echo esc_html( $customer_info['addr_line_1'] ?? '' ); ?><br>
                        <?php echo esc_html( $customer_info['addr_line_2'] ?? '' ); ?><br>
                        <?php echo esc_html( $customer_info['city'] ?? '' ); ?><br>
                        <?php echo esc_html( $customer_info['post_code'] ?? '' ); ?>
                    </address>
                </td>
            </tr>
            </tbody>
        </table>
    </div>

<?php
/**
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );

