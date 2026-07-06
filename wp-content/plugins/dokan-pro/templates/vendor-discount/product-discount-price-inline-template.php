<?php
/**
 * @var int|float $percentage
 * @var int       $quantity
 * @var string    $price_html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// translators: %1$s: quantity, %2$s: percentage, %3$s: percentage amount, %4$s: percentage sign
$message = sprintf( __( 'Buy %1$s to get %2$s%3$s discount', 'dokan' ), number_format_i18n( $quantity ), number_format_i18n( $percentage ), '%' );
?>

<span class="dokan-discount-price-amount">
    <?php echo $price_html; ?>
    </br>
    <span class="dokan-discount-amount-text">
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
        </span>
</span>
