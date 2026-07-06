<?php

namespace WeDevs\DokanPro\Emails;

class RefundRequest extends AbstractRefund {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id             = 'dokan_refund_request';
        $this->title          = __( 'Dokan New Refund Request', 'dokan' );
        $this->description    = __( 'These emails are sent to chosen recipient(s) when a vendor send request for refund', 'dokan' );
        $this->template_html  = 'emails/refund_request.php';
        $this->template_plain = 'emails/plain/refund_request.php';
        $this->template_base  = DOKAN_PRO_DIR . '/templates/';
        $this->placeholders   = [
            '{seller_name}' => '',
            '{order_id}'    => '',
            '{refund_url}'  => '',
            '{amount}'      => '',
            // Only for backward compatibility.
            '{site_name}'   => $this->get_from_name(),
        ];

        // Triggers for this email
        add_action( 'dokan_rma_requested_amount', array( $this, 'trigger' ), 30, 2 );
        add_action( 'dokan_refund_requested_amount', array( $this, 'trigger' ), 30, 2 );

        // Call parent constructor
        parent::__construct();

        // Other settings
        $this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
    }

    /**
     * Get email subject.
     * @return string
     */
    public function get_default_subject() {
        return __( '[{site_title}] A New refund request is made by {seller_name}', 'dokan' );
    }

    /**
     * Get email heading.
     * @return string
     */
    public function get_default_heading() {
        return __( 'New Refund Request from - {seller_name}', 'dokan' );
    }

    /**
     * Trigger the sending of this email.
     *
     * @param int   $order_id      Order id
     * @param float $refund_amount Refund amount
     *
     * @return void
     */
    public function trigger( $order_id, $refund_amount ) {
        if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
            return;
        }
        $this->setup_locale();
        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return;
        }

        // get seller id from order
        $seller_id = dokan_get_seller_id_by_order( $order_id );
        if ( ! $seller_id ) {
            return;
        }

        // get seller object
        $seller = dokan()->vendor->get( $seller_id );
        if ( ! is_a( $seller, '\WeDevs\Dokan\Vendor\Vendor' ) || ! $seller->get_id() ) {
            return;
        }

        $this->object                        = $order;
        $this->placeholders['{seller_name}'] = $seller->get_shop_name();
        $this->placeholders['{order_id}']    = $order_id;
        $this->placeholders['{refund_url}']  = admin_url( 'admin.php?page=dokan#/refund?status=pending' );
        $this->placeholders['{amount}']        = dokan()->email->currency_symbol( wc_format_decimal( $refund_amount, false, true ) );
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
                'order'              => $this->object,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => true,
                'plain_text'         => false,
                'email'              => $this,
                'data'               => $this->placeholders,
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
                'order'              => $this->object,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => true,
                'plain_text'         => true,
                'email'              => $this,
                'data'               => $this->placeholders,
            ), 'dokan/', $this->template_base
        );
    }

    /**
     * Initialise settings form fields.
     */
    public function init_form_fields() {
        $placeholders = $this->placeholders;
        // delete backward compatibility placeholder
        unset( $placeholders['{site_name}'] );
        /* translators: %s: list of placeholders */
        $placeholder_text  = sprintf( __( 'Available placeholders: %s', 'dokan' ), '<code>' . implode( '</code>, <code>', array_keys( $placeholders ) ) . '</code>' );
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
                'description'   => sprintf(
                    // translators: %s is admin email address.
                    __( 'Enter recipients (comma separated) for this email. Defaults to %s.', 'dokan' ),
                    '<code>' . esc_attr( get_option( 'admin_email' ) ) . '</code>'
                ),
                'placeholder'   => '',
                'default'       => '',
                'desc_tip'      => true,
            ),
            'subject' => array(
                'title'         => __( 'Subject', 'dokan' ),
                'type'          => 'text',
                'desc_tip'      => true,
                'description'   => $placeholder_text,
                'placeholder'   => $this->get_default_subject(),
                'default'       => '',
            ),
            'heading' => array(
                'title'         => __( 'Email heading', 'dokan' ),
                'type'          => 'text',
                'desc_tip'      => true,
                'description'   => $placeholder_text,
                'placeholder'   => $this->get_default_heading(),
                'default'       => '',
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
                'title'         => __( 'Email type', 'dokan' ),
                'type'          => 'select',
                'description'   => __( 'Choose which format of email to send.', 'dokan' ),
                'default'       => 'html',
                'class'         => 'email_type wc-enhanced-select',
                'options'       => $this->get_email_type_options(),
                'desc_tip'      => true,
            ),
        );
    }
}
