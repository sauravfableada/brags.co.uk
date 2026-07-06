<?php
/**
 * Vendor Questions List filters.
 *
 * @since 3.11.0
 *
 * @var int    $product_id              Product id.
 * @var int    $vendor_id               Vendor id.
 * @var string $product_title           Product title.
 * @var string $answered                Answered question.
 * @var array  $question_filter_options Question filter options.
 *
 * @var array  $filters                 Question filter args.
 */

use WeDevs\DokanPro\Modules\ProductQA\Vendor;

?>
<form action='' method='get' class='dokan-form-inline dokan-w12 dokan-product-qa-filter-form'>
    <?php do_action( 'dokan_product_qa_list_filter_form_start', $filters ); ?>

    <div class='dokan-form-group dokan-product-qa-product-search-form-group'>
        <select name='product_id' class='dokan-form-control dokan-product-search dokan-product-qa-product-search' data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'dokan' ); ?>" data-action='dokan_json_search_products_and_variations' data-user_ids='<?php echo dokan_get_current_user_id(); ?>'>
	        <?php if ( ! empty( $product_id ) ) : ?>
                <option value="<?php echo esc_attr( $product_id ); ?>" selected="selected"><?php echo esc_html( $product_title ); ?></option>
	        <?php else : ?>
                <option value="" selected="selected"><?php esc_html_e( 'Select an option', 'dokan' ); ?></option>
	        <?php endif; ?>
        </select>
    </div>

    <?php if ( ! empty( $question_filter_options ) && is_array( $question_filter_options ) ) : ?>
        <div class="dokan-form-group">
            <select name="answered" class="dokan-form-control">
                <option value=""><?php esc_html_e( 'Select Status', 'dokan' ); ?></option>
                <?php foreach ( $question_filter_options as $filter_key => $filter_label ) : ?>
                    <option value="<?php echo esc_attr( $filter_key ); ?>" <?php selected( $answered, $filter_key ); ?>>
                        <?php echo esc_html( $filter_label ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>

    <input type="submit" value="<?php esc_attr_e( 'Filter', 'dokan' ); ?>" class="dokan-btn">
    <a class="dokan-btn" href="<?php echo esc_url( dokan_get_navigation_url( Vendor::QUERY_VAR ) ); ?>"><?php esc_attr_e( 'Reset', 'dokan' ); ?> </a>

    <?php do_action( 'dokan_product_qa_list_filter_from_end', $filters ); ?>
</form>
