<?php
/**
 * Printful Add Size Guide Button Template.
 *
 * @var bool $is_printful Is Printful.
 * @var int  $product_id  Product id.
 * @var int  $catalog_id  Catalog id.
 * @var int  $vendor_id   Vendor id.
 */

defined( 'ABSPATH' ) || exit;

do_action( 'dokan_printful_add_size_guide_button_before' );
?>
<span class="dokan-right dokan-printful-add-size-guide-btn-wrapper" style="margin-right: 7px;">
    <a
        id="dokan-printful-add-size-guide-btn"
        class="dokan-btn dokan-btn-theme dokan-btn-sm"
        href="#"
        data-nonce="<?php echo esc_attr( wp_create_nonce( 'dokan-printful-add-product-size-guide' ) ); ?>"
        data-product_id="<?php echo esc_attr( $product_id ); ?>"
        data-catalog_id="<?php echo esc_attr( $catalog_id ); ?>"
        data-vendor_id="<?php echo esc_attr( $vendor_id ); ?>"
    >
        <?php esc_html_e( 'Add Size Guide', 'dokan' ); ?>
        <i class="fa fa-spinner fa-spin" id="dokan-printful-add-size-guide-spiner" style="display: none;"></i>
    </a>
</span>
<?php
do_action( 'dokan_printful_add_size_guide_button_after' );
