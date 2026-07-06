<?php

namespace WeDevs\DokanPro\Modules\VendorAnalytics;

use Automattic\WooCommerce\Utilities\NumberUtil;

defined( 'ABSPATH' ) || exit();

/**
 * Analytics output formatter.
 *
 * @since 3.7.23
 */
class Formatter {

    /**
     * Format as percentage.
     *
     * @since 3.7.23
     *
     * @param mixed $value Value to be formatted.
     *
     * @return string
     */
    public function percentage( $value ): string {
        if ( is_numeric( $value) ) {
            $value = $value * 100;
            $value = number_format_i18n( $value, 2 );
        }

        return "{$value}%";
    }


    /**
     * Format as round.
     *
     * @since 3.7.23
     *
     * @param mixed $value Value to be formatted.
     *
     * @return string
     */
    public function round( $value ): string {
        if ( is_numeric( $value) ) {
            $value = number_format_i18n( NumberUtil::round( $value, 2 ), 2 );
        }

        return strval( $value );
    }
}
