<?php

namespace WeDevs\DokanPro\Modules\Printful\Processors;

use Exception;
use WC_Data_Exception;
use WC_Product;
use WC_Product_Attribute;
use WC_Product_Variation;
use WeDevs\DokanPro\Dependencies\Printful\Exceptions\PrintfulException;
use WeDevs\DokanPro\Dependencies\Printful\PrintfulApiClient;
use WeDevs\DokanPro\Dependencies\Printful\Structures\Sync\Responses\SyncProductRequestResponse;
use WeDevs\DokanPro\Dependencies\Printful\Structures\Sync\Responses\SyncVariantResponse;
use WeDevs\DokanPro\Modules\Printful\Auth;
use WeDevs\DokanPro\Modules\Printful\Integrations\ProductsClient;
use WP_Post;

/**
 * Class Printful Product Processor.
 *
 * @since 3.13.0
 *
 * @package WeDevs\DokanPro\Modules\Printful\Processors
 *
 * @phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
 */
class PrintfulProductProcessor {

    /**
     * Meta key for storing Printful product ID.
     *
     * @since 3.13.0
     */
    const META_KEY_PRODUCT_ID = 'dokan_printful_product_id';

    /**
     * Meta key for storing Printful product variation ID.
     *
     * @since 3.13.0
     */
    const META_KEY_PRODUCT_VARIATION_ID = 'dokan_printful_product_variation_id';

    /**
     * Meta key for storing Printful product external variation ID.
     *
     * @since 3.13.0
     */
    const META_KEY_PRODUCT_EXTERNAL_VARIATION_ID = 'dokan_printful_product_external_variation_id';

    /**
     * Meta key for storing Printful store ID associated with product.
     *
     * @since 3.13.0
     */
    const META_KEY_STORE_ID = 'dokan_printful_product_store_id';

    /**
     * Meta key for storing Printful catalog product ID.
     *
     * @since 3.13.0
     */
    const META_KEY_CATALOG_PRODUCT_ID = 'dokan_printful_catalog_product_id';

    /**
     * Meta key for storing Printful product size guide.
     *
     * @since 3.13.0
     */
    const META_KEY_PRODUCT_SIZE_GUIDE = 'dokan_printful_product_size_guide';

    /**
     * Meta key for storing Printful product size guide request failure count.
     *
     * @since 3.13.0
     */
    const META_KEY_PRINTFUL_SIZE_GUIDE_FAILURE_COUNT = 'dokan_printful_size_guide_request_failure_count';

    /**
     * Vendor ID.
     *
     * @since 3.13.0
     *
     * @var int $vendor_id Vendor ID.
     */
    protected int $vendor_id;

    /**
     * Constructor.
     *
     * @since 3.13.0
     *
     * @param int $vendor_id Vendor ID.
     */
    public function __construct( int $vendor_id ) {
        set_time_limit( 0 );
        $this->vendor_id = $vendor_id;
    }

