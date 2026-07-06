<?php


// Flush rewrite rules when the theme is activated
function flush_rewrite_rules_on_activation() {
    add_brand_owner_registration_endpoint();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'flush_rewrite_rules_on_activation');

function custom_add_seller_approval_menu( $items ) {

    $user_id = get_current_user_id();
    $user = get_userdata($user_id);
    $user_roles = $user->roles;
    if (in_array('brand_owner', $user_roles)) {
        // Define the new menu item
        $new_item = array( 'authorise-sellers' => __( 'Authorise Sellers', 'user-registration' ) );
        // Find the position of 'edit-profile' and insert the new item after it
        $new_items = array();
        foreach ( $items as $key => $value ) {
            $new_items[$key] = $value;
            if ( $key === 'edit-profile' ) {
                $new_items = array_merge( $new_items, $new_item );
            }
        }

        return $new_items;
    }else{
        return $items;
    }
}
add_filter( 'user_registration_account_menu_items', 'custom_add_seller_approval_menu' );

function custom_add_seller_approval_endpoint() {
    add_rewrite_endpoint('authorise-sellers', EP_ROOT | EP_PAGES);
}
add_action('init', 'custom_add_seller_approval_endpoint');

function custom_seller_approval_page_content() {
    if (!is_user_logged_in()) {
        echo '<p>You must be logged in to view this page.</p>';
        return;
    }

    $user_id = get_current_user_id();
    $user = get_userdata($user_id);

    if (!in_array('brand_owner', $user->roles)) {
        echo '<p>You do not have permission to access this page.</p>';
        return;
    }

    get_template_part('template-parts/seller-approval');

    
}
add_action('user_registration_account_authorise-sellers_endpoint', 'custom_seller_approval_page_content');

function enqueue_seller_approval_scripts() {
    global $wp;
    if (isset($wp->request) && strpos($wp->request, 'brags-brand-network-account/authorise-sellers') !== false) {
        wp_enqueue_script(
            'seller-approval-js',
            get_stylesheet_directory_uri() . '/assets/js/seller-approval.js',
            array('jquery'),
            null,
            true
        );

        wp_localize_script('seller-approval-js', 'ajaxurl', admin_url('admin-ajax.php'));
        wp_localize_script('seller-approval-js', 'seller_request_nonce', wp_create_nonce('seller_request_nonce'));

        wp_enqueue_style(
            'seller-approval-css',
            get_stylesheet_directory_uri() . '/assets/css/seller-approval.css'
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_seller_approval_scripts');

