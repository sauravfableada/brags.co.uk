<?php
/**
 * Class Dokan_New_Support_Ticket file
 *
 * @package Dokan/Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Dokan_New_Support_Ticket' ) ) :

    /**
     * New Support Ticket.
     *
     * An email sent to the seller when a new support ticket submit.
     *
     * @class       Dokan_New_Support_Ticket
     * @version     3.3.4
     * @package     Dokan/Classes/Emails
     * @extends     WC_Email
     */
    class Dokan_New_Support_Ticket extends WC_Email {

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
         * Constructor.
         */
        public function __construct() {
            $this->id             = 'dokan_new_support_ticket';
            $this->title          = __( 'Dokan New Support Ticket', 'dokan' );
            $this->description    = __( 'New support ticket emails are sent to chosen recipient(s) when a new ticket is received.', 'dokan' );
            $this->template_html  = 'emails/new-support-ticket.php';
            $this->template_plain = 'emails/plain/new-support-ticket.php';
            $this->template_base  = DOKAN_STORE_SUPPORT_DIR . '/templates/';

            // Triggers for this email.
            add_action( 'dokan_new_ticket_created_notify', array( $this, 'trigger' ), 10, 2 );

            // Call parent constructor.
            parent::__construct();

            // Other settings.
            $this->recipient = 'vendor@email.com';
        }

        /**
         * Get email subject.
         *
         * @since  3.3.4
         * @return string
         */
        public function get_default_subject() {
            return __( '[{site_title}] A New Support Ticket', 'dokan' );
        }

        /**
         * Get email heading.
         *
         * @since  3.3.4
         * @return string
         */
        public function get_default_heading() {
            return __( 'A New Support Ticket', 'dokan' );
        }

        /**
         * Trigger the sending of this email.
         *
         * @param int $store_id The Vendor ID.
         * @param int $topic_id Topic ID.
         */
        public function trigger( $store_id, $topic_id ) {
            if ( ! $this->is_enabled() ) {
                return;
            }

            $this->setup_locale();
            $vendor           = new WeDevs\Dokan\Vendor\Vendor( $store_id );
            $email            = $vendor->get_email();
            $this->store_info = $vendor->get_shop_info();
            $this->topic_id   = $topic_id;

            $this->send( $email, $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

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
                    'store_info'         => $this->store_info,
                    'topic_id'           => $this->topic_id,
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
                    'email'              => $this,
                    'store_info'         => $this->store_info,
                    'topic_id'           => $this->topic_id,
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
