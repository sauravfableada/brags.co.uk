<?php

namespace WeDevs\DokanPro\Modules\RequestForQuotation\Frontend;

use WC_Product;
use WC_Emails;
use Exception;
use WC_Data_Exception;
use WeDevs\DokanPro\Modules\RequestForQuotation\Model\Quote;
use WeDevs\DokanPro\Modules\RequestForQuotation\RulesValidator;
use WP_Error;

use WC_Product_Addons_Helper;

use WeDevs\DokanPro\Modules\RequestForQuotation\Helper;
use WeDevs\DokanPro\Modules\RequestForQuotation\Session;
use WeDevs\DokanPro\Modules\RequestForQuotation\SettingsHelper;
use WeDevs\Dokan\ReverseWithdrawal\Helper as ReverseWithdrawalHelper;
use WeDevs\Dokan\ReverseWithdrawal\SettingsHelper as ReverseWithdrawalSettingsHelper;

defined( 'ABSPATH' ) || exit;

/**
 * Class for Frontend Hooks integration.
 *
 * @since 3.6.0
 */
class Hooks {

    /**
     * @var mixed $quote_rules
     */
    public $quote_rules;
    /**
     * @var mixed $single_quote_rule
     */
    public $single_quote_rule;

    /**
     * @var array $group_child_products
     */
    public static $group_child_products;

    /**
     * Construct for hooks class.
     */
    public function __construct() {
        // Hide price for selected products.
        add_filter( 'woocommerce_get_price_html', [ $this, 'remove_woocommerce_price_html' ], 10, 2 );
        add_filter( 'dokan_order_min_max_is_valid_cart_item', [ $this, 'validate_order_min_max_for_quote_product' ], 10, 2 );

        // Process and initialize the hooks.
        add_action( 'init', [ $this, 'add_archive_page_hooks' ] );
        add_action( 'woocommerce_single_product_summary', [ $this, 'custom_product_button' ], 1, 0 );
        add_action( 'woocommerce_before_single_product', [ $this, 'custom_product_button' ], 1, 0 ); // Elementor issue fixed

        // Save quote data.
        add_action( 'template_redirect', [ $this, 'insert_customer_quote' ] );
        add_action( 'dokan_quote_expiration_date', [ $this, 'handle_quote_expiration_date' ] );
    }

    /**
     * Remove woocommerce price html.
     *
     * @since 3.6.0
     *
     * @param             $price
     * @param WC_Product $product
     *
     * @return mixed
     */
    public function remove_woocommerce_price_html( $price, WC_Product $product ) {
        // For shop single page loop main product.
        if ( 'grouped' === $product->get_type() ) {
            self::$group_child_products = $product->get_children();

            return $price;
        }

        // For shop single page loop child product.
        if ( ! empty( self::$group_child_products ) && in_array( $product->get_id(), self::$group_child_products, true ) ) {
            return $price;
        }

        if ( 'variation' === $product->get_type() ) {
            $product_id = $product->get_parent_id();
            $product    = wc_get_product( $product_id );
        }

        if ( empty( $this->quote_rules ) ) {
            $this->quote_rules = Helper::get_all_quote_rules();
        }

        $applicable_rule = null;

        foreach ( $this->quote_rules as $rule ) {
            if ( false === apply_filters( 'dokan_request_a_quote_apply_rules', true, $product, $rule ) || ! RulesValidator::validate_availability( $rule, $product->get_id() ) ) {
                continue;
            }

            // Checking if there are no capable rule is set or current loop rule priority is less or lower than the previous rule.
            if ( null === $applicable_rule || $applicable_rule->rule_priority >= $rule->rule_priority ) {
                $applicable_rule = $rule;
            }
        }

        if ( dokan_is_product_author( $product->get_id() ) && dokan_is_seller_dashboard() ) {
            return $price;
        } elseif ( null !== $applicable_rule && $applicable_rule->hide_price && ! dokan_is_product_author( $product->get_id() ) ) {
            add_filter( 'dokan_should_render_stripe_express_payment_request_button', '__return_false' );
            return $applicable_rule->hide_price_text;
        }

        return $price;
    }

    /**
     * Filtered the quote products for min-maxi order quantities in the cart.
     *
     * @since 3.12.3
     *
     * @param bool  $is_valid  Indicates whether the cart item is valid before applying this validation.
     * @param array $cart_item The cart item array containing product details.
     *
     * @return bool
     */
    public function validate_order_min_max_for_quote_product( $is_valid, $cart_item ) {
        $product_id = $cart_item['variation_id'] ?? ( $cart_item['product_id'] ?? 0 );

        if ( ! $product_id ) {
            return $is_valid;
        }

        return ! RulesValidator::validate_availability( Helper::get_quote_applicable_rule(), $product_id );
    }

