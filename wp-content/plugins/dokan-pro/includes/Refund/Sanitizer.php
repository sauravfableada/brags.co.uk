<?php

namespace WeDevs\DokanPro\Refund;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Sanitizer {

    /**
     * Sanitize amount
     *
     * @since 3.0.0
     *
     * @param string $amount
     *
     * @return string
     */
    protected static function sanitize_amount( $amount ) {
        return wc_format_decimal( sanitize_text_field( $amount ), '' );
    }

    /**
     * Sanitize id
     *
     * @since 3.0.0
     *
     * @param int $id
     *
     * @return int
     */
    public static function sanitize_id( $id ) {
        return absint( $id );
    }

    /**
     * Sanitize order_id
     *
     * @since 3.0.0
     *
     * @param int $order_id
     *
     * @return int
     */
    public static function sanitize_order_id( $order_id ) {
        return absint( $order_id );
    }

    /**
     * Sanitize refund_amount
     *
     * @since 3.0.0
     *
     * @param string $refund_amount
     *
     * @return string
     */
    public static function sanitize_refund_amount( $refund_amount ) {
        return self::sanitize_amount( $refund_amount );
    }

    /**
     * Sanitize refund_reason
     *
     * @since 3.0.0
     *
     * @param string $refund_reason
     *
     * @return string
     */
    public static function sanitize_refund_reason( $refund_reason ) {
        return sanitize_text_field( $refund_reason );
    }

    /**
     * Sanitize item_qtys
     *
     * @since 3.0.0
     *
     * @param string|array $item_qtys
     *
     * @return array
     */
    public static function sanitize_item_qtys( $item_qtys ) {
        $item_qtys = $item_qtys ?? [];
        $item_qtys = is_array( $item_qtys ) ? $item_qtys : json_decode( $item_qtys, true );

        $sanitized_qtys = [];

        foreach ( $item_qtys as $item_id => $item_qty ) {
            $item_qty = absint( $item_qty );

            if ( ! $item_qty ) {
                continue;
            }

            $sanitized_qtys[ absint( $item_id ) ] = $item_qty;
        }

        $item_qtys = $sanitized_qtys;

        return $item_qtys;
    }

    /**
     * Sanitize item_totals
     *
     * @since 3.0.0
     *
     * @param string|array $item_totals
     *
     * @return array
     */
    public static function sanitize_item_totals( $item_totals ) {
        $item_totals = $item_totals ?? [];
        $item_totals = is_array( $item_totals ) ? $item_totals : json_decode( $item_totals, true );

        $sanitize_totals = [];

        foreach ( $item_totals as $item_id => $item_total ) {
            $sanitize_totals[ absint( $item_id ) ] = self::sanitize_amount( $item_total );
        }

        $item_totals = $sanitize_totals;

        return $item_totals;
    }

    /**
     * Sanitize item_tax_totals
     *
     * @since 3.0.0
     *
     * @param mixed $item_tax_totals
     *
     * @return array
     */
    public static function sanitize_item_tax_totals( $item_tax_totals ) {
        return is_array( $item_tax_totals ) ? $item_tax_totals : json_decode( $item_tax_totals, true );
    }

    /**
     * Sanitize restock_items
     *
     * @since 3.0.0
     *
     * @param mixed $restock_items
     *
     * @return array|null|mixed
     */
    public static function sanitize_restock_items( $restock_items ) {
        if ( is_array( $restock_items ) ) {
            return $restock_items;
        } elseif ( ! empty( $restock_items ) ) {
            return json_decode( $restock_items, true );
        } else {
            return '';
        }
    }

    /**
     * Sanitize date
     *
     * @since 3.0.0
     *
     * @param string $date
     *
     * @return string
     */
    public static function sanitize_date( $date ) {
        return mysql2date( 'Y-m-d H:i:s', $date, false );
    }

    /**
     * Sanitize status
     *
     * @since 3.0.0
     *
     * @param string $status
     *
     * @return int
     */
    public static function sanitize_status( $status ) {
        $status = absint( $status );
        return in_array( $status, dokan_pro()->refund->get_status_codes(), true ) ? $status : 0;
    }
}
