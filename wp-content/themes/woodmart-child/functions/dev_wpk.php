<?php



function clinic_form_shortcode() {
    ob_start();
    ?>
    <style>
      .button-wrapper {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 50px;
        flex-wrap: wrap;
      }

      .clinic-button {
        padding: 8px 30px;
        font-size: 16px;
        background-color: #d3d3d3 !important;
        color: black;
        border: none;
        cursor: pointer;
        border-radius: 8px;
        font-weight: bold;
        transition: background-color 0.3s ease;
        min-width: 140px;
      }

      .clinic-button.active {
        background-color: #f8ba07 !important;
      }

      #formContainer {
        margin-top: 50px;
        text-align: center;
        position: relative;
        min-height: 300px;
      }

      .iframe-wrapper {
        width: 100%;
        max-width: 100%;
        aspect-ratio: 4 / 3;
        position: relative;
      }

      .iframe-wrapper iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: none;
      }

      #formLoader {
        display: none;
        position: absolute;
        top: 40%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 18px;
        font-weight: bold;
        color: #333;
      }

      @media (max-width: 600px) {
        .clinic-button {
          min-width: 120px;
        }

        .iframe-wrapper {
          aspect-ratio: 3 / 4;
        }
      }
    </style>

    <div id="clinicSection">
      <div class="button-wrapper">
        <button id="greenwichBtn" class="clinic-button">Greenwich</button>
        <button id="batterseaBtn" class="clinic-button">Battersea</button>
      </div>

      <div id="formContainer">
        <div id="formLoader">Loading form...</div>
        <div class="iframe-wrapper" id="iframeWrapper">
          <iframe id="clinicForm" src=""></iframe>
        </div>
      </div>
    </div>

    <script>
      document.addEventListener("DOMContentLoaded", function () {
        const greenwichBtn = document.getElementById("greenwichBtn");
        const batterseaBtn = document.getElementById("batterseaBtn");
        const clinicForm = document.getElementById("clinicForm");
        const formLoader = document.getElementById("formLoader");
        const clinicSection = document.getElementById("clinicSection");

        function setActiveButton(button) {
          greenwichBtn.classList.remove("active");
          batterseaBtn.classList.remove("active");
          button.classList.add("active");
        }

        function scrollToClinicSection() {
          const yOffset = -20;
          const y = clinicSection.getBoundingClientRect().top + window.pageYOffset + yOffset;
          window.scrollTo({ top: y, behavior: "smooth" });
        }

        function showForm(location) {
          let src = "";
          if (location === "greenwich") {
            src = "https://referral.clin-sync.com/contact/313736/enquiry";
            setActiveButton(greenwichBtn);
          } else if (location === "battersea") {
            src = "https://referral.clin-sync.com/contact/313832/enquiry/";
            setActiveButton(batterseaBtn);
          }

          clinicForm.style.display = "none";
          formLoader.style.display = "block";

          clinicForm.onload = function () {
            formLoader.style.display = "none";
            clinicForm.style.display = "block";
            scrollToClinicSection();
          };

          clinicForm.src = src;
        }

        greenwichBtn.addEventListener("click", () => {
          showForm("greenwich");
        });

        batterseaBtn.addEventListener("click", () => {
          showForm("battersea");
        });

        // Only auto-load form if not iOS (to prevent Safari blocking)
        if (!/iPhone|iPad|iPod/.test(navigator.userAgent)) {
          showForm("greenwich");
        } else {
          let loaded = false;
          function loadFormOnce() {
            if (!loaded) {
              loaded = true;
              showForm("greenwich");
            }
          }
          window.addEventListener("scroll", loadFormOnce, { once: true });
          window.addEventListener("touchstart", loadFormOnce, { once: true });
        }

        // Scroll if hash is present
        if (window.location.hash === "#clinicSection") {
          setTimeout(() => {
            scrollToClinicSection();
          }, 100);
        }
      });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('clinic_form', 'clinic_form_shortcode');



add_filter('wp_authenticate_user', 'brags_allow_pending_seller_login', 1);

function brags_allow_pending_seller_login($user) {
    if (!is_object($user) || empty($user->ID)) {
        return $user;
    }
    

    $status = get_user_meta($user->ID, 'pw_user_status', true);
    

    if ($status === 'pending') {
        $roles = (array) $user->roles;

        if (in_array('seller', $roles)) {
            // ✅ Allow seller login — override plugin before it runs
             $redirect_url = site_url('/seller-application-review/');
            wp_safe_redirect($redirect_url);
            exit;
            //return $user;
        }

        // Block everyone else (e.g. customers)
        return new WP_Error('pending_approval', __('Your account is still pending approval.', 'new-user-approve'));
    }

    if ($status === 'denied') {
        return new WP_Error('denied_access', __('Your account has been denied access to this site.', 'new-user-approve'));
    }

    return $user;
}


add_action('woocommerce_thankyou', 'dokan_show_trial_message_on_thankyou', 20);
add_action('woocommerce_order_details_after_order_table', 'dokan_show_trial_message_on_order_page', 20);

function dokan_show_trial_message_on_thankyou($order_id) {
    dokan_check_and_show_trial_message($order_id);
}

function dokan_show_trial_message_on_order_page($order) {
    if (is_a($order, 'WC_Order')) {
        $order_id = $order->get_id();
        dokan_check_and_show_trial_message($order_id);
    }
}

// Show on Email Confirmation
add_action('woocommerce_email_after_order_table', 'dokan_add_6_month_trial_to_email', 10, 4);
function dokan_add_6_month_trial_to_email($order, $sent_to_admin, $plain_text, $email) {
    dokan_check_and_show_trial_message($order->get_id());
}

function dokan_check_and_show_trial_message($order_id) {
    $order = wc_get_order($order_id);
    
    
    if (!$order) return;

    foreach ($order->get_items() as $item) {
        $product_name = strtolower($item->get_name());
        $product_id = $item->get_product_id();
        
        $dokan_subscription = dokan()->subscription->get($product_id);
        if (! $dokan_subscription || ! is_object($dokan_subscription)) {
            continue;
        }
       

        // If the plan has a trial period
        //$dokan_subscription->is_trial()
        if ($dokan_subscription->get_trial_period_length()) {
            $trial_length = (int) $dokan_subscription->get_trial_period_length();
            $dokan_subscription_trail_range = get_post_meta($product_id,'dokan_subscription_trail_range',true);
            $dokan_subscription_trial_period_types = get_post_meta($product_id,'dokan_subscription_trial_period_types',true);
             // Get trial info from Dokan's own fields
            $trial_range = (int) get_post_meta($product_id, 'dokan_subscription_trail_range', true);
            $trial_type = get_post_meta($product_id, 'dokan_subscription_trial_period_types', true); // e.g. day, month, year


            if ($trial_range > 0 && $trial_type) {
                // Capitalize and pluralize if needed
                $formatted_type = ucfirst($trial_type);
                if ($trial_range > 1) {
                    $formatted_type .= 's';
                }

                echo '<p style="font-weight:bold; color:#681da8;">
                    You are currently in your ' . $trial_range . ' ' . $formatted_type . ' Trial period and have not been charged for this selling plan.
                    After your trial period ends, you will be charged regularly unless you choose to cancel your seller plan.
                </p>';
                break;
            }
            
        }

        
       
    }
}




// Change the email subject
add_filter('new_user_approve_welcome_user_subject', function($subject) {
    return 'Your Brags Seller Application is under Review';
});

add_filter('new_user_approve_welcome_user_message', function($message, $user_email) {
    $message = '<p>Hi there,</p>';
    $message .= '<p>Thank you for registering to sell on <strong>Brags</strong>.</p>';
    $message .= '<p>We’ve successfully received your request for a Seller Account and it is currently <strong>pending approval</strong>.</p>';
    $message .= '<p>Our team will review your information as soon as possible.</p>';
    $message .= '<p>You’ll receive an email with further instructions on what to do next, we typical approve applications with 1 - 2 Business days.</p>';
    $message .= '<p>Many thanks,<br>The Brags Seller Team</p>';
    
    return $message;
}, 10, 2);

add_filter('new_user_approve_email_header', function($headers) {
    if (!is_array($headers)) {
        $headers = [$headers];
    }

    $headers[] = 'Content-Type: text/html; charset=UTF-8';

    return $headers;
});


// Change the email message content
// add_filter('new_user_approve_welcome_user_message', function($message, $user_email) {
//     $message = "Hi there,\r\n\r\n";
//     $message .= "Thank you for registering to sell on Brags.\r\n\r\n";
//     $message .= "We’ve successfully received your request for a Seller Account and it is currently pending approval.\r\n\r\n";
//     $message .= "Our team will review your information as soon as possible.\r\n\r\n";
//     $message .= "You’ll receive an email with further instructions on what to do next. We typically approve applications within 1–2 business days.\r\n\r\n";
//     $message .= "Many thanks,\r\n";
//     $message .= "The Brags Seller Team";
    
//     return $message;
// }, 10, 2);

// ---------------------------------------------------------------------------------------------------

add_action('dokan_staff_form_fields', 'custom_dokan_vendor_staff_extra_fields', 10, 2);
function custom_dokan_vendor_staff_extra_fields($staff_id, $staff_info) {
    $phone = isset($staff_info['phone']) ? esc_attr($staff_info['phone']) : '';
    $department = isset($staff_info['department']) ? esc_attr($staff_info['department']) : '';
    ?>
    <div class="dokan-form-group">
        <label for="staff_phone" class="form-label"><?php esc_html_e('Phone Number', 'textdomain'); ?></label>
        <input type="text" name="phone" id="staff_phone" class="dokan-form-control" value="<?php echo $phone; ?>">
    </div>

    <div class="dokan-form-group">
        <label for="staff_department" class="form-label"><?php esc_html_e('Department', 'textdomain'); ?></label>
        <input type="text" name="department" id="staff_department" class="dokan-form-control" value="<?php echo $department; ?>">
    </div>
    <?php
}






add_action('dokan_after_save_staff', 'bragsy_send_staff_creation_email', 20, 2);
function bragsy_send_staff_creation_email($vendor_id, $staff_id) {
    $staff_user = get_userdata($staff_id);

    if (!$staff_user || empty($staff_user->user_email)) {
        return;
    }

    $email = $staff_user->user_email;
    $first_name = get_user_meta($staff_id, 'first_name', true);
    $vendor = get_userdata($vendor_id);

    // Generate WooCommerce-compatible password reset link
    $reset_key = get_password_reset_key($staff_user);
    $user_login = rawurlencode($staff_user->user_login);
    $reset_url = wc_get_page_permalink('myaccount') . "lost-password/?key={$reset_key}&login={$user_login}";

    $subject = "Welcome to Brags - Set Your Password";
    $message = "
        <p>Hi {$first_name},</p>
        <p>Your staff account has been created by vendor <strong>{$vendor->display_name}</strong>.</p>
        <p>You can log in using your email: <strong>{$email}</strong></p>
        <p><strong>Set your password here:</strong> <a href='{$reset_url}'>Click to set password</a></p>
        <br>
        <p>Regards,<br>Brags Team</p>
    ";

    $headers = ['Content-Type: text/html; charset=UTF-8'];

    wp_mail($email, $subject, $message, $headers);
}


add_action( 'dokan_after_save_staff', 'save_staff_country_code', 10, 2 );
function save_staff_country_code( $vendor_id, $staff_id ) {
    if ( isset( $_POST['country_code'] ) ) {
        $country_code = sanitize_text_field( $_POST['country_code'] );
        update_user_meta( $staff_id, 'country_code', $country_code );
    }
    if ( isset( $_POST['staff_otp'] ) ) {
        $country_code = sanitize_text_field( $_POST['staff_otp'] );
        update_user_meta( $staff_id, 'staff_otp_verified', $country_code );
    }
}

