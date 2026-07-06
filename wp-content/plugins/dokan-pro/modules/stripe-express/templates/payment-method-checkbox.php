<?php
/**
 * Payment method checkbox
 *
 * This template can be overridden by copying it to yourtheme/dokan/stripe-express/payment-method-checkbox.php.
 *
 * HOWEVER, on occasion Dokan will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does happen.
 *
 * @package Dokan/Templates
 *
 * @version 3.7.8
 *
 * @var string $id Field ID
 * @var bool $force_checked Force checked
 */
?>


<fieldset <?php echo $force_checked ? 'style="display:none;"' : ''; ?>>
    <p class="form-row woocommerce-SavedPaymentMethods-saveNew">
        <input id="<?php echo esc_attr( $id ); ?>"
            name="<?php echo esc_attr( $id ); ?>"
            type="checkbox"
            value="true"
            style="width:auto;"
            <?php echo $force_checked ? 'checked' : ''; ?>
        />
        <label for="<?php echo esc_attr( $id ); ?>" style="display:inline;">
            <?php
            echo esc_html(
                apply_filters(
                    'dokan_stripe_express_save_to_account_text',
                    __( 'Save payment information to my account for future purchases.', 'dokan' )
                )
            );
            ?>
        </label>
    </p>
</fieldset>
