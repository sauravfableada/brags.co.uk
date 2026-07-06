<?php

namespace WeDevs\DokanPro\Intelligence\Model;

use WeDevs\Dokan\Intelligence\Services\AIImageGenerationInterface;
use WeDevs\Dokan\Intelligence\Services\Model;

/**
 * Class GeminiTwoDotFiveFlashImage
 *
 * @since 4.1.0
 *
 * Image generation/editing using Google Gemini 2.5 Flash Image preview model.
 * Extends ImagenThree to reuse Gemini generateContent integration
 * (prompt + inline image) and response parsing.
 */
class GeminiTwoDotFiveFlashImage extends Model implements AIImageGenerationInterface {

    /**
     * Get the model ID.
     *
     * @return string
     */
    public function get_id(): string {
        return 'gemini-2.5-flash-image';
    }

    /**
     * Get the model title.
     *
     * @return string
     */
    public function get_title(): string {
        return __( 'Gemini 2.5 Flash Image (aka Nano Banana)', 'dokan' );
    }

    /**
     * Get the model description.
     *
     * @return string
     */
    public function get_description(): string {
        return __( 'Google Gemini 2.5 Flash Image (preview) model for editing or generating images with a guiding prompt and an input image.', 'dokan' );
    }

    /**
     * Get the model provider ID.
     *
     * @return string
     */
    public function get_provider_id(): string {
        return 'gemini';
    }

    /**
     * Retrieves the API url required.
     *
     * @return string The API key.
     */
    protected function get_url(): string {
        return 'https://generativelanguage.googleapis.com/v1beta/models/' . $this->get_id() . ':generateContent?key=' . $this->get_api_key();
    }

    /**
     * Retrieves the headers required for the API request.
     *
     * @return array
     */
    protected function get_headers(): array {
        return [
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Get the model provider ID.
     *
     * @since 4.1.0
     *
     * @param string $prompt Prompt text.
     * @param array  $args Additional arguments.
     *
     * @return array
     */
    public function get_payload( string $prompt, $args = [] ): array {
        $previous_image = $args['existing_image'] ?? null;

        if ( ! $previous_image ) {
            return [];
        }

        if ( filter_var( $previous_image, FILTER_VALIDATE_URL ) ) {
            // Use wp_remote_get for remote images and handle errors.
            $response = wp_remote_get( $previous_image, [ 'sslverify' => false ] );

            if ( is_wp_error( $response ) ) {
                return [];
            }

            $image_data = wp_remote_retrieve_body( $response );
            $image_data = base64_encode( $image_data ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
            // Get image mimetype from the response headers.
            $content_type = wp_remote_retrieve_header( $response, 'content-type' );

            if ( empty( $image_data ) ) {
                return [];
            }
        } elseif ( preg_match( '/^data:image\/\w+;base64,/', $previous_image ) ) {
            // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
            $image_data = preg_replace( '/^data:image\/\w+;base64,/', '', $previous_image );
            $content_type = 'image/png'; // Default to PNG if not specified.
            if ( $image_data === false ) {
                return [];
            }
        } else {
            return [];
        }
        return [
            'contents'         => [
                [
                    'parts' => [
                        [ 'text' => $prompt ],
                        [
                            'inline_data' => [
                                'data'      => $image_data,
                                'mime_type' => $content_type,
                            ],
                        ],
                    ],
                ],
            ],
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
        if ( ! isset( $response['candidates'] ) || ! is_array( $response['candidates'] ) || empty( $response['candidates'] ) ) {
            return new \WP_Error( 'dokan_ai_image_generation_error', __( 'No image data returned from the AI model.', 'dokan' ) );
        }
        $image_data = $response['candidates'][0]['content']['parts'][0]['inlineData']['data'] ?? $response['candidates'][0]['content']['parts'][1]['inlineData']['data'] ?? null;
        $mime_type = $response['candidates'][0]['content']['parts'][0]['inlineData']['mimeType'] ?? $response['candidates'][0]['content']['parts'][1]['inlineData']['mimeType'] ?? 'image/png';
        if ( ! $image_data ) {
            return new \WP_Error( 'dokan_ai_image_generation_error', __( 'No image data found in the response.', 'dokan' ) );
        }

        return apply_filters(
            'dokan_ai_' . $this->get_type_prefix_for_generation() . $this->get_provider_id() . '_response_json', [
                'response' => 'data:' . $mime_type . ';base64,' . $image_data,
                'prompt' => $prompt,
            ]
        );
    }
}
