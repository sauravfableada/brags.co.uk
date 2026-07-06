<?php
/**
 * Product Rejection History Metabox Template
 *
 * Template for displaying product rejection history and actions in the WordPress admin area.
 *
 * @package WeDevs\DokanPro\ProductRejection
 * @since   3.16.0
 *
 * @var WC_Product $product            Product object being reviewed
 * @var Vendor     $vendor             Vendor who submitted the product
 * @var bool       $is_rejected        Whether product is currently rejected
 * @var bool       $is_resubmitted     Whether product is resubmitted after rejection
 * @var string     $submitted_date     Time of product submission
 * @var string     $resubmission_time  Time of resubmission
 * @var array      $rejection_history  Complete rejection history
 * @var string     $shop_name          Vendor shop name
 */

use WeDevs\Dokan\Vendor\Vendor;

defined( 'ABSPATH' ) || exit;
?>

<div class="dokan-product-rejection-form">
    <?php if ( ! empty( $rejection_history['history'] ) ) : ?>
        <ul class="rejection-history">
            <?php foreach ( $rejection_history['history'] as $rejection ) : ?>
                <?php
                $admin_user = get_userdata( $rejection['admin_id'] );
                $admin_name = $admin_user ? $admin_user->display_name : __( 'Unknown Admin', 'dokan' );
                ?>
                <li class="history-item">
                    <div class="history-content">
                        <?php echo wp_kses_post( wpautop( $rejection['reason'] ) ); ?>
                    </div>

                    <div class="history-footer">
                        <span class="history-date">
                            <abbr class="exact-date" title="<?php echo esc_attr( $rejection['date'] ); ?>">
                                <?php echo esc_html( dokan_format_datetime( strtotime( $rejection['date'] ) ) ); ?>
                            </abbr>
                        </span>
                        <span class="history-meta">
                            <?php
                            printf(
                            /* translators: %s: admin name */
                                esc_html__( 'by %s', 'dokan' ),
                                '<strong>' . esc_html( $admin_name ) . '</strong>'
                            );
                            ?>
                        </span>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <div class="rejection-actions">
        <div class="vendor-info">
            <span class="info-label"><?php esc_html_e( 'Vendor:', 'dokan' ); ?></span>
            <strong class="info-value">
                <a href="<?php echo esc_url( $vendor->get_shop_url() ); ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <?php echo esc_html( $shop_name ); ?>
                </a>
            </strong>
        </div>

        <?php if ( $is_resubmitted ) : ?>
            <div class="resubmission-info">
                <span class="info-label resubmission-date"><?php esc_html_e( 'Resubmitted on:', 'dokan' ); ?></span>
                <strong class="info-value resubmission-date">
                    <abbr class="exact-date resubmission-date" title="<?php echo esc_attr( $resubmission_time ); ?>">
                        <?php echo esc_html( $resubmission_time ); ?>
                    </abbr>
                </strong>
            </div>
        <?php else : ?>
            <div class="product-info">
                <span class="info-label submitted-date"><?php esc_html_e( 'Submitted on:', 'dokan' ); ?></span>
                <strong class="info-value submitted-date">
                    <abbr class="exact-date submitted-date" title="<?php echo esc_attr( $submitted_date ); ?>">
                        <?php echo esc_html( $submitted_date ); ?>
                    </abbr>
                </strong>
            </div>
        <?php endif; ?>

        <?php if ( ! $is_rejected ) : ?>
            <div class="add-rejection">
                <label for="rejection_reason">
                    <?php esc_html_e( 'Add Rejection Reason', 'dokan' ); ?>
                    <span class="woocommerce-help-tip" tabindex="0" data-tip="<?php esc_attr_e( 'Add reason for rejecting this product. The vendor will be notified via email.', 'dokan' ); ?>"></span>
                </label>

                <textarea name="rejection_reason" id="rejection_reason" class="input-text" rows="5" placeholder="<?php esc_attr_e( 'Enter reason for rejecting this product...', 'dokan' ); ?>"></textarea>

                <div class="button-group">
                    <button type="button"
                            class="button button-secondary button-large dokan-reject-product-action"
                            data-product-id="<?php echo esc_attr( (string) $product->get_id() ); ?>"
                            data-vendor-name="<?php echo esc_attr( $shop_name ); ?>"
                    >
                        <?php esc_html_e( 'Reject', 'dokan' ); ?>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php
    /**
     * Fires after the rejection history section
     *
     * @since 3.16.0
     *
     * @param WC_Product $product           Product being reviewed
     * @param array      $rejection_history Complete rejection history
     */
    do_action( 'dokan_product_rejection_metabox_after', $product, $rejection_history );
    ?>
</div>
