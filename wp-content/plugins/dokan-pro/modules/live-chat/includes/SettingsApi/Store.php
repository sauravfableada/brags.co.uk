<?php

namespace WeDevs\DokanPro\Modules\LiveChat\SettingsApi;

use WeDevs\DokanPro\Modules\LiveChat\AdminSettings;

defined( 'ABSPATH' ) || exit;

/**
 * Store Settings for LiveChat
 */
class Store {

    /**
     * Constructor function
     *
     * @return void
     */
    public function __construct() {
        add_filter( 'dokan_pro_vendor_settings_api_support_card', [ $this, 'dokan_live_chat_settings_api_support_card' ] );
    }

    /**
     * Livechat settings API support card.
     *
     * @since 3.7.13
     *
     * @param array $support_card Settings.
     *
     * @return array
     */
    public function dokan_live_chat_settings_api_support_card( array $support_card ): array {
        if ( ! AdminSettings::is_enabled() ) {
            return $support_card;
        }
        $provider     = AdminSettings::get_provider();
        $is_messenger = 'messenger' === $provider;
        $is_tawkto    = 'tawkto' === $provider;
        $is_whatsapp  = 'whatsapp' === $provider;
        $support      = [];

        $support[] = [
            'id'        => 'live_chat',
            'title'     => __( 'Enable Live Chat', 'dokan' ),
            'desc'      => __( 'Allow customers to ask queries and interact through Live Chat feature on your store', 'dokan' ),
            'info'      => [],
            'icon'      => '',
            'type'      => 'toggle',
            'default'   => 'yes',
            'options'   => [
                'yes' => __( 'Yes', 'dokan' ),
                'no'  => __( 'No', 'dokan' ),
            ],
            'parent_id' => 'store',
            'tab'       => 'advance',
            'card'      => 'support_card',
        ];

        if ( $is_messenger ) {
            $support[] = [
                'id'        => 'fb_page_id',
                'title'     => __( 'Facebook Page ID', 'dokan' ),
                'desc'      => __( 'Your Facebook Page ID', 'dokan' ),
                'info'      => [
                    [
                        'text' => __( 'Get Facebook page id', 'dokan' ),
                        'url'  => 'https://www.facebook.com/pages/create/',
                        'icon' => '',
                    ],
                    [
                        'text' => __( 'Setup Facebook Messenger Chat', 'dokan' ),
                        'url'  => '',
                        'icon' => '',
                    ],
                    [
                        'text' => __( 'Get Help', 'dokan' ),
                        'url'  => 'https://dokan.co/docs/wordpress/modules/dokan-live-chat/',
                        'icon' => '',
                    ],
                ],
                'icon'      => '',
                'type'      => 'text',
                'parent_id' => 'store',
                'tab'       => 'advance',
                'card'      => 'support_card',
            ];
        }

        if ( $is_tawkto ) {
            $support[] = [
                'id'        => 'tawkto_property_id',
                'title'     => __( 'Tawkto Property ID', 'dokan' ),
                'desc'      => __( 'Tawkto Property ID', 'dokan' ),
                'info'      => [],
                'icon'      => '',
                'type'      => 'text',
                'parent_id' => 'store',
                'tab'       => 'advance',
                'card'      => 'support_card',
            ];
            $support[] = [
                'id'        => 'tawkto_widget_id',
                'title'     => __( 'Tawkto widget ID', 'dokan' ),
                'desc'      => __( 'Tawkto widget ID', 'dokan' ),
                'info'      => [],
                'icon'      => '',
                'type'      => 'text',
                'parent_id' => 'store',
                'tab'       => 'advance',
                'card'      => 'support_card',
            ];
        }
        if ( $is_whatsapp ) {
            $support[] = [
                'id'        => 'whatsapp_number',
                'title'     => __( 'WhatsApp Number', 'dokan' ),
                'desc'      => __( 'Enter "WhatsApp" or "WhatsApp business" number with country code ( E.g. +13214125218 - herein e.g. +1 is country code, 3214125218 is the mobile number )', 'dokan' ),
                'info'      => [],
                'icon'      => '',
                'type'      => 'text',
                'parent_id' => 'store',
                'tab'       => 'advance',
                'card'      => 'support_card',
            ];
        }

        $support = apply_filters( 'dokan_pro_vendor_settings_api_live_chat_setings', $support );
        array_push( $support_card, ...$support );
        return $support_card;
    }
}
