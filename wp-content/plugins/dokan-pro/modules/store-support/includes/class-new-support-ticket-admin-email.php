<?php
/**
 * Class DokanNewSupportTicketForAdmin file
 *
 * @package Dokan/Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'DokanNewSupportTicketForAdmin' ) ) :

    /**
     * New Support Ticket.
     *
     * An email sent to the admin when a new support ticket submit.
     *
     * @class       DokanNewSupportTicketForAdmin
     * @version     3.6.0
     * @package     Dokan/Classes/Emails
     * @extends     WC_Email
     */
    class DokanNewSupportTicketForAdmin extends WC_Email {

        /**
         * Vendor shop info.
         *
         * @var array
         */
        protected $store_info;

        /**
         * Ticket topic ID.
         *
         * @var int
         */
        protected $topic_id;

        /**
         * Store/Vendor ID.
         *
         * @var int
         */
        protected $store_id;
        /**
         * Constructor.
         */
        public function __construct() {
            $this->id             = 'DokanNewSupportTicketForAdmin';
            $this->title          = __( 'Dokan New Support Ticket For Admin', 'dokan' );
            $this->description    = __( 'New support ticket emails are sent to site admin when a new ticket is received.', 'dokan' );
            $this->template_html  = 'emails/new-support-ticket-for-admin.php';
            $this->template_plain = 'emails/plain/new-support-ticket-for-admin.php';
            $this->template_base  = DOKAN_STORE_SUPPORT_DIR . '/templates/';

            // Triggers for this email.
            add_action( 'dokan_new_ticket_created_notify', array( $this, 'trigger' ), 10, 2 );

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
            return __( '[{site_title}] A New Support Ticket', 'dokan' );
        }

        /**
         * Get email heading.
         *
         * @since  3.6.0
         * @return string
         */
        public function get_default_heading() {
            return __( 'A New Support Ticket Created.', 'dokan' );
        }

        /**
         * Triggers to send the email to admin.
         *
         * @since  3.6.0
         *
         * @param int $store_id The Store ID.
         * @param int $topic_id Topic ID.
         *
         * @return void
         */
        public function trigger( $store_id, $topic_id ) {
            $topic_specific_settins = get_post_meta( 1, 'dokan_admin_email_notification', true );

            // Return if global admin settings is off or global is on and topic specific settings is off.
            if ( ! $this->is_enabled() || 'off' === $topic_specific_settins || ! $this->get_recipient() ) {
                return;
            }
            $this->setup_locale();

            $this->store_info = dokan_get_store_info( $store_id );
            $this->topic_id   = $topic_id;
            $this->store_id   = $store_id;

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
                    'sent_to_admin'      => true,
                    'email'              => $this,
                    'store_info'         => $this->store_info,
                    'topic_id'           => $this->topic_id,
                    'store_id'           => $this->store_id,
                ), 'dokan/', $this->template_base
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
                    'sent_to_admin'      => true,
                    'email'              => $this,
                    'store_info'         => $this->store_info,
                    'topic_id'           => $this->topic_id,
                    'store_id'           => $this->store_id,
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
                'enabled' => array(
                    'title'         => __( 'Enable/Disable', 'dokan' ),
                    'type'          => 'checkbox',
                    'label'         => __( 'Enable this email notification', 'dokan' ),
                    'default'       => 'yes',
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
