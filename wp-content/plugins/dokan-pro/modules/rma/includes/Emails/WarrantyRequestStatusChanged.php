<?php

namespace WeDevs\DokanPro\Modules\RMA\Emails;

use WC_Email;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * RMA (Return/Warranty) Status Changed Email.
 */
class WarrantyRequestStatusChanged extends WC_Email {

    public function __construct() {
        $this->id             = 'Dokan_Rma_Status_Changed';
        $this->title          = __( 'Dokan RMA Status Changed', 'dokan' );
        $this->description    = __( 'This email is sent to the customer when a warranty/request status is updated by vendor', 'dokan' );

        $this->template_base  = DOKAN_RMA_DIR . '/templates/';
        $this->template_html  = 'emails/rma-status-changed.php';
        $this->template_plain = 'emails/plain/rma-status-changed.php';
        $this->placeholders   = array(
            '{customer_name}'  => '',
            '{vendor_name}'    => '',
            '{updated_status}' => '',
            '{request_id}'     => '',
            '{order_number}'   => '',
            '{site_name}'      => $this->get_from_name(),
        );

        add_action( 'dokan_warranty_request_updated', [ $this, 'trigger' ], 30, 2 );

        parent::__construct();

        $this->recipient = '';
    }

    /**
     * Default subject
     */
    public function get_default_subject() {
        return __( 'The status of your RMA request has been updated to: {updated_status} for Order #{order_number}', 'dokan' );
    }

    /**
     * Default heading
     */
    public function get_default_heading() {
        return __( 'Your RMA Request Status Has Been Updated', 'dokan' );
    }

    /**
     * Trigger email.
     */
    public function trigger( $request_id = 0, $vendor_id = 0 ) {
        $this->setup_locale();

        $request = dokan_get_warranty_request( [ 'id' => $request_id ] );
        if ( ! $request ) {
            $this->restore_locale();
            return;
        }

        $this->object = $request;
        // Check if email is enabled
        if ( ! $this->is_enabled() ) {
            return;
        }

        $order_id   = isset( $request['order_id'] ) ? absint( $request['order_id'] ) : 0;
        $order      = $order_id ? wc_get_order( $order_id ) : false;
        $order_num  = $order ? $order->get_order_number() : $order_id;

        // Get customer
        $customer_email = '';
        $customer_name  = '';
        if ( $order ) {
            $customer_email = $order->get_billing_email();
            $customer_name  = $order->get_formatted_billing_full_name();
        } elseif ( ! empty( $request['customer_id'] ) ) {
            $user = get_user_by( 'id', $request['customer_id'] );
            if ( $user ) {
                $customer_email = $user->user_email;
                $customer_name  = $user->display_name;
            }
        }

        if ( empty( $customer_email ) ) {
            $this->restore_locale();
            return;
        }

        // Vendor info
        $vendor_name = '';
        if ( $vendor_id ) {
            $vendor_obj = dokan()->vendor->get( $vendor_id );
            if ( $vendor_obj ) {
                $vendor_name = $vendor_obj->get_shop_name() ?? $vendor_obj->get_seller_username();
            }
        }

        $this->placeholders['{customer_name}']  = $customer_name;
        $this->placeholders['{vendor_name}']    = $vendor_name;
        $this->placeholders['{updated_status}'] = isset( $request['status'] ) ? dokan_warranty_request_status( $request['status'] ) : '';
        $this->placeholders['{request_id}']     = $request_id;
        $this->placeholders['{order_number}']   = $order_num;

        // Recipient(s)
        $recipients = $customer_email;

        if ( 'yes' === $this->get_option( 'notify_admin', 'no' ) ) {
            $admin_email = get_option( 'admin_email' );
            if ( $admin_email ) {
                $recipients .= ',' . $admin_email;
            }
        }

        $this->recipient = $recipients;

        // Send
        $this->send(
            $recipients,
            $this->get_subject(),
            $this->get_content(),
            $this->get_headers(),
            $this->get_attachments()
        );

        $this->restore_locale();
    }

    /**
     * HTML content
     */
    public function get_content_html() {
        return wc_get_template_html(
            $this->template_html,
            [
                'data'               => $this->object,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'plain_text'         => false,
                'email'              => $this,
                'replace'            => $this->placeholders,
            ],
            'dokan/',
            $this->template_base
        );
    }

    /**
     * Plain content
     */
    public function get_content_plain() {
        return wc_get_template_html(
            $this->template_plain,
            [
                'data'               => $this->object,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'plain_text'         => true,
                'email'              => $this,
                'replace'            => $this->placeholders,
            ],
            'dokan/',
            $this->template_base
        );
    }

    /**
     * Settings fields.
     */
    public function init_form_fields() {
        $placeholders = $this->placeholders;
        unset( $placeholders['{site_name}'] );
        $placeholder_text = sprintf(
            // translators: %s: list of placeholders
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
            'notify_admin' => [
                'title'   => __( 'Notify admin', 'dokan' ),
                'type'    => 'checkbox',
                'label'   => __( 'Send a copy of this email to site admin', 'dokan' ),
                'default' => 'no',
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
