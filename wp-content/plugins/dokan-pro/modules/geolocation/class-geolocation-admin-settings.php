<?php

/**
 * Dokan Geolocation Admin Settings
 *
 * @since 1.0.0
 */
class Dokan_Geolocation_Admin_Settings {

    /**
     * Class constructor
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function __construct() {
        add_filter( 'dokan_settings_sections', array( $this, 'add_settings_section' ) );
        add_filter( 'dokan_settings_fields', array( $this, 'add_settings_fields' ) );
    }

    /**
     * Add admin settings section
     *
     * @since 1.0.0
     *
     * @param array $sections
     *
     * @return array
     */
    public function add_settings_section( $sections ) {
        $sections['dokan_geolocation'] = [
            'id'                   => 'dokan_geolocation',
            'title'                => __( 'Geolocation', 'dokan' ),
            'icon_url'             => DOKAN_GEOLOCATION_ASSETS . '/images/geolocation.svg',
            'description'          => __( 'Store Location Setup', 'dokan' ),
            'document_link'        => 'https://dokan.co/docs/wordpress/modules/dokan-geolocation/',
            'settings_title'       => __( 'Geolocation Settings', 'dokan' ),
            'settings_description' => __( 'You can configure your store location settings and access configuration for vendor store from this settings menu.', 'dokan' ),
        ];

        return $sections;
    }

    /**
     * Add admin settings fields
     *
     * @since 1.0.0
     *
     * @param array $settings_fields
     *
     * @return array
     */
    public function add_settings_fields( $settings_fields ) {
        $settings_fields['dokan_geolocation'] = [
            'show_locations_map' => [
                'name'    => 'show_locations_map',
                'label'   => __( 'Location Map Position', 'dokan' ),
                'type'    => 'radio',
                'default' => 'top',
                'tooltip' => __( 'Choose where to place the Location Map of your store.', 'dokan' ),
                'options' => [
                    'top'   => __( 'Top', 'dokan' ),
                    'left'  => __( 'Left', 'dokan' ),
                    'right' => __( 'Right', 'dokan' ),
                ],
            ],
            'show_location_map_pages' => [
                'name'    => 'show_location_map_pages',
                'label'   => __( 'Show Map', 'dokan' ),
                'desc'    => __( 'Select where want to show the map only', 'dokan' ),
                'type'    => 'radio',
                'default' => 'all',
                'tooltip' => __( 'Select which pages to display the store map.', 'dokan' ),
                'options' => [
                    'all'           => __( 'Both', 'dokan' ),
                    'store_listing' => __( 'Store Listing', 'dokan' ),
                    'shop'          => __( 'Shop Page', 'dokan' ),
                ],
            ],
            'show_filters_before_locations_map' => [
                'name'    => 'show_filters_before_locations_map',
                'label'   => __( 'Show Filters Before Location Map', 'dokan' ),
                'desc'    => __( 'Yes', 'dokan' ),
                'type'    => 'switcher',
                'default' => 'on',
            ],
            'show_product_location_in_wc_tab' => [
                'name'    => 'show_product_location_in_wc_tab',
                'label'   => __( 'Product Location Tab', 'dokan' ),
                'desc'    => __( 'Show location tab in single product page', 'dokan' ),
                'type'    => 'switcher',
                'default' => 'on',
            ],
            'distance_unit' => [
                'name'    => 'distance_unit',
                'label'   => __( 'Radius Search - Unit', 'dokan' ),
                'type'    => 'radio',
                'default' => 'km',
                'tooltip' => __( 'Set the unit measurement for map radius.', 'dokan' ),
                'options' => [
                    'km'    => __( 'Kilometers', 'dokan' ),
                    'miles' => __( 'Miles', 'dokan' ),
                ],
            ],
            'distance_min' => [
                'name'    => 'distance_min',
                'label'   => __( 'Radius Search - Minimum Distance', 'dokan' ),
                'desc'    => __( 'Set minimum distance for radius search.', 'dokan' ),
                'type'    => 'number',
                'min'     => 0,
                'default' => 0,
                'tooltip' => __( 'Set the minimum unit distance of the radius.', 'dokan' ),
            ],
            'distance_max' => [
                'name'    => 'distance_max',
                'label'   => __( 'Radius Search - Maximum Distance', 'dokan' ),
                'desc'    => __( 'Set maximum distance for radius search.', 'dokan' ),
                'type'    => 'number',
                'min'     => 1,
                'default' => 10,
                'tooltip' => __( 'Set the maximum unit distance of the radius.', 'dokan' ),
            ],
            'map_zoom'     => [
                'name'          => 'map_zoom',
                'label'         => __( 'Map Zoom Level', 'dokan' ),
                'desc'          => __( 'To zoom in increase the number, to zoom out decrease the number.', 'dokan' ),
                'type'          => 'number',
                'min'           => 1,
                'max'           => 18,
                'default'       => 11,
            ],
            'location' => [
                'name'    => 'location',
                'label'   => __( 'Default Location', 'dokan' ),
                'desc'    => __( 'In case the searched store is not found, the default location will be set on the map.', 'dokan' ),
                'type'    => 'gmap',
                'default' => [
                    'latitude'  => 23.709921,
                    'longitude' => 90.40714300000002,
                    'address'   => __( 'Dhaka', 'dokan' ),
                    'zoom'      => 10,
                ],
            ],
        ];

        return $settings_fields;
    }
}
