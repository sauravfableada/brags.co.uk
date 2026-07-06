<?php
/**
 * Dokan Product Q&A Vendor Notification Email Template.
 *
 * An email sent to the vendor when a new Question is asked by customer.
 *
 * @class       Dokan_Email_Product_QA_Vendor
 * @version 3.11.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

	<p><?php esc_attr_e( 'Hello,', 'dokan' ); ?></p>

	<p><?php esc_attr_e( 'A new question is asked to one of your product', 'dokan' ); ?> <a href="<?php echo esc_url( $data['{product_link}'] ); ?>" ><?php echo esc_attr( $data['{product_name}'] ); ?></a> </p>
	<p><?php esc_attr_e( 'Summary of the question:', 'dokan' ); ?></p>
	<hr>
	<ul>
		<li>
			<strong>
				<?php esc_attr_e( 'Product Title :', 'dokan' ); ?>
			</strong>
			<?php printf( '<a href="%s">%s</a>', esc_url( $data['{product_link}'] ), esc_attr( $data['{product_name}'] ) ); ?>
		</li>
		<li>
			<strong>
				<?php esc_attr_e( 'Asked by :', 'dokan' ); ?>
			</strong>
			<?php echo esc_html( $data['{customer_name}'] ); ?>
		</li>
		<li>
			<strong>
				<?php esc_attr_e( 'question :', 'dokan' ); ?>
			</strong>
			<?php echo esc_html( $data['{question}'] ); ?>
		</li>
	</ul>

<?php

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
