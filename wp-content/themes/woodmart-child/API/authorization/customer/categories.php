<?php
/**
 * Customer Categories API Endpoint
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

add_action('rest_api_init', 'brags_register_customer_categories_route');

function brags_register_customer_categories_route() {
    register_rest_route('brags/v1/customer', '/categories', [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'brags_customer_get_categories_callback',
        'permission_callback' => '__return_true',
    ]);
}

/**
 * Get Categories Callback
 */
function brags_customer_get_categories_callback() {
    $args = [
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
    ];

    $terms = get_terms($args);

    if (is_wp_error($terms)) {
        return new WP_REST_Response([
            'status'  => 'error',
            'code'    => 'cannot_get_categories',
            'message' => $terms->get_error_message(),
        ], 500);
    }

    $categories = [];

    foreach ($terms as $term) {
        // Retrieve WooCommerce category image thumbnail URL
        $thumbnail_id = get_term_meta($term->term_id, 'thumbnail_id', true);
        $image_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'full') : '';
        if (!$image_url && $thumbnail_id) {
            $image_url = wp_get_attachment_url($thumbnail_id);
        }

        // Retrieve Woodmart custom category icon if present
        $icon_id = get_term_meta($term->term_id, 'category_icon', true);
        $icon_url = $icon_id ? wp_get_attachment_image_url($icon_id, 'full') : '';
        $icon_custom_url = get_term_meta($term->term_id, 'category_icon_url', true);
        
        $placeholder = function_exists('wc_placeholder_img_src') ? wc_placeholder_img_src('full') : '';
        if (!$placeholder) {
            $placeholder = site_url('/wp-content/uploads/woocommerce-placeholder-430x430.png');
        }
        
        $categories[] = [
            'id'          => $term->term_id,
            'name'        => html_entity_decode($term->name),
            'slug'        => $term->slug,
            'parent'      => $term->parent,
            'description' => $term->description,
            'count'       => $term->count,
            'image'       => $image_url ?: $placeholder,
            'icon'        => $icon_url ?: ($icon_custom_url ?: $placeholder),
        ];
    }

    return new WP_REST_Response([
        'status'  => 'success',
        'data'    => $categories,
    ], 200);
}
