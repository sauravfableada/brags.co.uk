<?php
use WeDevs\DokanPro\Modules\RequestForQuotation\Helper;

$account_page     = is_account_page();
$hide_contents    = $account_page && $hide_price;
$is_pending_quote = Helper::is_qoute_status( 'pending', $quote );

$store_info = ! empty( $quote->store_info ) ? maybe_unserialize( $quote->store_info ) : [];
$store_id   = ! empty( $store_info['store_id'] ) ? absint( $store_info['store_id'] ) : 0;
$vendor_msg = ! empty( $store_info['vendor_additional_msg'] ) ? $store_info['vendor_additional_msg'] : '';

$offer_label        = $account_page ? __( 'Offered Price', 'dokan' ) : __( 'Customer Offer', 'dokan' );
$vendor_offer_label = $is_pending_quote ? __( 'My Offer', 'dokan' ) : __( 'Your Offer', 'dokan' );

$store       = dokan_get_store_info( $store_id );
$store_name  = $store['store_name'] ?? '';
$store_phone = $store['phone'] ?? '';

$user_data   = get_userdata( $store_id );
$store_email = $user_data->user_email ?? '';

$customer_info         = ! empty( $quote->customer_info ) ? maybe_unserialize( $quote->customer_info ) : [];
$quoted_user_name      = $customer_info['name_field'] ?? '';
$quoted_user_city      = $customer_info['city'] ?? '';
$quoted_user_email     = $customer_info['email_field'] ?? '';
$quoted_user_phone     = $customer_info['phone_field'] ?? '';
$quoted_user_state     = $customer_info['state_address'] ?? '';
$quoted_user_addr_1    = $customer_info['addr_line_1'] ?? '';
$quoted_user_addr_2    = $customer_info['addr_line_2'] ?? '';
$quoted_user_country   = $customer_info['country'] ?? '';
$quoted_customer_offer = $customer_info['customer_offers'] ?? [];

$country_obj = new WC_Countries();
$countries   = $country_obj->get_allowed_countries();
$states      = $country_obj->states ?? [];

$country_name  = $countries[ $quoted_user_country ] ?? '';
$customer_msg  = $customer_info['customer_additional_msg'] ?? '';
$country_state = $states[ $quoted_user_country ][ $quoted_user_state ] ?? '';
$expected_date = $quote->expected_date ?? '';
$quote_user_id = $quote->user_id ?? 0;
$shipping_cost = $quote->shipping_cost ?? 0;
$colspan       = $account_page ? ( $quoted_customer_offer ? 2 : 3 ) : ( ! $quoted_customer_offer ? 3 : 2 );

$hide_vendor_settings = dokan_get_option( 'hide_vendor_info', 'dokan_appearance' );
$hide_vendor_email    = ! empty( $hide_vendor_settings['email'] );
$hide_vendor_phone    = ! empty( $hide_vendor_settings['phone'] );
?>

