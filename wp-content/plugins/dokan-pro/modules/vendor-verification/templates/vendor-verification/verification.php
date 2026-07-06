<?php

use WeDevs\DokanPro\Modules\VendorVerification\Models\VerificationMethod;
use WeDevs\DokanPro\Modules\VendorVerification\Models\VerificationRequest;

$dokan_Seller_Verification = dokan_pro()->module->vendor_verification; // phpcs:ignore

// phpcs:ignore
$current_user   = get_current_user_id();
$seller_profile = dokan_get_store_info( $current_user );
$phone          = isset( $seller_profile['dokan_verification']['info']['phone'] ) ? $seller_profile['dokan_verification']['info']['phone'] : '';
$phone_status   = isset( $seller_profile['dokan_verification']['info']['phone_status'] ) ? $seller_profile['dokan_verification']['info']['phone_status'] : '';
$phone_no       = isset( $seller_profile['dokan_verification']['info']['phone_no'] ) ? $seller_profile['dokan_verification']['info']['phone_no'] : '';

$verification_methods = ( new VerificationMethod() )->query( [ 'status' => VerificationMethod::STATUS_ENABLED ] );
?>

<div class="dokan-verification-content">

    <?php
    foreach ( $verification_methods as $verification_method ) :
        $verification_request = ( new VerificationRequest() )->query(
            [
                'method_id' => $verification_method->get_id(),
                'vendor_id' => dokan_get_current_user_id(),
                'per_page'  => 1,
                'order_by'  => 'id',
                'order'     => 'DESC',
            ]
        );
        $last_verification_request = reset( $verification_request );
        ?>
        <!-- =================================================== -->
        <!-- Dynamic Verification Content Start -->
        <!-- =================================================== -->
        <div class='dokan-panel dokan-panel-default'>
            <div class='dokan-panel-heading'>
                <strong><?php echo esc_html( apply_filters( 'dokan_pro_vendor_verification_method_title', $verification_method->get_title() ) ); ?></strong>

                <?php if ( $verification_method->is_required() ) : ?>
                    <span style="color: #cb0909;"><small><?php esc_html_e( '(Required)', 'dokan' ); ?></small></span>
                <?php endif; ?>
            </div>
            <div class='dokan-panel-body'>
                <?php if ( ! $last_verification_request || VerificationRequest::STATUS_CANCELLED === $last_verification_request->get_status() ) : ?>
                <button class="dokan-btn dokan-btn-theme dokan-v-start-btn dokan-vendor-verification-start"
                        id="dokan-vendor-verification-start-<?php echo esc_attr( $verification_method->get_id() ); ?>"
                        data-method="<?php echo esc_attr( $verification_method->get_id() ); ?>"
                ><?php esc_html_e( 'Start Verification', 'dokan' ); ?></button>
                <?php else : ?>
                <div class="dokan-verification-request-content">
                    <?php
                    $last_status = "<label class='dokan-label dokan-label-default {$last_verification_request->get_status()}'>{$last_verification_request->get_status_title()}</label>";
                    // translators: Verification request status.
                    $message = sprintf( __( 'Your verification request is %1$s', 'dokan' ), $last_status );
                    ?>
                    <p><?php echo wp_kses_post( $message ); ?></p>

                    <div class='dokan-vendor-verification-file-container'
                        id="dokan-vendor-verification-file-container-<?php echo esc_attr( $verification_method->get_id() ); ?>"
                        data-method="<?php echo esc_attr( $verification_method->get_id() ); ?>"
                    >
                        <?php
                        if ( $verification_method->get_kind() === VerificationMethod::TYPE_ADDRESS ) :
                            $address = dokan_get_seller_address( $current_user );
                            ?>
                            <p class="dokan-vendor-verification-file-heading"><?php esc_html_e( 'Address:', 'dokan' ); ?></p>
                            <address><?php echo wp_kses_post( $address ); ?></address>
                        <?php endif; ?>
                        <?php if ( ! empty( $last_verification_request->get_note() ) ) : ?>
                        <p class="dokan-vendor-verification-file-heading"><?php esc_html_e( 'Note:', 'dokan' ); ?></p>
                        <p><?php echo esc_html( $last_verification_request->get_note() ); ?></p>
                        <?php endif; ?>
                        <p class="dokan-vendor-verification-file-heading"><?php esc_html_e( 'Files:', 'dokan' ); ?></p>
                        <?php foreach ( $last_verification_request->get_documents() as $key => $file_id ) : ?>
                            <div class='dokan-vendor-verification-file-item' >
                                <a href="<?php echo wp_get_attachment_url( $file_id ); ?>"
                                    target='_blank'><?php echo get_the_title( $file_id ); ?></a>
                            </div>
                        <?php endforeach; ?>

                    </div>
                    <?php if ( $last_verification_request->get_status() !== VerificationRequest::STATUS_APPROVED ) : ?>
                        <?php if ( $last_verification_request->get_status() !== VerificationRequest::STATUS_REJECTED ) : ?>
                            <button class='dokan-btn dokan-btn-theme dokan-v-cancel-btn dokan-vendor-verification-cancel-request'
                                    id="dokan-vendor-verification-cancel-<?php echo esc_attr( $verification_method->get_id() ); ?>"
                                    data-message="<?php esc_attr_e( 'Are you sure that you want to cancel the verification request?', 'dokan' ); ?>"
                                    data-method="<?php echo esc_attr( $verification_method->get_id() ); ?>"
                                    data-request="<?php echo esc_attr( $last_verification_request->get_id() ); ?>"
                                    data-nonce="<?php echo esc_attr( wp_create_nonce( 'dokan-vendor-verification-cancel-request' ) ); ?>"
                            ><?php esc_html_e( 'Cancel', 'dokan' ); ?></button>
                        <?php else : ?>
                            <button class='dokan-btn dokan-btn-theme dokan-v-start-btn dokan-vendor-verification-start'
                                    id="dokan-vendor-verification-start-<?php echo esc_attr( $verification_method->get_id() ); ?>"
                                    data-method="<?php echo esc_attr( $verification_method->get_id() ); ?>"
                            ><?php esc_html_e( 'Resubmit', 'dokan' ); ?></button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>


                <?php endif; ?>
                <div class="dokan_v_verification_method_box dokan-hide" id="dokan-vendor-verification-inner-content-<?php echo esc_attr( $verification_method->get_id() ); ?>" data-method="<?php echo esc_attr( $verification_method->get_id() ); ?>">

                    <?php echo wp_kses_post( wpautop( apply_filters( 'dokan_pro_vendor_verification_method_help_text', $verification_method->get_help_text() ) ) ); ?>
                    <?php
                    if ( $verification_method->get_kind() === VerificationMethod::TYPE_ADDRESS ) :
                        $address = dokan_get_seller_address( $current_user );
                        ?>
                        <p class="dokan-vendor-verification-file-heading"><?php esc_html_e( 'Address:', 'dokan' ); ?></p>
                        <?php if ( ! empty( $address ) ) : ?>
                        <address><?php echo wp_kses_post( $address ); ?></address>
                        <?php else : ?>
                        <p>
                            <?php
                            // translators: %1$s: store settings link start, %2$s: store settings link end.
                            printf( esc_html__( 'Please update your address in the %1$s store settings %2$s before submitting verification request.', 'dokan' ), sprintf( '<a href="%s">', dokan_get_navigation_url( 'settings/store' ) ), '</a>' );
                            ?>
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>

                    <form method="post" id="dokan-verification-form-<?php echo esc_attr( $verification_method->get_id() ); ?>" action="" class="dokan-form-horizontal dokan-vendor-verification-request-form">

                        <div class="dokan-form-group dokan-text-left">
                            <label class="dokan-control-label dokan-text-left"><?php esc_html_e( 'Files:', 'dokan' ); ?></label>
                            <div class="dokan-text-left">
                                <div class="dokan-form-control">
                                    <div
                                        class="dokan-vendor-verification-method-files"
                                        id="dokan-vendor-verification-method-files-<?php echo esc_attr( $verification_method->get_id() ); ?>"
                                    >
                                        <?php
                                        if (
                                            $last_verification_request
                                            && ! empty( $last_verification_request->get_documents() )
                                        ) :
                                            foreach ( $last_verification_request->get_documents() as $key => $attachment_id ) :
                                                $custom_id = 'dokan-vendor-verification-' . $verification_method->get_id() . '-file-' . absint( $attachment_id );
                                                ?>
                                                <div class="dokan-vendor-verification-file-item" id="<?php echo $custom_id; ?>">
                                                    <a href="<?php echo wp_get_attachment_url( $attachment_id ); ?>"
                                                        target="_blank"><?php echo get_the_title( $attachment_id ); ?>
                                                    </a>
                                                    <a href="#" onclick="dokanVendorVerificationRemoveFile(event)"
                                                        data-attachment_id="<?php echo esc_attr( $custom_id ); ?>"
                                                        class="dokan-btn disconnect dokan-btn-danger">
                                                        <i class="fas fa-times"
                                                            data-attachment_id="<?php echo $custom_id; ?>">
                                                        </i>
                                                    </a>
                                                    <input type="hidden" name="vendor_verification_files_ids[]"
                                                            value="<?php echo esc_attr( $attachment_id ); ?>"/>
                                                </div>
                                                <?php
                                            endforeach;
                                        endif;
                                        ?>
                                    </div>
                                    <a
                                        style="width: 100%;"
                                        href="#"
                                        class="dokan-vendor-verification-files-drag-button dokan-btn dokan-btn-default"
                                        data-uploader_title="<?php esc_attr_e( 'Uploads or Select Documents', 'dokan' ); ?>"
                                        data-uploader_button_text="<?php esc_attr_e( 'Add File', 'dokan' ); ?>"
                                        data-method="<?php echo esc_attr( $verification_method->get_id() ); ?>"

                                    >
                                        <i class="fas fa-cloud-upload-alt"></i> <?php esc_html_e( 'Upload Files', 'dokan' ); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php do_action( 'dokan_vendor_verification_before_button', $seller_profile, $verification_method ); ?>
                        <div class="dokan-form-group">
                            <div class="dokan-text-left">
                                <input type="submit" id='dokan_vendor_verification_submit_<?php echo esc_attr( $verification_method->get_id() ); ?>'
                                        class="dokan-left dokan-btn dokan-btn-theme dokan_vendor_verification_submit"
                                        value="<?php esc_attr_e( 'Submit', 'dokan' ); ?>">
                                <input type="button" id='dokan_vendor_verification_cancel_<?php echo esc_attr( $verification_method->get_id() ); ?>'
                                        class="dokan-left dokan-btn dokan-btn-theme dokan_vendor_verification_cancel"
                                        value="<?php esc_attr_e( 'Cancel', 'dokan' ); ?>"
                                        data-method="<?php echo esc_attr( $verification_method->get_id() ); ?>"
                                >
                                <input type="hidden" name="method_id" value="<?php echo esc_attr( $verification_method->get_id() ); ?>"/>
                                <input type="hidden" name="action" value="dokan_vendor_verification_request_creation"/>
                                <?php wp_nonce_field( 'dokan_vendor_verification_request_creation', '_nonce' ); ?>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </div>
        <!-- =================================================== -->
        <!-- Dynamic Verification Content End -->
        <!-- =================================================== -->
    <?php endforeach; ?>

    <!-- =================================================== -->
    <!-- Phone Verification Content Start -->
    <!-- =================================================== -->
    <?php
    $active_gateway     = dokan_get_option( 'active_gateway', 'dokan_verification_sms_gateways' );
    $active_gw_username = trim( dokan_get_option( $active_gateway . '_username', 'dokan_verification_sms_gateways' ) );
    $active_gw_pass     = trim( dokan_get_option( $active_gateway . '_pass', 'dokan_verification_sms_gateways' ) );

    if ( ! empty( $active_gw_username ) || ! empty( $active_gw_pass ) ) {
        ?>
        <div class="dokan-panel dokan-panel-default dokan_v_phone">
            <div class="dokan-panel-heading">
                <strong><?php esc_attr_e( 'Phone Verification', 'dokan' ); ?></strong>
            </div>

            <div class="dokan-panel-body">
                <div class="" id="d_v_phone_feedback"></div>

                <?php if ( $phone_status !== 'verified' ) { ?>

                    <div class="dokan_v_phone_box">
                        <form method="post" id="dokan-verify-phone-form"  action="" class="dokan-form-horizontal">
                            <?php wp_nonce_field( 'dokan_verify_action', 'dokan_verify_action_nonce' ); ?>
                            <div class="dokan-form-group">
                                <label class="dokan-w3 dokan-control-label" for="phone"><?php esc_attr_e( 'Phone No', 'dokan' ); ?></label>
                                <div class="dokan-w5">
                                    <input id="phone" value="<?php echo $phone; ?>" name="phone" placeholder="<?php esc_attr_e( '+123456..', 'dokan' ); ?>" class="dokan-form-control input-md" type="text"><br>
                                    <?php if ( 'nexmo' === $active_gateway ) { ?>
                                    <div class="dokan-alert dokan-alert-warning">
                                        <?php esc_html_e( 'When entering US phone numbers, exclude the plus sign from the phone number.', 'dokan' ); ?>
                                    </div>
                                    <?php } ?>
                                    <?php do_action( 'dokan_before_phone_verification_submit_button', $seller_profile ); ?>
                                    <div class="dokan-form-group">
                                        <input type="submit" id='dokan_v_phone_submit' class="dokan-left dokan-btn dokan-btn-theme" value="<?php esc_attr_e( 'Submit', 'dokan' ); ?>">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="dokan_v_phone_code_box dokan-hide">
                        <form method="post" id="dokan-v-phone-code-form"  action="" class="dokan-form-horizontal">
                            <?php wp_nonce_field( 'dokan_verify_action', 'dokan_verify_action_nonce' ); ?>

                            <div class="dokan-form-group">
                                <label class="dokan-w3 dokan-control-label" for="sms_code"><?php esc_attr_e( 'SMS code', 'dokan' ); ?></label>
                                <div class="dokan-w5">
                                    <input id="sms_code" value="" name="sms_code" placeholder="" class="dokan-form-control input-md" type="text">
                                </div>
                            </div>

                            <div class="dokan-form-group">
                                <input type="submit" id='dokan_v_code_submit' class="dokan-left dokan-btn dokan-btn-theme" value="<?php esc_attr_e( 'Submit', 'dokan' ); ?>">
                            </div>
                        </form>
                    </div>

                <?php } elseif ( 'verified' === $phone_status ) { ?>

                    <div class="dokan-alert dokan-alert-success">
                        <p>
                        <?php
                        esc_attr_e( 'Your Verified Phone number is : ', 'dokan' );
						echo '<b>' . $phone_no . '</b>';
						?>
                        </p>
                    </div>

                <?php } ?>
            </div>
        </div>
    <?php } ?>
    <!-- =================================================== -->
    <!-- Phone Verification Content End -->
    <!-- =================================================== -->

    <!-- =================================================== -->
    <!-- Social Profiles Verification Content Start -->
    <!-- =================================================== -->
    <div class="dokan-panel dokan-panel-default">
        <div class="dokan-panel-heading clickable">
            <strong><?php esc_attr_e( 'Social Profiles', 'dokan' ); ?></strong>
        </div>

        <div class="dokan-panel-body">
            <div class="dokan-verify-links">
                <?php

                $configured_providers = array();

                //facebook config from admin
                $fb_status = dokan_get_option( 'facebook_enable_status', 'dokan_verification', 'on' );
                $fb_id     = dokan_get_option( 'fb_app_id', 'dokan_verification' );
                $fb_secret = dokan_get_option( 'fb_app_secret', 'dokan_verification' );
                if ( $fb_status === 'on' && $fb_id !== '' && $fb_secret !== '' ) {
                    $configured_providers [] = 'facebook';
                }
                //google config from admin
                $g_status = dokan_get_option( 'google_enable_status', 'dokan_verification', 'on' );
                $g_id     = dokan_get_option( 'google_app_id', 'dokan_verification' );
                $g_secret = dokan_get_option( 'google_app_secret', 'dokan_verification' );
                if ( $g_status === 'on' && $g_id !== '' && $g_secret !== '' ) {
                    $configured_providers [] = 'google';
                }
                //linkedin config from admin
                $l_status = dokan_get_option( 'linkedin_enable_status', 'dokan_verification', 'on' );
                $l_id     = dokan_get_option( 'linkedin_app_id', 'dokan_verification' );
                $l_secret = dokan_get_option( 'linkedin_app_secret', 'dokan_verification' );
                if ( $l_status === 'on' && $l_id !== '' && $l_secret !== '' ) {
                    $configured_providers [] = 'linkedin';
                }
                //Twitter config from admin
                $twitter_status = dokan_get_option( 'twitter_enable_status', 'dokan_verification', 'on' );
                $twitter_id     = dokan_get_option( 'twitter_app_id', 'dokan_verification' );
                $twitter_secret = dokan_get_option( 'twitter_app_secret', 'dokan_verification' );
                if ( $twitter_status === 'on' && $twitter_id !== '' && $twitter_secret !== '' ) {
                    $configured_providers [] = 'twitter';
                }


                /**
                 * Filter the list of Providers connect links to display
                 *
                 * @since 1.0.0
                 *
                 * @param array $providers
                 */
                $providers = apply_filters( 'dokan_verify_provider_list', $configured_providers );
                $provider  = '';
                if ( ! empty( $providers ) ) {
                    foreach ( $providers as $provider ) {
                        $provider_info = '';

                        if ( isset( $seller_profile['dokan_verification'][ $provider ] ) ) {
                            $provider_info = $seller_profile['dokan_verification'][ $provider ];
                        }
                        ?>
                        <?php if ( ! isset( $provider_info ) || '' === $provider_info ) { ?>
                            <a href="<?php echo add_query_arg( array( 'dokan_auth' => $provider ), dokan_get_navigation_url( 'settings/verification' ) ); ?>">
                                <button class="dokan-btn dokan-verify-connect-btn">
                                    <?php
                                    esc_html_e( 'Connect ', 'dokan' );
                                    echo ucwords( $provider );
                                    ?>
                                </button>
                            </a>
                        <?php } else { ?>
                            <div class="dokan-va-row">
                                <div class="dokan-w12">
                                    <div class=""><h2><u><?php echo ucwords( $provider ); ?></u></h2></div>
                                    <div class="">
                                        <div class="dokan-w4"><img src="<?php echo $provider_info['photoURL']; ?>"/></div>
                                        <div class="dokan-w5"><a target="_blank" href="<?php echo $provider_info['profileURL']; ?>"><?php echo $provider_info['displayName']; ?></a></div>
                                        <div class="dokan-w5"><?php echo $provider_info['email']; ?></div>
                                    </div>

                                    <div class="dokan_verify_dc_button">
                                        <a href="<?php echo add_query_arg( array( 'dokan_auth_dc' => $provider ), dokan_get_navigation_url( 'settings/verification' ) ); ?>">
                                            <button class="dokan-btn dokan-btn-block">
                                                <?php
                                                esc_html_e( 'Disconnect ', 'dokan' );
                                                echo ucwords( $provider );
                                                ?>
                                            </button>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    <?php } ?>
                <?php } else { ?>
                    <div class="dokan-alert dokan-alert-info">
                            <?php echo esc_html__( 'No Social App is configured by website Admin', 'dokan' ); ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
    <!-- =================================================== -->
    <!-- Social Profiles Verification Content End -->
    <!-- =================================================== -->
</div>
