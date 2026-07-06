<?php

namespace WeDevs\DokanPro\Modules\RequestForQuotation;

use WeDevs\DokanPro\Modules\RequestForQuotation\Model\Quote;

/**
 * Class for Frontend Ajax integration.
 *
 * @since 3.6.0
 */
class Ajax {

    /**
     * Contains an array of quote items.
     *
     * @var array
     */
    public $quote_contents = [];

    /**
     * Construct for Ajax class.
     */
    public function __construct() {
        add_action( 'wp_ajax_dokan_add_to_quote', [ $this, 'dokan_add_to_quote' ] );
        add_action( 'wp_ajax_nopriv_dokan_add_to_quote', [ $this, 'dokan_add_to_quote' ] );
        add_action( 'wp_ajax_remove_dokan_quote_item', [ $this, 'remove_dokan_quote_item' ] );
        add_action( 'wp_ajax_nopriv_remove_dokan_quote_item', [ $this, 'remove_dokan_quote_item' ] );
        add_action( 'wp_ajax_dokan_update_quote_status', [ $this, 'update_quote_status' ] );
        add_action( 'wp_ajax_nopriv_dokan_update_quote_status', [ $this, 'update_quote_status' ] );
    }

    /**
     * Callback for Dokan_add_to_quote.
     *
     * @since 3.6.0
     *
     * @throws \Exception
     * @return void
     */
    public function dokan_add_to_quote() {
        $quote_session = Session::init();
        if ( empty( $this->quote_contents ) ) {
            $this->quote_contents = (array) $quote_session->get( DOKAN_SESSION_QUOTE_KEY );
            unset( $this->quote_contents[0] );
        }
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'dokan_request_quote_nonce' ) ) {
            wp_send_json(
                [
                    'type'    => 'error',
                    'message' => __( 'Are you cheating?', 'dokan' ),
                ]
            );
        }

        $product_id = isset( $_POST['product_id'] ) ? intval( wp_unslash( $_POST['product_id'] ) ) : 0;
        $quantity   = isset( $_POST['quantity'] ) ? intval( wp_unslash( $_POST['quantity'] ) ) : 1;
        $product_id = isset( $_POST['variation_id'] ) ? intval( wp_unslash( $_POST['variation_id'] ) ) : $product_id;

        $quote_item_key = $this->add_to_quote( $product_id, $quantity );
        $quote_contents = $quote_session->get( DOKAN_SESSION_QUOTE_KEY );
        $product_name   = __( 'Product', 'dokan' );

        if ( ! empty( $quote_contents[ $quote_item_key ] ) && ! empty( $quote_contents[ $quote_item_key ]['data'] ) ) {
            $product = $quote_contents[ $quote_item_key ]['data'];
        } else {
            $product = wc_get_product( $product_id );
        }

        if ( is_object( $product ) ) {
            $product_name = $product->get_name();
        }

        if ( false === $quote_item_key ) {
            /* translators: %s: Product name */
            wc_add_notice( sprintf( __( 'Quote is not available for “%s”', 'dokan' ), $product_name ), 'error' );
        } else {
            if ( SettingsHelper::is_redirect_to_quote_page() ) {
                wp_send_json(
                    [
                        'redirect_to' => esc_url( get_page_link( Helper::get_quote_page_id() ) ),
                    ]
                );
            }

            if ( SettingsHelper::is_ajax_add_to_quote_enabled() ) {
                ob_start();
                ?>
                <a href="<?php echo esc_url( get_page_link( Helper::get_quote_page_id() ) ); ?>" class="added_to_cart added_to_quote wc-forward" title="<?php echo __( 'View Quote', 'dokan' ); ?>"><?php echo __( 'View Quote', 'dokan' ); ?></a>
                <?php
                $view_quote_btn = ob_get_clean();
                wp_send_json(
                    [
                        'view_button' => $view_quote_btn,
                    ]
                );
                /* translators: %s: Product name */
                wc_add_notice( sprintf( __( '“%s” has been added to your dokan quote.', 'dokan' ), $product_name ), 'success' );
            }
        }

        wp_die();
    }

    /**
     * Add a product to the Quote.
     *
     * @since 3.6.0
     *
     * @param int  $product_id      contains the id of the product to add to the quote.
     * @param int  $quantity        contains the quantity of the item to add.
     * @param bool $return_contents If return content.
     *
     * @throws \Exception Plugins can throw an exception to prevent adding to quote.
     * @return string|bool $quote_item_key
     */
    public function add_to_quote( $product_id = 0, $quantity = 1, $return_contents = false ) {
        try {
            if ( 0 >= intval( $quantity ) ) {
                return false;
            }

            $product_id   = absint( $product_id );
            $product_data = wc_get_product( $product_id );

            if ( ! $product_data || Quote::STATUS_TRASH === $product_data->get_status() ) {
                return false;
            }

            if ( $this->find_another_vendor_in_quote( $product_id ) ) {
                $quote_page_url = esc_url( get_page_link( Helper::get_quote_page_id() ) );
                $message = __( 'You cannot add multiple vendor\'s product to your quote.', 'dokan' );
                wc_add_notice( sprintf( '<a href="%s" class="button wc-forward">%s</a> %s', $quote_page_url, __( 'View Quote', 'dokan' ), $message ), 'error' );
                die();
            }
            // Generate an ID based on product ID, variation ID, variation data, and other quote item data.
            $quote_id = $this->generate_quote_id( $product_id );
            // Find the quote item key in the existing quote.
            $quote_item_key = $this->find_product_in_quote( $quote_id );

            // Force quantity to 1 if sold individually and check for existing item in quote.
            if ( $product_data->is_sold_individually() && ! empty( $quote_item_key ) ) {
                $quote_page_url = esc_url( get_page_link( Helper::get_quote_page_id() ) );
                /* translators: %s: product name */
                $message = sprintf( __( 'You cannot add another "%s" to your quote.', 'dokan' ), $product_data->get_name() );
                $message = apply_filters( 'dokan_quote_product_cannot_add_another_message', $message, $product_data );

                throw new \Exception( sprintf( '<a href="%s" class="button wc-forward">%s</a> %s', $quote_page_url, __( 'View Quote', 'dokan' ), $message ) );
            }

            if ( ! $product_data->is_purchasable() ) {
                $message = __( 'Sorry, this product cannot be purchased.', 'dokan' );
                $message = apply_filters( 'dokan_quote_product_cannot_be_purchased_message', $message, $product_data );
                throw new \Exception( $message );
            }

            // If quote_item_key is set, the item is already in the quote.
            if ( ! empty( $quote_item_key ) ) {
                $this->quote_contents[ $quote_item_key ]['quantity'] += intval( $quantity );
            } else {
                $quote_item_key = $quote_id;

                $incr_offered_price = SettingsHelper::decrease_offered_price();

                $offered_price = $product_data->get_price();
                $args          = [
                    'qty'   => 1,
                    'price' => $offered_price,
                ];
                $offered_price = (float) $this->get_product_price( $product_data, $args, 'edit' );

                if ( ! empty( $incr_offered_price ) ) {
                    $offered_price += ( $incr_offered_price * (float) $product_data->get_price() ) / 100;
                }
                // Add item after merging with $quote_item_data - hook to allow plugins to modify quote item.
                $this->quote_contents[ $quote_item_key ] = [
                    'key'           => $quote_item_key,
                    'product_id'    => $product_id,
                    'quantity'      => $quantity,
                    'offered_price' => $offered_price,
                    'data'          => $product_data,
                ];
            }

            $this->quote_contents = apply_filters( 'dokan_quote_contents_changed', $this->quote_contents );

            if ( $return_contents ) {
                return $this->quote_contents;
            } else {
                $quote_session = Session::init();
                $quote_session->set( DOKAN_SESSION_QUOTE_KEY, $this->quote_contents );
                do_action( 'dokan_quote_session_changed' );
            }

            do_action( 'dokan_add_to_quote', $quote_item_key, $product_id, $quantity );

            return $quote_item_key;
        } catch ( \Exception $e ) {
            if ( $e->getMessage() && ! is_admin() ) {
                wc_add_notice( $e->getMessage(), 'error' );
            }

            return false;
        }
    }

    /**
     * Generate a unique ID for the quote item being added.
     *
     * @since 3.6.0
     *
     * @param int $product_id id of the product the key is being generated for.
     *
     * @return string quote item key
     */
    public function generate_quote_id( $product_id ) {
        $id_parts = [ $product_id ];

        return md5( implode( '_', $id_parts ) );
    }

    /**
     * Check if product is in the quote and return quote item key.
     *
     * Cart item key will be unique based on the item and its properties, such as variations.
     *
     * @since 3.6.0
     *
     * @param bool|string $quote_key
     *
     * @return string|bool|null Quote item key
     */
    public function find_product_in_quote( $quote_key = false ) {
        if ( false !== $quote_key && is_array( $this->quote_contents ) && isset( $this->quote_contents[ $quote_key ] ) ) {
            return $quote_key;
        }

        return '';
    }

    /**
     * Check if another vendor is in the quote and return quote item key.
     *
     * @since 3.6.0
     *
     * @param int $product_id
     *
     * @return bool
     */
    public function find_another_vendor_in_quote( $product_id = 0 ) {
        $quote_session = Session::init();
        $quotes = $quote_session->get( DOKAN_SESSION_QUOTE_KEY );
        if ( 0 !== $product_id && ! empty( $quotes ) ) {
            foreach ( $quotes as $quote ) {
                $quote_product = wc_get_product( $quote['data'] );
                if ( ! $quote_product ) {
                    continue;
                }
                if ( get_post_field( 'post_author', $quote_product->get_id() ) !== get_post_field( 'post_author', $product_id ) ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get the product row price per item.
     *
     * @since 3.6.0
     *
     * @param \WC_Product $product Product object.
     *
     * @return string formatted price
     */
    public function get_product_price( $product, $args = [], $view = 'view' ) {
        if ( 'incl' === $this->get_tax_price_display_mode() ) {
            $product_price = wc_get_price_including_tax( $product, $args );
        } else {
            $product_price = wc_get_price_excluding_tax( $product, $args );
        }

        if ( 'edit' === $view ) {
            return $product_price;
        }

        $price_suffix = 'incl' === $this->get_tax_price_display_mode() ? wc()->countries->inc_tax_or_vat() : '';

        $price_suffix = '<small>' . $price_suffix . '</small>';

        return wc_price( $product_price ) . ' ' . $price_suffix;
    }

    /**
     * Returns 'incl' if tax should be included in cart, otherwise returns 'excl'.
     *
     * @since 3.6.0
     *
     * @return string
     */
    public function get_tax_price_display_mode() {
        if ( WC()->customer->get_is_vat_exempt() ) {
            return 'excl';
        }

        return get_option( 'woocommerce_tax_display_cart' );
    }

    /**
     * Remove dokan quote item.
     *
     * @since 3.6.0
     *
     * @return void
     */
    public function remove_dokan_quote_item() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'dokan_request_quote_nonce' ) ) {
            wp_send_json(
                [
                    'type'    => 'error',
                    'message' => __( 'Are you cheating?', 'dokan' ),
                ]
            );
        }

        $quote_key = isset( $_POST['quote_key'] ) ? sanitize_text_field( wp_unslash( $_POST['quote_key'] ) ) : '';

        if ( empty( $quote_key ) ) {
            die( __( 'Quote key is empty', 'dokan' ) );
        }

        $quote_session = Session::init();
        $quotes = $quote_session->get( DOKAN_SESSION_QUOTE_KEY );

        if ( empty( $quotes ) || empty( $quotes[ $quote_key ]['data'] ) ) {
            die( __( 'Quote is empty', 'dokan' ) );
        }

        $product    = $quotes[ $quote_key ]['data'];
        $hide_price = isset( $_POST['hide_price'] ) ? sanitize_text_field( wp_unslash( $_POST['hide_price'] ) ) : '';

        unset( $quotes[ $quote_key ] );

        $quote_session->set( DOKAN_SESSION_QUOTE_KEY, $quotes );

        if ( empty( $quote_session->get( DOKAN_SESSION_QUOTE_KEY ) ) ) {
            $quote_session->delete( DOKAN_SESSION_QUOTE_KEY );
        }

        ob_start();
        dokan_get_template_part(
            'quote-table', '', [
                'quotes'              => $quotes,
                'hide_price'          => $hide_price,
                'request_quote_table' => true,
            ]
        );
        $quote_table = ob_get_clean();

        /* translators: %s: Product name */
        $message      = sprintf( __( '“%s” has been removed from quote basket.', 'dokan' ), $product->get_name() );
        $message_html = '<div class="woocommerce-message" role="alert">' . $message . '</div>';
        $quote_totals = ( new Helper() )->get_calculated_totals( $quotes );

        ob_start();
        dokan_get_template_part(
            'quote-totals-table', '', [
                'quote_totals'        => $quote_totals,
                'request_quote_table' => true,
            ]
        );
        $quote_totals = ob_get_clean();

        if ( empty( $quote_totals ) ) {
            $quote_totals = '';
        }

        wp_send_json(
            [
                'quote_empty'  => empty( $quote_session->get( DOKAN_SESSION_QUOTE_KEY ) ),
                'quote-table'  => $quote_table,
                'message'      => $message_html,
                'quote-totals' => $quote_totals,
            ]
        );

        die();
    }

    /**
     * Handle quote actions update status.
     *
     * @since 3.12.3
     *
     * @return void
     */
    public function update_quote_status() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'dokan_request_quote_nonce' ) ) {
            wp_send_json(
                [
                    'type'    => 'error',
                    'message' => __( 'Are you cheating?', 'dokan' ),
                ]
            );
        }

        $quote_id    = ! empty( $_POST['quote_id'] ) ? absint( $_POST['quote_id'] ) : '';
        $status      = ! empty( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';
        $success_msg = ! empty( $_POST['success_msg'] ) ? sanitize_text_field( wp_unslash( $_POST['success_msg'] ) ) : '';
        $result      = Helper::change_status( 'dokan_request_quotes', $quote_id, $status );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        if ( ! $result ) {
            wp_send_json_error( __( 'Something went wrong! Your quote could not be updated.', 'dokan' ) );
        }

        $response = [ 'success_msg' => $success_msg ];

        // Handle redirection based on status.
        switch ( $status ) {
            case Quote::STATUS_REJECT:
                $response['redirect_to'] = dokan_get_navigation_url( 'requested-quotes/' . absint( $quote_id ) ); // Redirect url for quotation single page.
                break;
            case Quote::STATUS_CANCEL:
                $response['redirect_to'] = wc_get_endpoint_url( 'request-a-quote', $quote_id, wc_get_page_permalink( 'myaccount' ) ); // Redirect url for my-account quotation page.
                break;
            default:
                break;
        }

        wp_send_json_success( $response );
    }
}