add_action('wp_enqueue_scripts', 'add_staff_dashboard_js_by_url');
function add_staff_dashboard_js_by_url() {
    $current_url = home_url($_SERVER['REQUEST_URI']);
    
    // Check if URL contains /dashboard/staffs
    if (strpos($current_url, '/dashboard/staffs') !== false) {
        // Enqueue intl-tel-input CSS from CDN
        wp_enqueue_style(
            'intl-tel-input-css',
            'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/css/intlTelInput.css',
            array(),
            '17.0.13'
        );
        
        // Enqueue intl-tel-input JS from CDN (with jQuery as dependency)
        wp_enqueue_script(
            'intl-tel-input-js',
            'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/js/intlTelInput-jquery.min.js',
            array('jquery'),
            '17.0.13',
            true
        );
        wp_enqueue_script(
            'dokan-staff-custom-js',
            get_stylesheet_directory_uri() . '/assets/js/dokan-staff-custom.js',
            array('jquery'),
            filemtime(get_stylesheet_directory() . '/assets/js/dokan-staff-custom.js'),
            true
        );
        // Localize script with AJAX URL and nonce
        wp_localize_script('dokan-staff-custom-js', 'dokanStaffData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('dokan_staff_nonce'),
            'is_edit'  => ! empty( $_GET['staff_id'] ) ? '1' : '0',

        ));
    }
}

add_filter('lostpassword_url', 'custom_lostpassword_url', 999, 2);
function custom_lostpassword_url($lostpassword_url, $redirect) {
    // Only modify for front-end requests
    if (!is_admin() && function_exists('wc_get_account_endpoint_url')) {
        $wc_url = wc_get_account_endpoint_url('lost-password');
        
        // Only return WC URL if it's not empty and different from default
        if ($wc_url && $wc_url != $lostpassword_url) {
            // Preserve redirect parameter if it exists
            if (!empty($redirect)) {
                $wc_url = add_query_arg('redirect_to', urlencode($redirect), $wc_url);
            }
            return $wc_url;
        }
    }
    
    return $lostpassword_url;
}

add_filter( 'dokan_get_dashboard_nav', function( $navs ) {
    // Remove default subscription
    
    //unset( $navs['staffs'] );

    if(isset($navs['subscription'])){
        unset( $navs['subscription'] );
        // Add custom subscription tab
        $navs['subscription'] = array(
            'title' => __( 'Brags Selling Plan', 'your-text-domain' ),
            'icon'  => '<i class="fas fa-book"></i>',
            'url'   =>site_url('dashboard/subscription'),
            'pos'   => 55, // position in the menu, change as needed
        );

    }

    if(isset($navs['staffs'])){
        unset( $navs['staffs'] );
        $navs['staffs'] = array(
            'title' => __( 'Staff', 'your-text-domain' ),
            'icon'  => '<i class="fas fa-users"></i>',
            'url'   =>site_url('dashboard/staffs'),
            'pos'   => 56, // position in the menu, change as needed
        );

    }
    

    
    

    return $navs;
}, 99 );


function show_brand_before_product_image() {
    global $post;

    // Get brand meta
    $brand_id      = get_post_meta($post->ID, '_brand_name', true);
    $custom_brand  = get_post_meta($post->ID, '_custom_brand_name', true);
    $no_brand      = get_post_meta($post->ID, '_no_brand', true);
    $brand_display = '';
    // if ($no_brand) {
    //     $brand_display = '';
    // } else

    if (!empty($brand_id)) {
        $term = get_term($brand_id, 'product_brand');
        if ($term && !is_wp_error($term)) {
            $brand_display = $term->name;
        }
    } elseif (!empty($custom_brand)) {
        $brand_display = esc_html($custom_brand);
    }
    echo '<style>
            .product-top-info {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 15px;
                width: 100%;
            }

            .product-brand {
                margin: 0;
            }

            .shipping-info {
                margin: 0;
                color: #2a5d84;
                font-weight: 600;
            }
    </style>';
     
    echo '<div class="product-top-info">';
    
    if ($brand_display) {
        echo '<p class="product-brand"><strong>Brand:</strong> ' . esc_html($brand_display) . '</p>';
    } else {
        echo '<div></div>'; // Empty div for spacing when no brand
    }
    
    echo '<p class="shipping-info">UK-Wide Shipping</p>';
    
    echo '</div>';
}
add_action('woocommerce_before_single_product', 'show_brand_before_product_image', 5);



add_action('wp_ajax_brags_update_ticket_status', 'admin_brags_update_ticket_status');

