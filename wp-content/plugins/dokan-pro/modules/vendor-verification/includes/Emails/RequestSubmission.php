<?php

namespace WeDevs\DokanPro\Modules\VendorVerification\Emails;

use WC_Email;
use WeDevs\DokanPro\Modules\VendorVerification\Models\VerificationRequest;

defined( 'ABSPATH' ) || exit;

/**
 * Dokan Vendor Verification Request Submission Email.
 *
 * An email sent to the admin when a vendor submits a document for verification.
 *
 * @class   DokanVendorVerificationRequestSubmission
 * @version 3.7.23
 * @author  weDevs
 * @extends WC_Email
 */
class RequestSubmission extends WC_Email {

    /**
     * Class Constructor.
     */
    public function __construct() {
        $this->id             = 'dokan_vendor_verification_request_submission';
        $this->title          = __( 'Seller Verification Request Submitted', 'dokan' );
        $this->description    = __( 'This email will be sent to the admin after submitting documents by a vendor for verification.', 'dokan' );
        $this->template_html  = 'emails/vendor-verification-request-submission.php';
        $this->template_plain = 'emails/plain/vendor-verification-request-submission.php';
        $this->template_base  = DOKAN_VERFICATION_TEMPLATE_DIR;
        $this->placeholders   = [
            '{store_name}' => '',
            // backward compatibility
            '{site_name}'  => $this->get_from_name(),
        ];

        // Triggers for this email
        add_action( 'dokan_verification_summitted', [ $this, 'trigger' ], 20, 1 );
        add_action( 'dokan_pro_vendor_verification_request_created', [ $this, 'trigger_for_verification' ], 20, 1 );

        // Call parent constructor
        parent::__construct();

        // Other settings
        $this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
    }

    /**
     * Get email subject.
     *
     * @since 3.7.23
     *
     * @return string
     */
    public function get_default_subject() {
        return __( 'Seller Verification Request Submitted by {store_name}', 'dokan' );
    }

    /**
     * Get email heading.
     *
     * @since 3.7.23
     *
     * @return string
     */
    public function get_default_heading() {
        return __( 'Seller Verification Request Submitted by {store_name}', 'dokan' );
    }

    /**
     * Default content to show below main email content.
     *
     * @since 3.7.23
     *
     * @return string
     */
    public function get_default_additional_content() {
        return __( 'Thanks for using <a href="{site_url}">{site_title}</a>!', 'dokan' );
    }

    /**
     * Trigger the sending of this email.
     *
     * @since 3.7.23
     *
     * @param int $seller_id Sellder id
     *
     * @return void
     */
    public function trigger( $seller_id ) {
        if ( ! $this->is_enabled() ) {
            return;
        }

        if ( empty( $seller_id ) || ! user_can( $seller_id, 'dokandar' ) ) {
            return;
        }
        $this->setup_locale();
        $seller_info = dokan_get_store_info( $seller_id );
        $store_name  = $seller_info['store_name'];
        $site_name   = get_bloginfo( 'name' );
        $site_url    = site_url();

        $this->placeholders['{site_name}'] = $site_name;
        $this->placeholders['{store_name}'] = $store_name;

        $this->data = [
            'store_name' => $store_name,
            'home_url'   => $site_url,
            'admin_url'  => admin_url( 'admin.php?page=dokan#/verifications?status=pending' ),
        ];

        $this->setup_locale();
        $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

        $this->restore_locale();
    }

    /**
     * Trigger email.
     *
     * @since 3.11.1
     *
     * @param int $request_id ID of the request.
     *
     * @return void
     */
    public function trigger_for_verification( int $request_id ) {
        $request = new VerificationRequest( $request_id );

        $this->trigger( $request->get_vendor_id() );
    }

    /**
     * Get content html.
     *
     * @since  3.7.23
     *
     * @access public
     * @return string
     */
    public function get_content_html() {
        return wc_get_template_html(
            $this->template_html,
            [
                'email_heading'      => $this->get_heading(),
                'sent_to_admin'      => true,
                'plain_text'         => false,
                'email'              => $this,
                'store_name'         => $this->data['store_name'],
                'home_url'           => $this->data['home_url'],
                'admin_url'          => $this->data['admin_url'],
                'additional_content' => $this->get_additional_content(),
            ],
            'dokan/',
            $this->template_base
        );
    }

    /**
     * Get content plain.
     *
     * @since  3.7.23
     *
     * @access public
     * @return string
     */
    public function get_content_plain() {
        return wc_get_template_html(
            $this->template_plain,
            [
                'email_heading'      => $this->get_heading(),
                'sent_to_admin'      => true,
                'plain_text'         => true,
                'email'              => $this,
                'store_name'         => $this->data['store_name'],
                'home_url'           => $this->data['home_url'],
                'admin_url'          => $this->data['admin_url'],
                'additional_content' => $this->get_additional_content(),
            ],
            'dokan/',
            $this->template_base
        );
    }

    /**
     * Initialize settings form fields.
     *
     * @since 3.7.23
     *
     * @return void
     */
    public function init_form_fields() {
        $placeholders = $this->placeholders;
        unset( $placeholders['{site_name}'] );
        $placeholder_text = sprintf(
        /* translators: %s: list of placeholders */
            __( 'Available placeholders: %s', 'dokan' ),
            '<code>' . esc_html( implode( '</code>, <code>', array_keys( $placeholders ) ) ) . '</code>'
        );

        $this->form_fields = [
            'enabled'            => [
                'title'   => __( 'Vendor verification document submission', 'dokan' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable email for vendor verification document submission by vendor', 'dokan' ),
                'default' => 'yes',
            ],
            'recipient' => [
                'title'         => __( 'Recipient(s)', 'dokan' ),
                'type'          => 'text',
                // translators: 1) Email recipients
                'description'   => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to %s.', 'dokan' ), '<code>' . esc_attr( get_option( 'admin_email' ) ) . '</code>' ),
                'placeholder'   => '',
                'default'       => '',
                'desc_tip'      => true,
            ],
            'subject'            => [
                'title'       => __( 'Subject', 'dokan' ),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => $placeholder_text,
                'placeholder' => $this->get_default_subject(),
                'default'     => '',
            ],
            'heading'            => [
                'title'       => __( 'Email heading', 'dokan' ),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => $placeholder_text,
                'placeholder' => $this->get_default_heading(),
                'default'     => '',
            ],
            'additional_content' => [
                'title'       => __( 'Additional content', 'dokan' ),
                'description' => __( 'Text to appear below the main email content.', 'dokan' ) . ' ' . $placeholder_text,
                'css'         => 'width:400px; height: 75px;',
                'placeholder' => __( 'N/A', 'dokan' ),
                'type'        => 'textarea',
                'default'     => $this->get_default_additional_content(),
                'desc_tip'    => true,
            ],
            'email_type'         => [
                'title'       => __( 'Email type', 'dokan' ),
                'type'        => 'select',
                'description' => __( 'Choose which format of email to send.', 'dokan' ),
                'default'     => 'html',
                'class'       => 'email_type wc-enhanced-select',
                'options'     => $this->get_email_type_options(),
                'desc_tip'    => true,
            ],
        ];
    }
}
