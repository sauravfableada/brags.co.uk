<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * @var WC_Order_Refund $refund The refund object.
 */
$vendor_id = dokan_get_seller_id_by_order( $refund );
$vendor    = dokan()->vendor->get( $vendor_id );
?>
<tr class="refund <?php echo ( ! empty( $class ) ) ? $class : ''; ?>" data-order_refund_id="<?php echo $refund->get_id(); ?>">
    <!-- <td class="check-column"></td> -->

    <td class="thumb">
        <div><i class="far fa-money-bill-alt"></i></div>
    </td>

    <td class="name">
        <?php
        echo esc_attr__( 'Refund', 'dokan' ) . ' #' . $refund->get_id() . ' - ' . dokan_format_datetime( $refund->get_date_created() );

        if ( $vendor->get_id() ) {
            echo esc_attr_x( 'by ', 'Ex: Refund - $date >by< $username', 'dokan' ) . '<abbr class="refund_by" title="' . esc_attr__( 'ID: ', 'dokan' ) . absint( $vendor->get_id() ) . '">' . esc_attr( $vendor->get_shop_name() ) . '</abbr>';
        }
        ?>
        <?php if ( $refund->get_reason() ) : ?>
            <p class="description"><?php echo esc_html( $refund->get_reason() ); ?></p>
        <?php endif; ?>
        <input type="hidden" class="order_refund_id" name="order_refund_id[]" value="<?php echo absint( $refund->get_id() ); ?>" />
    </td>

    <?php do_action( 'woocommerce_admin_order_item_values', null, $refund, absint( $refund->get_id() ) ); ?>

    <td class="item_cost" width="1%">&nbsp;</td>
    <td class="quantity" width="1%">&nbsp;</td>

    <td class="line_cost" width="1%">
        <div class="view">
            <?php echo wc_price( '-' . $refund->get_amount() ); ?>
        </div>
    </td>

    <?php
    if ( ( ! isset( $legacy_order ) || ! $legacy_order ) && wc_tax_enabled() ) :
        for ( $i = 0; $i < count( $order_taxes ); $i++ ) : ?>
            <td class="line_tax" width="1%"></td>
        <?php
        endfor;
    endif;
    ?>

    <td class="wc-order-edit-line-item">
        <div class="wc-order-edit-line-item-actions">
            <!-- <a class="delete_refund" href="#">X</a> -->
        </div>
    </td>
</tr>
