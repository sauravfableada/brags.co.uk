<?php
/**
 * Vendor Questions Table Row Template.
 *
 * @since 3.11.0
 * @var  Question $question Question object.
 */

use WeDevs\DokanPro\Modules\ProductQA\Models\Question;
use WeDevs\DokanPro\Modules\ProductQA\Vendor;

defined( 'ABSPATH' ) || exit;

$edit_url   = add_query_arg(
    [ 'question_id' => $question->get_id() ],
    dokan_get_navigation_url( Vendor::QUERY_VAR )
);
$delete_url = wp_nonce_url(
    add_query_arg(
        [
            'action'      => 'dokan-delete-product-qa-question',
            'question_id' => $question->get_id(),
        ],
        dokan_get_navigation_url( Vendor::QUERY_VAR )
    ),
    'dokan-delete-product-qa-question'
);
$product    = wc_get_product( $question->get_product_id() );
?>

<tr>
    <td class='details column-primary'>
        <?php
        printf(
            '<a href="%s"><strong>%s</strong></a><br> <small>by %s</small>',
            $edit_url,
            esc_html( $question->get_question() ),
            $question->to_array()['user_display_name']
        );
        ?>
        <div class='row-actions'>
            <span class="delete">
                <a
                    data-security="<?php echo esc_attr( wp_create_nonce( 'dokan-product-qa-delete-question' ) ); ?>"
                    data-question="<?php echo esc_attr( $question->get_id() ); ?>"
                    data-message="<?php echo esc_attr( __( 'Are you sure you want to delete the question?', 'dokan' ) ); ?>"
                    data-redirect='<?php echo esc_url( dokan_get_navigation_url( Vendor::QUERY_VAR ) ); ?>'
                    class='request-delete dokan-product-qa-delete-question'
                >
                    <?php esc_html_e( 'Delete', 'dokan' ); ?>
                </a>
            </span>
        </div>

        <button type="button" class="toggle-row"></button>
    </td>
    <td class="details column-product" data-title="<?php esc_attr_e( 'Product', 'dokan' ); ?>">
        <div class="dokan-product-qa-product-image-wrapper">
            <?php if ( current_user_can( 'dokan_edit_product' ) ) : ?>
                <a href="<?php echo esc_url( dokan_edit_product_url( $product->get_id() ) ); ?>"><?php echo wp_kses_post( $product->get_image() ); ?></a>
                <strong><a href="<?php echo esc_url( dokan_edit_product_url( $product->get_id() ) ); ?>"><?php echo esc_html( $product->get_title() ); ?></a></strong>
            <?php else : ?>
                <?php echo wp_kses_post( $product->get_image() ); ?>
                <strong><a href=''><?php echo esc_html( $product->get_title() ); ?></a></strong>
            <?php endif; ?>
        </div>
    </td>
    <td class='details column-status' data-title="<?php esc_html_e( 'Status', 'dokan' ); ?>">
        <?php echo $question->is_answered() ? esc_html__( 'Answered', 'dokan' ) : esc_html__( 'Unanswered', 'dokan' ); ?>
    </td>
    <td data-title="<?php esc_html_e( 'Date', 'dokan' ); ?>">
        <?php echo esc_html( dokan_format_datetime( $question->get_created_at() ) ); ?>
    </td>
    <td data-title="<?php esc_html_e( 'View', 'dokan' ); ?>">
        <a class="dokan-btn dokan-btn-default dokan-btn-sm tips"
            href="<?php echo esc_url( $edit_url ); ?>" data-toggle="tooltip"
            title="<?php esc_html_e( 'View', 'dokan' ); ?>"
            data-placement="top"><i class="far fa-eye">&nbsp;</i></a>
    </td>
</tr>
