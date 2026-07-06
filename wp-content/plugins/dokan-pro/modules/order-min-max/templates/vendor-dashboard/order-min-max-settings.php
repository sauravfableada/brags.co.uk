<?php

/**
 * Dokan Order Min-Max Amount Field Template
 *
 * @since 3.12.0
 *
 * @package DokanPro
 *
 * @var array $min_max_args Min-Max arguments array
 * @var float $min_amount_to_order Minimum amount to order
 * @var float $max_amount_to_order Maximum amount to order
 */

use WeDevs\DokanPro\Modules\OrderMinMax\Helper;

if ( empty( $min_max_args ) ) {
    return;
}

// phpcs:ignore
extract( $min_max_args, EXTR_SKIP );

?>
<fieldset id="min_max_amount" class="">
    <h4 style="font-weight:600;color:#6d6d6d;">
        <?php esc_html_e( 'Set Cart Amount Min-Max', 'dokan' ); ?>
    </h4>
    <div class="dokan-form-group">
        <label class="dokan-w3 dokan-control-label" for="min_amount_to_order"><?php esc_html_e( 'Minimum amount to place an order', 'dokan' ); ?></label>
        <div class="dokan-w3">
            <div class="checkbox dokan-text-left">
                <input value="<?php echo esc_attr( $min_amount_to_order ); ?>" type="number" min="0" name="min_amount_to_order" id="min_amount_to_order" class="dokan-form-control order_min_max_input_handle" />
            </div>
        </div>
    </div>
    <div class="dokan-form-group">
        <label class="dokan-w3 dokan-control-label" for="max_amount_to_order"><?php esc_html_e( 'Maximum amount to place an order', 'dokan' ); ?></label>
        <div class="dokan-w3">
            <div class="checkbox dokan-text-left">
                <input value="<?php echo esc_attr( $max_amount_to_order ); ?>" type="number" min="0" name="max_amount_to_order" id="max_amount_to_order" class="dokan-form-control order_min_max_input_handle" />
            </div>
        </div>
    </div>
    <div style="font-size:0.8rem;text-align:left">
        <?php echo Helper::get_amount_min_max_notice(); ?>
    </div>
</fieldset>
