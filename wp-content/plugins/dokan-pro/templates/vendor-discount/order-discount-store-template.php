<?php
/**
 * @var int|float $percentage
 * @var int|float $minimum_amount
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// translators: %1$s: minimum order amount, %2$s: currency symbol, %3$s: percentage amount, %4$s: percentage sign
$message = sprintf( __( 'Spend %1$s or more to get a %2$s%3$s Discount', 'dokan' ), wc_price( $minimum_amount ), number_format_i18n( $percentage ), '%' );
?>

<div class="store-order-discount-wrap">
    <div class="dokan-alert dokan-alert-info">
        <p>
        <?php
        echo wp_kses(
            $message, array(
				'span' => array(
					'class' => array(),
				),
				'bdi' => array(),
			)
        );
		?>
        </p>
    </div>
</div>
