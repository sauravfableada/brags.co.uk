<?php
function get_dokan_subscription_orders_by_vendor( $vendor_id, $page = 1, $per_page = 20 ) {
    if ( ! class_exists( 'WC_Order_Query' ) ) {
        return false; // Ensure WooCommerce is active
    }

    $offset = ( $page - 1 ) * $per_page;

    $args = [
        'customer_id' => $vendor_id,
        'status'      => 'completed', // Fetch only completed subscription orders
        'limit'       => $per_page,
        'offset'      => $offset,
        'orderby'     => 'date',
        'order'       => 'DESC',
        'meta_query'  => [
            'relation' => 'OR',
            [
                'key'     => '_dokan_vendor_subscription_order',
                'value'   => 'yes',
                'compare' => '='
            ],
            [
                'key'     => '_pack_validity',
                'compare' => 'EXISTS'
            ],
            [
                'key'     => '_subscription_product_admin_commission',
                'compare' => 'EXISTS'
            ],
        ],
    ];

    // Fetch orders using WooCommerce WC_Order_Query
    $query = new WC_Order_Query( $args );
    $orders = $query->get_orders();

    // Get total count (for pagination)
    $args['return'] = 'count';
    $total_orders = count( $orders ); // No direct count method, so we use count()

    // Calculate total pages
    $total_pages = ceil( $total_orders / $per_page );

    // Return results
    return [
        'orders'       => $orders,
        'total_orders' => $total_orders,
        'total_pages'  => $total_pages,
        'current_page' => $page,
        'per_page'     => $per_page,
    ];
}



// 1. Add the "Bragsy Membership" menu item to the Dokan dashboard for seller.
function add_bragsy_membership_menu( $urls ) {
    if ( current_user_can( 'seller' ) ) { // Check if the user is a vendor/seller
        $urls['seller-bragsy-membership'] = array(
            'title'      => __( 'Bragsy Membership', 'dokan' ),
            'icon'       => '<i class="fas fa-crown"></i>',
            'url'        => dokan_get_navigation_url( 'seller-bragsy-membership' ),
            'pos'        => 50,
            'permission' => 'dokan_view_product_menu',
        );
    }
    return $urls;
}
//add_filter( 'dokan_get_dashboard_nav', 'add_bragsy_membership_menu' );

// 2. Register the custom query variable for seller Bragsy Membership.
function register_dokan_custom_query_seller_bragsy_membership_vars( $query_vars ) {
    $query_vars[] = 'seller-bragsy-membership';
    return $query_vars;
}
add_filter( 'query_vars', 'register_dokan_custom_query_seller_bragsy_membership_vars' );

// 3. Load the custom template seller Bragsy Membership.
function load_dokan_seller_bragsy_membership_template() {
    global $wp_query;

    if ( isset( $wp_query->query_vars['seller-bragsy-membership'] ) ) {
        $template_path = get_stylesheet_directory() . '/template-parts/dokan/seller-bragsy-membership.php';

        if ( file_exists( $template_path ) ) {
            include $template_path;
            exit;
        } else {
            wp_die( 'seller-bragsy-membership template not found.' );
        }
    }
}
add_action( 'template_redirect', 'load_dokan_seller_bragsy_membership_template' );
// 4. Flush rewrite rules and add rewrite endpoint seller Bragsy Membership.
function seller_bragsy_membership_flush_rules() {
    add_rewrite_endpoint( 'seller-bragsy-membership', EP_ROOT | EP_PAGES );
    flush_rewrite_rules();
}
add_action( 'init', 'seller_bragsy_membership_flush_rules' );
// ----------------------------------------------------

function brags_membership_add_my_account_endpoint() {
    add_rewrite_endpoint('brags-membership', EP_ROOT | EP_PAGES);
}
add_action('init', 'brags_membership_add_my_account_endpoint');
function add_bragsy_membership_link_to_my_account($items) {
    $user = wp_get_current_user();
    if (in_array('customer', (array) $user->roles) ) {
        $items['brags-membership'] = __('Bragsy Membership', 'text-domain');
    }
    return $items;
}
add_filter('woocommerce_account_menu_items', 'add_bragsy_membership_link_to_my_account');

