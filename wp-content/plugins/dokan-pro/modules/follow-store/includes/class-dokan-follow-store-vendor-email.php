<?php

use WeDevs\Dokan\Vendor\Vendor;

class Dokan_Follow_Store_Vendor_Email extends WC_Email {

    /**
     * Store Follower
     *
     * @since 1.0.0
     *
     * @var null|WP_User
     */
    public $follower = null;

    /**
     * Following stores
     *
     * @since 1.0.0
     *
     * @var null|int
     */
    public $vendor = null;

    /**
     * Follow status
     *
     * @since 1.0.1
     *
     * @var null|string
     */
    public $status = null;

    /**
     * Constructor Method
     */
    public function __construct() {
        $this->id             = 'vendor_new_store_follower';
        $this->title          = __( 'Dokan Vendor New Store Follower', 'dokan' );
        $this->description    = __( 'Send email to vendor when there is a new store follower or someone unfollows a vendor.', 'dokan' );
        $this->template_html  = 'emails/follow-store-vendor-email-html.php';
        $this->template_plain = 'emails/plain/follow-store-vendor-email-html.php';
        $this->template_base  = trailingslashit( DOKAN_FOLLOW_STORE_VIEWS );
        $this->placeholders   = array(
            '{follower_name}' => '',
        );

        // Call parent constructor
        parent::__construct();

        $this->recipient = 'vendor@ofthe.product';

        add_action( 'dokan_follow_store_toggle_status', array( $this, 'trigger' ), 15, 3 );
    }

    /**
     * Email settings
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function init_form_fields() {
        /* translators: %s: list of placeholders */
        $placeholder_text  = sprintf( __( 'Available placeholders: %s', 'dokan' ), '<code>' . implode( '</code>, <code>', array_keys( $this->placeholders ) ) . '</code>' );
        $this->form_fields = array(
            'enabled' => array(
                'title'         => __( 'Enable/Disable', 'dokan' ),
                'type'          => 'checkbox',
                'label'         => __( 'Enable this email', 'dokan' ),
                'default'       => 'yes',
            ),

            'subject' => array(
                'title'         => __( 'Subject', 'dokan' ),
                'type'          => 'text',
                'desc_tip'      => true,
                'description'   => $placeholder_text,
                'placeholder'   => $this->get_default_subject(),
                'default'       => $this->get_default_subject(),
            ),
            'heading' => array(
                'title'         => __( 'Email heading', 'dokan' ),
                'type'          => 'text',
                'desc_tip'      => true,
                'description'   => $placeholder_text,
                'placeholder'   => $this->get_default_heading(),
                'default'       => $this->get_default_heading(),
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

    /**
     * Email default subject
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_default_subject() {
        return __( '{follower_name}, see new updates from {site_title}', 'dokan' );
    }

    /**
     * Email default heading
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_default_heading() {
        return __( 'Latest updates from {site_title}', 'dokan' );
    }

    /**
     * Send email
     *
     * @since 1.0.0
     *
     * @param int $vendor_id Vendor ID.
     * @param int $follower_id Follower ID.
     * @param string $status Status.
     *
     * @return void
     */
    public function trigger( $vendor_id, $follower_id, $status ) {

        if ( ! $this->is_enabled() ) {
            return;
        }
        $this->setup_locale();

        $this->follower = get_userdata( $follower_id );
        $this->vendor   = dokan()->vendor->get( $vendor_id );
        $this->status   = $status;

        if ( ! $this->get_email_recipient() ) {
            return;
        }

        $this->placeholders['{follower_name}'] = $this->follower->display_name;

        $this->send( $this->get_email_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

        $this->restore_locale();
    }

    /**
     * Follower email
     *
     * @since 1.0.0
     *
     * @return string|null
     */
    public function get_email_recipient() {
        if ( $this->vendor instanceof Vendor && is_email( $this->vendor->get_email() ) ) {
            return $this->vendor->get_email();
        }

        return null;
    }

    /**
     * Email content
     *
     * @since 1.0.0
     *
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
                'data'               => array(
                    'follower' => $this->follower,
                    'status'   => $this->status,
                ),
            ),
            'dokan/',
            $this->template_base
        );
    }

    /**
     * Email content plain
     *
     * @since 4.0.0
     *
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
                'data'               => array(
                    'follower' => $this->follower,
                    'status'   => $this->status,
                ),
            ),
            'dokan/',
            $this->template_base
        );
    }
}
