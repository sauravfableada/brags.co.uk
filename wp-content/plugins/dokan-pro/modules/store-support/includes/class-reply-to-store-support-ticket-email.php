<?php
/**
 * Class Dokan_Reply_To_Store_Support_Ticket file
 *
 * @package Dokan/Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Dokan_Reply_To_Store_Support_Ticket' ) ) :

    /**
     * New Support Ticket.
     *
     * An email sent to the seller when a new support ticket submit.
     *
     * @class       Dokan_Reply_To_Store_Support_Ticket
     * @version     3.3.4
     * @package     Dokan/Classes/Emails
     * @extends     WC_Email
     */
    class Dokan_Reply_To_Store_Support_Ticket extends WC_Email {

        protected $email_data;

        /**
         * Constructor.
         */
        public function __construct() {
            $this->id             = 'dokan_reply_to_store_support_ticket';
            $this->title          = __( 'Dokan Reply To Store Support Ticket', 'dokan' );
            $this->description    = __( 'New support ticket emails are sent to chosen recipient(s) when a reply to store ticket is received.', 'dokan' );
            $this->template_html  = 'emails/reply-to-store-support-ticket.php';
            $this->template_plain = 'emails/plain/reply-to-store-support-ticket.php';
            $this->template_base  = DOKAN_STORE_SUPPORT_DIR . '/templates/';

            $this->placeholders = array(
                '{ticket_id}'   => '',
            );

            // Triggers for this email.
            add_action( 'dokan_reply_to_store_ticket_created_notify', array( $this, 'trigger' ), 10, 2 );

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
            return __( '[{site_title}] A New Reply On Ticket #{ticket_id}', 'dokan' );
        }

        /**
         * Get email heading.
         *
         * @since  3.3.4
         * @return string
         */
        public function get_default_heading() {
            return __( 'A New Reply On Ticket #{ticket_id}', 'dokan' );
        }

        /**
         * Trigger the sending of this email.
         *
         * @param int $store_id The Vendor ID.
         * @param array $email_data Email data.
         */
        public function trigger( $store_id, $email_data ) {
            if ( ! $this->is_enabled() ) {
                return;
            }

            $result = StoreSupportHelper::get_single_topic(
                array(
                    'id'        => $email_data['ticket_id'],
                    'vendor_id' => $store_id,
                )
            );
            if ( $result['dokan_admin_email_notification'] === 'off' ) {
                return;
            }

            $this->setup_locale();
            $this->email_data                  = $email_data;
            $this->placeholders['{ticket_id}'] = $email_data['ticket_id'];

            $this->send( $email_data['to_email'], $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
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
