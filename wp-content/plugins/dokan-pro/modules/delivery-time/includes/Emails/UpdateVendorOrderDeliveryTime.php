<?php

namespace WeDevs\DokanPro\Modules\DeliveryTime\Emails;

use \WC_Email;
use \WeDevs\DokanPro\Modules\DeliveryTime\Helper;

/**
 * Notify customer when update order delivery/pickup time via seller.
 *
 * @since 3.7.8
 */
class UpdateVendorOrderDeliveryTime extends WC_Email {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id             = 'dokan_update_vendor_order_delivery_time';
        $this->title          = __( 'Dokan Vendor Update Order Delivery Time', 'dokan' );
        $this->description    = __( 'This email will be sent to the customer of the corresponding order if the vendor has updated the delivery time.', 'dokan' );
        $this->template_html  = 'emails/update-vendor-order-time-email.php';
        $this->template_plain = 'emails/plain/update-vendor-order-time-email.php';
        $this->template_base  = trailingslashit( DOKAN_DELIVERY_TEMPLATE_DIR );
        $this->placeholders   = [
            '{order_id}'    => '',
            '{order_link}'  => '',
            '{seller_name}' => '',
        ];

        // Triggers for this email
        add_action( 'dokan_after_vendor_update_order_delivery_info', [ $this, 'trigger' ], 30, 2 );

        // Call parent constructor
        parent::__construct();

        // Other settings
        $this->recipient = 'customer@ofthe.order';
    }


    /**
     * Get email subject.
     *
     * @since  3.7.8
     *
     * @return string
     */
    public function get_default_subject() {
        return __( '[{site_title}] Your order id #{order_id} has been updated by {seller_name}', 'dokan' );
    }

    /**
     * Get email heading.
     *
     * @since 3.7.8
     *
     * @return string
     */
    public function get_default_heading() {
        return __( 'Order id #{order_id} has been updated by {seller_name}', 'dokan' );
    }

    /**
     * Default content to show below main email content.
     *
     * @since 3.7.8
     *
     * @return string
     */
    public function get_default_additional_content() {
        return __( 'Thanks for using {site_url}!', 'dokan' );
    }

    /**
     * Trigger the sending of this email.
     *
     * @since 3.7.8
     *
     * @param int   $seller_id
     * @param array $updated_data
     *
     * @return void
     */
    public function trigger( $seller_id, $updated_data ) {
        if ( ! $this->is_enabled() ) {
            return;
        }
        if ( ! current_user_can( 'seller' ) || ! $seller_id ) {
            return;
        }

        $order_id = $updated_data['order_id'];
        $order    = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        // get order delivery info
        $order_delivery_info = Helper::get_order_delivery_info( $seller_id, $order_id );
        if ( empty( $order_delivery_info ) ) {
            return;
        }

        $this->setup_locale();
        // Get order, customer & seller info.
        $seller_info   = dokan()->vendor->get( $seller_id );
        $seller_name   = $seller_info->get_shop_name();
        $customer_mail = $order->get_billing_email();
        $order_url     = esc_url(
            add_query_arg(
                [
                    'order_id'   => $order_id,
                    '_view_mode' => 'email',
                    'permission' => '1',
                ],
                dokan_get_navigation_url( 'orders' )
            )
        );

        $this->placeholders['{order_id}']    = $order_id;
        $this->placeholders['{order_link}']  = $order_url;
        $this->placeholders['{seller_name}'] = $seller_name;


        $this->data = [
            'seller_name'             => $seller_name,
            'order'                   => $order,
            'order_id'                => $order_id,
            'order_link'              => $order_url,
            'prev_delivery_date'      => $order_delivery_info->date,
            'prev_delivery_slot'      => $order_delivery_info->slot,
            'prev_delivery_type'      => $order_delivery_info->delivery_type,
            'prev_pickup_location'    => $order->get_meta( 'dokan_store_pickup_location' ),
            'updated_delivery_date'   => $updated_data['delivery_date'],
            'updated_delivery_slot'   => $updated_data['delivery_time_slot'],
            'updated_delivery_type'   => $updated_data['selected_delivery_type'],
            'updated_pickup_location' => $updated_data['store_pickup_location'],
        ];
        $this->send( $customer_mail, $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
        $this->restore_locale();
    }

    /**
     * Get content html.
     *
     * @since 3.7.8
     *
     * @access public
     * @return string
     */
    public function get_content_html() {
        return wc_get_template_html(
            $this->template_html,
            [
                'email_heading'           => $this->get_heading(),
                'sent_to_admin'           => false,
                'plain_text'              => false,
                'email'                   => $this,
                'seller_name'             => $this->data['seller_name'],
                'order'                   => $this->data['order'],
                'order_id'                => $this->data['order_id'],
                'order_link'              => $this->data['order_link'],
                'additional_content'      => $this->get_additional_content(),
                'prev_delivery_date'      => $this->data['prev_delivery_date'],
                'prev_delivery_slot'      => $this->data['prev_delivery_slot'],
                'prev_delivery_type'      => $this->data['prev_delivery_type'],
                'prev_pickup_location'    => $this->data['prev_pickup_location'],
                'updated_delivery_date'   => $this->data['updated_delivery_date'],
                'updated_delivery_slot'   => $this->data['updated_delivery_slot'],
                'updated_delivery_type'   => $this->data['updated_delivery_type'],
                'updated_pickup_location' => $this->data['updated_pickup_location'],
            ],
            'dokan/',
            $this->template_base
        );
        return ob_get_clean();
    }

    /**
     * Get content plain.
     *
     * @since 3.7.8
     *
     * @access public
     * @return string
     */
    public function get_content_plain() {
        return wc_get_template_html(
            $this->template_plain,
            [
                'email_heading'           => $this->get_heading(),
                'sent_to_admin'           => false,
                'plain_text'              => true,
                'email'                   => $this,
                'seller_name'             => $this->data['seller_name'],
                'order'                   => $this->data['order'],
                'order_link'              => $this->data['order_link'],
                'additional_content'      => $this->get_additional_content(),
                'prev_delivery_date'      => $this->data['prev_delivery_date'],
                'prev_delivery_slot'      => $this->data['prev_delivery_slot'],
                'prev_delivery_type'      => $this->data['prev_delivery_type'],
                'prev_pickup_location'    => $this->data['prev_pickup_location'],
                'updated_delivery_date'   => $this->data['updated_delivery_date'],
                'updated_delivery_slot'   => $this->data['updated_delivery_slot'],
                'updated_delivery_type'   => $this->data['updated_delivery_type'],
                'updated_pickup_location' => $this->data['updated_pickup_location'],
            ],
            'dokan/',
            $this->template_base
        );
    }

    /**
     * Initialise settings form fields.
     *
     * @since 3.7.8
     *
     * @return void
     */
    public function init_form_fields() {
        $placeholder_text = sprintf(
            /* translators: %s: list of placeholders */
            __( 'Available placeholders: %s', 'dokan' ),
            '<code>' . esc_html( implode( '</code>, <code>', array_keys( $this->placeholders ) ) ) . '</code>'
        );

        $this->form_fields = [
            'enabled'            => [
                'title'   => __( 'Enable order time updated email notification', 'dokan' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable order delivery time update notification', 'dokan' ),
                'default' => 'yes',
            ],
            'subject'            => [
                'title'       => __( 'Subject', 'dokan' ),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => $placeholder_text,
                'placeholder' => $this->get_default_subject(),
                'default'     => '',
            ],
            'heading'            => [
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
            'email_type'         => [
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