    /**
     * Create or Update a product from Printful Product ID.
     *
     * @since 3.13.0
     *
     * @param int    $product_id Printful Product ID.
     * @param int    $external_id Existing associated Product id.
     * @param string $previous_status Previous product status.
     *
     * @return void
     */
    public function process( int $product_id, int $external_id, string $previous_status = 'publish' ) {
        try {
            $product_client     = $this->get_printful_products_client();
            $printful_product   = $product_client->getProduct( $product_id );
        } catch ( Exception $e ) {
            dokan_log( $e->getMessage() );
            return;
        }

        $args = [
            'type'   => 'variable',
            'status' => 'draft',
            'author' => $this->vendor_id,
            'name'   => $printful_product->syncProduct->name,
        ];

        try {
            if ( ! empty( $external_id ) ) {
                // Remove all images associated with the product before re-sync.
                $this->clean_product_old_images( $external_id );

                $args['id'] = $external_id;
                $product    = dokan()->product->update( $args );
            } else {
                $product = dokan()->product->create( $args );
            }
        } catch ( Exception $e ) {
            dokan_log(
                sprintf(
                    // translators: 1: Product ID 2: External Product ID 3: Vendor ID 4: Error message.
                    esc_html__(
                        'Product Sync failed for webhook processing. Product ID: %1$d, External Product ID: %2$d, Vendor ID: %3$d. Error: %4$s',
                        'dokan'
                    ),
                    $product_id,
                    $external_id,
                    $this->vendor_id,
                    $e->getMessage()
                )
            );

            return;
        }

        $attributes         = $this->create_attributes( $printful_product->syncVariants );
        $store_id           = $this->get_connected_store_id();
        $catalog_product_id = $printful_product->syncVariants[0]->product->productId;

        $product->set_attributes( $attributes );
        $product->add_meta_data( self::META_KEY_PRODUCT_ID, $printful_product->syncProduct->id, true );
        $product->add_meta_data( self::META_KEY_CATALOG_PRODUCT_ID, $catalog_product_id, true );
        $product->add_meta_data( self::META_KEY_STORE_ID, (string) $store_id, true );

        $product->save();

        $product_images = $this->add_product_images( $printful_product );

        $product->set_gallery_image_ids( $product_images );

        if ( ! empty( $product_images ) ) {
            $product->set_image_id( reset( $product_images ) );
        }

        $product->save();

        foreach ( $printful_product->syncVariants as $variant ) {
            try {
                $this->create_product_variation( $product, $variant, $product_images, $store_id );
            } catch ( Exception $e ) {
                dokan_log(
                    sprintf(
                    // translators: 1: Product ID 2: External Product ID 3: Vendor ID 4: Error message.
                        esc_html__(
                            'Product Variant Sync failed for webhook processing. Product ID: %1$d, External Product ID: %2$d, Product Variant ID: %3$d,  Vendor ID: %4$d. Error: %5$s',
                            'dokan'
                        ),
                        $product_id,
                        $external_id,
                        $variant->id,
                        $this->vendor_id,
                        $e->getMessage()
                    )
                );
            }
        }

        $this->remove_orphan_variations( $product, $printful_product );
        $product->set_status( $previous_status );
        $product->set_stock_status();
        $product->save();

        $this->update_author( $product->get_id() );

        WC()->queue()->add(
            'dokan_printful_product_add_size_guide',
            [
                'product_id' => $product->get_id(),
                'catalog_id' => $catalog_product_id,
                'vendor_id'  => $this->vendor_id,
            ],
            'dokan_printful'
        );

        do_action( 'dokan_pro_printful_product_synced', $product, $this->vendor_id );
    }

    /**
     * Create a product variation for a defined variable product ID.
     *
     * @since 3.13.0
     *
     * @param WC_Product          $product Product parent variable product.
     * @param SyncVariantResponse $variation_data The data to insert in the product.
     * @param array<int, int>     $images Images.
     * @param int     $store_id  Store ID.
     *
     * @return WC_Product_Variation
     * @throws WC_Data_Exception If product is already exist.
     */
    protected function create_product_variation( WC_Product $product, SyncVariantResponse $variation_data, array $images, int $store_id ): WC_Product_Variation {
        $associated_variation = $this->get_associated_product_or_variation( $variation_data->id, 'variation' );

        $data_attributes = []; // Initialize an array to hold attributes for the variation.
        $external_id     = $associated_variation ? $associated_variation->ID : 0;
        $variation       = new WC_Product_Variation( $external_id );

        $variation->set_parent_id( $product->get_id() );
        // Check if the variation data includes 'size' and add it to the attributes array.
        if ( ! empty( $variation_data->size ) ) {
            $size = sanitize_title( esc_html__( 'Size', 'dokan' ) );
            $data_attributes[ $size ] = $variation_data->size;
        }

        // Check if the variation data includes 'color' and add it to the attributes array.
        if ( ! empty( $variation_data->color ) ) {
            $color = sanitize_title( esc_html__( 'Color', 'dokan' ) );
            $data_attributes[ $color ] = $variation_data->color;
        }

        // If there are attributes to set, update the variation with these attributes.
        if ( ! empty( $data_attributes ) ) {
            $variation->set_attributes( $data_attributes );
        }

        $variation->set_status( 'publish' );
        if ( isset( $variation_data->sku ) ) {
            try {
                $variation->set_sku( $variation_data->sku );
            } catch ( WC_Data_Exception $e ) {
                $variation->set_sku( $e->getErrorData()['unique_sku'] );
            }
        }

        $variation->set_price( (string) $variation_data->retailPrice );
        $variation->set_regular_price( (string) $variation_data->retailPrice );
        $variation->set_stock_status();

        if ( ! empty( $images[ $variation_data->id ] ) ) {
            $variation->set_image_id( $images[ $variation_data->id ] );
        }

        $variation->add_meta_data( self::META_KEY_PRODUCT_VARIATION_ID, (string) $variation_data->id, true );
        $variation->add_meta_data( self::META_KEY_PRODUCT_EXTERNAL_VARIATION_ID, $variation_data->externalId, true );
        $variation->add_meta_data( self::META_KEY_STORE_ID, (string) $store_id, true );

        $variation->save();
        $this->update_author( $variation->get_id() );

        return $variation;
    }