function bragsy_membership_content() {

    $current_user = wp_get_current_user();


    // seller
    if (in_array('customer', $current_user->roles)) {
        get_template_part('template-parts/my-account/bragsy-membership-customer');
    }else if(in_array('seller', $current_user->roles)){

        //get_template_part('template-parts/my-account/bragsy-membership-seller');
    }

}
add_action('woocommerce_account_brags-membership_endpoint', 'bragsy_membership_content');

function get_active_pms_subscriptions($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return [];
    }

    // Fetch user's active subscriptions
    $subscriptions = pms_get_member_subscriptions(array('user_id' => $user_id));
    $active_subscriptions = [];

    foreach ($subscriptions as $subscription) {
        if (in_array($subscription->status, ['active', 'trial'])) {
            $active_subscriptions[] = $subscription;
        }
    }

    return $active_subscriptions;
}

// function get_pms_all_subscription_products(){
//     $args = array(
//         'post_type'      => 'product',
//         'posts_per_page' => -1, // Retrieve all relevant products
//         'meta_query'     => array(
//             array(
//                 'key'     => '_pms_woo_subscription_id',  // Meta key where PMS stores the subscription ID
//                 'compare' => 'EXISTS'  // Ensures only products with PMS subscriptions are fetched
//             )
//         )
//     );

//     return new WP_Query($args);
// }
function get_pms_all_subscription_products($category_slug = '') {
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => -1, // Retrieve all relevant products
        'meta_query'     => array(
            array(
                'key'     => '_pms_woo_subscription_id',  // Meta key where PMS stores the subscription ID
                'compare' => 'EXISTS'  // Ensures only products with PMS subscriptions are fetched
            )
        )
    );

    // If a category slug is provided, add a tax_query to filter by category
    if ($category_slug!='') {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'product_cat',  // WooCommerce product category taxonomy
                'field'    => 'slug',         // Search by category slug
                'terms'    => $category_slug  // Category to filter
            )
        );
    }

    return new WP_Query($args);
}


function is_product_subscription_active($product_id, $user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id || !$product_id) {
        return false; // No user or product
    }

    // Get the subscription plans attached to the product
    $linked_plans = get_post_meta($product_id, '_pms_woo_subscription_id', true);


    // Convert linked plans to an array (ensure proper type handling)
    if (!is_array($linked_plans)) {
        $linked_plans = !empty($linked_plans) ? [(int) $linked_plans] : [];
    } else {
        $linked_plans = array_map('intval', $linked_plans);
    }

    if (empty($linked_plans)) {
        return false; // No subscription plans linked to this product
    }

    // Get user subscriptions
    //$subscriptions = pms_get_member_subscriptions($user_id);
    $subscriptions = get_active_pms_subscriptions($user_id);


    if (empty($subscriptions)) {
        return false; // User has no subscriptions
    }

    // Debug: Log subscriptions
    error_log("User Subscriptions: " . print_r($subscriptions, true));

    // Check if the user has an active subscription matching the product's plan
    foreach ($subscriptions as $subscription) {
        // Check if object properties exist
        if (!isset($subscription->subscription_plan_id) || !isset($subscription->status)) {
            continue; // Skip if required properties are missing
        }

        $subscription_plan_id = (int) $subscription->subscription_plan_id;
        $status = strtolower(trim($subscription->status)); // Normalize status

        if (in_array($subscription_plan_id, $linked_plans) && $status === 'active') {
            error_log("Active subscription found for Product ID: $product_id");
            return true;
        }
    }

    return false; // No active subscription found
}

