<?php

use WeDevs\DokanPro\Modules\Razorpay\Helper;

/**
 * @var array $bank_account_types
 * @var array $razorpay_business_types
 * @var string $ajax_url
 */

$existing_razorpay_id = get_user_meta( get_current_user_id(), Helper::get_seller_account_id_key_trashed(), true );
?>

<div class="dokan-razorpay-modals"></div>
<script type="text/html" id="tmpl-dokan-razorpay-vendor-account-register">
    <div class="dokan-popup-content white-popup dokan-razorpay-account-popup-wrapper" id="dokan-razorpay-account-popup" style="width: 600px;">
        <h2 class="dokan-popup-title dokan-razorpay-account-title">
            <?php esc_html_e( 'Connect Razorpay Account', 'dokan' ); ?>
        </h2>

        <form action="<?php echo esc_url( $ajax_url ); ?>" method="POST" id="dokan-razorpay-vendor-register-form">
            <div class="vendor-register-form-container">
                <div class="dokan-razorpay-already-registered">
                    <label for="dokan_razorpay_existing_user_chekbox">
                        <input type="checkbox" name="razorpay_existing_user" id="dokan_razorpay_existing_user_chekbox">
                        <?php esc_html_e( 'I\'ve already an account', 'dokan' ); ?>
                    </label>

                    <?php if ( ! empty( $existing_razorpay_id ) ) : ?>
                        <div class="dokan-existing-account-info dokan-hide">
                            <?php esc_html_e( 'Existing Razorpay Account: ', 'dokan' ); ?>
                            <strong><?php echo esc_html( $existing_razorpay_id ); ?></strong>
                            <span class="account-hint">&nbsp;( <?php esc_html_e( 'To use this account, just write it below or give any razorpay account ID.', 'dokan' ); ?> )</span>
                        </div>
                    <?php endif; ?>

                    <input
                        name="razorpay_account_id"
                        class="dokan-form-control dokan-hide"
                        id="dokan_razorpay_account_id"
                        placeholder="<?php esc_html_e( 'Razorpay Account ID; eg: acc_', 'dokan' ); ?>"
                        type="text"
                    >
                </div>

                <div id="dokan-razorpay-new-connect">
                    <div class="dokan-form-group dokan-clearfix">
                        <div class="content-half-part">
                            <label class="dokan-control-label" for="razorpay_account_email">
                                <?php esc_html_e( 'Email', 'dokan' ); ?>
                                <span class="dokan-text-required">*</span>
                            </label>
                            <input
                                name="razorpay_account_email"
                                class="dokan-form-control"
                                id="razorpay_account_email"
                                placeholder="<?php esc_html_e( 'Your Email', 'dokan' ); ?>"
                                type="email"
                                required
                            >
                        </div>

                        <div class="content-half-part">
                            <label class="dokan-control-label" for="razorpay_account_phone">
                                <?php esc_html_e( 'Phone', 'dokan' ); ?>
                                <span class="dokan-text-required">*</span>
                            </label>
                            <input
                                name="razorpay_account_phone"
                                class="dokan-form-control"
                                id="razorpay_account_phone"
                                placeholder="<?php esc_html_e( 'Your Phone', 'dokan' ); ?>"
                                type="text"
                                required
                            >
                        </div>
                    </div>

                    <div class="dokan-razorpay-form-group">
                        <p><b><?php esc_html_e( 'Business Information', 'dokan' ); ?></b></p>
                        <div class="dokan-form-group dokan-clearfix">
                            <div class="content-half-part">
                                <label for="razorpay_business_name" class="dokan-control-label">
                                    <?php esc_html_e( 'Your Company name', 'dokan' ); ?>
                                    <span class="dokan-text-required">*</span>
                                </label>
                                <input
                                    name="razorpay_business_name"
                                    class="dokan-form-control"
                                    id="razorpay_business_name"
                                    placeholder="<?php esc_html_e( 'Your Company Name', 'dokan' ); ?>"
                                    type="text"
                                    required
                                >
                            </div>

                            <div class="content-half-part">
                                <label for="razorpay_business_type" class="dokan-control-label">
                                    <?php esc_html_e( 'Your company type', 'dokan' ); ?>
                                    <span class="dokan-text-required">*</span>
                                </label>
                                <select name="razorpay_business_type" id="razorpay_business_type" required>
                                    <?php foreach ( $razorpay_business_types as $key => $business_type ) : ?>
                                        <option value="<?php echo esc_attr( $key ); ?>">
                                            <?php echo esc_attr( $business_type ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="dokan-clearfix"></div>
                    </div>
                    <div class="dokan-razorpay-form-group">
                        <p><b><?php esc_html_e( 'Address', 'dokan' ); ?></b></p>
                        <div class="dokan-form-group dokan-clearfix">
                                <div class="content-half-part">
                                    <label for="razorpay_address_line1" class="dokan-control-label">
                                        <?php esc_html_e( 'Address Line 1', 'dokan' ); ?>
                                        <span class="dokan-text-required">*</span>
                                    </label>
                                    <input
                                        name="razorpay_address_line1"
                                        class="dokan-form-control"
                                        id="razorpay_address_line1"
                                        placeholder="<?php esc_html_e( 'Address Line 1', 'dokan' ); ?>"
                                        type="text"
                                        required
                                    >
                                </div>

                                <div class="content-half-part">
                                    <label for="razorpay_address_line2" class="dokan-control-label">
                                        <?php esc_html_e( 'Address Line 2', 'dokan' ); ?>
                                        <span class="dokan-text-required">*</span>
                                    </label>
                                    <input
                                        name="razorpay_address_line2"
                                        class="dokan-form-control"
                                        id="razorpay_address_line2"
                                        placeholder="<?php esc_html_e( 'Address Line 2', 'dokan' ); ?>"
                                        type="text"
                                        required
                                    >
                                </div>
                            </div>
                        <div class="dokan-form-group dokan-clearfix">
                            <div class="content-half-part">
                                <label for="razorpay_city" class="dokan-control-label">
                                    <?php esc_html_e( 'City', 'dokan' ); ?>
                                    <span class="dokan-text-required">*</span>
                                </label>
                                <input
                                    name="razorpay_city"
                                    class="dokan-form-control"
                                    id="razorpay_city"
                                    placeholder="<?php esc_html_e( 'City', 'dokan' ); ?>"
                                    type="text"
                                    required
                                >
                            </div>

                            <div class="content-half-part">
                                <label for="razorpay_state" class="dokan-control-label">
                                    <?php esc_html_e( 'State', 'dokan' ); ?>
                                    <span class="dokan-text-required">*</span>
                                </label>
                                <input
                                    name="razorpay_state"
                                    class="dokan-form-control"
                                    id="razorpay_state"
                                    placeholder="<?php esc_html_e( 'State', 'dokan' ); ?>"
                                    type="text"
                                    required
                                >
                            </div>
                        </div>
                        <div class="dokan-form-group dokan-clearfix">
                            <div class="content-half-part">
                                <label for="razorpay_postal_code" class="dokan-control-label">
                                    <?php esc_html_e( 'Postal Code', 'dokan' ); ?>
                                    <span class="dokan-text-required">*</span>
                                </label>
                                <input
                                    name="razorpay_postal_code"
                                    class="dokan-form-control"
                                    id="razorpay_postal_code"
                                    placeholder="<?php esc_html_e( 'Postal Code', 'dokan' ); ?>"
                                    type="text"
                                    required
                                >
                            </div>

                            <div class="content-half-part">
                                <label for="razorpay_account_type" class="dokan-control-label">
                                    <?php esc_html_e( 'Country', 'dokan' ); ?>
                                    <span class="dokan-text-required">*</span>
                                </label>
                                <input
                                    name="razorpay_country"
                                    class="dokan-form-control"
                                    id="razorpay_country"
                                    placeholder="<?php esc_html_e( 'Country, e.g.: IN or india', 'dokan' ); ?>"
                                    type="text"
                                    required
                                >
                            </div>
                        </div>

                        <small>
                            <?php esc_html_e( 'The country. The minimum length is 2 and the maximum length is 64. This can either be a country code in capital letters or the full name of the country in lower case letters. For example, for India, you must write either IN or india.', 'dokan' ); ?>
                        </small>
                        <div class="dokan-clearfix"></div>
                    </div>

                    <div>
                        <p class="account-note">
                            <b><?php esc_html_e( 'Note:', 'dokan' ); ?></b>
                            <?php esc_html_e( 'Please make sure that you have entered all the details correctly. The information can not be changed.', 'dokan' ); ?>
                        </p>
                    </div>
                </div>

                <div class="dokan-form-group">
                    <div class="dokan-w12">
                        <?php wp_nonce_field( 'dokan_razorpay_connect' ); ?>
                        <input type="hidden" name="action" value="dokan_razorpay_connect">
                        <span class="dokan-spinner dokan-razorpay-connect-spinner dokan-hide"></span>
                        <button type="button" class="button button-primary" id="dokan_razorpay_vendor_register_button"> <?php esc_html_e( 'Connect Account', 'dokan' ); ?></button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</script>
