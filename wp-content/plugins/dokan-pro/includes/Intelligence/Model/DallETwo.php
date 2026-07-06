<?php

namespace WeDevs\DokanPro\Intelligence\Model;

use Exception;
use WeDevs\Dokan\Intelligence\Services\AIImageGenerationInterface;
use WeDevs\Dokan\Intelligence\Services\Model;
use WeDevs\DokanPro\Dependencies\GuzzleHttp\Client;
use WeDevs\DokanPro\Dependencies\GuzzleHttp\Exception\GuzzleException;
use WeDevs\DokanPro\Dependencies\GuzzleHttp\Exception\RequestException;

/**
 * Class DallETwo
 *
 * @since 4.1.0
 *
 * A class representing the OpenAI DALL-E 2 image generation model.
 * This class enables interaction with the OpenAI API for generating images
 * based on textual descriptions or editing existing images.
 */
class DallETwo extends Model implements AIImageGenerationInterface {

    protected const BASE_URL = 'https://api.openai.com/v1/';

    /**
     * Get the model ID.
     *
     * @return string
     */
    public function get_id(): string {
        return 'dall-e-2';
    }

    /**
     * Get the model title.
     *
     * @return string
     */
    public function get_title(): string {
        return __( 'DALL-E 2', 'dokan' );
    }

    /**
     * Get the model description.
     *
     * @return string
     */
    public function get_description(): string {
        return __( 'DALL-E 2 is an advanced AI model developed by OpenAI for generating high-quality images from textual descriptions.', 'dokan' );
    }

    /**
     * Get the model provider ID.
     *
     * @return string
     */
    public function get_provider_id(): string {
        return 'openai';
    }

    /**
     * Retrieves the API url required.
     *
     * @return string The API key.
     */
    protected function get_url(): string {
        return self::BASE_URL . 'images/edits';
    }

    /**
     * Retrieves the headers required for the API request.
     *
     * @return array
     */
    protected function get_headers(): array {
		return [
            'Authorization' => 'Bearer ' . $this->get_api_key(),
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

        if ( ! isset( $response['data'] ) || ! is_array( $response['data'] ) || empty( $response['data'] ) ) {
            return new \WP_Error( 'dokan_ai_image_generation_error', __( 'No image data returned from the AI model.', 'dokan' ) );
        }
        $image_data = $response['data'][0]['b64_json'] ?? null;
        if ( ! $image_data ) {
            return new \WP_Error( 'dokan_ai_image_generation_error', __( 'No image data found in the response.', 'dokan' ) );
        }

        return apply_filters(
            'dokan_ai_' . $this->get_type_prefix_for_generation() . $this->get_provider_id() . '_response_json', [
                'response' => 'data:image/png;base64,' . $image_data,
                'prompt' => $prompt,
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

            if ( empty( $image_data ) ) {
                return [];
            }
        } elseif ( preg_match( '/^data:image\/\w+;base64,/', $previous_image ) ) {
            // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
            $image_data = base64_decode( preg_replace( '/^data:image\/\w+;base64,/', '', $previous_image ) );
            if ( $image_data === false ) {
                return [];
            }
        } else {
            return [];
        }

        return [
            [
                'name'     => 'model',
                'contents' => $this->get_id(),
            ],
            [
                'name'     => 'image',
                'contents' => $image_data,
                'filename' => 'image.png',
                'headers'  => [
                    'Content-Type' => 'image/png',
                ],
            ],
            [
                'name'     => 'n',
                'contents' => $args['n'] ?? 1,
            ],
            [
                'name'     => 'size',
                'contents' => $args['size'] ?? '1024x1024',
            ],
            [
                'name'     => 'prompt',
                'contents' => $prompt,
            ],
            [
                'name'     => 'response_format',
                'contents' => 'b64_json',
            ],
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

        $client = new Client();

        $options = [
            'headers' => $this->get_headers(),
            'multipart' => $this->get_payload( $prompt, $args ),
            'timeout' => 60,
        ];

        try {
            $response = $client->post( $this->get_url(), $options );
            $response_body = $response->getBody()->getContents();
            $data = json_decode( $response_body, true );
        } catch ( GuzzleException $e ) {
            $codes = [
                'invalid_api_key' => 401,
                'insufficient_quota' => 429,
            ];
            $code = $e->getCode() ?: 500;
            $message = $e->getMessage();
            if ( $e->hasResponse() ) {
                $body = $e->getResponse()->getBody()->getContents();
                $data = json_decode( $body, true );
                if ( isset( $data['error']['message'] ) ) {
                    $message = $data['error']['message'];
                }
                if ( isset( $data['error']['code'] ) && isset( $codes[ $data['error']['code'] ] ) ) {
                    $code = $codes[ $data['error']['code'] ];
                }
            }
            throw new Exception( esc_html( $message ), $code );
        }

        if ( isset( $data['error'] ) ) {
            $codes = [
                'invalid_api_key' => 401,
                'insufficient_quota' => 429,
            ];
            throw new Exception( esc_html( $data['error']['message'] ), $codes[ $data['error']['code'] ] ?? 500 );
        }

        return $data;
    }
}
