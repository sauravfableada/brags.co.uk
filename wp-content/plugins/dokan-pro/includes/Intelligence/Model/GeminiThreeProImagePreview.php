<?php

namespace WeDevs\DokanPro\Intelligence\Model;

/**
 * Class GeminiThreeProImagePreview
 *
 * @since 4.2.4
 *
 * Image generation/editing using Google Gemini 3 Pro Image preview model.
 * Inherits all logic from GeminiTwoDotFiveFlashImage as the API structure is identical.
 */
class GeminiThreeProImagePreview extends GeminiTwoDotFiveFlashImage {

    /**
     * Get the model ID.
     *
     * @return string
     */
    public function get_id(): string {
        return 'gemini-3-pro-image-preview';
    }

    /**
     * Get the model title.
     *
     * @return string
     */
    public function get_title(): string {
        return __( 'Gemini 3 Pro Image (aka Nano Banana)', 'dokan' );
    }

    /**
     * Get the model description.
     *
     * @return string
     */
    public function get_description(): string {
        return __( 'Google Gemini 3 Pro Image (preview) model for editing or generating images with a guiding prompt and an input image.', 'dokan' );
    }
}
