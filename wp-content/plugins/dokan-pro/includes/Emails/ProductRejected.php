<?php

namespace WeDevs\DokanPro\Emails;

use RuntimeException;
use WC_Email;
use WC_Product;
use WeDevs\Dokan\Vendor\Vendor;
use WeDevs\DokanPro\ProductRejection\ProductStatusService;

/**
 * Product Rejection Email Handler
 *
 * @since 3.16.0
 */
class ProductRejected extends WC_Email {

    /**
     * Product status manager instance
     *
     * @since 3.16.0
     *
     * @var ProductStatusService
     */
    private ProductStatusService $product_status_service;

    /**
     * Email data used in templates
     *
     * @since 3.16.0
     *
     * @var array
     */
    protected $data;

    /**
     * Constructor
     *
     * Sets up the email notification system.
     *
     * @since 3.16.0
     */
    public function __construct() {
        $this->id          = 'dokan_product_rejected';
        $this->title       = __( 'Dokan Product Rejected', 'dokan' );
        $this->description = __( 'Product rejection emails are sent when an admin rejects the "pending-review" product', 'dokan' );

        $this->template_base  = DOKAN_PRO_DIR . '/templates/';
        $this->template_html  = 'emails/product-rejected.php';
        $this->template_plain = 'emails/plain/product-rejected.php';

        // Initialize status manager
        $this->product_status_service = new ProductStatusService();

        // Set up placeholders
        $this->placeholders = [
            '{site_name}'        => $this->get_blogname(),
            '{product_name}'     => '',
            '{vendor_name}'      => '',
            '{admin_name}'       => '',
            '{product_link}'     => '',
            '{dashboard_link}'   => '',
            '{rejection_date}'   => '',
            '{rejection_reason}' => '',
        ];

        // Call parent constructor
        parent::__construct();

        // Set default recipient to vendor
        $this->recipient = 'vendor@ofthe.product';
    }

    /**
     * Get default email subject
     *
     * @since 3.16.0
     *
     * @return string
     */
    public function get_default_subject(): string {
        /**
         * Filter the default subject of the product rejection email
         *
         * @since 3.16.0
         *
         * @param string $subject Default email subject with placeholders
         */
        return apply_filters( 'dokan_product_rejected_email_subject', __( '[{site_name}] Your product - {product_name} - is rejected', 'dokan' ) );
    }

    /**
     * Get default email heading
     *
     * @since 3.16.0
     *
     * @return string
     */
    public function get_default_heading(): string {
        /**
         * Filter the default heading of the product rejection email
         *
         * @since 3.16.0
         *
         * @param string $heading Default email heading with placeholders
         */
        return apply_filters( 'dokan_product_rejected_email_heading', __( '{product_name} - is rejected', 'dokan' ) );
    }

