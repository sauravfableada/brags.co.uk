<?php

namespace WeDevs\DokanPro\Emails;

use WeDevs\Dokan\Vendor\Vendor;

class RefundVendor extends AbstractRefund {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id             = 'dokan_vendor_refund';
        $this->title          = __( 'Dokan Refund Processed', 'dokan' );
        $this->description    = __( 'These emails are sent to vendor when a vendor refund request is processed', 'dokan' );
        $this->template_html  = 'emails/refund-seller-mail.php';
        $this->template_plain = 'emails/plain/refund-seller-mail.php';
        $this->template_base  = DOKAN_PRO_DIR . '/templates/';
        $this->placeholders   = [
            '{seller_name}' => '',
            '{amount}'      => '',
            '{reason}'      => '',
            '{order_id}'    => '',
            '{status}'      => '',
            '{order_link}'  => '',
            // Only for backward compatibility.
            '{site_name}' => $this->get_from_name(),
        ];

        // Triggers for this email
        add_action( 'dokan_refund_processed_notification', [ $this, 'trigger' ], 30, 5 );

        // Call parent constructor
        parent::__construct();

        // Other settings
        $this->recipient = 'vendor@ofthe.product';
    }

    /**
     * Get email subject.
     *
     * @since  3.1.0
     * @return string
     */
    public function get_default_subject() {
        return __( '[{site_title}] Your refund request was {status}', 'dokan' );
    }

    /**
     * Get email heading.
     *
     * @since  3.1.0
     * @return string
     */
    public function get_default_heading() {
        return __( 'Refund request for {order_id} is {status}', 'dokan' );
    }

    /**
     * Trigger the sending of this email.
     *
     * @param $seller_mail
     * @param $order_id
     * @param $status
     * @param $refund_amount
     * @param $refund_reason
     */
    public function trigger( $seller_mail, $order_id, $status, $refund_amount, $refund_reason ) {
        if ( ! $this->is_enabled() ) {
            return;
        }
        $this->setup_locale();
        $seller                    = get_user_by( 'email', $seller_mail );
        $this->object              = new Vendor( $seller );

        $this->placeholders['{seller_name}'] = $this->object->get_name();
        $this->placeholders['{amount}']      = dokan()->email->currency_symbol( wc_format_decimal( $refund_amount, false, true ) );
        $this->placeholders['{reason}']      = $refund_reason;
        $this->placeholders['{order_id}']    = $order_id;
        $this->placeholders['{status}']      = $this->get_translated_status( $status );
        $this->placeholders['{order_link}']  = dokan_get_navigation_url( 'orders' ) . '?order_status=wc-refunded';

        $this->send( $seller_mail, $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
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
            $this->template_html, [
                'seller'             => $this->object,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => true,
                'plain_text'         => false,
                'email'              => $this,
                'data'               => $this->placeholders,
            ], 'dokan/', $this->template_base
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
            $this->template_plain, [
                'seller'             => $this->object,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => true,
                'plain_text'         => true,
                'email'              => $this,
                'data'               => $this->placeholders,
			], 'dokan/', $this->template_base
        );
    }

    /**
     * Initialise settings form fields.
     */
    public function init_form_fields() {
        $placeholders = $this->placeholders;
        // remove site_name placeholder
        unset( $placeholders['{site_name}'] );
        /* translators: %s: list of placeholders */
        $placeholder_text  = sprintf( __( 'Available placeholders: %s', 'dokan' ), '<code>' . implode( '</code>, <code>', array_keys( $placeholders ) ) . '</code>' );
        $this->form_fields = [
            'enabled'    => [
                'title'   => __( 'Enable/Disable', 'dokan' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable this email notification', 'dokan' ),
                'default' => 'yes',
            ],
            'subject'    => [
                'title'       => __( 'Subject', 'dokan' ),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => $placeholder_text,
                'placeholder' => $this->get_default_subject(),
                'default'     => '',
            ],
            'heading'    => [
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
