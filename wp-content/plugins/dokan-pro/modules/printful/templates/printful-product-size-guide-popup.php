<?php
/**
 * Printful Product Size Guide Popup Template.
 *
 * @var bool   $is_printful          Is Printful.
 * @var int    $catalog_product_id.  Printful catalog product id.
 * @var array  $available_sizes      Available product sizes.
 * @var object $size_guide_processor Size guide processor.
 * @var array  $size_guide_data      Size guide data.
 * @var array  $size_guide_styles    Size guide Styles.
 */

defined( 'ABSPATH' ) || exit;

do_action( 'dokan_printful_product_size_guide_popup_before' );
?>

<style>
    .dokan-printful-size-guide-popup-wrapper {
        --popup-text-color: <?php echo esc_html( $size_guide_styles['popup_text_color'] ) ?>;
        --popup-bg-color: <?php echo esc_html( $size_guide_styles['popup_bg_color'] ) ?>;
        --tab-bg-color: <?php echo esc_html( $size_guide_styles['tab_bg_color'] ) ?>;
        --tab-active-bg-color: <?php echo esc_html( $size_guide_styles['active_tab_bg_color'] ) ?>;
    }
</style>
<div class="dokan-printful-size-guide-popup-wrapper"></div>
<script type="text/html" id="tmpl-dokan-printful-product-size-guide">
    <div id="dokan-printful-size-guide-tabs" class="dokan-printful-size-guide-wrapper">
        <ul>
        <?php
        foreach ( $size_guide_data as $measure_key => $measure_value ) {
            ?>
            <li><a href="#dokan-printful-tab-<?php echo esc_attr( $measure_key ); ?>"><?php echo 'product_measure' === $measure_key ? esc_html__( 'Product Measurements', 'dokan' ) : esc_html__( 'Measure Yourself', 'dokan' ) ?></a></li>
            <?php
        }
        ?>
        </ul>

        <?php
        foreach ( $size_guide_data as $measure_key => $measure_value ) {
            $description = ! empty( $measure_value['description'] ) ? $measure_value['description'] : '';
            $image_url   = ! empty( $measure_value['image_url'] ) ? $measure_value['image_url'] : '';
            $image_desc  = ! empty( $measure_value['image_description'] ) ? $measure_value['image_description'] : '';
            ?>
            <div id="dokan-printful-tab-<?php echo esc_attr( $measure_key ); ?>" class="dokan-printful-single-size-tab">
                <h2 class="dokan-printful-measurement-title"><?php echo 'product_measure' === $measure_key ? esc_html__( 'Product Measurements', 'dokan' ) : esc_html__( 'Measure Yourself', 'dokan' ) ?></h2>
                <div class="dokan-printful-measurement-desc"><?php echo wp_kses_post( $description ); ?></div>
                <div class="dokan-printful-measurement-img-wrapper">
                    <img class="dokan-printful-measurement-img" src="<?php echo esc_url( $image_url ); ?>">
                    <div class="dokan-printful-measurement-img-desc"><?php echo wp_kses_post( $image_desc ); ?></div>
                </div>

                <h3 class="dokan-printful-size-table-title"><?php echo esc_html__( 'Size Chart', 'dokan' ); ?></h3>
                <div id="dokan-printful-size-table-tabs-<?php echo esc_attr( $measure_key ); ?>" class="dokan-printful-size-table-wrapper">
                    <ul>
                    <?php
                    foreach ( $measure_value as $unit_key => $unit_value ) {
                        if ( ! is_array( $unit_value ) ) {
                            continue;
                        }

                        ?>
                        <li><a href="#dokan-printful-size-table-tab-<?php echo esc_attr( $measure_key . '-' . $unit_key ); ?>"><?php echo esc_html( strtoupper( $unit_key ) ); ?></a></li>
                        <?php
                    }
                    ?>
                    </ul>
                    <?php
                    foreach ( $measure_value as $unit_key => $unit_value ) {
                        if ( ! is_array( $unit_value ) ) {
                            continue;
                        }

                        ?>
                        <div id="dokan-printful-size-table-tab-<?php echo esc_attr( $measure_key . '-' . $unit_key ); ?>" class="dokan-printful-single-size-table-tab">
                            <?php echo wp_kses_post( $size_guide_processor->get_table( $measure_key, $unit_key ) ); ?>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
            <?php
        }
        ?>
    </div>
</script>
<?php
do_action( 'dokan_printful_product_size_guide_popup_after' );
