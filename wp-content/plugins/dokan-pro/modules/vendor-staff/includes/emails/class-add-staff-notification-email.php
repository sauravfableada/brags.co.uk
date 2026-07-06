<?php

/**
 * New Staff Account Email to staff.
 *
 * An email sent to the newly added staff.
 *
 * @class       Dokan_Staff_Add_Notification
 * @extends     WC_Email
 */

if ( ! class_exists( 'WC_Email' ) ) {
    include_once WC_ABSPATH . 'includes/emails/class-wc-email.php';
}

if ( ! class_exists( 'Dokan_Staff_Add_Notification' ) ) :
	class Dokan_Staff_Add_Notification extends WC_Email {
		public function __construct() {
			$this->id             = 'dokan_staff_add_notification';
			$this->title          = __( 'Dokan Staff Add Notification', 'dokan' );
			$this->description    = __( 'This is an email notification sent to the newly added staff.', 'dokan' );
			$this->template_html  = 'emails/staff-account-notification.php';
			$this->template_base  = DOKAN_VENDOR_STAFF_DIR . '/templates/';
			$this->template_plain = 'emails/plain/staff-account-notification.php';
			$this->placeholders   = [];

            // add action to send email
            add_action( 'dokan_new_staff_created', [ $this, 'trigger' ] );

			// Call parent constructor
			parent::__construct();

			// Other settings
			$this->recipient = $this->get_option( 'recipient', 'staff@email.com' );
		}

		/**
		 * Get email subject.
		 *
		 * @return string
		 */
		public function get_default_subject() {
			return __( '[{site_title}] - New Staff Account', 'dokan' );
		}

		/**
		 * Get email heading.
		 *
		 * @return string
		 */
		public function get_default_heading() {
			return __( '[{site_title}] - New Staff Account', 'dokan' );
		}

		/**
		 * Default content to show below main email content.
		 *
		 * @return string
		 */
		public function get_default_additional_content() {
			return __( 'Thank you!', 'dokan' );
		}

		/**
		 * Activation link add on new staff notify email body
		 *
		 * @param $user
		 *
		 * @return void
		 */
		public function trigger( $user ) {
			if ( ! $this->is_enabled() ) {
				return;
			}

			// Ensure we have a proper WP_User object
			$user = $user instanceof WP_User ? $user : new WP_User( $user );

			// Get the password reset key for the user
			$key = get_password_reset_key( $user );
			if ( is_wp_error( $key ) ) {
				return;
			}

			// Get the password reset URL
			$password_reset_url = add_query_arg(
                [
					'action' => 'rp',
					'key'    => $key,
					'login'  => rawurlencode( $user->user_login ),
                ],
                wp_lostpassword_url()
			);

			$this->data = [
				'user_login'         => $user->user_login,
				'password_reset_url' => $password_reset_url,
			];

			$staff_email = $user->user_email;

			// Triggered mails on multiple email based on user settings.
			$recipients = str_replace( 'staff@email.com', '', $this->get_recipient() );
			$recipients = ! empty( $recipients ) ? $staff_email . ',' . $recipients : $staff_email;

			$this->setup_locale();
			$this->send( $recipients, $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
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
			ob_start();
			wc_get_template(
                $this->template_html,
                [
					'email'              => $this,
					'plain_text'         => false,
					'sent_to_admin'      => false,
					'email_heading'      => $this->get_heading(),
					'user_login'         => $this->data['user_login'],
					'password_reset_url' => $this->data['password_reset_url'],
					'additional_content' => $this->get_additional_content(),
                ],
                'dokan/',
                $this->template_base
			);
			return ob_get_clean();
		}

		/**
		 * Get content plain.
		 *
		 * @access public
		 *
		 * @return string
		 */
		public function get_content_plain() {
			ob_start();
			wc_get_template(
                $this->template_plain,
                [
					'email'              => $this,
					'plain_text'         => true,
					'sent_to_admin'      => true,
					'email_heading'      => $this->get_heading(),
					'user_login'         => $this->data['user_login'],
					'password_reset_url' => $this->data['password_reset_url'],
					'additional_content' => $this->get_additional_content(),
                ],
                'dokan/',
                $this->template_base
			);
			return ob_get_clean();
		}
	}
endif;
