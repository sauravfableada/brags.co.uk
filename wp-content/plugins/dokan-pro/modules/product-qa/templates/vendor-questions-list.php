<?php
/**
 * Vendor Questions List
 *
 * @var Question[] $questions       Question Collection.
 * @var string     $pagination_html Pagination HTML.
 */

use WeDevs\DokanPro\Modules\ProductQA\Models\Question;

defined( 'ABSPATH' ) || exit;
?>

    <table class='dokan-table table table-striped product-qa-listing-table'>
        <thead>
        <tr>
            <th><?php esc_html_e( 'Question', 'dokan' ); ?></th>
            <th><?php esc_html_e( 'Products', 'dokan' ); ?></th>
            <th><?php esc_html_e( 'Status', 'dokan' ); ?></th>
            <th><?php esc_html_e( 'Date', 'dokan' ); ?></th>
            <th><?php esc_html_e( 'Action', 'dokan' ); ?></th>
        </tr>
        </thead>

        <tbody>
        <?php if ( ! empty( $questions ) ) : ?>
            <?php foreach ( $questions as $question ) : ?>
                <?php
                dokan_get_template_part(
                    'vendor-questions',
                    'row',
                    [
                        'pro'           => true,
                        'question'      => $question,
                        'is_product_qa' => true,
                    ]
                );
                ?>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="4">
                    <?php esc_html_e( 'No question found.', 'dokan' ); ?>
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

<?php
echo wp_kses_post( $pagination_html );
