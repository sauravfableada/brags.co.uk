<?php
namespace WeDevs\DokanPro\Modules\RequestForQuotation\Emails;

use WC_Email;
use WeDevs\DokanPro\Modules\RequestForQuotation\Helper;

/**
 * Accepted Quote Email.
 *
 * An email sent to the admin and vendor when a quote is accepted by customer.
 *
 * @class       AcceptQuote
 * @version     3.12.3
 * @package     Dokan/Modules/RequestAQuote/Emails
 * @author      weDevs
 * @extends     WC_Email
 */
class AcceptQuote extends WC_Email {

    /**
     * @var int $quote_id ID for the quote
     */
    public $quote_id;

    /**
     * @var mixed $request_quote Request quote object
     */
    public $request_quote;

    /**
     * @var mixed $quote_details Quote details
     */
    public $quote_details;

    /**
     * @var mixed $customer_info Customer info
     */
    public $customer_info;

    /**
     * @var mixed $store_info Store info
     */
    public $store_info;

    /**
     * @var string $sending_to Sending to whom
     */
    public $sending_to;

    /**
     * @var string $seller_email Seller email address
     */
    public $seller_email = '';

    /**
     * @var float $shipping_cost Shipping cost
     */
    public $shipping_cost;

    /**
     * @var string $expected_date Delivery expected date.
     */
    public $expected_date;

    /**
     * @var int $quote_expiry Quote expiry timestamp.
     */
    public $quote_expiry;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id             = 'dokan_request_accepted_quote';
        $this->title          = __( 'Dokan Request Accepted Quote', 'dokan' );
        $this->description    = __( 'Quote accept emails are sent to chosen recipient(s) when a quote is accepted by customer.', 'dokan' );
        $this->template_html  = 'emails/request-accept-quote-email.php';
        $this->template_plain = 'emails/plain/request-accept-quote-email.php';
        $this->template_base  = DOKAN_RAQ_TEMPLATE_PATH;

        // Triggers for this email.
        add_action( 'after_dokan_request_quote_accepted', [ $this, 'trigger' ] );

        // Call parent constructor.
        parent::__construct();