function admin_brags_update_ticket_status() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    global $wpdb;
    $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

    if ($ticket_id > 0 && in_array($status, ['Open', 'In Progress', 'Closed'])) {
        $updated = $wpdb->update(
            $wpdb->prefix . 'support_tickets',
            ['status' => $status],
            ['id' => $ticket_id],
            ['%s'],
            ['%d']
        );

        if ($updated !== false) {
            wp_send_json_success();
        }
    }

    wp_send_json_error('Failed to update status');
}
function brags_add_customer_support_menu() {
    // Check if the current user is an administrator
    if (current_user_can('administrator')) {
        // Create the custom menu item
        add_menu_page(
            'Brags Customer Support', // Page title
            'Brags Customer Support', // Menu title
            'manage_options', // Capability needed to access this menu
            'brags-customer-support', // Slug for the menu
            'brags_customer_support_page', // Function to display the page content
            'dashicons-sos', // Icon for the menu item (you can choose any Dashicon)
            25 // Position in the menu (higher value means lower position in the menu)
        );
    }
}
add_action('admin_menu', 'brags_add_customer_support_menu');
function brags_customer_support_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'support_tickets';

    // Check if the 'view_ticket' parameter is present
    $ticket_id = isset($_GET['view_ticket']) ? intval($_GET['view_ticket']) : 0;

    if ($ticket_id > 0) {
        // Fetch the specific ticket details
        $ticket = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $ticket_id));

        if ($ticket) {

            // Display ticket details
            ?>
            <div class="wrap">
                <h1><?php echo esc_html__('Ticket #', 'text-domain') . esc_html($ticket->id); ?> - <?php echo esc_html($ticket->subject); ?></h1>
                <p><strong><?php esc_html_e('Status:', 'text-domain'); ?></strong> <?php echo esc_html($ticket->status); ?></p>
                <p><strong><?php esc_html_e('Created On:', 'text-domain'); ?></strong> <?php echo esc_html($ticket->created_at); ?></p>
                <hr>
                <h3><?php esc_html_e('Ticket Messages', 'text-domain'); ?></h3>
                <p>
                    <?php echo $ticket->message??''; ?>
                </p>

                <?php
                // Fetch all the messages related to the ticket
                $log_table = $wpdb->prefix . 'support_ticket_logs';
                $messages = $wpdb->get_results($wpdb->prepare("SELECT * FROM $log_table WHERE ticket_id = %d ORDER BY created_at ASC", $ticket_id));

                if ($messages) {
                    foreach ($messages as $msg) {
                        $user = get_userdata($msg->user_id);
                        $role = $user ? $user->roles[0] : '';
                        $is_admin = $role == 'administrator' ? true : false;
                        ?>
                        <div class="ticket-message <?php echo $is_admin ? 'admin-response' : 'user-message'; ?>">
                            <strong><?php echo esc_html($user->user_login); ?>:</strong>
                            <p><?php echo esc_html($msg->message); ?></p>
                            <span class="msg-time"><?php echo esc_html($msg->created_at); ?></span>
                        </div>
                        <?php
                    }
                } else {
                    //echo "<p>" . esc_html__('No messages found.', 'text-domain') . "</p>";
                }
                ?>
                <hr>
                <h3><?php esc_html_e('Reply to Ticket', 'text-domain'); ?></h3>
                <form method="post" id="ticket-reply-form" class="ticket-reply-form">
                    <input type="hidden" name="ticket_id" value="<?php echo esc_attr($ticket_id); ?>">
                    <textarea rows="6" cols="50" name="reply_message" placeholder="Type your reply here..." required></textarea>
                    <br>
                    <button type="submit" class="button-primary"><?php esc_html_e('Send Reply', 'text-domain'); ?></button>
                </form>

                <p id="reply-status"></p>

                <script>
                    jQuery(document).ready(function ($) {
                        $("#ticket-reply-form").submit(function (e) {
                            e.preventDefault();
                            var formData = $(this).serialize();
                            $("#reply-status").text("<?php echo esc_js(__('Sending...', 'text-domain')); ?>").css("color", "blue");

                            $.post("<?php echo admin_url('admin-ajax.php'); ?>", formData + "&action=brags_add_ticket_reply", function (response) {
                                if (response.success) {
                                    $("#reply-status").text("<?php echo esc_js(__('Reply sent successfully!', 'text-domain')); ?>").css("color", "green");
                                    location.reload(); // Refresh the page to show new reply
                                } else {
                                    $("#reply-status").text("<?php echo esc_js(__('Error sending reply.', 'text-domain')); ?>").css("color", "red");
                                }
                            });
                        });

                        

                    });
                </script>
            </div>
            <?php
        } else {
            echo "<p>" . esc_html__('Ticket not found.', 'text-domain') . "</p>";
        }
    } else {

        if (isset($_GET['delete_ticket']) && current_user_can('manage_options')) {
            $delete_id = intval($_GET['delete_ticket']);
            if (check_admin_referer('delete_ticket_' . $delete_id)) {
                $wpdb->delete($table_name, ['id' => $delete_id]);
                $wpdb->delete($wpdb->prefix . 'support_ticket_logs', ['ticket_id' => $delete_id]); // Optional: delete logs too
        
                echo '<div class="notice notice-success"><p>Ticket deleted successfully.</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Security check failed. Ticket not deleted.</p></div>';
            }
            echo '<style>
                .delete-ticket-button {
                    background-color: #d63638;
                    color: white;
                    border-color: #a00;
                }
            </style>';
        }
        $current_status = isset($_GET['ticket_status']) ? $_GET['ticket_status'] : 'all';
        // If 'view_ticket' is not set, show the ticket listing page
        // Fetch all tickets for the logged-in admin
        //$tickets = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");

        $paged     = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page  = 10; // Set how many tickets per page
        $offset    = ($paged - 1) * $per_page;

        // Apply filtering by status if needed
        $status_filter = '';
        if (!empty($_GET['ticket_status']) && $_GET['ticket_status'] !== 'all') {
            $status = sanitize_text_field($_GET['ticket_status']);
            if( $status == 'open'){
                $status = 'Open';
            }else if( $status == 'closed'){
                $status = 'Closed';
            }else if( $status == 'in_progress'){
                $status = 'In Progress';
            }
            $status_filter = $wpdb->prepare("WHERE status = %s", $status);
        }

        $tickets = $wpdb->get_results("SELECT * FROM $table_name $status_filter ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");

        // if(isset($_GET['dev']) && $_GET['dev']=='k2'){
        //     print_r("SELECT * FROM $table_name $status_filter ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");
        // }
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name $status_filter");
        $total_pages = ceil($total_items / $per_page);

        // Fetch ticket counts for each status
        $ticket_counts = $wpdb->get_results("
            SELECT status, COUNT(*) as count FROM $table_name GROUP BY status", OBJECT_K);

        // Get counts dynamically
        $all_count = count($tickets);
        $open_count = isset($ticket_counts['Open']) ? $ticket_counts['Open']->count : 0;
        $closed_count = isset($ticket_counts['Closed']) ? $ticket_counts['Closed']->count : 0;
        $in_progress_count = isset($ticket_counts['In Progress']) ? $ticket_counts['In Progress']->count : 0;
        ?>

        <div class="wrap">
            <h1><?php esc_html_e('Support Tickets', 'text-domain'); ?></h1>

            <ul class="subsubsub ticket-tabs" style="display: none;">
                <li><a href="?page=brags-customer-support&ticket_status=all" class="ticket-tab <?php echo $current_status === 'all' ? 'active' : ''; ?>">
                    <?php esc_html_e('All Tickets', 'text-domain'); ?> (<?php echo $all_count; ?>)</a> |
                </li>
                <li><a href="?page=brags-customer-support&ticket_status=open" class="ticket-tab <?php echo $current_status === 'open' ? 'active' : ''; ?>">
                    <?php esc_html_e('Open', 'text-domain'); ?> (<?php echo $open_count; ?>)</a> |
                </li>
                <li><a href="?page=brags-customer-support&ticket_status=closed" class="ticket-tab <?php echo $current_status === 'closed' ? 'active' : ''; ?>">
                    <?php esc_html_e('Closed', 'text-domain'); ?> (<?php echo $closed_count; ?>)</a> |
                </li>
                <li><a href="?page=brags-customer-support&ticket_status=in_progress" class="ticket-tab <?php echo $current_status === 'in_progress' ? 'active' : ''; ?>">
                    <?php esc_html_e('In Progress', 'text-domain'); ?> (<?php echo $in_progress_count; ?>)</a>
                </li>
            </ul>

            <div class="dokan-support-topics-list">
                <?php if (!empty($tickets)) : ?>
                    <table class="widefat fixed striped" cellspacing="0">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('ID', 'text-domain'); ?></th>
                                <th><?php esc_html_e('Subject', 'text-domain'); ?></th>
                                <th><?php esc_html_e('Status', 'text-domain'); ?></th>
                                <th><?php esc_html_e('Date', 'text-domain'); ?></th>
                                <th><?php esc_html_e('Action', 'text-domain'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $ticket) : ?>
                                <tr class="ticket-row" data-status="<?php echo strtolower($ticket->status); ?>">
                                    <td><?php echo esc_html($ticket->id); ?></td>
                                    <td><?php echo esc_html($ticket->subject); ?></td>
                                    <td>
                                        <?php // echo esc_html($ticket->status); ?>
                                        <p><strong><?php esc_html_e('Status:', 'text-domain'); ?></strong>
                                            <select class="ticket-status" data-ticket-id="<?php echo esc_attr($ticket->id); ?>">
                                                <option value="Open" <?php selected($ticket->status, 'Open'); ?>>Open</option>
                                                <option value="In Progress" <?php selected($ticket->status, 'In Progress'); ?>>In Progress</option>
                                                <option value="Closed" <?php selected($ticket->status, 'Closed'); ?>>Closed</option>
                                            </select>
                                        </p>

                                    </td>
                                    <td><?php echo esc_html($ticket->created_at); ?></td>
                                    <td>
                                        <a href="?page=brags-customer-support&view_ticket=<?php echo esc_attr($ticket->id); ?>" class="button"><?php esc_html_e('View', 'text-domain'); ?></a>
                                        <a  href="<?php echo wp_nonce_url(admin_url('admin.php?page=brags-customer-support&delete_ticket=' . $ticket->id), 'delete_ticket_' . $ticket->id); ?>" 
                                            class="button delete-ticket-button" 
                                            onclick="return confirm('Are you sure you want to delete this ticket?');">
                                            <?php esc_html_e('Delete', 'text-domain'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php

                    if ($total_pages > 1) {
                        echo '<style>
                            .tablenav-pages {
                                margin-top: 15px;
                            }
                            .tablenav-pages .button {
                                margin-right: 5px;
                                text-decoration: none;
                            }
                            .tablenav-pages .current-page {
                                font-weight: bold;
                                background-color: #007cba;
                                color: #fff;
                            }
                        </style>';

                        $base_url = remove_query_arg('paged'); // Keep other filters
                    
                        echo '<div class="tablenav"><div class="tablenav-pages"><span class="pagination-links">';
                    
                        // Previous Page Link
                        if ($paged > 1) {
                            echo '<a class="prev-page button" href="' . esc_url(add_query_arg('paged', $paged - 1, $base_url)) . '">&laquo;</a>';
                        }
                    
                        // Page numbers
                        for ($i = 1; $i <= $total_pages; $i++) {
                            $class = ($i == $paged) ? 'button current-page' : 'button';
                            echo '<a class="' . esc_attr($class) . '" href="' . esc_url(add_query_arg('paged', $i, $base_url)) . '">' . $i . '</a>';
                        }
                    
                        // Next Page Link
                        if ($paged < $total_pages) {
                            echo '<a class="next-page button" href="' . esc_url(add_query_arg('paged', $paged + 1, $base_url)) . '">&raquo;</a>';
                        }
                    
                        echo '</span></div></div>';
                    }
                    
                    ?>
                <?php else : ?>
                    <p><?php esc_html_e('No support tickets found.', 'text-domain'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <script>

        jQuery(document).ready(function ($) {
            $(".ticket-status").change(function () {
                var ticketId = $(this).data("ticket-id");
                var newStatus = $(this).val();

                $.post("<?php echo admin_url('admin-ajax.php'); ?>", {
                    action: "brags_update_ticket_status",
                    ticket_id: ticketId,
                    status: newStatus
                }, function (response) {
                    if (response.success) {
                        //alert("Ticket status updated successfully!");
                        location.reload(); // Optional: refresh to reflect changes
                    } else {
                        //alert("Failed to update status.");
                    }
                });
            });

        });
            
        </script>
        <?php
    }
}




function get_phone_to_country_mapping() {
    return [
        '+1'    => 'US', // United States
        '+7'    => 'RU', // Russia
        '+20'   => 'EG', // Egypt
        '+27'   => 'ZA', // South Africa
        '+30'   => 'GR', // Greece
        '+31'   => 'NL', // Netherlands
        '+32'   => 'BE', // Belgium
        '+33'   => 'FR', // France
        '+34'   => 'ES', // Spain
        '+36'   => 'HU', // Hungary
        '+39'   => 'IT', // Italy
        '+40'   => 'RO', // Romania
        '+41'   => 'CH', // Switzerland
        '+43'   => 'AT', // Austria
        '+44'   => 'GB', // United Kingdom
        '+45'   => 'DK', // Denmark
        '+46'   => 'SE', // Sweden
        '+47'   => 'NO', // Norway
        '+48'   => 'PL', // Poland
        '+49'   => 'DE', // Germany
        '+51'   => 'PE', // Peru
        '+52'   => 'MX', // Mexico
        '+53'   => 'CU', // Cuba
        '+54'   => 'AR', // Argentina
        '+55'   => 'BR', // Brazil
        '+56'   => 'CL', // Chile
        '+57'   => 'CO', // Colombia
        '+58'   => 'VE', // Venezuela
        '+60'   => 'MY', // Malaysia
        '+61'   => 'AU', // Australia
        '+62'   => 'ID', // Indonesia
        '+63'   => 'PH', // Philippines
        '+64'   => 'NZ', // New Zealand
        '+65'   => 'SG', // Singapore
        '+66'   => 'TH', // Thailand
        '+81'   => 'JP', // Japan
        '+82'   => 'KR', // South Korea
        '+84'   => 'VN', // Vietnam
        '+86'   => 'CN', // China
        '+90'   => 'TR', // Turkey
        '+91'   => 'IN', // India
        '+92'   => 'PK', // Pakistan
        '+93'   => 'AF', // Afghanistan
        '+94'   => 'LK', // Sri Lanka
        '+95'   => 'MM', // Myanmar
        '+98'   => 'IR', // Iran
        '+211'  => 'SS', // South Sudan
        '+212'  => 'MA', // Morocco
        '+213'  => 'DZ', // Algeria
        '+216'  => 'TN', // Tunisia
        '+218'  => 'LY', // Libya
        '+220'  => 'GM', // Gambia
        '+221'  => 'SN', // Senegal
        '+222'  => 'MR', // Mauritania
        '+223'  => 'ML', // Mali
        '+224'  => 'GN', // Guinea
        '+225'  => 'CI', // Ivory Coast
        '+226'  => 'BF', // Burkina Faso
        '+227'  => 'NE', // Niger
        '+228'  => 'TG', // Togo
        '+229'  => 'BJ', // Benin
        '+230'  => 'MU', // Mauritius
        '+231'  => 'LR', // Liberia
        '+232'  => 'SL', // Sierra Leone
        '+233'  => 'GH', // Ghana
        '+234'  => 'NG', // Nigeria
        '+235'  => 'TD', // Chad
        '+236'  => 'CF', // Central African Republic
        '+237'  => 'CM', // Cameroon
        '+238'  => 'CV', // Cape Verde
        '+239'  => 'ST', // Sao Tome and Principe
        '+240'  => 'GQ', // Equatorial Guinea
        '+241'  => 'GA', // Gabon
        '+242'  => 'CG', // Republic of the Congo
        '+243'  => 'CD', // Democratic Republic of the Congo
        '+244'  => 'AO', // Angola
        '+245'  => 'GW', // Guinea-Bissau
        '+246'  => 'IO', // British Indian Ocean Territory
        '+248'  => 'SC', // Seychelles
        '+249'  => 'SD', // Sudan
        '+250'  => 'RW', // Rwanda
        '+251'  => 'ET', // Ethiopia
        '+252'  => 'SO', // Somalia
        '+253'  => 'DJ', // Djibouti
        '+254'  => 'KE', // Kenya
        '+255'  => 'TZ', // Tanzania
        '+256'  => 'UG', // Uganda
        '+257'  => 'BI', // Burundi
        '+258'  => 'MZ', // Mozambique
        '+260'  => 'ZM', // Zambia
        '+261'  => 'MG', // Madagascar
        '+263'  => 'ZW', // Zimbabwe
        '+264'  => 'NA', // Namibia
        '+265'  => 'MW', // Malawi
        '+266'  => 'LS', // Lesotho
        '+267'  => 'BW', // Botswana
        '+268'  => 'SZ', // Eswatini
        '+269'  => 'KM', // Comoros
        '+290'  => 'SH', // Saint Helena
        '+291'  => 'ER', // Eritrea
        '+297'  => 'AW', // Aruba
        '+298'  => 'FO', // Faroe Islands
        '+299'  => 'GL', // Greenland
        '+350'  => 'GI', // Gibraltar
        '+351'  => 'PT', // Portugal
        '+352'  => 'LU', // Luxembourg
        '+353'  => 'IE', // Ireland
        '+354'  => 'IS', // Iceland
        '+355'  => 'AL', // Albania
        '+356'  => 'MT', // Malta
        '+357'  => 'CY', // Cyprus
        '+358'  => 'FI', // Finland
        '+359'  => 'BG', // Bulgaria
        '+370'  => 'LT', // Lithuania
        '+371'  => 'LV', // Latvia
        '+372'  => 'EE', // Estonia
        '+373'  => 'MD', // Moldova
        '+374'  => 'AM', // Armenia
        '+375'  => 'BY', // Belarus
        '+376'  => 'AD', // Andorra
        '+377'  => 'MC', // Monaco
        '+378'  => 'SM', // San Marino
        '+380'  => 'UA', // Ukraine
        '+381'  => 'RS', // Serbia
        '+382'  => 'ME', // Montenegro
        '+383'  => 'XK', // Kosovo
        '+385'  => 'HR', // Croatia
        '+386'  => 'SI', // Slovenia
        '+387'  => 'BA', // Bosnia and Herzegovina
        '+389'  => 'MK', // North Macedonia
        '+420'  => 'CZ', // Czech Republic
        '+421'  => 'SK', // Slovakia
        '+423'  => 'LI', // Liechtenstein
        '+852'  => 'HK', // Hong Kong
        '+853'  => 'MO', // Macao
        '+855'  => 'KH', // Cambodia
        '+856'  => 'LA', // Laos
        '+880'  => 'BD', // Bangladesh
        '+886'  => 'TW', // Taiwan
        '+960'  => 'MV', // Maldives
        '+961'  => 'LB', // Lebanon
        '+962'  => 'JO', // Jordan
        '+963'  => 'SY', // Syria
        '+964'  => 'IQ', // Iraq
        '+965'  => 'KW', // Kuwait
        '+966'  => 'SA', // Saudi Arabia
        '+967'  => 'YE', // Yemen
        '+968'  => 'OM', // Oman
        '+970'  => 'PS', // Palestine
        '+971'  => 'AE', // United Arab Emirates
        '+972'  => 'IL', // Israel
        '+973'  => 'BH', // Bahrain
        '+974'  => 'QA', // Qatar
        '+975'  => 'BT', // Bhutan
        '+976'  => 'MN', // Mongolia
        '+977'  => 'NP', // Nepal
    ];
}


add_action('wp_footer', function () {
    ?>
    <script>
    jQuery(document).ready(function ($) {
        $("#dokan-verification-form-1").on("submit", function (event) {
            event.preventDefault(); // Prevent default form submission

            let form = $(this);
            let formData = new FormData(this);

            // Show loading state
            form.find("#dokan_vendor_verification_submit_1").prop("disabled", true).val("Submitting...");

            // Make AJAX request
            $.ajax({
                url: dokan.ajaxurl, // Use Dokan's AJAX URL
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.success) {
                        //alert("Verification request submitted successfully!");
                        location.reload(); // Reload to reflect changes
                    } else {
                        //alert("Error: " + response.data);
                    }
                },
                error: function () {
                    //alert("An error occurred. Please try again.");
                },
                complete: function () {
                    form.find("#dokan_vendor_verification_submit_1").prop("disabled", false).val("Submit");
                }
            });
        });
    });
    </script>
    <?php
});



