<?php
/**
 * Dokan Bulk edit field template for vendor
 *
 * @since 3.12.0
 */

defined( 'ABSPATH' ) || exit;

use WeDevs\DokanPro\Modules\OrderMinMax\Constants;
use WeDevs\DokanPro\Modules\OrderMinMax\Helper;

?>
<div class="dokan-min-max-wrapper dokan-min-max-vendor" style="margin: 0">
    <div class="dokan-inline-edit-field-row dokan-clearfix">
        <label class="dokan-w3"><?php esc_html_e( 'Quantity Min/Max', 'dokan' ); ?></label>
        <div class="dokan-w9">
            <select class="dokan-form-control" id="change_vendor_override_bulk_product_min_max" name="dokan_vendor_override_bulk_product_min_max">
                <option value="">— No change —</option>
                <option value="1">Change to:</option>
            </select>
            <input
                type="number"
                id="dokan_dashboard_min_quantity"
                name="<?php echo esc_attr( Constants::BULK_EDIT_VENDOR_MINIMUM_QUANTITY ); ?>"
                class="dokan-mt10 dokan-form-control dokan-hide <?php echo esc_attr( Constants::BULK_EDIT_VENDOR_MINIMUM_QUANTITY ); ?>"
                placeholder="Min Quantity"
                value=""
                min="1"
            />
            <input
                type="number"
                id="dokan_dashboard_max_quantity"
                name="<?php echo esc_attr( Constants::BULK_EDIT_VENDOR_MAXIMUM_QUANTITY ); ?>"
                class="dokan-mt10 dokan-form-control dokan-hide <?php echo esc_attr( Constants::BULK_EDIT_VENDOR_MAXIMUM_QUANTITY ); ?>"
                placeholder="Max Quantity"
                value=""
                min="1"
            />
        </div>
    </div>
    <div class="wc-quick-edit-warning inline-edit-group dokan-min-max-warning-message">
        <?php echo Helper::get_quantity_min_max_notice(); ?>
    </div>
</div>
