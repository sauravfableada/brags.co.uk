<?php
/**
 * Settings discount template
 *
 * @since 2.6
 * @since 3.9.4 rewritten this template
 *
 * @var string $is_enable_order_discount
 * @var string $setting_minimum_order_amount
 * @var string $setting_order_percentage
 */

use WeDevs\DokanPro\VendorDiscount\OrderDiscount;

$setting_order_percentage     = $setting_order_percentage ? wc_format_localized_price( wc_format_decimal( $setting_order_percentage, wc_get_price_decimals() ) ) : '';
$setting_minimum_order_amount = $setting_minimum_order_amount ? wc_format_localized_price( wc_format_decimal( $setting_minimum_order_amount, wc_get_price_decimals() ) ) : '';
?>
<div class="dokan-form-group">
    <label class="dokan-w3 dokan-control-label"><?php esc_html_e( 'Discount ', 'dokan' ); ?></label>
    <div class="dokan-w5 dokan-text-left">
        <div class="checkbox">
            <label class="dokan-control-label" for="lbl_setting_minimum_quantity">
                <input type="hidden" name="setting_show_minimum_order_discount_option" value="no">
                <input
                    id="lbl_setting_minimum_quantity"
                    type="checkbox" name="setting_show_minimum_order_discount_option"
                    value="yes"<?php checked( $is_enable_order_discount, 'yes' ); ?>
                />
                <?php esc_html_e( 'Enable storewide discount', 'dokan' ); ?>
            </label>
        </div>
        <div class="show_if_needs_sw_discount <?php echo ( $is_enable_order_discount === 'yes' ) ? '' : 'hide_if_order_discount'; ?>">
            <div class="dokan-text-left dokan-form-group">
                <input
                    type="text"
                    id="setting_minimum_order_amount"
                    value="<?php echo esc_attr( $setting_minimum_order_amount ); ?>"
                    name="<?php echo esc_attr( OrderDiscount::SETTING_MINIMUM_ORDER_AMOUNT ); ?>"
                    placeholder="<?php esc_attr_e( 'Minimum Order Amount', 'dokan' ); ?>"
                    class="dokan-form-control input-md"
                />
            </div>
            <div class="dokan-text-left dokan-form-group">
                <input
                    type="text"
                    id="setting_order_percentage"
                    value="<?php echo esc_attr( $setting_order_percentage ); ?>"
                    name="<?php echo esc_attr( OrderDiscount::SETTING_ORDER_PERCENTAGE ); ?>"
                    placeholder="<?php esc_attr_e( 'Percentage', 'dokan' ); ?>"
                    class="dokan-form-control input-md"
                />
            </div>
        </div>
    </div>
</div>
