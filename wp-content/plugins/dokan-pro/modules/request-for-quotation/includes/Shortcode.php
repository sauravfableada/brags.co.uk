<?php
namespace WeDevs\DokanPro\Modules\RequestForQuotation;

class Shortcode {

    /**
     * Class construct.
     */
    public function __construct() {
        add_shortcode( 'dokan-request-quote', [ $this, 'render_shortcode' ] );
    }

    /**
     * Render [dokan-request-quote] shortcode.
     *
     * @since 3.6.0
     *
     * @return false|string
     */
    public function render_shortcode() {
        $quoted_vendor = '';
        $quote_session = Session::init();
        $quotes        = $quote_session->get( DOKAN_SESSION_QUOTE_KEY );
        $quote_totals  = [
            '_subtotal'      => 0,
            '_offered_total' => 0,
            '_tax_total'     => 0,
            '_total'         => 0,
        ];

        if ( ! empty( $quotes ) ) {
            foreach ( $quotes as $quote_item_key => $quote_item ) {
                if ( isset( $quote_item['quantity'] ) && empty( $quote_item['quantity'] ) ) {
                    unset( $quotes[ $quote_item_key ] );
                }

                if ( ! isset( $quote_item['data'] ) ) {
                    unset( $quotes[ $quote_item_key ] );
                }

                if ( $quoted_vendor === '' && ! empty( $quote_item['product_id'] ) ) {
                    $quoted_vendor = dokan_get_vendor_by_product( $quote_item['product_id'] )->get_shop_name(); // Retrieve the quoted store name.
                }
            }

            $quote_totals = ( new Helper() )->get_calculated_totals( $quote_session->get( DOKAN_SESSION_QUOTE_KEY ) );
        }

        $enable_hide_price = Helper::enable_quote_hide_price_rule();

        ob_start();
        dokan_get_template_part(
            'dokan-request-quote-shortcode-page', '', [
                'request_quote_shortcode' => true,
                'quotes'                  => $quotes,
                'quote_totals'            => $quote_totals,
                'store_name'              => $quoted_vendor,
                'applicable_quote_rule'   => $enable_hide_price,
            ]
        );

        return ob_get_clean();
    }
}
