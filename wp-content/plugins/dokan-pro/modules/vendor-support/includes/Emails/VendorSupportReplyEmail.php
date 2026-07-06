<?php

namespace WeDevs\DokanPro\Modules\VendorSupport\Emails;

use WeDevs\DokanPro\Modules\VendorSupport\Models\Conversation;
use WeDevs\DokanPro\Modules\VendorSupport\Models\Ticket;
use WC_Email;

defined( 'ABSPATH' ) || exit;

/**
 * Vendor Support Ticket Reply Email to Vendor
 *
 * An email sent to the vendor when an admin replies to their support ticket.
 *
 * @class       VendorSupportReplyEmail
 * @since       4.1.2
 * @extends     WC_Email
 */
class VendorSupportReplyEmail extends WC_Email {

    /**
     * Vendor Object
     *
     * @var null|\WeDevs\Dokan\Vendor\Vendor
     */
    public $vendor = null;

    /**
     * Ticket data
     *
     * @var array
     */
    public $ticket_data = [];

    /**
     * Constructor Method
     */
    public function __construct() {
        $this->id             = 'dokan_vendor_support_ticket_reply';
        $this->title          = __( 'Dokan - Ticket Reply from Admin', 'dokan' );
        $this->description    = __( 'Sent to the vendor when an admin replies to their support ticket.', 'dokan' );
        $this->template_base  = DOKAN_VENDOR_SUPPORT_DIR . '/templates/';
        $this->template_html  = 'emails/vendor-ticket-reply.php';
        $this->template_plain = 'emails/plain/vendor-ticket-reply.php';
        $this->placeholders   = [
            '{site_title}'     => '',
            '{ticket_subject}' => '',
        ];

        // Triggers for this email
        add_action( 'dokan_after_vendor_support_conversation_object_save', [ $this, 'trigger' ], 10, 2 );

        // Call parent constructor
        parent::__construct();

        $this->recipient = '';
    }

    /**
     * Get email subject.
     *
     * @return string
     */
    public function get_default_subject() {
        return __( '[{site_title}] - {ticket_subject}', 'dokan' );
    }

    /**
     * Get email heading.
     *
     * @return string
     */
    public function get_default_heading() {
        return __( '{site_title} Reply from Support Ticket', 'dokan' );
    }

    /**
     * Trigger the sending of this email.
     *
     * @param Conversation $data      The conversation object.
     * @param bool         $is_new    Whether this is a new ticket.
     */
    public function trigger( Conversation $data, $is_new = false ) {
        if ( ! $this->is_enabled() ) {
            return;
        }

        // Check if $data is a Conversation object and sender_type is 'admin'
        if ( ! $data instanceof Conversation || $data->get_sender_type() !== 'admin' ) {
            return;
        }
        $ticket_id = $data->get_ticket_id();
        $ticket_post = get_post( $ticket_id );

        if ( ! $ticket_post ) {
            return;
        }
        $ticket = new Ticket( absint( $ticket_id ) );
        $vendor_id = $ticket->get_vendor_id();
        $this->vendor = dokan()->vendor->get( $vendor_id );
        if ( ! $this->vendor || ! $this->vendor->get_id() || ! method_exists( $this->vendor, 'get_email' ) ) {
            return;
        }

        // Populate ticket_data with conversation data
        $this->ticket_data = [
            'ticket_id'   => $ticket_id,
            'message'     => $data->get_message(),
            'sender_id'   => $data->get_sender_id(),
            'sender_type' => $data->get_sender_type(),
        ];
        $this->object      = $this->vendor;
        $this->recipient   = $this->vendor->get_email();

        $this->placeholders['{site_title}']     = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
        $this->placeholders['{ticket_subject}'] = $ticket->get_subject();

        $this->send(
            $this->get_recipient(),
            $this->get_subject(),
            $this->get_content(),
            $this->get_headers(),
            $this->get_attachments()
        );

        $this->restore_locale();
    }

    /**
     * Get content html.
     *
     * @return string
     */
    public function get_content_html() {

        return wc_get_template_html(
            $this->template_html,
            [
                'vendor'             => $this->object,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => false,
                'plain_text'         => false,
                'email'              => $this,
                'admin_name'         => isset( $this->ticket_data['sender_id'] ) ? get_userdata( $this->ticket_data['sender_id'] )->display_name : __( 'Admin', 'dokan' ),
                'ticket_details'     => isset( $this->ticket_data['message'] ) ? $this->ticket_data['message'] : '',
                'ticket_id'          => isset( $this->ticket_data['ticket_id'] ) ? $this->ticket_data['ticket_id'] : 0,
                'admin_support_url'  => dokan_get_navigation_url( 'new' ) . '#/vendor-support/' . ( isset( $this->ticket_data['ticket_id'] ) ? $this->ticket_data['ticket_id'] : 0 ),
            ],
            'dokan/',
            $this->template_base
        );
    }

    /**
     * Get content plain.
     *
     * @return string
     */
    public function get_content_plain() {
        return wc_get_template_html(
            $this->template_plain,
            [
                'vendor'             => $this->object,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => false,
                'plain_text'         => true,
                'email'              => $this,
                'admin_name'         => isset( $this->ticket_data['sender_id'] ) ? get_userdata( $this->ticket_data['sender_id'] )->display_name : __( 'Admin', 'dokan' ),
                'ticket_details'     => isset( $this->ticket_data['message'] ) ? $this->ticket_data['message'] : '',
                'ticket_id'          => isset( $this->ticket_data['ticket_id'] ) ? $this->ticket_data['ticket_id'] : 0,
                'admin_support_url'  => dokan_get_navigation_url( 'new' ) . '#/vendor-support/' . ( isset( $this->ticket_data['ticket_id'] ) ? $this->ticket_data['ticket_id'] : 0 ),
            ],
            'dokan/',
            $this->template_base
        );
    }

    /**
     * Initialise settings form fields.
     */
    public function init_form_fields() {
        $placeholder_text = sprintf(
            // translators: %s available placeholders
            __( 'Available placeholders: %s', 'dokan' ),
            '<code>' . implode( '</code>, <code>', array_keys( $this->placeholders ) ) . '</code>'
        );

        $this->form_fields = [
            'enabled' => [
                'title'   => __( 'Enable/Disable', 'dokan' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable this email notification', 'dokan' ),
                'default' => 'yes',
            ],
            'recipient' => [
                'title'       => __( 'Recipient(s)', 'dokan' ),
                'type'        => 'text',
                'description' => sprintf( __( 'Enter recipients (comma separated). Defaults to vendor email.', 'dokan' ) ),
                'placeholder' => '',
                'default'     => '',
                'desc_tip'    => true,
            ],
            'subject' => [
                'title'       => __( 'Subject', 'dokan' ),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => $placeholder_text,
                'placeholder' => $this->get_default_subject(),
                'default'     => '',
            ],
            'heading' => [
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
                'placeholder' => __( 'Thank you!', 'dokan' ),
                'type'        => 'textarea',
                'default'     => $this->get_default_additional_content(),
                'desc_tip'    => true,
            ],
            'email_type' => [
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
