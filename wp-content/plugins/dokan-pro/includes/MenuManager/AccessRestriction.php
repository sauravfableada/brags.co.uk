<?php

namespace WeDevs\DokanPro\MenuManager;

defined( 'ABSPATH' ) || exit;

use WP_Error;

/**
 * Access restriction for disabled menus
 *
 * @since 3.10.0
 */
class AccessRestriction {

    /**
     * List of restricted keys
     *
     * @since 3.10.0
     *
     * @var array
     */
    protected static $restricted_keys = [];

    /**
     * Alters Query Params to restrict access
     *
     * @since 3.10.0
     *
     * @param array $query_vars
     *
     * @return array|WP_Error
     */
    public static function restrict_access( array $query_vars ) {
        if ( dokan_is_seller_dashboard() ) {
            if ( ! empty( $query_vars['settings'] ) ) {
                $pagename = [ $query_vars['settings'] ];
            } else {
                $pagename = array_diff( array_keys( $query_vars ), [ 'pagename' ] );
            }
            $restricted_key = current( array_intersect( $pagename, self::get_restricted_keys() ) );
            if ( ! empty( $restricted_key ) ) {
                if ( ! empty( $query_vars['settings'] ) ) {
                    unset( $query_vars['settings'] );
                } else {
                    unset( $query_vars[ $restricted_key ] );
                }
                return new WP_Error( 'action-restricted', __( 'You have no permission to view this page', 'dokan' ) );
            }
        }
        return $query_vars;
    }

    /**
     * Get a list of restricted keys
     *
     * @since 3.10.0
     *
     * @return array
     */
    public static function get_restricted_keys(): array {

        if ( ! empty( self::$restricted_keys ) ) {
            return self::$restricted_keys;
        }

        $menu_manager_settings = dokan_get_option( Constants::MENU_MANAGER_OPTIONS, Constants::DOKAN_MENU_MANAGER, [] );

        // For initial state when no option is available
        if ( empty( $menu_manager_settings ) ) {
            return [];
        }

        $left_menus = $menu_manager_settings['left_menus'];
        $settings_sub_menu = $menu_manager_settings['settings_sub_menu'];
        $restricted_keys = [];
        foreach ( $left_menus as $key => $menu_item ) {
            if ( wc_string_to_bool( $menu_item['is_switched_on'] ) === false ) {
                $restricted_keys[] = $key;
            }
        }
        foreach ( $settings_sub_menu as $menu_item ) {
            if ( wc_string_to_bool( $menu_item['is_switched_on'] ) === false ) {
                $restricted_keys[] = $menu_item['menu_key'];
            }
        }
        self::$restricted_keys = self::addRestrictedKeyAliases( $restricted_keys );

        return self::$restricted_keys;
    }

    /**
     * Adds aliases to restricted key list
     *
     * @since 3.10.0
     *
     * @param array $restricted_keys
     *
     * @return array
     */
    protected static function addRestrictedKeyAliases( array $restricted_keys ): array {
        $aliases_list = [
            'announcement' => [ 'single-announcement' ],
            'shipping' => [ 'regular-shipping' ],
            'withdraw' => [ 'withdraw-requests' ],
            'auction' => [ 'auction-activity' ],
            'payment' => [
                'payment-manage-paypal',
                'payment-manage-bank',
                'payment-manage-dokan-stripe-connect',
                'payment-manage-dokan_mangopay',
                'payment-manage-dokan_razorpay',
                'payment-manage-skrill',
                'payment-manage-dokan_custom',
            ],
        ];

        foreach ( $aliases_list as $key => $value ) {
            if ( in_array( $key, $restricted_keys, true ) ) {
                $restricted_keys = array_merge( $restricted_keys, $value );
            }
        }

        return $restricted_keys;
    }

    /**
     * Adds widget restriction
     *
     * @since 3.10.0 Added widget restriction for lite and pro plugin.
     *
     * @param bool $status
     * @param string $widget_id
     *
     * @return bool
     */
    public static function validateWidgetRestriction( bool $status, string $widget_id ): bool {
        if ( in_array( $widget_id, self::get_restricted_keys(), true ) ) {
            return false;
        }
        return $status;
    }

}
