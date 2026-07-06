<?php
namespace WeDevs\DokanPro\Modules\RequestForQuotation\Emails;

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
        add_filter( 'dokan_email_classes', array( $this, 'load_dokan_emails' ), 35 );
        add_filter( 'dokan_email_list', [ $this, 'add_email_template_file' ] );
        add_filter( 'dokan_email_actions', [ $this, 'add_email_action' ] );
    }

    /**
     * Add Dokan Email classes in WC Email
     *
     * @since 3.6.0
     *
     * @param array $wc_emails
     *
     * @return array $wc_emails
     */
    public function load_dokan_emails( $wc_emails ) {
        $wc_emails['Dokan_Email_New_Request_Quote']    = new NewQuote();
        $wc_emails['Dokan_Email_Update_Request_Quote'] = new UpdateQuote();
        $wc_emails['Dokan_Email_Accept_Request_Quote'] = new AcceptQuote();

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
        $template_files[] = 'request-new-quote-email.php';
        $template_files[] = 'request-update-quote-email.php';

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
        $actions[] = 'after_dokan_request_quote_inserted';
        $actions[] = 'after_dokan_request_quote_updated';

        return $actions;
    }
}
