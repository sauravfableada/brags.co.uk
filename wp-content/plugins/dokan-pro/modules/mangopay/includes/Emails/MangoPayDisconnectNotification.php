<?php

namespace WeDevs\DokanPro\Modules\MangoPay\Emails;

use WC_Email;
use WeDevs\Dokan\Vendor\Vendor;

class MangoPayDisconnectNotification extends WC_Email {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id             = 'dokan_mangopay_disconnect_notification';
        $this->title          = __( 'Dokan MangoPay Account Disconnection', 'dokan' );
        $this->description    = __( 'This email is sent to vendors when their MangoPay account is disconnected.', 'dokan' );
        $this->template_html  = 'emails/mangopay-disconnect-notification.php';
        $this->template_plain = 'emails/plain/mangopay-disconnect-notification.php';
        $this->template_base  = DOKAN_MANGOPAY_TEMPLATE_PATH;
        $this->placeholders   = array(
            '{vendor_name}' => '',
            '{reason}'      => '',
            '{site_name}'   => $this->get_from_name(),
        );

        // Call parent constructor
        parent::__construct();

        // Other settings
        $this->recipient = 'vendor@ofthe.product';
    }

    /**
     * Get email subject.
     *
     * @return string
     */
    public function get_default_subject() {
        return __( '[{site_title}] Your MangoPay Account Has Been Disconnected', 'dokan' );
    }

    /**
     * Get email heading.
     *
     * @return string
     */
    public function get_default_heading() {
        return __( 'MangoPay Account Disconnect Notice', 'dokan' );
    }

    /**
     * Trigger the sending of this email.
     *
     * @param Vendor $vendor Vendor object
     * @param string $reason Reason for disconnection
     */
    public function trigger( $vendor, $reason = '' ) {
        if ( ! $vendor instanceof Vendor ) {
            return;
        }
        /**
         * Fires before sending MangoPay email
         *
         * @since 4.0.7
         *
         * @param $vendor
         * @param $reason
         **/
        do_action( 'dokan_mangopay_disconnect_notification_before_trigger', $vendor, $reason );

        $this->setup_locale();
        $this->object = $vendor;
        $this->placeholders['{vendor_name}'] = $vendor->get_name();
        $this->placeholders['{reason}']      = $reason;

        $this->send(
            $vendor->get_email(),
            $this->get_subject(),
            $this->get_content(),
            $this->get_headers(),
            $this->get_attachments()
        );
        /**
         * Fires after sending MangoPay email
         *
         * @since 4.0.7
         *
         * @param $vendor
         * @param $reason
         **/
        do_action( 'dokan_mangopay_disconnect_notification_after_trigger', $vendor, $reason );
        $this->restore_locale();
    }

    /**
     * Get content html.
     *
     * @return string
     */
    public function get_content_html() {
        ob_start();
        wc_get_template(
            $this->template_html,
            array(
                'vendor_name'      => $this->placeholders['{vendor_name}'],
                'email_heading'    => $this->get_heading(),
                'sent_to_admin'    => false,
                'plain_text'       => false,
                'email'            => $this,
                'reason'           => $this->placeholders['{reason}'],
            ),
            '',
            $this->template_base
        );
        return ob_get_clean();
    }

    /**
     * Get content plain.
     *
     * @return string
     */
    public function get_content_plain() {
        ob_start();
        wc_get_template(
            $this->template_plain,
            array(
                'vendor_name'      => $this->placeholders['{vendor_name}'],
                'email_heading'    => $this->get_heading(),
                'sent_to_admin'    => false,
                'plain_text'       => true,
                'email'            => $this,
                'reason'           => $this->placeholders['{reason}'],
            ),
            '',
            $this->template_base
        );
        return ob_get_clean();
    }
}
