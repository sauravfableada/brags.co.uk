<?php
/**
 * Template for displaying product rejection message
 *
 * @since 3.16.0
 *
 * @var string $rejection_message Rejection message from admin
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="dokan-alert dokan-alert-warning">
    <strong><?php esc_html_e( 'Reason for Rejection:', 'dokan' ); ?></strong>
    <?php echo wp_kses_post( wpautop( $rejection_message ) ); ?>
</div>
