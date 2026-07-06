<?php
defined( 'ABSPATH' ) || exit;

$quote_subtotal = isset( $quote_totals['_subtotal'] ) ? $quote_totals['_subtotal'] : 0;
$vat_total      = isset( $quote_totals['_tax_total'] ) ? $quote_totals['_tax_total'] : 0;
$quote_total    = isset( $quote_totals['_total'] ) ? $quote_totals['_total'] : 0;
$offered_total  = isset( $quote_totals['_offered_total'] ) ? $quote_totals['_offered_total'] : 0;

?>

	<table class="shop_table shop_table_responsive table_quote_totals">

			<tr class="cart-subtotal">
				<th><?php esc_html_e( 'Subtotal (standard)', 'dokan' ); ?></th>
				<td data-title="<?php esc_attr_e( 'Subtotal (standard)', 'dokan' ); ?>"><?php echo wp_kses_post( wc_price( $quote_subtotal ) ); ?></td>
			</tr>
			<tr class="cart-subtotal offered">
				<th><?php esc_html_e( 'Offered Price Subtotal', 'dokan' ); ?></th>
				<td data-title="<?php esc_attr_e( 'Offered Price Subtotal', 'dokan' ); ?>"><?php echo wp_kses_post( wc_price( $offered_total ) ); ?></td>
			</tr>
	</table>