function create_verification_request($user_id, $file_urls) {
    global $wpdb;

    if (!empty($file_urls)) {
        $document_ids = [];

        foreach ($file_urls as $file_url) {
            $attachment_id = attachment_url_to_postid($file_url);
            if ($attachment_id !== 0) {
                $document_ids[] = $attachment_id;
            } else {
                error_log('Failed to retrieve attachment ID for file: ' . $file_url);
            }
        }

        if (empty($document_ids)) {
            return; // No valid documents, stop execution
        }

        $table_name = $wpdb->prefix . 'dokan_vendor_verification_requests'; // Adjust to your actual Dokan verification table

        //$wpdb->query("TRUNCATE TABLE $table_name"); //  This removes all records from the table!

        $inserted = $wpdb->insert(
            $table_name,
            [
                'vendor_id'       => $user_id,
                'method_id'       => 1,  // Adjust as per your verification method
                'status'          => 'pending',
                'checked_by'      => 0,
                'additional_info' => maybe_serialize([]),
                'documents'       => maybe_serialize($document_ids), // ✅ Fixed for multiple files
                'note'            => 'Passport verification',
                'created_at'      => current_time('mysql'),
                'updated_at'      => current_time('mysql'),
            ],
            [
                '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s'
            ]
        );

        if (!$inserted) {
            error_log('Verification request insertion failed for user ' . $user_id);
        }

    }
}
add_action('woocommerce_created_customer', 'save_passport_upload', 10, 3);
function save_passport_upload($customer_id, $new_customer_data, $password_generated) {
    if (!empty($_FILES['passport_upload_individual']['name'][0])) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $file_urls = [];

        foreach ($_FILES['passport_upload_individual']['name'] as $key => $value) {
            if ($_FILES['passport_upload_individual']['error'][$key] === UPLOAD_ERR_OK) {
                $file_array = [
                    'name'     => $_FILES['passport_upload_individual']['name'][$key],
                    'type'     => $_FILES['passport_upload_individual']['type'][$key],
                    'tmp_name' => $_FILES['passport_upload_individual']['tmp_name'][$key],
                    'error'    => $_FILES['passport_upload_individual']['error'][$key],
                    'size'     => $_FILES['passport_upload_individual']['size'][$key],
                ];

                $uploaded_file = wp_handle_upload($file_array, ['test_form' => false]);

                if (!isset($uploaded_file['error'])) {
                    $file_path = $uploaded_file['file']; // Full path
                    $file_url  = $uploaded_file['url'];  // File URL
                    $file_name = basename($file_path);

                    // Insert into Media Library
                    $attachment = [
                        'guid'           => $file_url,
                        'post_mime_type' => $uploaded_file['type'],
                        'post_title'     => preg_replace('/\.[^.]+$/', '', $file_name),
                        'post_content'   => '',
                        'post_status'    => 'inherit'
                    ];

                    $attachment_id = wp_insert_attachment($attachment, $file_path);

                    if (!is_wp_error($attachment_id)) {
                        // Generate attachment metadata
                        $attachment_data = wp_generate_attachment_metadata($attachment_id, $file_path);
                        wp_update_attachment_metadata($attachment_id, $attachment_data);

                        // Get the correct file URL from attachment ID
                        $file_urls[] = wp_get_attachment_url($attachment_id);
                    }
                } else {
                    wc_add_notice(__('File upload failed: ', 'dokan') . $uploaded_file['error'], 'error');
                }
            }
        }



        if (!empty($file_urls)) {
            // Store uploaded file URLs in user meta
            update_user_meta($customer_id, 'passport_upload', maybe_serialize($file_urls));

            // Create verification request after file upload
            create_verification_request($customer_id, $file_urls);
        }
    }
}





add_filter('woocommerce_get_endpoint_url', 'change_affiliate_menu_url', 999, 4);

function change_affiliate_menu_url($url, $endpoint, $value, $permalink) {
    if ($endpoint === 'uap') {
        $url = home_url('/braggers-portal');
    }
    return $url;
}


//add_action('woocommerce_after_single_product', 'move_dokan_store_support_button', 20);
add_action('woodmart_after_product_tabs', 'move_dokan_store_support_button', 20);

function move_dokan_store_support_button() {
    if ( function_exists('dokan_pro') ) {
        // Check if the support button should be shown on the product page
        $store_support_show = dokan_get_option('store_support_product_page', 'dokan_store_support_setting', 'above_tab');
        if ('above_tab' == $store_support_show || 'inside_tab' == $store_support_show) {

        }else{
			return;
		}

        // Get product and store information
        $product_id = get_the_ID();
        $store_id = get_post_field('post_author', $product_id);
        $store_info = dokan_get_store_info($store_id);

        // Check if the store allows showing the support button
        if (isset($store_info['show_support_btn_product']) && 'no' === $store_info['show_support_btn_product']) {
            return;
        }

        // Replicate the get_support_button logic
        $button_class = is_user_logged_in() ? 'user_logged' : 'user_logged_out';
        $default_text = dokan_get_option('support_button_label', 'dokan_store_support_setting', __('Get Support', 'dokan'));
        $button_text = !empty($store_info['support_btn_name']) ? $store_info['support_btn_name'] : $default_text;

        // Output the support button
        echo '<div class="container"><button data-store_id="' . esc_attr($store_id) . '" class="dokan-store-support-btn-product dokan-store-support-btn button alt ' . esc_attr($button_class) . '">' . esc_html($button_text) . '</button></div>';
    }
}



function add_barcode_field() {
    // Barcode input field
    woocommerce_wp_text_input( array(
        'id'            => '_barcode',
        'label'         => 'Product Barcode (EAN, GTIN, UPC)',
        'placeholder'   => 'Enter the barcode number',
        'desc_tip'      => 'true',
        'description'   => 'Enter a valid barcode number or select the option below if the product doesn’t have a barcode.',
    ));

    // Checkbox for "No Barcode"
    woocommerce_wp_checkbox( array(
        'id'          => '_no_barcode',
        'label'       => 'This product doesn’t have a barcode',
    ));
}
add_action( 'woocommerce_product_options_general_product_data', 'add_barcode_field' );



function save_barcode_field( $post_id ) {
    // Save the barcode field
    $barcode = isset( $_POST['_barcode'] ) ? sanitize_text_field( $_POST['_barcode'] ) : '';

    if ( ! empty( $barcode ) ) {
        global $wpdb;

        // Query the database to check if the barcode already exists
        $existing_product = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = '_barcode' AND meta_value = %s AND post_id != %d",
                $barcode,
                $post_id // Exclude the current product from the query
            )
        );

        if ( !$existing_product ) {
            update_post_meta( $post_id, '_barcode', $barcode );
        }
    }
   // update_post_meta( $post_id, '_barcode', $barcode );

    // Save the checkbox for "No Barcode"
 $no_barcode = isset( $_POST['_no_barcode'] ) ? 'yes' : 'no';

    update_post_meta( $post_id, '_no_barcode', $no_barcode );
}
add_action( 'woocommerce_process_product_meta', 'save_barcode_field' );


function validate_barcode_field( $product ) {
    try {
        $barcode = isset( $_POST['_barcode'] ) ? sanitize_text_field( $_POST['_barcode'] ) : '';
        $no_barcode = isset( $_POST['_no_barcode'] ) ? 'yes' : 'no';

        // Check if the barcode is empty and the "no barcode" checkbox is not checked
        if ( empty( $barcode ) && $no_barcode === 'yes' ) {
            throw new Exception( __( 'Please enter a barcode or check the "This product doesn’t have a barcode" option.', 'woocommerce' ) );
        }

        // Check for unique barcode (if barcode is entered)
        if ( ! empty( $barcode ) ) {
            global $wpdb;

            // Query the database to check if the barcode already exists
            $existing_product = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = '_barcode' AND meta_value = %s AND post_id != %d",
                    $barcode,
                    $product->get_id() // Exclude the current product from the query
                )
            );

            if ( $existing_product ) {
                throw new Exception( __( 'The barcode must be unique. This barcode is already in use by another product.', 'woocommerce' ) );
            }
        }
    } catch ( Exception $e ) {
        // Add the error message
        WC_Admin_Meta_Boxes::add_error( $e->getMessage() );
    }
}
add_action( 'woocommerce_admin_process_product_object', 'validate_barcode_field' );


// barcode filed for the dokan plugin


function add_dokan_barcode_field_old( $post ) {
    // Get the stored barcode value for the product
    $barcode = get_post_meta( $post->ID, '_barcode', true );

    // Get the stored value for the "No Barcode" checkbox
    $no_barcode = get_post_meta( $post->ID, '_no_barcode', true );



    ?>

    <div class="dokan-form-simple-barcode-field">

    <div class="dokan-form-group" id="product_barcode_field">
        <label for="product_barcode"><?php _e( 'Product Barcode (EAN, GTIN, UPC)', 'dokan-lite' ); ?></label>
        <input type="text" class="dokan-form-control" name="product_barcode" id="product_barcode" placeholder="Enter barcode" value="<?php echo esc_attr( $barcode ); ?>">
    </div>

    <div class="dokan-form-group" id="no_barcode_field">
        <label class="dokan-checkbox-inline">
            <input type="checkbox" name="no_barcode" id="no_barcode" value="yes" <?php checked( $no_barcode, 'yes' ); ?>>
            <?php _e( 'This product doesn’t have a barcode', 'dokan-lite' ); ?>
        </label>
    </div>
    </div>

    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Toggle barcode field based on product type selection
            function toggleFields() {
                var productType = $('#product_type').val();
                if (productType == 'variable') {
                    $('.dokan-form-simple-barcode-field').show(); // Hide barcode field for variable products
                } else {
                    $('.dokan-form-simple-barcode-field').show(); // Show barcode field for simple products
                }
            }

            // Trigger on page load
            toggleFields();

            // Trigger when product type changes
            $('#product_type').on('change', function() {
                toggleFields();
            });
        });
    </script>

    <?php
}
//add_action( 'dokan_product_edit_after_inventory_variants', 'add_dokan_barcode_field_old' );



function add_dokan_barcode_field( $post ) {
    $barcode = get_post_meta( $post->ID, '_barcode', true );
    $no_barcode = get_post_meta( $post->ID, '_no_barcode', true );
    ?>

    <div class="dokan-form-simple-barcode-field">
        <div class="dokan-form-group" id="product_barcode_field">
            <label for="product_barcode"><?php _e( 'Product Barcode (EAN, GTIN, UPC)', 'dokan-lite' ); ?></label>
            <input type="text" class="dokan-form-control" name="product_barcode" id="product_barcode" placeholder="Enter barcode" value="<?php echo esc_attr( $barcode ); ?>">
        </div>

        <div class="dokan-form-group" id="no_barcode_field">
            <label class="dokan-checkbox-inline">
                <input type="checkbox" name="no_barcode" id="no_barcode" value="yes" <?php checked( $no_barcode, 'yes' ); ?>>
                <?php _e( 'This product doesn’t have a barcode', 'dokan-lite' ); ?>
            </label>
        </div>
    </div>

    <script type="text/javascript">
        jQuery(document).ready(function($) {
            function toggleFields() {
                var productType = $('#product_type').val();
                $('.dokan-form-simple-barcode-field').show(); // Always show
            }

            toggleFields();
            $('#product_type').on('change', toggleFields);

            // Toggle required attribute
            function toggleRequired() {
                var barcodeInput = $('#product_barcode');
                var noBarcodeChecked = $('#no_barcode').is(':checked');

                if (noBarcodeChecked) {
                    barcodeInput.removeAttr('required');
                } else {
                    barcodeInput.attr('required', 'required');
                }
            }

            // On page load
            toggleRequired();

            // On checkbox change
            $('#no_barcode').on('change', toggleRequired);

            // Extra safety: block form submit manually too
            $('form.dokan-product-edit-form').on('submit', function(e) {
                var barcodeInput = $('#product_barcode');
                var noBarcodeChecked = $('#no_barcode').is(':checked');

                barcodeInput.removeClass('dokan-error');
                barcodeInput.next('.dokan-error-message').remove();

                if (!noBarcodeChecked && barcodeInput.val().trim() === '') {
                    e.preventDefault();
                    barcodeInput.addClass('dokan-error');
                    barcodeInput.after('<span class="dokan-error-message" style="color:red;">Please enter the product barcode or check "no barcode".</span>');
                    $('html, body').animate({ scrollTop: barcodeInput.offset().top - 100 }, 300);
                }
            });
        });
    </script>

    <?php
}
add_action( 'dokan_product_edit_after_inventory_variants', 'add_dokan_barcode_field' );



