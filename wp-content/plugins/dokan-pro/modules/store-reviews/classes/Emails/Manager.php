<?php
namespace WeDevs\DokanPro\Modules\StoreReviews\Emails;

/**
 * Dokan email handler class
 *
 * @package Dokan
 */
class Manager {

    /**
     * Load automatically when class initiate
     */
    public function __construct() {
        //Dokan Email filters for WC Email
        add_filter( 'dokan_email_classes', [ $this, 'load_dokan_emails' ], 35 );
        add_filter( 'dokan_email_list', [ $this, 'add_email_template_file' ] );
        add_filter( 'dokan_email_actions', [ $this, 'add_email_action' ] );
    }

    /**
     * Add Dokan Store Review Email classes in WC Email
     *
     * @since 3.5.5
     *
     * @param array $wc_emails
     *
     * @return array $wc_emails
     */
    public function load_dokan_emails( $wc_emails ) {
        require_once DOKAN_SELLER_RATINGS_DIR . '/classes/Emails/NewStoreReview.php';
        $wc_emails['Dokan_Email_New_Store_Review'] = new NewStoreReview();

        return $wc_emails;
    }

    /**
     * Add email template
     *
     * @since 4.0.0
     *
     * @param array $template_files Template files.
     *
     * @return array
     */
    public static function add_email_template_file( $template_files ): array {
        $template_files[] = 'new-store-review-email.php';

        return $template_files;
    }

    /**
     * Add email action
     *
     * @since 4.0.0
     *
     * @param array $actions Email actions.
     *
     * @return array
     */
    public static function add_email_action( $actions ) {
        $actions[] = 'dokan_store_review_saved';

        return $actions;
    }
}
