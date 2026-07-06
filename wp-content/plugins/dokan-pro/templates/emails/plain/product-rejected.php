<?php
/**
 * Product Rejection Email Template - Plain Text Version
 *
 * This template can be overridden by copying it to:
 * yourtheme/dokan/emails/plain/product-rejected.php
 *
 * @version 3.16.0
 *
 * @var array $data Array of data for the email
 * @var string $email_heading Heading for the email
 * @var string $additional_content Additional content to display in the email
 * @var array $action_steps Array of action steps for the vendor
 * @var WC_Email $email WC_Email object
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

echo '= ' . $email_heading . " =\n\n";

echo __( 'Hello,', 'dokan' ) . "\n\n";

// Main message
printf(
// translators: %s: product name
    __( 'Your product "%s" has been reviewed and requires some updates before it can be approved.', 'dokan' ),
    $data['product']['name']
);
echo "\n\n";

echo __( 'Summary of review:', 'dokan' );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

// translators: %s: product name
printf( __( 'Product: %s', 'dokan' ), $data['product']['name'] );
echo "\n";

// translators: %s: product URL
printf( __( 'Product URL: %s', 'dokan' ), esc_url( $data['product']['edit_link'] ) );
echo "\n";

// translators: %s: review date
printf( __( 'Review Date: %s', 'dokan' ), $data['rejection']['date'] );
echo "\n\n";

// Rejection reason section
echo __( 'The reason for the rejection:', 'dokan' );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo wp_strip_all_tags( wpautop( $data['rejection']['reason'] ) );
echo "\n\n";

// Recommended Actions Section
echo __( 'Recommended Actions:', 'dokan' );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

foreach ( $action_steps as $step ) {
    printf(
        "%d. %s\n   %s\n\n",
        $step['num'],
        $step['title'],
        $step['desc']
    );
}

// Quick Links
echo __( 'Quick Actions:', 'dokan' );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

// translators: %s: product edit link
printf( __( 'To edit your product, visit: %s', 'dokan' ), esc_url( $data['product']['edit_link'] ) );
echo "\n\n";

// Additional content
if ( ! empty( $additional_content ) ) {
    echo wp_strip_all_tags( wpautop( wptexturize( $additional_content ) ) );
    echo "\n\n";
}

// Footer
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo wp_strip_all_tags( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
