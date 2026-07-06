<?php

use WeDevs\Dokan\ProductCategory\Helper;

/**
 * Template shortcode class file
 *
 * @load all shortcode for template  rendering
 */
class Dokan_Template_Auction {

    public static $errors;
    public static $post_content;
    public static $validated;
    public static $validate;

	/**
	 * Meta-key for auction extension enable setting
	 *
	 * @since 4.1.2
	 */
	protected const META_AUCTION_EXTEND_ENABLE = '_auction_extend_enable';

	/**
	 * Meta-key for auction extension in time setting
	 *
	 * @since 4.1.2
	 */
	protected const META_AUCTION_EXTEND_IN_TIME = '_auction_extend_in_time';

	/**
	 * Meta-key for auction extension for time setting
	 *
	 * @since 4.1.2
	 */
	protected const META_AUCTION_EXTEND_FOR_TIME = '_auction_extend_for_time';

	/**
     * __construct function
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action( 'template_redirect', [ $this, 'auction_handle_all_submit' ], 11 );
        add_action( 'template_redirect', [ $this, 'handle_auction_product_delete' ] );
        add_action( 'template_redirect', [ $this, 'handle_auction_product_duplicate' ] );
        add_action( 'dokan_auction_after_general_options', [ $this, 'load_attribute_options' ], 12 );
        add_action( 'dokan_auction_after_general_options', [ $this, 'load_shipping_options' ], 13 );

        // Remove `load_inventory_template` hook and add inventory template only for auction product
        add_action( 'init', [ $this, 'replace_auction_inventory_template' ], 10, 2 );
    }

    /**
     * Initializes the Dokan_Template_Auction() class
     *
     * Checks for an existing Dokan_Template_Auction() instance
     * and if it doesn't find one, creates it.
     */
    public static function init() {
        static $instance = false;

        if ( ! $instance ) {
            $instance = new Dokan_Template_Auction();
        }

        return $instance;
    }

    /**
     * Load attribute templates
     *
     * @since 1.5.2
     *
     * @return void
     **/
    public function load_attribute_options( $post_id ) {
        $product_attributes   = get_post_meta( $post_id, '_product_attributes', true );
        $attribute_taxonomies = wc_get_attribute_taxonomies();

        dokan_get_template_part(
            'auction/html-auction-attribute', '', array(
                'is_auction'           => true,
                'post_id'              => $post_id,
                'product_attributes'   => $product_attributes,
                'attribute_taxonomies' => $attribute_taxonomies,
            )
        );
    }

    /**
     * Load Shipping templates
     *
     * @since 1.5.2
     *
     * @return void
     **/
    public function load_shipping_options( $post_id ) {
        $is_shipping_disabled = false;

        if ( 'sell_digital' === dokan_pro()->digital_product->get_selling_product_type() ) {
            $is_shipping_disabled = true;
        }

        dokan_get_template_part(
            'auction/auction-shipping', '', array(
                'is_auction'           => true,
                'post_id'              => $post_id,
                'is_shipping_disabled' => $is_shipping_disabled,
            )
        );
    }

