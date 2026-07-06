<?php

// Add a custom SEO Keywords field to the product edit page as a tag input field
function add_seo_keywords_field() {
    global $post;

    // Get existing SEO Keywords value
    $seo_keywords = get_post_meta( $post->ID, '_seo_keywords', true );

    ?>
    <p class="form-field">
        <label for="_seo_keywords"><?php _e( 'SEO Keywords', 'your-text-domain' ); ?></label>
        <input type="text" name="_seo_keywords" id="_seo_keywords" value="<?php echo esc_attr( $seo_keywords ); ?>" class="short" />
        <span class="description"><?php _e( 'Enter SEO keywords separated by commas. Example: "electronics, laptop, 4K TV". This field is not visible to customers.', 'your-text-domain' ); ?></span>
    </p>

    <script>
        jQuery(document).ready(function($){
            $('#_seo_keywords').tagsinput({
                tagClass: 'badge badge-primary',
                confirmKeys: [44] // Comma key
            });
        });
    </script>
    <?php
}
add_action( 'woocommerce_product_options_general_product_data', 'add_seo_keywords_field' );

// Save the SEO Keywords data when the product is saved
function save_seo_keywords_field( $post_id ) {
    if ( isset( $_POST['_seo_keywords'] ) ) {
        update_post_meta( $post_id, '_seo_keywords', sanitize_text_field( $_POST['_seo_keywords'] ) );
    }
}
add_action( 'woocommerce_process_product_meta', 'save_seo_keywords_field' );

// Add SEO keywords to the HTML <head> for product pages
function add_seo_keywords_to_meta_tags() {
    if ( is_product() ) { // Check if it's a product page
        global $post;
        $seo_keywords = get_post_meta( $post->ID, '_seo_keywords', true );

        // If there are SEO keywords, output them in the meta tag
        if ( ! empty( $seo_keywords ) ) {
            echo '<meta name="keywords" content="' . esc_attr( $seo_keywords ) . '" />' . "\n";
        }
    }
}
add_action( 'wp_head', 'add_seo_keywords_to_meta_tags', 10 );



// Save the SEO Keywords field when the product is updated
function dokan_save_seo_keywords_field( $post_id ) {
    if ( isset( $_POST['_seo_keywords'] ) ) {
        update_post_meta( $post_id, '_seo_keywords', sanitize_text_field( $_POST['_seo_keywords'] ) );
    }
}
add_action( 'dokan_process_product_meta', 'dokan_save_seo_keywords_field' );

// Add metabox to product
add_action('add_meta_boxes', function () {
    add_meta_box(
        'product_noindex_meta',
        'SEO: Noindex Option',
        'render_product_noindex_meta_box',
        'product',
        'side',
        'default'
    );
});

// Render checkbox
function render_product_noindex_meta_box($post) {
    $value = get_post_meta($post->ID, '_noindex_product', true);
    wp_nonce_field('save_noindex_product_meta', 'noindex_product_meta_nonce');
    ?>
    <label>
        <input type="checkbox" name="noindex_product" value="1" <?php checked($value, '1'); ?>>
        Add <code>noindex, nofollow</code> to this product
    </label>
    <?php
}


add_action('save_post_product', function ($post_id) {
    
    if (!isset($_POST['noindex_product_meta_nonce']) || !wp_verify_nonce($_POST['noindex_product_meta_nonce'], 'save_noindex_product_meta')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    if (isset($_POST['noindex_product']) && $_POST['noindex_product']=='1') {
        update_post_meta($post_id, '_noindex_product', '1');
    } else {
        delete_post_meta($post_id, '_noindex_product');
    }
});

add_action('wp_head', function () {
    if (is_product()) {
        global $post;

        $noindex = get_post_meta($post->ID, '_noindex_product', true);

        if ($noindex === '1') {
            echo '<meta name="robots" content="noindex, nofollow">' . "\n";
        }
    }
});



// Redirect unauthorized users away from noindex products
add_action('template_redirect', function () {
    if (is_product()) {
        global $post;

        $noindex = get_post_meta($post->ID, '_noindex_product', true);

        if ($noindex === '1') {
            $user = wp_get_current_user();

            // Only allow approved Dokan vendors
            if (!in_array('seller', $user->roles)) {
                wp_redirect(home_url()); // or show a custom page
                exit;
            }
        }
    }
});


// Exclude noindex products from shop/category/search
add_action('pre_get_posts', function ($query) {
    if (is_admin() || !$query->is_main_query()) return;

    if (is_shop() || is_product_category() || is_product_tag() || is_search()) {
        $user = wp_get_current_user();

        // Allow only approved Dokan sellers to see these
        $allowed = false;
        if (in_array('seller', $user->roles)) {
            $seller_status = get_user_meta($user->ID, 'dokan_enable_selling', true);
            if ($seller_status === 'yes') {
                $allowed = true;
            }
        }

        if (!$allowed) {
            $meta_query = $query->get('meta_query');
            if (!is_array($meta_query)) $meta_query = [];

            // $meta_query[] = [
            //     'key'     => '_noindex_product',
            //     'value'   => '1',
            //     'compare' => '!='
            // ];
            $meta_query[] = [
                'relation' => 'OR',
                [
                    'key'     => '_noindex_product',
                    'value'   => '1',
                    'compare' => '!='
                ],
                [
                    'key'     => '_noindex_product',
                    'compare' => 'NOT EXISTS'
                ]
            ];

            $query->set('meta_query', $meta_query);
        }
    }
});



