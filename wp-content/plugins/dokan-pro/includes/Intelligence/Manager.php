<?php

namespace WeDevs\DokanPro\Intelligence;

use WeDevs\DokanPro\Intelligence\Model\BriaGenerateBackground;
use WeDevs\DokanPro\Intelligence\Model\DallETwo;
use WeDevs\DokanPro\Intelligence\Model\GeminiTwoDotFiveFlashImage;
use WeDevs\DokanPro\Intelligence\Model\GeminiThreeProImagePreview;
use WeDevs\DokanPro\Intelligence\Model\GPTImageOne;
use WeDevs\DokanPro\Intelligence\Model\ImagenThree;
use WeDevs\DokanPro\Intelligence\Provider\BriaAi;
use WeDevs\DokanPro\Intelligence\Provider\LeonardoAi;

/**
 * Intelligence Manager Class
 *
 * @since 4.1.0
 */
class Manager {
    public function __construct() {
        new Settings();
        new Assets();

        $bria_ai = new BriaAi();
        $bria_ai->register_hooks();

        $bria_generate_background = new BriaGenerateBackground();
        $bria_generate_background->register_hooks();

        $dall_e_2 = new DallETwo();
//        $dall_e_2->register_hooks(); // TODO: Enable this when Dall-E 2 is ready

        $gpt_image_one = new GPTImageOne();
//        $gpt_image_one->register_hooks(); // TODO: Enable this when GPT-Image-One is ready

        $gemini_2_5_image = new GeminiTwoDotFiveFlashImage();
        $gemini_2_5_image->register_hooks();

        $gemini_3_pro_image = new GeminiThreeProImagePreview();
        $gemini_3_pro_image->register_hooks();
    }
}
