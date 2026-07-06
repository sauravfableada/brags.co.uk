<?php
/**
 * Printful Vendor Settings template
 *
 * @var bool   $connected        Printful connected or not.
 * @var array  $store            Printful Store Information.
 * @var string $site_currency    Site Currency.
 * @var string $shipping_enabled Printful Shipping Enabled.
 * @var string $rates_enabled    Marketplace Rates Enabled.
 */

defined( 'ABSPATH' ) || exit;

do_action( 'dokan_pro_printful_vendor_settings_section_before' );
?>
<div id="dokan-pro-printful-vendor-settings" class="dokan-pro-printful-vendor-settings-wrapper">
    <div class="dokan-pro-printful-vendor-settings-section-content">
        <?php do_action( 'dokan_pro_printful_vendor_settings_notices_before' ); ?>

        <div class='dokan-alert dokan-alert-warning dokan-panel-alert'>
            <p><?php esc_html_e( 'With the Printful integration, you are responsible for all shipping arrangements for your products. Ensure your Printful account is set up correctly to handle these responsibilities.', 'dokan' ); ?></p>
        </div>

        <div class='dokan-alert dokan-alert-warning dokan-panel-alert'>
            <p>
                <strong><?php esc_html_e( 'Important: Set Accurate Store Currency in Printful', 'dokan' ); ?></strong><br>
                <?php
                printf(
                /* translators: 1) Site Currency, 2) Opening Strong Tag, 3) Closing Strong Tag, 4) Line Break */
                    esc_html__( 'For successful integration with Printful, ensure that the currency set in your Marketplace %2$s(%1$s)%3$s matches the currency in your Printful account. You will find the Currency option in %2$sPrintful Dashboard → Billing → Billing Methods → Store Billing Settings%3$s.%4$sThis will help avoid any inaccurate conversion in pricing and order cancellation.', 'dokan' ),
                    $site_currency,
                    '<strong>',
                    '</strong>',
                    '<br>',
                )
                ?>
            </p>
        </div>

        <?php do_action( 'dokan_pro_printful_vendor_settings_notices_after' ); ?>
    </div>

    <?php do_action( 'dokan_pro_printful_vendor_settings_integration_panel_before' ); ?>
    <div class="dokan-panel dokan-panel-default">
        <div class="dokan-panel-heading"><strong><?php esc_html_e( 'Printful Integration', 'dokan' ); ?></strong></div>
        <div class="dokan-panel-body general-details">
            <div class="dokan-clearfix dokan-panel-inner-container dokan-pro-printful-vendor-settings-section-content">
                <?php if ( ! $connected ) : ?>
                    <div class='dokan-form-group'>
                        <div class="dokan-w8">
                            <p><?php esc_html_e( 'Connect your store with Printful to add custom products.', 'dokan' ); ?></p>
                        </div>
                        <div class="dokan-w5">
                            <button type="button"
                                    id="dokan-pro-connect-printful-btn"
                                    class="dokan-btn dokan-btn-theme dokan-btn-sm dokan-pro-connect-printful-btn"
                                    data-nonce="<?php echo esc_attr( wp_create_nonce( 'dokan-printful-vendor-connect' ) ); ?>">
                                <?php esc_html_e( 'Connect with Printful', 'dokan' ); ?>
                                <i class="fa fa-spinner fa-spin" id="dokan-pro-connect-printful-spiner" style="display: none;"></i>
                            </button>
                        </div>
                    </div>
                <?php else : ?>
                    <div class='dokan-form-group'>
                        <div class="dokan-w8">
                            <p>
                                <?php
                                printf(
                                /* translators: 1) Opening Strong Tag, 2) Printful Store Name, 3) Closing Strong Tag */
                                    esc_html__(
                                        'Connected with Printful Store: %1$s%2$s%3$s', 'dokan'
                                    ),
                                    '<strong>',
                                    $store['name'] ?? '',
                                    '</strong>'
                                );
                                ?>
                            </p>
                        </div>
                        <div class="dokan-w5">
                            <button type="button"
                                    id="dokan-pro-disconnect-printful-btn"
                                    class="dokan-btn dokan-btn-theme dokan-btn-sm dokan-pro-disconnect-printful-btn"
                                    data-nonce="<?php echo esc_attr( wp_create_nonce( 'dokan-printful-vendor-disconnect' ) ); ?>">
                                <?php esc_html_e( 'Disconnect', 'dokan' ); ?>
                                <i class="fa fa-spinner fa-spin" id="dokan-pro-disconnect-printful-spiner" style="display: none;"></i>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php do_action( 'dokan_pro_printful_vendor_settings_integration_panel_after' ); ?>

    <?php do_action( 'dokan_pro_printful_vendor_shipping_settings_panel_before' ); ?>
    <div class="dokan-panel dokan-panel-default">
        <div class="dokan-panel-heading">
            <strong><?php esc_html_e( 'Shipping Settings', 'dokan' ); ?></strong>
            <i class='fas fa-question-circle tips' data-title='<?php esc_attr_e( 'If both options are enabled, customers will see both Printful and marketplace shipping methods at checkout.', 'dokan' ); ?>'></i>
        </div>
        <div class="dokan-panel-body general-details">
            <div id="dokan-pro-printful-shipping-inputs-wrapper" class="dokan-clearfix dokan-panel-inner-container dokan-pro-printful-vendor-settings-section-content">
                <div class="dokan-form-group">
                    <div class="dokan-switch-container">
                        <label class="dokan-w8 dokan-control-label" for="enable_shipping">
                            <strong><?php esc_html_e( 'Printful Shipping Methods', 'dokan' ); ?></strong>
                        </label>

                        <div class="dokan-w5 dokan-text-right">
                            <label class="switch dokan-switch">
                                <input
                                    type="checkbox"
                                    id="dokan-pro-printful-enable-shipping-toggle"
                                    <?php echo 'yes' === esc_html( $shipping_enabled ) ? 'checked' : ''; ?>
                                    data-nonce="<?php echo esc_attr( wp_create_nonce( 'dokan-printful-enable-shipping' ) ); ?>"
                                >
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>

                    <div class='dokan-w10 printful-description'>
                        <?php esc_html_e( 'When this option is enabled, customers will see shipping methods provided by Printful at checkout. These rates are based on Printful\'s fulfillment services and may vary depending on the shipping destination and product type.', 'dokan' ); ?>
                    </div>
                </div>

                <div class="dokan-form-group">
                    <div class="dokan-switch-container">
                        <label class="dokan-w8 dokan-control-label" for="enable_rates">
                            <strong><?php esc_html_e( 'Marketplace Shipping Methods', 'dokan' ); ?></strong>
                        </label>

                        <div class="dokan-w5 dokan-text-right">
                            <label class="switch dokan-switch">
                                <input
                                    type="checkbox"
                                    id="dokan-pro-printful-enable-rates-toggle"
                                    <?php echo 'no' !== esc_html( $rates_enabled ) ? 'checked' : ''; ?>
                                    data-nonce="<?php echo esc_attr( wp_create_nonce( 'dokan-printful-enable-rates' ) ); ?>"
                                >
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>

                    <div class='dokan-w10 printful-description'>
		                <?php esc_html_e( 'When this option is enabled, customers will see the standard shipping methods as per marketplace settings.', 'dokan' ); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php do_action( 'dokan_pro_printful_vendor_shipping_settings_panel_after' ); ?>
</div>
<?php
do_action( 'dokan_pro_printful_vendor_settings_section_after' );
