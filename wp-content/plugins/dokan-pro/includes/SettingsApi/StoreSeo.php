<?php

namespace WeDevs\DokanPro\SettingsApi;

use WeDevs\Dokan\Vendor\SettingsApi\Abstracts\Page;

defined( 'ABSPATH' ) || exit;

/**
 * Store SEO Settings API Page.
 */
class StoreSeo extends Page {

    /**
     * Group or page key.
     *
     * @var string $group Group or page key.
     */
    protected $group = 'store_seo';

    public function __construct() {
        parent::__construct();
        add_action( 'dokan_rest_store_seo_settings_after_update', [ $this, 'update_store_seo_settings' ], 10, 2 );
    }

    /**
     * Render the settings page with tab, cad, fields.
     *
     *
     * @param array $groups Settings Group or page to render.
     *
     * @return array
     */
    public function render_group( array $groups ): array {
        $groups[] = [
            'id'          => $this->group,
            'label'       => __( 'Store SEO', 'dokan' ),
            'description' => __( 'Vendor Store SEO Settings', 'dokan' ),
            'parent_id'   => '',
        ];
        return $groups;
    }

    /**
     * Render the settings store seo tab.
     */
    public function render_settings( array $settings ): array {
        $seo_tab = [
			[
				'id'        => 'store_seo_general',
				'title'     => __( 'General', 'dokan' ),
				'desc'      => __( 'The general store SEO settings', 'dokan' ),
				'info'      => [],
				'type'      => 'tab',
				'parent_id' => 'store_seo',
			],
		];

		$seo_card = [
			[
				'id'        => 'store_seo_card',
				'title'     => __( 'SEO', 'dokan' ),
				'desc'      => __( 'Store SEO settings', 'dokan' ),
				'info'      => [],
				'type'      => 'card',
				'parent_id' => 'store_seo',
			],
		];

		$store_seo = [
			[
				'id'        => 'dokan-seo-meta-title',
				'title'     => __( 'Social Media', 'dokan' ),
				'desc'      => __( 'The social media settings', 'dokan' ),
				'info'      => [],
				'type'      => 'text',
				'card'      => 'store_seo_card',
				'tab'       => 'store_seo_general',
				'parent_id' => 'store_seo',
			],
			[
				'id'        => 'dokan-seo-meta-desc',
				'title'     => __( 'Meta Description', 'dokan' ),
				'desc'      => __( 'The meta description settings', 'dokan' ),
				'info'      => [],
				'type'      => 'text',
				'card'      => 'store_seo_card',
				'tab'       => 'store_seo_general',
				'parent_id' => 'store_seo',
			],
			[
				'id'        => 'dokan-seo-meta-keywords',
				'title'     => __( 'Meta Keywords', 'dokan' ),
				'desc'      => __( 'The meta keywords settings', 'dokan' ),
				'info'      => [],
				'type'      => 'text',
				'card'      => 'store_seo_card',
				'tab'       => 'store_seo_general',
				'parent_id' => 'store_seo',
			],
			[
				'id'        => 'dokan-seo-og-title',
				'title'     => __( 'Open Graph Title', 'dokan' ),
				'desc'      => __( 'The open graph title settings', 'dokan' ),
				'info'      => [],
				'type'      => 'text',
				'card'      => 'store_seo_card',
				'tab'       => 'store_seo_general',
				'parent_id' => 'store_seo',
			],
			[
				'id'        => 'dokan-seo-og-desc',
				'title'     => __( 'Open Graph Description', 'dokan' ),
				'desc'      => __( 'The open graph description settings', 'dokan' ),
				'info'      => [],
				'type'      => 'text',
				'card'      => 'store_seo_card',
				'tab'       => 'store_seo_general',
				'parent_id' => 'store_seo',
			],
			[
				'id'        => 'dokan-seo-og-image',
				'title'     => __( 'Open Graph Image', 'dokan' ),
				'desc'      => __( 'The open graph image settings', 'dokan' ),
				'info'      => [],
				'type'      => 'image',
				'card'      => 'store_seo_card',
				'tab'       => 'store_seo_general',
				'parent_id' => 'store_seo',
			],
			[
				'id'        => 'dokan-seo-twitter-title',
				'title'     => __( 'Twitter Title', 'dokan' ),
				'desc'      => __( 'The twitter title settings', 'dokan' ),
				'info'      => [],
				'type'      => 'text',
				'card'      => 'store_seo_card',
				'tab'       => 'store_seo_general',
				'parent_id' => 'store_seo',
			],
			[
				'id'        => 'dokan-seo-twitter-desc',
				'title'     => __( 'Twitter Description', 'dokan' ),
				'desc'      => __( 'The twitter description settings', 'dokan' ),
				'info'      => [],
				'type'      => 'text',
				'card'      => 'store_seo_card',
				'tab'       => 'store_seo_general',
				'parent_id' => 'store_seo',
			],
			[
				'id'        => 'dokan-seo-twitter-image',
				'title'     => __( 'Twitter Image', 'dokan' ),
				'desc'      => __( 'The twitter image settings', 'dokan' ),
				'info'      => [],
				'type'      => 'image',
				'card'      => 'store_seo_card',
				'tab'       => 'store_seo_general',
				'parent_id' => 'store_seo',
			],
		];

		$store_seo  = apply_filters( 'dokan_vendor_settings_api_store_seo', $store_seo );
		$seo_card   = apply_filters( 'dokan_vendor_settings_api_store_seo_card', $seo_card );
		$seo_tab    = apply_filters( 'dokan_vendor_settings_api_store_seo_general_tab', $seo_tab );

		array_push( $seo_card, ...$store_seo );
		array_push( $seo_tab, ...$seo_card );
		array_push( $settings, ...$seo_tab );

        return $settings;
    }

    public function update_store_seo_settings( $vendor, array $settings ) {
        $default_store_seo = array(
            'dokan-seo-meta-title'    => '',
            'dokan-seo-meta-desc'     => '',
            'dokan-seo-meta-keywords' => '',
            'dokan-seo-og-title'      => '',
            'dokan-seo-og-desc'       => '',
            'dokan-seo-og-image'      => '',
            'dokan-seo-twitter-title' => '',
            'dokan-seo-twitter-desc'  => '',
            'dokan-seo-twitter-image' => '',
        );
        $data = array_column( $settings, 'value', 'id' );
        $seller_profile = dokan_get_store_info( $vendor->get_id() );
        $seller_profile['store_seo'] = wp_parse_args( $data, $default_store_seo );
        update_user_meta( $vendor->get_id(), 'dokan_profile_settings', $seller_profile );
    }
}
