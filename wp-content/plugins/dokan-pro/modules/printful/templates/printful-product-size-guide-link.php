<?php
/**
 * Printful Product Size Guide Link Template.
 *
 * @var bool   $is_printful.    Is Printful.
 * @var string $link_label      Size guide link label.
 * @var string $link_text_color Size guide link text color
 */

defined( 'ABSPATH' ) || exit;

do_action( 'dokan_printful_product_size_guide_link_before' );
?>
<div>
    <a class="dokan-printful-size-guide-preview-link" href="#" style="color: <?php echo esc_attr( $link_text_color ); ?>">
        <?php echo esc_html( $link_label ); ?>
    </a>
</div>
<?php
do_action( 'dokan_printful_product_size_guide_link_after' );
