<?php

namespace WeDevs\DokanPro\Emails;

use WC_Email;
use WeDevs\Dokan\Vendor\Vendor;

class Announcement extends WC_Email {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id             = 'dokan_announcement';
        $this->title          = __( 'Dokan Announcement', 'dokan' );
        $this->description    = __( 'These emails are sent to a vendor(s) who is are selected in a annoucement ', 'dokan' );
        $this->template_html  = 'emails/announcement.php';
        $this->template_plain = 'emails/plain/announcement.php';
        $this->template_base  = DOKAN_PRO_DIR . '/templates/';
        $this->placeholders   = [
            '{announcement_title}' => '',
            '{announcement_url}'   => '',
            // Only for backward compatibility.
            '{title}'              => '',
            '{message}'            => '',
            '{site_name}'           => $this->get_from_name(),
        ];

        add_action( 'dokan_pro_process_announcement_background_process', [ $this, 'trigger' ], 30, 3 );

        parent::__construct();
        $this->recipient = 'selecetedvendors@the.announcement';
    }

    /**
     * Get email subject.
     * @return string
     */
    public function get_default_subject() {
        return __( 'A new announcement is made at - {site_title}', 'dokan' );
    }

    /**
     * Get email heading.
     * @return string
     */
    public function get_default_heading() {
        return __( 'New Announcement - {announcement_title}', 'dokan' );
    }

    /**
     * Trigger the this email.
     */
    public function trigger( $seller_id, $post_id, $notice_id ) {
        if ( ! $this->is_enabled() ) {
            return;
        }

        $seller_info = new Vendor( $seller_id );

        if ( ! $seller_info->get_id() ) {
            return;
        }

        if ( ! $notice_id ) {
            return;
        }

        $this->setup_locale();
        $email = $seller_info->get_email();

        $announcement_url = dokan_get_navigation_url( 'announcement/single-announcement' ) . "{$notice_id}/";
        $post = get_post( $post_id );

        $this->placeholders['{announcement_title}'] = $post->post_title;
        $this->placeholders['{announcement_url}']   = $announcement_url;
        $this->placeholders['{title}']              = $post->post_title;
        $this->placeholders['{message}']            = $post->post_content;

        $this->data = [
            'announcement_body' => $post->post_content,
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
                'announcement_body'  => $this->data['announcement_body'],
                'data'               => $this->placeholders,
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
                'announcement_body'  => $this->data['announcement_body'],
                'data'               => $this->placeholders,
            ),
            'dokan/', $this->template_base
        );
    }

    /**
     * Initialize settings form fields.
     */
    public function init_form_fields() {
        $placeholders = $this->placeholders;
        // unset deprecated placeholders.
        unset( $placeholders['{title}'], $placeholders['{message}'], $placeholders['{site_name}'] );
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
