<?php
/**
 * Template file to be loaded on vendor simple product
 *
 * @since 3.12.0
 *
 * @package DokanPro
 *
 * @var WeDevs\DokanPro\Modules\OrderMinMax\DataSource\ProductMinMaxSettings $min_max_settings Min-Max settings object
 * @var int $post_id Product ID
 * @var string $message_class Message class
 * @var string $min_quantity Minimum quantity
 * @var string $max_quantity Maximum quantity
 */
defined( 'ABSPATH' ) || exit;

use WeDevs\DokanPro\Modules\OrderMinMax\Constants;
use WeDevs\DokanPro\Modules\OrderMinMax\Helper;

?>

<?php do_action( 'dokan_order_min_max_product_settings_before', $post_id ); ?>

<div class="dokan-edit-row show_if_simple <?php echo esc_attr( Constants::VENDOR_SIMPLE_PRODUCT_METABOX_WRAPPER ); ?>">
    <div class="dokan-section-heading">
        <h2>
            <i class="fas fa-dollar-sign"></i>
            <?php esc_html_e( 'Min/Max Options', 'dokan' ); ?>
        </h2>
        <p>
            <?php esc_html_e( 'Manage min max options for this product', 'dokan' ); ?>
        </p>
    </div>
    <div class="dokan-section-content">
        <div class="dokan-clearfix">
            <div class="dokan-form-group">
                <label
                    class="form-label"
                    for="<?php echo Constants::SIMPLE_PRODUCT_MIN_QUANTITY; ?>"
                >
                    <?php esc_html_e( 'Minimum quantity to order', 'dokan' ); ?>
                </label>
                <input
                    type="number"
                    id="<?php echo Constants::SIMPLE_PRODUCT_MIN_QUANTITY; ?>"
                    class="dokan-for-control"
                    min="0"
                    name="<?php echo Constants::SIMPLE_PRODUCT_MIN_QUANTITY; ?>"
                    value="<?php echo esc_attr( $min_max_settings->min_quantity( 'edit' ) ); ?>"
                >
            </div>
            <div class="dokan-form-group">
                <label
                    class="form-label"
                    for="<?php echo Constants::SIMPLE_PRODUCT_MIN_QUANTITY; ?>"
                >
                    <?php esc_html_e( 'Maximum quantity to order', 'dokan' ); ?>
                </label>
                <input
                    type="number"
                    id="<?php echo Constants::SIMPLE_PRODUCT_MAX_QUANTITY; ?>"
                    class="dokan-for-control"
                    min="0"
                    name="<?php echo Constants::SIMPLE_PRODUCT_MAX_QUANTITY; ?>"
                    value="<?php echo esc_attr( $min_max_settings->max_quantity( 'edit' ) ); ?>"
                >
            </div>
            <div class="<?php echo $message_class; ?>">
                <?php echo Helper::get_quantity_min_max_notice(); ?>
            </div>
            <?php wp_nonce_field( Constants::SIMPLE_PRODUCT_MIN_MAX_NONCE, Constants::SIMPLE_PRODUCT_MIN_MAX_NONCE ); ?>
            <?php
            /**
             * Action hook to add more fields after min max settings
             *
             * @since 3.12.0
             */
            do_action( 'dokan_order_min_max_product_settings_after', $post_id );
            ?>
        </div>
    </div>
</div>
