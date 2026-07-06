<?php



// function remove_woodmart_ajax_search_action() {
//     remove_action('wp_ajax_woodmart_ajax_search', 'woodmart_ajax_suggestions', 10);
//     remove_action('wp_ajax_nopriv_woodmart_ajax_search', 'woodmart_ajax_suggestions', 10);

//     add_action('wp_ajax_woodmart_ajax_search', 'custom_woodmart_ajax_suggestions', 10);
//     add_action('wp_ajax_nopriv_woodmart_ajax_search', 'custom_woodmart_ajax_suggestions', 10);
// }
// add_action('wp_loaded', 'remove_woodmart_ajax_search_action');

// function custom_woodmart_ajax_suggestions() {
//     $allowed_types = array('post', 'product', 'portfolio', 'any', 'page');
//     $post_type = 'product';

//     if (apply_filters('woodmart_search_by_sku', woodmart_get_opt('search_by_sku')) && woodmart_woocommerce_installed()) {
//         add_filter('posts_search', 'woodmart_product_ajax_search_sku', 10);
//     }

//     $query_args = array(
//         'posts_per_page' => 5,
//         'post_status'    => 'publish',
//         'post_type'      => $post_type,
//         'no_found_rows'  => 1,
//     );

//     if (!empty($_REQUEST['post_type']) && in_array($_REQUEST['post_type'], $allowed_types)) {
//         $post_type = strip_tags($_REQUEST['post_type']);
//         $query_args['post_type'] = $post_type;
//     }

//     if (!empty($_REQUEST['query'])) {
//         $query_args['s'] = sanitize_text_field($_REQUEST['query']);
//     }

//     $query_args = apply_filters('woodmart_ajax_search_args', $query_args, $post_type);
//     $results = new WP_Query($query_args);

//     $suggestions = array();

//     // Step 1: Get and Display Frequent Searches First
//     $frequent_searches = get_option('frequent_searches', []);

//     if (!empty($frequent_searches)) {
//         arsort($frequent_searches); // Sort by highest count
//         $frequent_searches = array_slice($frequent_searches, 0, 5, true); // Limit to 5 top searches

//         foreach ($frequent_searches as $search_term => $count) {
//             $suggestions[] = array(
//                 'value' => esc_html($search_term),
//                 'permalink' => '#',
//                 'is_frequent' => true
//             );
//         }
//     }

//     // Step 2: Fetch Product/Post Results
//     if ($results->have_posts()) {
//         if ($post_type == 'product' && woodmart_woocommerce_installed()) {
//             $factory = new WC_Product_Factory();
//         }

//         while ($results->have_posts()) {
//             $results->the_post();

//             if ($post_type == 'product' && woodmart_woocommerce_installed()) {
//                 $product = $factory->get_product(get_the_ID());

//                 $suggestions[] = array(
//                     'value' => html_entity_decode(get_the_title()),
//                     'permalink' => get_the_permalink(),
//                     'price' => $product->get_price_html(),
//                     'thumbnail' => $product->get_image(),
//                     'sku' => $product->get_sku() ? esc_html__('SKU:', 'woodmart') . ' ' . $product->get_sku() : '',
//                 );
//             } else {
//                 $suggestions[] = array(
//                     'value' => html_entity_decode(get_the_title()),
//                     'permalink' => get_the_permalink(),
//                     'thumbnail' => get_the_post_thumbnail(null, 'medium', ''),
//                 );
//             }
//         }
//         wp_reset_postdata();
//     }

//     wp_send_json(
//         array(
//             'suggestions' => $suggestions,
//         )
//     );
// }

// add_action('wp_ajax_woodmart_ajax_search', 'custom_woodmart_ajax_suggestions', 10);
// add_action('wp_ajax_nopriv_woodmart_ajax_search', 'custom_woodmart_ajax_suggestions', 10);


// function enqueue_custom_search_script() {
//     wp_enqueue_script(
//         'custom-search-js',
//         get_stylesheet_directory_uri() . '/assets/js/custom-search.js',
//         ['jquery'],
//         null,
//         true
//     );

//     // Localize script to pass AJAX URL
//     wp_localize_script('custom-search-js', 'woodmart_settings', [
//         'ajaxurl' => admin_url('admin-ajax.php'),
//     ]);
// }
//add_action('wp_enqueue_scripts', 'enqueue_custom_search_script');






