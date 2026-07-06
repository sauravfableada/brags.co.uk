<?php
/**
 * New Quote Email.
 *
 * An email sent to the vendor and customer when a new quote is created by customer.
 *
 * @class       NewQuote
 * @version     3.6.0
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
        <table cellspacing='0'>
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
                <td class="quote-status"><?php echo esc_html( Quote::get_status_label( 'pending' ) ); ?> </td>
            </tr>
            <?php if ( 'customer' === $sending_to ) : ?>
                <?php if ( ! empty( $store_info['store_name'] ) ) : ?>
                    <tr>
                        <th class="customer-email"><?php esc_html_e( 'Store:', 'dokan' ); ?></th>
                        <td class="customer-email"><?php echo esc_html( $store_info['store_name'] ); ?> </td>
                    </tr>
                <?php endif; ?>
                <?php if ( ! empty( $store_info['store_email'] ) ) : ?>
                    <tr>
                        <th class="customer-email"><?php esc_html_e( 'Store Email:', 'dokan' ); ?></th>
                        <td class="customer-email"><?php echo esc_html( $store_info['store_email'] ?? '' ); ?> </td>
                    </tr>
                <?php endif; ?>
                <?php if ( ! empty( $store_info['store_phone'] ) ) : ?>
                    <tr>
                        <th class="customer-email"><?php esc_html_e( 'Store Phone:', 'dokan' ); ?></th>
                        <td class="customer-email"><?php echo esc_html( $store_info['store_phone'] ?? '' ); ?> </td>
                    </tr>
                <?php endif; ?>
            <?php else : ?>
                <?php if ( ! empty( $customer_info['name_field'] ) ) : ?>
                    <tr>
                        <th class="customer-name"><?php esc_html_e( 'Customer Name', 'dokan' ); ?></th>
                        <td class="customer-name"><?php echo esc_html( $customer_info['name_field'] ); ?> </td>
                    </tr>
                <?php endif; ?>
                <?php if ( ! empty( $customer_info['email_field'] ) ) : ?>
                    <tr>
                        <th class="customer-email"><?php esc_html_e( 'Customer Email', 'dokan' ); ?></th>
                        <td class="customer-email"><?php echo esc_html( $customer_info['email_field'] ?? '' ); ?> </td>
                    </tr>
                <?php endif; ?>
                <?php if ( ! empty( $customer_info['phone_field'] ) ) : ?>
                    <tr>
                        <th class="customer-name"><?php esc_html_e( 'Customer Phone', 'dokan' ); ?></th>
                        <td class="customer-name"><?php echo esc_html( $customer_info['phone_field'] ); ?> </td>
                    </tr>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ( ! empty( $expected_date ) ) : ?>
                <tr>
                    <th class="customer-email"><?php esc_html_e( 'Expected Delivery Date:', 'dokan' ); ?></th>
                    <td class="customer-email"><?php echo esc_html( $expected_date ); ?> </td>
                </tr>
            <?php endif; ?>
            <?php if ( ! empty( $customer_info['customer_additional_msg'] ) ) : ?>
                <tr>
                    <th class="customer-email"><?php esc_html_e( 'Additional Message:', 'dokan' ); ?></th>
                    <td class="customer-email"><?php echo esc_html( $customer_info['customer_additional_msg'] ); ?> </td>
                </tr>
            <?php endif; ?>
        </table>
        <h2><?php echo esc_html__( 'Quote Details', 'dokan' ); ?></h2>
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
                $_product          = wc_get_product( $quote_item->product_id );
                $price             = $_product->get_price();
                $offer_price       = isset( $quote_item->offer_price ) ? floatval( $quote_item->offer_price ) : $price;
                $product_permalink = $_product->is_visible() ? $_product->get_permalink() : '';
                ?>
                <tr>
                    <td class='td' scope='col' data-title="<?php esc_attr_e( 'Product', 'dokan' ); ?>">
                        <?php
                        if ( ! $product_permalink ) {
                            echo wp_kses_post( $_product->get_name() . '&nbsp;' );
                        } else {
                            echo wp_kses_post( sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $_product->get_name() ) );
                        }
                        echo '<p><strong>' . esc_html__( 'SKU:', 'dokan' ) . '</strong> ' . esc_html( $_product->get_sku() ) . '</p>';
                        ?>
                    </td>
                    <td class='td' scope='col' data-title="<?php esc_attr_e( 'Offered Price', 'dokan' ); ?>">
                        <?php
                        echo wp_kses_post( wc_price( $offer_price ) );
                        ?>
                    </td>
                    <td class='td' scope='col' data-title="<?php esc_attr_e( 'Quantity', 'dokan' ); ?>">
                        <?php
                        $qty_display = $quote_item->quantity;
                        echo wp_kses_post( ' <strong class="product-quantity">' . sprintf( '&nbsp;%s', $qty_display ) . '</strong>' );
                        ?>
                    </td>
                    <td class='td' scope='col' data-title="<?php esc_attr_e( 'Offered Subtotal', 'dokan' ); ?>">
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
                    <tr class="cart-subtotal offered">
                        <th><?php esc_html_e( 'Total Offered Price', 'dokan' ); ?></th>
                        <td data-title="<?php esc_attr_e( 'Total Offered Price', 'dokan' ); ?>"><?php echo wp_kses_post( wc_price( $offered_total ) ); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <?php if ( 'customer' === $sending_to ) : ?>
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
        <?php endif; ?>
    </div>

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
    echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

/**
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );

