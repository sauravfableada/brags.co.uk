<?php

function dokan_save_variations( $post_id ) {
    global $woocommerce, $wpdb;

    $attributes = (array) maybe_unserialize( get_post_meta( $post_id, '_product_attributes', true ) );
    update_post_meta( $post_id, '_create_variation', 'yes' );

    $_post_data = wp_unslash( $_POST ); //phpcs:ignore WordPress.Security.NonceVerification.Missing

    if ( isset( $_post_data['variable_sku'] ) ) {
        $variable_post_id               = $_post_data['variable_post_id'];
        $variable_sku                   = $_post_data['variable_sku'];
        $variable_regular_price         = $_post_data['variable_regular_price'];
        $variable_sale_price            = $_post_data['variable_sale_price'];
        $upload_image_id                = $_post_data['upload_image_id'];
        $variable_download_limit        = $_post_data['variable_download_limit'];
        $variable_download_expiry       = $_post_data['variable_download_expiry'];
        $variable_shipping_class        = isset( $_post_data['variable_shipping_class'] ) ? $_post_data['variable_shipping_class'] : [];
        $variable_tax_class             = isset( $_post_data['variable_tax_class'] ) ? $_post_data['variable_tax_class'] : [];
        $variable_menu_order            = $_post_data['variation_menu_order'];
        $variable_sale_price_dates_from = $_post_data['variable_sale_price_dates_from'];
        $variable_sale_price_dates_to   = $_post_data['variable_sale_price_dates_to'];

        $variable_weight          = isset( $_post_data['variable_weight'] ) ? $_post_data['variable_weight'] : [];
        $variable_length          = isset( $_post_data['variable_length'] ) ? $_post_data['variable_length'] : [];
        $variable_width           = isset( $_post_data['variable_width'] ) ? $_post_data['variable_width'] : [];
        $variable_height          = isset( $_post_data['variable_height'] ) ? $_post_data['variable_height'] : [];
        $variable_enabled         = isset( $_post_data['variable_enabled'] ) ? $_post_data['variable_enabled'] : [];
        $variable_is_virtual      = isset( $_post_data['variable_is_virtual'] ) ? $_post_data['variable_is_virtual'] : [];
        $variable_is_downloadable = isset( $_post_data['variable_is_downloadable'] ) ? $_post_data['variable_is_downloadable'] : [];

        $variable_manage_stock = isset( $_post_data['variable_manage_stock'] ) ? $_post_data['variable_manage_stock'] : [];
        $variable_stock        = isset( $_post_data['variable_stock'] ) ? $_post_data['variable_stock'] : [];
        $variable_low_stock_amount        = isset( $_post_data['variable_low_stock_amount'] ) ? $_post_data['variable_low_stock_amount'] : [];
        $variable_backorders   = isset( $_post_data['variable_backorders'] ) ? $_post_data['variable_backorders'] : [];
        $variable_stock_status = isset( $_post_data['variable_stock_status'] ) ? $_post_data['variable_stock_status'] : [];

        $variable_description = isset( $_post_data['variable_description'] ) ? $_post_data['variable_description'] : [];

        $max_loop = max( array_keys( $_post_data['variable_post_id'] ) );

        for ( $i = 0; $i <= $max_loop; $i++ ) {
            if ( ! isset( $variable_post_id[ $i ] ) ) {
                continue;
            }

            $variation_id = absint( $variable_post_id[ $i ] );

            // Checkboxes
            $is_virtual      = isset( $variable_is_virtual[ $i ] ) ? 'yes' : 'no';
            $is_downloadable = isset( $variable_is_downloadable[ $i ] ) ? 'yes' : 'no';
            $post_status     = isset( $variable_enabled[ $i ] ) ? 'publish' : 'private';
            $manage_stock    = isset( $variable_manage_stock[ $i ] ) ? 'yes' : 'no';

            // Update or Add post
            if ( ! $variation_id ) {
                $variation = [
                    'post_content' => '',
                    'post_status'  => $post_status,
                    'post_author'  => get_current_user_id(),
                    'post_parent'  => $post_id,
                    'post_type'    => 'product_variation',
                    'menu_order'   => $variable_menu_order[ $i ],
                ];

                $variation_id = wp_insert_post( $variation );
                $product = wc_get_product( $variation_id );

                do_action( 'woocommerce_create_product_variation', $product->get_id(), $product );
                do_action( 'dokan_create_product_variation', $product->get_id(), $product );
            } else {
                $modified_date = date_i18n( 'Y-m-d H:i:s', current_time( 'timestamp' ) );//phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested

                $wpdb->update(
                    $wpdb->posts,
                    [
                        'post_status'       => $post_status,
                        'menu_order'        => $variable_menu_order[ $i ],
                        'post_modified'     => $modified_date,
                        'post_modified_gmt' => get_gmt_from_date( $modified_date ),
                    ],
                    [ 'ID' => $variation_id ]
                );

                clean_post_cache( $variation_id );

                $product = wc_get_product( $variation_id );
                do_action( 'woocommerce_update_product_variation', $product->get_id(), $product );
                do_action( 'dokan_update_product_variation', $product->get_id(), $product );
            }

            // Only continue if we have a variation ID
            if ( ! $variation_id ) {
                continue;
            }

            // Unique SKU
            $sku     = get_post_meta( $variation_id, '_sku', true );
            $new_sku = wc_clean( $variable_sku[ $i ] );

            if ( '' === $new_sku ) {
                update_post_meta( $variation_id, '_sku', '' );
            } elseif ( $new_sku !== $sku ) {
                if ( ! empty( $new_sku ) ) {
                    $unique_sku = wc_product_has_unique_sku( $variation_id, $new_sku );

                    if ( ! $unique_sku ) {
                        /* translators: %s: variation id  */
                        $woocommerce_errors[] = sprintf( __( '#%s &ndash; Variation SKU must be unique.', 'dokan' ), $variation_id );
                    } else {
                        update_post_meta( $variation_id, '_sku', $new_sku );
                    }
                } else {
                    update_post_meta( $variation_id, '_sku', '' );
                }
            }

            // Update post meta
            update_post_meta( $variation_id, '_thumbnail_id', absint( $upload_image_id[ $i ] ) );
            update_post_meta( $variation_id, '_virtual', wc_clean( $is_virtual ) );
            update_post_meta( $variation_id, '_downloadable', wc_clean( $is_downloadable ) );

            if ( isset( $variable_weight[ $i ] ) ) {
                update_post_meta( $variation_id, '_weight', ( '' === $variable_weight[ $i ] ) ? '' : wc_format_decimal( $variable_weight[ $i ] ) );
            }

            if ( isset( $variable_length[ $i ] ) ) {
                update_post_meta( $variation_id, '_length', ( '' === $variable_length[ $i ] ) ? '' : wc_format_decimal( $variable_length[ $i ] ) );
            }

            if ( isset( $variable_width[ $i ] ) ) {
                update_post_meta( $variation_id, '_width', ( '' === $variable_width[ $i ] ) ? '' : wc_format_decimal( $variable_width[ $i ] ) );
            }

            if ( isset( $variable_height[ $i ] ) ) {
                update_post_meta( $variation_id, '_height', ( '' === $variable_height[ $i ] ) ? '' : wc_format_decimal( $variable_height[ $i ] ) );
            }

            // Stock handling
            update_post_meta( $variation_id, '_manage_stock', $manage_stock );

            if ( 'yes' === $manage_stock ) {
                update_post_meta( $variation_id, '_backorders', wc_clean( $variable_backorders[ $i ] ) );
                wc_update_product_stock( $variation_id, wc_stock_amount( $variable_stock[ $i ] ) );
                update_post_meta( $variable_low_stock_amount, '_low_stock_amount', wc_format_decimal( $variable_low_stock_amount[ $i ] ) );
            } else {
                $parent_manage_stock = ! empty( $_post_data['_manage_stock'] ) ? 'yes' : 'no';
                $parent_stock_amount = isset( $_post_data['_stock'] ) ? wc_clean( $_post_data['_stock'] ) : '';
                $parent_stock_amount = 'yes' === $parent_manage_stock ? wc_stock_amount( wp_unslash( $parent_stock_amount ) ) : '';

                delete_post_meta( $variation_id, '_backorders' );
                wc_update_product_stock( $variation_id, $parent_stock_amount );
            }

            // Only update stock status to user setting if changed by the user, but do so before looking at stock levels at variation level
            if ( ! empty( $variable_stock_status[ $i ] ) ) {
                wc_update_product_stock_status( $variation_id, $variable_stock_status[ $i ] );
            }

            // Price handling
            dokan_save_product_price( $variation_id, $variable_regular_price[ $i ], $variable_sale_price[ $i ], $variable_sale_price_dates_from[ $i ], $variable_sale_price_dates_to[ $i ] );

            if ( isset( $variable_tax_class[ $i ] ) && 'parent' !== $variable_tax_class[ $i ] ) {
                update_post_meta( $variation_id, '_tax_class', wc_clean( $variable_tax_class[ $i ] ) );
            } else {
                delete_post_meta( $variation_id, '_tax_class' );
            }

            if ( 'yes' === $is_downloadable ) {
                // fix download limit
                $download_limit = intval( $variable_download_limit[ $i ] );
                if ( ! $download_limit || -1 === $download_limit ) {
                    $download_limit = '';
                }
                // fix download expiry
                $download_expiry = intval( $variable_download_expiry[ $i ] );
                if ( ! $download_expiry || -1 === $download_expiry ) {
                    $download_expiry = '';
                }
                update_post_meta( $variation_id, '_download_limit', $download_limit );
                update_post_meta( $variation_id, '_download_expiry', $download_expiry );

                $files         = [];
                $_post_data    = wp_unslash( $_POST );//phpcs:ignore WordPress.Security.NonceVerification.Missing
                $file_names    = isset( $_post_data['_wc_variation_file_names'][ $variation_id ] ) ? array_map( 'wc_clean', $_post_data['_wc_variation_file_names'][ $variation_id ] ) : [];
                $file_urls     = isset( $_post_data['_wc_variation_file_urls'][ $variation_id ] ) ? array_map( 'esc_url_raw', array_map( 'trim', $_post_data['_wc_variation_file_urls'][ $variation_id ] ) ) : [];
                $file_url_size = count( $file_urls );

                for ( $ii = 0; $ii < $file_url_size; $ii++ ) {
                    if ( ! empty( $file_urls[ $ii ] ) ) {
                        $files[ md5( $file_urls[ $ii ] ) ] = [
                            'name' => $file_names[ $ii ],
                            'file' => $file_urls[ $ii ],
                        ];
                    }
                }

                // grant permission to any newly added files on any existing orders for this product prior to saving
                do_action( 'dokan_process_file_download', $post_id, $variation_id, $files );
                update_post_meta( $variation_id, '_downloadable_files', $files );
            } else {
                update_post_meta( $variation_id, '_download_limit', '' );
                update_post_meta( $variation_id, '_download_expiry', '' );
                update_post_meta( $variation_id, '_downloadable_files', '' );
            }

            // Update variation description
            update_post_meta( $variation_id, '_variation_description', wp_kses_post( $variable_description[ $i ] ) );

            // Save shipping class
            $variable_shipping_class[ $i ] = ! empty( $variable_shipping_class[ $i ] ) ? (int) $variable_shipping_class[ $i ] : '';
            wp_set_object_terms( $variation_id, $variable_shipping_class[ $i ], 'product_shipping_class' );

            // Update Attributes
            $updated_attribute_keys = [];
            foreach ( $attributes as $attribute ) {
                if ( $attribute['is_variation'] ) {
                    $attribute_key            = 'attribute_' . sanitize_title( $attribute['name'] );
                    $updated_attribute_keys[] = $attribute_key;

                    if ( $attribute['is_taxonomy'] ) {
                        // Don't use wc_clean as it destroys sanitized characters
                        $value = isset( $_post_data[ $attribute_key ][ $i ] ) ? sanitize_title( stripslashes( $_post_data[ $attribute_key ][ $i ] ) ) : '';
                    } else {
                        $value = isset( $_post_data[ $attribute_key ][ $i ] ) ? wc_clean( stripslashes( $_post_data[ $attribute_key ][ $i ] ) ) : '';
                    }

                    update_post_meta( $variation_id, $attribute_key, $value );
                }
            }

            // Remove old taxonomies attributes so data is kept up to date - first get attribute key names
            $delete_attribute_keys = $wpdb->get_col( $wpdb->prepare( "SELECT meta_key FROM {$wpdb->postmeta} WHERE meta_key LIKE 'attribute_%%' AND meta_key NOT IN ( '" . implode( "','", $updated_attribute_keys ) . "' ) AND post_id = %d;", $variation_id ) ); //phpcs:ignore

            foreach ( $delete_attribute_keys as $key ) {
                delete_post_meta( $variation_id, $key );
            }

            do_action( 'woocommerce_save_product_variation', $variation_id, $i );
            do_action( 'dokan_save_product_variation', $variation_id, $i );
        }
    }

    // Update parent if variable so price sorting works and stays in sync with the cheapest child
    WC_Product_Variable::sync( $post_id );

    // Update default attribute options setting
    $default_attributes = [];

    foreach ( $attributes as $attribute ) {
        if ( $attribute['is_variation'] ) {
            $value = '';

            if ( isset( $_post_data[ 'default_attribute_' . sanitize_title( $attribute['name'] ) ] ) ) {
                if ( $attribute['is_taxonomy'] ) {
                    // Don't use wc_clean as it destroys sanitized characters
                    $value = sanitize_title( trim( stripslashes( $_post_data[ 'default_attribute_' . sanitize_title( $attribute['name'] ) ] ) ) );
                } else {
                    $value = wc_clean( trim( stripslashes( $_post_data[ 'default_attribute_' . sanitize_title( $attribute['name'] ) ] ) ) );
                }
            }

            if ( $value ) {
                $default_attributes[ sanitize_title( $attribute['name'] ) ] = $value;
            }
        }
    }

    update_post_meta( $post_id, '_default_attributes', $default_attributes );
}

