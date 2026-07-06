<?php

use WeDevs\Dokan\Utilities\AdminSettings;
use WeDevs\DokanPro\Modules\VendorVerification\Models\VerificationMethod;
use WeDevs\DokanPro\Modules\VendorVerification\Models\VerificationRequest;

defined( 'ABSPATH' ) || exit;

$seller_profile = dokan_get_store_info( get_current_user_id() );

try {
    $new_seller_enable_selling_status = dokan_get_container()->get( AdminSettings::class )->get_new_seller_enable_selling_status();
} catch ( \Exception $e ) {
    $new_seller_enable_selling_status = 'automatically';
}

/**
 * @var string $next_step_link next step link.
 */
$verification_methods = ( new VerificationMethod() )->query( [ 'status' => VerificationMethod::STATUS_ENABLED ] );

// Separate verification methods into non-required and required
$non_required_methods = [];
$required_methods = [];

foreach ( $verification_methods as $method ) {
    if ( $method->is_required() ) {
        $required_methods[] = $method;
    } else {
        $non_required_methods[] = $method;
    }
}
?>

<div class="dokan-verification-content">
    <?php if ( 'verified_only' === $new_seller_enable_selling_status ) : ?>
    <div class="data-warning">
        <div class="dokan-text-left">
            <span class="display-block"><b><?php esc_attr_e( 'You need to be verified for selling in the marketplace', 'dokan' ); ?></b></span>
        </div>
    </div>
    </br>
    <?php endif; ?>

    <!-- Non-Required Verification Methods -->
    <?php if ( ! empty( $non_required_methods ) ) : ?>
        <div class="dokan-verification-group">
            <?php foreach ( $non_required_methods as $verification_method ) : ?>
                <?php
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
                <?php
                dokan_get_template_part(
                    'vendor-verification/verification',
                    'method',
                    [
                        'is_vendor_verification'     => true,
                        'verification_method'        => $verification_method,
                        'last_verification_request'  => $last_verification_request,
                        'current_user'               => get_current_user_id(),
                        'seller_profile'             => $seller_profile,
                        'show_required_label'        => false,
                        'show_empty_address_warning' => false,
                    ]
                );
                ?>
            <?php endforeach; ?>
        </div>
        <br>
    <?php endif; ?>

    <!-- Required Verification Methods -->
    <?php if ( ! empty( $required_methods ) ) : ?>
        <div class="dokan-verification-group">
            <p class="dokan-verification-group-description"><?php esc_html_e( 'To show Verified Badge in your profile you are required to submit below information', 'dokan' ); ?></p>
            <?php foreach ( $required_methods as $verification_method ) : ?>
                <?php
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
                <?php
                dokan_get_template_part(
                    'vendor-verification/verification',
                    'method',
                    [
                        'is_vendor_verification'     => true,
                        'verification_method'        => $verification_method,
                        'last_verification_request'  => $last_verification_request,
                        'current_user'               => get_current_user_id(),
                        'seller_profile'             => $seller_profile,
                        'show_required_label'        => false,
                        'show_empty_address_warning' => false,
                    ]
                );
                ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ( empty( $non_required_methods ) && empty( $required_methods ) ) : ?>
        <div class="dokan-alert dokan-alert-info">
            <?php esc_html_e( 'No verification methods are currently available.', 'dokan' ); ?>
        </div>
    <?php endif; ?>
</div>

<p class='wc-setup-actions step'>
    <a href="<?php echo esc_url( $next_step_link ); ?>" class='button button-large button-next payment-step-skip-btn dokan-btn-theme'><?php esc_html_e( 'Continue', 'dokan' ); ?></a>
</p>