<table class='shop_table shop_table_responsive cart order_details quote_details dokan-table order-items'>
    <thead>
    <tr>
        <th class='product-thumbnail'>&nbsp;</th>
        <th colspan="<?php echo esc_attr( $hide_contents ? 5 : $colspan ); ?>" class='product-name<?php echo esc_attr( $hide_contents ? ' hide-product-price' : '' ); ?>'>
            <?php esc_html_e( 'Product Name', 'dokan' ); ?>
        </th>
        <?php if ( ! $hide_contents ) : ?>
            <th class="product-price"><?php esc_html_e( 'Original Price', 'dokan' ); ?></th>
            <?php if ( $quoted_customer_offer ) : ?>
                <th class="product-price"><?php echo esc_html( $offer_label ); ?></th>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ( ! $account_page ) : ?>
            <th class="product-price">
                <?php echo esc_html( $vendor_offer_label ); ?>
            </th>
			<?php
        else :
            echo ! $is_pending_quote ? wp_kses_post( '<th class="product-price">' . esc_html__( 'Vendor Offer', 'dokan' ) . '</th>' ) : '';
        endif;
        ?>
        <th class="product-quantity">
            <?php esc_html_e( 'Qty.', 'dokan' ); ?>
        </th>
        <?php if ( ! $hide_contents ) : ?>
            <th class="product-subtotal"><?php esc_html_e( 'Total', 'dokan' ); ?></th>
        <?php endif; ?>
    </tr>
    </thead>
    <tbody>
    <?php
    $quote_subtotal  = 0;
    $offered_total   = $shipping_cost;
    $customer_offers = array_values( (array) $quoted_customer_offer );
    foreach ( $quote_details as $item_key => $quote_item ) {
        $_product          = wc_get_product( $quote_item->product_id );
        $price             = $_product->get_price();
        $offer_price       = isset( $quote_item->offer_price ) ? floatval( $quote_item->offer_price ) : $price;
        $product_permalink = $_product->is_visible() ? $_product->get_permalink() : '';
        ?>
        <tr class="product-row">
            <td class='product-thumbnail'>
                <?php
                $thumbnail = $_product->get_image();

                if ( ! $product_permalink ) {
                    echo wp_kses_post( $thumbnail ); // phpcs:ignore WordPress.Security.EscapeOutput
                } else {
                    printf( '<a href="%s">%s</a>', esc_url( $product_permalink ), wp_kses_post( $thumbnail ) ); // phpcs:ignore WordPress.Security.EscapeOutput
                }
                ?>
            </td>
            <td colspan="<?php echo esc_attr( $hide_contents ? 5 : $colspan ); ?>" data-title="<?php esc_attr_e( 'Product Name', 'dokan' ); ?>" class='product-name<?php echo esc_attr( $hide_contents ? ' hide-product-price' : '' ); ?>'>
                <?php
                if ( ! $product_permalink ) {
                    echo wp_kses_post( $_product->get_name() . '&nbsp;' );
                } else {
                    echo wp_kses_post( sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $_product->get_name() ) );
                }
                ?>
                <br>
                <?php
                echo '<div><strong>' . esc_html__( 'SKU:', 'dokan' ) . '</strong> ' . esc_html( $_product->get_sku() ) . '</div>';

                $product_meta['product_id'] = $quote_item->product_id;
                $product_meta['data']       = $_product;
                if ( $_product->is_type( 'variation' ) ) {
                    $product_meta['variation'] = $_product->get_formatted_name();
                }
                // Meta data.
                echo wp_kses_post( wc_get_formatted_cart_item_data( $product_meta ) ); // phpcs:ignore WordPress.Security.EscapeOutput

                // Backorder notification.
                if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $quote_item->quantity ) ) {
                    echo wp_kses_post( '<p class="backorder_notification">' . esc_html__( 'Available on backorder', 'dokan' ) . '</p>' );
                }
                ?>
            </td>

            <?php if ( ! $hide_contents ) : ?>
                <td data-title="<?php esc_attr_e( 'Original Price', 'dokan' ); ?>">
                    <?php echo wp_kses_post( wc_price( $price ) ); ?>
                </td>
                <?php if ( $quoted_customer_offer ) : ?>
                    <td data-title="<?php echo esc_attr( $offer_label ); ?>">
                        <?php
                        $customer_offer = $customer_offers[ $item_key ] ?? 0;
                        echo wp_kses_post( '<strong class="product-price">' . wc_price( $customer_offers[ $item_key ] ) . '</strong>' );
                        ?>
                    </td>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ( ! $account_page ) : ?>
                <td data-title="<?php echo esc_attr( $vendor_offer_label ); ?>">
                    <?php if ( ! $is_pending_quote ) : ?>
                        <strong class="product-price"><?php echo wp_kses_post( wc_price( $offer_price ) ); ?></strong>
                    <?php else : ?>
                        <input min="0" type='number' class='input-text my-offer offered-price-input text' step='any' data-offer_price="<?php echo esc_attr( $offer_price ); ?>" name="offer_price[<?php echo esc_attr( $quote_item->product_id ); ?>]" value="<?php echo esc_attr( $offer_price ); ?>" />
                    <?php endif; ?>
                </td>
                <?php
            else :
                echo ! $is_pending_quote ? wp_kses_post( '<td class="product-price"><strong class="product-price">' . wc_price( $offer_price ) . '</strong></td>' ) : '';
            endif;
			?>

            <td data-title="<?php esc_attr_e( 'Qty.', 'dokan' ); ?>">
                <?php
                $qty_display    = $quote_item->quantity;
                $offered_total += ( $offer_price * $qty_display );

                if ( $_product->is_sold_individually() ) :
                    printf( '<input class="qty" type="hidden" readonly name="quote_qty[%s]" value="1" />', $quote_item->id );
                else :
                    echo '<input max="' . $_product->get_max_purchase_quantity() . '" min="0" class="qty" type="hidden" name="quote_qty[' . $quote_item->product_id . ']" value="' . $quote_item->quantity . '" />';
                    echo wp_kses_post( '<strong class="product-quantity">' . sprintf( '&nbsp;%s', $qty_display ) . '</strong>' );
                endif;
                ?>
            </td>
            <?php if ( ! $hide_contents ) : ?>
                <td data-title="<?php esc_attr_e( 'Total', 'dokan' ); ?>" class="total">
                    <?php echo wp_kses_post( wc_price( $offer_price * $qty_display ) ); ?>
                </td>
            <?php endif; ?>
        </tr>
        <?php
    }
    ?>
    <tr id="shipping-info">
        <td colspan="<?php echo esc_attr( $account_page && $is_pending_quote ? 4 : 6 ); ?>">
            <label for="shipping-cost">
                <strong><?php esc_html_e( 'Shipping Cost', 'dokan' ); ?></strong>
            </label>
        </td>
        <td colspan="<?php echo esc_attr( $account_page && $is_pending_quote ? 4 : 2 ); ?>">
            <?php if ( $account_page && $is_pending_quote ) : ?>
                <strong id="shipping-cost">
                    <span class="shipping-price">
                        <?php
                        printf(
                            /* translators: 1) Default shipping value. */
                            esc_html__( '%1$s', 'dokan' ),
                            wc_price( 0.00 )
                        );
                        ?>
                    </span>
                    <p class="shipping-warning-info">
                        <?php esc_html_e( '(shipping cost can be updated after vendor reviewed)', 'dokan' ); ?>
                    </p>
                </strong>
            <?php elseif ( ! $account_page && $is_pending_quote ) : ?>
                <input id="shipping-cost" min="0" type="text" step="0.10" autocomplete="off" data-shipping_cost="<?php echo esc_attr( $shipping_cost ); ?>" name="shipping_cost" value="<?php echo esc_attr( $shipping_cost ); ?>" />
            <?php else : ?>
                <strong id="shipping-cost"><?php echo wp_kses_post( wc_price( $shipping_cost ) ); ?></strong>
            <?php endif; ?>
        </td>
    </tr>
    <tr id="quote-total">
        <td colspan="<?php echo esc_attr( $account_page && $is_pending_quote ? 4 : 6 ); ?>">
            <strong id="total-label"><?php esc_html_e( 'Total', 'dokan' ); ?></strong>
        </td>
        <td colspan="<?php echo esc_attr( $account_page && $is_pending_quote ? 4 : 2 ); ?>">
            <?php if ( $account_page && $is_pending_quote ) : ?>
                <strong id="total" class="total-amount">
                    <?php
                    printf(
                        /* translators: 1) Default shipping value. */
                        __( '%1$s', 'dokan' ),
                        ! $hide_contents ? wp_kses_post( wc_price( $offered_total ) ) : wc_price( 0.00 )
                    );
                    ?>
                </strong>
            <?php else : ?>
                <strong id="total" class="total-amount">
                    <?php echo wp_kses_post( wc_price( $offered_total ) ); ?>
                </strong>
            <?php endif; ?>
        </td>
    </tr>
    </tbody>
