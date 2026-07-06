<?php
/**
 * Settings discount template
 *
 * @since 3.9.4 rewritten this template
 *
 * @var $lot_discount_enabled      string
 * @var $product_discount_quantity int
 * @var $product_discount_amount   string
 * @var $product                   WC_Product
 */

use WeDevs\DokanPro\VendorDiscount\ProductDiscount;

$product_discount_amount = $product_discount_amount ? wc_format_localized_price( wc_format_decimal( $product_discount_amount, wc_get_price_decimals() ) ) : '';
?>
<div class="dokan-discount-options dokan-edit-row dokan-clearfix hide_if_external">
    <div class="dokan-section-heading" data-togglehandler="dokan_discount_options">
        <h2><i class="fas fa-gift"></i> <?php esc_html_e( 'Discount Options', 'dokan' ); ?></h2>
        <p><?php esc_html_e( 'Set your discount for this product', 'dokan' ); ?></p>
        <a href="#" class="dokan-section-toggle">
            <i class="fas fa-sort-down fa-flip-vertical" aria-hidden="true"></i>
        </a>
        <div class="dokan-clearfix"></div>
    </div>

    <div class="dokan-section-content">
        <label class="dokan-form-label" for="<?php echo ProductDiscount::IS_LOT_DISCOUNT; ?>">
            <input
                type="checkbox"
                id="<?php echo ProductDiscount::IS_LOT_DISCOUNT; ?>"
                name="<?php echo ProductDiscount::IS_LOT_DISCOUNT; ?>"
                value="yes"
                <?php checked( $lot_discount_enabled, 'yes' ); ?>
            />
            <?php esc_html_e( 'Enable bulk discount', 'dokan' ); ?>
        </label>

        <div class="show_if_needs_lot_discount <?php echo ( $lot_discount_enabled === 'yes' ) ? '' : 'dokan-hide'; ?>">
            <div class="content-half-part">
                <label class="dokan-form-label" for="<?php echo ProductDiscount::LOT_DISCOUNT_QUANTITY; ?>">
                    <?php esc_html_e( 'Minimum quantity', 'dokan' ); ?>
                </label>
                <input
                    type="number"
                    id="<?php echo ProductDiscount::LOT_DISCOUNT_QUANTITY; ?>"
                    name="<?php echo ProductDiscount::LOT_DISCOUNT_QUANTITY; ?>"
                    value="<?php echo $product_discount_quantity; ?>"
                    placeholder="0"
                    min="0"
                    class="dokan-form-control"
                />
            </div>
            <div class="dokan-form-group content-half-part">
                <label class="dokan-form-label" for="<?php echo ProductDiscount::LOT_DISCOUNT_AMOUNT; ?>">
                    <?php esc_html_e( 'Discount %', 'dokan' ); ?>
                </label>
                <div class="dokan-input-group">
                    <input
                        type="text"
                        id="<?php echo ProductDiscount::LOT_DISCOUNT_AMOUNT; ?>"
                        name="<?php echo ProductDiscount::LOT_DISCOUNT_AMOUNT; ?>"
                        value="<?php echo $product_discount_amount; ?>"
                        placeholder="<?php esc_attr_e( 'Percentage', 'dokan' ); ?>"
                        class="dokan-form-control"
                    />
                    <span class="dokan-input-group-addon"><?php echo '%'; ?></span>
                </div>
            </div>
            <div class="dokan-clearfix"></div>
        </div>
    </div>
</div>
