<?php
/**
 * Admin new wholesale customer email
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

	$opening_paragraph = __( 'A customer has been request for being wholesale. and is awaiting your approval. The details of this  are as follows:', 'dokan' );
?>

<?php do_action( 'woocommerce_email_header', $email_heading ); ?>


<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;" border="1" bordercolor="#eee">
	<tbody>
		<tr>
			<th scope="row" style="text-align:left; border: 1px solid #eee;"><?php esc_html_e( 'User Name', 'dokan' ); ?></th>
			<td style="text-align:left; border: 1px solid #eee;"><?php echo $user->display_name; ?></td>
		</tr>
		<tr>
			<th style="text-align:left; border: 1px solid #eee;" scope="row"><?php esc_html_e( 'User Email', 'dokan' ); ?></th>
			<td style="text-align:left; border: 1px solid #eee;"><?php echo $user->user_email; ?></td>
		</tr>
		<tr>
			<th style="text-align:left; border: 1px solid #eee;" scope="row"><?php esc_html_e( 'User NiceName', 'dokan' ); ?></th>
			<td style="text-align:left; border: 1px solid #eee;"><?php echo $user->user_nicename; ?></td>
		</tr>
		<tr>
			<th style="text-align:left; border: 1px solid #eee;" scope="row"><?php esc_html_e( 'User Total Spent', 'dokan' ); ?></th>
			<td style="text-align:left; border: 1px solid #eee;"><?php echo (int) wc_get_customer_total_spent( $user->ID ); ?></td>
		</tr>
	</tbody>
</table>

<p><?php esc_html_e( 'This request is awaiting your approval. Please check it and inform the customer if he is eligble or not.', 'dokan' ); ?></p>

<p><?php echo make_clickable( sprintf( '<a href="%s">%s</a>', untrailingslashit( admin_url() ) . '?page=dokan#/wholesale-customer', __( 'View and edit this this request in teh admin panel ', 'dokan' ) ) ); ?></p>

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
    echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}
do_action( 'woocommerce_email_footer' );
