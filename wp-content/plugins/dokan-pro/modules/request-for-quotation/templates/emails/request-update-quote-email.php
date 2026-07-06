<?php
/**
 * Update Quote Email.
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

$quotation_status = $quote_status !== Quote::STATUS_APPROVED ? 'Updated' : 'Approved';
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
                <td class="quote-status"><?php echo esc_html( $quotation_status ); ?> </td>
            </tr>
            <?php if ( 'seller' === $sending_to ) : ?>
                <tr>
                    <th class="quote-status"><?php esc_html_e( 'Customer name', 'dokan' ); ?></th>
                    <td class="quote-status"><?php echo esc_html( ! empty( $customer_info['name_field'] ) ? $customer_info['name_field'] : '' ); ?> </td>
                </tr>
                <tr>
                    <th class="quote-status"><?php esc_html_e( 'Customer email', 'dokan' ); ?></th>
                    <td class="quote-status"><?php echo esc_html( ! empty( $customer_info['email_field'] ) ? $customer_info['email_field'] : '' ); ?> </td>
                </tr>
            <?php endif; ?>
            <?php if ( ! empty( $vendor_msg ) ) : ?>
                <tr>
                    <th class="quote-status"><?php esc_html_e( 'Vendor Message:', 'dokan' ); ?></th>
                    <td class="quote-status"><?php echo esc_html( $vendor_msg ); ?> </td>
                </tr>
            <?php endif; ?>
            <?php if ( $quote_status !== Quote::STATUS_UPDATE && $quote_expiry ) : ?>
                <tr>
                    <th class="quote-expiry"><?php esc_html_e( 'Quote Expiry:', 'dokan' ); ?></th>
                    <td class="quote-expiry"><?php echo esc_html( $quote_expiry ); ?> </td>
                </tr>
            <?php endif; ?>
        </table>
        <h2><?php echo esc_html__( 'Previous quote details', 'dokan' ); ?></h2>
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
            foreach ( $old_quote_details as $quote_item ) {
                $_product          = wc_get_product( $quote_item->product_id );
                $price             = $_product->get_price();
                $offer_price       = isset( $quote_item->offer_price ) ? floatval( $quote_item->offer_price ) : $price;
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
                        ?>
                    </td>
                </tr>
                <?php
            }
            ?>
            </tbody>
        </table>
        <h2>
            <?php
            printf(
                /* translators: %s: Quotation status */
                esc_html__( '%s Quote Details', 'dokan' ),
                $quotation_status
            );
            ?>
        </h2>
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

