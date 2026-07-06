<?php

use WeDevs\DokanPro\Modules\ProductAdvertisement\Helper;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * @since 3.5.0
 *
 * @var int $product_id
 * @var int $vendor_id
 * @var bool $already_advertised
 * @var bool $can_advertise_for_free
 * @var string $expire_date
 * @var float $listing_price
 * @var bool|\DokanPro\Modules\Subscription\SubscriptionPack $subscription_status
 * @var int $remaining_slot if subscription exists, this will get remaining slot form package, otherwise from global settings
 * @var int $subscription_remaining_slot
 * @var int $expires_after_days if subscription exists, this will get remaining slot form package, otherwise from global settings
 * @var int $subscription_expires_after_days
 * @var string $post_status
 * @var string $advertise_active_color
 */
?>
<?php do_action( 'dokan_product_edit_before_product_advertisement', $product_id ); ?>

<style>
    .product-edit-new-container .dokan-proudct-advertisement {
        margin-bottom: 20px;
    }
</style>

<div class="dokan-edit-row dokan-proudct-advertisement dokan-clearfix">
    <div class="dokan-section-heading">
        <h2>
        <span class="fa-stack fa-xs tips">
            <i class="fa fa-circle fa-stack-2x" style="color: <?php echo esc_html( $advertise_active_color ); ?>; font-size: 2em;"></i>
            <i class="fa fa-bullhorn fa-stack-1x fa-inverse" data-fa-transform="shrink-6"></i>
        </span>
            <?php esc_html_e( 'Advertise Product', 'dokan' ); ?>
        </h2>
        <p><?php esc_html_e( 'Manage Advertisement for this product', 'dokan' ); ?></p>
        <a href="#" class="dokan-section-toggle">
            <i class="fas fa-sort-down fa-flip-vertical" aria-hidden="true"></i>
        </a>
        <div class="dokan-clearfix"></div>
    </div>

    <div class="dokan-section-content">
        <?php
        /**
         * Logic flow:
         * 1. Product not published → Show "publish first" message
         * 2. Product already advertised → Show expiry date
         * 3. No slots available (remaining_slot = 0 or false) → Show appropriate message
         * 4. Slots available → Show free or paid advertising option
         */

        // Case 1: Product is not published
        if ( 'publish' !== $post_status && true !== $already_advertised ) :
            ?>
            <p>
                <?php esc_html_e( 'You can not advertise this product. Product needs to be published before you can advertise.', 'dokan' ); ?>
            </p>

            <?php
            // Case 2: Product is already being advertised
        elseif ( true === $already_advertised ) :
            ?>
            <label for="dokan_advertise_single_product">
                <input type="checkbox" id="dokan_advertise_single_product" name="dokan_advertise_single_product" value="on" checked="checked" disabled="disabled" />
                <?php
                // translators: %s: expiration date
                printf( __( 'Product advertisement is currently ongoing. Advertisement will end on: <strong>%s</strong>', 'dokan' ), $expire_date );
                ?>
            </label>

            <?php
            // Case 3: No advertisement slots available (either from subscription or global)
        elseif ( $remaining_slot === 0 || $remaining_slot === false ) :
            ?>
            <p>
                <?php
                if ( false !== $subscription_status && 0 === $subscription_remaining_slot ) {
                    esc_html_e( 'Your subscription plan does not include product advertisement slots. Please upgrade your subscription or contact the admin for more information.', 'dokan' );
                } else {
                    esc_html_e('No advertisement slots are currently available. Please contact the site administrator to request additional slots or check back later.', 'dokan');
                }
                ?>
            </p>

            <?php
            // Case 4: Slots available (unlimited or limited) and can advertise for free
        elseif ( $can_advertise_for_free && ( $remaining_slot > 0 || $remaining_slot === -1 ) ) :
            ?>
            <label for="dokan_advertise_single_product">
                <input type="checkbox"
                        id="dokan_advertise_single_product"
                        name="dokan_advertise_single_product"
                        value="off"
                        data-product-id="<?php echo esc_attr( $product_id ); ?>" />
                <?php
                printf(
                // translators: 1) expiration period, 2) remaining slots
                    __( 'You can advertise this product for free. Expire after <strong>%1$s</strong>, Remaining slot: <strong>%2$s</strong>', 'dokan' ),
                    Helper::format_expire_after_days_text( $expires_after_days ),
                    Helper::get_formatted_remaining_slot_count( $remaining_slot )
                );
                ?>
            </label>

            <?php
            // Case 5: Slots available but must purchase
        elseif ( $remaining_slot > 0 || $remaining_slot === -1 ) :
            ?>
            <label for="dokan_advertise_single_product">
                <input type="checkbox"
                        id="dokan_advertise_single_product"
                        name="dokan_advertise_single_product"
                        value="off"
                        data-product-id="<?php echo esc_attr( $product_id ); ?>" />
                <?php
                printf(
                // translators: 1) expiration period, 2) cost, 3) remaining slots
                    __( 'Advertise this product for: <strong>%1$s</strong>, Advertisement Cost: <strong>%2$s</strong>, Remaining slot: <strong>%3$s</strong>', 'dokan' ),
                    Helper::format_expire_after_days_text( $expires_after_days ),
                    wc_price( $listing_price ),
                    Helper::get_formatted_remaining_slot_count( $remaining_slot )
                );
                ?>
            </label>

            <?php
            // Case 6: Fallback - this should be unreachable, but kept for safety
        else :
            ?>
            <p class="dokan-error">
                <?php esc_html_e( 'There was an error determining your advertisement eligibility. Please refresh the page or contact support.', 'dokan' ); ?>
            </p>
        <?php endif; ?>

        <div class="dokan-clearfix"></div>
    </div>
</div>

<?php do_action( 'dokan_product_edit_after_product_advertisement', $product_id ); ?>
