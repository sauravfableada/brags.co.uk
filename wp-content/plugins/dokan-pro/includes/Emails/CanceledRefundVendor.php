<?php

namespace WeDevs\DokanPro\Emails;

use WeDevs\DokanPro\Refund\Refund;

/**
 * Notify Vendor when a refund request get canceled.
 *
 * @since 3.3.6
 */
class CanceledRefundVendor extends AbstractRefund {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id             = 'dokan_vendor_refund_canceled';
        $this->title          = __( 'Dokan Refund Canceled', 'dokan' );
        $this->description    = __(
            'These emails are sent to vendor when a vendor refund request is canceled',
            'dokan'
        );
        $this->template_html  = 'emails/refund-canceled-seller-mail.php';
        $this->template_plain = 'emails/plain/refund-canceled-seller-mail.php';
        $this->template_base  = DOKAN_PRO_DIR . '/templates/';
        $this->placeholders   = array(
            '{seller_name}' => '',
            '{amount}'      => '',
            '{reason}'      => '',
            '{order_id}'    => '',
            '{status}'      => '',
            '{order_link}'  => '',
            // Only for backward compatibility.
            '{site_name}' => $this->get_from_name(),
        );

        // Triggers for this email
        add_action( 'dokan_pro_refund_cancelled', array( $this, 'trigger' ), 30, 1 );

        // Call parent constructor
        parent::__construct();

        // Other settings
        $this->recipient = 'vendor@ofthe.product';
    }

    /**
     * Get email subject.
     *
     * @since  3.3.6
     *
     * @return string
     */
    public function get_default_subject() {
        return __( '[{site_title}] Your refund request is {status}', 'dokan' );
    }

    /**
     * Get email heading.
     *
     * @since 3.3.6
     *
     * @return string
     */
    public function get_default_heading() {
        return __( 'Refund request for order id #{order_id} is {status}', 'dokan' );
    }

    /**
     * Trigger the sending of this email.
     *
     * @since 3.3.6
     *
     * @param Refund $refund Vendor Refund Request.
     */
    public function trigger( $refund ) {
        if ( ! $this->is_enabled() ) {
            return;
        }
        $this->setup_locale();
        $seller = dokan()->vendor->get( $refund->get_seller_id() );

        if ( ! is_a( $seller, '\WeDevs\Dokan\Vendor\Vendor' ) || ! $seller->get_id() ) {
            return;
        }

        $this->object  = $seller;
        $order_id      = $refund->get_order_id();
        $refund_amount = wc_format_decimal( $refund->get_refund_amount(), false, true );
        $refund_reason = $refund->get_refund_reason();
        $status        = $this->get_translated_status( 'canceled' );
        $seller_mail   = $seller->get_email();
        $order_url     = esc_url(
            add_query_arg(
                array(
                    'order_id' => $order_id,
                    '_view_mode' => 'email',
                    'permission' => '1',
                ), dokan_get_navigation_url( 'orders' )
            )
        );

        $this->placeholders['{seller_name}'] = $seller->get_name();
        $this->placeholders['{order_id}']    = $order_id;
        $this->placeholders['{amount}']      = dokan()->email->currency_symbol( $refund_amount );
        $this->placeholders['{reason}']      = $refund_reason;
        $this->placeholders['{status}']      = $status;
        $this->placeholders['{order_link}']  = $order_url;

        $this->data = array(
            'seller_name' => $seller->get_name(),
            'amount'      => dokan()->email->currency_symbol( $refund_amount ),
            'reason'      => $refund_reason,
            'order_id'    => $order_id,
            'status'      => $status,
            'order_link'  => $order_url,
            'site_name' => $this->get_from_name(),
            'site_url'    => site_url(),
        );
        $this->send( $seller_mail, $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
        $this->restore_locale();
    }

    /**
     * Get content html.
     *
     * @since 3.3.6
     *
     * @access public
     * @return string
     */
    public function get_content_html() {
        return wc_get_template_html(
            $this->template_html, array(
				'seller'             => $this->object,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => false,
				'plain_text'         => false,
				'email'              => $this,
				'seller_name'        => $this->data['seller_name'],
				'status'             => $this->data['status'],
				'order_id'           => $this->data['order_id'],
				'amount'             => $this->data['amount'],
				'reason'             => $this->data['reason'],
				'order_link'         => $this->data['order_link'],
            ), 'dokan/', $this->template_base
        );
    }

    /**
     * Get content plain.
     *
     * @since 3.3.6
     *
     * @access public
     * @return string
     */
    public function get_content_plain() {
        return wc_get_template_html(
            $this->template_plain, array(
                'seller'             => $this->object,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => false,
                'plain_text'         => true,
                'email'              => $this,
                'seller_name'        => $this->data['seller_name'],
                'status'             => $this->data['status'],
                'order_id'           => $this->data['order_id'],
                'amount'             => $this->data['amount'],
                'reason'             => $this->data['reason'],
                'order_link'         => $this->data['order_link'],
            ), 'dokan/', $this->template_base
        );
    }

    /**
     * Initialise settings form fields.
     *
     * @since 3.3.6
     *
     * @return void
     */
    public function init_form_fields() {
        $placeholders = $this->placeholders;
        // unset deprecated placeholders.
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
