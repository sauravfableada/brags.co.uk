<?php
/**
 * Vendor Questions & Answers Product Tab Template.
 *
 * @since 3.11.0
 * @var WC_Product $product This product.
 * @var int $count This product question count.
 */


defined( 'ABSPATH' ) || exit;
?>
<div id="dokan-product-qa-frontend-root"
    class="dokan-product-qa-frontend-root dokan-layout"
    data-product="<?php echo esc_attr( $product->get_id() ); ?>"
    data-user="<?php echo esc_attr( get_current_user_id() ); ?>"
    data-count="<?php echo esc_attr( $count ); ?>"
>
    <?php esc_html_e( 'Loading...', 'dokan' ); ?>
</div>
