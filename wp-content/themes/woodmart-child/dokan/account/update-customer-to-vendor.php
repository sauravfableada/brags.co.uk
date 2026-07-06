<?php
/**
 * Dokan Update Customer to Vendor Template.
 *
 * @since 3.7.21
 *
 * @var int    $user_id
 * @var string $first_name
 * @var string $last_name
 * @var string $shop_url
 * @var string $show_toc
 * @var string $shop_name
 * @var string $phone
 * @var string $toc_page_id
 */

$home_url         = untrailingslashit( home_url() );
$custom_store_url = dokan_get_option( 'custom_store_url', 'dokan_general', 'store' );
?>

<h2><?php esc_html_e( 'Update account to Vendor', 'dokan-lite' ); ?></h2>
<p class="dokan-new-notice">
            <?php
            // translators: %s is the link to the seller policy.
            // printf(
            //     esc_html__( ' Please note, upon completion of this form, your request for a Seller Account will be reviewed by our Team. You will not be able to Buy or Sell Products on Brags whilst your Account is being reviewed. We may contact you for further information.', 'dokan-lite' ),
            // );
            printf(
                '<strong class="error-notice">%s</strong>',
                esc_html__( ' Please note, upon completion of this form, your request for a Seller Account will be reviewed by our Team. You will not be able to Buy or Sell Products on Brags whilst your Account is being reviewed. We may contact you for further information.', 'dokan-lite' )
            );
           ?>
        </p>