function save_dokan_barcode_field( $post_id ) {
    global $woocommerce_errors, $wpdb;

    // Save and sanitize the SKU
    $sku = isset($_POST['_sku']) ? sanitize_text_field($_POST['_sku']) : '';
    if ( empty($sku) ) {
        $woocommerce_errors[] = __( 'SKU is required for all products.', 'dokan-lite' );
        return;
    }

    if ( !preg_match('/^[a-zA-Z0-9-_]+$/', $sku) ) {
        $woocommerce_errors[] = __( 'Error: SKU can only contain letters, numbers, dashes (-), and underscores (_). No spaces or special characters allowed.', 'dokan-lite' );
        return;
    }

    // Check if SKU is unique
    $existing_sku = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = '_sku' AND meta_value = %s AND post_id != %d",
            $sku,
            $post_id
        )
    );
    if ( $existing_sku ) {
        $woocommerce_errors[] = __( 'Error: This SKU is already in use. Please enter a unique SKU.', 'dokan-lite' );
        return;
    }
    update_post_meta( $post_id, '_sku', $sku );

    // Handle Barcode and No Barcode
    $barcode = isset($_POST['product_barcode']) ? sanitize_text_field($_POST['product_barcode']) : '';
    $no_barcode = isset($_POST['no_barcode']) && $_POST['no_barcode'] === 'yes' ? 'yes' : 'no';

    // Validate barcode or checkbox
    if ( empty($barcode) && $no_barcode !== 'yes' ) {
        $woocommerce_errors[] = __( 'You must enter a barcode or check the "This product doesn’t have a barcode" option.', 'dokan-lite' );
        return;
    }

    // If barcode is given, check uniqueness
    if ( !empty($barcode) ) {
        $existing_barcode = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = '_barcode' AND meta_value = %s AND post_id != %d",
                $barcode,
                $post_id
            )
        );

        if ( $existing_barcode ) {
            $woocommerce_errors[] = __( 'The barcode must be unique. This barcode is already in use by another product.', 'dokan-lite' );
            return;
        }

        update_post_meta( $post_id, '_barcode', $barcode );
    } else {
        delete_post_meta( $post_id, '_barcode' ); // Clear barcode if not used
    }

    update_post_meta( $post_id, '_no_barcode', $no_barcode );
}
add_action( 'dokan_process_product_meta', 'save_dokan_barcode_field' );





// ----------------------- only product filed bracode add --------------------------------------


add_action( 'dokan_product_added', 'save_custom_product_fields', 10, 2 );
add_action( 'dokan_product_updated', 'save_custom_product_fields', 10, 2 );

function save_custom_product_fields( $post_id, $product ) {
    if ( isset( $_POST['variation_barcode'] ) ) {
        // Save custom field value
        update_post_meta( $post_id, '_variation_barcode', sanitize_text_field( $_POST['variation_barcode'] ) );
    }
}




// Add barcode field and "No Barcode" checkbox to variation options in Dokan
function dokan_add_variation_barcode_field( $loop, $variation_data, $variation ) {
    $variation_barcode = get_post_meta( $variation->ID, '_variation_barcode', true );
    $no_variation_barcode = get_post_meta( $variation->ID, '_no_variation_barcode', true );
    ?>
    <div class="dokan-form-group">
        <label for="variation_barcode_<?php echo esc_attr( $loop ); ?>"><?php _e( 'Variation Barcode (EAN, GTIN, UPC)', 'dokan-lite' ); ?></label>
        <input type="text" class="dokan-form-control" name="variation_barcode[<?php echo esc_attr( $loop ); ?>]" id="variation_barcode_<?php echo esc_attr( $loop ); ?>" value="<?php echo esc_attr( $variation_barcode ); ?>" placeholder="<?php _e( 'Enter barcode', 'dokan-lite' ); ?>" />
    </div>

    <div class="dokan-form-group">
        <label class="dokan-checkbox-inline">
            <input type="checkbox" name="no_variation_barcode[<?php echo esc_attr( $loop ); ?>]" value="yes" <?php checked( $no_variation_barcode, 'yes' ); ?> />
            <?php _e( 'This variation doesn’t have a barcode', 'dokan-lite' ); ?>
        </label>
    </div>
    <?php
}
add_action( 'dokan_variation_options', 'dokan_add_variation_barcode_field', 10, 3 );

// Save variation barcode and "No Barcode" checkbox values
function dokan_save_variation_custom_fields( $variation_id, $i ) {
    if ( isset( $_POST['variation_barcode'][ $i ] ) ) {
        $barcode_variation = sanitize_text_field( $_POST['variation_barcode'][ $i ] );
        update_post_meta( $variation_id, '_variation_barcode', $barcode_variation );
    }

    if ( isset( $_POST['no_variation_barcode'][ $i ] ) && $_POST['no_variation_barcode'][ $i ] === 'yes' ) {
        update_post_meta( $variation_id, '_no_variation_barcode', 'yes' );
    } else {
        update_post_meta( $variation_id, '_no_variation_barcode', 'no' );
    }
}
add_action( 'woocommerce_save_product_variation', 'dokan_save_variation_custom_fields', 10, 2 );

// Display main product barcode
function display_product_barcode() {
    global $product;

    if ( ! $product ) {
        return;
    }

    $barcode = get_post_meta( $product->get_id(), '_barcode', true );

    if ( ! empty( $barcode ) ) {
        echo '<div class="product-meta">';
        echo '<div class="product-barcode"><strong>' . __( 'Product Barcode:', 'woocommerce' ) . '</strong> ' . esc_html( $barcode ) . '</div>';
        echo '</div>';
    }

}
add_action( 'woocommerce_single_product_summary', 'display_product_barcode', 30 );



// ---------------------------------- display product bracode --------------------------------------

function dokan_display_variation_custom_fields() {
    global $product;

    if ( ! $product || ! $product->is_type( 'variable' ) ) {
        return;
    }

    $variations = $product->get_available_variations();
    $displayed_count = 0;

    echo '<div id="variation-barcode-container"></div>'; // Placeholder for dynamic updates

    foreach ( $variations as $variation ) {
        $variation_id = $variation['variation_id'];
        $barcode_variation = get_post_meta( $variation_id, '_variation_barcode', true );
        $no_barcode = get_post_meta( $variation_id, '_no_variation_barcode', true );


        if ( ! empty( $barcode_variation ) ) {
            echo '<p class="variation-barcode" data-variation-id="' . esc_attr( $variation_id ) . '">
                    <strong>' . __( 'Variation Barcode:', 'dokan-lite' ) . '</strong>
                    <span>' . esc_html( $barcode_variation ) . '</span>
                  </p>';
            $displayed_count++;
        } else if ( $no_barcode === 'yes' ) {
            // echo '<p class="variation-barcode" data-variation-id="' . esc_attr( $variation_id ) . '">
            //         <strong>' . __( 'Variation Barcode:', 'dokan-lite' ) . '</strong>
            //         ' . __( 'No Barcode', 'dokan-lite' ) . '
            //       </p>';
        }

        if ( $displayed_count >= 2 ) {
            break; // Stop after displaying two barcodes
        }
    }

}
add_action( 'woocommerce_single_product_summary', 'dokan_display_variation_custom_fields', 25 );

// jQuery to update barcode dynamically when a variation is selected
function dokan_variation_barcode_script() {
    if ( is_product() ) {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $(document).on('found_variation', 'form.variations_form', function(event, variation) {
                    var barcode = variation.variation_id ? $('.variation-barcode[data-variation-id="' + variation.variation_id + '"]').html() : '';

                    if (barcode) {
                        $('#variation-barcode-container').html(barcode);
                    } else {
                        $('#variation-barcode-container').html('<p><strong><?php _e( "Variation Barcode:", "dokan-lite" ); ?></strong> <?php _e( "No Barcode", "dokan-lite" ); ?></p>');
                    }
                });
            });
        </script>
        <?php
    }
}
add_action( 'wp_footer', 'dokan_variation_barcode_script' );





function custom_modify_account_menu_items( $items ) {
    $user_id = get_current_user_id();

    if ( $user_id ) {
        $user = get_userdata( $user_id );
        $user_roles = $user->roles; // Get user roles

        // Ensure vendors always see "Profile Details"
        //if ( in_array( 'vendor', $user_roles ) ) {
            $items['edit-profile'] = __( 'Profile Details', 'user-registration' );
        //}
    }

    return $items;
}
add_filter( 'user_registration_account_menu_items', 'custom_modify_account_menu_items', 10, 1 );

function custom_default_ur_form_id( $value, $object_id, $meta_key, $single ) {
    // Target only 'ur_form_id'
    if ( 'ur_form_id' !== $meta_key || !empty( $value ) ) {
        return $value; // Return the original value if it exists
    }

    // Get user data
    $user = get_userdata( $object_id );
    if ( !$user ) {
        return $value; // Return original (which is empty) if user not found
    }

    $user_roles = $user->roles; // Get user roles

    if ( in_array( 'seller', $user_roles ) ) {
        return $default_form_ids = array(17078);
    }

    // Define default form IDs for specific roles

    return $value; // Return original value (empty) if no matching role
}
add_filter( 'get_user_metadata', 'custom_default_ur_form_id', 10, 4 );


function add_custom_dokan_menu( $menus ) {


    // Add "Brags Brand Network" menu
    $menus['brags-brand-network-account'] = array(
        'title' => __('Brags Brand Network', 'dokan-lite'),
        'icon'  => '<i class="fas fa-network-wired"></i>',
        'url'   => dokan_get_navigation_url('brags-brand-network-account'),
        'target' => '_self',
        'pos'   => 60
    );

    return $menus;
}
add_filter('dokan_get_dashboard_nav', 'add_custom_dokan_menu');



function add_brand_owner_registration_link($items) {
    // Add a new menu item (change slug if needed)
    $items['brags-brand-network-account'] = __('Brags Brand Network', 'text-domain');
    return $items;
}
add_filter('woocommerce_account_menu_items', 'add_brand_owner_registration_link');

function brand_owner_registration_endpoint_content() {
    // Redirect if user is already logged in
    if (is_user_logged_in()) {
        wp_redirect(site_url('/my-account/'));
        exit;
    }

    echo '<p>Redirecting to Brand Owner Registration...</p>';
    wp_redirect(site_url('/brags-brand-network-account/'));
    exit;
}
add_action('woocommerce_account_brand-owner-registration_endpoint', 'brand_owner_registration_endpoint_content');





function get_lowest_price_product($product_id) {
    global $wpdb;

    // Check if the product has multiple vendors
    $has_multivendor = get_post_meta($product_id, '_has_multi_vendor', true);

    if (!$has_multivendor) {
        return wc_get_product($product_id); // Return the current product if no multi-vendor mapping exists
    }

    // Query to get all mapped products from different sellers
    $sql = $wpdb->prepare(
        "SELECT `product_id` FROM `{$wpdb->prefix}dokan_product_map` WHERE `map_id` = %d AND `product_id` != %d AND `is_trash` = 0",
        $has_multivendor,
        $product_id
    );

    $lists = $wpdb->get_results($sql);

    // Default to the current product
    $lowest_price_product_id = $product_id;
    $lowest_price = floatval(get_post_meta($product_id, '_price', true));
    $best_review_score = get_seller_review_score($product_id);
    $fastest_processing_time = get_product_processing_time($product_id); // Get processing time

    // Loop through mapped products to find the best one
    foreach ($lists as $list) {
        $seller_price = floatval(get_post_meta($list->product_id, '_price', true));
        $seller_review_score = get_seller_review_score($list->product_id);
        $seller_processing_time = get_product_processing_time($list->product_id); // Get processing time

        // Debugging output
        // echo "Product ID: {$list->product_id}, Price: {$seller_price}, Review: {$seller_review_score}, Processing Time: {$seller_processing_time} <br>";

        if ($seller_price < $lowest_price) {
            // Found a lower price, update the lowest price product
            $lowest_price = $seller_price;
            $lowest_price_product_id = $list->product_id;
            $best_review_score = $seller_review_score;
            $fastest_processing_time = $seller_processing_time;
        } elseif ($seller_price == $lowest_price) {

            if ($seller_review_score > $best_review_score) {
                // If the price is the same, prefer the seller with a higher review score
                $lowest_price_product_id = $list->product_id;
                $best_review_score = $seller_review_score;
                $fastest_processing_time = $seller_processing_time;
            } elseif ($seller_review_score == $best_review_score) {

                // If the review score is also the same, prefer the product with the lowest processing time
                if ($seller_processing_time < $fastest_processing_time) {
                    $lowest_price_product_id = $list->product_id;
                    $fastest_processing_time = $seller_processing_time;
                }
            }
        }

        //echo  $fastest_processing_time;
    }

    // Return the product with the lowest price, best review score, and fastest processing time
    return wc_get_product($lowest_price_product_id);
}

