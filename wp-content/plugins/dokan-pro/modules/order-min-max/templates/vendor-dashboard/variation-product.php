<?php
/**
 * Template to load variation product fields for order min max
 *
 * @since 3.12.0
 *
 * @package DokanPro
 *
 * @var WeDevs\DokanPro\Modules\OrderMinMax\DataSource\ProductMinMaxSettings $min_max_settings Min-Max settings object
 * @var int $loop Loop index
 * @var string $message_class Message class
 * @var string $message Message
 */

defined( 'ABSPATH' ) || exit;

use WeDevs\DokanPro\Modules\OrderMinMax\Constants;

$min_quantity_class = 'variable_min_quantity ' . Constants::VARIATION_PRODUCT_MIN_QUANTITY;
$min_quantity_id = Constants::VARIATION_PRODUCT_MIN_QUANTITY . '_' . $loop;
$min_quantity_name = Constants::VARIATION_PRODUCT_MIN_QUANTITY . '[' . $loop . ']';
$min_quantity_value = $min_max_settings->min_quantity( 'edit' );

$max_quantity_class = 'variable_max_quantity ' . Constants::VARIATION_PRODUCT_MAX_QUANTITY;
$max_quantity_id = Constants::VARIATION_PRODUCT_MAX_QUANTITY . '_' . $loop;
$max_quantity_name = Constants::VARIATION_PRODUCT_MAX_QUANTITY . '[' . $loop . ']';
$max_quantity_value = $min_max_settings->max_quantity( 'edit' );
$wrapper_class = Constants::VENDOR_VARIATION_PRODUCT_METABOX_WRAPPER;
?>
<div class="dokan-edit-row <?php echo esc_attr( $wrapper_class ); ?> ">
    <div class="header-wrapper">
        <h3>
            <?php esc_html_e( 'Order Min Max Settings', 'dokan' ); ?>
        </h3>
    </div>
    <div class="body-wrapper">
        <div class="dokan-form-group">
            <label for="<?php echo esc_attr( $min_quantity_id ); ?>">
                <?php esc_html_e( 'Minimum Quantity', 'dokan' ); ?>
            </label>
            <input
                type="number"
                id="<?php echo esc_attr( $min_quantity_id ); ?>"
                name="<?php echo esc_attr( $min_quantity_name ); ?>"
                value="<?php echo esc_attr( $min_quantity_value ); ?>"
                class="<?php echo esc_attr( $min_quantity_class ); ?>"
                min="0"
            >
        </div>
        <div class="dokan-form-group">
            <label for="<?php echo esc_attr( $max_quantity_id ); ?>">
                <?php esc_html_e( 'Maximum Quantity', 'dokan' ); ?>
            </label>
            <input
                type="number"
                id="<?php echo esc_attr( $max_quantity_id ); ?>"
                name="<?php echo esc_attr( $max_quantity_name ); ?>"
                value="<?php echo esc_attr( $max_quantity_value ); ?>"
                class= "<?php echo esc_attr( $max_quantity_class ); ?>"
                min="0"
            >
        </div>
        <div class="<?php echo $message_class; ?>">
            <?php echo $message; ?>
        </div>
        <?php wp_nonce_field( Constants::VARIATION_PRODUCT_MIN_MAX_NONCE, Constants::VARIATION_PRODUCT_MIN_MAX_NONCE ); ?>
    </div>
</div>