    /**
     * Add archive page hooks.
     *
     * @since 3.6.0
     *
     * @return void
     */
    public function add_archive_page_hooks() {
        // Replace add to cart button with custom button on shop page.
        add_filter( 'woocommerce_loop_add_to_cart_link', [ $this, 'replace_loop_add_to_cart_link' ], 10, 2 );

        // Add Custom button along with add to cart button on shop page.
        add_action( 'woocommerce_after_shop_loop_item', [ $this, 'custom_add_to_quote_button' ], 11, 2 );
    }

    /**
     * Replace loop add to cart link.
     *
     * @since 3.6.0
     *
     * @param $html
     * @param $product
     *
     * @return mixed|string
     */
    public function replace_loop_add_to_cart_link( $html, $product ) {
        $cart_txt = $html;

        if ( 'simple' !== $product->get_type() ) {
            return $html;
        }

        if ( ! $product->is_in_stock() && ! SettingsHelper::is_out_of_stock_enabled() ) {
            return $html;
        }

        if ( empty( $this->quote_rules ) ) {
            $this->quote_rules = Helper::get_all_quote_rules();
        }

        $applicable_rule = null;

        foreach ( $this->quote_rules as $rule ) {
            if ( ! RulesValidator::validate_availability( $rule, $product->get_id() ) ) {
                continue;
            }

            if ( false === apply_filters( 'dokan_request_a_quote_apply_rules', true, $product, $rule ) ) {
                continue;
            }

            // Checking if there are no capable rule is set or current loop rule priority is less or lower than the previous rule.
            if ( null === $applicable_rule || $applicable_rule->rule_priority >= $rule->rule_priority ) {
                $applicable_rule = $rule;
            }
        }

        if ( null !== $applicable_rule && 'replace' === $applicable_rule->hide_cart_button ) {
            if ( ! dokan_is_product_author( $product->get_id() ) ) {
                wp_enqueue_script( 'dokan-request-a-quote-frontend' );
                return '<a href="javascript:void(0)" rel="nofollow" data-quantity="1" class="dokan_request_button button product_type_' . esc_attr( $product->get_type() ) . ' dokan_add_to_quote_button" data-product_id="' . intval( $product->get_id() ) . '" data-product_sku="' . esc_attr( $product->get_sku() ) . '" aria-label="Add &ldquo;' . esc_attr( $product->get_title() ) . '&rdquo; to your quote" >' . esc_html( $applicable_rule->button_text ) . '</a>';
            }
        }

        if ( $this->check_required_addons( $product->get_id() ) ) {
            //WooCommerce Product Add-ons compatibility
            return $html;
        }

        return $cart_txt;
    }

    /**
     * Custom_add_to_quote_button.
     *
     * @since 3.6.0
     *
     * @return string|void
     */
    public function custom_add_to_quote_button() {
        global $product;

        if ( ! $product->is_in_stock() && ! SettingsHelper::is_out_of_stock_enabled() ) {
            return;
        }

        if ( empty( $this->quote_rules ) ) {
            $this->quote_rules = Helper::get_all_quote_rules();
        }

        $applicable_rule = null;

        foreach ( $this->quote_rules as $rule ) {
            if ( ! RulesValidator::validate_availability( $rule, $product->get_id() ) ) {
                continue;
            }

            if ( $this->check_required_addons( $product->get_id() ) ) {
                return __( 'Select options', 'dokan' );
            }

            if ( false === apply_filters( 'dokan_request_a_quote_apply_rules', true, $product, $rule ) ) {
                continue;
            }

            if ( null === $applicable_rule || $applicable_rule->rule_priority >= $rule->rule_priority ) {
                $applicable_rule = $rule;
            }
        }

        if ( null === $applicable_rule || 'replace' === $applicable_rule->hide_cart_button ) {
            return;
        }

        if ( ! dokan_is_product_author( $product->get_id() ) ) {
            if ( 'simple' === $product->get_type() ) {
                wp_enqueue_script( 'dokan-request-a-quote-frontend' );
                echo '<a href="javascript:void(0)" rel="nofollow" data-product_id="' . esc_attr( $product->get_id() ) . '" data-product_sku="' . esc_attr( $product->get_sku() ) . '" class="button dokan_request_button add_to_cart_button product_type_' . esc_attr( $product->get_type() ) . '">' . esc_html( $applicable_rule->button_text ) . '</a>';
            } elseif ( 'keep_and_add_new' === $applicable_rule->hide_cart_button && ! empty( $applicable_rule->button_text ) ) {
                wp_enqueue_script( 'dokan-request-a-quote-frontend' );
                echo '<a href="javascript:void(0)" rel="nofollow" data-product_id="' . esc_attr( $product->get_id() ) . '" data-product_sku="' . esc_attr( $product->get_sku() ) . '" class="button dokan_request_button add_to_cart_button product_type_' . esc_attr( $product->get_type() ) . '">' . esc_html( $applicable_rule->button_text ) . '</a>';
            }
        }
    }