/**
 * Get seller review score for a product.
 */
function get_seller_review_score($product_id) {
    $vendor = dokan_get_vendor_by_product($product_id);
    if (!$vendor) {
        return 0;
    }
    $store_rating = $vendor->get_rating();
    $ratings = $store_rating['count'] ?? 0;
    return $ratings ? floatval($ratings) : 0; // Default to 0 if no ratings are available
}

/**
 * Get the product's processing time for shipping.
 */
function get_product_processing_time($product_id) {
    $processing_time = get_post_meta($product_id, '_dps_processing_time', true);
    return ($processing_time !== false && $processing_time !== '') ? intval($processing_time) : PHP_INT_MAX; // Default to a large value if not set
}




function remove_show_vendor_comparison() {
    global $wp_filter;

    foreach ($wp_filter['woocommerce_after_single_product_summary']->callbacks[1] as $key => $callback) {
        if (is_array($callback['function']) && $callback['function'][1] === 'show_vendor_comparison') {
            remove_action('woocommerce_after_single_product_summary', $callback['function'], 1);
        }
    }
}
add_action('wp', 'remove_show_vendor_comparison', 20);


$display_position = dokan_get_option( 'available_vendor_list_position', 'dokan_spmv', 'below_tabs' );

if ( 'below_tabs' == $display_position ) {

    add_action( 'woocommerce_after_single_product_summary', 'show_vendor_comparison', 1 );

}else if ( 'after_tabs' == $display_position  ) {
    add_action( 'woocommerce_after_single_product_summary', 'show_vendor_comparison', 12 );
}
function show_vendor_comparison() {
    global $product;

        if ( ! $product ) {
            return;
        }

        $lists = get_other_reseller_vendors( $product->get_id() );

        if ( $lists ) {
            ?>
            <div class="dokan-other-vendor-camparison">

                <h3>
                    <?php echo dokan_get_option( 'available_vendor_list_title', 'dokan_spmv', __( 'Other Available Vendor', 'dokan' ) ); ?>
                </h3>

                <div class="table dokan-table dokan-other-vendor-camparison-table">

                    <?php foreach ( $lists as $key => $list ): ?>
                        <?php
                            $product_obj    = wc_get_product( $list->product_id );
                            $post_author_id = get_post_field( 'post_author', $product_obj->get_id() );
                            $seller_info    = dokan_get_store_info( $post_author_id );
                            $rating_count   = $product_obj->get_rating_count();
                            $review_count   = $product_obj->get_review_count();
                            $average        = $product_obj->get_average_rating();

                            if ( ! $product_obj->is_visible() ) {
                                continue;
                            }
                        ?>

                    <div class="table-row <?php echo ( $list->product_id == $product->get_id() ) ? 'active' : ''; ?>">
                        <div class="table-cell vendor">
                            <?php echo get_avatar( $post_author_id, 52 ); ?>
                            <a href="<?php echo dokan_get_store_url( $post_author_id ); ?>"><?php echo $seller_info['store_name'] ?></a>
                        </div>
                        <div class="table-cell price">
                            <span class="cell-title"><?php _e( 'Price', 'dokan' ); ?></span>
                            <?php echo $product_obj->get_price_html(); ?>
                        </div>
                        <div class="table-cell rating">
                            <span class="cell-title"><?php _e( 'Seller Rating', 'dokan' ); ?></span>
                            <div class="woocommerce-product-rating">
                                <?php echo wc_get_rating_html( $average, $rating_count ); ?>
                                <?php if ( comments_open() ) : ?><a href="#reviews" class="woocommerce-review-link" rel="nofollow">(<?php printf( _n( '%s customer review', '%s customer reviews', $review_count, 'dokan' ), '<span class="count">' . esc_html( $review_count ) . '</span>' ); ?>)</a><?php endif ?>
                            </div>
                        </div>
                        <div class="table-cell delivery" style="    width: 20%;">
                            <?php
                                $processing_time         = dokan_get_shipping_processing_times();
                                $_processing_time        = get_post_meta( $list->product_id , '_dps_processing_time', true );
                                $dps_pt                  = get_user_meta( $list->product_id , '_dps_pt', true );
                                $porduct_shipping_pt     = ( $_processing_time ) ? $_processing_time : $dps_pt;

                            ?>
                            <span class="cell-title"><?php _e( 'Delivery Time', 'dokan' ); ?></span>
                            <div class="woocommerce-product-rating">
                                <?php
                                if($processing_time && $porduct_shipping_pt !=""){

                                    foreach($processing_time  as $key=> $p_time){
                                        if($porduct_shipping_pt==$key){
                                            echo $p_time;
                                        }
                                    }
                                }else{
                                    echo "-";
                                }
                                ?>
                            </div>
                        </div>
                        <div class="table-cell action-area">
                            <a href="<?php echo dokan_get_store_url( $post_author_id ); ?>" class="dokan-btn tips link">
                                <!-- <i class="fas fa-external-link-alt"></i> --> <?php _e( 'View Seller Store', 'dokan' ); ?>
                            </a>
                            <!-- <a href="<?php // echo $product_obj->get_permalink(); ?>" class="dokan-btn tips view" title="<?php // _e( 'View Product', 'dokan' ); ?>">
                                <i class="far fa-eye" aria-hidden="true"></i>
                            </a> -->
                            <?php if ( 'simple' == $product_obj->get_type() ): ?>
                                <?php
                                echo sprintf( '<a href="%s" data-quantity="%s" data-product_id="%s" data-product_sku="%s" class="%s" title="%s">%s</a>',
                                    esc_url( $product_obj->add_to_cart_url() ),
                                    1,
                                    esc_attr( $product_obj->get_id() ),
                                    esc_attr( $product_obj->get_sku() ),
                                    'dokan-btn tips cart',
                                    __( 'Add To cart', 'dokan' ),
                                    'Add To Basket'
                                );
                                ?>
                            <?php elseif ( 'variable' == $product_obj->get_type() ) : ?>
                                <a href="<?php echo $product_obj->get_permalink(); ?>" class="dokan-btn tips bars"><?php _e( 'Add To Basket', 'dokan' ); ?></a>
                            <?php endif ?>
                        </div>
                    </div>

                    <?php endforeach ?>

                </div>
            </div>

            <style>
                .dokan-other-vendor-camparison {
                    clear: both;
                    margin: 10px 0px 20px;
                }

                .dokan-other-vendor-camparison h3 {
                    margin-bottom: 15px;
                }

                .dokan-other-vendor-camparison-table {
                    margin:50px 0;
                }
                .table-row {
                    display: table;
                    background: white;
                    border-radius: 5px;
                    border: 1px solid #edf2f7;
                    padding: 20px;
                    width: 100%;
                    margin-bottom: 15px;
                    box-shadow: 1.21px 4.851px 27px 0px rgba(202, 210, 240, 0.2);
                }

                .table-row.active {
                    border: 1px solid #e3e3e3;
                }

                .table-cell {
                    display: table-cell;
                    vertical-align: middle;
                }
                .table-cell.vendor {
                    width: 45%;
                }
                .table-cell.price {
                    width: 15%;
                }
                .table-cell.rating {
                    width: 20%;
                }
                .table-cell.action-area {
                    width: 20%;
                    text-align: center;
                }

                .table-cell.vendor img{
                    display: inline-block;
                    vertical-align: middle;
                    border-radius: 3px;
                }
                .table-cell.vendor a{
                    display: inline-block;
                    vertical-align: middle;
                    text-decoration: none;
                    color: black;
                    font-size: 20px;
                    line-height: 1.2em;
                    margin-left: 15px;
                }
                .table-cell .woocommerce-product-rating{
                    margin-bottom:0 !important;
                }
                span.cell-title {
                    display: block;
                    font-size: 16px;
                    margin-bottom: 10px;
                    color: #82959b;
                }
                .table-cell .woocommerce-Price-amount{
                    color: #e74c3c;
                    font-size: 20px;
                    line-height: 1.2em;
                }

                .table-cell .dokan-btn {
                    padding: 5px 12px;
                    font-size: 14px;
                }
                .table-cell .dokan-btn.link {
                    color: #8e44ad;
                }
                .table-cell .dokan-btn.view {
                    color: #008fd5;
                }
                .table-cell .dokan-btn.cart {
                    color: #d35400;
                }
                .table-cell .dokan-btn:hover {
                    background-color: #f5f7fa;
                    color: inherit;
                }

                @media screen and (max-width: 767px){
                    .table-row {
                        display: block;
                        padding:0;
                        width: 100%;
                    }
                    .table-cell {
                        display: block;
                        width: 100% !important;
                        text-align: center;
                    }
                    .table-cell.vendor img{
                        display: block;
                        margin: 30px auto;
                    }
                    .table-cell.vendor a{
                        display: block;
                        margin: 0 20px;
                    }
                    .table-cell.price{
                        padding: 20px 0;
                    }
                    span.cell-title{
                        display: none;
                    }

                    .action-area{
                        border-top: 1px solid #e5edf0;
                        margin-top: 20px;
                        padding: 10px 0;
                    }
                }
            </style>

            <script>
                ;(function($) {
                    $(document).ready( function() {
                        $('.tips').tooltip();
                    })
                })(jQuery);
            </script>
            <?php
        }
}


function get_other_reseller_vendors( $product_id ) {
    global $wpdb;

    if ( ! $product_id ) {
        return false;
    }

    $has_multivendor = get_post_meta( $product_id, '_has_multi_vendor', true );

    if ( empty( $has_multivendor ) ) {
        return false;
    }

    $sql     = "SELECT `product_id` FROM `{$wpdb->prefix}dokan_product_map` WHERE `map_id`= '$has_multivendor' AND `product_id` != $product_id AND `is_trash` = 0";
    $results = $wpdb->get_results( $sql );

    if ( $results ) {
        return $results;
    }

    return false;
}




function enqueue_custom_scripts() {
    // Enqueue Country Select CSS
    wp_enqueue_style('country-select-css', 'https://cdnjs.cloudflare.com/ajax/libs/country-select-js/2.1.1/css/countrySelect.min.css');

    // Enqueue Country Select JS
    wp_enqueue_script('country-select-js', 'https://cdnjs.cloudflare.com/ajax/libs/country-select-js/2.1.1/js/countrySelect.min.js', array('jquery'), null, true);

    // Enqueue libphonenumber-js
    wp_enqueue_script('libphonenumber-js', 'https://cdnjs.cloudflare.com/ajax/libs/libphonenumber-js/1.9.43/libphonenumber-js.min.js', array(), null, true);


}
add_action('wp_enqueue_scripts', 'enqueue_custom_scripts');





// ------------------------------ customer Score display for admin side ------------------------------


// Register custom cron schedules
function custom_cron_schedules($schedules) {
    $schedules['every_1_minute'] = array(
        'interval' => 60, // 1 minute (for testing)
        'display'  => __('Every 1 Minute')
    );
    $schedules['every_1_days'] = array(
        'interval' => 1 * DAY_IN_SECONDS, // 1 days (live)
        'display'  => __('Every 1 Days')
    );
    return $schedules;
}
add_filter('cron_schedules', 'custom_cron_schedules');

// Set testing mode (change to false for live mode)
define('TEST_MODE', false); // Change to false for live mode

// Schedule the cron job based on mode
function schedule_cron_jobs() {
    $interval = TEST_MODE ? 'every_1_minute' : 'every_1_days';

    if (!wp_next_scheduled('check_user_scores')) {
        wp_schedule_event(time(), $interval, 'check_user_scores');
    }
}
add_action('init', 'schedule_cron_jobs');

// Function to check and schedule the 1-day update (or 1-minute in test mode)
function check_and_schedule_user_update() {
    $users = get_users(array('role__in' => array('customer', 'seller')));

    foreach ($users as $user) {
        $last_update = get_user_meta($user->ID, 'last_score_update', true);
        $update_interval = TEST_MODE ? 60 : (90 * DAY_IN_SECONDS); // 1 min for test, 90 days for live

        if (empty($last_update) || (time() - $last_update) >= $update_interval) {
            if (in_array('customer', (array) $user->roles)) {
                wp_schedule_single_event(time(), 'update_customer_score', array($user->ID));
            } elseif (in_array('seller', (array) $user->roles)) {
                wp_schedule_single_event(time(), 'update_seller_score', array($user->ID));
            }

            // Update last update timestamp
            update_user_meta($user->ID, 'last_score_update', time());
        }
    }
}
add_action('check_user_scores', 'check_and_schedule_user_update');

