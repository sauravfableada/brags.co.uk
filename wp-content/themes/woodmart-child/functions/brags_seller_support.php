<?php

// set create ticket capability for seller role
function add_create_ticket_capability_to_seller() {
    $role = get_role('seller'); // Replace 'seller' with your actual seller role slug
    if ($role) {
        $role->add_cap('create_ticket'); // Grant permission to create tickets
        $role->add_cap('view_ticket');
    }
    $role = get_role('subscriber');
    if ($role) {
        //$role->add_cap('create_ticket'); // Grant permission to create tickets
        $role->add_cap('view_ticket');
        $role->add_cap('edit_ticket');      // Allow editing their own ticket
        $role->add_cap('edit_tickets');     // Allow editing all tickets
        $role->add_cap('delete_ticket');    // Allow deleting their own ticket
        $role->add_cap('delete_tickets');   // Allow deleting any ticket
        $role->add_cap('publish_tickets');  // Allow publishing tickets
        $role->add_cap('read_ticket');      // Allow reading their own ticket
        $role->add_cap('read_private_tickets'); // Allow reading private tickets
    }
}
add_action('init', 'add_create_ticket_capability_to_seller');



function add_dokan_brags_seller_team_menu( $urls ) {

    $current_user = wp_get_current_user();

    //print_r($current_user->roles);
    if (in_array('seller', $current_user->roles)) {

        $urls['brags-seller-team'] = array(
            'title'      => __( 'Brags Seller Team', 'dokan' ),
            'icon'       => '<i class="fas fa-users"></i>',
            'url'        => dokan_get_navigation_url( 'brags-seller-team' ),
            'pos'        => 67,
            'permission' => 'dokan_view_product_menu',
        );
    }
    return $urls;
}
add_filter( 'dokan_get_dashboard_nav', 'add_dokan_brags_seller_team_menu' );
function register_dokan_brags_seller_team_query_var( $query_vars ) {
    $query_vars[] = 'brags-seller-team';
    return $query_vars;
}
add_filter( 'query_vars', 'register_dokan_brags_seller_team_query_var' );

function load_dokan_brags_seller_team_template() {
    global $wp_query;

    if ( isset( $wp_query->query_vars['brags-seller-team'] ) ) {
        $template_path = get_stylesheet_directory() . '/template-parts/dokan/brags-seller-team.php';

        if ( file_exists( $template_path ) ) {
            include $template_path;
            exit;
        } else {
            wp_die( 'Brags Seller Team template not found.' );
        }
    }
}
add_action( 'template_redirect', 'load_dokan_brags_seller_team_template' );
function flush_brags_seller_team_rules() {
    add_rewrite_endpoint( 'brags-seller-team', EP_ROOT | EP_PAGES );
    flush_rewrite_rules();
}
add_action( 'init', 'flush_brags_seller_team_rules' );




// Register the 'brags-my-ticket' query variable
function register_brags_my_ticket_query_var( $query_vars ) {
    $query_vars[] = 'brags-my-ticket';
    return $query_vars;
}
add_filter( 'query_vars', 'register_brags_my_ticket_query_var' );

// Load the 'Brags My Ticket' template
function load_brags_my_ticket_template() {
    global $wp_query;

    if ( isset( $wp_query->query_vars['brags-my-ticket'] ) ) {
        
        if(isset($_GET['ticket_id']) && $_GET['ticket_id']!=''){
            $template_path = get_stylesheet_directory() . '/template-parts/dokan/brags-my-ticket-single.php';
        }else{
            $template_path = get_stylesheet_directory() . '/template-parts/dokan/brags-my-ticket.php';
        }

        if ( file_exists( $template_path ) ) {
            include $template_path;
            exit;
        } else {
            wp_die( 'Brags My Ticket template not found.' );
        }
    }
}
add_action( 'template_redirect', 'load_brags_my_ticket_template' );

// Flush rewrite rules (only needed once)
function flush_brags_my_ticket_rewrite_rules() {
    add_rewrite_endpoint( 'brags-my-ticket', EP_ROOT | EP_PAGES );
    flush_rewrite_rules();
}
add_action( 'init', 'flush_brags_my_ticket_rewrite_rules' );

function seller_suport_my_account_endpoint() {
    add_rewrite_endpoint('brags-seller-support-tickets', EP_ROOT | EP_PAGES);
}
add_action('init', 'seller_suport_my_account_endpoint');

function add_brags_tickers_link($items) {
    $current_user = wp_get_current_user();

    //print_r($current_user->roles);
    if (in_array('subscriber', $current_user->roles)) {
    // Add a new menu item (change slug if needed)
        $items['brags-seller-support-tickets'] = __('Brags Seller Support Tickets', 'text-domain');
    }
    return $items;
}
add_filter('woocommerce_account_menu_items', 'add_brags_tickers_link');


function seller_support_tickets_content() {
    echo do_shortcode('[admin_tickets_list]');
}
add_action('woocommerce_account_brags-seller-support-tickets_endpoint', 'seller_support_tickets_content');


