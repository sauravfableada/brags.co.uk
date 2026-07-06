<?php
/**
 * New Conversation Notification Email.
 *
 * An email sent to the vendor or customer when a warranty request conversation is made by customer or vendor.
 *
 * @class       Dokan_Rma_Conversation_Notification
 * @version     3.9.5
 *
 * @var array $data Email info.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$to_name   = $data['to_name'] ?? '';
$from_name = $data['from_name'] ?? '';
$message   = $data['message'] ?? '';
$rma_url   = $data['rma_url'] ?? '';

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>
    <p>
        <?php
        // translators: %s from name.
        echo wp_kses_post( sprintf( __( 'Hello %s,', 'dokan' ), $to_name ) );
        ?>
    </p><p>
        <?php
        // translators: %s from name.
        echo wp_kses_post( sprintf( __( 'A new reply has been added to the conversation by %s', 'dokan' ), $from_name ) );
        ?>
    </p>
    <hr>
    <p>
        <?php echo wp_kses_post( $message ); ?>
    </p>
    <hr>
    <p>
        <?php
        // translators: %s dashboard url.
        echo wp_kses_post( sprintf( __( 'You can check this reply by clicking <a href="%s">here</a>.', 'dokan' ), esc_url( $rma_url ) ) );
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
