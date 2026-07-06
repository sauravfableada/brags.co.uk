<?php
/**
 * Dokan Product Q&A Customer Notification Email Template.
 *
 * An email sent to the customer when a Question is answered by vendor.
 *
 * @class       Dokan_Email_Product_QA_Customer
 * @version 3.11.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

	<p><?php esc_attr_e( 'Hello,', 'dokan' ); ?></p>

	<p><?php esc_attr_e( 'Your question is answered by the vendor of the product.', 'dokan' ); ?> <a href="<?php echo esc_url( $data['{product_url}'] ); ?>" ><?php echo esc_attr( $data['{product_name}'] ); ?></a> </p>
	<p><?php esc_attr_e( 'Summary of the question and answer:', 'dokan' ); ?></p>
	<hr>
	<ul>
		<li>
			<strong>
				<?php esc_attr_e( 'Product :', 'dokan' ); ?>
			</strong>
			<?php printf( '<a href="%s">%s</a>', esc_url( $data['{product_url}'] ), esc_attr( $data['{product_name}'] ) ); ?>
		</li>
		<li>
			<strong>
				<?php esc_attr_e( 'question :', 'dokan' ); ?>
			</strong>
			<?php echo esc_html( $data['{question}'] ); ?>
		</li>

        <li>
            <strong>
				<?php esc_attr_e( 'Answered by :', 'dokan' ); ?>
            </strong>
			<?php echo esc_html( $data['{seller_name}'] ); ?>
        </li>
        <li>
            <strong>
				<?php esc_attr_e( 'Answer :', 'dokan' ); ?>
            </strong>
			<?php echo wp_kses_post( $data['{answer}'] ); ?>
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
