<?php
/**
 * Vendor Verification Request Submission Email.
 *
 * An email sent to the admin when a vendor submit a document for verification.
 *
 * @since 3.7.23
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email );

?>
    <p><?php esc_html_e( 'Hi,', 'dokan' ); ?></p>

    <p>
        <?php
        echo sprintf(
            // translators: 1: Store Name.
            __( 'A new verification request has been made by %s.', 'dokan' ),
            wp_strip_all_tags( $store_name )
        );
        ?>
    </p>

    <p>
        <?php
        echo sprintf(
            // translators: 1: Admin URL, 2: Dokan Admin Dashboard.
            __( 'You can approve or reject it by going to the <a href="%1$s">%2$s</a>.', 'dokan' ),
            esc_url( $admin_url ),
            __( 'Dokan Admin Dashboard', 'dokan' )
        );
        ?>
    </p>

    <p>
        <?php
        /**
         * Show user-defined additional content - this is set in each email's settings.
         */
        if ( $additional_content ) {
            echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
        }
        ?>
    </p>

<?php

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