<form method="post" action="" class="update-customer-to-vendor register">
    <div class="dokan-become-seller">
        <div class="split-row form-row-wide">
            <p class="form-row form-group">
                <label for="first-name"><?php esc_html_e( 'First Name', 'dokan-lite' ); ?> <span class="required">*</span></label>
                <input type="text" class="input-text form-control" name="fname" id="first-name" value="<?php echo esc_attr( $first_name ); ?>" required="required" />
            </p>
            <p class="form-row form-group">
                <label for="last-name"><?php esc_html_e( 'Last Name', 'dokan-lite' ); ?> <span class="required">*</span></label>
                <input type="text" class="input-text form-control" name="lname" id="last-name" value="<?php echo esc_attr( $last_name ); ?>" required="required" />
            </p>
        </div>

        <p class="form-row form-group form-row-wide">
            <label for="company-name"><?php esc_html_e( 'Shop Name', 'dokan-lite' ); ?> <span class="required">*</span></label>
            <input type="text" class="input-text form-control" name="shopname" id="company-name" value="<?php echo esc_attr( $shop_name ); ?>" required="required" />
        </p>

        <p class="form-row form-group form-row-wide">
            <label for="seller-url" class="pull-left"><?php esc_html_e( 'Shop URL', 'dokan-lite' ); ?> <span class="required">*</span></label>
            <strong id="url-alart-mgs" class="pull-right"></strong>
            <input type="text" class="input-text form-control" name="shopurl" id="seller-url" value="<?php echo esc_attr( $shop_url ); ?>" required="required" />
            <small><?php echo esc_url( $home_url . '/' . $custom_store_url ) . '/'; ?><strong id="url-alart"></strong></small>
        </p>

        <p class="form-row form-group form-row-wide">
            <label for="shop-phone"><?php esc_html_e( 'Phone Number', 'dokan-lite' ); ?><span class="required">*</span></label>
            <input type="text" class="input-text form-control" name="phone" id="shop-phone" value="<?php echo esc_attr( $phone ); ?>" required="required" />
        </p>




        <?php
        /**
         * Hook for adding fields after vendor migration.
         *
         * @since 3.7.21
         */
        do_action( 'dokan_after_seller_migration_fields' );

        if ( $show_toc === 'on' && ! empty( $toc_page_id ) ) {
            $toc_page_url = get_permalink( $toc_page_id );
            ?>
            <!-- <p class="form-row form-group form-row-wide">
                <input class="tc_check_box" type="checkbox" id="tc_agree" name="tc_agree" required="required">
                <label style="display: inline" for="tc_agree">
                    <?php
                    // $tc_link = sprintf( '<a class="new-seller-migration" target="_blank" href="%1$s">%2$s</a>', esc_url( $toc_page_url ), __( 'Brags Seller Policy', 'dokan-lite' ) );
                    // // translators: 1. Terms and conditions of agreement link.
                    // printf( __( 'I have read and agree to %1$s.', 'dokan-lite' ), $tc_link );
                    ?>

                    <style>
                        a.new-seller-migration {
    text-decoration: underline;
    color: #5c879b;
}
                    </style>
                </label>
            </p> -->

            <?php
                // Get pages by their slugs
                $terms_page = get_page_by_path( 'terms-and-conditions' );
                $policy_page = get_page_by_path( 'seller-policy' );

                $terms_url  = $terms_page ? get_permalink( $terms_page->ID ) : '#';
                $policy_url = $policy_page ? get_permalink( $policy_page->ID ) : '#';
            ?>

            <p class="form-row form-group form-row-wide">
                <input class="tc_check_box" type="checkbox" id="tc_agree" name="tc_agree" required="required" data-gtm-form-interact-field-id="3">
                <label style="display: inline" for="tc_agree">
                    I confirm that I have read and agree to the 
                    <a target="_blank" href="<?php echo esc_url( $terms_url ); ?>">Terms &amp; Conditions</a> and 
                    <a target="_blank" href="<?php echo esc_url( $policy_url ); ?>">Brags Seller Policy</a>. 
                    I understand that I/my company must hold the legal rights to sell my products in the UK, maintain my own Product Liability Insurance and acknowledge that Brags &amp; Partners Ltd holds no responsibility for the products I choose to sell on Brags.co.uk.
                </label>
            </p>
            <p class="form-row form-group form-row-wide">
                <input class="tc_check_box" type="checkbox" id="custom_filed" name="custom_filed" required="required">
                <label for="custom_filed" style="margin-left: 25px; margin-top: -20px;">
                    <?php _e('I confirm that I will only sell products on Brags.co.uk to customers based in the United Kingdom only and will offer UK-wide shipping.', 'woocommerce'); ?>
                </label>
                <span class="error-message" style="color: red; display: none;"></span>
            </p>
            <p class="form-row form-group form-row-wide">
                <input class="tc_check_box" type="checkbox" id="brand_ownership_tc_agree" name="brand_ownership_tc_agree" require="require" <?php checked($is_checked, 'yes');  ?>>
                <label for="brand_ownership_tc_agree" style="margin-left: 25px; margin-top: -20px;">
                    <?php _e('I understand that Brags & Partners Ltd takes a 10% +VAT (12% inc VAT) commission on each successful product sale. I also understand that successful sales through the ‘Braggers’ Programme may result in an additional 5% commission to referral partners. By signing up to sell on Brags, I give Brags & Partners permission to promote my products through their own communication channels, as well as other online selling platforms such as Google Shopping, Facebook Shop, Instagram Shop, TikTok Shop, and more.', 'woocommerce'); ?>
                </label>
                <span class="error-message" style="color: red; display: none;"></span>
            </p>
        <?php } ?>


        <p class="form-row">
            <?php wp_nonce_field( 'account_migration', 'dokan_nonce' ); ?>
            <input type="hidden" name="user_id" value="<?php echo esc_attr( $user_id ); ?>">
            <input type="submit" class="dokan-btn dokan-btn-default" name="dokan_migration" value="<?php esc_attr_e( 'Become a Vendor', 'dokan-lite' ); ?>" />
        </p>


        

    </div>
</form>

<script>
    (function($) {
        // Sanitize phone input characters.
        $( 'form.update-customer-to-vendor.register input#shop-phone' ).on( 'keydown', dokan_sanitize_phone_number );
    })(jQuery);
</script>