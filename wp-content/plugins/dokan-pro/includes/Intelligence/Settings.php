<?php

namespace WeDevs\DokanPro\Intelligence;

use WeDevs\Dokan\Intelligence\Manager;
use WeDevs\Dokan\Intelligence\Services\Model;

/**
 * Settings class for AI product info generator.
 *
 * This class adds settings fields for configuring the AI product info generator
 * in the Dokan settings.
 *
 * @since 4.1.0
 */
class Settings {
    public function __construct() {
        add_filter( 'dokan_ai_settings_fields', [ $this, 'add_settings_fields' ] );
    }

    /**
     * Add settings fields for AI product info generator.
     *
     * @since 4.1.0
     *
     * @param array $settings_fields The existing settings fields.
     *
     * @return array Modified settings fields with AI product info generator options.
     */
    public function add_settings_fields( array $settings_fields ) {
        $image_providers = dokan()->get_container()->get( Manager::class )->get_image_supported_providers();

        $image_fields = [
            'dokan_ai_image_gen'              => [
                'name'          => 'dokan_ai_image_gen',
                'type'          => 'sub_section',
                'label'         => __( 'AI Product Image Enhance', 'dokan' ),
                'description'   => __( 'Enable AI Image Enhancement mode for vendors to enhance product images.', 'dokan' ),
                'content_class' => 'sub-section-styles',
            ],
            'dokan_ai_image_gen_availability'              => [
                'name'          => 'dokan_ai_image_gen_availability',
                'type'          => 'switcher',
                'label'         => __( 'Enable AI Image Enhancer', 'dokan' ),
                'description'   => __( 'Control marketplace vendors get this feature or not.', 'dokan' ),
                'default'       => 'off',
            ],
            'dokan_ai_image_engine' => [
                'name'    => 'dokan_ai_image_engine',
                'label'   => __( 'Engine', 'dokan' ),
                'type'    => 'select',
                'options' => array_map(
                    fn( $provider ) => $provider->get_title(),
                    $image_providers
                ),
                'desc'    => __( 'Select which AI provider to use for generating content.', 'dokan' ),
                'show_if' => [
                    'dokan_ai_image_gen_availability' => [
                        'equal' => 'on',
                    ],
                ],
                'default' => 'openai',
            ],
        ];

        $settings_fields['dokan_ai'] = array_merge( $settings_fields['dokan_ai'], $image_fields );

        foreach ( $image_providers as $provider_id => $provider ) {
            $settings_fields['dokan_ai'][ 'dokan_ai_image_' . $provider_id . '_api_key' ] = [
                'name'    => 'dokan_ai_image_' . $provider_id . '_api_key',
                // translators: %s is the provider name, e.g., OpenAI
                'label'   => sprintf( __( '%s API Key', 'dokan' ), $provider->get_title() ),
                'type'    => 'text',
                /* translators: 1: OpenAi Link */
                'desc'    => sprintf( __( 'You can get your API Keys in your <a href="%1$s" target="_blank">%2$s Account.</a>', 'dokan' ), $provider->get_api_key_url(), $provider->get_title() ),
                'default' => '',
                'secret_text' => true,
                'show_if' => [
                    'dokan_ai_image_gen_availability' => [
                        'equal' => 'on',
                    ],
                    'dokan_ai_image_engine' => [
                        'equal' => $provider_id,
                    ],
                ],
                'tooltip' => __( 'Your API key provides secure access to the AI service. Usage costs will be charged to the connected account.', 'dokan' ),
            ];

            $settings_fields['dokan_ai'][ 'dokan_ai_image_' . $provider_id . '_model' ] = [
                'name'    => 'dokan_ai_image_' . $provider_id . '_model',
                'label'   => __( 'Model', 'dokan' ),
                'type'    => 'select',
                'options' => array_map( fn( $model ) => $model->get_title(), $provider->get_models_by_type( Model::SUPPORTS_IMAGE ) ),
                'desc'    => __( 'More advanced models provide higher quality output but may cost more per generation.', 'dokan' ),
                'default' => $provider->get_default_model_id(),
                'show_if' => [
                    'dokan_ai_image_gen_availability' => [
                        'equal' => 'on',
                    ],
                    'dokan_ai_image_engine' => [
                        'equal' => $provider_id,
                    ],
                ],
            ];
        }

        return $settings_fields;
    }
}