// Update customer score function
function update_customer_score($user_id) {
    update_user_meta($user_id, 'customer_score', 100);
}
add_action('update_customer_score', 'update_customer_score');

// Update seller score function
function update_seller_score($user_id) {
    update_user_meta($user_id, 'seller_score', 100);
}
add_action('update_seller_score', 'update_seller_score');






function add_customer_score_field($user) {
    if (!in_array('customer', (array) $user->roles)) {
        return;
    }

    $customer_score = get_user_meta($user->ID, 'customer_score', true);
    if (!$customer_score) {
        $customer_score = 100;
        update_user_meta($user->ID, 'customer_score', $customer_score);
    }
    ?>
    <h3>Customer Score</h3>
    <table class="form-table">
        <tr>
            <th><label for="customer_score">Score</label></th>
            <td>
                <input type="number" name="customer_score" id="customer_score" value="<?php echo esc_attr($customer_score); ?>" min="0" max="9999">
                <p class="description">Set the customer's score manually.</p>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'add_customer_score_field');
add_action('edit_user_profile', 'add_customer_score_field');

function save_customer_score_field($user_id) {
    $user = get_userdata($user_id);
    if (!in_array('customer', (array) $user->roles)) {
        return false;
    }

    if (isset($_POST['customer_score'])) {
        update_user_meta($user_id, 'customer_score', sanitize_text_field($_POST['customer_score']));
    }
}
add_action('personal_options_update', 'save_customer_score_field');
add_action('edit_user_profile_update', 'save_customer_score_field');

function get_customer_score() {
    if (is_user_logged_in()) {
        $customer_id = get_current_user_id();
        $customer_score = get_user_meta($customer_id, 'customer_score', true);

        if (!$customer_score) {
            $customer_score = 100;
            update_user_meta($customer_id, 'customer_score', $customer_score);
        }

        return $customer_score;
    }
    return false;
}



function display_customer_score_in_dokan() {
    $customer_score = get_customer_score();

    if ($customer_score !== false) {
        echo '<h3>Your Current Customer Score: <strong>' . esc_html($customer_score) . '%</strong></h3>';
    } else {
        echo '<p>You must be logged in to view your customer score.</p>';
    }
}


add_shortcode('display_customer_score', 'display_customer_score_in_dokan');


function customer_score_dokan_account_menu_items($items) {
    if (current_user_can('customer')) {
        $items['customer-score'] = __('Customer Score', 'your-textdomain');
    }
    return $items;
}
add_filter('woocommerce_account_menu_items', 'customer_score_dokan_account_menu_items');



// ------------------------------ seller Score display for admin side ------------------------------

function add_seller_score_field($user) {
    if (!in_array('seller', (array) $user->roles)) {
        return;
    }

    $seller_score = get_user_meta($user->ID, 'seller_score', true);
    if (!$seller_score) {
        $seller_score = 100;
        update_user_meta($user->ID, 'seller_score', $seller_score);
    }
    ?>
    <h3>Seller Score</h3>
    <table class="form-table">
        <tr>
            <th><label for="seller_score">Score</label></th>
            <td>
                <input type="number" name="seller_score" id="seller_score" value="<?php echo esc_attr($seller_score); ?>" min="0" max="99">
                <p class="description">Set the seller's score manually.</p>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'add_seller_score_field');
add_action('edit_user_profile', 'add_seller_score_field');

// Save seller score when updated in the user profile
function save_seller_score_field($user_id) {
    $user = get_userdata($user_id);
    if (!in_array('seller', (array) $user->roles)) {
        return false;
    }

    if (isset($_POST['seller_score'])) {
        update_user_meta($user_id, 'seller_score', sanitize_text_field($_POST['seller_score']));
    }
}
add_action('personal_options_update', 'save_seller_score_field');
add_action('edit_user_profile_update', 'save_seller_score_field');

function revert_seller_score($user_id) {
    $user = get_userdata($user_id);
    if (in_array('seller', (array) $user->roles)) {
        update_user_meta($user_id, 'seller_score', 100);
    }
}

function get_seller_score() {
    if (is_user_logged_in()) {
        $customer_id = get_current_user_id();
        $seller_score = get_user_meta($customer_id, 'seller_score', true);

        if (!$seller_score) {
            $seller_score = 100;
            update_user_meta($customer_id, 'seller_score', $seller_score);
        }

        return $seller_score;
    }
    return false;
}

function display_seller_score_in_dokan() {
    $seller_score = get_seller_score();

    if ($seller_score !== false) {
        echo '<h3>Your Current Seller Score: <strong>' . esc_html($seller_score) . '%</strong></h3>';
    } else {
        echo '<p>You must be logged in to view your seller score.</p>';
    }
}

add_shortcode('display_seller_score', 'display_seller_score_in_dokan');


function seller_score_dokan_account_menu_items( $items ) {
    if ( current_user_can( 'seller' ) ) {
        // Add 'Seller Score' menu item with a link to a custom page
        $items['seller-score'] = array(
            'title' => __( 'Seller Score', 'your-textdomain' ),
            'url'   => site_url( '/seller-score/' ), // Replace with the correct URL
            'icon'  => '<i class="fas fa-star"></i>', // Optional icon
            'pos'   => 60
        );
    }
    return $items;
}
add_filter( 'dokan_get_dashboard_nav', 'seller_score_dokan_account_menu_items' );




// ------------------------------ Customer billing postcode display  ------------------------------

function brags_shipping_postcode() {
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        $postcode = get_user_meta($user->ID, 'billing_postcode', true);
        $roles = (array) $user->roles;

        if (in_array('customer', $roles) && !empty($postcode)) {
            return '<h6 class="shipping-notice"> Shipping to ' . esc_html($postcode) . '</h6>';
        }

        // if (! empty ( $postcode )) {
        //     return '<h6 class="shipping-notice"> Shipping to ' . esc_html($postcode) . '</h6>';
        // }
    }
    return '';
}
add_shortcode('brags_shipping', 'brags_shipping_postcode');

function home_shortcode_add() {
    if (is_front_page()) {
        echo '<div id="brags-shipping">' . do_shortcode('[brags_shipping]') . '</div>';
    }
}
add_action('wp_body_open', 'home_shortcode_add');

function custom_default_script() {
    if (is_front_page()) {
        ?>
        <script>
            jQuery(document).ready(function($) {
                if ($("#brags-shipping").length && $(".main-page-wrapper .container").length) {
                    $(".main-page-wrapper .container").prepend($("#brags-shipping"));
                }
            });
        </script>
        <?php
    }
}
add_action('wp_footer', 'custom_default_script');


//  Business Customers and VAT - upon sign up for Customer Account, include additional field that states “Are you buying as an Individual or Business?

function custom_woocommerce_register_customer_fields() {
    ?>
    <div class="customer-form">
        <p class="form-row form-row-wide" id="customer_account_type_wrapper" style="display:none;">
            <label for="customer_account_type">
                <span style="color: red; font-weight: bold;">
                    <?php _e('Are you an Individual or a Business? Please select an Account Type:', 'woocommerce'); ?>
                </span>
            </label>
            <select name="customer_account_type" id="customer_account_type">
                <option value="individual"><?php _e('Individual (representing yourself)', 'woocommerce'); ?></option>
                <option value="business"><?php _e('Business (representing a Business)', 'woocommerce'); ?></option>
            </select>
        </p>

        <div id="customer_business_fields" style="display:none;">
            <h3><?php _e('Business Information', 'woocommerce'); ?></h3>

            <div class="field-row">
                <p class="form-row form-row-wide half-width">
                    <label for="customer_business_name" class="vendor-label"><?php _e('Business Name', 'woocommerce'); ?>

                    </label>
                    <input type="text" class="input-text" name="customer_business_name" id="customer_business_name"
                        placeholder="Business Name" />
                </p>
                <p class="form-row form-row-wide half-width">
                    <label for="customer_business_address" class="vendor-label"><?php _e('Business Name', 'woocommerce'); ?>

                    </label>
                    <input type="text" class="input-text" name="customer_business_address" id="customer_business_adress"
                        placeholder="Business address" />
                </p>

            </div>
            <div class="field-row">
                <p class="form-row form-row-wide half-width">
                    <label for="customer_business_type" class="vendor-label"><?php _e('Business Type', 'woocommerce'); ?>

                    </label>
                    <select name="customer_business_type" id="customer_business_type">
                        <option value="">Select Business Type</option>
                        <option value="private_company">Private Company</option>
                        <option value="public_company">Public Company</option>
                        <option value="partnership">Partnership</option>
                        <option value="self_employed">Self-Employed</option>
                        <option value="other">Other</option>
                    </select>
                </p>
                <p class="form-row form-row-wide half-width">
                    <label for="customer_tax_id" class="vendor-label"><?php _e('Tax ID/VAT Information', 'woocommerce'); ?>

                    </label>
                    <input type="text" class="input-text" name="customer_tax_id" id="customer_tax_id" placeholder="Tax ID/VAT Information" />
                </p>

            </div>



        </div>



    </div>
    <script>
        jQuery(document).ready(function($) {

            $('input[name="role"]').first().prop('checked', true);
            var selectedRole = $('input[name="role"]:checked').val();
            var account_type = $('input[name="customer_account_type"]:selected').val();

            if (selectedRole === 'customer') {
                $('#customer_account_type_wrapper').show();
            } else {
                $('#customer_account_type_wrapper').hide();
            }

            $('input[name="role"]').change(function() {
                if ($('input[name="role"]:checked').val() === 'customer') {
                    $('#customer_account_type_wrapper').show();
                } else {
                    $('#customer_account_type_wrapper').hide();
                }
            });

            if (account_type == 'business') {
                $('#customer_business_fields').show();
            } else {
                $('#customer_business_fields').hide();
            }

            $('#customer_account_type').change(function() {
                if ($(this).val() == 'business') {
                    $('#customer_business_fields').show();
                } else {
                    $('#customer_business_fields').hide();
                }
            });



        });
        </script>
    <?php
}
add_action('woocommerce_register_form', 'custom_woocommerce_register_customer_fields');

function custom_woocommerce_registration_errors($errors, $sanitized_user_login, $user_email)
{
    if (isset($_POST['customer_account_type']) && $_POST['customer_account_type'] == 'business' && empty($_POST['customer_tax_id'])) {
        $errors->add('customer_tax_id_error', __('Please enter your Tax ID/VAT Information', 'woocommerce'));
    }
    return $errors;
}
add_filter('woocommerce_registration_errors', 'custom_woocommerce_registration_errors', 10, 3);

function save_custom_registration_fields($customer_id) {
    if (isset($_POST['customer_account_type'])) {
        update_user_meta($customer_id, 'dokan_custom_account_type', sanitize_text_field($_POST['customer_account_type']));
    }
    if (isset($_POST['customer_business_name'])) {
        update_user_meta($customer_id, 'dokan_custom_business_name', sanitize_text_field($_POST['customer_business_name']));
    }
    if (isset($_POST['customer_business_address'])) {
        update_user_meta($customer_id, 'dokan_custom_business_address', sanitize_text_field($_POST['customer_business_address']));
    }
    if (isset($_POST['customer_business_type'])) {
        update_user_meta($customer_id, 'dokan_custom_business_type', sanitize_text_field($_POST['customer_business_type']));
    }
    if (isset($_POST['customer_tax_id'])) {
        update_user_meta($customer_id, 'dokan_custom_tax_id', sanitize_text_field($_POST['customer_tax_id']));
    }
}
add_action('woocommerce_created_customer', 'save_custom_registration_fields');

function show_custom_registration_fields($user) {
    ?>
    <h3>Dokan Business Information</h3>
    <table class="form-table">
        <tr>
            <th>
                <label for="dokan_custom_account_type">
                    <span style="color: red; font-weight: bold;">
                        <?php _e('Are you an Individual or a Business? Please select an Account Type:', 'woocommerce'); ?>
                    </span>
                </label>
            </th>
            <td>
                <select name="dokan_custom_account_type" id="dokan_custom_account_type">
                    <option value="individual" <?php selected(get_user_meta($user->ID, 'dokan_custom_account_type', true), 'individual'); ?>>Individual (representing yourself)</option>
                    <option value="business" <?php selected(get_user_meta($user->ID, 'dokan_custom_account_type', true), 'business'); ?>>Business (representing a Business)</option>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="dokan_custom_business_name">Business Name</label></th>
            <td><input type="text" name="dokan_custom_business_name" id="dokan_custom_business_name" value="<?php echo esc_attr(get_user_meta($user->ID, 'dokan_custom_business_name', true)); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th><label for="dokan_custom_business_address">Business Address</label></th>
            <td><input type="text" name="dokan_custom_business_address" id="dokan_custom_business_address" value="<?php echo esc_attr(get_user_meta($user->ID, 'dokan_custom_business_address', true)); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th><label for="dokan_custom_business_type">Business Type</label></th>
            <td>
                <select name="dokan_custom_business_type" id="dokan_custom_business_type">
                    <option value="">Select Business Type</option>
                    <option value="private_company" <?php selected(get_user_meta($user->ID, 'dokan_custom_business_type', true), 'private_company'); ?>>Private Company</option>
                    <option value="public_company" <?php selected(get_user_meta($user->ID, 'dokan_custom_business_type', true), 'public_company'); ?>>Public Company</option>
                    <option value="partnership" <?php selected(get_user_meta($user->ID, 'dokan_custom_business_type', true), 'partnership'); ?>>Partnership</option>
                    <option value="self_employed" <?php selected(get_user_meta($user->ID, 'dokan_custom_business_type', true), 'self_employed'); ?>>Self-Employed</option>
                    <option value="other" <?php selected(get_user_meta($user->ID, 'dokan_custom_business_type', true), 'other'); ?>>Other</option>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="dokan_custom_tax_id">Tax ID/VAT Information</label></th>
            <td><input type="text" name="dokan_custom_tax_id" id="dokan_custom_tax_id" value="<?php echo esc_attr(get_user_meta($user->ID, 'dokan_custom_tax_id', true)); ?>" class="regular-text"></td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'show_custom_registration_fields');
add_action('edit_user_profile', 'show_custom_registration_fields');

function save_custom_registration_fields_admin($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    if (isset($_POST['dokan_custom_account_type'])) {
        update_user_meta($user_id, 'dokan_custom_account_type', sanitize_text_field($_POST['dokan_custom_account_type']));
    }
    if (isset($_POST['dokan_custom_business_name'])) {
        update_user_meta($user_id, 'dokan_custom_business_name', sanitize_text_field($_POST['dokan_custom_business_name']));
    }
    if (isset($_POST['dokan_custom_business_address'])) {
        update_user_meta($user_id, 'dokan_custom_business_address', sanitize_text_field($_POST['dokan_custom_business_address']));
    }
    if (isset($_POST['dokan_custom_business_type'])) {
        update_user_meta($user_id, 'dokan_custom_business_type', sanitize_text_field($_POST['dokan_custom_business_type']));
    }
    if (isset($_POST['dokan_custom_tax_id'])) {
        update_user_meta($user_id, 'dokan_custom_tax_id', sanitize_text_field($_POST['dokan_custom_tax_id']));
    }
}
add_action('personal_options_update', 'save_custom_registration_fields_admin');
add_action('edit_user_profile_update', 'save_custom_registration_fields_admin');


add_action( 'wp_footer', 'add_custom_uap_nonce_script', 100 );
function add_custom_uap_nonce_script_OLD() {
    // Only load this if uapData.nonce is available (e.g., passed via wp_localize_script)
    if ( wp_script_is( 'jquery', 'enqueued' ) ) : ?>
        <script type="text/javascript">
            jQuery(document).ajaxSend(function(event, jqxhr, settings) {

                if (settings.url.includes('admin-ajax.php') && settings.data && settings.data.includes('action=uap_check_reg_field_ajax')) {
                     jqxhr.setRequestHeader('X-CSRF-UAP-TOKEN', uapData.nonce);
                }
            });
        </script>
    <?php endif;
}

function add_custom_uap_nonce_script() {
    // Only proceed if jQuery is enqueued and uapData exists with a nonce
    if (!wp_script_is('jquery', 'enqueued') || !wp_script_is('uap_scripts', 'enqueued')) {
        return;
    }
    
    // Get the nonce safely
    $nonce = wp_create_nonce('uap_ajax_nonce');
    ?>
    <script type="text/javascript">
        jQuery(document).ajaxSend(function(event, jqxhr, settings) {
            try {
                // First check if we have all required components
                if (typeof uapData === 'undefined' || typeof uapData.nonce === 'undefined') {
                    return;
                }
                
                // Verify URL is a string and contains admin-ajax.php
                if (typeof settings.url !== 'string' || !settings.url.includes('admin-ajax.php')) {
                    return;
                }
                
                // Handle different data types safely
                var dataString = '';
                if (typeof settings.data === 'string') {
                    dataString = settings.data;
                } else if (settings.data instanceof FormData) {
                    // For FormData, check if it contains our action
                    if (settings.data.has('action') && settings.data.get('action') === 'uap_check_reg_field_ajax') {
                        jqxhr.setRequestHeader('X-CSRF-UAP-TOKEN', uapData.nonce);
                    }
                    return;
                } else if (typeof settings.data === 'object' && settings.data !== null) {
                    // Convert object to query string
                    dataString = jQuery.param(settings.data);
                }
                
                // Finally check for our action
                if (dataString.includes('action=uap_check_reg_field_ajax')) {
                    jqxhr.setRequestHeader('X-CSRF-UAP-TOKEN', uapData.nonce);
                }
            } catch (e) {
                console.error('UAP nonce error:', e);
            }
        });
    </script>
    <?php
}





add_action('admin_init', function () {
    if (isset($_GET['action']) && in_array($_GET['action'], ['approve', 'deny']) && isset($_GET['user'])) {
        $user_id = intval($_GET['user']);

        if (!current_user_can('edit_users')) {
            wp_die('Not allowed.');
        }

        // Nonce check
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'new-user-approve')) {
            wp_die('The link you followed has expired.');
        }

        $status_message = '';

        if ($_GET['action'] === 'approve') {
            update_user_meta($user_id, 'new_user_approve_status', 'approve');
            do_action('new_user_approve_approve_user', $user_id);
            $status_message = 'User approved successfully.';

        } elseif ($_GET['action'] === 'deny') {
            update_user_meta($user_id, 'new_user_approve_status', 'deny');
            do_action('new_user_approve_deny_user', $user_id);
            $status_message = 'User denied successfully.';
        }

        wp_redirect(admin_url('users.php?message=' . urlencode($status_message)));
        exit;
    }
});


