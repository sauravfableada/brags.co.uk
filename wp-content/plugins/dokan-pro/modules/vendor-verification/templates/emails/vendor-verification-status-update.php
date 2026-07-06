<?php
/**
 * Vendor Verification Status Update Email.
 *
 * An email sent to the vendor after updating the verification status by admin.
 *
 * @since 3.7.23
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email );

?>
    <p>
        <?php
        echo sprintf(
            // translators: 1: Store Name.
            __( 'Hello %s,', 'dokan' ),
            wp_strip_all_tags( $store_name )
        );
        ?>
    </p>

    <p>
        <?php
        echo sprintf(
            // translators: 1: Document Type, 2: Verification Status.
            __( 'Your %1$s verification request has been %2$s by the admin.', 'dokan' ),
            wp_strip_all_tags( $document_type ),
            wp_strip_all_tags( $verification_status )
        );
        ?>
    </p>

    <p>
        <?php
        echo sprintf(
            // translators: 1: Home URL.
            __( 'You can check out it by going <a href="%s">here</a>.', 'dokan' ),
            esc_url( $home_url )
        );
        ?>
    </p>

    <p>
        <?php esc_attr_e( 'From: Admin', 'dokan' ); ?>
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
