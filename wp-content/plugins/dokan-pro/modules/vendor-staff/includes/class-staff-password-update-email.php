<?php

namespace DokanPro\Modules\VendorStaff;

use WC_Email;

/**
 * Class Dokan_Staff_Password_Update file
 *
 * @package WooCommerce\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( '\DokanPro\Modules\VendorStaff\Dokan_Staff_Password_Update' ) ) :

    /**
     * Staff password update Email.
     *
     * An email sent to the staff if Vendor updates the password of the staff
     *
     * @class       Dokan_Staff_Password_Update
     * @version     3.7.0
     * @package     WooCommerce/Classes/Emails
     * @extends     WC_Email
     */
    class Dokan_Staff_Password_Update extends WC_Email {

        /**
         * Constructor.
         */
        public function __construct() {
            $this->id             = 'dokan_staff_password_update';
            $this->title          = __( 'Dokan Staff Password Update', 'dokan' );
            $this->description    = __( 'Send email to Vendor Staffs if Vendor updates his/her password.', 'dokan' );
            $this->template_html  = 'emails/staff-password-update.php';
            $this->template_plain = 'emails/plain/staff-password-update.php';
            $this->template_base  = DOKAN_VENDOR_STAFF_DIR . '/templates/';

            // Call parent constructor.
            parent::__construct();

            // Other settings.
            $this->recipient = 'staff@email.com';
            $this->manual = true;
        }

        /**
         * Get email subject.
         *
         * @since 3.7.0
         *
         * @return string
         */
        public function get_default_subject() {
            return __( '[{site_title}] Password Updated', 'dokan' );
        }

        /**
         * Get email heading.
         *
         * @since 3.7.0
         *
         * @return string
         */
        public function get_default_heading() {
            return __( 'Your password updated', 'dokan' );
        }

        /**
         * Default content to show below main email content.
         *
         * @since 3.7.0
         *
         * @return string
         */
        public function get_default_additional_content() {
            return '';
        }

        /**
         * Trigger the sending of this email.
         *
         * @since 3.7.0
         *
         * @param int $staff_id The vendor staff ID.
         */
        public function trigger( $staff_id ) {
            if ( ! $this->is_enabled() ) {
                return;
            }

            $staff = get_userdata( $staff_id );

            if ( ! $staff ) {
                return;
            }

            $this->object = $staff;

            $this->setup_locale();

            $this->send( $staff->user_email, $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

            $this->restore_locale();
        }

        /**
         * Get content html.
         *
         * @since 3.7.0
         *
         * @access public
         *
         * @return string
         */
        public function get_content_html() {
            $store_info = dokan_get_store_info( dokan_get_current_user_id() );
            $store_name = isset( $store_info['store_name'] ) ? $store_info['store_name'] : __( 'Store', 'dokan' );
            $store_url = dokan_get_store_url( dokan_get_current_user_id() );

            return wc_get_template_html(
                $this->template_html,
                [
                    'staff_name'         => $this->object->display_name,
                    'staff_email'        => $this->object->user_email,
                    'blog_title'         => $this->get_blogname(),
                    'store_info'         => sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( $store_url ), $store_name ),
                    'email_heading'      => $this->get_heading(),
                    'sent_to_admin'      => false,
                    'plain_text'         => false,
                    'email'              => $this,
                    'additional_content' => $this->get_additional_content(),
                ],
                'dokan/',
                $this->template_base
            );
        }

        /**
         * Get content plain.
         *
         * @since 3.7.0
         *
         * @access public
         *
         * @return string
         */
        public function get_content_plain() {
            $store_info = dokan_get_store_info( dokan_get_current_user_id() );
            $store_name = isset( $store_info['store_name'] ) ? $store_info['store_name'] : __( 'Store', 'dokan' );

            return wc_get_template_html(
                $this->template_plain,
                [
                    'staff_name'         => $this->object->display_name,
                    'staff_email'        => $this->object->user_email,
                    'blog_title'         => $this->get_blogname(),
                    'store_info'         => $store_name,
                    'email_heading'      => $this->get_heading(),
                    'sent_to_admin'      => false,
                    'plain_text'         => true,
                    'email'              => $this,
                    'additional_content' => $this->get_additional_content(),
                ],
                'dokan/',
                $this->template_base
            );
        }

        /**
         * Initialise settings form fields.
         *
         * @since 3.7.0
         */
        public function init_form_fields() {
            // translators: 1: placeholders
            $placeholder_text = sprintf( __( 'Available placeholders: %s', 'dokan' ), '<code>' . esc_html( implode( '</code>, <code>', array_keys( $this->placeholders ) ) ) . '</code>' );

            $this->form_fields = array(
                'enabled'    => array(
                    'title'   => __( 'Enable/Disable', 'dokan' ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable this email notification', 'dokan' ),
                    'default' => 'yes',
                ),
                'subject'    => array(
                    'title'       => __( 'Subject', 'dokan' ),
                    'type'        => 'text',
                    'desc_tip'    => true,
                    'description' => $placeholder_text,
                    'placeholder' => $this->get_default_subject(),
                    'default'     => '',
                ),
                'heading'    => array(
                    'title'       => __( 'Email heading', 'dokan' ),
                    'type'        => 'text',
                    'desc_tip'    => true,
                    'description' => $placeholder_text,
                    'placeholder' => $this->get_default_heading(),
                    'default'     => '',
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

return new \DokanPro\Modules\VendorStaff\Dokan_Staff_Password_Update();
