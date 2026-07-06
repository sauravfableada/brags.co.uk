<?php
/**
 * Class DokanReplyToAdminSupportTicket file
 *
 * @package Dokan/Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'DokanReplyToAdminSupportTicket' ) ) :

    /**
     * Support Ticket Replay Email.
     *
     * An email sent to the admin when vendor or customer replies on a ticket depending on settings.
     *
     * @class       DokanReplyToAdminSupportTicket
     * @version     3.6.0
     * @package     Dokan/Classes/Emails
     * @extends     WC_Email
     */
    class DokanReplyToAdminSupportTicket extends WC_Email {
         
        /**
         * Email data passed to the template.
         *
         * @var array
         */
        protected $email_data = [];
        
        /**
         * Constructor.
         */
        public function __construct() {
            $this->id             = 'DokanReplyToAdminSupportTicket_vendor_customer';
            $this->title          = __( 'Dokan Reply To Admin Support Ticket From Vendor & Customer', 'dokan' );
            $this->description    = __( 'An email sent to the admin when vendor or customer replies on a ticket depending on settings.', 'dokan' );
            $this->template_html  = 'emails/reply-to-admin-support-ticket.php';
            $this->template_plain = 'emails/plain/reply-to-admin-support-ticket.php';
            $this->template_base  = DOKAN_STORE_SUPPORT_DIR . '/templates/';
            $this->placeholders   = [
                '{ticket_id}' => '',
            ];

            // Triggers for this email.
            add_action( 'dokan_reply_to_admin_ticket_created_notify', array( $this, 'trigger' ), 10, 2 );

            // Call parent constructor.
            parent::__construct();

            // Other settings.
            $this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
        }

        /**
         * Get email subject.
         *
         * @since  3.6.0
         * @return string
         */
        public function get_default_subject() {
            return __( '[{site_title}] A New Reply On Ticket #{ticket_id}', 'dokan' );
        }

        /**
         * Get email heading.
         *
         * @since  3.6.0
         * @return string
         */
        public function get_default_heading() {
            return __( 'A New Reply On Ticket #{ticket_id}', 'dokan' );
        }

        /**
         * Trigger the sending of this email.
         *
         * @param int $store_id The order ID.
         * @param array $email_data Email data.
         */
        public function trigger( $store_id, $email_data ) {
            // Getting vendor settings from specific store settings.
            $topic_specific_settings = get_post_meta( $email_data['ticket_id'], 'dokan_admin_email_notification', true );

            // Return if global admin settings is off or global is on and topic specific settings is off.
            if ( ! $this->is_enabled() || 'off' === $topic_specific_settings || ! $this->get_recipient() ) {
                return;
            }

            $this->setup_locale();
            $this->email_data                = $email_data;
            $this->placeholders['ticket_id'] = $email_data['ticket_id'];

            $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

            $this->restore_locale();
        }

        /**
         * Get content html.
         *
         * @access public
         * @return string
         */
        public function get_content_html() {
            return wc_get_template_html(
                $this->template_html, array(
                    'email_heading'      => $this->get_heading(),
                    'additional_content' => $this->get_additional_content(),
                    'plain_text'         => false,
                    'email'              => $this,
                    'email_data'         => $this->email_data,
                ), 'dokan', $this->template_base
            );
        }

        /**
         * Get content plain.
         *
         * @access public
         * @return string
         */
        public function get_content_plain() {
            return wc_get_template_html(
                $this->template_plain, array(
                    'email_heading'      => $this->get_heading(),
                    'additional_content' => $this->get_additional_content(),
                    'plain_text'         => true,
                    'email'              => $this,
                    'email_data'         => $this->email_data,
                ), 'dokan/', $this->template_base
            );
        }

        /**
         * Initialise settings form fields.
         */
        public function init_form_fields() {
            /* translators: %s: list of placeholders */
            $placeholder_text  = sprintf( __( 'Available placeholders: %s', 'dokan' ), '<code>' . implode( '</code>, <code>', array_keys( $this->placeholders ) ) . '</code>' );
            $this->form_fields = array(
                'enabled'    => array(
                    'title'   => __( 'Enable/Disable', 'dokan' ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable this email notification', 'dokan' ),
                    'default' => 'yes',
                ),
                'recipient' => array(
                    'title'         => __( 'Recipient(s)', 'dokan' ),
                    'type'          => 'text',
                    // translators: 1) Email recipients
                    'description'   => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to %s.', 'dokan' ), '<code>' . esc_attr( get_option( 'admin_email' ) ) . '</code>' ),
                    'placeholder'   => '',
                    'default'       => '',
                    'desc_tip'      => true,
                ),
                'subject'    => array(
                    'title'       => __( 'Subject', 'dokan' ),
                    'type'        => 'text',
                    'desc_tip'    => true,
                    'description' => $placeholder_text,
                    'placeholder' => $this->get_default_subject(),
                    'default'     => '',
                ),
                'heading'    => array(
                    'title'       => __( 'Email heading', 'dokan' ),
                    'type'        => 'text',
                    'desc_tip'    => true,
                    'description' => $placeholder_text,
                    'placeholder' => $this->get_default_heading(),
                    'default'     => '',
                ),
                'additional_content' => array(
                    'title'       => __( 'Additional content', 'dokan' ),
                    'description' => __( 'Text to appear below the main email content.', 'dokan' ) . ' ' . $placeholder_text,
                    'css'         => 'width:400px; height: 75px;',
                    'placeholder' => __( 'N/A', 'dokan' ),
                    'type'        => 'textarea',
                    'default'     => $this->get_default_additional_content(),
                    'desc_tip'    => true,
                ),
                'email_type' => array(
                    'title'       => __( 'Email type', 'dokan' ),
                    'type'        => 'select',
                    'description' => __( 'Choose which format of email to send.', 'dokan' ),
                    'default'     => 'html',
                    'class'       => 'email_type wc-enhanced-select',
                    'options'     => $this->get_email_type_options(),
                    'desc_tip'    => true,
                ),
            );
        }
    }

endif;