    /**
     * Create attributes for a product variation.
     *
     * @since 3.13.0
     *
     * @param SyncVariantResponse[] $variations The data to insert in the variation.
     *
     * @return WC_Product_Attribute[]
     */
    protected function create_attributes( array $variations ): array {
        $attributes = [];
        $data       = [
            'color' => [
                'name'    => esc_html__( 'Color', 'dokan' ),
                'options' => [],
            ],
            'size'  => [
                'name'    => esc_html__( 'Size', 'dokan' ),
                'options' => [],
            ],
        ];

        // Loop through each variation to collect unique colors and sizes.
        foreach ( $variations as $variation ) {
            // Safely retrieve color and size from each variation.
            $variation_color = $variation->color ?? '';
            $variation_size  = $variation->size ?? '';

            // Add unique colors to the data array if not already present.
            if ( $variation_color && ! in_array( $variation_color, $data['color']['options'], true ) ) {
                $data['color']['options'][] = $variation_color;
            }

            // Add unique sizes to the data array if not already present.
            if ( $variation_size && ! in_array( $variation_size, $data['size']['options'], true ) ) {
                $data['size']['options'][] = $variation_size;
            }
        }

        foreach ( $data as $attr_slug => $attr ) {
            // Continue if there are no options for the attribute.
            if ( empty( $attr['options'] ) ) {
                continue;
            }

            // Create and set properties of the product attribute.
            $attribute = new WC_Product_Attribute();
            $attribute->set_id( 0 );
            $attribute->set_name( $attr['name'] );
            $attribute->set_options( $attr['options'] );
            $attribute->set_visible( true );
            $attribute->set_variation( true );

            $attributes[] = $attribute;
        }

        return $attributes;
    }

    /**
     * Get WC product associated with a Printful product.
     *
     * @since 3.13.0
     *
     * @phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
     *
     * @param int    $product_id Printful product ID.
     * @param string $type       Product type.
     *
     * @return WP_Post|false
     */
    public function get_associated_product_or_variation( int $product_id, string $type = 'product' ) {
        $key = 'dokan_printful_product_variation_id';
        $post_type = 'product_variation';

        if ( 'product' === $type ) {
            $key = 'dokan_printful_product_id';
            $post_type = 'product';
        }

        $product_query = dokan()->product->all(
            [
                'posts_per_page' => 1,
                'post_type'      => $post_type,
                'author'         => $this->vendor_id,
                'meta_query'     => [
                    [
                        'key'   => $key,
                        'value' => absint( $product_id ),
                    ],
                ],
            ]
        );

        if ( $product_query->have_posts() ) {
            return $product_query->get_posts()[0];
        }

        return false;
    }

    /**
     * Get Printful Products Client.
     *
     * @since 3.13.0
     *
     * @return ProductsClient
     * @throws PrintfulException|Exception
     */
    public function get_printful_products_client(): ProductsClient {
        $auth            = new Auth( $this->vendor_id );
        $printful_client = PrintfulApiClient::createOauthClient( $auth->get_access_token() );

        return new ProductsClient( $printful_client );
    }

    /**
     * Add product images.
     *
     * @since 3.13.0
     *
     * @param SyncProductRequestResponse $printful_product Printful product Response.
     *
     * @return int[]
     */
    protected function add_product_images( SyncProductRequestResponse $printful_product ): array {
        $product_images = [];

        foreach ( $printful_product->syncVariants as $variant ) {
            if ( empty( $variant->files ) ) {
                continue;
            }

            foreach ( $variant->files as $file ) {
                if ( 'preview' !== $file->type || empty( $file->previewUrl ) ) {
                    continue;
                }

                $image_id = $this->upload_image( $file->previewUrl );

                if ( ! $image_id ) {
                    continue;
                }

                $product_images[ $variant->id ] = $image_id;
            }
        }

        return $product_images;
    }