    /**
     * Saving handle for auction data
     *
     * @since  1.0.0
     *
     * @return void
     */
    public function auction_handle_all_submit() {
        if ( ! is_user_logged_in() ) {
            return;
        }

        if ( ! dokan_is_user_seller( get_current_user_id() ) ) {
            return;
        }

        $errors = array();
        self::$post_content = '';
        $request_data = $_POST; // phpcs:ignore
        if ( ! $request_data ) {
            return;
        }

        $data = wp_unslash( $request_data );
        $data['product_type'] = 'auction';

        global $woocommerce_auctions;

        if ( isset( $request_data['add_auction_product'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $request_data['dokan_add_new_auction_product_nonce'] ) ), 'dokan_add_new_auction_product' ) ) {
            if ( ! current_user_can( 'dokan_add_auction_product' ) ) {
                return;
            }
            $post_title     = isset( $request_data['post_title'] ) ? trim( wc_clean( wp_unslash( $request_data['post_title'] ) ) ) : '';
            $post_content   = isset( $request_data['post_content'] ) ? wp_kses_post( $request_data['post_content'] ) : '';
            $post_excerpt   = isset( $request_data['post_excerpt'] ) ? wp_kses_post( $request_data['post_excerpt'] ) : '';
            $chosen_product_cat    = isset( $request_data['chosen_product_cat'] ) ? wc_clean( wp_unslash( $request_data['chosen_product_cat'] ) ) : [];
            $featured_image = isset( $request_data['feat_image_id'] ) ? absint( wp_unslash( $request_data['feat_image_id'] ) ) : '';

            if ( empty( $post_title ) ) {
                $errors[] = __( 'Please enter product title', 'dokan' );
            }

            if ( count( $chosen_product_cat ) < 1 ) {
                $errors[] = __( 'Please select a category', 'dokan' );
            }

            self::$errors = apply_filters( 'dokan_can_add_product', $errors );

            if ( ! self::$errors ) {
                $product_status = dokan_get_default_product_status();
                $post_data = apply_filters(
                    'dokan_insert_auction_product_post_data', array(
						'post_type'    => 'product',
						'post_status'  => $product_status,
						'post_title'   => $post_title,
						'post_content' => $post_content,
						'post_excerpt' => $post_excerpt,
						'post_author'  => dokan_get_current_user_id(),
                    )
                );

                $product_id = wp_insert_post( $post_data );

                if ( $product_id ) {

                    // Set featured images
                    if ( $featured_image ) {
                        set_post_thumbnail( $product_id, $featured_image );
                    }

                    // Set Gallery Images
                    if ( ! empty( $request_data['product_image_gallery'] ) ) {
                        $attachment_ids = array_filter( explode( ',', wc_clean( $request_data['product_image_gallery'] ) ) );
                        update_post_meta( $product_id, '_product_image_gallery', implode( ',', $attachment_ids ) );
                    }

                    /** Set product category * */

                    if ( ! empty( $request_data['chosen_product_cat'] ) ) {
                        $chosen_cat = Helper::product_category_selection_is_single() ? [ reset( $request_data['chosen_product_cat'] ) ] : $request_data['chosen_product_cat'];
                    } else {
                        $chosen_cat = [ absint( get_option( 'default_product_cat' ) ) ];
                    }
                    Helper::set_object_terms_from_chosen_categories( $product_id, $chosen_cat );

                    // Set Product tags
                    if ( isset( $request_data['product_tag'] ) ) {
                        $tags_ids = array_map( 'intval', (array) $request_data['product_tag'] );
                    } else {
                        $tags_ids = array();
                    }
                    wp_set_object_terms( $product_id, $tags_ids, 'product_tag' );

                    // Set product type
                    wp_set_object_terms( $product_id, 'auction', 'product_type' );

                    // Save downloadable
                    $is_downloadable = isset( $data['_downloadable'] ) ? 'yes' : 'no';

                    $auction_product = new \WC_Product_Auction( $product_id );

                    $auction_product->set_downloadable( $is_downloadable );

                    // Downloadable options
                    if ( 'yes' === $is_downloadable ) {

                        // file paths will be stored in an array keyed off md5(file path)
                        if ( ! empty( $data['_wc_file_urls'] ) ) {
                            $files = [];

                            $file_names    = ! empty( $data['_wc_file_names'] ) ? array_map( 'sanitize_file_name', $data['_wc_file_names'] ) : [];
                            $file_urls     = array_map( 'esc_url_raw', array_map( 'trim', $data['_wc_file_urls'] ) );

                            foreach ( $file_urls as $index => $url ) {
                                // Skip invalid URLs
                                if ( empty( $url ) ) {
                                    continue;
                                }

                                // Parse URL and check if it's in an approved directory
                                $parsed_url = wp_parse_url( $url );
                                $approved_dir = 'wp-content/uploads/woocommerce_uploads';

                                if ( empty( $parsed_url['path'] ) || strpos( $parsed_url['path'], $approved_dir ) === false ) {
                                    continue;
                                }
                                $files[] = [
                                    'download_id' => md5( $file_urls[ $index ] ),
                                    'name'        => $file_names[ $index ],
                                    'file'        => $url,
                                ];
                            }

                            // grant permission to any newly added files on any existing orders for this product prior to saving
                            $variation_id = 0;

                            do_action( 'dokan_process_file_download', $product_id, $variation_id, $files );

                            $auction_product->set_downloads( $files );
                        } else {
                            $auction_product->set_downloads( [] );
                        }

                        if ( ! empty( $data['_download_limit'] ) ) {
                            $download_limit = absint( wp_unslash( $data['_download_limit'] ) );

                            $auction_product->set_download_limit( $download_limit );
                        }

                        if ( ! empty( $data['_download_expiry'] ) ) {
                            $download_expiry = absint( wp_unslash( $data['_download_expiry'] ) );

                            $auction_product->set_download_expiry( $download_expiry );
                        }

                        if ( ! empty( $data['_download_type'] ) ) {
                            $download_type = wc_clean( wp_unslash( $data['_download_type'] ) );

                            $auction_product->update_meta_data( '_download_type', $download_type );
                        }
                    }

                    // Virtual options
                    $is_virtual = isset( $data['_virtual'] ) ? 'yes' : 'no';

                    $auction_product->set_virtual( $is_virtual );

                    // Dimensions
                    if ( 'no' === $is_virtual ) {
                        if ( ! empty( $data['_weight'] ) ) {
                            $weight = wc_format_decimal( wp_unslash( $data['_weight'] ) );

                            $auction_product->set_weight( $weight );
                        }

                        if ( ! empty( $data['_length'] ) ) {
                            $length = wc_format_decimal( wp_unslash( $data['_length'] ) );

                            $auction_product->set_length( $length );
                        }

                        if ( ! empty( $data['_width'] ) ) {
                            $width = wc_format_decimal( wp_unslash( $data['_width'] ) );

                            $auction_product->set_width( $width );
                        }

                        if ( ! empty( $data['_height'] ) ) {
                            $height = wc_format_decimal( wp_unslash( $data['_height'] ) );

                            $auction_product->set_height( $height );
                        }
                    }

                    //Save shipping meta data
                    $disable_shipping = ! empty( $post_data['_disable_shipping'] ) ? sanitize_text_field( wp_unslash( $post_data['_disable_shipping'] ) ) : 'no';

                    // _disable_shipping does not have setter method
                    $auction_product->update_meta_data( '_disable_shipping', $disable_shipping );

                    // Save shipping class
                    $shipping_class_id = ( isset( $data['product_shipping_class'] ) && $data['product_shipping_class'] > 0 && 'external' !== $data['product_type'] ) ? absint( wp_unslash( $data['product_shipping_class'] ) ) : '';

                    $auction_product->set_shipping_class_id( $shipping_class_id );

                    if ( isset( $data['_tax_status'] ) ) {
                        $_tax_status = sanitize_text_field( wp_unslash( $data['_tax_status'] ) );

                        $auction_product->set_tax_status( $_tax_status );
                    }

                    if ( isset( $data['_tax_class'] ) ) {
                        $_tax_class = sanitize_text_field( wp_unslash( $data['_tax_class'] ) );

                        $auction_product->set_tax_class( $_tax_class );
                    }

                    $auction_product->save();

                    $woocommerce_auctions->product_save_data( $product_id, get_post( $product_id ) );

                    do_action( 'dokan_new_auction_product_added', $product_id, $post_data );

                    // dokan()->email->new_product_added( $product_id, $product_status );

                    do_action( 'dokan_new_product_added', $product_id, $data );

                    if ( current_user_can( 'dokan_edit_auction_product' ) ) {
                        $redirect_url = add_query_arg(
                            array(
                                'product_id' => $product_id,
                                'action'     => 'edit',
                                'message'    => 'success',
                            ), dokan_get_navigation_url( 'auction' )
                        );
                    } else {
                        $redirect_url = dokan_get_navigation_url( 'auction' );
                    }

                    wp_safe_redirect( $redirect_url );
                    exit;
                }
            }
        }
        // Edit handle in auction product

