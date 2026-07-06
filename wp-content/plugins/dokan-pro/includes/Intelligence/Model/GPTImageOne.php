<?php

namespace WeDevs\DokanPro\Intelligence\Model;

use WeDevs\DokanPro\Intelligence\Model\DallETwo;

/**
 * Class GPTImageOne
 *
 * @since 4.1.0
 *
 * Represents the GPTImageOne model, an advanced image generation module
 * that builds on the DallETwo framework to create stunning visuals from text prompts.
 */
class GPTImageOne extends DallETwo {

    /**
     * Get the model ID.
     *
     * @return string
     */
    public function get_id(): string {
        return 'gpt-image-1';
    }

    /**
     * Get the model title.
     *
     * @return string
     */
    public function get_title(): string {
        return __( 'GPT Image 1', 'dokan' );
    }

    /**
     * Get the model description.
     *
     * @return string
     */
    public function get_description(): string {
        return __( 'GPT Image 1 is an advanced image generation model that creates stunning images from text prompts.', 'dokan' );
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
                'name'     => 'image[]',
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
        ];
    }
}
