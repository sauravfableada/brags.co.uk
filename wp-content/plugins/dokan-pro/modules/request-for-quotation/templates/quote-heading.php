<?php
use WeDevs\DokanPro\Modules\RequestForQuotation\Helper;
use WeDevs\DokanPro\Modules\RequestForQuotation\Model\Quote;
use WeDevs\DokanPro\Modules\RequestForQuotation\SettingsHelper;

$formatted_quote_creation_date = dokan_current_datetime()->setTimestamp( $quote->created_at )->format( 'd M Y, H:i:s' );

$account_page       = is_account_page();
$is_trashed_quote   = Helper::is_qoute_status( 'trash', $quote );
$is_pending_quote   = Helper::is_qoute_status( 'pending', $quote );
$is_expired_quote   = Helper::is_qoute_status( 'expired', $quote );
$is_updated_quote   = Helper::is_qoute_status( 'updated', $quote );
$is_rejected_quote  = Helper::is_qoute_status( 'reject', $quote );
$is_approved_quote  = Helper::is_qoute_status( 'approve', $quote );
$is_accepted_quote  = Helper::is_qoute_status( 'accepted', $quote );
$is_converted_quote = Helper::is_qoute_status( 'converted', $quote );

$enable_to_convert = SettingsHelper::is_convert_to_order_enabled();
?>
<div id="quote-heading-contents">
    <?php
    if ( ! empty( $quote->status ) ) {
        dokan_get_template_part(
            'quote-notices', '', [
                'quote'                => $quote,
                'is_account_page'      => $account_page,
                'request_quote_vendor' => true,
            ]
        );
    }
    ?>

    <div id="quote-heading">
        <div class="details-area">
            <a href="<?php echo esc_url( $back_to_quotes ); ?>">
                <svg width="18" height="19" viewBox="0 0 18 19" fill="none">
                    <path d="M14.25 9.05469L3.75 9.05469" stroke="#828282" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M9 14.3047L3.75 9.05469L9 3.80469" stroke="#828282" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </a>
            <div class="quote-contents">
                <div class="title-contents">
                    <h3 class="quote-no">
                        <a href="<?php echo esc_url( $back_to_quotes ); ?>">
                            <?php
                            /* translators: 1) Quote id. */
                            printf( __( 'Quote #%1$s', 'dokan' ), number_format_i18n( $quote->id ) );
                            ?>
                        </a>
                    </h3>
                    <div class="quote-status"><?php echo Helper::get_order_quote_status_html( $quote->status ); ?></div>
                </div>
                <div class="date-contents">
                    <label><?php echo esc_html( $formatted_quote_creation_date ); ?></label>
                </div>
            </div>
        </div>
        <div class="submission-area">
            <div id="quote-action-container" class='cart-collaterals'>
                <?php if ( $account_page && ( $is_pending_quote || $is_updated_quote || $is_approved_quote || $is_accepted_quote ) ) : ?>
                    <div class='dokan_cancel_button'>
                        <button id="cancelled_by_customer_button" type="submit" data-action="cancel"
                            data-quote_id="<?php echo esc_attr( $quote->id ); ?>" class="button status-updater"
                            data-message="<?php esc_html_e( 'Quote Successfully Cancelled', 'dokan' ); ?>">
                            <?php echo esc_html__( 'Cancel', 'dokan' ); ?>
                        </button>
                    </div>
                <?php endif; ?>
                <?php if ( ! $account_page && ( $is_pending_quote || $is_updated_quote || $is_approved_quote || $is_accepted_quote ) ) : ?>
                    <div class='dokan_reject_button'>
                        <button data-action="reject" data-quote_id="<?php echo esc_attr( $quote->id ); ?>"
                            id="rejected_by_vendor_button" class="button-secondary button-large status-updater" type="submit"
                            data-message="<?php esc_html_e( 'Quote Successfully Rejected', 'dokan' ); ?>">
                            <?php echo esc_html__( 'Reject the Deal', 'dokan' ); ?>
                        </button>
                    </div>
                <?php endif; ?>
                <?php if ( ! $account_page && ( $is_rejected_quote || $is_expired_quote ) ) : ?>
                    <div class='dokan_reopen_button'>
                        <input name='reopened_by_vendor' value='<?php echo esc_attr( Quote::STATUS_PENDING ); ?>' type='hidden' />
                        <button type="submit" value="<?php echo esc_attr( $quote->id ); ?>" name="reopened_by_vendor_button" class="button">
                            <?php echo esc_html__( 'Re-Open', 'dokan' ); ?>
                        </button>
                    </div>
                <?php endif; ?>
                <?php if ( ! $account_page && $is_pending_quote ) : ?>
                    <div class='dokan_convert_to_order_button'>
                        <input name='updated_by' value='Vendor' type='hidden' />
                        <input name='approved_by_vendor' value='approve' type='hidden' />
                        <button type="submit" value="<?php echo esc_attr( $quote->id ); ?>" name="approved_by_vendor_button" class="button button-secondary button-large quote-approve-button" data-update_label="<?php esc_attr_e( 'Update', 'dokan' ); ?>" data-approve_label="<?php esc_attr_e( 'Approve', 'dokan' ); ?>">
                            <?php echo esc_html__( 'Approve', 'dokan' ); ?>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if ( $account_page && $enable_to_convert && $is_accepted_quote && ! $is_converted_quote ) : ?>
                    <div class="dokan_convert_to_order_button">
                        <input name='converted_by' value='<?php echo isset( $converted_by ) ? esc_attr( $converted_by ) : 'Customer'; ?>' type='hidden'>
                        <button type="submit" value="<?php echo intval( $quote->id ); ?>" name="dokan_convert_to_order_customer" class="button button-primary button-large">
                            <?php echo esc_html__( 'Convert to Order', 'dokan' ); ?>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if ( ! $account_page && ( $is_approved_quote || $is_accepted_quote ) && ! $is_converted_quote ) : ?>
                    <div class="dokan_convert_to_order_button">
                        <input name='converted_by' value='<?php echo isset( $converted_by ) ? esc_attr( $converted_by ) : 'Customer'; ?>' type='hidden'>
                        <button type="submit" value="<?php echo intval( $quote->id ); ?>" name="dokan_convert_to_order_customer" class="button button-primary button-large">
                            <?php echo esc_html__( 'Convert to Order', 'dokan' ); ?>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if ( $account_page && ( $is_updated_quote || $is_approved_quote ) && ! $is_converted_quote ) : ?>
                    <div class="dokan_accept_to_order_button">
                        <input name='accepted_by_customer' value='<?php echo esc_attr( Quote::STATUS_ACCEPT ); ?>' type='hidden' />
                        <button type="submit" value="<?php echo intval( $quote->id ); ?>" name="accepted_by_customer_button" class="button button-primary button-large">
                            <?php echo esc_html__( 'Accept', 'dokan' ); ?>
                        </button>
                    </div>
                <?php endif; ?>

                <?php
                if ( ! empty( $quote->order_id ) && $is_converted_quote ) :
                    $order_id         = absint( $quote->order_id );
                    $quote_order      = wc_get_order( $order_id );
                    $vendor_order_url = wp_nonce_url(
                        add_query_arg(
                            [ 'order_id' => $order_id ],
                            dokan_get_navigation_url( 'orders' ),
                        ),
                        'dokan_view_order'
                    );

                    $order_url = $account_page ? $quote_order->get_view_order_url() : $vendor_order_url;
                    ?>
                    <div class='dokan_order_button'>
                        <a target='_blank' href='<?php echo esc_url( $order_url ); ?>' class='button dokan-btn dokan-btn-theme'>
                            <?php echo esc_html__( 'View Order', 'dokan' ); ?>
                        </a>
                    </div>
                <?php endif; ?>

                <?php wp_nonce_field( 'save_dokan_quote_action', 'dokan_quote_nonce' ); ?>
            </div>
        </div>
    </div>
</div>
