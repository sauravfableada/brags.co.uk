<?php
defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

    <p>
        <?php
        // translators: Vendor Name.
        echo esc_html( sprintf( __( 'Hello %s', 'dokan' ), $invoice->vendor_name ) );
        ?>
    </p>
    <p>
        <?php
        echo wp_kses_post(
            sprintf(
                // translators: Invoice URL.
                __( 'Please pay the outstanding <a href="%s" target="_blank" >subcription bill</a> and confirm your payment method to avoid subscription cancellation.', 'dokan' ),
                esc_url( $invoice->invoice_url )
            )
        );
        ?>
    </p>

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
    echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