add_action('admin_notices', function () {
    if (isset($_GET['message'])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($_GET['message']) . '</p></div>';
    }
});


if ( ! function_exists( 'load_finance_template' ) ) {
    function load_finance_template() {
        //echo '<h2>Finance Dashboard Content</h2>';
    }
}



/**
 * Add first/last name fields to WooCommerce registration form (for customers only)
 */
add_action('woocommerce_register_form_start', 'add_customer_name_fields');
function add_customer_name_fields() {
    ?>
    <p class="form-row form-row-first">
        <label for="reg_billing_first_name"><?php esc_html_e('First name', 'woocommerce'); ?> <span class="required">*</span></label>
        <input type="text" class="input-text" name="billing_first_name" id="reg_billing_first_name" value="<?php if (!empty($_POST['billing_first_name'])) esc_attr_e($_POST['billing_first_name']); ?>" />
    </p>
    <p class="form-row form-row-last">
        <label for="reg_billing_last_name"><?php esc_html_e('Last name', 'woocommerce'); ?> <span class="required">*</span></label>
        <input type="text" class="input-text" name="billing_last_name" id="reg_billing_last_name" value="<?php if (!empty($_POST['billing_last_name'])) esc_attr_e($_POST['billing_last_name']); ?>" />
    </p>
    <div class="clear"></div>
    <?php
}

/**
 * Validate customer name fields
 */
add_filter('woocommerce_registration_errors', 'validate_customer_name_fields', 10, 3);
function validate_customer_name_fields($errors, $username, $email) {
    // Only validate for customers (not vendors)
    if (!isset($_POST['role']) || $_POST['role'] === 'customer') {
        if (empty($_POST['billing_first_name'])) {
            $errors->add('billing_first_name_error', __('First name is required!', 'woocommerce'));
        }
        if (empty($_POST['billing_last_name'])) {
            $errors->add('billing_last_name_error', __('Last name is required!', 'woocommerce'));
        }
    }
    return $errors;
}

/**
 * Save customer name fields
 */

add_action('user_register', 'save_customer_names_ultimate', 99, 1);
add_action('woocommerce_created_customer', 'save_customer_names_ultimate', 99, 1);
add_action('dokan_new_seller_created', 'save_customer_names_ultimate', 99, 1);
function save_customer_names_ultimate($customer_id) {
    // Only save for customers (not vendors)
   
        if (isset($_POST['billing_first_name'])) {
            update_user_meta($customer_id, 'first_name', sanitize_text_field($_POST['billing_first_name']));
            update_user_meta($customer_id, 'billing_first_name', sanitize_text_field($_POST['billing_first_name']));
        }
        if (isset($_POST['billing_last_name'])) {
            update_user_meta($customer_id, 'last_name', sanitize_text_field($_POST['billing_last_name']));
            update_user_meta($customer_id, 'billing_last_name', sanitize_text_field($_POST['billing_last_name']));
        }
    
}



// Then add our custom text
add_action('woocommerce_register_form', 'brags_custom_registration_text');
function brags_custom_registration_text() {
    // Get policy pages dynamically
    $terms_page = get_page_by_path('terms-and-conditions');
    $privacy_page = get_page_by_path('privacy-policy');
    $cookies_page = get_page_by_path('cookies-policy');
    
    // Create links or fallback to text
    $terms_link = $terms_page ? 
        '<a href="' . esc_url(get_permalink($terms_page->ID)) . '" target="_blank" rel="noopener">' . esc_html__('Terms & Conditions', 'woocommerce') . '</a>' : 
        esc_html__('Terms & Conditions', 'woocommerce');
    
    $privacy_link = $privacy_page ? 
        '<a href="' . esc_url(get_permalink($privacy_page->ID)) . '" target="_blank" rel="noopener">' . esc_html__('Privacy Policy', 'woocommerce') . '</a>' : 
        esc_html__('Privacy Policy', 'woocommerce');
    
    $cookies_link = $cookies_page ? 
        '<a href="' . esc_url(get_permalink($cookies_page->ID)) . '" target="_blank" rel="noopener">' . esc_html__('Cookies Policy', 'woocommerce') . '</a>' : 
        esc_html__('Cookies Policy', 'woocommerce');
    
    // Output the text
    echo '<p class="woocommerce-privacy-policy-text2" style="margin: 1em 0;">';
    printf(
        esc_html__('By creating a Brags account, you agree to our %1$s, %2$s and %3$s.', 'woocommerce'),
        $terms_link,
        $privacy_link,
        $cookies_link
    );
    
    echo '</p>';
    echo '<p class="woocommerce-privacy-policy-text2" style="margin: 1em 0;">';
    printf('Brags is a multi-seller marketplace. Brags & Partners Ltd acts only as a payment processor and facilitator, we are not responsible for the products sellers list, their product listings or any VAT associated with those goods');
    echo '</p>';

    echo '<p class="woocommerce-privacy-policy-text2" style="margin: 1em 0;">';
    printf('All sellers on Brags.co.uk confirm that they have the legal right to sell in the UK, hold necessary product documentation, insurances, and are solely responsible for their listings. This includes ensuring timely delivery, handling returns and responding to customer queries.');
    echo '</p>';

    echo '<p class="woocommerce-privacy-policy-text2" style="margin: 1em 0;">';
    printf('For any product or delivery questions, please contact sellers directly via the ‘Contact Seller’ button on the product page.');
    echo '</p>';
}


add_action('wp_footer', function () {
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const closeBtn = document.querySelector('.mobile-nav .wd-heading');
            const menuTab = document.querySelector('.mobile-tab-title.mobile-pages-title');
            const shopTab = document.querySelector('.mobile-tab-title.mobile-categories-title');

            if (!closeBtn || !menuTab || !shopTab) return;

            //closeBtn.style.display = 'none';

            menuTab.addEventListener('click', function () {
                closeBtn.style.display = 'block';
            });

            shopTab.addEventListener('click', function () {
                closeBtn.style.display = 'none';
            });
        });
    </script>
    <?php
}, 100);
