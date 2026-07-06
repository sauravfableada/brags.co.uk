<?php

namespace WeDevs\DokanPro\Emails;

use WC_Email;
use WeDevs\DokanPro\Shipping\Helper;

class ShippingStatus extends WC_Email {

    /**
     * Customer note.
     *
     * @var array
     */
    public $shipping_info;

    /**
     * Customer note.
     *
     * @var string
     */
    public $ship_info;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id             = 'dokan_email_shipping_status_tracking';
        $this->title          = __( 'Shipping Status Notification for Customer', 'dokan' );
        $this->description    = __( 'This email is set to a customer when add new shipping status on their order', 'dokan' );
        $this->template_html  = 'emails/shipping-status.php';
        $this->template_plain = 'emails/plain/shipping-status.php';
        $this->template_base  = DOKAN_PRO_DIR . '/templates/';
        $this->placeholders   = [
            '{set_email_subject}' => '',
            '{shipping_status}'   => '',
            '{message}'           => '',
            // Only for backward compatibility.
            '{site_name}'         => $this->get_from_name(),
        ];

        // Triggers for this email
        add_action( 'dokan_order_shipping_status_tracking_notify', [ $this, 'trigger' ], 11, 5 );

        // Call parent constructor
        parent::__construct();

        $this->recipient = 'customer@ofthe.order';
    }

    /**
     * Get email subject.
     * @return string
     */
    public function get_default_subject() {
        return __( '[{site_title}] {set_email_subject}', 'dokan' );
    }

    /**
     * Get email heading.
     * @return string
     */
    public function get_default_heading() {
        return __( '{set_email_subject}', 'dokan' );
    }

    /**
     * Default content to show below main email content.
     *
     * @since 3.11.4
     *
     * @return string
     */
    public function get_default_additional_content() {
        return __( 'Thank you for shopping with us!', 'dokan' );
    }

    /**
     * Trigger the email.
     */
    public function trigger( $order_id, $tracking_info, $ship_info, $seller_id, $new_shipment = false ) {
        if ( ! $this->is_enabled() ) {
            return;
        }

        if ( ! $order_id ) {
            return;
        }

        $this->setup_locale();
        $this->object = wc_get_order( $order_id );
        if ( ! $this->object ) {
            return;
        }
        $default_heading = __( 'Your order shipping status changed', 'dokan' );

		if ( $new_shipment ) {
			$default_heading = __( 'New shipment created on your order', 'dokan' );
		}

        $this->recipient     = $this->object->get_billing_email();
        $this->shipping_info = $tracking_info;
        $this->ship_info     = $ship_info;

        $this->shipment_status = $tracking_info->shipping_status ?? '';

        $this->placeholders['{set_email_subject}'] = $default_heading;
        $this->placeholders['{shipping_status}']   = $tracking_info->status_label;
        $this->placeholders['{message}']           = wp_strip_all_tags( $ship_info );

        if ( $this->get_recipient() ) {
            $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
        }

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
                'email'               => $this,
                'order'               => $this->object,
                'ship_info'           => $this->ship_info,
                'plain_text'          => false,
                'sent_to_admin'       => false,
                'email_heading'       => $this->get_heading(),
                'tracking_info'       => $this->shipping_info,
                'additional_content'  => $this->get_additional_content(),
                'enable_mark_receive' => ( Helper::is_mark_as_received_allowed_for_customers() && $this->shipment_status === 'ss_delivered' ),
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
            $this->template_html, array(
                'order'               => $this->object,
                'email'               => $this,
                'plain_text'          => true,
                'sent_to_admin'       => false,
                'tracking_info'       => $this->shipping_info,
                'email_heading'       => $this->get_heading(),
                'additional_content'  => $this->get_additional_content(),
                'enable_mark_receive' => ( Helper::is_mark_as_received_allowed_for_customers() && $this->shipment_status === 'ss_delivered' ),
            ), 'dokan/', $this->template_base
        );
    }

    /**
     * Initialize settings form fields.
     */
    public function init_form_fields() {
        $placeholders = $this->placeholders;
        // remove {site_name} placeholder
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
