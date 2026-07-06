<?php
/**
 * Vendor Questions single Template.
 *
 * @since 3.11.0
 *
 * @var  Question $question Question object.
 */

use WeDevs\DokanPro\Modules\ProductQA\Models\Question;
use WeDevs\DokanPro\Modules\ProductQA\Vendor;

defined( 'ABSPATH' ) || exit;

$question_info = $question->to_array();
wc_print_notices();
?>
<div class="dokan-w8 dokan-product-qa-single-left-content">
    <div class="dokan-clearfix">
        <div class="dokan-panel dokan-panel-default">
            <div class="dokan-panel-heading">
                <strong><?php esc_html_e( 'Question Details', 'dokan' ); ?></strong>
            </div>
            <div class="dokan-panel-body">
                <table class="dokan-table dokan-product-qa-table">
                    <tbody>
                        <tr>
                            <td class="dokan-product-qa-row-title"><strong><?php esc_html_e( 'Product:', 'dokan' ) ?></strong></td>
                            <td class="dokan-product-qa-row-info">
                                <div class='dokan-product-qa-product-image-wrapper-inner'>
                                    <?php $product = wc_get_product( $question->get_product_id() ); ?>
                                    <?php if ( current_user_can( 'dokan_edit_product' ) ) : ?>
                                        <a href="<?php echo esc_url( dokan_edit_product_url( $product->get_id() ) ); ?>"><?php echo wp_kses_post( $product->get_image() ); ?></a>
                                        <strong><a class="" href="<?php echo esc_url( dokan_edit_product_url( $product->get_id() ) ); ?>"><?php echo esc_html( $product->get_title() ); ?></a></strong>
                                    <?php else : ?>
                                        <?php echo wp_kses_post( $product->get_image() ); ?>
                                        <strong><a class="" href=''><?php echo esc_html( $product->get_title() ); ?></a></strong>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class='dokan-product-qa-row-title'><strong><?php esc_html_e( 'Questioner:', 'dokan' ) ?></strong></td>
                            <td class="dokan-product-qa-row-info"><?php echo esc_html( $question_info['user_display_name'] ); ?></td>
                        </tr>
                        <tr>
                            <td class='dokan-product-qa-row-title'><strong><?php esc_html_e( 'Question:', 'dokan' ) ?></strong></td>
                            <td class="dokan-product-qa-row-info"><?php echo esc_html( $question->get_question() ); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="dokan-w4 dokan-product-qa-single-right-content">
    <div class="dokan-clearfix">
        <div class="dokan-panel dokan-panel-default dokan-status-update-panel">
            <div class='dokan-panel-heading'>
                <strong><?php esc_html_e( 'Status', 'dokan' ); ?></strong>
            </div>
            <div class="dokan-panel-body">
                <?php
                $created_at = $question_info['display_human_readable_created_at'] ? $question_info['human_readable_created_at'] : $question_info['created_at'];
                $updated_at = $question_info['display_human_readable_updated_at'] ? $question_info['human_readable_updated_at'] : $question_info['updated_at'];
                ?>
                <p>
                    <?php echo sprintf( '<strong>%s</strong>: %s', __( 'Created', 'dokan' ), $created_at ); ?> <br>
                    <?php echo sprintf( '<strong>%s</strong>: %s', __( 'Last Updated', 'dokan' ), $updated_at ); ?>
                </p>
                <div class='dokan-form-group dokan-clearfix'>
                    <button
                        data-security="<?php echo esc_attr( wp_create_nonce( 'dokan-product-qa-delete-question' ) ); ?>"
                        data-question="<?php echo esc_attr( $question->get_id() ); ?>"
                        data-message="<?php echo esc_attr( __( 'Are you sure you want to delete the question?', 'dokan' ) ); ?>"
                        data-redirect="<?php echo esc_url( dokan_get_navigation_url( Vendor::QUERY_VAR ) ); ?>"
                        type='button' class='dokan-right dokan-btn dokan-btn-default dokan-product-qa-delete-question'
                    >
                        <?php esc_html_e( 'Delete Question', 'dokan' ); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="dokan-clearfix"></div>

<div class="dokan-w12">
    <div class="dokan-clearfix">
        <div class="dokan-panel dokan-panel-default">
            <div class="dokan-panel-heading">
                <strong><?php esc_html_e( 'Answer', 'dokan' ) ?></strong>
            </div>

            <div class="dokan-panel-body">
                <div class="dokan-product-qa-answer-display" style="display: <?php echo $question->get_answer()->get_id() ? 'block' : 'none'; ?>;">
                    <div class='additional-details'>
                        <div class="details-row">
                            <div class="details-value">
                                <?php echo wp_kses_post( do_shortcode( $question->get_answer()->get_answer() ) ); ?>
                            </div>
                        </div>
                        <div class='details-row'>
                            <p class='details-value'>
                                <?php
                                // translators:// 1) By. 2) User Display Name. 3) on. 4) Last updated date.
                                echo sprintf(
                                    '%1$s <strong>%2$s</strong> %3$s <strong>%4$s</strong>',
                                    esc_html__( 'by', 'dokan' ),
                                    esc_html( $question->get_answer()->to_array()['user_display_name'] ),
                                    esc_html__( 'on', 'dokan' ),
                                    esc_html( dokan_format_datetime( $question->get_answer()->get_updated_at() ) )
                                );
                                ?>
                            </p>
                        </div>
                        <div class='details-row'>
                            <p class='details-value'>
                                <button
                                    type='button' name='dokan_product_qa_edit_answer' id='dokan_product_qa_edit_answer' class='dokan-btn dokan-btn-default'
                                >
                                    <?php esc_html_e( 'Edit', 'dokan' ); ?>
                                </button>
                                <button
                                    data-security="<?php echo esc_attr( wp_create_nonce( 'dokan-product-qa-answer-delete' ) ); ?>"
                                    data-question="<?php echo esc_attr( $question->get_id() ); ?>"
                                    data-message="<?php esc_attr_e( 'Are you sure you want to delete the answer?', 'dokan' ); ?>"
                                    type='button' name='dokan_product_qa_delete_answer' class='dokan-btn dokan-btn-default'
                                    id='dokan_product_qa_delete_answer'
                                >
                                    <?php esc_html_e( 'Delete', 'dokan' ); ?>
                                </button>
                            </p>
                        </div>
                    </div>
                </div>
                <div id="dokan-product-qa-answer-text-area" style="display: <?php echo $question->get_answer()->get_id() ? 'none' : 'block'; ?>;">
                    <div class='form-row'>
                        <?php
                        wp_editor(
                            $question->get_answer()->get_answer(),
                            'dokan-product-qa-answer',
                            [
                                'media_buttons' => false,
                                'tinymce'       => true,
                                'quicktags'     => false,
                            ]
                        );
                        ?>
                    </div>
                    <button
                        data-security="<?php echo esc_attr( wp_create_nonce( 'dokan-product-qa-answer-save' ) ); ?>"
                        data-question="<?php echo esc_attr( $question->get_id() ); ?>"
                        type="button" name="dokan_product_qa_save_answer" class="dokan-btn dokan-btn-default"
                        id="dokan_product_qa_save_answer"
                    >
                        <?php esc_html_e( 'Save', 'dokan' ); ?>
                    </button>
                    <?php if ( $question->get_answer()->get_id() ): ?>
                    <button
                        type='button' name='dokan_product_qa_cancel_save_answer' id="dokan_product_qa_cancel_save_answer" class='dokan-btn dokan-btn-default'
                    >
                        <?php esc_html_e( 'Cancel', 'dokan' ); ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