</table>

<div id="quote-details-container">
    <?php if ( $account_page ) : ?>
        <section class="store-details">
            <div class="heading">
                <svg width="23" height="20" viewBox="0 0 23 20" fill="none">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M21.3438 6.56398C21.3426 6.59615 21.3408 6.62814 21.3383 6.65992C21.338 6.66296 21.3378 6.66599 21.3375 6.66902C21.2848 7.31247 20.9662 7.87416 20.5029 8.22164C20.4956 8.22708 20.4883 8.23246 20.481 8.23779C20.2655 8.39474 20.0197 8.50581 19.7555 8.55806C19.7381 8.56151 19.7205 8.5647 19.7029 8.56764C19.6074 8.58355 19.5096 8.5918 19.4102 8.5918C18.8017 8.5918 18.2579 8.28344 17.9031 7.80246C17.7703 7.62234 17.6639 7.418 17.5907 7.19658C17.5691 7.1314 17.5504 7.06473 17.5347 6.99676C17.5312 6.98154 17.5279 6.96625 17.5246 6.9509C17.4932 6.80018 17.4766 6.64337 17.4766 6.48242C17.4766 6.0943 17.1616 5.7793 16.7734 5.7793C16.3853 5.7793 16.0703 6.0943 16.0703 6.48242C16.0703 6.64337 16.0537 6.80018 16.0222 6.9509C16.019 6.96625 16.0157 6.98154 16.0122 6.99676C15.9965 7.06473 15.9778 7.1314 15.9562 7.19658C15.8829 7.418 15.7766 7.62234 15.6437 7.80246C15.289 8.28344 14.7452 8.5918 14.1367 8.5918C13.5283 8.5918 12.9844 8.28344 12.6297 7.80246C12.4968 7.62234 12.3905 7.418 12.3172 7.19658C12.2957 7.1314 12.277 7.06473 12.2613 6.99676C12.2578 6.98154 12.2544 6.96625 12.2512 6.9509C12.2197 6.80018 12.2031 6.64337 12.2031 6.48242C12.2031 6.0943 11.8881 5.7793 11.5 5.7793C11.1119 5.7793 10.7969 6.0943 10.7969 6.48242C10.7969 6.64337 10.7803 6.80018 10.7488 6.9509C10.7456 6.96625 10.7422 6.98154 10.7387 6.99676C10.723 7.06473 10.7043 7.1314 10.6828 7.19658C10.6095 7.418 10.5032 7.62234 10.3703 7.80246C10.0156 8.28344 9.47172 8.5918 8.86328 8.5918C8.25484 8.5918 7.711 8.28344 7.35625 7.80246C7.2234 7.62234 7.11707 7.418 7.0438 7.19659C7.02223 7.1314 7.00352 7.06473 6.98784 6.99676C6.98433 6.98154 6.98098 6.96625 6.97777 6.9509C6.94631 6.80018 6.92969 6.64337 6.92969 6.48242C6.92969 6.0943 6.61469 5.7793 6.22656 5.7793C5.83844 5.7793 5.52344 6.0943 5.52344 6.48242C5.52344 6.64337 5.50682 6.80018 5.47535 6.9509C5.47215 6.96625 5.46879 6.98154 5.46528 6.99676C5.44961 7.06473 5.4309 7.1314 5.40933 7.19659C5.33605 7.418 5.22972 7.62234 5.09687 7.80246C4.74213 8.28344 4.19829 8.5918 3.58984 8.5918C3.49036 8.5918 3.3926 8.58355 3.29711 8.56764C3.27949 8.5647 3.26195 8.56151 3.24449 8.55806C2.98027 8.50581 2.73451 8.39475 2.51909 8.2378C2.51175 8.23246 2.50445 8.22706 2.49718 8.2216C2.03408 7.87417 1.71581 7.31264 1.66376 6.66936C1.66351 6.66633 1.66327 6.66329 1.66304 6.66025C1.66058 6.62836 1.65879 6.59626 1.65766 6.56398L2.90922 1.56055H20.0922L21.3438 6.56398ZM1.47426 9.19151C0.75918 8.57258 0.288863 7.65179 0.252298 6.61317C0.250771 6.56979 0.25 6.5262 0.25 6.48242C0.25 6.42477 0.257031 6.36711 0.271094 6.31227L1.67734 0.687266C1.75469 0.373672 2.03734 0.154297 2.35938 0.154297H20.6406C20.9627 0.154297 21.2453 0.373672 21.3227 0.687266L22.7289 6.31227C22.743 6.36711 22.75 6.42477 22.75 6.48242C22.75 6.52677 22.7492 6.57093 22.7476 6.61488C22.7106 7.65279 22.2404 8.57292 21.5257 9.19151C21.4667 9.24261 21.406 9.29164 21.3438 9.33852V19.1387C21.3438 19.5268 21.0287 19.8418 20.6406 19.8418H13.6094C13.2212 19.8418 12.9062 19.5268 12.9062 19.1387V14.2168H10.0938V19.1387C10.0938 19.5268 9.77875 19.8418 9.39062 19.8418H2.35938C1.97125 19.8418 1.65625 19.5268 1.65625 19.1387V9.33852C1.59399 9.29164 1.53329 9.24261 1.47426 9.19151ZM3.0625 18.4355H8.6875V13.5137C8.6875 13.1255 9.0025 12.8105 9.39062 12.8105H13.6094C13.9975 12.8105 14.3125 13.1255 14.3125 13.5137V18.4355H19.9375V9.9418C19.8771 9.95211 19.8165 9.96209 19.7555 9.9707C19.642 9.98674 19.5273 9.99805 19.4102 9.99805C19.2062 9.99805 19.0066 9.97875 18.8128 9.9418C18.5235 9.88665 18.2472 9.79217 17.989 9.66386C17.5665 9.45389 17.1923 9.1533 16.8891 8.78608C16.8493 8.73791 16.8107 8.6886 16.7734 8.6382C16.7362 8.6886 16.6976 8.73791 16.6578 8.78608C16.3545 9.1533 15.9804 9.45389 15.5579 9.66386C15.1266 9.87815 14.645 9.99805 14.1367 9.99805C13.6285 9.99805 13.1468 9.87815 12.7156 9.66386C12.293 9.45389 11.9189 9.1533 11.6156 8.78608C11.5758 8.73791 11.5373 8.6886 11.5 8.6382C11.4627 8.6886 11.4242 8.73791 11.3844 8.78608C11.0811 9.1533 10.707 9.45389 10.2844 9.66386C9.85319 9.87815 9.37152 9.99805 8.86328 9.99805C8.35504 9.99805 7.87337 9.87815 7.44213 9.66386C7.01959 9.45389 6.64545 9.1533 6.34219 8.78608C6.30241 8.73791 6.26385 8.6886 6.22656 8.6382C6.18928 8.6886 6.15071 8.73791 6.11094 8.78608C5.80767 9.1533 5.43354 9.45389 5.01099 9.66386C4.75277 9.79217 4.47646 9.88665 4.18719 9.9418C3.99338 9.97875 3.79375 9.99805 3.58984 9.99805C3.47273 9.99805 3.358 9.98674 3.24449 9.9707C3.18353 9.96209 3.12293 9.95211 3.0625 9.9418V18.4355Z" fill="#828282"/>
                </svg>
                <h3><?php esc_html_e( 'Store Contact', 'dokan' ); ?></h3>
            </div>
            <div class="details">
                <div class="store-name">
                    <a href="<?php echo esc_url( dokan_get_store_url( $store_id ) ); ?>"><?php echo esc_html( $store_name ); ?></a>
                </div>
                <?php if ( ! $hide_vendor_email ) : ?>
                    <div class="store-email">
                        <label>
                            <?php
                            /* translators: 1) Store email. */
                            printf( __( 'Email: %1$s', 'dokan' ), $store_email );
                            ?>
                        </label>
                    </div>
                <?php endif; ?>
                <?php if ( ! $hide_vendor_phone ) : ?>
                    <div class="store-phone">
                        <label>
                            <?php
                            /* translators: 1) Store email. */
                            printf( __( 'Phone: %1$s', 'dokan' ), $store_phone );
                            ?>
                        </label>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        <section class="quotation-details">
            <div class="heading">
                <svg width="19" height="22" viewBox="0 0 19 22" fill="none">
                    <path d="M9.06956 10.4872C10.4973 10.4872 11.7335 10.0036 12.7438 9.04937C13.7538 8.09533 14.2661 6.92799 14.2661 5.57939C14.2661 4.23125 13.754 3.06376 12.7437 2.1094C11.7333 1.15551 10.4972 0.671875 9.06956 0.671875C7.64163 0.671875 6.40563 1.15551 5.39546 2.10955C4.38529 3.0636 3.87305 4.2311 3.87305 5.57939C3.87305 6.92799 4.38529 8.09548 5.39546 9.04953C6.40596 10.0034 7.64213 10.4872 9.06956 10.4872ZM6.28958 2.95385C7.0647 2.2218 7.97397 1.86595 9.06956 1.86595C10.165 1.86595 11.0744 2.2218 11.8497 2.95385C12.6248 3.68606 13.0018 4.54497 13.0018 5.57939C13.0018 6.61412 12.6248 7.47287 11.8497 8.20508C11.0744 8.93729 10.165 9.29313 9.06956 9.29313C7.9743 9.29313 7.06503 8.93713 6.28958 8.20508C5.5143 7.47303 5.13736 6.61412 5.13736 5.57939C5.13736 4.54497 5.5143 3.68606 6.28958 2.95385Z" fill="#828282"/>
                    <path d="M18.1624 16.3395C18.1333 15.9424 18.0744 15.5093 17.9876 15.052C17.9001 14.5912 17.7873 14.1556 17.6523 13.7575C17.5127 13.346 17.3233 12.9396 17.0887 12.5502C16.8456 12.146 16.5599 11.794 16.2392 11.5044C15.9039 11.2014 15.4934 10.9578 15.0187 10.7801C14.5456 10.6034 14.0214 10.5138 13.4605 10.5138C13.2403 10.5138 13.0273 10.5992 12.616 10.8521C12.3628 11.008 12.0667 11.1884 11.7362 11.3878C11.4535 11.5579 11.0707 11.7172 10.5978 11.8615C10.1364 12.0025 9.66793 12.074 9.2054 12.074C8.74319 12.074 8.27473 12.0025 7.81302 11.8615C7.34061 11.7174 6.95758 11.558 6.67545 11.388C6.34805 11.1904 6.05177 11.0101 5.79482 10.8519C5.38381 10.599 5.17081 10.5137 4.95057 10.5137C4.38961 10.5137 3.86551 10.6034 3.3926 10.7803C2.91822 10.9577 2.50753 11.2013 2.17191 11.5046C1.85126 11.7943 1.56551 12.1461 1.32256 12.5502C1.08833 12.9396 0.898704 13.3458 0.759121 13.7576C0.624311 14.1558 0.511558 14.5912 0.423989 15.052C0.337079 15.5087 0.278315 15.942 0.249181 16.3399C0.22054 16.7291 0.206055 17.134 0.206055 17.5432C0.206055 18.6068 0.564066 19.4679 1.27005 20.103C1.96731 20.7296 2.88974 21.0474 4.01184 21.0474H14.4003C15.522 21.0474 16.4445 20.7296 17.1419 20.103C17.848 19.4684 18.2061 18.607 18.2061 17.543C18.2059 17.1325 18.1912 16.7275 18.1624 16.3395ZM16.2702 19.2378C15.8094 19.652 15.1978 19.8533 14.4001 19.8533H4.01184C3.21401 19.8533 2.60235 19.652 2.14179 19.238C1.68995 18.8318 1.47037 18.2773 1.47037 17.5432C1.47037 17.1614 1.4837 16.7844 1.51037 16.4225C1.53638 16.0674 1.58954 15.6774 1.66839 15.2629C1.74625 14.8536 1.84534 14.4695 1.96319 14.1217C2.07627 13.7882 2.23051 13.4581 2.42178 13.14C2.60432 12.8368 2.81435 12.5768 3.04612 12.3672C3.2629 12.1712 3.53614 12.0107 3.8581 11.8904C4.15587 11.7791 4.49051 11.7182 4.85379 11.709C4.89806 11.7312 4.97691 11.7737 5.10464 11.8523C5.36455 12.0123 5.66413 12.1948 5.99531 12.3946C6.36863 12.6194 6.8496 12.8224 7.42423 12.9976C8.01169 13.177 8.61085 13.2681 9.20556 13.2681C9.80027 13.2681 10.3996 13.177 10.9867 12.9977C11.5619 12.8222 12.0427 12.6194 12.4165 12.3943C12.7554 12.1897 13.0466 12.0124 13.3065 11.8523C13.4342 11.7738 13.5131 11.7312 13.5573 11.709C13.9208 11.7182 14.2554 11.7791 14.5533 11.8904C14.8751 12.0107 15.1484 12.1713 15.3652 12.3672C15.5969 12.5766 15.807 12.8367 15.9895 13.1401C16.1809 13.4581 16.3353 13.7884 16.4483 14.1216C16.5663 14.4698 16.6655 14.8538 16.7432 15.2628C16.8219 15.678 16.8752 16.0682 16.9012 16.4226V16.423C16.9281 16.7835 16.9416 17.1603 16.9417 17.5432C16.9416 18.2774 16.722 18.8318 16.2702 19.2378Z" fill="#828282"/>
                </svg>
                <h3><?php esc_html_e( 'Quotation Contact', 'dokan' ); ?></h3>
            </div>
            <div class="details">
                <div class="quote-user-name store-name">
                    <strong><?php echo esc_html( $quoted_user_name ); ?></strong>
                </div>
                <div class="store-email">
                    <label>
                        <?php
                        /* translators: 1) Store email. */
                        printf( __( 'Email: %1$s', 'dokan' ), $quoted_user_email );
                        ?>
                    </label>
                </div>
                <div class="store-phone">
                    <label>
                        <?php
                        /* translators: 1) Store email. */
                        printf( __( 'Phone: %1$s', 'dokan' ), $quoted_user_phone );
                        ?>
                    </label>
                </div>
            </div>
        </section>
    <?php endif; ?>
    <?php if ( $country_state && $country_name ) : ?>
        <section class="shipping-details">
            <div class="heading">
                <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
                    <path d="M22.0691 12.181C22.0691 18.6564 13.7435 24.2068 13.7435 24.2068C13.7435 24.2068 5.41797 18.6564 5.41797 12.181C5.41797 9.97294 6.29512 7.85531 7.85646 6.29396C9.41781 4.73262 11.5354 3.85547 13.7435 3.85547C15.9516 3.85547 18.0692 4.73262 19.6306 6.29396C21.1919 7.85531 22.0691 9.97294 22.0691 12.181Z" stroke="#828282" stroke-width="2.7" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M13.7441 15.1562C15.6081 15.1562 17.1191 13.6452 17.1191 11.7812C17.1191 9.91729 15.6081 8.40625 13.7441 8.40625C11.8802 8.40625 10.3691 9.91729 10.3691 11.7812C10.3691 13.6452 11.8802 15.1562 13.7441 15.1562Z" stroke="#828282" stroke-width="2.7" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <div class="text-contents">
                    <h3><?php esc_html_e( 'Shipping Details', 'dokan' ); ?></h3>
                    <p><?php esc_html_e( '(The information needs to assume your distance from our shop)', 'dokan' ); ?></p>
                </div>
            </div>
            <div class="details">
                <div class="quote-user-name store-name">
                    <strong>
                        <?php
                        /* translators: 1) State name, 2) Country name */
                        printf( '%1$s, %2$s', esc_html( $country_state ), esc_html( $country_name ) );
                        ?>
                    </strong>
                </div>
                <?php if ( $quoted_user_addr_1 && $quoted_user_addr_2 && $quoted_user_city ) : ?>
                    <div class="store-email">
                        <label>
                            <?php
                            /* translators: 1) User address 1, 2) User address 2, 3) User city */
                            printf( esc_html( '%1$s, %2$s, %3$s' ), $quoted_user_addr_1, $quoted_user_addr_2, $quoted_user_city );
                            ?>
                        </label>
                    </div>
                <?php endif; ?>
                <?php if ( $expected_date ) : ?>
                    <div class="store-phone">
                        <label class="expected-date">
                            <svg width="18" height="19" viewBox="0 0 18 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M13.7162 4.30859H4.4959C3.76844 4.30859 3.17871 4.89832 3.17871 5.62578V14.8461C3.17871 15.5736 3.76844 16.1633 4.4959 16.1633H13.7162C14.4437 16.1633 15.0334 15.5736 15.0334 14.8461V5.62578C15.0334 4.89832 14.4437 4.30859 13.7162 4.30859Z" stroke="#828282" stroke-width="1.58062" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M11.7412 2.99219V5.62656" stroke="#828282" stroke-width="1.58062" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M6.47168 2.99219V5.62656" stroke="#828282" stroke-width="1.58062" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M3.17871 8.26172H15.0334" stroke="#828282" stroke-width="1.58062" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>

                            <?php
                            /* translators: 1) Store email. */
                            printf( __( 'Expected Shipping Date: %1$s', 'dokan' ), dokan_current_datetime()->setTimestamp( $expected_date )->format( 'd F Y' ) );
                            ?>
                        </label>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>
    <?php if ( $customer_msg || $vendor_msg ) : ?>
        <section class="additional-msg">
            <div class="heading">
                <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
                    <path d="M23.8691 17.752C23.8691 18.3487 23.6321 18.921 23.2101 19.3429C22.7882 19.7649 22.2159 20.002 21.6191 20.002H8.11914L3.61914 24.502V6.50195C3.61914 5.90522 3.85619 5.33292 4.27815 4.91096C4.70011 4.48901 5.2724 4.25195 5.86914 4.25195H21.6191C22.2159 4.25195 22.7882 4.48901 23.2101 4.91096C23.6321 5.33292 23.8691 5.90522 23.8691 6.50195V17.752Z" stroke="#828282" stroke-width="3.17647" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h3><?php esc_html_e( 'Additional Message', 'dokan' ); ?></h3>
            </div>
            <div class="details">
                <?php if ( $customer_msg ) : ?>
                    <div id="customer-additional-msg">
                        <div class="customer-profile"><?php echo get_avatar( $quote_user_id ); ?></div>
                        <div class="customer-content">
                            <div class="customer-name">
                                <label><?php echo esc_html( $quoted_user_name ); ?></label>
                            </div>
                            <div class="customer-msg">
                                <p><?php echo esc_html( $customer_msg ); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ( $customer_msg && $vendor_msg ) : ?>
                    <hr />
                <?php endif; ?>
                <?php if ( $vendor_msg ) : ?>
                    <div id="vendor-additional-msg">
                        <div class="customer-profile"><?php echo get_avatar( $store_id ); ?></div>
                        <div class="customer-content">
                            <div class="customer-name">
                                <label><?php echo esc_html( $store_name ); ?></label>
                            </div>
                            <div class="customer-msg">
                                <p><?php echo esc_html( $vendor_msg ); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>
    <?php if ( ! $account_page && Helper::is_qoute_status( 'pending', $quote ) ) : ?>
        <div class="vendor-additional-msg">
            <label for="additional-msg">
                <strong><?php esc_html_e( 'Reply', 'dokan' ); ?></strong>
            </label>
            <textarea name="vendor_additional_msg" id="additional-msg" cols="10" rows="2" placeholder='<?php esc_html_e( 'Write here', 'dokan' ); ?>'></textarea>
        </div>
    <?php endif; ?>

    <?php wp_nonce_field( 'save_dokan_quote_action', 'dokan_quote_nonce' ); ?>
</div>
