<?php

namespace WeDevs\DokanPro\Modules\Printful\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Admin Settings Class
 *
 * @since 3.13.0
 */
class Settings {

    /**
     * Constructor
     *
     * @since 3.13.0
     *
     * @return void
     */
    public function __construct() {
        add_filter( 'dokan_settings_sections', [ $this, 'add_section' ] );
        add_filter( 'dokan_settings_fields', [ $this, 'add_fields' ] );
    }

    /**
     * Add settings section.
     *
     * @since 3.13.0
     *
     * @param array $sections Dokan settings sections.
     *
     * @return array
     */
    public function add_section( $sections ) {
        $sections['dokan_printful'] = [
            'id'                   => 'dokan_printful',
            'title'                => __( 'Printful', 'dokan' ),
            'icon_url'             => DOKAN_PRINTFUL_ASSETS . '/images/logo.svg',
            'description'          => __( 'Configure Printful', 'dokan' ),
            'document_link'        => 'https://dokan.co/docs/wordpress/modules/printful/',
            'settings_title'       => __( 'Printful Settings', 'dokan' ),
            'settings_description' => __( 'Configure Dokan to give vendors the ability to connect with Printful.', 'dokan' ),
        ];

        return $sections;
    }

    /**
     * Add settings fields.
     *
     * @since 3.13.0
     *
     * @param array $fields dokan settings fields.
     *
     * @return array
     */
    public function add_fields( $fields ) {
        $urlparts              = wp_parse_url( home_url() );
        $domain                = ! empty( $urlparts ) ? $urlparts['host'] : '';
        $redirect_url          = dokan_get_navigation_url( 'settings/printful' );
		$currency_settings_url = admin_url( 'admin.php?page=wc-settings&tab=general#pricing_options-description' );
        $fields_warning        = [];

        if ( ! ( $this->shipping_fee_recipient_is_seller() && $this->shipping_tax_fee_recipient_is_seller() ) ) {
            $fields_warning['shipping_fee_warning'] = [
                'name'              => 'shipping_fee_warning',
                'type'              => 'warning',
                'desc'              => __( 'To enable Prinful correctly, assign Shipping Fee Recipient, and Shipping Tax Fee Recipient to individual vendors. This integration requires that each vendor will manage and fulfill their orders using their Printful account.', 'dokan' ),
                'content_class'     => 'dokan-printful-shipping-warning',
                'scroll_into_view'  => true,
                'scroll_to_label'   => 'Shipping Fee Recipient Settings',
                'scroll_to_section' => 'dokan_selling',
                'scroll_to_field'   => 'shipping_fee_recipient',
            ];
        }

        if ( ! $this->is_printful_supported_currency( get_woocommerce_currency() ) ) {
            $fields_warning['unsupported_currency_warning'] = [
                'name'  => 'unsupported_currency_warning',
                'label' => __( 'Important notice: Unsupported Store Currency', 'dokan' ),
                'type'  => 'warning',
                'desc'              => __( 'To ensure accurate pricing and successful integration, please select one of the Printful supported currencies from <strong>WooCommerce → Settings → General → Currency</strong>, under <strong>Currency Options</strong>. Please select one of these currencies to match Printful: <b>USD, EUR, GBP, CAD, JPY, AUD, BRL, CHF, DKK, HKD, MXN, NZD, SEK</b>.', 'dokan' ),
                'content_class' => 'dokan-printful-currency-warning',
                'external_link' => true,
                'link_text'     => __( 'Currency Settings', 'dokan' ),
                'link_url'      => $currency_settings_url,
            ];
        }

        $fields['dokan_printful'] = [
            'app' => [
                'name'                => 'app',
                'type'                => 'social',
                'desc'                => sprintf(
                /* translators: 1) Opening anchor tag, 2) Closing anchor tag */
                    __( 'Configure your Printful Client settings. %1$sGet Help%2$s', 'dokan' ),
                    '<a href="https://dokan.co/docs/wordpress/modules/printful/#configuring-printful-admin" target="_blank">',
                    '</a>'
                ),

                'label'               => __( 'Connect to Printful', 'dokan' ),
                'icon_url'            => DOKAN_PRINTFUL_ASSETS . '/images/printful.svg',
                'social_desc'         => __( 'You can successfully connect Printful with your Marketplace.', 'dokan' ),
                'app_label'  => [
                    'name'         => 'app_label',
                    'label'        => __( 'Printful App Settings', 'dokan' ),
                    'type'         => 'html',
                    'desc'         => sprintf(
                    /* translators: 1) Opening anchor tag, 2) Closing anchor tag */
                        __( '%1$sCreate an App%2$s if you don\'t have one. Then fill Client ID and Secret below.', 'dokan' ),
                        '<a target="_blank" href="https://developers.printful.com/apps">',
                        '</a>'
                    ),
                    'social_field' => true,
                ],
                'app_url'    => [
                    'url'          => $redirect_url,
                    'name'         => 'app_url',
                    'label'        => __( 'App Url', 'dokan' ),
                    'type'         => 'html',
                    'tooltip'      => __( 'Your store URL, which will be required in creating the App.', 'dokan' ),
                    'social_field' => true,
                ],
                'app_redirect_domain'    => [
                    'url'          => $domain,
                    'name'         => 'app_redirect_domain',
                    'label'        => __( 'Redirection Domains', 'dokan' ),
                    'type'         => 'html',
                    'tooltip'      => __( 'Your store domain, which will be required in creating the App.', 'dokan' ),
                    'social_field' => true,
                ],
                'app_id'     => [
                    'name'         => 'app_id',
                    'label'        => __( 'Client ID', 'dokan' ),
                    'type'         => 'text',
                    'tooltip'      => __( 'You can get it from Facebook Developer platform -> Login -> Select  "Add A New App" -> Collect Client ID.', 'dokan' ),
                    'social_field' => true,
                ],
                'app_secret' => [
                    'name'         => 'app_secret',
                    'label'        => __( 'Secret key', 'dokan' ),
                    'type'         => 'text',
                    'tooltip'      => __( 'You can get it from Facebook Developer platform -> Login -> Select  "Add A New App" -> Collect App secret.', 'dokan' ),
                    'social_field' => true,
                ],
            ],
        ];

        $fields_size_guide = [
            'size_guide_sub_section' => [
                'name'          => 'size_guide_sub_section',
                'type'          => 'sub_section',
                'label'         => __( 'Size Guide Settings', 'dokan' ),
                'description'   => __( 'These settings control how the size guide will look on your Single Product Page.', 'dokan' ),
                'content_class' => 'sub-section-styles',
            ],
            'popup_title' => [
                'name'    => 'popup_title',
                'label'   => __( 'Size Guide Popup Title', 'dokan' ),
                'type'    => 'text',
                'default' => __( 'Size Guide', 'dokan' ),
            ],
            'popup_text_color' => [
                'name'    => 'popup_text_color',
                'label'   => __( 'Size Guide Popup Text Color', 'dokan' ),
                'type'    => 'color',
                'default' => '#000000',
            ],
            'popup_bg_color' => [
                'name'    => 'popup_bg_color',
                'label'   => __( 'Size Guide Popup Background Color', 'dokan' ),
                'type'    => 'color',
                'default' => '#FFFFFF',
            ],
            'tab_bg_color' => [
                'name'    => 'tab_bg_color',
                'label'   => __( 'Size Guide Tab Background Color', 'dokan' ),
                'type'    => 'color',
                'default' => '#EEEEEE',
            ],
            'active_tab_bg_color' => [
                'name'    => 'active_tab_bg_color',
                'label'   => __( 'Size Guide Active Tab Background Color', 'dokan' ),
                'type'    => 'color',
                'default' => '#DDDDDD',
            ],
            'size_guide_button_text' => [
                'name'    => 'size_guide_button_text',
                'label'   => __( 'Size Guide Button Text', 'dokan' ),
                'type'    => 'text',
                'default' => __( 'Size Guide', 'dokan' ),
            ],
            'button_text_color' => [
                'name'    => 'button_text_color',
                'label'   => __( 'Size Guide Button Text Color', 'dokan' ),
                'type'    => 'color',
                'default' => '#1064A9',
            ],
            'primary_measurement_unit' => [
                'name'    => 'primary_measurement_unit',
                'label'   => __( 'Primary Measurement Unit', 'dokan' ),
                'type'    => 'select',
                'options' => [
                    'inches'     => __( 'Inches', 'dokan' ),
                    'centimetre' => __( 'Centimetre', 'dokan' ),
                ],
                'default' => 'inches',
            ],
        ];

        $fields['dokan_printful'] = array_merge( $fields_warning, $fields['dokan_printful'], $fields_size_guide );

        return $fields;
    }

