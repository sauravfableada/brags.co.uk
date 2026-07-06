<?php

namespace WeDevs\DokanPro\Modules\RMA\Emails;

use WC_Email;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * New Product Published Email to vendor.
 *
 * An email sent to the vendor when a warranty request is made by customer.
 *
 * @class       Dokan_Rma_Send_Warranty_Request
 * @version     2.9.3
 * @author      weDevs
 * @extends     WC_Email
 */
class SendWarrantyRequest extends WC_Email {

        /**
         * Constructor.
         */
        public function __construct() {
            $this->id             = 'Dokan_Rma_Send_Warranty_Request';
            $this->title          = __( 'Dokan Send Refund Request to Vendor', 'dokan' );
            $this->description    = __( 'This email send to vendor once customer request for a refund', 'dokan' );

            $this->template_base  = DOKAN_RMA_DIR . '/templates/';
            $this->template_html  = 'emails/send-warranty-request.php';
            $this->template_plain = 'emails/plain/send-warranty-request.php';
            $this->placeholders   = array(
                '{customer_name}' => '',
                // only for backward compatibility
                '{site_name}'     => $this->get_from_name(),
            );

            // Triggers for this email
            add_action( 'dokan_rma_send_warranty_request', [ $this, 'trigger' ], 30 );

            // Call parent constructor
            parent::__construct();

            $this->recipient = 'vendor@ofthe.product';
        }

        /**
         * Get email subject.
         *
         * @since  2.9.3
         *
         * @return string
         */
        public function get_default_subject() {
            return __( '[{site_title}] A new refunds and return request is sent by ({customer_name})', 'dokan' );
        }

        /**
         * Get email heading.
         *
         * @since  2.9.3
         *
         * @return string
         */
        public function get_default_heading() {
            return __( 'Refunds and return request is sent by ({customer_name})', 'dokan' );
        }

        /**
         * Trigger the sending of this email.
         *
         * @param array $data
         */
        public function trigger( $data ) {
            if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
                return;
            }

            $this->setup_locale();
            $this->object = $data;

            $vendor_id = $data['vendor_id'] ?? '';
            $vendor    = dokan()->vendor->get( $vendor_id );
            $email     = $vendor->get_email();

            if ( ! $email ) {
                return;
            }

            $order_id = $data['order_id'] ?? 0;
            $order    = wc_get_order( $order_id );

            if ( ! $order ) {
                return;
            }

            $this->placeholders['{customer_name}'] = $order->get_formatted_billing_full_name();

            $this->send( $email, $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
            $this->restore_locale();
        }

        /**
         * Get content html.
         *
         * @access public
         *
         * @return string
         */
        public function get_content_html() {
            return wc_get_template_html(
                $this->template_html,
                array(
                    'data'               => $this->get_template_data(),
                    'email_heading'      => $this->get_heading(),
                    'additional_content' => $this->get_additional_content(),
                    'plain_text'         => false,
                    'email'              => $this,
                    'replace'            => $this->placeholders,
                ),
                'dokan/',
                $this->template_base
            );
        }

        /**
         * Get content plain.
         *
         * @access public
         *
         * @return string
         */
        public function get_content_plain() {
            return wc_get_template_html(
                $this->template_plain,
                array(
                    'data'               => $this->get_template_data(),
                    'email_heading'      => $this->get_heading(),
                    'additional_content' => $this->get_additional_content(),
                    'plain_text'         => true,
                    'email'              => $this,
                    'replace'            => $this->placeholders,
                ),
                'dokan/',
                $this->template_base
            );
        }

        /**
         * Build the data array passed to the email template.
         *
         * Falls back to safe defaults when the email is rendered outside
         * of the trigger flow (e.g. in the WooCommerce email preview),
         * where $this->object is replaced with a dummy WC_Order and would
         * otherwise cause a fatal "Cannot use object as array" error.
         *
         * @since 5.0.1
         *
         * @return array
         */
        protected function get_template_data(): array {
            return wp_parse_args(
                $this->object,
                array(
                    'order_id' => '',
                    'type'     => '',
                    'details'  => '',
                    'reasons'  => '',
                )
            );
        }

        /**
         * Initialise settings form fields.
         */
        public function init_form_fields() {
            $placeholders = $this->placeholders;
            // unset site_name
            unset( $placeholders['{site_name}'] );
            /* translators: %s: list of placeholders */
            $placeholder_text  = sprintf( __( 'Available placeholders: %s', 'dokan' ), '<code>' . implode( '</code>, <code>', array_keys( $this->placeholders ) ) . '</code>' );
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
