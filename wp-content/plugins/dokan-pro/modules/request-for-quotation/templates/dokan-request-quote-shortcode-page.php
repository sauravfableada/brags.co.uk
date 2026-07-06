<?php
defined( 'ABSPATH' ) || exit;

if ( ! empty( $quotes ) ) :
    $user_id   = get_current_user_id();
    $user_meta = get_user_meta( $user_id );
    ?>
    <div class="woocommerce">
        <div class="woocommerce-notices-wrapper"></div>
        <div class="store-info">
            <strong>
                <?php
                /* translators: %s: Quoted store name. */
                printf( __( 'Store: %s', 'dokan' ), $store_name );
                ?>
            </strong>
        </div>
        <form class="woocommerce-cart-form dokan-quote-form" method="post" enctype="multipart/form-data">

            <?php
            dokan_get_template_part(
                'quote-table', '', [
                    'quotes'              => $quotes,
                    'hide_price'          => $applicable_quote_rule,
                    'request_quote_table' => true,
                ]
            );
            ?>
            <div class="quote_fields">
                <div class="fields-heading">
                    <div class="icon">
                        <svg width="19" height="22" viewBox="0 0 19 22" fill="none">
                            <path d="M9.06956 10.4872C10.4973 10.4872 11.7335 10.0036 12.7438 9.04937C13.7538 8.09533 14.2661 6.92799 14.2661 5.57939C14.2661 4.23125 13.754 3.06376 12.7437 2.1094C11.7333 1.15551 10.4972 0.671875 9.06956 0.671875C7.64163 0.671875 6.40563 1.15551 5.39546 2.10955C4.38529 3.0636 3.87305 4.2311 3.87305 5.57939C3.87305 6.92799 4.38529 8.09548 5.39546 9.04953C6.40596 10.0034 7.64213 10.4872 9.06956 10.4872ZM6.28958 2.95385C7.0647 2.2218 7.97397 1.86595 9.06956 1.86595C10.165 1.86595 11.0744 2.2218 11.8497 2.95385C12.6248 3.68606 13.0018 4.54497 13.0018 5.57939C13.0018 6.61412 12.6248 7.47287 11.8497 8.20508C11.0744 8.93729 10.165 9.29313 9.06956 9.29313C7.9743 9.29313 7.06503 8.93713 6.28958 8.20508C5.5143 7.47303 5.13736 6.61412 5.13736 5.57939C5.13736 4.54497 5.5143 3.68606 6.28958 2.95385Z" fill="#828282"/>
                            <path d="M18.1624 16.3395C18.1333 15.9424 18.0744 15.5093 17.9876 15.052C17.9001 14.5912 17.7873 14.1556 17.6523 13.7575C17.5127 13.346 17.3233 12.9396 17.0887 12.5502C16.8456 12.146 16.5599 11.794 16.2392 11.5044C15.9039 11.2014 15.4934 10.9578 15.0187 10.7801C14.5456 10.6034 14.0214 10.5138 13.4605 10.5138C13.2403 10.5138 13.0273 10.5992 12.616 10.8521C12.3628 11.008 12.0667 11.1884 11.7362 11.3878C11.4535 11.5579 11.0707 11.7172 10.5978 11.8615C10.1364 12.0025 9.66793 12.074 9.2054 12.074C8.74319 12.074 8.27473 12.0025 7.81302 11.8615C7.34061 11.7174 6.95758 11.558 6.67545 11.388C6.34805 11.1904 6.05177 11.0101 5.79482 10.8519C5.38381 10.599 5.17081 10.5137 4.95057 10.5137C4.38961 10.5137 3.86551 10.6034 3.3926 10.7803C2.91822 10.9577 2.50753 11.2013 2.17191 11.5046C1.85126 11.7943 1.56551 12.1461 1.32256 12.5502C1.08833 12.9396 0.898704 13.3458 0.759121 13.7576C0.624311 14.1558 0.511558 14.5912 0.423989 15.052C0.337079 15.5087 0.278315 15.942 0.249181 16.3399C0.22054 16.7291 0.206055 17.134 0.206055 17.5432C0.206055 18.6068 0.564066 19.4679 1.27005 20.103C1.96731 20.7296 2.88974 21.0474 4.01184 21.0474H14.4003C15.522 21.0474 16.4445 20.7296 17.1419 20.103C17.848 19.4684 18.2061 18.607 18.2061 17.543C18.2059 17.1325 18.1912 16.7275 18.1624 16.3395ZM16.2702 19.2378C15.8094 19.652 15.1978 19.8533 14.4001 19.8533H4.01184C3.21401 19.8533 2.60235 19.652 2.14179 19.238C1.68995 18.8318 1.47037 18.2773 1.47037 17.5432C1.47037 17.1614 1.4837 16.7844 1.51037 16.4225C1.53638 16.0674 1.58954 15.6774 1.66839 15.2629C1.74625 14.8536 1.84534 14.4695 1.96319 14.1217C2.07627 13.7882 2.23051 13.4581 2.42178 13.14C2.60432 12.8368 2.81435 12.5768 3.04612 12.3672C3.2629 12.1712 3.53614 12.0107 3.8581 11.8904C4.15587 11.7791 4.49051 11.7182 4.85379 11.709C4.89806 11.7312 4.97691 11.7737 5.10464 11.8523C5.36455 12.0123 5.66413 12.1948 5.99531 12.3946C6.36863 12.6194 6.8496 12.8224 7.42423 12.9976C8.01169 13.177 8.61085 13.2681 9.20556 13.2681C9.80027 13.2681 10.3996 13.177 10.9867 12.9977C11.5619 12.8222 12.0427 12.6194 12.4165 12.3943C12.7554 12.1897 13.0466 12.0124 13.3065 11.8523C13.4342 11.7738 13.5131 11.7312 13.5573 11.709C13.9208 11.7182 14.2554 11.7791 14.5533 11.8904C14.8751 12.0107 15.1484 12.1713 15.3652 12.3672C15.5969 12.5766 15.807 12.8367 15.9895 13.1401C16.1809 13.4581 16.3353 13.7884 16.4483 14.1216C16.5663 14.4698 16.6655 14.8538 16.7432 15.2628C16.8219 15.678 16.8752 16.0682 16.9012 16.4226V16.423C16.9281 16.7835 16.9416 17.1603 16.9417 17.5432C16.9416 18.2774 16.722 18.8318 16.2702 19.2378Z" fill="#828282"/>
                        </svg>
                    </div>
                    <div class="heading">
                        <h3><?php esc_html_e( 'Personal Information', 'dokan' ); ?></h3>
                    </div>
                </div>
                <?php
                $first_name = $user_meta['billing_first_name'][0] ?? '';
                $last_name  = $user_meta['billing_last_name'][0] ?? '';

                $full_name  = $first_name . ' ' . $last_name;
                $phone      = $user_meta['billing_phone'][0] ?? '';
                $email      = $user_meta['billing_email'][0] ?? '';

                dokan_get_template_part(
                    'quote-customer-info-fields', '', [
                        'full_name'           => ! empty( $full_name ) ? $full_name : '',
                        'user_email'          => $email,
                        'phone_number'        => $phone,
                        'request_quote_table' => true,
                    ]
                );
                ?>

                <div class="shipping-fields-heading fields-heading">
                    <div class="icon">
                        <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
                            <path d="M22.0691 12.181C22.0691 18.6564 13.7435 24.2068 13.7435 24.2068C13.7435 24.2068 5.41797 18.6564 5.41797 12.181C5.41797 9.97294 6.29512 7.85531 7.85646 6.29396C9.41781 4.73262 11.5354 3.85547 13.7435 3.85547C15.9516 3.85547 18.0692 4.73262 19.6306 6.29396C21.1919 7.85531 22.0691 9.97294 22.0691 12.181Z" stroke="#828282" stroke-width="2.7" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M13.7441 15.1562C15.6081 15.1562 17.1191 13.6452 17.1191 11.7812C17.1191 9.91729 15.6081 8.40625 13.7441 8.40625C11.8802 8.40625 10.3691 9.91729 10.3691 11.7812C10.3691 13.6452 11.8802 15.1562 13.7441 15.1562Z" stroke="#828282" stroke-width="2.7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="heading">
                        <h3><?php esc_html_e( 'Shipping Details', 'dokan' ); ?></h3>
                        <p><?php esc_html_e( '(The information needs to assume your distance from our shop)', 'dokan' ); ?></p>
                    </div>
                </div>
                <?php
                $country_obj = new WC_Countries();
                $countries   = $country_obj->get_allowed_countries();
                $states      = $country_obj->states;

                $city      = $user_meta['billing_city'][0] ?? '';
                $state     = $user_meta['billing_state'][0] ?? '';
                $addr_1    = $user_meta['billing_address_1'][0] ?? '';
                $addr_2    = $user_meta['billing_address_2'][0] ?? '';
                $country   = $user_meta['billing_country'][0] ?? '';
                $post_code = $user_meta['billing_postcode'][0] ?? '';

                dokan_get_template_part(
                    'quote-shipping-info-fields', '', [
                        'city'                => $city,
                        'state'               => $state,
                        'states'              => $states,
                        'country'             => $country,
                        'address_1'           => $addr_1,
                        'address_2'           => $addr_2,
                        'countries'           => $countries,
                        'post_code'           => $post_code,
                        'request_quote_table' => true,
                    ]
                );
                ?>

                <div class="fields-heading">
                    <div class="icon">
                        <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
                            <path d="M23.8691 17.752C23.8691 18.3487 23.6321 18.921 23.2101 19.3429C22.7882 19.7649 22.2159 20.002 21.6191 20.002H8.11914L3.61914 24.502V6.50195C3.61914 5.90522 3.85619 5.33292 4.27815 4.91096C4.70011 4.48901 5.2724 4.25195 5.86914 4.25195H21.6191C22.2159 4.25195 22.7882 4.48901 23.2101 4.91096C23.6321 5.33292 23.8691 5.90522 23.8691 6.50195V17.752Z" stroke="#828282" stroke-width="3.17647" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="heading">
                        <h3><?php esc_html_e( 'Additional Message', 'dokan' ); ?></h3>
                    </div>
                </div>
                <?php
                dokan_get_template_part(
                    'quote-additional-info-fields', '', [
                        'request_quote_table' => true,
                    ]
                );
                ?>

                <div class="form_row">
                    <input name='dokan_quote_save_action' type='hidden' value='dokan_quote_save_action' />
                    <?php wp_nonce_field( 'save_dokan_quote_action', 'dokan_quote_nonce' ); ?>
                    <button type="submit" name="dokan_checkout_place_quote" class="button alt dokan_checkout_place_quote">
                        <?php esc_html_e( 'Place Quotation', 'dokan' ); ?>
                    </button>
                </div>

            </div>
        </form>
    </div>

<?php else : ?>

    <div class="dokan_quote">
        <p class="cart-empty"><?php echo esc_html__( 'Your quote is currently empty.', 'dokan' ); ?></p>
        <p class="return-to-shop"><a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>" class="button wc-backward"><?php echo esc_html__( 'Return To Shop', 'dokan' ); ?></a>
        </p>
    </div>

    <?php
endif;