    /**
     * Check required addons.
     *
     * @since 3.6.0
     *
     * @param $product_id
     *
     * @return bool
     */
    public function check_required_addons( $product_id ): bool {
        // No parent add-ons, but yes to global.
        if ( in_array( 'woocommerce-product-addons/woocommerce-product-addons.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
            $addons = WC_Product_Addons_Helper::get_product_addons( $product_id );

            if ( ! empty( $addons ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Custom product button.
     *
     * @since 3.6.0
     *
     * @return void
     */
    public function custom_product_button() {
        global $product;

        if ( empty( $this->quote_rules ) ) {
            $this->quote_rules = Helper::get_all_quote_rules();
        }

        // check if reverse withdrawal feature is enabled
        if ( ReverseWithdrawalSettingsHelper::is_enabled() ) {
            // get vendor id
            $vendor_id = dokan_get_vendor_by_product( $product, true );
            // check if action is taken for this vendor
            $failed_actions = ReverseWithdrawalHelper::get_failed_actions_by_vendor( $vendor_id );
            if ( in_array( 'enable_catalog_mode', $failed_actions, true ) ) {
                return;
            }
        }

        foreach ( $this->quote_rules as $rule ) {
            if ( ! RulesValidator::validate_availability( $rule, $product->get_id() ) ) {
                continue;
            }

            if ( 'replace' !== $rule->hide_cart_button && 'keep_and_add_new' !== $rule->hide_cart_button ) {
                continue;
            }

            if ( false === apply_filters( 'dokan_request_a_quote_apply_rules', true, $product, $rule ) ) {
                continue;
            }

            // Checking if already there is an applied rule and if current loop rule priority if higher than the old rule loop priority then skip.
            if ( ! empty( $this->single_quote_rule ) && $this->single_quote_rule->rule_priority < $rule->rule_priority ) {
                continue;
            }

            $this->single_quote_rule = $rule;

            if ( 'variable' === $product->get_type() ) {
                remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20 );
                add_action( 'woocommerce_single_variation', [ $this, 'custom_button_replacement' ], 30 );
            } else {
                remove_action( 'woocommerce_simple_add_to_cart', 'woocommerce_simple_add_to_cart', 30 );
                add_action( 'woocommerce_simple_add_to_cart', [ $this, 'custom_button_replacement' ], 30 );
            }
            add_filter( 'dokan_stripe_express_should_render_payment_request_button', '__return_false' );
        }
    }

    /**
     * Custom button replacement.
     *
     * @since 3.6.0
     *
     * @return void
     */
    public function custom_button_replacement() {
        global $product;

        $template_name = 'variable' === $product->get_type() ? 'variable' : 'custom-button';

        wp_enqueue_script( 'dokan-request-a-quote-frontend' );

        dokan_get_template_part(
            $template_name, '', [
                'single_quote_rule'    => $this->single_quote_rule,
                'request_quote_vendor' => true,
            ]
        );
    }

    /**
     * Verify the nonce field.
     *
     * @since 3.12.3
     *
     * @param array  $data        Form submission data.
     * @param string $nonce_field The nonce field name.
     * @param string $action      The action name.
     *
     * @return bool Returns true if the nonce is valid, otherwise false.
     */
    private function verify_nonce( $data, $nonce_field, $action ): bool {
        if ( empty( $data[ $nonce_field ] ) || ! wp_verify_nonce( esc_attr( sanitize_text_field( wp_unslash( $data[ $nonce_field ] ) ) ), $action ) ) {
            return false;
        }

        return true;
    }

    /**
     * Insert customer quote.
     *
     * @since 3.6.0
     *
     * @throws WC_Data_Exception
     * @throws Exception
     * @return void
     */
    public function insert_customer_quote() {

        /**
         * Filters the data submitted for the Dokan quote actions.
         *
         * This filter allows modification of the data submitted via the quote actions form.
         *
         * @since 3.12.3
         *
         * @param array $data The submitted data from the quote actions form.
         */
        $data = apply_filters( 'dokan_quote_actions_submitted_data', $_POST ); // phpcs:ignore

        if ( empty( $data ) ) {
            return;
        }

        if ( ! $this->verify_nonce( $data, 'dokan_quote_nonce', 'save_dokan_quote_action' ) ) {
            return;
        }

        // Handle quotation re-opened statement from vendor.
        if ( ! empty( $data['reopened_by_vendor_button'] ) ) {
            return $this->handle_quotation_reopened_statement( $data );
        }

        // Handle quotation accepted statement from customer.
        if ( ! empty( $data['accepted_by_customer_button'] ) ) {
            return $this->handle_quotation_accepted_statement( $data );
        }

        // Handle quotation approved statement from vendor.
        if ( ! empty( $data['approved_by_vendor_button'] ) ) {
            return $this->handle_quotation_approve_statement( $data );
        }

        // Handle quotation order conversion statement.
        if ( ! empty( $data['dokan_convert_to_order_customer'] ) ) {
            return $this->handle_quotation_convert_statement( $data );
        }

        // Get quotes from session.
        $quotes = $this->get_updated_quotes( $data );

        if ( empty( $quotes ) ) {
            wc_add_notice( __( 'No item found in quote basket.', 'dokan' ), 'error' );
            return;
        }

        $validate_info = $this->validate_customer_info_fields( $data );
        if ( isset( $validate_info['status'] ) && ! $validate_info['status'] ) {
            $error_msg = $validate_info['msg'] ?? __( 'Please provide all the required information.', 'dokan' );

            wc_add_notice( $error_msg, 'error' );
            return;
        }

        if ( isset( $data['dokan_quote_save_action'] ) ) {
            $this->dokan_quote_save_action( $data, $quotes );
        }
    }

    /**
     * Handle quotation reopened by vendor statements.
     *
     * @since 3.12.3
     *
     * @param array $data
     *
     * @return void
     */
    public function handle_quotation_reopened_statement( $data ): void {
        $this->handle_quote_action(
            $data,
            'reopened_by_vendor_button',
            'reopened_by_vendor',
            Quote::STATUS_PENDING,
            __( 'Your quote has been successfully reopened.', 'dokan' )
        );
    }

    /**
     * Get the quote list from session.
     *
     * @since 3.12.3
     *
     * @param array $data
     *
     * @return array|mixed
     */
    public function get_updated_quotes( $data ) {
        $quote_session = Session::init();
        $quotes        = $quote_session->get( DOKAN_SESSION_QUOTE_KEY );

        if ( empty( $quotes ) ) {
            return [];
        }

        $quote_qty     = ! empty( $data['quote_qty'] ) ? wc_clean( wp_unslash( $data['quote_qty'] ) ) : [];
        $offered_price = ! empty( $data['offered_price'] ) ? wc_clean( wp_unslash( $data['offered_price'] ) ) : [];

        foreach ( $quotes as $quote_item_key => $quote_item ) {
            $quantity = isset( $quote_qty[ $quote_item_key ] ) ? intval( $quote_qty[ $quote_item_key ] ) : $quote_item['quantity'];
            $price    = isset( $offered_price[ $quote_item_key ] ) ? floatval( $offered_price[ $quote_item_key ] ) : $quote_item['offered_price'];

            if ( $quantity <= 0 || $price <= 0 ) {
                unset( $quotes[ $quote_item_key ] );
            } else {
                $quotes[ $quote_item_key ]['quantity']      = $quantity;
                $quotes[ $quote_item_key ]['offered_price'] = $price;
            }
        }

        $quote_session->set( DOKAN_SESSION_QUOTE_KEY, $quotes );

        return $quotes;
    }

    /**
     * Handle quotation accepted by customer statements.
     *
     * @since 3.12.3
     *
     * @param array $data
     *
     * @return void
     */
    public function handle_quotation_accepted_statement( $data ) {
        global $wpdb;

        $status   = ! empty( $data['accepted_by_customer'] ) ? sanitize_text_field( wp_unslash( $data['accepted_by_customer'] ) ) : Quote::STATUS_ACCEPT;
        $quote_id = sanitize_text_field( wp_unslash( $data['accepted_by_customer_button'] ) );

        $updated = $wpdb->update(
            $wpdb->prefix . 'dokan_request_quotes',
            [
                'status'      => $status,
                'expiry_date' => Helper::handle_schedule_for_quote_expiration( $quote_id ),
            ],
            [ 'id' => $quote_id ],
            [ '%s' ],
            [ '%d' ]
        );

        if ( is_wp_error( $updated ) ) {
            wc_add_notice( $updated->get_error_message(), 'error' );
            return;
        }

        if ( ! $updated ) {
            wc_add_notice( __( 'Something went wrong! Your quote could not be updated.', 'dokan' ), 'error' );
            return;
        }

        wc_add_notice( __( 'Your quote has been successfully accepted.', 'dokan' ), 'success' );

        WC_Emails::instance();
        $quote_id = sanitize_text_field( wp_unslash( $data['accepted_by_customer_button'] ) );
        do_action( 'after_dokan_request_quote_accepted', $quote_id );
    }

    /**
     * Handle quotation approved by vendor statements.
     *
     * @since 3.12.3
     *
     * @param array $data
     *
     * @return void
     */
    public function handle_quotation_approve_statement( $data ) {
        global $wpdb;

        // Sanitize input data.
        $quote_id      = sanitize_text_field( wp_unslash( $data['approved_by_vendor_button'] ) );
        $shipping_cost = ! empty( $data['shipping_cost'] ) ? sanitize_text_field( wp_unslash( $data['shipping_cost'] ) ) : 0;
        $status        = ! empty( $data['approved_by_vendor'] ) ? sanitize_text_field( wp_unslash( $data['approved_by_vendor'] ) ) : 'approve';

        // Get quote and store info
        $quote_info = Helper::get_request_quote_by_id( $quote_id );
        $store_info = ! empty( $quote_info->store_info ) ? maybe_unserialize( $quote_info->store_info ) : [];

        // Determine the status.
        if ( Helper::compare_quote_for_update_status_availability( $data, $quote_info ) ) {
            $status = Quote::STATUS_UPDATE;
        }

        // Add vendor additional message if present
        if ( ! empty( $data['vendor_additional_msg'] ) ) {
            $store_info['vendor_additional_msg'] = sanitize_textarea_field( wp_unslash( $data['vendor_additional_msg'] ) );
        }

        // Handle schedule for quote expiry and get the expiry timestamp.
        $expiry_date = $status === 'approve' ? Helper::handle_schedule_for_quote_expiration( $quote_id ) : 0;

        // Update the quote in the database.
        $quote_approved = $wpdb->update(
            $wpdb->prefix . 'dokan_request_quotes',
            [
                'status'        => $status,
                'store_info'    => maybe_serialize( $store_info ),
                'expiry_date'   => $expiry_date,
                'shipping_cost' => $shipping_cost,
            ],
            [ 'id' => $quote_id ],
            [ '%s', '%s', '%d', '%f' ],
            [ '%d' ]
        );

        // Handle errors and notifications.
        if ( $this->handle_wp_error( $quote_approved ) ) {
            return;
        }

        // Update the quote details.
        $quote_updated = $this->dokan_update_quote( $data );
        if ( $this->handle_wp_error( $quote_updated ) ) {
            return;
        }

        wc_add_notice( __( 'Your quote has been successfully updated.', 'dokan' ), 'success' );
    }

    /**
     * Handle quote expiration date based on the scheduled action.
     *
     * @since 3.12.3
     *
     * @param int $quote_id
     *
     * @return void
     */
    public function handle_quote_expiration_date( $quote_id ) {
        $expired = Helper::change_status( 'dokan_request_quotes', $quote_id, 'expired' );

        if ( is_wp_error( $expired ) ) {
            wc_add_notice( $expired->get_error_message(), 'error' );
            return;
        }

        if ( ! $expired ) {
            wc_add_notice( __( 'Something went wrong! Your quote could not be updated.', 'dokan' ), 'error' );
            return;
        }
    }

    /**
     * Handle WP_Error and add notice if there's an error.
     *
     * @since 3.12.3
     *
     * @param mixed $result The result to check for WP_Error.
     *
     * @return bool True if there was an error, false otherwise.
     */
    private function handle_wp_error( $result ) {
        if ( is_wp_error( $result ) ) {
            wc_add_notice( $result->get_error_message(), 'error' );
            return true;
        }

        if ( ! $result ) {
            wc_add_notice( __( 'Something went wrong! Your quote could not be updated.', 'dokan' ), 'error' );
            return true;
        }

        return false;
    }

    /**
     * Handle quotation actions.
     *
     * @since 3.12.3
     *
     * @param array  $data            Form submission data.
     * @param string $button_field    The button field name.
     * @param string $status_field    The status field name.
     * @param string $default_status  The default status.
     * @param string $success_message The success message.
     *
     * @return void
     */
    private function handle_quote_action( $data, $button_field, $status_field, $default_status, $success_message ): void {
        $status   = ! empty( $data[ $status_field ] ) ? sanitize_text_field( wp_unslash( $data[ $status_field ] ) ) : $default_status;
        $quote_id = sanitize_text_field( wp_unslash( $data[ $button_field ] ) );
        $updated  = Helper::change_status( 'dokan_request_quotes', $quote_id, $status );

        if ( is_wp_error( $updated ) ) {
            wc_add_notice( $updated->get_error_message(), 'error' );
            return;
        }

        if ( ! $updated ) {
            wc_add_notice( __( 'Something went wrong! Your quote could not be updated.', 'dokan' ), 'error' );
            return;
        }

        wc_add_notice( $success_message, 'success' );
    }

    /**
     * Handle quotation approve by vendor statements.
     *
     * @since 3.12.3
     *
     * @param array $data
     *
     * @return void
     */
    public function handle_quotation_convert_statement( $data ) {
        $quote_id     = sanitize_text_field( wp_unslash( $data['dokan_convert_to_order_customer'] ) );
        $converted_by = empty( $data['converted_by'] ) ? 'Admin' : sanitize_text_field( wp_unslash( $data['converted_by'] ) );
        $converted    = $this->convert_to_order( $quote_id, $converted_by );

        if ( is_wp_error( $converted ) ) {
            wc_add_notice( $converted->get_error_message(), 'error' );
            return;
        }

        $quote_order = wc_get_order( $converted );

        /* translators: %1$s: Quote id, %2$s: Order id */
        wc_add_notice( sprintf( __( 'Your Quote #%1$s has been converted to Order #%2$s.', 'dokan' ), $quote_id, $quote_order->get_id() ), 'success' );

        if ( 'Customer' === $converted_by ) {
            wp_safe_redirect( $quote_order->get_checkout_payment_url() );
            exit;
        }
    }

    /**
     * Validate customer info inputs for quote placements.
     *
     * @since 3.12.3
     *
     * @param array $data
     *
     * @return array
     */
    public function validate_customer_info_fields( $data ) {
        $validate       = true;
        $missing_fields = [];

        // List of required fields and their corresponding error messages.
        $required_fields = [
            'name_field'     => __( 'name', 'dokan' ),
            'email_field'    => __( 'email', 'dokan' ),
            'phone_field'    => __( 'phone', 'dokan' ),
            'country'        => __( 'shipping country', 'dokan' ),
            'state_address'  => __( 'shipping state', 'dokan' ),
        ];

        // Check for missing required fields
        foreach ( $required_fields as $field => $error_message ) {
            $field_value = ! empty( $data[ $field ] ) ? sanitize_text_field( wp_unslash( $data[ $field ] ) ) : '';
            if ( empty( trim( $field_value ) ) ) {
                $missing_fields[] = $error_message;
                $validate         = false;
            }
        }

        // Generate error message if any field is missing.
        if ( ! $validate ) {
            $fields = implode( ', ', $missing_fields );
            $msg    = sprintf(
                /* translators: %s: Missing fields name */
                __( 'Please provide %s field information\'s as per required.', 'dokan' ),
                $fields
            );

            return [
                'status' => $validate,
                'msg'    => $msg,
            ];
        }

        // Return validation status if all fields are provided.
        return [
            'status' => $validate,
            'msg'    => '',
        ];
    }

    /**
     * Dokan save quote.
     *
     * @since 3.6.0
     *
     * @param array $data
     * @param array $quotes
     *
     * @return void
     */
    public function dokan_quote_save_action( $data, $quotes ) {
        if ( ! $this->verify_nonce( $data, 'dokan_quote_nonce', 'save_dokan_quote_action' ) ) {
            return;
        }

        if ( empty( $quotes ) ) {
            wc_add_notice( __( 'No item found in quote basket.', 'dokan' ), 'error' );
            return;
        }

        if ( isset( $data['dokan_quote_save_action'] ) ) {
            $customer_info['customer_offers'] = ! empty( $data['offered_price'] ) ? wc_clean( wp_unslash( $data['offered_price'] ) ) : [];
            unset( $_POST['dokan_quote_save_action'] );
        }

        $customer_info = $this->get_customer_info( $data );
        $request_quote = [
            'customer_info' => $customer_info,
            'quote_title'   => "{$customer_info['name_field']}",
            'user_id'       => ! empty( get_current_user_id() ) ? get_current_user_id() : 0,
            'store_info'    => $this->get_store_info( $quotes ),
            'expected_date' => $this->get_expected_delivery_date( $data ),
        ];

        $request_quote_id = Helper::create_request_quote( $request_quote );

        if ( is_wp_error( $request_quote_id ) ) {
            wc_add_notice( $request_quote_id->get_error_message(), 'error' );
            return;
        }

        if ( $request_quote_id > 0 ) {
            $this->save_quote_details( $request_quote_id, $quotes );

            WC_Emails::instance();
            do_action( 'after_dokan_request_quote_inserted', $request_quote_id );

            $quote_session = Session::init();
            $quote_session->delete( DOKAN_SESSION_QUOTE_KEY );

            wc_add_notice( __( 'Your quote has been submitted successfully.', 'dokan' ), 'success' );

            wp_safe_redirect( rtrim( wc_get_account_endpoint_url( 'request-a-quote' ), '/' ) . "/{$request_quote_id}" );
            exit;
        }

        wc_add_notice( __( 'Something went wrong! Your quote not saved.', 'dokan' ), 'error' );
    }

    /**
     * Get customer information from POST data.
     *
     * @since 3.12.3
     *
     * @param array $data
     *
     * @return array The sanitized customer information.
     */
    private function get_customer_info( $data ): array {
        return [
            'city'                    => sanitize_text_field( wp_unslash( $data['city'] ?? '' ) ),
            'country'                 => sanitize_text_field( wp_unslash( $data['country'] ?? '' ) ),
            'post_code'               => sanitize_text_field( wp_unslash( $data['post_code'] ?? '' ) ),
            'name_field'              => sanitize_text_field( wp_unslash( $data['name_field'] ?? '' ) ),
            'customer_id'             => is_user_logged_in() ? dokan_get_current_user_id() : 0,
            'email_field'             => sanitize_text_field( wp_unslash( $data['email_field'] ?? '' ) ),
            'phone_field'             => dokan_sanitize_phone_number( wp_unslash( $data['phone_field'] ?? '' ) ),
            'addr_line_1'             => sanitize_text_field( wp_unslash( $data['addr_line_1'] ?? '' ) ),
            'addr_line_2'             => sanitize_text_field( wp_unslash( $data['addr_line_2'] ?? '' ) ),
            'state_address'           => sanitize_text_field( wp_unslash( $data['state_address'] ?? '' ) ),
            'customer_offers'         => ! empty( $data['offered_price'] ) ? wc_clean( $data['offered_price'] ) : [],
            'customer_additional_msg' => sanitize_textarea_field( wp_unslash( $data['customer_additional_msg'] ?? '' ) ),
        ];
    }

    /**
     * Get store information from the quotes.
     *
     * @param array $quotes The quotes.
     *
     * @return array|null The store information or null if not found.
     */
    private function get_store_info( $quotes ): ?array {
        foreach ( $quotes as $quote_item ) {
            if ( empty( $quote_item['product_id'] ) ) {
                continue;
            }

            $quoted_vendor = dokan_get_vendor_by_product( $quote_item['product_id'] );
            if ( $quoted_vendor ) {
                return [
                    'store_id'   => $quoted_vendor->id,
                    'store_name' => $quoted_vendor->get_shop_name(),
                ];
            }
        }

        return null;
    }

    /**
     * Get the expected delivery date from POST data.
     *
     * @since 3.12.3
     *
     * @param array $data
     *
     * @return int|null The timestamp of the expected delivery date or null if not provided.
     */
    private function get_expected_delivery_date( $data ): ?int {
        if ( ! empty( $data['expected_delivery_date'] ) ) {
            $expected_date = sanitize_text_field( wp_unslash( $data['expected_delivery_date'] ) );
            return dokan_current_datetime()->modify( $expected_date )->getTimestamp();
        }

        return null;
    }

    /**
     * Save quote details.
     *
     * @since 3.12.3
     *
     * @param int   $quote_id The quote ID.
     * @param array $quotes The quotes.
     *
     * @return void
     */
    private function save_quote_details( $quote_id, $quotes ) {
        $quote_details = [ 'quote_id' => $quote_id ];

        foreach ( $quotes as $quote ) {
            $quote_details['product_id']  = $quote['product_id'];
            $quote_details['quantity']    = $quote['quantity'];
            $quote_details['offer_price'] = $quote['offered_price'];

            Helper::create_request_quote_details( $quote_details );
        }
    }

    /**
     * Dokan update quote.
     *
     * @since 3.6.0
     *
     * @param array $data
     *
     * @return void | bool | WP_Error
     */
    public function dokan_update_quote( $data ) {
        if ( ! $this->verify_nonce( $data, 'dokan_quote_nonce', 'save_dokan_quote_action' ) ) {
            return;
        }

        $quote_id    = ! empty( $data['approved_by_vendor_button'] ) ? sanitize_text_field( wp_unslash( $data['approved_by_vendor_button'] ) ) : 0;
        $offer_price = ! empty( $data['offer_price'] ) ? array_map( 'floatval', wp_unslash( $data['offer_price'] ) ) : [];
        $quote_qty   = ! empty( $data['quote_qty'] ) ? array_map( 'absint', wp_unslash( $data['quote_qty'] ) ) : [];

        if ( empty( $offer_price ) || min( $offer_price ) <= 0 ) {
            return new WP_Error( 'error', __( 'Please enter a valid offer price.', 'dokan' ) );
        }

        if ( empty( $quote_qty ) || min( $quote_qty ) <= 0 ) {
            return new WP_Error( 'error', __( 'Please enter a valid quantity.', 'dokan' ) );
        }

        $converted_by      = empty( $data['updated_by'] ) ? 'Admin' : sanitize_text_field( wp_unslash( $data['updated_by'] ) );
        $old_quote_details = Helper::get_request_quote_details_by_quote_id( $quote_id );
        Helper::update_dokan_request_quote_converted( $quote_id, $converted_by );
        Helper::delete( 'quote_details', $quote_id, 'quote_id', true );
        $quote_details['quote_id'] = $quote_id;

        foreach ( $offer_price as $key => $price ) {
            $quote_details['product_id']  = $key;
            $quote_details['quantity']    = $quote_qty[ $key ];
            $quote_details['offer_price'] = $price;
            Helper::create_request_quote_details( $quote_details );
        }

        $new_quote_details = Helper::get_request_quote_details_by_quote_id( $quote_id );
        if ( empty( $new_quote_details ) ) {
            Helper::delete( 'quotes', $quote_id, 'id', true );
        }

        WC_Emails::instance();
        do_action( 'after_dokan_request_quote_updated', $quote_id, $old_quote_details, $new_quote_details );

        return true;
    }

    /**
     * Convert to order.
     *
     * @since 3.6.0
     *
     * @param $quote_id
     * @param $converted_by
     *
     * @throws Exception
     * @return int | WP_Error
     */
    public function convert_to_order( $quote_id, $converted_by ) {
        if ( empty( $quote_id ) ) {
            return new WP_Error( 'no_quote_found', __( 'No quote found', 'dokan' ), [ 'status' => 404 ] );
        }

        $quote         = (object) Helper::get_request_quote_by_id( $quote_id );
        $quote_details = Helper::get_request_quote_details_by_quote_id( $quote_id );

        $order_id = Helper::convert_quote_to_order( $quote, $quote_details );

        Helper::change_status( 'dokan_request_quotes', $quote_id, Quote::STATUS_CONVERTED );
        Helper::update_dokan_request_quote_converted( $quote_id, $converted_by, $order_id );

        return $order_id;
    }

    /**
     * Get pagination.
     *
     * @since 3.6.0
     *
     * @param int | string $total_page
     * @param int | string $page_no
     *
     * @return string
     */
    public static function get_pagination( $total_page, $page_no ): string {
        $pagination_html = '';
        if ( $total_page > 1 ) {
            $pagination_html = '<div class="pagination-wrap">';
            $page_links      = paginate_links(
                [
                    'base'      => add_query_arg( 'page_no', '%#%' ),
                    'format'    => '',
                    'type'      => 'array',
                    'prev_text' => __( '&laquo; Previous', 'dokan' ),
                    'next_text' => __( 'Next &raquo;', 'dokan' ),
                    'total'     => $total_page,
                    'current'   => $page_no,
                ]
            );
            $pagination_html .= '<ul class="pagination"><li>';
            $pagination_html .= join( "</li>\n\t<li>", $page_links );
            $pagination_html .= "</li>\n</ul>\n";
            $pagination_html .= '</div>';
        }

        return $pagination_html;
    }
}