/**
 * Show the variable product options.
 *
 * @access public
 * @return void
 */
function dokan_variable_product_type_options() {
    global $post, $woocommerce;

    $attributes = maybe_unserialize( get_post_meta( $post->ID, '_product_attributes', true ) );

    // See if any are set
    $variation_attribute_found = false;
    if ( $attributes ) {
        foreach ( $attributes as $attribute ) {
            if ( isset( $attribute['is_variation'] ) ) {
                $variation_attribute_found = true;
                break;
            }
        }
    }

    // Get tax classes
    if ( class_exists( 'WC_Tax' ) ) {
        $tax_classes = WC_Tax::get_tax_classes();
    } else {
        $tax_classes = array_filter( array_map( 'trim', explode( "\n", get_option( 'woocommerce_tax_classes' ) ) ) );
    }

    $tax_class_options     = [];
    $tax_class_options[''] = __( 'Standard', 'dokan' );

    if ( $tax_classes ) {
        foreach ( $tax_classes as $class ) {
            $tax_class_options[ sanitize_title( $class ) ] = esc_attr( $class );
        }
    }
    ?>
    <div id="variable_product_options" class="wc-metaboxes-wrapper">
        <div id="variable_product_options_inner">

            <?php if ( ! $variation_attribute_found ) : ?>

                <div id="message" class="inline woocommerce-message">
                    <div class="squeezer">
                        <h4><?php esc_html_e( 'Before adding variations, add and save some attributes on the <strong>Attributes</strong> tab.', 'dokan' ); ?></h4>

                        <p class="submit">
                            <a class="button-primary" href="http://docs.woothemes.com/document/product-variations/" target="_blank">
                                <?php esc_html_e( 'Learn more', 'dokan' ); ?>
                            </a>
                        </p>
                    </div>
                </div>

            <?php else : ?>

                <div class="woocommerce_variations wc-metaboxes">
                    <?php
                    // Get parent data
                    $parent_data = [
                        'id'                => $post->ID,
                        'attributes'        => $attributes,
                        'tax_class_options' => $tax_class_options,
                        'sku'               => get_post_meta( $post->ID, '_sku', true ),
                        'weight'            => get_post_meta( $post->ID, '_weight', true ),
                        'length'            => get_post_meta( $post->ID, '_length', true ),
                        'width'             => get_post_meta( $post->ID, '_width', true ),
                        'height'            => get_post_meta( $post->ID, '_height', true ),
                        'tax_class'         => get_post_meta( $post->ID, '_tax_class', true ),
                    ];

                    if ( ! $parent_data['weight'] ) {
                        $parent_data['weight'] = '0.00';
                    }

                    if ( ! $parent_data['length'] ) {
                        $parent_data['length'] = '0';
                    }

                    if ( ! $parent_data['width'] ) {
                        $parent_data['width'] = '0';
                    }

                    if ( ! $parent_data['height'] ) {
                        $parent_data['height'] = '0';
                    }

                    // Get variations
                    $args       = [
                        'post_type'   => 'product_variation',
                        'post_status' => [ 'private', 'publish' ],
                        'numberposts' => - 1,
                        'orderby'     => 'menu_order',
                        'order'       => 'asc',
                        'post_parent' => $post->ID,
                    ];
                    $variations = get_posts( $args );
                    $loop       = 0;

                    if ( $variations ) {
                        foreach ( $variations as $variation ) {
                            $variation_id                        = absint( $variation->ID );
                            $variation_post_status               = esc_attr( $variation->post_status );
                            $variation_data                      = get_post_meta( $variation_id );
                            $variation_data['variation_post_id'] = $variation_id;

                            // Grab shipping classes
                            $shipping_classes = get_the_terms( $variation_id, 'product_shipping_class' );
                            $shipping_class   = ( $shipping_classes && ! is_wp_error( $shipping_classes ) ) ? current( $shipping_classes )->term_id : '';

                            $variation_fields = [
                                '_sku',
                                '_stock',
                                '_manage_stock',
                                '_stock_status',
                                '_regular_price',
                                '_sale_price',
                                '_weight',
                                '_length',
                                '_width',
                                '_height',
                                '_download_limit',
                                '_download_expiry',
                                '_downloadable_files',
                                '_downloadable',
                                '_virtual',
                                '_thumbnail_id',
                                '_sale_price_dates_from',
                                '_sale_price_dates_to',
                                '_variation_description',
                            ];

                            foreach ( $variation_fields as $field ) {
                                $$field = isset( $variation_data[ $field ][0] ) ? maybe_unserialize( $variation_data[ $field ][0] ) : '';
                            }

                            $_backorders = isset( $variation_data['_backorders'][0] ) ? $variation_data['_backorders'][0] : null;

                            $_tax_class = isset( $variation_data['_tax_class'][0] ) ? $variation_data['_tax_class'][0] : null;
                            $image_id   = absint( $_thumbnail_id );
                            $image      = $image_id ? wp_get_attachment_thumb_url( $image_id ) : '';

                            // Locale formatting
                            $_regular_price = wc_format_localized_price( $_regular_price );
                            $_sale_price    = wc_format_localized_price( $_sale_price );
                            $_weight        = wc_format_localized_decimal( $_weight );
                            $_length        = wc_format_localized_decimal( $_length );
                            $_width         = wc_format_localized_decimal( $_width );
                            $_height        = wc_format_localized_decimal( $_height );

                            // Stock BW compat
                            if ( '' !== $_stock ) {
                                $_manage_stock = 'yes';
                            }

                            include DOKAN_PRO_INC . '/woo-views/variation-admin-html.php';

                            ++$loop;
                        }
                    }
                    ?>
                </div> <!-- .woocommerce_variations -->

                <p class="toolbar">

                    <button type="button"
                            class="dokan-btn dokan-btn-sm dokan-btn-success button-primary add_variation" <?php disabled( $variation_attribute_found, false ); ?>><?php esc_html_e( 'Add Variation', 'dokan' ); ?></button>

                    <button type="button"
                            class="dokan-btn dokan-btn-sm dokan-btn-default link_all_variations" <?php disabled( $variation_attribute_found, false ); ?>><?php esc_html_e( 'Link all variations', 'dokan' ); ?></button>

                    <strong><?php esc_html_e( 'Default selections:', 'dokan' ); ?></strong>
                    <?php
                    $default_attributes = maybe_unserialize( get_post_meta( $post->ID, '_default_attributes', true ) );
                    foreach ( $attributes as $attribute ) {

                        // Only deal with attributes that are variations
                        if ( ! $attribute['is_variation'] ) {
                            continue;
                        }

                        // Get current value for variation (if set)
                        $variation_selected_value = isset( $default_attributes[ sanitize_title( $attribute['name'] ) ] ) ? $default_attributes[ sanitize_title( $attribute['name'] ) ] : '';

                        // Name will be something like attribute_pa_color
                        echo '<select name="default_attribute_' . sanitize_title( $attribute['name'] ) . '" data-current="' . esc_attr( $variation_selected_value ) . '"><option value="">' . __( 'No default', 'dokan' ) . ' ' . esc_html( wc_attribute_label( $attribute['name'] ) ) . '&hellip;</option>';

                        // Get terms for attribute taxonomy or value if its a custom attribute
                        if ( $attribute['is_taxonomy'] ) {
                            $post_terms = wp_get_post_terms( $post->ID, $attribute['name'] );

                            foreach ( $post_terms as $term ) {
                                echo '<option ' . selected( $variation_selected_value, $term->slug, false ) . ' value="' . esc_attr( $term->slug ) . '">' . apply_filters( 'woocommerce_variation_option_name', esc_html( $term->name ) ) . '</option>';
                            }
                        } else {
                            $options = wc_get_text_attributes( $attribute['value'] );

                            foreach ( $options as $option ) {
                                $selected = sanitize_title( $variation_selected_value ) === $variation_selected_value ? selected( $variation_selected_value, sanitize_title( $option ), false ) : selected( $variation_selected_value, $option, false );
                                echo '<option ' . $selected . ' value="' . esc_attr( $option ) . '">' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option ) ) . '</option>';
                            }
                        }

                        echo '</select>';
                    }
                    ?>
                </p> <!-- .toolbar -->

            <?php endif; ?>
        </div>
    </div>
    <?php
    /**
     * Product Type Javascript
     */
    ob_start();
    ?>
    jQuery(function($){

    var variation_sortable_options = {
    items:'.woocommerce_variation',
    cursor:'move',
    axis:'y',
    handle: 'h3',
    scrollSensitivity:40,
    forcePlaceholderSize: true,
    helper: 'clone',
    opacity: 0.65,
    placeholder: 'wc-metabox-sortable-placeholder',
    start:function(event,ui){
    ui.item.css('background-color','#f6f6f6');
    },
    stop:function(event,ui){
    ui.item.removeAttr('style');
    variation_row_indexes();
    }
    };

    // Add a variation
    jQuery('#variable_product_options').on('click', 'button.add_variation', function(){

    jQuery('.woocommerce_variations').block({ message: null, overlayCSS: { background: '#fff url(<?php echo $woocommerce->plugin_url(); ?>/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

    var loop = jQuery('.woocommerce_variation').length;

    var data = {
    action: 'dokan_add_variation',
    post_id: <?php echo $post->ID; ?>,
    loop: loop,
    security: '<?php echo wp_create_nonce( 'add-variation' ); ?>'
    };

    jQuery.post('<?php echo admin_url( 'admin-ajax.php' ); ?>', data, function(response) {

    jQuery('.woocommerce_variations').append( response );
    jQuery(".tips").tooltip();

    jQuery('input.variable_is_downloadable, input.variable_is_virtual').trigger( 'change' );

    jQuery('.woocommerce_variations').unblock();
    jQuery('#variable_product_options').trigger('woocommerce_variations_added');
    });

    return false;

    });

    jQuery('#variable_product_options').on('click', 'button.link_all_variations', function(){

    var answer = confirm(dokan.i18n_link_all_variations);

    if (answer) {

    jQuery('#variable_product_options').block({ message: null, overlayCSS: { background: '#fff url(<?php echo $woocommerce->plugin_url(); ?>/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

    var data = {
    action: 'dokan_link_all_variations',
    post_id: <?php echo $post->ID; ?>,
    security: '<?php echo wp_create_nonce( 'link-variations' ); ?>'
    };

    jQuery.post('<?php echo admin_url( 'admin-ajax.php' ); ?>', data, function(response) {

    var count = parseInt( response );

    if (count==1) {
    alert( count + ' <?php echo esc_js( __( 'variation added', 'dokan' ) ); ?>');
    } else if (count==0 || count>1) {
    alert( count + ' <?php echo esc_js( __( 'variations added', 'dokan' ) ); ?>');
    } else {
    alert('<?php echo esc_js( __( 'No variations added', 'dokan' ) ); ?>');
    }

    if (count>0) {
    var this_page = window.location.toString();

    this_page = this_page.replace( 'post-new.php?', 'post.php?post=<?php echo $post->ID; ?>&action=edit&' );

    $('#variable_product_options').load( this_page + ' #variable_product_options_inner', function() {
    $('#variable_product_options').unblock();
    jQuery('#variable_product_options').trigger('woocommerce_variations_added');
    } );
    } else {
    $('#variable_product_options').unblock();
    }

    });
    }
    return false;
    });

    jQuery('#variable_product_options').on('click', 'button.remove_variation', function(e){
    e.preventDefault();
    var answer = confirm('<?php echo esc_js( __( 'Are you sure you want to remove this variation?', 'dokan' ) ); ?>');
    if (answer){

    var el = jQuery(this).parent().parent();

    var variation = jQuery(this).attr('rel');

    if (variation>0) {

    jQuery(el).block({ message: null, overlayCSS: { background: '#fff url(<?php echo $woocommerce->plugin_url(); ?>/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

    var data = {
    action: 'dokan_remove_variation',
    variation_ids: variation,
    security: '<?php echo wp_create_nonce( 'delete-variations' ); ?>'
    };

    jQuery.post('<?php echo admin_url( 'admin-ajax.php' ); ?>', data, function(response) {
    // Success
    jQuery(el).fadeOut('300', function(){
    jQuery(el).remove();
    });
    });

    } else {
    jQuery(el).fadeOut('300', function(){
    jQuery(el).remove();
    });
    }

    }
    return false;
    });

    jQuery('#variable_product_options').on('change', 'input.variable_is_downloadable', function(){

    jQuery(this).closest('.woocommerce_variation').find('.show_if_variation_downloadable').hide();

    if (jQuery(this).is(':checked')) {
    jQuery(this).closest('.woocommerce_variation').find('.show_if_variation_downloadable').show();
    }

    });

    jQuery('#variable_product_options').on('change', 'input.variable_manage_stock', function(){

    jQuery(this).closest('.woocommerce_variation').find('.show_if_variation_manage_stock').hide();

    if (jQuery(this).is(':checked')) {
    jQuery(this).closest('.woocommerce_variation').find('.show_if_variation_manage_stock').show();
    }

    });

    jQuery('#variable_product_options').on('change', 'input.variable_is_virtual', function(){

    jQuery(this).closest('.woocommerce_variation').find('.hide_if_variation_virtual').show();

    if (jQuery(this).is(':checked')) {
    jQuery(this).closest('.woocommerce_variation').find('.hide_if_variation_virtual').hide();
    }

    });


    jQuery('input.variable_is_downloadable, input.variable_is_virtual, input.variable_manage_stock' ).trigger( 'change' );

    // Ordering
    $('#variable_product_options').on( 'woocommerce_variations_added', function() {
    $('.woocommerce_variations').sortable( variation_sortable_options );
    } );

    $('.woocommerce_variations').sortable( variation_sortable_options );

    function variation_row_indexes() {
    $('.woocommerce_variations .woocommerce_variation').each(function(index, el){
    $('.variation_menu_order', el).val( parseInt( $(el).index('.woocommerce_variations .woocommerce_variation') ) );
    });
    };
    });
    <?php
    $javascript = ob_get_clean();
    wc_enqueue_js( $javascript );
}

add_filter( 'woocommerce_cart_shipping_packages', 'dokan_custom_split_shipping_packages' );

/**
 * Split shpping seller wise
 *
 * @param array $packages
 *
 * @return array
 */
function dokan_custom_split_shipping_packages( $packages ) {
    // Bail out if WooCommerce cart or customer is not initialized (can happen during early recalculation).
    if ( ! WC()->cart instanceof \WC_Cart || ! WC()->customer instanceof \WC_Customer ) { // @phpstan-ignore-line -- stubs type these non-null, but they are null until WooCommerce initializes them.
        return $packages;
    }

    $selling_type = dokan_pro()->digital_product->get_selling_product_type() ?? '';
    if ( empty( $selling_type ) ) {
        return $packages;
    }

    $cart_content = WC()->cart->get_cart();
    $seller_pack  = [];
    $packages     = [];

    foreach ( $cart_content as $key => $item ) {
        // If individual seller product shipping is disable then out from here
        if ( \WeDevs\DokanPro\Shipping\Methods\ProductShipping::is_product_disable_shipping( $item['product_id'] ) ) {
            continue;
        }

        $post_author = get_post_field( 'post_author', $item['data']->get_id() );

        if ( 'sell_digital' === $selling_type ) {
            continue;
        }

        $seller_pack[ $post_author ][ $key ] = $item;
    }

    foreach ( $seller_pack as $seller_id => $pack ) {
        $packages[] = [
            'contents'        => $pack,
            'contents_cost'   => array_sum( wp_list_pluck( $pack, 'line_total' ) ),
            'applied_coupons' => WC()->cart->get_applied_coupons(),
            'user'            => [
                'ID' => get_current_user_id(),
            ],
            'seller_id'       => $seller_id,
            'destination'     => [
                'country'   => WC()->customer->get_shipping_country(),
                'state'     => WC()->customer->get_shipping_state(),
                'postcode'  => WC()->customer->get_shipping_postcode(),
                'city'      => WC()->customer->get_shipping_city(),
                'address'   => WC()->customer->get_shipping_address(),
                'address_2' => WC()->customer->get_shipping_address_2(),
            ],
        ];
    }

    return apply_filters( 'dokan_cart_shipping_packages', $packages );
}

add_filter( 'woocommerce_shipping_package_name', 'dokan_change_shipping_pack_name', 10, 3 );

/**
 * Set packagewise seller name
 *
 * @param string $title
 * @param integer $i
 * @param array $package
 *
 * @return string
 */
function dokan_change_shipping_pack_name( $title, $i, $package ) {
    $user_id = $package['seller_id'];

    if ( empty( $user_id ) ) {
        return $title;
    }

    if ( is_array( $user_id ) ) {
        $user_id = reset( $user_id );
    }

    $store_info = dokan_get_store_info( $user_id );

    $shipping_label = sprintf( '%s %s', __( 'Shipping: ', 'dokan' ), ! empty( $store_info['store_name'] ) ? $store_info['store_name'] : '' );

    return apply_filters( 'dokan_shipping_package_name', $shipping_label, $i, $package );
}

add_action( 'woocommerce_checkout_create_order_shipping_item', 'dokan_add_shipping_pack_meta', 10, 4 );

/**
 * Added shipping meta after order
 *
 * @param object $item
 * @param string $package_key
 * @param array $package
 * @param object $order
 *
 * @return void
 */
function dokan_add_shipping_pack_meta( $item, $package_key, $package, $order ) {
    $item->add_meta_data( 'seller_id', $package['seller_id'], true );
}

/**
 * Handles the social registration form
 *
 * @return void
 */
if ( ! function_exists( 'dokan_social_reg_handler' ) ) {

    function dokan_social_reg_handler() {
        $_post_data = wp_unslash( $_POST );
        if ( isset( $_post_data['dokan_social'] ) && isset( $_post_data['dokan_nonce'] ) && wp_verify_nonce( $_post_data['dokan_nonce'], 'account_migration' ) ) {
            $userdata = get_userdata( get_current_user_id() );

            $userdata->first_name = sanitize_text_field( $_post_data['fname'] );
            $userdata->last_name  = sanitize_text_field( $_post_data['lname'] );

            wp_update_user( $userdata );

            wp_safe_redirect( dokan_get_page_url( 'dashboard', 'dokan' ) );
        }
    }
}

add_action( 'template_redirect', 'dokan_social_reg_handler' );

if ( function_exists( 'dokan_add_privacy_policy' ) ) {
    // show privacy policy text in product enquiry form
    add_action( 'dokan_product_enquiry_after_form', 'dokan_add_privacy_policy' );
}

add_filter( 'woocommerce_ajax_admin_get_variations_args', 'dokan_set_variations_args' );
add_filter( 'woocommerce_variable_children_args', 'dokan_set_variations_args' );

/**
 * Include pending product status into variation args
 *
 * @param array $args
 *
 * @since 2.9.13
 */
function dokan_set_variations_args( $args ) {
    if ( ! is_array( $args['post_status'] ) ) {
        return $args;
    }

    $args['post_status'] = array_merge( $args['post_status'], [ 'pending' ] );

    return $args;
}

/**
 * Set variation product author to product vendor id
 *
 * @param int $variation_id
 *
 * @since 2.9.13
 *
 * @return void
 */
function dokan_override_variation_product_author( $variation_id ) {
    if ( ! is_admin() ) {
        return;
    }

    $variation_product = get_post( $variation_id );

    if ( ! $variation_product ) {
        return;
    }

    $product_id = $variation_product->post_parent;

    if ( ! $product_id ) {
        return;
    }

    $product = wc_get_product( $product_id );

    if ( ! $product ) {
        return;
    }

    $vendor    = dokan_get_vendor_by_product( $product );
    $vendor_id = $vendor->get_id();

    if ( ! $vendor || ! $vendor_id ) {
        return;
    }

    if ( absint( $vendor_id ) === absint( $variation_product->post_author ) ) {
        return;
    }

    wp_update_post(
        [
            'ID'          => $variation_id,
            'post_author' => $vendor_id,
        ]
    );

    do_action( 'dokan_after_override_variation_product_author', $product, $vendor_id );
}

add_action( 'woocommerce_save_product_variation', 'dokan_override_variation_product_author' );

/**
 * Dokan enabble single seller mode
 *
 * @param bool $valid
 * @param int $product_id
 *
 * @since  2.9.16
 *
 * @return bool
 */
function dokan_validate_cart_for_single_seller_mode( $valid, $product_id ) {
    if ( ! dokan_validate_boolean( dokan_is_single_seller_mode_enable() ) ) {
        return $valid;
    }

    $products                = WC()->cart->get_cart();
    $products[ $product_id ] = [ 'product_id' => $product_id ];

    if ( ! $products ) {
        return $valid;
    }

    $vendors = [];

    foreach ( $products as $key => $data ) {
        $product_id = isset( $data['product_id'] ) ? $data['product_id'] : 0;
        $vendor     = dokan_get_vendor_by_product( $product_id );
        $vendor_id  = $vendor && $vendor->get_id() ? $vendor->get_id() : 0;

        if ( ! $vendor_id ) {
            continue;
        }

        if ( ! in_array( $vendor_id, $vendors, true ) ) {
            array_push( $vendors, $vendor_id );
        }
    }

    if ( count( $vendors ) > 1 ) {
        wc_add_notice( __( 'Sorry, you can\'t add more than one vendor\'s product in the cart.', 'dokan' ), 'error' );
        $valid = false;
    }

    return $valid;
}

add_filter( 'woocommerce_add_to_cart_validation', 'dokan_validate_cart_for_single_seller_mode', 10, 2 );

/**
 * Dokan rest validate single seller mode
 *
 * @param WC_Order $order
 * @param WP_REST_Request
 * @param bool $creating
 *
 * @since  2.9.16
 *
 * @return WC_Order|WP_REST_Response on failure
 */
function dokan_rest_validate_single_seller_mode( $order, $request, $creating ) {
    if ( ! $creating ) {
        return $order;
    }

    if ( ! dokan_validate_boolean( dokan_is_single_seller_mode_enable() ) ) {
        return $order;
    }

    if ( $order->get_meta( 'has_sub_order' ) ) {
        return rest_ensure_response(
            new WP_Error(
                'dokan_single_seller_mode',
                __( 'Sorry, you can\'t purchase from multiple vendors at once.', 'dokan' ),
                [
                    'status' => 403,
                ]
            )
        );
    }

    return $order;
}

add_filter( 'woocommerce_rest_pre_insert_shop_order_object', 'dokan_rest_validate_single_seller_mode', 15, 3 );

if ( ! function_exists( 'woocommerce_customer_available_downloads_modified' ) ) {

    /**
     * Dokan customer available downloads modified for sub orders
     *
     * @param array $downloads
     *
     * @since  3.1.2
     *
     * @return array $modified_downloads|$downloads
     */
    function dokan_woocommerce_customer_available_downloads_modified( $downloads ) {

        if ( empty( $downloads ) ) {
            return $downloads;
        }

        $modified_downloads = [];

        foreach ( $downloads as $download ) {
            $order_id = $download['order_id'];
            $order    = wc_get_order( $order_id );

            if ( empty( $order ) ) {
                continue;
            }

            if ( $order->get_meta( 'has_sub_order' ) ) {
                continue;
            }

            $modified_downloads[] = $download;
        }

        if ( ! empty( $modified_downloads ) ) {
            return $modified_downloads;
        }

        return $downloads;
    }

    add_filter( 'woocommerce_customer_available_downloads', 'dokan_woocommerce_customer_available_downloads_modified', 15, 1 );
}

