<?php

namespace WeDevs\DokanPro\Modules\RequestForQuotation\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Class for Settings Hooks integration.
 *
 * @since 3.6.0
 */
class Settings {

    /**
     * Class constructor
     *
     * @since 3.6.0
     *
     * @return void
     */
    public function __construct() {
        add_filter( 'dokan_settings_sections', [ $this, 'load_settings_section' ], 11 );
        add_filter( 'dokan_settings_fields', [ $this, 'load_settings_fields' ], 11 );
    }

    /**
     * Load admin settings section.
     *
     * @since 3.6.0
     *
     * @param $section
     *
     * @return mixed
     */
    public function load_settings_section( $section ) {
        $section[] = [
            'id'                   => 'dokan_quote_settings',
            'title'                => __( 'Quote Settings', 'dokan' ),
            'icon_url'             => DOKAN_RAQ_ASSETS_DIR . '/images/quote.svg',
            'description'          => __( 'Configure Quote Settings ', 'dokan' ),
            'document_link'        => 'https://dokan.co/docs/wordpress/modules/dokan-request-for-quotation-module/',
            'settings_title'       => __( 'Quote Settings', 'dokan' ),
            'settings_description' => __( 'You can configure your site to allow customers to send customized quotes on the selected products.', 'dokan' ),
        ];

        return $section;
    }

    /**
     * Load all settings fields.
     *
     * @since 3.6.0
     *
     * @param $fields
     *
     * @return mixed
     */
    public function load_settings_fields( $fields ) {
        $fields['dokan_quote_settings'] = [
            'dokan_quote_settings'           => [
                'name'        => 'dokan_quote_settings',
                'label'       => __( 'Configuration', 'dokan' ),
                'type'        => 'sub_section',
                'description' => __( 'Configure your quote page settings and control access to your site quote product.', 'dokan' ),
            ],
            'enable_out_of_stock'            => [
                'name'    => 'enable_out_of_stock',
                'label'   => __( 'Enable Quote for Out of Stock Products', 'dokan' ),
                'type'    => 'switcher',
                'desc'    => __( 'Enable/Disable quote button for out of stock products. (Note: It is compatible with simple and variable products only)', 'dokan' ),
                'default' => 'on',
            ],
            'enable_ajax_add_to_quote'       => [
                'name'    => 'enable_ajax_add_to_quote',
                'label'   => __( 'Enable Ajax Add to Quote', 'dokan' ),
                'type'    => 'switcher',
                'desc'    => esc_html__( 'Enable/Disable Ajax add to quote.', 'dokan' ),
                'default' => 'on',
            ],
            'redirect_to_quote_page'         => [
                'name'    => 'redirect_to_quote_page',
                'label'   => __( 'Redirect to Quote Page', 'dokan' ),
                'type'    => 'switcher',
                'desc'    => esc_html__( 'Redirect to the quote page after a product is successfully added to quote.', 'dokan' ),
                'default' => 'off',
            ],
            'quote_attributes_settings'      => [
                'name'          => 'quote_attributes_settings',
                'label'         => __( 'Quote Attributes Settings', 'dokan' ),
                'type'          => 'sub_section',
                'description'   => __( 'Configure your quote attribute settings and control access to your site quote product attributes.', 'dokan' ),
                'content_class' => 'sub-section-styles',
            ],
            'decrease_offered_price'         => [
                'name'    => 'decrease_offered_price',
                'label'   => __( 'Decrease Offered Price', 'dokan' ),
                'type'    => 'number',
                'default' => 0,
                'desc'    => esc_html__( 'Enter number in percent to decrease the offered price from standard price of product. Set zero (0) for standard price. Note: offered price will be display according to settings of cart. (eg: including/excluding tax)', 'dokan' ),
            ],
            'enable_convert_to_order'        => [
                'name'    => 'enable_convert_to_order',
                'label'   => __( 'Enable Convert to Order', 'dokan' ),
                'type'    => 'switcher',
                'desc'    => esc_html__( 'Customer can convert a quote into order.', 'dokan' ),
                'default' => 'off',
            ],
            'enable_quote_converter_display' => [
                'name'    => 'enable_quote_converter_display',
                'label'   => __( 'Enable Quote Converter Display', 'dokan' ),
                'type'    => 'switcher',
                'desc'    => __( 'Enable display of "Quote converted by" in customer\'s my-account quote details page.', 'dokan' ),
                'default' => 'off',
            ],
        ];

        return $fields;
    }

}
