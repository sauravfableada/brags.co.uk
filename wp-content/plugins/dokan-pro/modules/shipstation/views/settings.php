<?php
/**
 * Dokan Dashbaord Shipstation Settings Form Template.
 *
 * @since 1.0.0
 * @since 3.14.4 Added the `$vendor_id` and `$api_credential` vars.
 *
 * @var int    $vendor_id       Vendor ID.
 * @var string $auth_key        Auth Key.
 * @var array  $api_credential  API Credential.
 * @var array  $statuses        Order Statuses.
 * @var array  $export_statuses Export Statuses.
 * @var string $shipped_status  Shipped Status.
 *
 * @package dokan
 */

defined( 'ABSPATH' ) || exit;

do_action( 'dokan_shipstation_vendor_settings_section_before' );
?>
    <div id="dokan-shipstation-vendor-settings" class="dokan-shipstation-vendor-settings-wrapper">
        <?php do_action( 'dokan_shipstation_vendor_settings_credential_panel_before' ); ?>
        <div id="dokan-panel-shipstation-credential" class="dokan-panel dokan-panel-default">
            <div class="dokan-panel-heading"><strong><?php esc_html_e( 'ShipStation Credential Details', 'dokan' ); ?></strong></div>
            <div class="dokan-panel-body general-details">
                <div class="dokan-clearfix dokan-panel-inner-container dokan-shipstation-vendor-settings-section-content">
                    <?php if ( ! $api_credential['key_id'] ) : ?>
                        <div id="dokan-form-group-shipstation-generate-credential" class="dokan-form-group">
                            <div class="dokan-w9">
                                <p class="dokan-shipstation-panel-desc"><?php esc_html_e( 'Generate credential to connect your store with ShipStation.', 'dokan' ); ?></p>
                            </div>
                            <div class="dokan-w3">
                                <button type="button"
                                    id="dokan-shipstation-generate-credentials-btn"
                                    class="dokan-btn dokan-btn-theme dokan-btn-sm dokan-shipstation-credentials-btn"
                                    data-vendor_id="<?php echo esc_attr( $vendor_id ); ?>"
                                >
                                    <?php esc_html_e( 'Generate Credentials', 'dokan' ); ?>
                                    <i class="fa fa-spinner fa-spin" id="dokan-shipstation-generate-credentials-spinner" style="display: none;"></i>
                                </button>
                            </div>
                        </div>
                    <?php else : ?>
                        <div class='dokan-form-group'>
                            <div class="dokan-w9">
                                <p class="dokan-shipstation-panel-desc"><?php esc_html_e( 'Use these credentials to connect your ShipStation account.', 'dokan' ); ?></p>
                            </div>
                            <div class="dokan-w3">
                                <button type="button"
                                        id="dokan-shipstation-revoke-credentials-btn"
                                        class="dokan-btn dokan-btn-sm dokan-shipstation-credentials-btn"
                                        data-vendor_id="<?php echo esc_attr( $vendor_id ); ?>"
                                        data-key_id="<?php echo esc_attr( $api_credential['key_id'] ); ?>"
                                >
                                    <?php esc_html_e( 'Revoke Credentials', 'dokan' ); ?>
                                    <i class="fa fa-spinner fa-spin" id="dokan-shipstation-revoke-credentials-spinner" style="display: none;"></i>
                                </button>
                            </div>
                        </div>

                        <div class="dokan-form-group">
                            <label class="dokan-w3 dokan-control-label" for="dokan-shipstation-auth-key"><?php esc_html_e( 'Authentication Key', 'dokan' ); ?>
                                <span class="dokan-tooltips-help tips" title="" data-placement="bottom" data-original-title="<?php esc_html_e( 'This is the Auth Key you set in ShipStation and allows ShipStation to communicate with your store.', 'dokan' ); ?>">
                                    <i class="fas fa-question-circle"></i>
                                </span>
                            </label>
                            <div class="dokan-w6 dokan-text-left">
                                <code class="dokan-shipstation-code"><?php echo esc_html( $auth_key ); ?></code>
                                <span class="dokan-copy-to-clipboard" data-copy="<?php echo esc_attr( $auth_key ); ?>"></span>
                            </div>
                        </div>

                        <div class="dokan-form-group">
                            <label class="dokan-w3 dokan-control-label" for="dokan-shipstation-consumer-key"><?php esc_html_e( 'Consumer Key', 'dokan' ); ?>
                                <span class="dokan-tooltips-help tips" title="" data-placement="bottom" data-original-title="<?php esc_html_e( 'An unique identifier required for establishing a secure connection between your store and ShipStation.', 'dokan' ); ?>">
                                    <i class="fas fa-question-circle"></i>
                                </span>
                            </label>
                            <div class="dokan-w6 dokan-text-left">
                                <code class="dokan-shipstation-code"><?php echo esc_html( $api_credential['consumer_key'] ); ?></code>
                                <span class="dokan-copy-to-clipboard" data-copy="<?php echo esc_attr( $api_credential['consumer_key'] ); ?>"></span>
                            </div>
                        </div>

                        <?php if ( $api_credential['consumer_secret'] ) : ?>
                            <div class="dokan-form-group">
                                <label class="dokan-w3 dokan-control-label" for="dokan-shipstation-consumer-secret"><?php esc_html_e( 'Consumer Secret', 'dokan' ); ?>
                                    <span class="dokan-tooltips-help tips" title="" data-placement="bottom" data-original-title="<?php esc_html_e( 'Functions as a secure password for API access. It is imperative that this key is kept confidential.', 'dokan' ); ?>">
                                    <i class="fas fa-question-circle"></i>
                                </span>
                                </label>
                                <div class="dokan-w6 dokan-text-left">
                                    <code class="dokan-shipstation-code"><?php echo esc_html( $api_credential['consumer_secret'] ); ?></code>
                                    <span class="dokan-copy-to-clipboard" data-copy="<?php echo esc_attr( $api_credential['consumer_secret'] ); ?>"></span>
                                    <div class="dokan-shipstation-input-warning-wrapper">
                                        <p><?php esc_html_e( 'Note: Once this page is refreshed, the consumer secret will no longer be available.', 'dokan' ); ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php delete_transient( "dokan_shipstation_wc_api_consumer_secret_Key_for_vendor_{$vendor_id}" ); ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php do_action( 'dokan_shipstation_vendor_settings_credential_panel_after' ); ?>

        <?php do_action( 'dokan_shipstation_vendor_settings_order_status_panel_before' ); ?>
        <div id="dokan-panel-shipstation-order-status" class="dokan-panel dokan-panel-default">
            <div class="dokan-panel-heading"><strong><?php esc_html_e( 'Order Status Settings', 'dokan' ); ?></strong></div>
            <div class="dokan-panel-body general-details">
                <div id="dokan-shipstation-shipping-inputs-wrapper" class="dokan-clearfix dokan-panel-inner-container dokan-shipstation-vendor-settings-section-content">
                    <form method="post" id="dokan-shipstation-settings-form"  action="" class="dokan-form">
                        <div class="dokan-form-group">
                            <label class="dokan-w3 dokan-control-label" for="dokan-shipstation-export-statuses"><?php esc_html_e( 'Export Order Statuses', 'dokan' ); ?>
                                <span class="dokan-tooltips-help tips" data-placement="bottom" data-original-title="<?php esc_html_e( 'Define the order statuses you wish to export to ShipStation.', 'dokan' ); ?>">
                                    <i class="fas fa-question-circle"></i>
                                </span>
                            </label>
                            <div class="dokan-w8 dokan-text-left">
                                <select name="dokan_shipstation_export_statuses[]" id="dokan-shipstation-export-statuses" class="dokan-select2 dokan-form-control" multiple>
                                    <?php foreach ( $statuses as $status => $label ) : ?>
                                        <option value="<?php echo esc_attr( $status ); ?>"<?php echo in_array( $status, $export_statuses ) ? ' selected' : ''; ?>><?php echo esc_html( $label ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="dokan-form-group">
                            <label class="dokan-w3 dokan-control-label" for="dokan-shipstation-order-status"><?php esc_html_e( 'Shipped Order Status', 'dokan' ); ?>
                                <span class="dokan-tooltips-help tips" data-placement="bottom" data-original-title="<?php esc_html_e( 'Define the order status you wish to update to once an order has been shipping via ShipStation. By default this is Completed.', 'dokan' ); ?>">
                                    <i class="fas fa-question-circle"></i>
                                </span>
                            </label>
                            <div class="dokan-w8 dokan-text-left">
                                <select name="dokan_shipstation_shipped_status" id="dokan-shipstation-order-status" class="dokan-select2 dokan-form-control">
                                    <?php foreach ( $statuses as $status => $label ) : ?>
                                        <option value="<?php echo esc_attr( $status ); ?>" <?php selected( $shipped_status, $status ); ?>><?php echo esc_html( $label ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="dokan-form-group">
                            <div class="dokan-w3">
                                <input type="hidden" name="dokan_shipstation_vendor_id" id="dokan_shipstation_vendor_id" value="<?php echo absint( $vendor_id ); ?>">
                                &nbsp;
                            </div>
                            <div class="dokan-w8 dokan-text-left">
                                <button
                                    type="submit"
                                    id='dokan-store-shipstation-form-submit'
                                    class="dokan-left dokan-btn dokan-btn-theme"
                                >
                                    <?php esc_html_e( 'Save Changes', 'dokan' ); ?>
                                    <i class="fa fa-spinner fa-spin" id="dokan-shipstation-form-submit-spinner" style="display: none;"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php do_action( 'dokan_shipstation_vendor_settings_order_status_panel_after' ); ?>
    </div>
<?php
do_action( 'dokan_shipstation_vendor_settings_section_after' );