    /**
     * Remove all images associated with a product.
     *
     * @since 3.13.0
     *
     * @param int $product_id Product id.
     *
     * @return void
     */
    protected function clean_product_old_images( int $product_id ): void {
        $product = $product_id ? wc_get_product( $product_id ) : '';

        // Return if product not found.
        if ( ! $product ) {
            return;
        }

        // Remove featured image
        $featured_image_id = $product->get_image_id();
        if ( $featured_image_id ) {
            wp_delete_attachment( $featured_image_id, true );
            $product->set_image_id( '' );
        }

        // Remove gallery images
        $gallery_image_ids = $product->get_gallery_image_ids();
        foreach ( $gallery_image_ids as $image_id ) {
            wp_delete_attachment( $image_id, true );
        }

        // Set gallery images empty.
        $product->set_gallery_image_ids( array() );

        // Remove images from variations
        if ( $product->is_type( 'variable' ) ) {
            $variations = $product->get_available_variations();
            foreach ( $variations as $variation ) {
                $variation_obj = wc_get_product( $variation['variation_id'] );
                if ( $variation_obj ) {
                    $variation_image_id = $variation_obj->get_image_id();
                    if ( $variation_image_id ) {
                        wp_delete_attachment( $variation_image_id, true );
                        $variation_obj->set_image_id( '' );
                        $variation_obj->save();
                    }
                }
            }
        }

        // Save the product to persist changes
        $product->save();
    }

    /**
     * Upload image from url.
     *
     * @since 3.13.0
     *
     * @param string $url Image url.
     *
     * @return false|int
     */
    protected function upload_image( string $url ) {
        if ( ! function_exists( 'download_url' ) || ! function_exists( 'wp_handle_sideload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        if ( ! function_exists( 'wp_update_attachment_metadata' ) || ! function_exists( 'wp_generate_attachment_metadata' ) ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        try {
            // download to temp dir
            $temp_file = download_url( $url );

            if ( is_wp_error( $temp_file ) ) {
                return false;
            }

            // move the temp file into the uploads directory
            $file = array(
                'name'     => basename( $url ),
                'type'     => mime_content_type( $temp_file ),
                'tmp_name' => $temp_file,
                'size'     => filesize( $temp_file ),
            );
            $sideload = wp_handle_sideload(
                $file,
                array(
                    'test_form'   => false, // no needs to check 'action' parameter
                )
            );

            if ( ! empty( $sideload['error'] ) ) {
                wp_delete_file( $temp_file );
                // you may return error message if you want
                return false;
            }

            // it is time to add our uploaded image into WordPress media library
            $attachment_id = wp_insert_attachment(
                array(
                    'guid'           => $sideload['url'],
                    'post_mime_type' => $sideload['type'],
                    'post_title'     => basename( $sideload['file'] ),
                    'post_content'   => '',
                    'post_status'    => 'inherit',
                ),
                $sideload['file']
            );

            if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
                wp_delete_file( $temp_file );
                return false;
            }

            // update medatata, regenerate image sizes
            wp_update_attachment_metadata(
                $attachment_id,
                wp_generate_attachment_metadata( $attachment_id, $sideload['file'] )
            );
        } catch ( Exception $e ) {
            return false;
        }

        wp_delete_file( $temp_file );

        return $attachment_id;
    }

    /**
     * Update product or variation author.
     *
     *
     * @param int $product Product or Variation ID.
     *
     * @return int|\WP_Error
     */
    protected function update_author( int $product ) {
        return wp_update_post(
            [
                'ID'          => $product,
                'post_author' => $this->vendor_id,
            ]
        );
    }

    /**
     * Get connected store ID.
     *
     * @since 3.13.0
     *
     * @return int
     */
    protected function get_connected_store_id(): int {
        try {
            $auth = new Auth( $this->vendor_id );
            $store_id = $auth->get_store_info()['id'];
        } catch ( Exception $e ) {
            $store_id = 0;
        }

        return $store_id;
    }

    /**
     * Remove orphan variations that are not present in the Printful product sync data.
     *
     * @since 3.13.0
     *
     * @param WC_Product                 $product          The WooCommerce product object.
     * @param SyncProductRequestResponse $printful_product The Printful product sync response.
     *
     * @return void
     */
    protected function remove_orphan_variations( WC_Product $product, SyncProductRequestResponse $printful_product ): void {
        $product_variations = $product->get_children();

        // List of printful variation ids.
        $synced_variations = array_map(
            function ( $variant ) {
                return $variant->id;
            },
            $printful_product->syncVariants
        );

        foreach ( $product_variations as $variation_id ) {
            $variation = wc_get_product( $variation_id );

            if ( ! $variation ) {
                continue;
            }

            $variation_id = $variation->get_meta( self::META_KEY_PRODUCT_VARIATION_ID );
            $found        = $variation_id && ! in_array( (int) $variation_id, $synced_variations, true );

            // Remove orphan variations.
            if ( $found ) {
                $variation->delete();
            }
        }
    }
}
