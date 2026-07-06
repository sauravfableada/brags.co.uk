<?php

namespace WeDevs\DokanPro\Modules\ProductQA\Emails;

use WeDevs\Dokan\Vendor\Vendor as DVendor;
use WeDevs\DokanPro\Modules\ProductQA\Models\Question;

defined( 'ABSPATH' ) || exit;

/**
 * Send email to vendor on new Question asked event.
 *
 * @since 3.11.0
 */
class Vendor extends \WC_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'dokan_product_qa_vendor_notification';
		$this->title          = __( 'Dokan Product Q&A Vendor Notification', 'dokan' );
		$this->description    = __( 'This email is sent to a vendor when a Question is asked by customer.', 'dokan' );
		$this->template_html  = 'emails/dokan-product-qa-vendor.php';
		$this->template_plain = 'emails/plain/dokan-product-qa-vendor.php';
		$this->template_base  = DOKAN_PRODUCT_QA_TEMPLATE_PATH;
		$this->placeholders   = [
			'{seller_name}'    => '',
			'{product_name}'   => '',
			'{product_link}'   => '',
			'{customer_name}'  => '',
			'{question}'       => '',
			'{question_url}'   => '',
		];

		// Triggers for this email
		add_action( 'dokan_pro_product_qa_question_created', array( $this, 'trigger' ), 30 );

		// Call parent constructor
		parent::__construct();

		// Other settings
		$this->recipient = 'vendor@ofthe.product';
	}

	/**
	 * Get email subject.
	 *
	 * @since 3.11.0
	 *
	 * @return string
	 */
	public function get_default_subject() {
		return __( '[{customer_name}] asked you a question for your product - {product_name}', 'dokan' );
	}

	/**
	 * Get email heading.
	 *
	 * @since 3.11.0
	 * @return string
	 */
	public function get_default_heading() {
		return __( '{customer_name} - Asked a question on {product_name}', 'dokan' );
	}

	/**
	 * Trigger the email.
	 */
	public function trigger( $question_id ) {
		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return;
		}
		$this->setup_locale();

		try {
			$question = new Question( $question_id );
		} catch (\Exception $exception ) {
			return;
		}

		$product   = wc_get_product( $question->get_product_id() );

		if ( !$product ) {
			return;
		}

		$vendor_id = get_post_field( 'post_author', $question->get_product_id() );
		$vendor = new \WeDevs\Dokan\Vendor\Vendor( absint( $vendor_id ) );

		$this->placeholders['{seller_name}']    = $vendor->get_shop_name();
		$this->placeholders['{customer_name}']  = $question->to_array()['user_display_name'];
		$this->placeholders['{product_name}'] = $product->get_title();
		$this->placeholders['{product_link}'] = dokan_edit_product_url( $product->get_id() );
		$this->placeholders['{question}']     = $question->get_question();
		$this->placeholders['{question_url}'] = add_query_arg( [ 'question_id' => $question->get_id() ], dokan_get_navigation_url( \WeDevs\DokanPro\Modules\ProductQA\Vendor::QUERY_VAR ) );

		$this->send( $vendor->get_email(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		$this->restore_locale();
	}

	/**
	 * Get content html.
	 *
	 * @access public
	 * @return string
	 */
	public function get_content_html(): string {
		return wc_get_template_html(
			$this->template_html, array(
			'email_heading'      => $this->get_heading(),
			'additional_content' => $this->get_additional_content(),
			'sent_to_admin'      => true,
			'plain_text'         => false,
			'email'              => $this,
			'data'               => $this->placeholders,
		), 'dokan/', $this->template_base
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
			$this->template_plain, array(
			'email_heading'      => $this->get_heading(),
			'additional_content' => $this->get_additional_content(),
			'sent_to_admin'      => true,
			'plain_text'         => true,
			'email'              => $this,
			'data'               => $this->placeholders,
		), 'dokan/', $this->template_base
		);
	}

	/**
	 * Initialize settings form fields.
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
