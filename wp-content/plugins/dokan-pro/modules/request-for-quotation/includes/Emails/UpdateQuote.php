<?php
namespace WeDevs\DokanPro\Modules\RequestForQuotation\Emails;

use WC_Email;
use WeDevs\DokanPro\Modules\RequestForQuotation\Helper;
use WeDevs\DokanPro\Modules\RequestForQuotation\Model\Quote;

/**
 * New Quote Email.
 *
 * An email sent to the admin, vendor and customer when a new quote is created.
 *
 * @class       NewQuote
 * @version     3.6.0
 * @package     Dokan/Modules/RequestAQuote/Emails
 * @author      weDevs
 * @extends     WC_Email
 */
class UpdateQuote extends WC_Email {

    /**
     * @var int $quote_id ID for the quote
     */
    public $quote_id;

    /**
     * @var mixed $request_quote Request quote object
     */
    public $request_quote;

    /**
     * @var mixed $old_quote_details Old quote details
     */
    public $old_quote_details;

    /**
     * @var mixed $quote_details Quote details
     */
    public $quote_details;

    /**
     * @var mixed $customer_info Customer info
     */
    public $customer_info;

    /**
     * @var string $sending_to Sending to whom
     */
    public $sending_to;

    /**
     * @var string $quote_status Quatation status
     */
    public $quote_status;

    /**
     * @var float $shipping_cost Shipping cost
     */
    public $shipping_cost;

    /**
     * @var string $vendor_msg Vendor quote message.
     */
    public $vendor_msg;

    /**
     * @var int $quote_expiry Quote expiry timestamp.
     */
    public $quote_expiry;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id             = 'dokan_request_update_quote';
        $this->title          = __( 'Dokan Request Update Quote', 'dokan' );
        $this->description    = __( 'New quote emails are sent to chosen recipient(s) when a quote is updated.', 'dokan' );
        $this->template_html  = 'emails/request-update-quote-email.php';
        $this->template_plain = 'emails/plain/request-update-quote-email.php';
        $this->template_base  = DOKAN_RAQ_TEMPLATE_PATH;
        $this->placeholders   = [
            '{quote_date}'   => '',
            '{quote_number}' => '',
        ];

        // Triggers for this email.
        add_action( 'after_dokan_request_quote_updated', [ $this, 'trigger' ], 10, 3 );
        // Call parent constructor.
        parent::__construct();