    /**
     * Trigger the rejection email
     *
     * @since 3.16.0
     *
     * @param int    $product_id Product ID that was rejected
     * @param string $date       Rejection date
     * @param string $reason     Rejection reason from admin
     *
     * @return bool Whether the email was sent successfully
     */
    public function trigger( int $product_id, string $date, string $reason ): bool {
        try {
            $this->setup_locale();

            // Check if product is actually rejected
            $product = wc_get_product( $product_id );
            if ( ! $this->product_status_service->is_rejected( $product ) ) {
                throw new RuntimeException( __( 'Product is not rejected', 'dokan' ) );
            }

            // Get and validate vendor
            $vendor_id = dokan_get_vendor_by_product( $product_id, true );
            $vendor    = dokan()->vendor->get( $vendor_id );
            if ( ! $vendor ) {
                throw new RuntimeException( __( 'Invalid vendor', 'dokan' ) );
            }

            // Validate rejection reason
            $reason = wp_strip_all_tags( $reason );

            // Generate edit link
            $edit_link = add_query_arg(
                [
                    'product_id' => $product_id,
                    '_view_mode' => 'product_email',
                    'action'     => 'edit',
                ],
                dokan_get_navigation_url( 'products' )
            );

            // Prepare email data
            $this->data = $this->prepare_email_data( $product, $vendor, $edit_link, $date, $reason );

            // Set recipient and replace placeholders
            $this->setup_email_properties( $product, $vendor, $edit_link, $date, $reason );

            // Check if email is enabled and has recipient
            if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
                return false;
            }

            /**
             * Fires before sending rejection email
             *
             * @since 3.16.0
             *
             * @param WC_Email $email Email object
             * @param array    $data  Email data
             */
            do_action( 'dokan_before_product_rejection_email', $this, $this->data );

            // Send email
            $sent = $this->send(
                $this->get_recipient(),
                $this->get_subject(),
                $this->get_content(),
                $this->get_headers(),
                $this->get_attachments()
            );

            /**
             * Fires after sending rejection email
             *
             * @since 3.16.0
             *
             * @param WC_Email $email Email object
             * @param array    $data  Email data
             * @param bool     $sent  Whether email was sent
             */
            do_action( 'dokan_after_product_rejection_email', $this, $this->data, $sent );

            return $sent;
        } catch ( \Throwable $e ) {
            dokan_log(
                sprintf(
                    'Product rejection email failed for product #%d: %s',
                    $product_id,
                    $e->getMessage()
                )
            );
            return false;
        } finally {
            $this->restore_locale();
        }
    }

    /**
     * Prepare email data array
     *
     * @since 3.16.0
     *
     * @param WC_Product $product   Product object
     * @param Vendor     $vendor    Vendor object
     * @param string     $edit_link Product edit link
     * @param string     $date      Rejection date
     * @param string     $reason    Rejection reason
     *
     * @return array
     */
    protected function prepare_email_data( WC_Product $product, Vendor $vendor, string $edit_link, string $date, string $reason ): array {
        $data = [
            'product' => [
                'name'      => $product->get_name(),
                'id'        => $product->get_id(),
                'edit_link' => esc_url( $edit_link ),
                'type'      => $product->get_type(),
                'status'    => $product->get_status(),
                'price'     => $product->get_price(),
                'sku'       => $product->get_sku(),
            ],
            'rejection' => [
                'reason' => wp_kses_post( $reason ),
                'date'   => dokan_format_date( $date ),
            ],
        ];

        /**
         * Filter email data before sending
         *
         * @since 3.16.0
         *
         * @param array      $data    Email data
         * @param WC_Product $product Product object
         * @param object     $vendor  Vendor object
         */
        return apply_filters( 'dokan_product_rejection_email_data', $data, $product, $vendor );
    }

    /**
     * Set up email properties
     *
     * @since 3.16.0
     *
     * @param WC_Product $product   Product object
     * @param Vendor     $vendor    Vendor object
     * @param string     $edit_link Product edit link
     * @param string     $date      Rejection date
     * @param string     $reason    Rejection reason
     *
     * @return void
     */
    protected function setup_email_properties( WC_Product $product, Vendor $vendor, string $edit_link, string $date, string $reason ): void {
        $this->recipient = $vendor->get_email();

        $this->placeholders['{product_name}']     = $product->get_name();
        $this->placeholders['{vendor_name}']      = $vendor->get_shop_name();
        $this->placeholders['{product_link}']     = $edit_link;
        $this->placeholders['{dashboard_link}']   = dokan_get_navigation_url();
        $this->placeholders['{rejection_date}']   = dokan_format_date( $date );
        $this->placeholders['{rejection_reason}'] = wp_strip_all_tags( $reason );
    }

    /**
     * Get email content in HTML format
     *
     * @since 3.16.0
     *
     * @return string
     */
    public function get_content_html(): string {
        return wc_get_template_html(
            $this->template_html,
            [
                'email'             => $this,
                'data'              => $this->data,
                'email_heading'     => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'     => false,
                'plain_text'        => false,
                'action_steps'      => $this->get_recommended_actions(),
            ],
            'dokan/',
            $this->template_base
        );
    }

    /**
     * Get email content in plain text format
     *
     * @since 3.16.0
     *
     * @return string
     */
    public function get_content_plain(): string {
        return wc_get_template_html(
            $this->template_plain,
            [
                'email'             => $this,
                'data'              => $this->data,
                'email_heading'     => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'     => false,
                'plain_text'        => true,
                'action_steps'      => $this->get_recommended_actions(),
            ],
            'dokan/',
            $this->template_base
        );
    }

    /**
     * Get action steps for product improvement
     *
     * @since 3.16.0
     *
     * @return array
     */
    protected function get_recommended_actions(): array {
        $steps = [
            [
                'num'   => '1',
                'title' => __( 'Review Requirements', 'dokan' ),
                'desc'  => __( 'Carefully review all the required updates mentioned above.', 'dokan' ),
            ],
            [
                'num'   => '2',
                'title' => __( 'Make Updates', 'dokan' ),
                'desc'  => __( 'Update your product with all the necessary changes.', 'dokan' ),
            ],
            [
                'num'   => '3',
                'title' => __( 'Resubmit', 'dokan' ),
                'desc'  => __( 'Resubmit your product for review.', 'dokan' ),
            ],
        ];

        /**
         * Filter the recommended action steps shown in rejection emails
         *
         * @since 3.16.0
         *
         * @param array $steps Array of step information
         */
        return apply_filters( 'dokan_product_edit_recommended_actions', $steps );
    }

    /**
     * Initialize form fields for email settings
     *
     * @since 3.16.0
     *
     * @return void
     */
    public function init_form_fields(): void {
        $placeholder_text = sprintf(
        /* translators: %s: list of placeholders */
            __( 'Available placeholders: %s', 'dokan' ),
            '<code>' . implode( '</code>, <code>', array_keys( $this->placeholders ) ) . '</code>'
        );

        $this->form_fields = [
            'enabled' => [
                'title'       => __( 'Enable/Disable', 'dokan' ),
                'type'        => 'checkbox',
                'label'       => __( 'Enable product rejection emails', 'dokan' ),
                'default'     => 'yes',
                'desc_tip'    => __( 'Enable this email notification', 'dokan' ),
            ],
            'subject' => [
                'title'       => __( 'Subject', 'dokan' ),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => $placeholder_text,
                'placeholder' => $this->get_default_subject(),
                'default'     => '',
            ],
            'heading' => [
                'title'       => __( 'Email Heading', 'dokan' ),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => $placeholder_text,
                'placeholder' => $this->get_default_heading(),
                'default'     => '',
            ],
            'email_type' => [
                'title'       => __( 'Email Type', 'dokan' ),
                'type'        => 'select',
                'description' => __( 'Choose format of rejection emails.', 'dokan' ),
                'default'     => 'html',
                'class'       => 'email_type wc-enhanced-select',
                'options'     => [
                    'plain'     => __( 'Plain text', 'dokan' ),
                    'html'      => __( 'HTML', 'dokan' ),
                ],
                'desc_tip'    => true,
            ],
        ];
    }
}
