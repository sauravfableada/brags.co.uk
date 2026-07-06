<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Dokan_Email_Booking_New' ) ) :

/**
 * New Booking Email to vendor.
 *
 * An email sent to the vendor when a new booking request for confirmation.
 *
 * @class       Dokan_Email_Booking_New
 *
 * @extends     WC_Email
 */
class Dokan_Email_Booking_New extends WC_Email {
    /**
     * @var string|null
     */
    private $heading_confirmation;

    /**
     * @var string|null
     */
    private $subject_confirmation;

        /**
         * Constructor.
         */
        public function __construct() {
            $this->id                   = 'Dokan_Email_Booking_New';
            $this->title                = __( 'Dokan New Booking', 'dokan' );
            $this->description          = __( 'New booking emails are sent to the vendor when a new booking is created and paid. This email is also received when a Pending confirmation booking is created.', 'dokan' );
            $this->heading              = __( 'New booking', 'dokan' );
            $this->heading_confirmation = __( 'Confirm booking', 'dokan' );
            $this->subject              = __( '[{site_title}] New booking for {product_title} (Order {order_number}) - {order_date}', 'dokan' );
            $this->subject_confirmation = __( '[{site_title}] A new booking for {product_title} (Order {order_number}) is awaiting your approval - {order_date}', 'dokan' );
            $this->recipient            = 'vendor@ofthe.product';
            $this->template_html        = 'emails/dokan-admin-new-booking.php';
            $this->template_plain       = 'emails/plain/dokan-admin-new-booking.php';
            $this->template_base        = DOKAN_WC_BOOKING_TEMPLATE_PATH;
            $this->placeholders         = [
                '{product_title}' => '',
                '{order_date}'    => '',
                '{order_number}'  => '',
            ];

            // Triggers for this email
            add_action( 'woocommerce_admin_new_booking_notification', array( $this, 'trigger' ) );

            // Call parent constructor
            parent::__construct();
        }

        /**
         * Get an email subject.
         *
         * @return string
         */
        public function get_default_subject() {
            return $this->subject;
        }

        /**
         * Get an email confirmation subject.
         *
         * @return string
         */
        public function get_default_subject_confirmation(): string {
            return $this->subject_confirmation;
        }

        /**
         * Get email heading.
         *
         * @return string
         */
        public function get_default_heading() {
            return $this->heading;
        }

        /**
         * Get email confirmation heading.
         *
         * @return string
         */
        public function get_default_heading_confirmation(): string {
            return $this->heading_confirmation;
        }

        public function get_subject() {
            if (
                wc_booking_order_requires_confirmation( $this->object->get_order() )
                && 'pending-confirmation' === $this->object->get_status()
            ) {
                $subject = $this->get_option( 'subject_confirmation', $this->subject_confirmation );
            } else {
                $subject = $this->get_option( 'subject', $this->subject );
            }

            return apply_filters( 'woocommerce_email_subject_' . $this->id, $this->format_string( $subject ), $this->object );
        }

        /**
         * Get_heading function.
         *
         * @return string
         */
        public function get_heading() {
            if (
                wc_booking_order_requires_confirmation( $this->object->get_order() )
                && 'pending-confirmation' === $this->object->get_status()
            ) {
                return apply_filters( 'woocommerce_email_heading_' . $this->id, $this->format_string( $this->heading_confirmation ), $this->object );
            } else {
                return apply_filters( 'woocommerce_email_heading_' . $this->id, $this->format_string( $this->heading ), $this->object );
            }
        }

        /**
         * Trigger function.
         *
         * @access public
         * @return void
         */
        public function trigger( $booking_id ) {
            if ( ! $booking_id || ! $this->is_enabled() ) {
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

            $vendor_id    = $this->object->get_meta( '_booking_seller_id' );
            $vendor       = dokan()->vendor->get( $vendor_id );
            $vendor_email = $vendor->get_email();

            $this->recipient = $vendor_email;
            if ( $this->object->get_order() ) {
                $billing_email = $this->object->get_order()->get_billing_email();
                $order_date    = $this->object->get_order()->get_date_created() ? $this->object->get_order()->get_date_created()->date( 'Y-m-d H:i:s' ) : '';

                $this->placeholders['{order_date}']   = dokan_format_date( $order_date );
                $this->placeholders['{order_number}'] = $this->object->get_order()->get_order_number();

                $this->recipient = get_bloginfo( 'admin_email' ) . ',' . $vendor_email . ',' . $billing_email;
            } else {
                $this->placeholders['{order_date}']   = dokan_format_date( $this->object->booking_date );
                $this->placeholders['{order_number}'] = __( 'N/A', 'dokan' );

                if ( $this->object->customer_id && ( $customer = get_user_by( 'id', $this->object->customer_id ) ) ) {
                    $this->recipient = get_bloginfo( 'admin_email' ) . ',' . $vendor_email . ',' . $customer->user_email;
                }
            }

            if ( ! $this->get_recipient() ) {
                return;
            }

            $this->send( $vendor_email, $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
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
                'dokan/',
                $this->template_base
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
                'dokan/',
                $this->template_base
            );
        }

        /**
         * Initialise settings form fields.
         */
        public function init_form_fields() {
            /* translators: %s: list of placeholders */
            $placeholder_text  = sprintf( __( 'Available placeholders: %s', 'dokan' ), '<code>' . implode( '</code>, <code>', array_keys( $this->placeholders ) ) . '</code>' );
            $this->form_fields = array(
                'enabled'              => array(
                    'title'   => __( 'Enable/Disable', 'dokan' ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable this email notification', 'dokan' ),
                    'default' => 'yes',
                ),
                'subject'              => array(
                    'title'       => __( 'Subject', 'dokan' ),
                    'type'        => 'text',
                    'description' => $placeholder_text,
                    'placeholder' => $this->get_default_subject(),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                'subject_confirmation' => array(
                    'title'       => __( 'Subject (Pending confirmation)', 'dokan' ),
                    'type'        => 'text',
                    'description' => $placeholder_text,
                    'placeholder' => $this->get_default_subject_confirmation(),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                'heading'              => array(
                    'title'       => __( 'Email Heading', 'dokan' ),
                    'type'        => 'text',
                    'description' => $placeholder_text,
                    'placeholder' => $this->get_default_heading(),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                'heading_confirmation' => array(
                    'title'       => __( 'Email Heading (Pending confirmation)', 'dokan' ),
                    'type'        => 'text',
                    'description' => $placeholder_text,
                    'placeholder' => $this->get_default_heading_confirmation(),
                    'default'     => '',
                    'desc_tip'    => true,
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
                'email_type'           => array(
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

return new Dokan_Email_Booking_New();
