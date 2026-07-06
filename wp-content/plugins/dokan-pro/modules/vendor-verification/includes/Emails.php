<?php

namespace WeDevs\DokanPro\Modules\VendorVerification;

use WeDevs\DokanPro\Modules\VendorVerification\Emails\RequestSubmission;
use WeDevs\DokanPro\Modules\VendorVerification\Emails\StatusUpdate;

defined( 'ABSPATH' ) || exit;


/**
 * Verification Emails.
 *
 * @since 3.11.1
 */
class Emails {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'dokan_email_classes', [ $this, 'load_email_classes' ] );
        add_filter( 'dokan_email_actions', [ $this, 'register_email_actions' ] );
        add_filter( 'dokan_email_list', [ $this, 'register_email_templates' ] );
    }

    /**
     * Load all email class related with vendor verification.
     *
     * @since 3.7.23
     * @since 3.11.1 Moved to separate class.
     *
     * @param array $wc_emails Email classes.
     *
     * @return array
     */
    public function load_email_classes( array $wc_emails ): array {
        $wc_emails['Dokan_Vendor_Verification_Request_Submission'] = new RequestSubmission();
        $wc_emails['Dokan_Vendor_Verification_Status_Update']      = new StatusUpdate();

        return $wc_emails;
    }

    /**
     * Register all email actions related with vendor verification.
     *
     * @since 3.7.23
     * @since 3.11.1 Moved to separate class.
     *
     * @param array $actions Actions.
     *
     * @return array
     */
    public function register_email_actions( array $actions ): array {
        $actions[] = 'dokan_verification_summitted';
        $actions[] = 'dokan_verification_status_change';
        $actions[] = 'dokan_pro_vendor_verification_request_created';
        $actions[] = 'dokan_pro_vendor_verification_request_updated';

        return $actions;
    }

    /**
     * Register all email templates related with vendor verification.
     *
     * @since 3.11.1
     *
     * @param array $templates
     *
     * @return array
     */
    public function register_email_templates( array $templates ): array {
        $templates[] = 'vendor-verification-request-submission.php';
        $templates[] = 'vendor-verification-status-update.php';

        return $templates;
    }

}
