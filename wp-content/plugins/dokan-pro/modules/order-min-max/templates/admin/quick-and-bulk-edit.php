<?php
/**
 * Dokan Quick edit field template for admin
 *
 * @since 3.12.0
 */

defined( 'ABSPATH' ) || exit;

use WeDevs\DokanPro\Modules\OrderMinMax\Constants;
use WeDevs\DokanPro\Modules\OrderMinMax\Helper;

?>
<div class="dokan-min-max-wrapper dokan-min-max-admin">
    <div class="inline-edit-group">
        <label class="alignleft">
            <span class="title" style="min-width: max-content">
                <?php esc_html_e( 'Qty Min/Max:', 'dokan' ); ?>
            </span>
            <span class="input-text-wrap">
                <select class="dokan_override_bulk_product_min_max change_to" name="dokan_override_bulk_product_min_max">
                    <option value=""><?php esc_html_e( '— No change —', 'dokan' ); ?></option>
                    <option value="1"><?php esc_html_e( 'Change to:', 'dokan' ); ?></option>
                </select>
            </span>
        </label>
        <div class="change-input inline-edit-group dokan-admin-bulk-product-min-max-data-box">
            <label class="dokan_min_quantity">
                <span class="title" style="min-width: max-content">
                    <?php esc_html_e( 'Min Quantity:', 'dokan' ); ?>
                </span>
                <span class="input-text-wrap">
                    <input
                        type="number"
                        name="<?php echo esc_attr( Constants::QUICK_EDIT_MINIMUM_QUANTITY ); ?>"
                        class="text dokan-min-quantity-input <?php echo esc_attr( Constants::QUICK_EDIT_MINIMUM_QUANTITY ); ?>"
                        value="0"
                        min="0"
                    >
                </span>
            </label>
            <label class="dokan_max_quantity">
                <span class="title" style="min-width: max-content">
                    <?php esc_html_e( 'Max Quantity:', 'dokan' ); ?>
                </span>
                <span class="input-text-wrap">
                    <input
                        type="number"
                        name="<?php echo esc_attr( Constants::QUICK_EDIT_MAXIMUM_QUANTITY ); ?>"
                        class="text dokan-max-quantity-input <?php echo esc_attr( Constants::QUICK_EDIT_MAXIMUM_QUANTITY ); ?>"
                        value="0"
                        min="0"
                    >
                </span>
            </label>
        </div>
    </div>
    <div class="inline-edit-group dokan-min-max-warning-message">
        <?php echo Helper::get_quantity_min_max_notice(); ?>
    </div>
</div>

