<?php

/**
 * Partial template for rendering a single verification method.
 *
 * @since 4.0.6
 *
 * @var VerificationMethod       $verification_method        The verification method object.
 * @var VerificationRequest|null $last_verification_request  The last verification request for this method, if any.
 * @var int                      $current_user               The current user ID.
 * @var array                    $seller_profile             The seller profile data.
 * @var bool                     $show_required_label        Whether to show the "(Required)" label (default: false).
 * @var bool                     $show_empty_address_warning Whether to show a warning for empty addresses (default: false).
 */

use WeDevs\DokanPro\Modules\VendorVerification\Models\VerificationMethod;
use WeDevs\DokanPro\Modules\VendorVerification\Models\VerificationRequest;

defined( 'ABSPATH' ) || exit;

$verification_method_id = (string) $verification_method->get_id();
?>

<div class='dokan-panel dokan-panel-default'>
    <div class='dokan-panel-heading'>
        <strong><?php echo esc_html( apply_filters( 'dokan_pro_vendor_verification_method_title', $verification_method->get_title() ) ); ?></strong>
        <?php if ( $show_required_label && $verification_method->is_required() ) : ?>
            <span style="color: #cb0909;"><small><?php esc_html_e( '(Required)', 'dokan' ); ?></small></span>
        <?php endif; ?>
    </div>
    <div class='dokan-panel-body'>
        <?php
        if (
            ! $last_verification_request
            || VerificationRequest::STATUS_CANCELLED === $last_verification_request->get_status()
        ) :
            ?>
            <button
                class="button button-primary dokan-btn dokan-btn-theme dokan-v-start-btn dokan-vendor-verification-start"
                id="dokan-vendor-verification-start-<?php echo esc_attr( $verification_method_id ); ?>"
                data-method="<?php echo esc_attr( $verification_method_id ); ?>"
            ><?php esc_html_e( 'Start Verification', 'dokan' ); ?></button>
        <?php else : ?>
            <div class="dokan-verification-request-content">
                <?php
                $last_status = "<label class='dokan-label dokan-label-default {$last_verification_request->get_status()}'>{$last_verification_request->get_status_title()}</label>";

                // translators: %1$s is the status of the verification request.
                $message = sprintf( __( 'Your verification request is %1$s', 'dokan' ), $last_status );
                ?>
                <p><?php echo wp_kses_post( $message ); ?></p>

                <div class='dokan-vendor-verification-file-container'
                    id="dokan-vendor-verification-file-container-<?php echo esc_attr( $verification_method_id ); ?>"
                    data-method="<?php echo esc_attr( $verification_method_id ); ?>"
                >
                    <?php if ( $verification_method->get_kind() === VerificationMethod::TYPE_ADDRESS ) : ?>
                        <p class="dokan-vendor-verification-file-heading"><?php esc_html_e( 'Address:', 'dokan' ); ?></p>
                        <p><?php echo wp_kses_post( dokan_get_seller_address( $current_user ) ); ?></p>
                    <?php endif; ?>
                    <?php if ( ! empty( $last_verification_request->get_note() ) ) : ?>
                        <p class="dokan-vendor-verification-file-heading"><?php esc_html_e( 'Note:', 'dokan' ); ?></p>
                        <p><?php echo esc_html( $last_verification_request->get_note() ); ?></p>
                    <?php endif; ?>
                    <p class="dokan-vendor-verification-file-heading"><?php esc_html_e( 'Files:', 'dokan' ); ?></p>
                    <?php foreach ( $last_verification_request->get_documents() as $key => $file_id ) : ?>
                        <div class='dokan-vendor-verification-file-item'>
                            <a href="<?php echo wp_get_attachment_url( $file_id ); ?>"
                                target='_blank'><?php echo get_the_title( $file_id ); ?></a>
                        </div>
                    <?php endforeach; ?>

                </div>
                <?php
                if ( $last_verification_request->get_status() !== VerificationRequest::STATUS_APPROVED ) :
                    ?>
                    <?php
                    if ( $last_verification_request->get_status() !== VerificationRequest::STATUS_REJECTED ) :
                        ?>
                        <button
                            class='button button-primary dokan-btn dokan-btn-theme dokan-v-cancel-btn dokan-vendor-verification-cancel-request'
                            id="dokan-vendor-verification-cancel-<?php echo esc_attr( $verification_method_id ); ?>"
                            data-message="<?php esc_attr_e( 'Are you sure that you want to cancel the verification request?', 'dokan' ); ?>"
                            data-method="<?php echo esc_attr( $verification_method_id ); ?>"
                            data-request="<?php echo esc_attr( (string) $last_verification_request->get_id() ); ?>"
                            data-nonce="<?php echo esc_attr( wp_create_nonce( 'dokan-vendor-verification-cancel-request' ) ); ?>"
                        ><?php esc_html_e( 'Cancel', 'dokan' ); ?></button>
                    <?php else : ?>
                        <button
                            class='button button-primary dokan-btn dokan-btn-theme dokan-v-start-btn dokan-vendor-verification-start'
                            id="dokan-vendor-verification-start-<?php echo esc_attr( $verification_method_id ); ?>"
                            data-method="<?php echo esc_attr( $verification_method_id ); ?>"
                        ><?php esc_html_e( 'Resubmit', 'dokan' ); ?></button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <div class="dokan_v_verification_method_box dokan-hide"
            id="dokan-vendor-verification-inner-content-<?php echo esc_attr( $verification_method_id ); ?>"
            data-method="<?php echo esc_attr( $verification_method_id ); ?>">

            <?php echo wp_kses_post( wpautop( apply_filters( 'dokan_pro_vendor_verification_method_help_text', $verification_method->get_help_text() ) ) ); ?>
            <?php if ( $verification_method->get_kind() === VerificationMethod::TYPE_ADDRESS ) : ?>
                <p class="dokan-vendor-verification-file-heading"><?php esc_html_e( 'Address:', 'dokan' ); ?></p>
                <p><?php echo wp_kses_post( dokan_get_seller_address( $current_user ) ); ?></p>
            <?php endif; ?>

            <form method="post"
                    id="dokan-verification-form-<?php echo esc_attr( $verification_method_id ); ?>"
                    action="" class="dokan-form-horizontal dokan-vendor-verification-request-form">

                <div class="dokan-form-group">
                    <label class="dokan-w3 dokan-control-label"><?php esc_html_e( 'Files:', 'dokan' ); ?></label>
                    <div class="dokan-w5 dokan-text-left">
                        <div class="dokan-form-control">
                            <div
                                class="dokan-vendor-verification-method-files"
                                id="dokan-vendor-verification-method-files-<?php echo esc_attr( $verification_method_id ); ?>"
                            >
                                <?php
                                if (
                                    $last_verification_request
                                    && ! empty( $last_verification_request->get_documents() )
                                ) :
                                    foreach (
                                        $last_verification_request->get_documents() as $key => $attachment_id
                                    ) :
                                        $custom_id = 'dokan-vendor-verification-' . $verification_method->get_id() . '-file-' . absint( $attachment_id );
                                        ?>
                                        <div class="dokan-vendor-verification-file-item"
                                            id="<?php echo $custom_id; ?>">
                                            <a href="<?php echo wp_get_attachment_url( $attachment_id ); ?>"
                                                target="_blank"><?php echo get_the_title( $attachment_id ); ?></a>
                                            <a href="#" onclick="dokanVendorVerificationRemoveFile(event)"
                                                data-attachment_id="<?php echo $custom_id; ?>"
                                                class="dokan-btn disconnect dokan-btn-danger"><i
                                                    class="fas fa-times"
                                                    data-attachment_id="<?php echo $custom_id; ?>"></i></a>
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
                                class="button button-secondary dokan-vendor-verification-files-drag-button dokan-btn dokan-btn-default"
                                data-uploader_title="<?php esc_attr_e( 'Uploads or Select Documents', 'dokan' ); ?>"
                                data-uploader_button_text="<?php esc_attr_e( 'Add File', 'dokan' ); ?>"
                                data-method="<?php echo esc_attr( $verification_method_id ); ?>"

                            >
                                <i class="fas fa-cloud-upload-alt"></i> <?php esc_html_e( 'Upload Files', 'dokan' ); ?>
                            </a>
                        </div>
                    </div>
                </div>
                <?php do_action( 'dokan_vendor_verification_before_button', $seller_profile, $verification_method ); ?>
                <div class="dokan-form-group">
                    <label class="dokan-w3 dokan-control-label" for="setting_bank_iban">&nbsp;</label>
                    <div class="dokan-w5 dokan-text-left">
                        <input type="submit"
                                id='dokan_vendor_verification_submit_<?php echo esc_attr( $verification_method_id ); ?>'
                                class="button button-primary dokan-left dokan-btn dokan-btn-theme dokan_vendor_verification_submit"
                                value="<?php esc_attr_e( 'Submit', 'dokan' ); ?>">
                        <input type="button"
                                id='dokan_vendor_verification_cancel_<?php echo esc_attr( $verification_method_id ); ?>'
                                class="button button-primary dokan-left dokan-btn dokan-btn-theme dokan_vendor_verification_cancel"
                                value="<?php esc_attr_e( 'Cancel', 'dokan' ); ?>"
                                data-method="<?php echo esc_attr( $verification_method_id ); ?>"
                        >
                        <input type="hidden" name="method_id"
                                value="<?php echo esc_attr( $verification_method_id ); ?>"/>
                        <input type="hidden" name="action" value="dokan_vendor_verification_request_creation"/>
                        <?php wp_nonce_field( 'dokan_vendor_verification_request_creation', '_nonce' ); ?>
                    </div>
                </div>
            </form>
        </div>

    </div>
</div>
