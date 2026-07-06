<?php

namespace WeDevs\DokanPro\Intelligence\Provider;

use WeDevs\Dokan\Intelligence\Services\Provider;

/**
 * Class BriaAi
 *
 * @since 4.1.0
 *
 * Represents the Bria AI provider integration, offering advanced AI-powered image generation
 * and content creation services. This class handles API identifiers, descriptions, and related
 * settings specific to Bria AI.
 */
class BriaAi extends Provider {

    /**
     * Get the provider ID.
     *
     * @since 4.1.0
     *
     * @return string
     */
    public function get_id(): string {
        return 'bria-ai';
    }

    /**
     * Get the provider title.
     *
     * @since 4.1.0
     *
     * @return string
     */
    public function get_title(): string {
        return __( 'BRIA AI', 'dokan' );
    }

    /**
     * Get the provider description.
     *
     * @since 4.1.0
     *
     * @return string
     */
    public function get_description(): string {
        return __( 'BRIA AI is an advanced AI provider that offers powerful image generation and content creation capabilities, enabling users to create high-quality visual content efficiently.', 'dokan' );
    }

    /**
     * Retrieves the URL for the API key management page.
     *
     * @since 4.1.0
     *
     * @return string The URL string for accessing the API key page.
     */
    public function get_api_key_url(): string {
        return 'https://platform.bria.ai/console/account/api-keys';
    }

    /**
     * Retrieves the default model identifier.
     *
     * @since 4.1.0
     *
     * @return string The default model ID.
     */
    public function get_default_model_id(): string {
        return 'bria-generate-background';
    }
}
