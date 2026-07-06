<?php

namespace WeDevs\DokanPro\Modules\SellerVacation\SettingsApi;

defined( 'ABSPATH' ) || exit;

/**
 * Store settings for SellerVacation.
 *
 * @since 3.7.13
 */
class Store {

    /**
     * Constructor
     *
     * @since 3.7.13
     */
    public function __construct() {
        add_filter( 'dokan_vendor_settings_api_store_weekly_timing_card', [ $this, 'add_store_vacation_api_to_store_weekly_timing_card' ] );
    }

    /**
     * Add store vacation to store weekly timing card.
     *
     * @since 3.7.13
     *
     * @param array $store_weekly_timing_card array of settings.
     *
     * @return array
     */
    public function add_store_vacation_api_to_store_weekly_timing_card( $store_weekly_timing_card ): array {
        $store_weekly_timing_card[] = [
            'id'        => 'setting_go_vacation',
            'title'     => __( 'Store Vacation', 'dokan' ),
            'desc'      => __( 'Seller can close their stores temporarily and give a message to customers', 'dokan' ),
            'info'      => [
                [
                    'text' => __( 'Docs', 'dokan' ),
                    'url'  => 'https://dokan.co/docs/wordpress/modules/dokan-vendor-vacation/',
                    'icon' => 'dokan-icon-doc',
                ],
            ],
            'help'      => [
                __( 'Store will not show any Products. There will be a banner text with disclaimer message.', 'dokan' ),
                __( 'Your store will show all products in catalog view. But, customers will be able to place any orders.', 'dokan' ),
                __( 'Customers can easily find your store name, logo by searching.', 'dokan' ),
                __( 'You will be able to resume anytime.', 'dokan' ),
            ],
            'icon'      => '',
            'type'      => 'checkbox',
            'default'   => 'no',
            'options'   => [
                'yes' => __( 'Yes', 'dokan' ),
                'no'  => __( 'No', 'dokan' ),
            ],
            'parent_id' => 'store',
            'tab'       => 'store_details',
            'card'      => 'store_weekly_timing',
        ];
        $store_weekly_timing_card[] = [
            'id'        => 'settings_closing_style',
            'title'     => __( 'Choose Closing Time', 'dokan' ),
            'desc'      => '',
            'info'      => [],
            'icon'      => '',
            'type'      => 'radio',
            'default'   => 'instantly',
            'options'   => [
                'instantly' => __( 'Instantly', 'dokan' ),
                'datewise'  => __( 'Date Wise', 'dokan' ),
            ],
            'parent_id' => 'store',
            'tab'       => 'store_details',
            'card'      => 'store_weekly_timing',
        ];
        $store_weekly_timing_card[] = [
            'id'        => 'seller_vacation_schedules',
            'title'     => __( 'Select Time Period', 'dokan' ),
            'desc'      => '',
            'info'      => [],
            'icon'      => '',
            'type'      => 'vacation_schedules',
            'parent_id' => 'store',
            'tab'       => 'store_details',
            'card'      => 'store_weekly_timing',
        ];
        $store_weekly_timing_card[] = [
            'id'        => 'setting_vacation_message',
            'title'     => __( 'Vacation Message', 'dokan' ),
            'desc'      => __( 'Write your vacation message to display on your store while you are away', 'dokan' ),
            'info'      => [],
            'icon'      => '',
            'type'      => 'textarea',
            'default'   => '',
            'parent_id' => 'store',
            'tab'       => 'store_details',
            'card'      => 'store_weekly_timing',
        ];

        return $store_weekly_timing_card;
    }
}
