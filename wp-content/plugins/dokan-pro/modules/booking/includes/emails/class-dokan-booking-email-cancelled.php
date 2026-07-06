<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Dokan_Email_Booking_Cancelled' ) ) :

    /**
     * New Product Published Email to vendor.
     *
     * An email sent to the vendor when a pending Product is published by admin.
     *
     * @class       Dokan_Email_Booking_Cancelled
     * @version     2.6.8
     * @author      weDevs
     * @extends     WC_Email
     */
    class Dokan_Email_Booking_Cancelled extends WC_Email {

        /**
         * Constructor.
         */
        public function __construct() {
            $this->id             = 'Dokan_Email_Booking_Cancelled_NEW';
            $this->title          = __( 'Dokan Booking Cancelled by Customer', 'dokan' );
            $this->description    = __( 'This email is sent to admin and vendor when booking is cancelled by the customer', 'dokan' );
            $this->template_base  = DOKAN_WC_BOOKING_DIR . '/templates/';
            $this->template_html  = 'emails/dokan-customer-booking-cancelled.php';
            $this->template_plain = 'emails/plain/dokan-customer-booking-cancelled.php';
            $this->placeholders   = [
                '{product_title}' => '',
                '{order_date}'    => '',
                '{order_number}'  => '',
            ];

            // Triggers for this email
            add_action( 'woocommerce_bookings_cancelled_booking', array( $this, 'trigger' ), 20, 1 );

            // Call parent constructor
            parent::__construct();

            $this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
        }

        /**
         * Get email subject.
         *
         * @since  3.1.0
         * @return string
         */
        public function get_default_subject() {
            return __( '"{product_title}" has been cancelled', 'dokan' );
        }

        /**
         * Get email heading.
         *
         * @since  3.1.0
         * @return string
         */
        public function get_default_heading() {
            return __( '{product_title} - is Cancelled', 'dokan' );
        }

        /**
         * Trigger function.
         *
         * @access public
         * @return void
         */
        public function trigger( $booking_id ) {
            if ( ! $this->is_enabled() || ! $this->get_recipient() || ! $booking_id ) {
                return;
            }

            // Only send the booking email for booking post types, not orders, etc
            if ( 'wc_booking' !== get_post_type( $booking_id ) ) {
                return;
            }
            $this->setup_locale();

            $this->object = get_wc_booking( $booking_id );

            if ( ! is_object( $this->object ) || ! $this->object->get_order() ) {
                return;
            }

            if ( $this->object->get_product() ) {
                $this->placeholders['{product_title}'] = $this->object->get_product()->get_title();
            }

            $vendor_id    = dokan_get_seller_id_by_order( wp_get_post_parent_id( $booking_id ) );
            $vendor       = dokan()->vendor->get( $vendor_id );
            $vendor_email = $vendor->get_email();

            if ( $this->object->get_order() ) {
                $billing_email = $this->object->get_order()->get_billing_email();
                $order_date    = $this->object->get_order()->get_date_created() ? $this->object->get_order()->get_date_created()->date( 'Y-m-d H:i:s' ) : '';

                $this->placeholders['{order_date}'] = dokan_format_date( $order_date );

                $this->placeholders['{order_number}'] = $this->object->get_order()->get_order_number();

                $this->recipient = $this->get_recipient() . ',' . $vendor_email . ',' . $billing_email;
            } else {
                $this->placeholders['{order_date}'] = dokan_format_date( $this->object->booking_date );

                $this->placeholders['{order_number}'] = __( 'N/A', 'dokan' );

                if ( $this->object->customer_id && ( $customer = get_user_by( 'id', $this->object->customer_id ) ) ) {
                    $this->recipient = $this->get_recipient() . ',' . $vendor_email . ',' . $customer->user_email;
                }
            }

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
                $this->template_html,
                array(
                    'booking'            => $this->object,
                    'email_heading'      => $this->get_heading(),
                    'additional_content' => $this->get_additional_content(),
                    'sent_to_admin'      => false,
                    'plain_text'         => false,
                ),
                'dokan/', $this->template_base
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
                $this->template_plain,
                array(
                    'booking'            => $this->object,
                    'email_heading'      => $this->get_heading(),
                    'additional_content' => $this->get_additional_content(),
                    'sent_to_admin'      => false,
                    'plain_text'         => false,
                ),
                'dokan/', $this->template_base
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

endif;

return new Dokan_Email_Booking_Cancelled();