        // Other settings.
        $this->recipient = $this->get_option( 'recipient', 'admin@ofthe.quote,vendor@ofthe.product' );
    }

    /**
     * Get email subject.
     *
     * @since 3.12.3
     *
     * @return string
     */
    public function get_default_subject() {
        return __( 'Accepted request quote #{quote_number} on {site_title} - {quote_date}', 'dokan' );
    }

    /**
     * Get email heading.
     *
     * @since 3.12.3
     *
     * @return string
     */
    public function get_default_heading() {
        return __( 'Accepted Request Quote: #{quote_number}', 'dokan' );
    }

    /**
     * Trigger the sending of this email.
     *
     * @since 3.12.3
     *
     * @param int $quote_id
     *
     * @return void
     */
    public function trigger( $quote_id ) {
        if ( ! $this->is_enabled() ) {
            return;
        }

        $this->setup_locale();
        if ( ! $quote_id ) {
            return;
        }

        $this->quote_id      = $quote_id;
        $this->request_quote = Helper::get_request_quote_by_id( $quote_id );
        $this->store_info    = maybe_unserialize( $this->request_quote->store_info );
        $this->quote_details = Helper::get_request_quote_details_by_quote_id( $quote_id );
        $this->customer_info = maybe_unserialize( $this->request_quote->customer_info );
        $this->expected_date = ! empty( $this->request_quote->expected_date ) ? dokan_format_date( $this->request_quote->expected_date ) : '';
        $this->shipping_cost = $this->request_quote->shipping_cost ?? 0;
        $this->placeholders  = [
            '{site_title}'   => $this->get_blogname(),
            '{quote_date}'   => dokan_format_date( $this->request_quote->updated_at ),
            '{quote_number}' => $quote_id,
        ];

        $quote_expiry       = $this->request_quote->expiry_date ?? 0;
        $this->quote_expiry = ! empty( $quote_expiry ) ? dokan_current_datetime()->setTimestamp( $quote_expiry )->format( 'jS M Y' ) : '';

        $seller_email = '';
        foreach ( $this->quote_details as $quote_detail ) {
            $product = wc_get_product( (int) $quote_detail->product_id );
            if ( ! is_a( $product, 'WC_Product' ) ) {
                return;
            }

            $seller_info = dokan_get_vendor_by_product( $product );
            if ( ! $seller_info ) {
                return;
            }

            $seller_email = $seller_info->get_email();
            break;
        }

        $recipients       = [];
        $this->sending_to = '';

        // Add seller email if available.
        if ( ! empty( $seller_email ) ) {
            $recipients[]       = $seller_email;
            $this->sending_to   = 'seller';
            $this->seller_email = $seller_email;
        }

        // Add admin email if available.
        $admin_email = get_option( 'admin_email', '' );
        if ( ! empty( $admin_email ) ) {
            $recipients[]     = $admin_email;
            $this->sending_to = 'admin';
        }

        // If there are recipients, send the email.
        if ( ! empty( $recipients ) ) {
            // Convert recipients array to a comma-separated string.
            $recipients_str = implode( ',', $recipients );

            // Triggered mails on multiple email based on user settings.
            $recipients = str_replace( 'admin@ofthe.quote, vendor@ofthe.product', '', $this->get_recipient() );
            $recipients = ! empty( $recipients ) ? $recipients_str . ',' . $recipients : $recipients_str;

            $this->setup_locale();
            $this->send( $recipients, $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
            $this->restore_locale();
        }
    }

    /**
     * Get content html.
     *
     * @since 3.12.3
     *
     * @access public
     * @return string
     */
    public function get_content_html() {
        ob_start();
        wc_get_template(
            $this->template_html,
            [
	            'quote_id'      => $this->quote_id,
	            'store_info'    => $this->store_info,
	            'request_quote' => $this->request_quote,
	            'customer_info' => $this->customer_info,
                'expected_date' => $this->expected_date,
	            'quote_details' => $this->quote_details,
                'shipping_cost' => $this->shipping_cost,
	            'email_heading' => $this->get_heading(),
                'quote_expiry'  => $this->quote_expiry,
	            'seller_email'  => $this->seller_email,
	            'sending_to'    => $this->sending_to,
	            'email'         => $this,
            ],
            'dokan/',
            $this->template_base
        );

        return ob_get_clean();
    }

    /**
     * Get content plain.
     *
     * @since 3.12.3
     *
     * @access public
     * @return string
     */
    public function get_content_plain() {
        ob_start();
        wc_get_template(
            $this->template_plain,
            [
				'quote_id'      => $this->quote_id,
                'store_info'    => $this->store_info,
                'request_quote' => $this->request_quote,
				'customer_info' => $this->customer_info,
                'expected_date' => $this->expected_date,
                'shipping_cost' => $this->shipping_cost,
				'quote_details' => $this->quote_details,
				'email_heading' => $this->get_heading(),
                'quote_expiry'  => $this->quote_expiry,
                'seller_email'  => $this->seller_email,
				'sending_to'    => $this->sending_to,
                'email'         => $this,
            ],
            'dokan/',
            $this->template_base
        );

        return ob_get_clean();
    }

    /**
     * Initialise settings form fields.
     *
     * @since 3.12.3
     *
     * @return void
     */
    public function init_form_fields() {
        $this->form_fields = [
            'enabled'    => [
                'title'   => __( 'Enable/Disable', 'dokan' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable this email notification', 'dokan' ),
                'default' => 'yes',
            ],
            'recipient'  => [
                'title'       => __( 'Recipient(s)', 'dokan' ),
                'type'        => 'text',
                /* translators: %s: WP admin email */
                'description' => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to %s.', 'dokan' ), '<code>' . esc_attr( 'admin@ofthe.quote, vendor@ofthe.product' ) . '</code>' ),
                'placeholder' => 'admin@ofthe.quote,vendor@ofthe.product',
                'default'     => 'admin@ofthe.quote,vendor@ofthe.product',
                'desc_tip'    => true,
            ],
            'subject'    => [
                'title'       => __( 'Subject', 'dokan' ),
                'type'        => 'text',
                'desc_tip'    => true,
                /* translators: %s: list of placeholders */
                'description' => sprintf( __( 'Available placeholders: %s', 'dokan' ), '<code>{site_title}, {quote_date}, {quote_number}</code>' ),
                'placeholder' => $this->get_default_subject(),
                'default'     => '',
            ],
            'heading'    => [
                'title'       => __( 'Email heading', 'dokan' ),
                'type'        => 'text',
                'desc_tip'    => true,
                /* translators: %s: list of placeholders */
                'description' => sprintf( __( 'Available placeholders: %s', 'dokan' ), '<code>{site_title}, {quote_date}, {quote_number}</code>' ),
                'placeholder' => $this->get_default_heading(),
                'default'     => '',
            ],
            'email_type' => [
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
