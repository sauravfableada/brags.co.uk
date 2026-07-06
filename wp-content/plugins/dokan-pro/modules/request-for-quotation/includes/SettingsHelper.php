<?php

namespace WeDevs\DokanPro\Modules\RequestForQuotation;

class SettingsHelper {

    /**
     * Get enable out of stock settings.
     *
     * @since 3.6.0
     *
     * @return bool
     */
    public static function is_out_of_stock_enabled() {
        return 'on' === dokan_get_option( 'enable_out_of_stock', 'dokan_quote_settings', 'on' );
    }

    /**
     * Get ajax add to quote settings.
     *
     * @since 3.6.0
     *
     * @return bool
     */
    public static function is_ajax_add_to_quote_enabled() {
        return 'on' === dokan_get_option( 'enable_ajax_add_to_quote', 'dokan_quote_settings', 'on' );
    }

    /**
     * Get redirect to quote page settings.
     *
     * @since 3.6.0
     *
     * @return bool
     */
    public static function is_redirect_to_quote_page() {
        return 'on' === dokan_get_option( 'redirect_to_quote_page', 'dokan_quote_settings', 'off' );
    }

    /**
     * Get increase offer price settings.
     *
     * @since 3.6.0
     *
     * @return float
     */
    public static function decrease_offered_price() {
        return (float) ( -1 * abs( (float) dokan_get_option( 'decrease_offered_price', 'dokan_quote_settings', 0 ) ) );
    }

    /**
     * Get enable convert to order settings.
     *
     * @since 3.6.0
     *
     * @return bool
     */
    public static function is_convert_to_order_enabled() {
        return 'on' === dokan_get_option( 'enable_convert_to_order', 'dokan_quote_settings', 'off' );
    }

    /**
     * Get enable quote converter display settings.
     *
     * @since 3.6.0
     *
     * @return bool
     */
    public static function is_quote_converter_display_enabled() {
        return 'on' === dokan_get_option( 'enable_quote_converter_display', 'dokan_quote_settings', 'off' );
    }
}