    /**
     * Is Currency Supported by Printful.
     *
     * @since 3.13.0
     *
     * @param string $currency_code Currency code to check.
     *
     * @return bool
     */
    public function is_printful_supported_currency( $currency_code ) {
        $supported_currencies = apply_filters(
			'dokan_printful_supported_currencies',
			[
	            'USD',
	            'EUR',
	            'GBP',
	            'CAD',
	            'JPY',
	            'AUD',
	            'BRL',
	            'CHF',
	            'DKK',
	            'HKD',
	            'MXN',
	            'NZD',
	            'SEK',
	        ]
        );

        return in_array( $currency_code, $supported_currencies, true );
    }

    /**
     * Is Seller The Shipping Fee Recipient.
     *
     * @since 3.13.0
     *
     * @return bool
     */
    public function shipping_fee_recipient_is_seller() {
        return 'seller' === dokan_get_option( 'shipping_fee_recipient', 'dokan_selling', 'seller' );
    }

    /**
     * Is Seller The Shipping Tax Fee Recipient.
     *
     * @since 3.13.0
     *
     * @return bool
     */
    public function shipping_tax_fee_recipient_is_seller() {
        return 'seller' === dokan_get_option( 'shipping_tax_fee_recipient', 'dokan_selling', 'seller' );
    }
}
