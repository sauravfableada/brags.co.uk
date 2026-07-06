<?php

namespace WeDevs\DokanPro\Emails;

use WC_Email;
use WC_Coupon;

class VendorCouponUpdate extends WC_Email {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id               = 'dokan_admin_updated_vendor_coupon';
        $this->title            = __( 'Dokan Vendor Coupon Updated', 'dokan' );
        $this->description      = __( 'This is an email notification sent to the vendor when a coupon is updated.', 'dokan' );
        $this->template_html    = 'emails/vendor-coupon-update.php';
        $this->template_plain   = 'emails/plain/vendor-coupon-update.php';
        $this->template_base    = DOKAN_PRO_DIR . '/templates/';
        $this->placeholders     = [
            '{coupon_code}' => '',
            '{store_name}'  => '',
        ];

        add_action( 'dokan_admin_updated_vendor_coupon_notification', [ $this, 'trigger' ] );

        parent::__construct();
        $this->recipient = 'vendor@the.coupon';
    }

    /**
     * Get email subject.
     * @return string
     */
    public function get_default_subject() {
        return __( '[{coupon_code}] Your coupon has been updated', 'dokan' );
    }

    /**
     * Get email heading.
     * @return string
     */
    public function get_default_heading() {
        return __( '[{store_name}] Your coupon has been updated', 'dokan' );
    }

    /**
     * Trigger the email.
     */
    public function trigger( $post_id ) {
        if ( ! $this->is_enabled() ) {
            return;
        }

        $coupon     = new WC_Coupon( $post_id );
        $author_id  = get_post_field( 'post_author', $post_id );
        $vendor     = dokan()->vendor->get( $author_id );
        $store_name = $vendor->get_shop_name();

        $this->placeholders['{coupon_code}'] = $coupon->get_code();
        $this->placeholders['{store_name}']  = $store_name;

        $this->setup_locale();
        $email = $vendor->get_email();

        $coupon_edit_url = wp_nonce_url(
            add_query_arg(
                [
                    'post' => $coupon->get_id(),
                    'action' => 'edit',
                    'view' => 'add_coupons',
                ], dokan_get_navigation_url( 'coupons' )
            ), '_coupon_nonce', 'coupon_nonce_url'
        );

        $this->data = [
            'coupon_code' => $coupon->get_code(),
            'store_name'  => $store_name,
            'site_name'   => $this->get_blogname(),
            'coupon_edit_url' => $coupon_edit_url,
        ];

        $this->send( $email, $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
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
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => false,
                'plain_text'         => false,
                'email'              => $this,
                'data'               => $this->data,
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
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => false,
                'plain_text'         => true,
                'email'              => $this,
                'data'               => $this->data,
            ),
            'dokan/', $this->template_base
        );
    }

    /**
     * Initialize settings form fields.
     */
    public function init_form_fields() {
        $placeholder_text  = sprintf( __( 'Available placeholders: %s', 'dokan' ), '<code>' . implode( '</code>, <code>', array_keys( $this->placeholders ) ) . '</code>' ); // phpcs:ignore
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
