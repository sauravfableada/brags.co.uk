<?php

namespace WeDevs\DokanPro\Dashboard\ManualOrders;

use WP_Roles;

/**
 * Class Capabilities
 *
 * Handles capability assignment for manual orders functionality
 *
 * @since 4.0.0
 */
class Capabilities {
    /**
     * Constructor.
     *
     * Sets up hooks for capabilities.
     */
    public function __construct() {
        add_action( 'dokan_after_saving_settings', [ $this, 'assign_vendor_capabilities' ], 10, 3 );
        add_filter( 'dokan_get_all_cap', [ $this, 'add_capabilities' ] );
    }

    /**
     * Assigns vendor capabilities related to orders.
     *
     * @param string $option_name  The name of the option being saved.
     * @param mixed  $options The value of the option being saved.
     * @param array  $old_options   The old options before saving.
     *
     * @since 4.0.0
     *
     * @return void
     */
    public function assign_vendor_capabilities( string $option_name, $options, $old_options ) {
        if ( $option_name !== 'dokan_selling' ) {
            return;
        }
        $new_value = $options['allow_vendor_create_manual_order'] ?? 'off';
        $old_value = $old_options['allow_vendor_create_manual_order'] ?? 'off';
        // If the value hasn't changed, no need to proceed
        if ( $old_value === 'on' || $new_value !== 'on' ) {
			return;
		}

        global $wp_roles;

        if ( class_exists( 'WP_Roles' ) && ! isset( $wp_roles ) ) {
            $wp_roles = new WP_Roles(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        }

        $wp_roles->add_cap( 'seller', 'dokan_manage_manual_order' );
        $wp_roles->add_cap( 'administrator', 'dokan_manage_manual_order' );
        $wp_roles->add_cap( 'shop_manager', 'dokan_manage_manual_order' );

        $this->flush_rewrite_rules();
    }

    /**
     * Adds custom capabilities to the list of all capabilities.
     *
     * @since 4.0.0
     *
     * @param array $capabilities Existing capabilities.
     *
     * @return array Modified capabilities.
     */
    public function add_capabilities( array $capabilities ): array {
        // Check if order capabilities exist, if not create an empty array
        if ( ! isset( $capabilities['order'] ) ) {
            $capabilities['order'] = [];
        }

        // Create the capability array we want to add
        $manual_order_cap = [
            'dokan_manage_manual_order' => esc_html__( 'Manage manual order', 'dokan' ),
        ];

        // Add our capability after dokan_manage_order
        $capabilities['order'] = dokan_array_after( $capabilities['order'], 'dokan_manage_order', $manual_order_cap );

        return $capabilities;
    }

    /**
     * Flushes rewrite rules.
     *
     * @since 4.0.0
     *
     * @return void
     */
    private function flush_rewrite_rules() {
        dokan()->rewrite->register_rule();
        flush_rewrite_rules();
    }
}
