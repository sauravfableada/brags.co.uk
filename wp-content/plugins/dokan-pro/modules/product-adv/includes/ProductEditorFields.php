<?php

namespace WeDevs\DokanPro\Modules\ProductAdvertisement;

use WeDevs\Dokan\ProductEditor\Elements;

defined( 'ABSPATH' ) || exit;

/**
 * Class Product Editor Fields.
 *
 * Registers product advertisement checkbox field in the product editor form.
 *
 * @since 5.0.0
 */
class ProductEditorFields {

    public const SECTION_ADVERTISEMENT  = 'product_advertisement_section';
    public const ADVERTISE_THIS_PRODUCT = 'dokan_advertise_this_product';

    /**
     * Class Constructor.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function __construct() {
        add_filter( 'dokan_product_editor_schema', [ $this, 'extend_default_fields' ], 10, 2 );
        add_filter( 'dokan_product_editor_layouts', [ $this, 'extend_layouts' ] );
        // Enqueue product editor scripts
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_product_editor_scripts' ] );
    }

    /**
     * Build dynamic checkbox label with advertisement details.
     *
     * @since 5.0.0
     *
     * @return string
     */
    private function build_checkbox_label(): string {
        $expire_days    = Helper::get_expire_after_days();
        $cost           = Helper::get_advertisement_cost();
        $remaining_slot = Helper::get_available_advertisement_slot_count();

        $days_text      = Helper::format_expire_after_days_text( $expire_days );
        $cost_text      = wp_strip_all_tags( wc_price( $cost ) );
        $remaining_text = Helper::get_formatted_remaining_slot_count( $remaining_slot );

        return sprintf(
            /* translators: 1: advertisement duration, 2: advertisement cost, 3: remaining slot count */
            __( 'Advertise this product for: %1$s, Advertisement Cost: %2$s, Remaining slot: %3$s', 'dokan' ),
            $days_text,
            $cost_text,
            $remaining_text
        );
    }

    /**
     * Extend default fields with advertisement section and checkbox.
     *
     * @since 5.0.0
     *
     * @param array $fields Flat sections + fields.
     *
     * @return array
     */
    public function extend_default_fields( array $fields, $product_id ): array {
        if ( ! Helper::is_per_product_advertisement_enabled() && ! Helper::is_enabled_for_vendor_subscription() ) {
            return $fields;
        }

        $advertisement_data = Helper::get_advertisement_data_by_product( $product_id );
        $already_advertised = ! empty( $advertisement_data['already_advertised'] );
        $is_published       = isset( $advertisement_data['post_status'] ) && 'publish' === $advertisement_data['post_status'];

        if ( $already_advertised ) {
            $label = sprintf(
                /* translators: %s: advertisement expiration date */
                __( 'Product advertisement is currently ongoing. Advertisement will end on: <strong>%s</strong>', 'dokan' ),
                esc_html( $advertisement_data['expire_date'] ?? '' )
            );
        } elseif ( ! $is_published ) {
            $label = __( 'You can not advertise this product. Product needs to be published before you can advertise.', 'dokan' );
        } else {
            $label = $this->build_checkbox_label();
        }

        $fields[] = [
            'id'          => self::SECTION_ADVERTISEMENT,
            'type'        => 'section',
            'label'       => __( 'Advertisement', 'dokan' ),
            'description' => __( 'Advertise your product to get more visibility', 'dokan' ),
            'visibility'  => true,
            'priority'    => 90,
        ];

        $fields[] = [
            'id'         => self::ADVERTISE_THIS_PRODUCT,
            'section_id' => self::SECTION_ADVERTISEMENT,
            'type'       => 'field',
            'variant'    => 'checkbox',
            'label'      => $label,
            'value'      => $already_advertised,
            'required'   => false,
            'visibility' => true,
            'disabled'   => $already_advertised || ! $is_published,
            'show_in_admin' => false,
        ];

        return $fields;
    }

    /**
     * Add advertisement section layout to the product editor form layout.
     *
     * @since 5.0.0
     *
     * @param array $layouts Flat layout items.
     *
     * @return array
     */
    public function extend_layouts( array $layouts ): array {
        $layouts[] = [
            'id'        => self::SECTION_ADVERTISEMENT,
            'parent_id' => Elements::PRIMARY_COLUMN,
            'priority'  => 90,
            'layout'    => [
                'type'       => 'card',
                'withHeader' => true,
            ],
        ];

        return $layouts;
    }

    /**
     * Enqueue product editor vendor dashboard scripts.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function enqueue_product_editor_scripts() {
        if ( ! dokan_is_seller_dashboard() ) {
            return;
        }
        $assets_file = DOKAN_PRODUCT_ADVERTISEMENT_DIR . '/assets/js/vendor-dashboard.asset.php';

        if ( ! file_exists( $assets_file ) ) {
            return;
        }

        $assets = require $assets_file;

        $deps = array_filter(
            $assets['dependencies'],
            function ( $handle ) {
                return wp_script_is( $handle, 'registered' );
            }
        );

        wp_enqueue_script(
            'dokan-product-adv-vendor-dashboard',
            DOKAN_PRODUCT_ADVERTISEMENT_ASSETS . '/js/vendor-dashboard.js',
            $deps,
            $assets['version'],
            true
        );

        wp_localize_script(
            'dokan-product-adv-vendor-dashboard',
            'dokanProductAdv',
            [
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'dokan_advertise_product_nonce' ),
            ]
        );
    }
}
