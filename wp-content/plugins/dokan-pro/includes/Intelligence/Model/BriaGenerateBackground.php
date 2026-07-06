<?php

namespace WeDevs\DokanPro\Intelligence\Model;

use Exception;
use WeDevs\Dokan\Intelligence\Services\AIImageGenerationInterface;
use WeDevs\Dokan\Intelligence\Services\Model;

/**
 * Class BriaGenerateBackground.
 *
 * @since 4.1.0
 *
 * This class represents a model to interact with the BRIA AI Generate Background API.
 * It implements methods for generating image backgrounds based on text prompts
 * and managing communication with the BRIA API service.
 */
class BriaGenerateBackground extends Model implements AIImageGenerationInterface {

    protected const BASE_URL = 'https://engine.prod.bria-api.com/v2/';

    /**
     * Get the model ID.
     *
     * @return string
     */
    public function get_id(): string {
        return 'bria-generate-background';
    }

    /**
     * Get the model title.
     *
     * @return string
     */
    public function get_title(): string {
        return __( 'Generate Background', 'dokan' );
    }

    /**
     * Get the model description.
     *
     * @return string
     */
    public function get_description(): string {
        return __( 'BRIA AI Generate Background model that replaces or generates backgrounds for images based on text prompts.', 'dokan' );
    }

    /**
     * Get the model provider ID.
     *
     * @return string
     */
    public function get_provider_id(): string {
        return 'bria-ai';
    }

    /**
     * Retrieves the API url required.
     *
     * @return string The API URL.
     */
    protected function get_url(): string {
        return self::BASE_URL . 'image/edit/replace_background';
    }

    /**
     * Retrieves the headers required for the API request.
     *
     * @return array
     */
    protected function get_headers(): array {
        return [
            'Content-Type' => 'application/json',
            'api_token' => $this->get_api_key(),
        ];
    }

    /**
     * Process the image prompt and return the generated image.
     *
     * @param string $prompt The input prompt for the AI model.
     * @param array  $args Optional additional data.
     *
     * @return mixed The generated image from the AI model.
     */
    public function process_image( string $prompt, array $args = [] ) {
        $this->generation_type = self::SUPPORTS_IMAGE;

        $response = $this->request( $prompt, $args );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        if ( isset( $args['json_format'] ) && is_string( $response ) ) {
            $response = json_decode( $response, true );
        }

        // Handle Bria AI response format
        if ( ! isset( $response['result'] ) || ! is_array( $response['result'] ) || empty( $response['result'] ) ) {
            return new \WP_Error( 'dokan_ai_image_generation_error', __( 'No image data returned from the AI model.', 'dokan' ) );
        }

        // Get the first result
        $result = $response['result'];
        $image_url = $result['image_url'] ?? null;

        if ( ! $image_url ) {
            return new \WP_Error( 'dokan_ai_image_generation_error', __( 'No image URL found in the response.', 'dokan' ) );
        }

        // Convert image URL to base64 data URL for consistency with other models
        $image_response = wp_remote_get( $image_url, [ 'sslverify' => false ] );

        if ( is_wp_error( $image_response ) ) {
            return $image_response;
        }

        $image_data = wp_remote_retrieve_body( $image_response );
        $content_type = wp_remote_retrieve_header( $image_response, 'content-type' );

        if ( empty( $image_data ) ) {
            return new \WP_Error( 'dokan_ai_image_generation_error', __( 'Failed to retrieve image data.', 'dokan' ) );
        }

        $base64_image = base64_encode( $image_data ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
        $mime_type = $content_type ?: 'image/png'; // phpcs:ignore

        return apply_filters(
            'dokan_ai_' . $this->get_type_prefix_for_generation() . $this->get_provider_id() . '_response_json', [
                'response' => 'data:' . $mime_type . ';base64,' . $base64_image,
                'prompt' => $prompt,
                'original_url' => $image_url,
            ]
        );
    }

    /**
     * Retrieves the payload required for the API request.
     *
     * @param string $prompt
     * @param array  $args
     *
     * @return array
     */
    protected function get_payload( string $prompt, array $args = [] ): array {
        $image = $args['image'] ?? $args['existing_image'] ?? $args['image_url'] ?? null;

        if ( ! $image ) {
            return [];
        }

        // If it's a data URL, we need to upload it first or convert it to a URL
        if ( ! preg_match( '/^data:image\/\w+;base64,/', $image ) ) {
            if ( ! filter_var( $image, FILTER_VALIDATE_URL ) ) {
                return [];
            }

            // If we're in debug mode, we are asuming the site is in local host. try to download the image and convert to base64.
            if ( WP_DEBUG ) {
                $response = wp_remote_get( $image, [ 'sslverify' => false ] );

                if ( is_wp_error( $response ) ) {
                    return [];
                }

                $image_data = wp_remote_retrieve_body( $response );
                $image = base64_encode( $image_data ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
            }
        }

        return [
            'image' => $image,
            'prompt' => $prompt,
            'sync' => $args['sync'] ?? true,
        ];
    }


    /**
     * Make API request
     *
     * @param string $prompt Prompt for the AI model.
     * @param array $args Additional arguments for the request.
     *
     * @return array
     * @throws Exception
     */
    protected function request( string $prompt, array $args = [] ): array {
        if ( ! $this->is_valid_api_key() ) {
            throw new Exception( esc_html__( 'API key is not configured', 'dokan' ) );
        }
        // Make the request
        $response = wp_remote_post(
            $this->get_url(), [
                'headers' => $this->get_headers(),
                'body' => wp_json_encode( $this->get_payload( $prompt, $args ) ),
                'timeout' => 60,
            ]
        );
        $codes = [
            'invalid_api_key' => 401,
            'failed_to_download_image' => 460,
            'insufficient_quota' => 429,
        ];

        // check the response status code using wp functionality
        $response_code = wp_remote_retrieve_response_code( $response );
        if ( is_wp_error( $response ) || in_array( (int) $response_code, array_values( $codes ), true ) ) {
            throw new Exception( esc_html__( 'Failed to connect to the AI service.', 'dokan' ), esc_html( $response_code ?? 500 ) );
        }

        if ( is_wp_error( $response ) ) {
            throw new Exception( esc_html( $response->get_error_message() ), $codes[ $response->get_error_code() ] ?? 500 ); // phpcs:ignore
        }

        $response_body = wp_remote_retrieve_body( $response );
        $data = json_decode( $response_body, true );

        if ( isset( $data['error'] ) ) {
            throw new Exception( esc_html( $data['error']['message'] ), $codes[ $data['error']['code'] ] ?? 500 ); // phpcs:ignore
        }

        return $data;
    }
}
