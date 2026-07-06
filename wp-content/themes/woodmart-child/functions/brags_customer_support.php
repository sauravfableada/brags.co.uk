<?php

function create_bragger_role() {
    add_role(
        'bragger',
        'Bragger',
        [
            'read' => true, // Basic capability to read content
            'manage_tickets' => true, // Custom capability for support management
        ]
    );
}
add_action('init', 'create_bragger_role');

function restrict_customer_support_page() {
    if (is_page('customer-support') && !is_user_logged_in()) {
        wp_redirect(home_url('/my-account/?action=login')); // Redirect to login page
        exit;
    }
}
add_action('template_redirect', 'restrict_customer_support_page');


function update_support_ticket_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'support_tickets';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        subject TEXT NOT NULL,
        message TEXT NOT NULL,
        extra_text TEXT NOT NULL,
        status ENUM('Open', 'Closed', 'In Progress') DEFAULT 'Open',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'update_support_ticket_table');

function update_support_ticket_table_if_missing() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'support_tickets';

    // Check if the table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        update_support_ticket_table(); // Call the function again
    }
}
add_action('admin_init', 'update_support_ticket_table_if_missing');

function custom_add_my_account_endpoint() {
    add_rewrite_endpoint('brags-support-tickets', EP_ROOT | EP_PAGES);
}
add_action('init', 'custom_add_my_account_endpoint');
function custom_add_my_account_menu_items($items) {
    // Insert "Support Tickets" before "support-tickets"
    $new_items = array();

    foreach ($items as $key => $title) {
        $new_items[$key] = $title;
        if ($key === 'support-tickets') { // Add after support-tickets
            $current_user = wp_get_current_user();
            // echo "<pre>";
            // print_r($current_user->roles);
            // echo "</pre>";
            if (in_array('subscriber', $current_user->roles)|| in_array('customer', $current_user->roles)) {
                $new_items['brags-support-tickets'] = __('Brags Customer Support', 'woocommerce');
            }
        }
    }

    return $new_items;
}
add_filter('woocommerce_account_menu_items', 'custom_add_my_account_menu_items');

function custom_support_tickets_content() {
    if(isset($_GET['view_ticket']) && $_GET['view_ticket']!=''){
        get_template_part('template-parts/my-account/view-ticket');
    }else{
        $current_user = wp_get_current_user();

        //subscriber // bragger
        if (in_array('subscriber', $current_user->roles)) {
            get_template_part('template-parts/my-account/brags-support-tickets');
        }else{
            get_template_part('template-parts/my-account/customer-support-tickets');
        }
        
    }
    
}
add_action('woocommerce_account_brags-support-tickets_endpoint', 'custom_support_tickets_content');

function brags_enqueue_support_scripts() {
    //if (is_account_page() && isset($_GET['ticket_status'])) {
    if (is_account_page()) { // Load only on the support page
        wp_enqueue_style('brags-support-css', get_stylesheet_directory_uri() . '/assets/css/brags-support-tickets.css', [], '1.0.0');
        wp_enqueue_script('brags-support-js', get_stylesheet_directory_uri() . '/assets/js/brags-support-tickets.js', ['jquery'], '1.0.0', true);

        // Pass AJAX URL to JS
        wp_localize_script('brags-support-js', 'brags_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('update_ticket_status')
        ]);
    }
}
add_action('wp_enqueue_scripts', 'brags_enqueue_support_scripts');


function brags_update_ticket_status() {
    // Security check
    check_ajax_referer('update_ticket_status', 'nonce');

    global $wpdb;
    $table_name = $wpdb->prefix . 'support_tickets';

    $ticket_id = intval($_POST['ticket_id']);
    $new_status = sanitize_text_field($_POST['status']);

    if ($ticket_id > 0 && in_array($new_status, ['Open', 'In Progress', 'Closed'])) {
        $wpdb->update(
            $table_name,
            ['status' => $new_status],
            ['id' => $ticket_id],
            ['%s'],
            ['%d']
        );

        wp_send_json_success(['message' => 'Status updated successfully!']);
    } else {
        wp_send_json_error(['message' => 'Invalid request!']);
    }
}
add_action('wp_ajax_brags_update_ticket_status', 'brags_update_ticket_status');
add_action('wp_ajax_nopriv_brags_update_ticket_status', 'brags_update_ticket_status'); // Allow for non-logged-in users if needed


function add_user_id_to_cf7() {
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                let userField = document.querySelector('input[name="user_id"]');
                if (userField) {
                    userField.value = "<?php echo esc_js($user_id); ?>";
                }
            });
        </script>
        <?php
    }
}
add_action('wp_footer', 'add_user_id_to_cf7');

function save_support_ticket($form_data) {
  
    //if (!is_user_logged_in()) return; // Only logged-in users can submit tickets

    global $wpdb;
    $user_id = isset($form_data['user_id']) ? (int) $form_data['user_id'] : get_current_user_id();

    $table_name = $wpdb->prefix . 'support_tickets';

    // Extract form data
    $name = sanitize_text_field($form_data['your-name']);
    $email = sanitize_email($form_data['your-email']);
    $phone = sanitize_text_field($form_data['tel-767']);
    $subject = sanitize_text_field($form_data['your-subject']??'Support');
    $message = sanitize_textarea_field($form_data['your-message']);
    $extra_text = sanitize_textarea_field($form_data['text-1']);

    // Insert into database
    $wpdb->insert(
        $table_name,
        [
            'user_id'    => $user_id,
            'name'       => $name,
            'email'      => $email,
            'phone'      => $phone,
            'subject'    => $subject,
            'message'    => $message,
            'extra_text' => $extra_text,
            'status'     => 'Open',
        ],
        ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
    );
}

// Hook into Contact Form 7 submission
add_action('wpcf7_mail_sent', function ($contact_form) {
    $submission = WPCF7_Submission::get_instance();

    if ($submission) {
        $posted_data = $submission->get_posted_data();
        save_support_ticket($posted_data);
    }
});



// view ticket

function brags_create_support_logs_table() {
    global $wpdb;
    $log_table = $wpdb->prefix . 'support_ticket_logs';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $log_table (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        ticket_id BIGINT UNSIGNED NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        message TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
add_action('after_setup_theme', 'brags_create_support_logs_table');

function brags_add_ticket_reply() {
    //check_ajax_referer('update_ticket_status', 'nonce');

    global $wpdb;
    $log_table = $wpdb->prefix . 'support_ticket_logs';

    $ticket_id = intval($_POST['ticket_id']);
    $user_id = get_current_user_id();
    $message = sanitize_textarea_field($_POST['reply_message']);

    if ($ticket_id > 0 && !empty($message)) {
        $wpdb->insert(
            $log_table,
            [
                'ticket_id'  => $ticket_id,
                'user_id'    => $user_id,
                'message'    => $message,
                'created_at' => current_time('mysql')
            ],
            ['%d', '%d', '%s', '%s']
        );

        wp_send_json_success(['message' => 'Reply added!']);
    } else {
        wp_send_json_error(['message' => 'Invalid request!']);
    }
}
add_action('wp_ajax_brags_add_ticket_reply', 'brags_add_ticket_reply');
add_action('wp_ajax_nopriv_brags_add_ticket_reply', 'brags_add_ticket_reply');