function is_user_subscribed_to_plan($plan_id, $user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id || !$plan_id) {
        return false; // No user or plan ID provided
    }

    // Get user's active subscriptions
    $subscriptions = pms_get_member_subscriptions($user_id);

    if (empty($subscriptions)) {
        error_log("No subscriptions found for User ID: $user_id");
        return false; // User has no subscriptions
    }

    // Debug: Log subscriptions for verification
    error_log("User ID: $user_id, Checking Plan ID: $plan_id, Subscriptions: " . print_r($subscriptions, true));

    // Check if the user has an active subscription for the given plan
    foreach ($subscriptions as $subscription) {
        if (!isset($subscription->subscription_plan_id) || !isset($subscription->status)) {
            continue; // Skip if required properties are missing
        }

        $subscription_plan_id = (int) $subscription->subscription_plan_id;
        $status = strtolower(trim($subscription->status)); // Normalize status

        if ($subscription_plan_id === (int) $plan_id && $status === 'active') {
            error_log("User ID: $user_id has an ACTIVE subscription for Plan ID: $plan_id");
            return true;
        }
    }

    return false; // No active subscription found for the given plan ID
}

function is_seller_bragsy_plan($user_id = null){
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    $product_package_id = get_user_meta( $user_id, 'product_package_id', true ) ;
    if($product_package_id=='17058'){
        return true;
    }

    // $selle_plans = array(17507,17503,17497);
    // $subscriptions = get_active_pms_subscriptions($user_id);

    // if($subscriptions){

    //     foreach ($subscriptions as $sub) {

    //         if (is_object($sub) && isset($sub->subscription_plan_id)) {

    //             if(in_array($sub->subscription_plan_id,$selle_plans)){
    //                 return true;
    //             }
    //         }
    //     }
    // }
    return false;
}

function get_current_user_id_seller_bargassy_plan($user_id = null){
    $selle_plans = array(17507,17503,17497);
    $active_plan = [];
    $subscriptions = get_active_pms_subscriptions();
    if($subscriptions){
        foreach ($subscriptions as $sub) {
            if (is_object($sub) && isset($sub->subscription_plan_id)) {
                if(in_array($sub->subscription_plan_id,$selle_plans)){
                    $active_plan[]=$sub;
                }
            }
        }
    }
    return $active_plan;
}




function update_bragsy_products_status() {
    $args = array(
        'role'    => 'seller', // Adjust this role if needed
        'fields'  => 'ID'      // Get only user IDs
    );

    $sellers = get_users($args);

    foreach ($sellers as $seller_id) {
       $is_bragsy = is_seller_bragsy_plan($seller_id);
        // Get all products by this seller
        $product_args = array(
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'post_status'    => 'any', // Include drafts, pending, etc.
            'author'         => $seller_id
        );

        $query = new WP_Query($product_args);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $product_id = get_the_ID();

                if ($is_bragsy) {

                    update_post_meta($product_id, '_is_bragsy_product', 'yes');
                } else {
                    delete_post_meta($product_id, '_is_bragsy_product');
                }
            }
        }
    }
}


function schedule_bragsy_product_check() {
    if (!wp_next_scheduled('bragsy_product_status_event')) {
        wp_schedule_event(time(), 'daily', 'bragsy_product_status_event');
    }
}
add_action('wp', 'schedule_bragsy_product_check');

// Hook the function to the scheduled event
add_action('bragsy_product_status_event', 'update_bragsy_products_status');




function display_bragsy_seller_label_before_thumbnail() {
    global $product;

    $vendor_id = get_post_field('post_author', $product->get_id());
    $is_bragsy = is_seller_bragsy_plan($vendor_id); // Check if the seller is on the Bragsy plan

    if ($is_bragsy) {
        $logo_url = get_bragsy_seller_logo();
        
        echo '<div class="dokan-vendor-bragsy">';
        
        if ($logo_url) {
            echo '<div class="bragsy-label-logo">';
            // Display the logo with alt text and proper dimensions
            echo '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr__('Bragsy Seller', 'dokan-lite') . '" class="bragsy-logo" >';
            echo '</div>';
        } else {
            // Fallback to text label
            echo '<div class="bragsy-label">';
            echo esc_html__('Bragsy it', 'dokan-lite');
            echo '</div>';
        }
        
        echo '</div>';
    }
}
add_action('woocommerce_before_shop_loop_item_title', 'display_bragsy_seller_label_before_thumbnail', 5);


