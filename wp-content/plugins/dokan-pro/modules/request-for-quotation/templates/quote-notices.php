<?php
use WeDevs\DokanPro\Modules\RequestForQuotation\Helper;
use WeDevs\DokanPro\Modules\RequestForQuotation\SettingsHelper;

$is_pending_quote   = Helper::is_qoute_status( 'pending', $quote );
$is_expired_quote   = Helper::is_qoute_status( 'expired', $quote );
$is_updated_quote   = Helper::is_qoute_status( 'updated', $quote );
$is_approved_quote  = Helper::is_qoute_status( 'approve', $quote );
$is_rejected_quote  = Helper::is_qoute_status( 'reject', $quote );
$is_accepted_quote  = Helper::is_qoute_status( 'accepted', $quote );
$is_converted_quote = Helper::is_qoute_status( 'converted', $quote );
$is_cancelled_quote = Helper::is_qoute_status( 'cancel', $quote );

$expiry_rules       = Helper::get_quote_expiry_rules();
$expiry_date        = ! empty( $expiry_rules['expiry_date'] ) ? absint( $expiry_rules['expiry_date'] ) : 0;
$enable_expiry_date = ! empty( $expiry_rules['enable_expiry_date'] ) ? filter_var( $expiry_rules['enable_expiry_date'], FILTER_VALIDATE_BOOLEAN ) : false;
?>

<div id='dokan-quote-notice'>
    <?php if ( ! $is_account_page ) : ?>
        <?php if ( $is_converted_quote ) : ?>
            <div class='quote-notice converted-notice'>
                <span>
                    <?php
                    printf(
                        /* translators: %s: Converted role or null. */
                        esc_html__( 'The Quote Has been Converted to an Order%s.', 'dokan' ),
                        /* translators: %s: Order converted role. */
                        SettingsHelper::is_quote_converter_display_enabled() ? sprintf( esc_html__( ' By %s', 'dokan' ), esc_html( $quote->converted_by ) ) : ''
                    );
                    ?>
                </span>
            </div>
        <?php elseif ( $is_updated_quote ) : ?>
            <div class='quote-notice update-notice'>
                <span><?php esc_html_e( 'Updated Quote has been sent successfully to the customer. Wait for response.', 'dokan' ); ?></span>
            </div>
        <?php elseif ( $is_cancelled_quote ) : ?>
            <div class='quote-notice cancel-notice'>
                <span><?php esc_html_e( 'The Quote is Cancelled by the Customer.', 'dokan' ); ?></span>
            </div>
			<?php
        elseif ( $is_accepted_quote ) :
            $date = dokan_current_datetime()->setTimestamp( $quote->expiry_date )->format( 'jS M Y' );
            ?>
            <div class='quote-notice accepted-notice'>
                <span>
                    <?php
                    printf(
                        /* translators: %s: Quote expiry date. */
                        esc_html__( 'The Quote is Accepted by the Customer. Please convert it before the quote expires on %s.', 'dokan' ),
                        '<strong>' . esc_html( $date ) . '</strong>'
                    );
                    ?>
                </span>
            </div>
        <?php endif; ?>
    <?php else : ?>
        <?php if ( $is_pending_quote ) : ?>
            <div class='quote-notice pending-notice'>
                <span><?php esc_html_e( 'Your Quote Has Been Sent to the Vendor. You will Find the Vendor Revised Price in this Page After Vendor Review.', 'dokan' ); ?></span>
            </div>
        <?php elseif ( $is_converted_quote ) : ?>
            <div class='quote-notice converted-notice'>
                <span>
                    <?php
                    printf(
                    /* translators: %s: Converted role or null. */
                        esc_html__( 'The Quote Has been Converted to an Order%s.', 'dokan' ),
                        /* translators: %s: Order converted role. */
                        SettingsHelper::is_quote_converter_display_enabled() ? sprintf( esc_html__( ' By %s', 'dokan' ), esc_html( $quote->converted_by ) ) : ''
                    );
                    ?>
                </span>
            </div>
        <?php elseif ( $is_updated_quote ) : ?>
            <div class='quote-notice update-notice'>
                <span><?php esc_html_e( 'The Quote has been Updated by the vendor. See below.', 'dokan' ); ?></span>
            </div>
        <?php elseif ( $is_rejected_quote ) : ?>
            <div class='quote-notice rejected-notice'>
                <span><?php esc_html_e( 'The Vendor has Rejected the Quotation. Send a New Quote', 'dokan' ); ?></span>
            </div>
			<?php
        elseif ( $enable_expiry_date && ! empty( $expiry_date ) && ! empty( $quote->expiry_date ) &&
            ( $is_accepted_quote || $is_approved_quote ) && ! $is_converted_quote ) :
            $date                 = dokan_current_datetime()->setTimestamp( $quote->expiry_date )->format( 'jS M Y' );
            $customer_can_convert = SettingsHelper::is_convert_to_order_enabled();

            if ( $customer_can_convert ) :
                $expiry_msg = sprintf(
                    /* translators: %s: Quote expiry date. */
                    __( 'Please convert it before the quote expires on %s.', 'dokan' ),
                    '<strong>' . esc_html( $date ) . '</strong>'
                );
            else :
                $expiry_msg = sprintf(
                /* translators: %s: Quote expiry date. */
                    __( 'Vendor will be converted the quote before the quote expiry & between %s', 'dokan' ),
                    '<strong>' . $date . '</strong>'
                );
            endif;
            ?>
            <div class='quote-notice approved-notice'>
                <span><?php echo wp_kses_post( $expiry_msg ); ?></span>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ( ! $is_account_page && $enable_expiry_date && ! empty( $expiry_date ) ) : ?>
        <?php
        if ( $is_pending_quote ) :
            $expiry_date_string = sprintf(
                /* translators: %s: Expiry date. */
                _n( '%s day', '%s days', $expiry_date, 'dokan' ),
                number_format_i18n( $expiry_date )
            );
            ?>
            <div class='quote-notice pending-notice'>
                <?php
                printf(
                    /* translators: 1) Opening strong tag, 2) Expiry date, 3) Closing strong tag. */
                    esc_html( __( 'Please be Aware that the Quote Will Expire After %1$s%2$s from the Approval%3$s.', 'dokan' ) ),
                    '<strong>',
                    $expiry_date_string,
                    '</strong>'
                );
                ?>
            </div>
			<?php
        elseif ( $is_approved_quote && ! empty( $quote->expiry_date ) ) :
            $date = dokan_current_datetime()->setTimestamp( $quote->expiry_date )->format( 'jS M Y' );
            ?>
            <div class='quote-notice approved-notice'>
                <?php
                printf(
                /* translators: 1) Opening strong tag, 2) Expiry date, 3) Closing strong tag. */
                    esc_html( __( 'The quote will expire on %1$s.', 'dokan' ) ),
                    '<strong>' . $date . '</strong>'
                );
                ?>
            </div>
			<?php
        elseif ( $is_expired_quote && ! empty( $expiry_date ) ) :
            $date = dokan_current_datetime()->setTimestamp( $quote->expiry_date )->format( 'jS M Y' );
            ?>
            <div class='quote-notice expired-notice'>
                <?php
                printf(
                /* translators: 1) Expiry available days. */
                    esc_html( __( 'The quote expired after %1$s.', 'dokan' ) ),
                    '<strong>' . $date . '</strong>'
                );
                ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