function display_awesome_support_tickets() {
    // if (!current_user_can('manage_options')) {
    //     return '<p>You do not have permission to view tickets.</p>';
    // }
    if(isset($_GET['ticket_id']) && $_GET['ticket_id']!=""){
        ob_start();
        get_template_part('template-parts/my-account/brags-support-ticket-single');
        wp_reset_postdata();
    }else{
        ob_start();
        get_template_part('template-parts/my-account/brags-to-seller-suport');
        wp_reset_postdata();
    }
    
    return ob_get_clean();
}


// Use this shortcode to display the ticket list in a page
add_shortcode('admin_tickets_list', 'display_awesome_support_tickets');


function user_ticket_list_frontend() {
    if (!is_user_logged_in()) {
        return '<div class="alert alert-warning text-center">Please log in to manage your tickets.</div>';
    }

    $current_user_id = get_current_user_id();

    $args = array(
        'post_type'      => 'ticket',
        'posts_per_page' => 10,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    $query = new WP_Query($args);
    ob_start();

    if ($query->have_posts()) {
        echo '<div class="container">';
        echo '<h2 class="text-xl font-bold mb-4">Tickets</h2>';
        echo '<div class="table-responsive">';
        echo '<table class="table table-bordered table-hover w-full border-collapse border border-gray-300">';
        echo '<thead class="bg-gray-200">';
        echo '<tr>';
        echo '<th class="p-2 border">Ticket Title</th>';
        echo '<th class="p-2 border">Status</th>';
        echo '<th class="p-2 border">Date</th>';
        echo '<th class="p-2 border">Actions</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        while ($query->have_posts()) {
            $query->the_post();
            $edit_url = add_query_arg('edit_ticket', get_the_ID(), site_url('/edit-ticket-page/')); // Replace with your actual edit page

            echo '<tr>';
            echo '<td class="p-2 border"><a href="' . get_permalink() . '" class="text-blue-600 hover:underline">' . get_the_title() . '</a></td>';
            echo '<td class="p-2 border text-sm font-semibold text-' . (get_post_status() == 'publish' ? 'green-600' : 'red-600') . '">' . ucfirst(get_post_status()) . '</td>';
            echo '<td class="p-2 border">' . get_the_date('F j, Y') . '</td>';
            echo '<td class="p-2 border text-center">';
            echo '<a href="' . esc_url($edit_url) . '" class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">Edit</a>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
        echo '</div>';
        echo '</div>';
    } else {
        echo '<div class="alert alert-info text-center">No tickets found.</div>';
    }

    wp_reset_postdata();
    return ob_get_clean();
}

add_shortcode('user_tickets', 'user_ticket_list_frontend');



function submit_ticket_reply() {
    if (!isset($_POST['ticket_id'], $_POST['ticket_reply'])) {
        wp_send_json_error('Invalid request.');
    }

    $ticket_id = intval($_POST['ticket_id']);
    $ticket_reply = sanitize_text_field($_POST['ticket_reply']);

    if (empty($ticket_reply)) {
        wp_send_json_error('Reply cannot be empty.');
    }

    $current_user = wp_get_current_user();

    // Ensure the user has permission to reply
    $ticket = get_post($ticket_id);
    if (!$ticket || $ticket->post_type !== 'ticket') {
        wp_send_json_error('Invalid ticket.');
    }

    // Insert comment as a reply
    $comment_data = [
        'comment_post_ID' => $ticket_id,
        'comment_content' => $ticket_reply,
        'comment_author' => $current_user->display_name,
        'user_id' => $current_user->ID,
        'comment_approved' => 1,
    ];
    wp_insert_comment($comment_data);

    wp_send_json_success();
}

add_action('wp_ajax_submit_ticket_reply', 'submit_ticket_reply');
add_action('wp_ajax_nopriv_submit_ticket_reply', 'submit_ticket_reply'); // Allow non-logged-in users if needed

function save_confirmed_legel_rights_field($post_id ){
    global $woocommerce_errors;
     // Validate legal rights confirmation checkbox
    $legal_confirmation = isset($_POST['legal_confirmation']) ? '1' : '';

    if ($legal_confirmation=='') {
        $woocommerce_errors[] = __('You must confirm that you have the legal rights to sell this product before submitting.', 'dokan-lite');
        return;
    }

    // Save legal rights confirmation
    update_post_meta($post_id, '_legal_confirmation', $legal_confirmation);
}

add_action( 'dokan_process_product_meta', 'save_confirmed_legel_rights_field' );

function display_legal_confirmation_under_additional_info($product) {
    $legal_confirmation = get_post_meta($product->get_id(), '_legal_confirmation', true);

    if ($legal_confirmation == '1') {
        echo '<br>';
        echo '<p><strong>' . esc_html__('The seller has confirmed that they (or their company) have the legal rights to sell this product in the UK, possess the necessary supporting documentation, hold valid/appropriate Insurance policies, will respond promptly to customer enquiries, manage returns in a fair and efficient manner, and acknowledge that Brags & Partners Ltd assumes no responsibility for this product.', 'dokan-lite') . '</strong></p>';
    }
}
add_action('woocommerce_product_additional_information', 'display_legal_confirmation_under_additional_info');