function add_bragsy_filter_toggle() {
    ?>
    <div class="widget bragsy-filter">
        <label for="bragsy_filter" style="font-weight: bold;">
            <input type="checkbox" id="bragsy_filter" name="bragsy_filter" value="1" <?php checked(isset($_GET['bragsy_filter']), true); ?>>
            <?php esc_html_e('Search by BRAGSY (Free UK Delivery for Members)', 'dokan-lite'); ?>
        </label>
    </div>
    <script>
        document.getElementById('bragsy_filter').addEventListener('change', function() {
            let url = new URL(window.location.href);
            if (this.checked) {
                url.searchParams.set('bragsy_filter', '1');
            } else {
                url.searchParams.delete('bragsy_filter');
            }
            window.location.href = url.toString();
        });
    </script>
    <?php
}
add_action('woocommerce_widget_price_filter_end', 'add_bragsy_filter_toggle');
function filter_bragsy_products_query($query) {
    if (!is_admin() && $query->is_main_query() && is_shop()) {
        if (isset($_GET['bragsy_filter']) && $_GET['bragsy_filter'] == '1') {
            $query->set('meta_query', array(
                array(
                    'key'     => '_is_bragsy_product',
                    'value'   => 'yes',
                    'compare' => '='
                )
            ));
        }
    }
}
add_action('pre_get_posts', 'filter_bragsy_products_query');


// function apply_bragsy_shipping_cost( $rates, $package ) {
//     $user_id = get_current_user_id();
//     $is_bragsy_member = get_active_pms_subscriptions( $user_id );
//     $has_bragsy_product = false;

//     foreach ( $package['contents'] as $cart_item ) {
//         if ( get_post_meta( $cart_item['product_id'], '_is_bragsy_product', true ) === 'yes' ) {
//             $has_bragsy_product = true;
//             break;
//         }
//     }

//     error_log('apply_bragsy_shipping_cost Hook Triggered');

//     if ( $has_bragsy_product ) {
//         foreach ( $rates as $rate_key => $rate ) {
//             error_log('Checking Rate: ' . $rate->method_id);

//             // Modify the correct shipping method
//             if ( isset($rate->method_id) && $rate->method_id === 'dokan_product_shipping' ) {
//                 $rates[$rate_key]->cost = (float) ($is_bragsy_member ? '0' : 5.99);
//                 $rates[$rate_key]->label = $is_bragsy_member ? 'FREE Shipping with BRAGSY (2 Day Delivery)' : 'Standard Shipping (£5.99 inc VAT)';
//                 $rates[$rate_key]->taxes = array_map('floatval', (array) $rates[$rate_key]->taxes);
//             }
//         }
//     }

//     WC()->session->set( 'shipping_for_package_0', false ); // Clear cache

//     return $rates;
// }
// add_filter( 'woocommerce_package_rates', 'apply_bragsy_shipping_cost', 9999, 2 );

function apply_bragsy_shipping_cost( $rates, $package ) {
    $user_id = get_current_user_id();
    $is_bragsy_member = get_active_pms_subscriptions( $user_id );
    $has_bragsy_product = false;

    // Check if cart has any Bragsy product
    foreach ( $package['contents'] as $cart_item ) {
        if ( get_post_meta( $cart_item['product_id'], '_is_bragsy_product', true ) === 'yes' ) {
            $has_bragsy_product = true;
            break;
        }
    }

    if ( $has_bragsy_product ) {
        $filtered_rates = [];

        foreach ( $rates as $rate_key => $rate ) {
            // Only allow Dokan product shipping
            if ( isset( $rate->method_id ) && $rate->method_id === 'dokan_product_shipping' ) {
                // Update the cost and label based on membership
                $rate->cost = $is_bragsy_member ? 0 : 5.99;
                $rate->label = $is_bragsy_member 
                    ? 'FREE Shipping with BRAGSY (2 Day Delivery)' 
                    : 'Standard Shipping (£5.99 inc VAT)';
                
                // Ensure taxes are set correctly
                $rate->taxes = array_map( 'floatval', (array) $rate->taxes );

                $filtered_rates[ $rate_key ] = $rate;
            }
        }

        WC()->session->set( 'shipping_for_package_0', false ); // Clear cached shipping rates

        return $filtered_rates; // Return only the filtered method
    }

    return $rates; // Return original if not Bragsy product
}
add_filter( 'woocommerce_package_rates', 'apply_bragsy_shipping_cost', 9999, 2 );