        // Other settings.
        $this->recipient = $this->get_option( 'recipient', 'vendor@ofthe.product,customer@ofthe.quote' );
    }

    /**
     * Get email subject.
     *
     * @since  3.6.0
     * @return string
     */
    public function get_default_subject() {
        return sprintf(
            /* translators: %s: Quotation status */
            __( '%s request quote #{quote_number} on {site_title} - {quote_date}', 'dokan' ),
            ( $this->quote_status !== Quote::STATUS_APPROVED ? 'Updated' : 'Approve' )
        );
    }

    /**
     * Get email heading.
     *
     * @since  3.6.0
     * @return string
     */
    public function get_default_heading() {
        return sprintf(
            /* translators: %s: Quotation status */
            __( '%s Request Quote: #{quote_number}', 'dokan' ),
            ( $this->quote_status !== Quote::STATUS_APPROVED ? 'Updated' : 'Approve' )
        );
    }

    /**
     * Trigger the sending of this email.
     *
     * @since 3.6.0
     *
     * @param $quote_id
     * @param $old_quote_details
     * @param $new_quote_details
     *
     * @return void
     */
    public function trigger( $quote_id, $old_quote_details, $new_quote_details ) {
        if ( ! $this->is_enabled() ) {
            return;
        }

        if ( ! $quote_id ) {
            return;
        }
        $this->setup_locale();

        $this->quote_id          = $quote_id;
        $this->request_quote     = Helper::get_request_quote_by_id( $quote_id );
        $this->quote_status      = $this->request_quote->status;
        $this->old_quote_details = $old_quote_details;
        $this->quote_details     = $new_quote_details;
        $this->shipping_cost     = $this->request_quote->shipping_cost ?? 0;
        $this->customer_info     = maybe_unserialize( $this->request_quote->customer_info );

        $this->placeholders['{quote_date}']   = dokan_format_date( $this->request_quote->updated_at, 'd-m-Y' );
        $this->placeholders['{quote_number}'] = $quote_id;

        $quote_expiry       = $this->request_quote->expiry_date ?? 0;
        $this->quote_expiry = ! empty( $quote_expiry ) ? dokan_current_datetime()->setTimestamp( $quote_expiry )->format( 'jS M Y' ) : '';

        $store_info       = maybe_unserialize( $this->request_quote->store_info );
        $this->vendor_msg = $store_info['vendor_additional_msg'] ?? '';

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
            $recipients[]     = $seller_email;
            $this->sending_to = 'seller';
        }

        if ( ! empty( $this->customer_info['email_field'] ) ) {
            $recipients[]     = $this->customer_info['email_field'];
            $this->sending_to = 'customer';
        }

        // If there are recipients, send the email.
        if ( ! empty( $recipients ) ) {
            // Convert recipients array to a comma-separated string.
            $recipients_str = implode( ',', $recipients );

            // Triggered mails on multiple email based on user settings.
            $recipients = str_replace( 'vendor@ofthe.product, customer@ofthe.quote', '', $this->get_recipient() );
            $recipients = ! empty( $recipients ) ? $recipients_str . ',' . $recipients : $recipients_str;

            $this->setup_locale();
            $this->send( $recipients, $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
            $this->restore_locale();
        }
    }

    /**
     * Get content html.
     *
     * @access public
     * @return string
     */
    public function get_content_html() {
        return wc_get_template_html(
            $this->template_html, [
                'quote_id'           => $this->quote_id,
                'request_quote'      => $this->request_quote,
                'customer_info'      => $this->customer_info,
                'quote_details'      => $this->quote_details,
                'vendor_msg'         => $this->vendor_msg,
                'old_quote_details'  => $this->old_quote_details,
                'email_heading'      => $this->get_heading(),
                'shipping_cost'      => $this->shipping_cost,
                'quote_status'       => $this->quote_status,
                'quote_expiry'       => $this->quote_expiry,
                'additional_content' => $this->get_additional_content(),
                'email'              => $this,
                'sending_to'         => $this->sending_to,
			], 'dokan/', $this->template_base
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
            $this->template_plain, [
                'quote_id'           => $this->quote_id,
                'request_quote'      => $this->request_quote,
                'customer_info'      => $this->customer_info,
                'quote_details'      => $this->quote_details,
                'vendor_msg'         => $this->vendor_msg,
                'old_quote_details'  => $this->old_quote_details,
                'email_heading'      => $this->get_heading(),
                'shipping_cost'      => $this->shipping_cost,
                'quote_status'       => $this->quote_status,
                'quote_expiry'       => $this->quote_expiry,
                'additional_content' => $this->get_additional_content(),
                'email'              => $this,
                'sending_to'         => $this->sending_to,
			], 'dokan/', $this->template_base
        );
    }

    /**
     * Initialise settings form fields.
     */
    public function init_form_fields() {
        /* translators: %s: list of placeholders */
        $placeholder_text  = sprintf( __( 'Available placeholders: %s', 'dokan' ), '<code>' . implode( '</code>, <code>', array_keys( $this->placeholders ) ) . '</code>' );
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
                'description' => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to %s.', 'dokan' ), '<code>' . esc_attr( 'vendor@ofthe.product, customer@ofthe.quote' ) . '</code>' ),
                'placeholder' => 'vendor@ofthe.product,customer@ofthe.quote',
                'default'     => 'vendor@ofthe.product,customer@ofthe.quote',
                'desc_tip'    => true,
            ],
            'subject'    => [
                'title'       => __( 'Subject', 'dokan' ),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => $placeholder_text,
                'placeholder' => $this->get_default_subject(),
                'default'     => '',
            ],
            'heading'    => [
                'title'       => __( 'Email heading', 'dokan' ),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => $placeholder_text,
                'placeholder' => $this->get_default_heading(),
                'default'     => '',
            ],
            'additional_content' => [
                'title'       => __( 'Additional content', 'dokan' ),
                'description' => __( 'Text to appear below the main email content.', 'dokan' ) . ' ' . $placeholder_text,
                'css'         => 'width:400px; height: 75px;',
                'placeholder' => __( 'N/A', 'dokan' ),
                'type'        => 'textarea',
                'default'     => $this->get_default_additional_content(),
                'desc_tip'    => true,
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