        if ( isset( $_GET['product_id'] ) ) {
            $post_id = intval( $_GET['product_id'] );
        } else {
            global $post, $product;
            if ( ! empty( $post ) ) {
                $post_id = $post->ID;
            }
        }

        if ( isset( $post_id ) && isset( $request_data['update_auction_product'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $request_data['dokan_edit_auction_product_nonce'] ) ), 'dokan_edit_auction_product' ) ) {
            if ( ! current_user_can( 'dokan_edit_auction_product' ) ) {
                return;
            }
            $is_new_product = get_post_status( $post_id ) === 'auto-draft';

            $product_info = array(
                'ID'             => absint( $post_id ),
                'post_title'     => wc_clean( wp_unslash( $request_data['post_title'] ) ),
                'post_content'   => wp_kses_post( $request_data['post_content'] ),
                'post_excerpt'   => wp_kses_post( $request_data['post_excerpt'] ),
                'post_status'    => 'publish' === $request_data['post_status'] ? dokan_get_default_product_status( dokan_get_current_user_id() ) : sanitize_text_field( wp_unslash( $request_data['post_status'] ) ),
                'comment_status' => isset( $request_data['_enable_reviews'] ) ? 'open' : 'closed',
            );

            wp_update_post( $product_info );

            /** Set product category * */
            if ( ! empty( $request_data['chosen_product_cat'] ) ) {
                $chosen_cat = Helper::product_category_selection_is_single() ? [ reset( $request_data['chosen_product_cat'] ) ] : $request_data['chosen_product_cat'];
            } else {
                $chosen_cat = [ absint( get_option( 'default_product_cat' ) ) ];
            }
            Helper::set_object_terms_from_chosen_categories( $post_id, $chosen_cat );

            wp_set_object_terms( $post_id, 'auction', 'product_type' );

            /** Set Product tags */
            if ( isset( $request_data['product_tag'] ) ) {
                $tags_ids = array_map( 'intval', (array) $request_data['product_tag'] );
            } else {
                $tags_ids = array();
            }
            wp_set_object_terms( $post_id, $tags_ids, 'product_tag' );

            // Handle visibility ( with WC 3.0.0+ compatibility )
            $terms = array();
            $_visibility = isset( $request_data['_visibility'] ) ? wc_clean( wp_unslash( $request_data['_visibility'] ) ) : '';
            switch ( $_visibility ) {
                case 'hidden':
                    $terms[] = 'exclude-from-search';
                    $terms[] = 'exclude-from-catalog';
                    break;
                case 'catalog':
                    $terms[] = 'exclude-from-search';
                    break;
                case 'search':
                    $terms[] = 'exclude-from-catalog';
                    break;
            }

            wp_set_post_terms( $post_id, $terms, 'product_visibility', false );
            update_post_meta( $post_id, '_visibility', $_visibility );

            /** Set images **/
            $featured_image = absint( wp_unslash( $request_data['feat_image_id'] ) );
            if ( $featured_image ) {
                set_post_thumbnail( $post_id, $featured_image );
            } else {
                delete_post_thumbnail( $post_id );
            }

            // Gallery Images
            $attachment_ids = array_filter( explode( ',', wc_clean( $request_data['product_image_gallery'] ) ) );
            update_post_meta( $post_id, '_product_image_gallery', implode( ',', $attachment_ids ) );

            $woocommerce_auctions->product_save_data( $post_id, get_post( $post_id ) );

            // Save Attributes
            $attributes = array();

            if ( isset( $request_data['attribute_names'] ) && isset( $request_data['attribute_values'] ) ) {
                $attribute_names  = wc_clean( wp_unslash( $request_data['attribute_names'] ) );
                $attribute_values = wc_clean( wp_unslash( $request_data['attribute_values'] ) );

                if ( isset( $request_data['attribute_visibility'] ) ) {
                    $attribute_visibility = wc_clean( wp_unslash( $request_data['attribute_visibility'] ) );
                }

                if ( isset( $request_data['attribute_variation'] ) ) {
                    $attribute_variation = wc_clean( wp_unslash( $request_data['attribute_variation'] ) );
                }

                $attribute_is_taxonomy   = wc_clean( wp_unslash( $request_data['attribute_is_taxonomy'] ) );
                $attribute_position      = wc_clean( wp_unslash( $request_data['attribute_position'] ) );
                $attribute_names_max_key = max( array_keys( $attribute_names ) );

                for ( $i = 0; $i <= $attribute_names_max_key; $i++ ) {
                    if ( empty( $attribute_names[ $i ] ) ) {
                        continue;
                    }

                    $is_visible   = isset( $attribute_visibility[ $i ] ) ? 1 : 0;
                    $is_variation = isset( $attribute_variation[ $i ] ) ? 1 : 0;
                    $is_taxonomy  = $attribute_is_taxonomy[ $i ] ? 1 : 0;

                    if ( $is_taxonomy ) {
                        $values_are_slugs = false;

                        if ( isset( $attribute_values[ $i ] ) ) {

                            // Select based attributes - Format values (posted values are slugs)
                            if ( is_array( $attribute_values[ $i ] ) ) {
                                $values           = array_map( 'sanitize_title', $attribute_values[ $i ] );
                                $values_are_slugs = true;

                                // Text based attributes - Posted values are term names - don't change to slugs
                            } else {
                                $values = array_map( 'stripslashes', array_map( 'strip_tags', explode( WC_DELIMITER, $attribute_values[ $i ] ) ) );
                            }

                            // Remove empty items in the array
                            $values = array_filter(
                                $values, function ( $value ) {
									return strlen( trim( $value ) ) > 0;
								}
                            );
                        } else {
                            $values = array();
                        }

                        // Update post terms
                        if ( taxonomy_exists( $attribute_names[ $i ] ) ) {
                            foreach ( $values as $key => $value ) {
                                $term = get_term_by( $values_are_slugs ? 'slug' : 'name', trim( $value ), $attribute_names[ $i ] );

                                if ( $term ) {
                                    $values[ $key ] = intval( $term->term_id );
                                } else {
                                    $term = wp_insert_term( trim( $value ), $attribute_names[ $i ] );
                                    if ( isset( $term->term_id ) ) {
                                        $values[ $key ] = intval( $term->term_id );
                                    }
                                }
                            }

                            wp_set_object_terms( $post_id, $values, $attribute_names[ $i ] );
                        }

                        if ( ! empty( $values ) ) {
                            // Add attribute to array, but don't set values
                            $attributes[ sanitize_title( $attribute_names[ $i ] ) ] = array(
                                'name'         => wc_clean( $attribute_names[ $i ] ),
                                'value'        => '',
                                'position'     => $attribute_position[ $i ],
                                'is_visible'   => $is_visible,
                                'is_variation' => $is_variation,
                                'is_taxonomy'  => $is_taxonomy,
                            );
                        }
                    } elseif ( isset( $attribute_values[ $i ] ) ) {

                        // Text based, possibly separated by pipes (WC_DELIMITER). Preserve line breaks in non-variation attributes.
                        $values = implode(
                            ' ' . WC_DELIMITER . ' ',
                            array_map(
                                'sanitize_text_field',
                                array_map(
                                    'stripslashes',
                                    $attribute_values[ $i ]
                                )
                            )
                        );

                        // Custom attribute - Add attribute to array and set the values
                        $attributes[ sanitize_title( $attribute_names[ $i ] ) ] = array(
                            'name'         => wc_clean( $attribute_names[ $i ] ),
                            'value'        => $values,
                            'position'     => $attribute_position[ $i ],
                            'is_visible'   => $is_visible,
                            'is_variation' => $is_variation,
                            'is_taxonomy'  => $is_taxonomy,
                        );
                    }
                }
            }
            uasort( $attributes, 'wc_product_attribute_uasort_comparison' );

            /**
             * Unset removed attributes by looping over previous values and
             * unsetting the terms.
             */
            $old_attributes = array_filter( (array) maybe_unserialize( get_post_meta( $post_id, '_product_attributes', true ) ) );

            if ( ! empty( $old_attributes ) ) {
                foreach ( $old_attributes as $key => $value ) {
                    if ( empty( $attributes[ $key ] ) && ! empty( $value['is_taxonomy'] ) && taxonomy_exists( $key ) ) {
                        wp_set_object_terms( $post_id, array(), $key );
                    }
                }
            }

            update_post_meta( $post_id, '_product_attributes', $attributes );

            // Dimensions
            if ( isset( $request_data['_weight'] ) ) {
                update_post_meta( $post_id, '_weight', ( '' === $request_data['_weight'] ) ? '' : wc_format_decimal( wc_clean( wp_unslash( $request_data['_weight'] ) ) ) );
            }

            if ( isset( $request_data['_length'] ) ) {
                update_post_meta( $post_id, '_length', ( '' === $request_data['_length'] ) ? '' : wc_format_decimal( wc_clean( wp_unslash( $request_data['_length'] ) ) ) );
            }

            if ( isset( $request_data['_width'] ) ) {
                update_post_meta( $post_id, '_width', ( '' === $request_data['_width'] ) ? '' : wc_format_decimal( wc_clean( wp_unslash( $request_data['_width'] ) ) ) );
            }

            if ( isset( $request_data['_height'] ) ) {
                update_post_meta( $post_id, '_height', ( '' === $request_data['_height'] ) ? '' : wc_format_decimal( wc_clean( wp_unslash( $request_data['_height'] ) ) ) );
            }

            //Save shipping meta data
            update_post_meta( $post_id, '_disable_shipping', stripslashes( isset( $request_data['_disable_shipping'] ) ? wc_clean( wp_unslash( $request_data['_disable_shipping'] ) ) : 'no' ) );

            if ( isset( $request_data['_overwrite_shipping'] ) && $request_data['_overwrite_shipping'] === 'yes' ) {
                update_post_meta( $post_id, '_overwrite_shipping', stripslashes( $request_data['_overwrite_shipping'] ) );
            } else {
                update_post_meta( $post_id, '_overwrite_shipping', 'no' );
            }

            update_post_meta( $post_id, '_additional_price', stripslashes( isset( $request_data['_additional_price'] ) ? wc_clean( wp_unslash( $request_data['_additional_price'] ) ) : '' ) );
            update_post_meta( $post_id, '_additional_qty', stripslashes( isset( $request_data['_additional_qty'] ) ? wc_clean( wp_unslash( $request_data['_additional_qty'] ) ) : '' ) );
            update_post_meta( $post_id, '_dps_processing_time', stripslashes( isset( $request_data['_dps_processing_time'] ) ? wc_clean( wp_unslash( $request_data['_dps_processing_time'] ) ) : '' ) );

            $auction_dates_to        = isset( $request_data['_auction_dates_to'] ) ? sanitize_text_field( wp_unslash( $request_data['_auction_dates_to'] ) ) : '';
            $auction_dates_from      = isset( $request_data['_auction_dates_from'] ) ? sanitize_text_field( wp_unslash( $request_data['_auction_dates_from'] ) ) : '';

            if ( ! empty( $request_data['_relist_auction_dates_from'] ) && ! empty( $request_data['_relist_auction_dates_to'] ) ) {
                // Set relisted dates data.
                $auction_dates_to   = sanitize_text_field( wp_unslash( $request_data['_relist_auction_dates_to'] ) );
                $auction_dates_from = sanitize_text_field( wp_unslash( $request_data['_relist_auction_dates_from'] ) );

                // Update auction relisted data.
                $this->relist_auction( $post_id, $auction_dates_to, $auction_dates_from );
            }

            // Update auction date.
            update_post_meta( $post_id, '_auction_dates_to', $auction_dates_to );
            update_post_meta( $post_id, '_auction_dates_from', $auction_dates_from );

            // Update auction extension settings
            $result = $this->update_auction_extension_settings( $post_id, $request_data );
            if ( is_wp_error( $result ) ) {
                dokan_get_template_part(
					'global/dokan-error',
					'',
					[
	                    'deleted' => true,
	                    'message' => $result->get_error_message(),
                    ]
                );
                return;
            }

            if ( strtotime( $auction_dates_to ) >= time() ) {
                delete_post_meta( $post_id, '_auction_closed' );
            }
            $product_type = isset( $request_data['product_type'] ) ? wc_clean( wp_unslash( $request_data['product_type'] ) ) : '';
            // Save shipping class
            $product_shipping_class = ( isset( $request_data['product_shipping_class'] ) && $request_data['product_shipping_class'] > 0 && 'external' !== $product_type ) ? absint( wp_unslash( $request_data['product_shipping_class'] ) ) : '';
            wp_set_object_terms( $post_id, $product_shipping_class, 'product_shipping_class' );

            // Update SKU
            $old_sku = get_post_meta( $post_id, '_sku', true );
            delete_post_meta( $post_id, '_sku' );

            $product = wc_get_product( $post_id );

            $sku = trim( wp_unslash( $request_data['_sku'] ) ) !== '' ? sanitize_text_field( wp_unslash( $request_data['_sku'] ) ) : '';
            try {
                $product->set_sku( $sku );
            } catch ( \WC_Data_Exception $e ) {
                wc_add_notice( __( 'Same SKU can not be set for multiple products! The SKU has been restored to previous one.', 'dokan' ), 'error' );
                $product->set_sku( $old_sku );
            }

            // Save virtual
            $_virtual   = isset( $request_data['_virtual'] ) ? wc_clean( wp_unslash( $request_data['_virtual'] ) ) : '';
            $is_virtual = 'on' === $_virtual ? 'yes' : 'no';
            $product->set_virtual( $is_virtual );

            // Save downloadable
            $is_downloadable   = isset( $request_data['_downloadable'] ) ? wc_clean( wp_unslash( $request_data['_downloadable'] ) ) : '';
            $is_downloadable = 'on' === $is_downloadable ? 'yes' : 'no';
            $product->set_downloadable( $is_downloadable );

            // Downloadable options
            if ( 'yes' === $is_downloadable ) {

                // file paths will be stored in an array keyed off md5(file path)
                if ( ! empty( $data['_wc_file_urls'] ) ) {
                    $files = [];

                    $file_names    = ! empty( $data['_wc_file_names'] ) ? array_map( 'sanitize_file_name', $data['_wc_file_names'] ) : [];
                    $file_urls     = array_map( 'esc_url_raw', array_map( 'trim', $data['_wc_file_urls'] ) );

                    foreach ( $file_urls as $index => $url ) {
                        // Skip invalid URLs
                        if ( empty( $url ) ) {
                            continue;
                        }

                        // Parse URL and check if it's in an approved directory
                        $parsed_url = wp_parse_url( $url );
                        $approved_dir = 'wp-content/uploads/woocommerce_uploads';

                        if ( empty( $parsed_url['path'] ) || strpos( $parsed_url['path'], $approved_dir ) === false ) {
                            continue;
                        }
                        $files[] = [
                            'download_id' => md5( $file_urls[ $index ] ),
                            'name'        => $file_names[ $index ],
                            'file'        => $url,
                        ];
                    }

                    // grant permission to any newly added files on any existing orders for this product prior to saving
                    $variation_id = 0;

                    do_action( 'dokan_process_file_download', $post_id, $variation_id, $files );

                    $product->set_downloads( $files );
                } else {
                    $product->set_downloads( [] );
                }

                $_download_limit = isset( $data['_download_limit'] ) ? wp_unslash( $data['_download_limit'] ) : '';
                if ( ! $_download_limit || -1 === $_download_limit ) {
                    $_download_limit = ''; // 0 or blank = unlimited
                }
                $product->set_download_limit( $_download_limit );

                $_download_expiry = isset( $data['_download_expiry'] ) ? intval( wp_unslash( $data['_download_expiry'] ) ) : '';
                if ( ! $_download_expiry || -1 === $_download_expiry ) {
                    $_download_expiry = ''; // 0 or blank = unlimited
                }
                $product->set_download_expiry( $_download_expiry );

                if ( ! empty( $data['_download_type'] ) ) {
                    $download_type = wc_clean( wp_unslash( $data['_download_type'] ) );

                    $product->update_meta_data( '_download_type', $download_type );
                }
            }
            if ( isset( $data['_tax_status'] ) ) {
                $_tax_status = sanitize_text_field( wp_unslash( $data['_tax_status'] ) );

                $product->set_tax_status( $_tax_status );
            }

            if ( isset( $data['_tax_class'] ) ) {
                $_tax_class = sanitize_text_field( wp_unslash( $data['_tax_class'] ) );

                $product->set_tax_class( $_tax_class );
            }
            $product->save();
            if ( $is_new_product ) {
                do_action( 'dokan_new_auction_product_added', $post_id, $product_info );
                do_action( 'dokan_new_product_added', $post_id, $data );
            } else {
                do_action( 'dokan_update_auction_product', $post_id, wp_unslash( $request_data ) );
                do_action( 'dokan_product_updated', $post_id, $data );
            }

            $edit_url = add_query_arg(
                array(
                    'product_id' => $post_id,
                    'action'     => 'edit',
                ), dokan_get_navigation_url( 'auction' )
            );
            wp_safe_redirect( add_query_arg( array( 'message' => 'success' ), $edit_url ) );
            exit;
        }
    }

    /**
     * Relist our auction & update relisted data.
     *
     * @since 3.5.0
     *
     * @param int    $post_id
     * @param string $relist_from
     * @param string $relist_to
     *
     * @return void
     */
    public function relist_auction( $post_id, $relist_from, $relist_to ) {
        global $wpdb;

        // Get auction product.
        $auction_product = wc_get_product( $post_id );

        // Set auction metas for updated.
        $update_auction_metas = [
            '_stock'              => '1',
            '_backorders'         => 'no',
            '_stock_status'       => 'instock',
            '_manage_stock'       => 'yes',
            '_auction_dates_to'   => stripslashes( $relist_to ),
            '_auction_relisted'   => current_time( 'mysql' ),
            '_sold_individually'  => 'yes',
            '_auction_dates_from' => stripslashes( $relist_from ),
        ];

        // Set auction metas for deleted.
        $delete_auction_metas = [
            '_stop_mails',
            '_auction_closed',
            '_auction_max_bid',
            '_auction_started',
            '_auction_bid_count',
            '_auction_fail_reason',
            '_auction_current_bid',
            '_auction_current_bider',
            '_auction_max_current_bider',
        ];

        // Update auction metas for relisted auction.
        $auction_product->set_props( $update_auction_metas );

        // Delete auction metas for relisted auction.
        foreach ( $delete_auction_metas as $auction_meta_key ) {
            $auction_product->delete_meta_data( $auction_meta_key );
        }

        $order_id = get_post_meta( $post_id, 'order_id', true );
        $order    = wc_get_order( $order_id );

        if ( $order instanceof \WC_Order ) {
            $order->update_status( 'failed', __( 'Failed because off relisting', 'dokan' ) );
            delete_post_meta( $post_id, '_order_id' );
        }

        // @codingStandardsIgnoreLine
        $wpdb->delete( $wpdb->usermeta, [ 'meta_key' => 'wsa_my_auctions', 'meta_value' => $post_id ], [ '%s', '%d' ] );

        /**
         * Fires after auction product relisted.
         *
         * @since 3.5.0
         *
         * @param \WC_Product $auction_product
         * @param string      $relist_from
         * @param string      $relist_to
         */
        do_action( 'dokan_do_simple_auction_relist', $auction_product, $relist_from, $relist_to );
    }

    /**
     * Handle auction product delete
     *
     * @since  1.0.0
     *
     * @return void
     */
    public function handle_auction_product_delete() {
        if ( ! is_user_logged_in() ) {
            return;
        }

        if ( ! dokan_is_user_seller( get_current_user_id() ) ) {
            return;
        }

        if ( ! current_user_can( 'dokan_delete_auction_product' ) ) {
            return;
        }

        if ( isset( $_GET['action'] ) && $_GET['action'] === 'dokan-delete-auction-product' ) {
            $product_id = isset( $_GET['product_id'] ) ? intval( $_GET['product_id'] ) : 0;

            if ( ! $product_id ) {
                wp_safe_redirect( add_query_arg( array( 'message' => 'error' ), dokan_get_navigation_url( 'auction' ) ) );
                return;
            }
            if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) ), 'dokan-delete-auction-product' ) ) {
                wp_safe_redirect( add_query_arg( array( 'message' => 'error' ), dokan_get_navigation_url( 'auction' ) ) );
                return;
            }

            if ( ! dokan_is_product_author( $product_id ) ) {
                wp_safe_redirect( add_query_arg( array( 'message' => 'error' ), dokan_get_navigation_url( 'auction' ) ) );
                return;
            }

            wp_delete_post( $product_id );

            /**
             * Fires Auction Product Deleted Action
             *
             * @since 3.4.2
             *
             * @param int $product_id
             */
            do_action( 'dokan_delete_auction_product', $product_id );

            wp_safe_redirect( add_query_arg( array( 'message' => 'product_deleted' ), dokan_get_navigation_url( 'auction' ) ) );
            exit;
        }
    }

    /**
     * Handles product duplicator for auction product
     *
     * @since 3.9.4
     *
     * @return void|WP_Error
     */
    public function handle_auction_product_duplicate() {
        $message = 'product_duplicated';

        if (
            ! isset( $_GET['dokan-duplicate-auction-product-nonce'] )
            || ! dokan_is_user_seller( get_current_user_id() )
            || ! current_user_can( 'dokan_duplicate_auction_product' )
        ) {
            return;
        }

        $product_id = ! empty( $_GET['product_id'] ) ? intval( $_GET['product_id'] ) : 0;

        if ( ! wp_verify_nonce( sanitize_key( $_GET['dokan-duplicate-auction-product-nonce'] ), 'dokan-duplicate-auction-product-' . $product_id ) ) {
            wp_safe_redirect( add_query_arg( array( 'message' => 'error' ), dokan_get_navigation_url( 'auction' ) ) );
            exit;
        }

        $product = wc_get_product( $product_id );

        if ( ! $product ) {
            $message = 'error';
        }

        if ( ! dokan_is_product_author( $product_id ) ) {
            $message = 'error';
        }
        if ( ! $product || ! dokan_is_product_author( $product_id ) ) {
            $message = 'error';
        } else {
            $duplicate_product = new WC_Admin_Duplicate_Product();
            $duplicate_product = $duplicate_product->product_duplicate( $product );
            $duplicate_product->update_meta_data( '_dokan_new_product_email_sent', 'no' );

            /** Setting up auction product thumbnail */
            $thumbnail_id = get_post_thumbnail_id( $product->get_id() );
            set_post_thumbnail( $duplicate_product->get_id(), $thumbnail_id );

            $duplicate_product->set_status( 'draft' );
            $duplicate_product->save();

            /**
             * Fires after auction product successfully duplicated
             *
             * @since 3.9.4
             *
             * @param int $product_id
             */
            do_action( 'dokan_duplicate_auction_product', $product_id );
        }

        wp_safe_redirect( add_query_arg( array( 'message' => $message ), dokan_get_navigation_url( 'auction' ) ) );
        exit;
    }

    /**
     * Replace action inventory template
     *
     * @since  3.2.2
     *
     * @return void
     */
    public function replace_auction_inventory_template() {
        $product_id  = ! empty( $_GET['product_id'] ) ? sanitize_text_field( wp_unslash( $_GET['product_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
        $product    = wc_get_product( $product_id );

        if ( $product && 'auction' === $product->get_type() ) {
            remove_action( 'dokan_product_edit_after_main', [ \WeDevs\Dokan\Dashboard\Templates\Products::class, 'load_inventory_template' ], 5 );
            add_action( 'dokan_product_edit_after_main', [ $this, 'load_inventory_template' ], 5, 2 );
        }
    }

    /**
     * Load inventory template
     *
     * @since  3.2.2
     *
     * @param WP_Post $post
     * @param int $post_id
     *
     * @return void
     */
    public function load_inventory_template( $post, $post_id ) {
        dokan_get_template_part(
            'auction/auction-inventory', '', [
                'post_id'    => $post_id,
                'is_auction' => true,
            ]
        );
    }

    /**
     * Validates auction extension time settings
     *
     * @since 4.1.2
     *
     * @param int $in_time  Time window before the auction end (in seconds)
     * @param int $for_time Time to extend the auction by (in seconds)
     *
     * @return bool True if valid, false otherwise
     */
    protected function validate_extend_times( $in_time, $for_time ) {
        if ( ! is_numeric( $in_time ) || ! is_numeric( $for_time ) ) {
            return false;
        }

        if ( $in_time < 0 || $for_time < 0 ) {
            return false;
        }

        return true;
    }

    /**
     * Update auction extension settings with proper validation
     *
     * @since 4.1.2
     *
     * @param int   $post_id Post ID
     * @param array $data Extension settings data
     *
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function update_auction_extension_settings( $post_id, $data ) {
        // Validate post ID
        if ( ! $post_id || ! get_post( $post_id ) ) {
            return new WP_Error( 'invalid_post', esc_html__( 'Invalid product ID', 'dokan' ) );
        }

        // Get product
        $product = wc_get_product( $post_id );
        if ( ! $product || $product->get_type() !== 'auction' ) {
            return new WP_Error( 'invalid_product', esc_html__( 'Invalid auction product', 'dokan' ) );
        }

        // Validate and sanitize enable setting
        $enable = isset( $data[ self::META_AUCTION_EXTEND_ENABLE ] )
            ? wc_clean( wp_unslash( $data[ self::META_AUCTION_EXTEND_ENABLE ] ) )
            : 'no';
        $enable = in_array( $enable, [ 'yes', 'no' ], true ) ? $enable : 'no';

        // Validate and sanitize time settings
        $in_time = isset( $data[ self::META_AUCTION_EXTEND_IN_TIME ] )
            ? absint( wp_unslash( $data[ self::META_AUCTION_EXTEND_IN_TIME ] ) )
            : 0;
        $for_time = isset( $data[ self::META_AUCTION_EXTEND_FOR_TIME ] )
            ? absint( wp_unslash( $data[ self::META_AUCTION_EXTEND_FOR_TIME ] ) )
            : 0;

        // Additional validation
        if ( $enable === 'yes' ) {
            if ( ! $this->validate_extend_times( $in_time, $for_time ) ) {
                return new WP_Error(
                    'invalid_time',
                    esc_html__( 'Extension time values must be positive', 'dokan' )
                );
            }
        }

        // Update meta-data using WC Product methods
        $product->update_meta_data( self::META_AUCTION_EXTEND_ENABLE, $enable );
        $product->update_meta_data( self::META_AUCTION_EXTEND_IN_TIME, $in_time );
        $product->update_meta_data( self::META_AUCTION_EXTEND_FOR_TIME, $for_time );

        // Save all changes
        $product->save();

        /**
         * Action after auction bidding extended settings are updated
         *
         * @since 4.1.2
         *
         * @param WC_Product $product The auction product
         * @param array      $data    The extension settings data
         */
        do_action( 'dokan_auction_bidding_extended_settings_updated', $product, $data );

        return true;
    }
}

Dokan_Template_Auction::init();