function display_bragsy_shipping_notice() {
    //$bragsy_url = esc_url(home_url('/bragsy/'));
    $bragsy_url = esc_url(home_url('/my-account/brags-membership/'));
    if ( is_user_logged_in() ) {
        $user_id = get_current_user_id();
        $is_bragsy_member = get_active_pms_subscriptions( $user_id );

        if ( $is_bragsy_member ) {
            ?>

        <style>
            ul.woocommerce-shipping-methods li {
                display: block;
            }
            
        </style>
           <?php
            
            echo '<p style="color:green;">You have Bragsy Membership - Enjoy Free 2-Day Shipping on products marked ‘Bragsy’ only</p>';
        } else {
            ?>
            <style>
                ul.woocommerce-shipping-methods li {
                    display: none;
                }
                ul.woocommerce-shipping-methods li:last-child {
                    display: block;
                }
        </style>
           <?php
        //    echo sprintf(
        //         '<strong class="bragsy-upsell-notice">%s</strong>',
        //         __('You do not have an active Bragsy Membership Plan. A £5.99 shipping fee applies to Bragsy products. Fancy endless Free UK Shipping for just £4.99 a month? <a href="%s" class="bragsy-upsell-link">Click here to Join Today</a>.', 'woocommerce'),
        //         $bragsy_url
        //     );

            echo sprintf(
                __('<strong class="bragsy-upsell-notice">You do not have an active Bragsy Membership Plan. A £5.99 shipping fee applies to Bragsy products. Fancy endless Free UK Shipping for just £4.99 a month? <a href="%s" class="bragsy-upsell-link">Click here to Join Today</a>.</strong>', 'woocommerce'),
                $bragsy_url
            );
        }
    } else {
        ?>

        <style>
            ul.woocommerce-shipping-methods li {
                display: none;
            }
            ul.woocommerce-shipping-methods li:last-child {
                display: block;
            }
        </style>

     <?php
    //    echo sprintf(
    //         '<strong class="bragsy-upsell-notice">%s</strong>',
    //         __('You do not have an active Bragsy Membership Plan. A £5.99 shipping fee applies to Bragsy products. Fancy endless Free UK Shipping for just £4.99 a month? <a href="%s" class="bragsy-upsell-link">Click here to Join Today</a>.', 'woocommerce'),
    //         $bragsy_url
    //     );

        echo sprintf(
                __('<strong class="bragsy-upsell-notice">You do not have an active Bragsy Membership Plan. A £5.99 shipping fee applies to Bragsy products. Fancy endless Free UK Shipping for just £4.99 a month? <a href="%s" class="bragsy-upsell-link">Click here to Join Today</a>.</strong>', 'woocommerce'),
                $bragsy_url
            );
    }
}
add_action( 'woocommerce_review_order_before_payment', 'display_bragsy_shipping_notice' );




// Disable shipping settings for 'Bragsy' Sellers in Dokan
function disable_shipping_settings_for_bragsy_sellers( $options ) {
    // Get the current user (seller)
    $user_id = get_current_user_id();
    $is_bragsy_plan = is_seller_bragsy_plan($user_id); // Assuming you have this function to check the seller's plan

    if ( $is_bragsy_plan ) {
        // Disable the shipping settings
        if ( isset( $options['shipping_settings'] ) ) {
            $options['shipping_settings'] = false; // Disable all shipping settings for Bragsy sellers
        }
    }

    return $options;
}
add_filter( 'dokan_get_option', 'disable_shipping_settings_for_bragsy_sellers', 10, 1 );


