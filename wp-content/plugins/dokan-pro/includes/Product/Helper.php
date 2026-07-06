<?php
namespace WeDevs\DokanPro\Product;

defined( 'ABSPATH' ) || exit;


/**
 * Dokan Product Form Helper Class
 *
 * @package WeDevs\DokanPro\Modules\ProductEditor
 *
 * @since 5.0.0
 */
class Helper {

    /**
     * Get the label for a field option by its value.
     *
     * @since 5.0.0
     *
     * @param array  $options Array of options, each with 'label' and 'value' keys.
     * @param string $value   The value to look up.
     *
     * @return string The label if found, otherwise the raw value.
     */
    public static function get_field_option_label_by_value( array $options, $value ): string {
        foreach ( $options as $option ) {
            if ( isset( $option['value'] ) && (string) $option['value'] === (string) $value ) {
                return $option['label'] ?? (string) $value;
            }
        }

        return (string) $value;
    }

    /**
     * Get comma-separated labels for multiple option values.
     *
     * @since 5.0.0
     *
     * @param array $options Array of options, each with 'label' and 'value' keys.
     * @param array $values  The values to look up.
     *
     * @return string Comma-separated labels.
     */
    public static function get_field_option_labels_by_values( array $options, array $values ): string {
        $labels = [];

        foreach ( $values as $value ) {
            $labels[] = self::get_field_option_label_by_value( $options, $value );
        }

        return implode( ', ', $labels );
    }

    /**
     * Format a date value for display on the frontend.
     *
     * @since 5.0.0
     *
     * @param mixed $value Date value (string or array for date ranges).
     *
     * @return string Formatted date string.
     */
    public static function get_formatted_date_label( $value ): string {
        if ( empty( $value ) ) {
            return '';
        }

        $date_format = get_option( 'date_format', 'F j, Y' );

        // Handle date range (array with 'from' and 'to' keys).
        if ( is_array( $value ) ) {
            $from = ! empty( $value['from'] ) ? date_i18n( $date_format, strtotime( $value['from'] ) ) : '';
            $to   = ! empty( $value['to'] ) ? date_i18n( $date_format, strtotime( $value['to'] ) ) : '';

            if ( $from && $to ) {
                return sprintf( '%s - %s', $from, $to );
            }

            return $from ?? $to;
        }

        $timestamp = strtotime( $value );

        if ( false === $timestamp ) {
            return (string) $value;
        }

        return date_i18n( $date_format, $timestamp );
    }
}