function pms_custom_handle_cancel_subscription() {
    if (isset($_POST['pms-action']) && $_POST['pms-action'] === 'cancel_subscription') {
        if (!isset($_POST['pmstkn']) || !wp_verify_nonce($_POST['pmstkn'], 'pms_cancel_subscription')) {
            wp_die(__('Security check failed.', 'paid-member-subscriptions'));
        }

        if (!is_user_logged_in()) {
            wp_die(__('You must be logged in to cancel a subscription.', 'paid-member-subscriptions'));
        }

        if (empty($_POST['subscription_id'])) {
            wp_die(__('Invalid subscription ID.', 'paid-member-subscriptions'));
        }

        // Load the subscription and cancel it
        $subscription_id = absint($_POST['subscription_id']);
        $member_subscription = pms_get_member_subscription($subscription_id);

        if ($member_subscription && $member_subscription->status === 'active') {
            $member_subscription->update(['status' => 'canceled']);

            // Redirect after cancellation
            wp_redirect(add_query_arg('subscription_cancelled', 'true', pms_get_current_page_url()));
            exit;
        } else {
            wp_die(__('Subscription not found or already canceled.', 'paid-member-subscriptions'));
        }
    }
}
add_action('init', 'pms_custom_handle_cancel_subscription');



// Add admin menu for Bragsy Logo
add_action('admin_menu', 'bragsy_logo_admin_menu');
function bragsy_logo_admin_menu() {
    add_menu_page(
        'Bragsy Logo Settings',
        'Bragsy Logo',
        'manage_options',
        'bragsy-logo-settings',
        'bragsy_logo_settings_page',
        'dashicons-format-image',
        80
    );
}

// Settings page content
function bragsy_logo_settings_page() {
    ?>
    <div class="wrap">
        <h1>Bragsy Seller Logo Settings</h1>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('bragsy_logo_upload', 'bragsy_logo_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">Bragsy Logo</th>
                    <td>
                        <?php $logo_url = get_option('bragsy_seller_logo'); ?>
                        <?php if ($logo_url) : ?>
                            <img src="<?php echo esc_url($logo_url); ?>" style="max-height: 100px; display: block; margin-bottom: 10px;">
                        <?php endif; ?>
                        <input type="file" name="bragsy_seller_logo" id="bragsy_seller_logo">
                        <p class="description">Upload new Bragsy seller logo (Recommended size: 300x100px)</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Logo'); ?>
        </form>
    </div>
    <?php
}

// Handle logo upload
add_action('admin_init', 'bragsy_handle_logo_upload');
function bragsy_handle_logo_upload() {
    if (!isset($_POST['bragsy_logo_nonce']) || !wp_verify_nonce($_POST['bragsy_logo_nonce'], 'bragsy_logo_upload')) {
        return;
    }

    if (!empty($_FILES['bragsy_seller_logo']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        
        $upload = wp_handle_upload($_FILES['bragsy_seller_logo'], array(
            'test_form' => false,
            'mimes' => array(
                'jpg|jpeg|jpe' => 'image/jpeg',
                'gif' => 'image/gif',
                'png' => 'image/png'
            )
        ));

        if (!isset($upload['error'])) {
            update_option('bragsy_seller_logo', $upload['url']);
            add_settings_error('bragsy_logo_messages', 'bragsy_logo_updated', 'Logo updated successfully!', 'updated');
        } else {
            add_settings_error('bragsy_logo_messages', 'bragsy_logo_error', $upload['error'], 'error');
        }
    }
}

// Display admin notices
add_action('admin_notices', 'bragsy_logo_admin_notices');
function bragsy_logo_admin_notices() {
    settings_errors('bragsy_logo_messages');
}

function get_bragsy_seller_logo() {
    $logo_url = get_option('bragsy_seller_logo');
    return $logo_url ? $logo_url : null;
}