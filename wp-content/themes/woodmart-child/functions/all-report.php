<?php

// -------------------------------------------- report customer seller page admin side --------------------------------------------
function create_report_customer_seller_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . "report_customer_seller";
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        seller_name VARCHAR(100) NOT NULL,
        seller_email VARCHAR(100) NOT NULL,
        company_name VARCHAR(100) NOT NULL,
        customer_name VARCHAR(100) NOT NULL,
        seller_order_id VARCHAR(100) NOT NULL,
        seller_message TEXT NOT NULL,
        files TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
add_action('init', 'create_report_customer_seller_table');

function handle_submit_report_customer_submission() {
    if ( isset($_POST['submit_report_customer']) && isset($_POST['report_customer_nonce']) ) {
        if ( ! wp_verify_nonce( $_POST['report_customer_nonce'], 'report_customer_action' ) ) {
            die( 'Security check failed' );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . "report_customer_seller";

        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            wp_die(__('You must be logged in to submit a report.', 'your-textdomain'));
        }

        $seller_name = sanitize_text_field($_POST['seller_name']);
        $seller_email = sanitize_email($_POST['seller_email']);
        $company_name = sanitize_text_field($_POST['company_name']);
        $customer_name = sanitize_text_field($_POST['customer_name']);
        $seller_order_id = sanitize_text_field($_POST['order_id']);
        $seller_message = sanitize_textarea_field($_POST['message']);


        $files_new = [];
        if ( !empty($_FILES['evidence_files']['name'][0]) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            $upload_dir = wp_upload_dir();
            $custom_upload_path = $upload_dir['basedir'] . '/report_customer_seller/';

            if ( ! file_exists($custom_upload_path) ) {
                wp_mkdir_p($custom_upload_path);
            }

            foreach ($_FILES['evidence_files']['name'] as $key => $value) {
                if ($_FILES['evidence_files']['size'][$key] > 0) {
                    $file = array(
                        'name'     => $_FILES['evidence_files']['name'][$key],
                        'type'     => $_FILES['evidence_files']['type'][$key],
                        'tmp_name' => $_FILES['evidence_files']['tmp_name'][$key],
                        'error'    => $_FILES['evidence_files']['error'][$key],
                        'size'     => $_FILES['evidence_files']['size'][$key],
                    );

                    // Generate unique filename
                    $file_name = sanitize_file_name($file['name']);
                    $unique_filename = wp_unique_filename($custom_upload_path, $file_name);
                    $new_file_path = $custom_upload_path . $unique_filename;


                    if (move_uploaded_file($file['tmp_name'], $new_file_path)) {
                        $files_new[] = $upload_dir['baseurl'] . '/report_customer_seller/' . $unique_filename;
                    }
                }
            }
        }

        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $current_user_id,
                'seller_name' => $seller_name,
                'seller_email' => $seller_email,
                'company_name' => $company_name,
                'customer_name' => $customer_name,
                'seller_order_id' => $seller_order_id,
                'seller_message' => $seller_message,
                'files' => json_encode($files_new),
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        $to = $seller_email;
        $subject = __('Thank you for your Report – Brags & Partners Ltd', 'your-textdomain');
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $first_name = explode(' ', $seller_name)[0];

        $message = "
            <p>Hi {$first_name},</p>
            <p>Thank you for Reporting a Customer, we will review the information provided as soon as possible.</p>
            <p>Many thanks,<br>Brags & Partners Ltd</p>
        ";

        wp_mail($to, $subject, $message, $headers);


        wp_redirect( add_query_arg('report_customer_status', 'success', wp_get_referer()) );
        exit;
    }
}
add_action('init', 'handle_submit_report_customer_submission');


function display_reported_seller($user) {
    if (!in_array('seller', (array) $user->roles)) {
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . "report_customer_seller";

    $can_view_reports = get_user_meta($user->ID, 'allow_report_view', true);
    if (!$can_view_reports || $can_view_reports == '0') {
        return;
    }

    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at ASC",
        $user->ID
    ));

    echo '<h3>' . __( 'Report Sellers', 'your-textdomain' ) . '</h3>';

    if (empty($results)) {
        echo '<p style="color:red;">' . __( 'No reports found.', 'your-textdomain' ) . '</p>';
        return;
    }

    echo '<table class="dokan-table widefat striped">';
    echo '<thead><tr>
            <th>ID</th>
            <th>Seller Name</th>
            <th>Seller Email</th>
            <th>Company</th>
            <th>Customer Name</th>
            <th>Order ID</th>
            <th>Message</th>
            <th>Files</th>
        </tr></thead><tbody>';

        foreach ($results as $row) {
            $files_new = json_decode($row->files);
            $file_links = '';
            if ($files_new) {
                foreach ($files_new as $file) {
                    $file_type = wp_check_filetype($file);
                    if (in_array($file_type['type'], ['image/jpeg', 'image/png', 'image/jpg'])) {
                        $file_links .= '<a href="' . esc_url($file) . '" target="_blank">View</a><br>';
                    }
                }
            }
        echo "<tr>
                <td>{$row->id}</td>
                <td>{$row->seller_name}</td>
                <td>{$row->seller_email}</td>
                <td>{$row->company_name}</td>
                <td>{$row->customer_name}</td>
                <td>{$row->seller_order_id}</td>
                <td>{$row->seller_message}</td>
                <td>{$file_links}</td>
              </tr>";
    }

    echo '</tbody></table>';
}
add_action('show_user_profile', 'display_reported_seller');
add_action('edit_user_profile', 'display_reported_seller');

// -------------------------------------------- report_product admin side --------------------------------------------

function create_report_product_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . "reported_products_new";
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        report_type VARCHAR(50) NOT NULL,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        seller_name VARCHAR(100) NOT NULL,
        company VARCHAR(100) NOT NULL,
        Link_Product_Listing VARCHAR(100) NOT NULL,
        seller_url VARCHAR(100) NOT NULL,
        order_id VARCHAR(100) NOT NULL,
        message TEXT NOT NULL,
        files TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
add_action('init', 'create_report_product_table');

function handle_report_product_submission() {
    if ( isset($_POST['submit_report_product']) && isset($_POST['report_product_form_nonce']) ) {
        if ( ! wp_verify_nonce( $_POST['report_product_form_nonce'], 'report_product_form_action' ) ) {
            die( 'Security check failed' );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . "reported_products_new";

        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            wp_die(__('You must be logged in to submit a report.', 'your-textdomain'));
        }

        $report_type = sanitize_text_field($_POST['report_product_type']);
        $name = sanitize_text_field($_POST['report_product_your_name']);
        $email = sanitize_email($_POST['report_product_your_email']);
        $company = sanitize_text_field($_POST['report_product_your_company']);
        $seller_name = sanitize_text_field($_POST['report_product_seller_name']);
        $seller_url = esc_url_raw($_POST['report_product_seller_url']);
        $Link_Product_Listing = sanitize_text_field($_POST['report_product_Link_Product_Listing']);
        $order_id = sanitize_text_field($_POST['report_product_order_id']);
        $message = sanitize_textarea_field($_POST['report_product_your_message']);

        $files_new = [];
        if ( !empty($_FILES['report_product_supporting_files']['name'][0]) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            $upload_dir = wp_upload_dir();
            $custom_upload_path = $upload_dir['basedir'] . '/reported_products/';

            if ( ! file_exists($custom_upload_path) ) {
                wp_mkdir_p($custom_upload_path);
            }

            foreach ($_FILES['report_product_supporting_files']['name'] as $key => $value) {
                if ($_FILES['report_product_supporting_files']['size'][$key] > 0) {
                    $file = array(
                        'name'     => $_FILES['report_product_supporting_files']['name'][$key],
                        'type'     => $_FILES['report_product_supporting_files']['type'][$key],
                        'tmp_name' => $_FILES['report_product_supporting_files']['tmp_name'][$key],
                        'error'    => $_FILES['report_product_supporting_files']['error'][$key],
                        'size'     => $_FILES['report_product_supporting_files']['size'][$key],
                    );

                    // Generate unique filename
                    $file_name = sanitize_file_name($file['name']);
                    $unique_filename = wp_unique_filename($custom_upload_path, $file_name);
                    $new_file_path = $custom_upload_path . $unique_filename;

                    if (move_uploaded_file($file['tmp_name'], $new_file_path)) {
                        $files_new[] = $upload_dir['baseurl'] . '/reported_products/' . $unique_filename;
                    }
                }
            }
        }

        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $current_user_id,
                'report_type' => $report_type,
                'name' => $name,
                'email' => $email,
                'seller_name' => $seller_name,
                'company' => $company,
                'seller_url' => $seller_url,
                'Link_Product_Listing' => $Link_Product_Listing,
                'order_id' => $order_id,
                'message' => $message,
                'files' => json_encode($files_new),
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );



        wp_redirect( add_query_arg('report_status', 'success', wp_get_referer()) );
        exit;
    }
}
add_action('init', 'handle_report_product_submission');

// function display_reported_products($user) {
//     if (!in_array('customer', (array) $user->roles)) {
//         return;
//     }

//     global $wpdb;
//     $table_name = $wpdb->prefix . "reported_products_new";

//     $can_view_reports = get_user_meta($user->ID, 'allow_report_view', true);
//     if (!$can_view_reports || $can_view_reports == '0') {
//         return;
//     }

//     $results = $wpdb->get_results($wpdb->prepare(
//         "SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at ASC",
//         $user->ID
//     ));

//     echo '<h3>' . __( 'Reported Products & Sellers', 'your-textdomain' ) . '</h3>';

//     if (empty($results)) {
//         echo '<p style="color:red;">' . __( 'No reports found.', 'your-textdomain' ) . '</p>';
//         return;
//     }

//     echo '<table class="dokan-table widefat striped">';
//     echo '<thead><tr>
//             <th>ID</th>
//             <th>Type</th>
//             <th>Name</th>
//             <th>Email</th>
//             <th>Company</th>
//             <th>Seller Name</th>
//             <th>Seller URL</th>
//             <th>Link Product Listing</th>
//             <th>Order ID</th>
//             <th>Message</th>
//             <th>Files</th>
//         </tr></thead><tbody>';

//     foreach ($results as $row) {
//         $files_new = json_decode($row->files);
//         $file_links = '';
//         if ($files_new) {
//             foreach ($files_new as $file) {
//                 $file_type = wp_check_filetype($file);
//                 if (in_array($file_type['type'], ['image/jpeg', 'image/png', 'image/jpg'])) {
//                     $file_links .= '<a href="' . esc_url($file) . '" target="_blank">View</a><br>';
//                 }
//             }
//         }

//         echo "<tr>
//                 <td>{$row->id}</td>
//                 <td>{$row->report_type}</td>
//                 <td>{$row->name}</td>
//                 <td>{$row->email}</td>
//                 <td>{$row->company}</td>
//                 <td>{$row->seller_name}</td>
//                 <td>{$row->seller_url}</td>
//                 <td>{$row->Link_Product_Listing}</td>
//                 <td>{$row->order_id}</td>
//                 <td>{$row->message}</td>
//                 <td>{$file_links}</td>
//               </tr>";
//     }

//     echo '</tbody></table>';
// }
// add_action('show_user_profile', 'display_reported_products');
// add_action('edit_user_profile', 'display_reported_products');

// -------------------------------------------- create_report_product_table admin side --------------------------------------------


function create_report_seller_customer_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . "report_seller_customer";
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        report_type VARCHAR(50) NOT NULL,
        report_seller_customer_name VARCHAR(100) NOT NULL,
        report_seller_customer_email VARCHAR(100) NOT NULL,
        report_seller_customer_seller_name VARCHAR(100) NOT NULL,
        report_seller_customer_company VARCHAR(100) NOT NULL,
        report_seller_customer_seller_url VARCHAR(100) NOT NULL,
        report_seller_customer_order_id VARCHAR(100) NOT NULL,
        report_seller_customer_message TEXT NOT NULL,
        report_seller_customer_files TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
add_action('init', 'create_report_seller_customer_table');

function handle_report_seller_customer_submission() {
    if ( isset($_POST['submit_seller_form']) && isset($_POST['seller_form_nonce']) ) {
        if ( ! wp_verify_nonce( $_POST['seller_form_nonce'], 'seller_form_action' ) ) {
            die( 'Security check failed' );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . "report_seller_customer";

        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            wp_die(__('You must be logged in to submit a report.', 'your-textdomain'));
        }

        $report_type = sanitize_text_field($_POST['report_product_type']);
        $report_seller_customer_name = sanitize_text_field($_POST['report_seller_your_name']);
        $report_seller_customer_email = sanitize_email($_POST['report_seller_your_email']);
        $report_seller_customer_company = sanitize_text_field($_POST['report_seller_your_company']);
        $report_seller_customer_seller_name = sanitize_text_field($_POST['report_seller_seller_name']);
        $report_seller_customer_seller_url = esc_url_raw($_POST['report_seller_seller_url']);
        $report_seller_customer_order_id = sanitize_text_field($_POST['report_seller_order_id']);
        $report_seller_customer_message = sanitize_textarea_field($_POST['report_seller_your_message']);

        $files_new = [];

        if ( !empty($_FILES['report_seller_sellering_files']['name'][0]) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';

            $upload_dir = wp_upload_dir();
            $custom_upload_path = $upload_dir['basedir'] . '/report_seller_sellering/';

            if ( ! file_exists($custom_upload_path) ) {
                wp_mkdir_p($custom_upload_path);
            }

            foreach ($_FILES['report_seller_sellering_files']['name'] as $key => $value) {
                if ($_FILES['report_seller_sellering_files']['size'][$key] > 0) {

                    $report_seller_customer_files = array(
                        'name'     => $_FILES['report_seller_sellering_files']['name'][$key],
                        'type'     => $_FILES['report_seller_sellering_files']['type'][$key],
                        'tmp_name' => $_FILES['report_seller_sellering_files']['tmp_name'][$key],
                        'error'    => $_FILES['report_seller_sellering_files']['error'][$key],
                        'size'     => $_FILES['report_seller_sellering_files']['size'][$key],
                    );

                    $file_name = sanitize_file_name($report_seller_customer_files['name']);

                    $unique_filename = wp_unique_filename($custom_upload_path, $file_name);
                    $new_file_path = $custom_upload_path . $unique_filename;

                    if ( move_uploaded_file($report_seller_customer_files['tmp_name'], $new_file_path) ) {
                        $files_new[] = $upload_dir['baseurl'] . '/report_seller_sellering/' . $unique_filename;
                    } else {
                        $files_new[] = 'Error uploading file: ' . $file_name;
                    }
                }
            }
        }


        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $current_user_id,
                'report_type' => $report_type,
                'report_seller_customer_name' => $report_seller_customer_name,
                'report_seller_customer_email' => $report_seller_customer_email,
                'report_seller_customer_seller_name' => $report_seller_customer_seller_name,
                'report_seller_customer_company' => $report_seller_customer_company,
                'report_seller_customer_seller_url' => $report_seller_customer_seller_url,
                'report_seller_customer_order_id' => $report_seller_customer_order_id,
                'report_seller_customer_message' => $report_seller_customer_message,
                'report_seller_customer_files' => json_encode($files_new),
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        $to = $report_seller_customer_email;
        $subject = __('Thank you for your report – Brags & Partners Ltd', 'your-textdomain');
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $first_name = explode(' ', $report_seller_customer_name)[0]; // Get first name from full name

        $message = "
            <p>Hi {$first_name},</p>
            <p>Thank you for Reporting a Customer, we will review the information provided as soon as possible.</p>
            <p>Many thanks,<br>Brags & Partners Ltd</p>
        ";

        wp_mail($to, $subject, $message, $headers);


        wp_redirect( add_query_arg('report_all_status', 'success', wp_get_referer()) );
        exit;
    }
}
add_action('init', 'handle_report_seller_customer_submission');

// function display_report_seller_customer($user) {
//     if (!in_array('customer', (array) $user->roles)) {
//         return;
//     }

//     global $wpdb;
//     $table_name = $wpdb->prefix . "report_seller_customer";

//     $can_view_reports = get_user_meta($user->ID, 'allow_report_view', true);
//     if (!$can_view_reports || $can_view_reports == '0') {
//         return;
//     }

//     $results = $wpdb->get_results($wpdb->prepare(
//         "SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at ASC",
//         $user->ID
//     ));

//     echo '<h3>' . __( 'Reported Products & Sellers', 'your-textdomain' ) . '</h3>';

//     if (empty($results)) {
//         echo '<p style="color:red;">' . __( 'No reports found.', 'your-textdomain' ) . '</p>';
//         return;
//     }

//     echo '<table class="dokan-table widefat striped">';
//     echo '<thead><tr>
//             <th>ID</th>
//             <th>Type</th>
//             <th>Name</th>
//             <th>Email</th>
//             <th>Company</th>
//             <th>Seller Name</th>
//             <th>Seller URL</th>
//             <th>Link Product Listing</th>
//             <th>Order ID</th>
//             <th>Message</th>
//             <th>Files</th>
//         </tr></thead><tbody>';

//     foreach ($results as $row) {
//         $files_new = json_decode($row->report_seller_customer_files);
//         $file_links = '';
//         if ($files_new) {
//             foreach ($files_new as $report_seller_customer_files) {
//                 $file_type = wp_check_filetype($report_seller_customer_files);
//                 if (in_array($file_type['type'], ['image/jpeg', 'image/png', 'image/jpg'])) {
//                     $file_links .= '<a href="' . esc_url($report_seller_customer_files) . '" target="_blank">View</a><br>';
//                 }
//             }
//         }

//         echo "<tr>
//                 <td>{$row->id}</td>
//                 <td>{$row->report_type}</td>
//                 <td>{$row->report_seller_customer_name}</td>
//                 <td>{$row->report_seller_customer_email}</td>
//                 <td>{$row->report_seller_customer_company}</td>
//                 <td>{$row->report_seller_customer_seller_name}</td>
//                 <td>{$row->report_seller_customer_seller_url}</td>
//                 <td>{$row->report_seller_customer_order_id}</td>
//                 <td>{$row->report_seller_customer_message}</td>
//                 <td>{$file_links}</td>
//               </tr>";
//     }

//     echo '</tbody></table>';
// }
// add_action('show_user_profile', 'display_report_seller_customer');
// add_action('edit_user_profile', 'display_report_seller_customer');



// --------------------- check box display table --------------------


function display_report_data($user) {
    if (!in_array('customer', (array) $user->roles)) {
        return;
    }

    global $wpdb;

    $can_view_reports = get_user_meta($user->ID, 'allow_report_view', true);
    if (!$can_view_reports || $can_view_reports == '0') {
        return;
    }


    $table_name_seller = $wpdb->prefix . "report_seller_customer";
    $table_name_product = $wpdb->prefix . "reported_products_new";

    $query_seller = $wpdb->prepare("SELECT *, 'seller' as report_type FROM $table_name_seller WHERE user_id = %d", $user->ID);
    $query_product = $wpdb->prepare("SELECT *, 'product' as report_type FROM $table_name_product WHERE user_id = %d", $user->ID);

    $results = array_merge(
        $wpdb->get_results($query_seller),
        $wpdb->get_results($query_product)
    );

    echo '<h3>' . __( 'Reported Products & Sellers', 'your-textdomain' ) . '</h3>';

    if (empty($results)) {
        echo '<p style="color:red;">' . __( 'No reports found.', 'your-textdomain' ) . '</p>';
        return;
    }

    echo '<table class="dokan-table widefat striped">';
    echo '<thead><tr>
            <th>ID</th>
            <th>Type</th>
            <th>Name</th>
            <th>Email</th>
            <th>Company</th>
            <th>Seller Name</th>
            <th>Seller URL</th>
            <th>Link Product Listing</th>
            <th>Order ID</th>
            <th>Message</th>
            <th>Files</th>
        </tr></thead><tbody>';

    foreach ($results as $row) {
        $files_new = json_decode($row->files ?? $row->report_seller_customer_files);
        $file_links = '';
        if ($files_new) {
            foreach ($files_new as $file) {
                $file_type = wp_check_filetype($file);
                if (in_array($file_type['type'], ['image/jpeg', 'image/png', 'image/jpg'])) {
                    $file_links .= '<a href="' . esc_url($file) . '" target="_blank">View</a><br>';
                }
            }
        }

        echo "<tr>
                <td>{$row->id}</td>
                <td>" . ucfirst($row->report_type) . "</td>
                <td>" . ($row->name ?? $row->report_seller_customer_name) . "</td>
                <td>" . ($row->email ?? $row->report_seller_customer_email) . "</td>
                <td>" . ($row->company ?? $row->report_seller_customer_company) . "</td>
                <td>" . ($row->seller_name ?? $row->report_seller_customer_seller_name) . "</td>
                <td>" . ($row->seller_url ?? $row->report_seller_customer_seller_url) . "</td>
                <td>" . ($row->Link_Product_Listing ?? 'N/A') . "</td>
                <td>" . ($row->order_id ?? $row->report_seller_customer_order_id) . "</td>
                <td>" . ($row->message ?? $row->report_seller_customer_message) . "</td>
                <td>{$file_links}</td>
              </tr>";
    }

    echo '</tbody></table>';
}

add_action('show_user_profile', 'display_report_data');
add_action('edit_user_profile', 'display_report_data');


function add_custom_user_field($user) {
    ?>

    <table class="form-table">
        <tr>
            <th><label for="allow_report_view">Allow Report View</label></th>
            <td>
                <input type="checkbox" name="allow_report_view" id="allow_report_view" value="1" <?php checked(get_user_meta($user->ID, 'allow_report_view', true), '1'); ?> disabled>
                <span class="description"><?php _e('Check this to allow the user to report view.', 'your-textdomain'); ?></span>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'add_custom_user_field');
add_action('edit_user_profile', 'add_custom_user_field');

function save_custom_user_field($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    update_user_meta($user_id, 'allow_report_view', isset($_POST['allow_report_view']) ? '1' : '1');
    // update_user_meta($user_id, 'allow_report_view', isset($_POST['allow_report_view']) ? '1' : '0');
}
add_action('personal_options_update', 'save_custom_user_field');
add_action('edit_user_profile_update', 'save_custom_user_field');


// add_filter( 'dokan_is_product_import_export_allowed', 'custom_dokan_import_export_for_specific_sellers', 10, 2 );

// function custom_dokan_import_export_for_specific_sellers( $is_allowed, $user_id ) {
//     $user = get_userdata( $user_id );

//     if ( ! in_array( 'seller', (array) $user->roles ) ) {
//         return false;
//     }

//     if ( function_exists( 'dokan_get_vendor_subscription_details' ) ) {
//         $subscription = dokan_get_vendor_subscription_details( $user_id );

//         if ( ! empty( $subscription['product_id'] ) ) {
//             $product_id = $subscription['product_id'];
//             $plan_name = get_the_title( $product_id );

//             $allowed_plan_names = array( 'Pro', 'Bragsy Seller' );

//             if ( in_array( $plan_name, $allowed_plan_names ) ) {
//                 return true;
//             }
//         }
//     }

//     return false;
// }



// ------------------------------ seller product add text changes -----------------------------------------


add_filter( 'gettext', 'custom_change_dokan_upload_text', 20, 3 );

function custom_change_dokan_upload_text( $translated_text, $text, $domain ) {
    if ( 'dokan-lite' === $domain && trim( $translated_text ) === 'Upload a product cover image' ) {
        $translated_text = 'Upload your first Product Image here';
    }
    return $translated_text;
}

add_filter( 'gettext', 'custom_change_dokan_vacation_texts', 20, 3 );
function custom_change_dokan_vacation_texts( $translated_text, $text, $domain ) {
    if ( in_array( $domain, ['dokan', 'dokan-lite'] ) ) {

        if ( $text === 'Go to Vacation' ) {
            $translated_text = 'Holiday Settings';
        }

        if ( $text === 'Want to go vacation by closing our store publically' ) {
            $translated_text = 'Check this box to enable holiday mode instantly or choose your holiday dates';
        }

        if ( $text === 'Set Vacation Message' ) {
            $translated_text = 'Set Holiday Message';
        }
    }
    return $translated_text;
}

add_filter( 'gettext', 'change_vendor_information_title', 20, 3 );
function change_vendor_information_title( $translated_text, $text, $domain ) {
    if ( $domain === 'dokan-lite' || $domain === 'dokan' ) {
        if ( $text === 'Vendor Information' ) {
            $translated_text = 'Seller Information';
        }

        if ( $text === 'Vendor Info' ) {
            $translated_text = 'Seller Info';
        }
    }
    return $translated_text;
}

add_action( 'wp_footer', 'inject_vendor_vat_with_jquery' );
function inject_vendor_vat_with_jquery() {
    if ( ! is_product() ) {
        return;
    }

    $author_id = get_post_field( 'post_author', get_the_ID() );
    $vat_number = get_user_meta( $author_id, 'dokan_vat_number', true );

    if ( empty( $vat_number ) ) {
        return;
    }
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        var vatNumber = '<?php echo esc_js( $vat_number ); ?>';
        var vatHtml = '<li class="store-vat"><span><strong>VAT Number:</strong></span> <span class="details">' + vatNumber + '</span></li>';
        $('.store-address').after(vatHtml);
    });
    </script>
    <?php
}

add_action('woocommerce_after_single_product_summary', 'add_dokan_disclaimer_and_button_below_tabs', 15);

function add_dokan_disclaimer_and_button_below_tabs() {
    global $product;

    $vendor_id = get_post_field('post_author', $product->get_id());

    if ($vendor_id == 1) {
        return;
    }

    echo '<div class="seller-disclaimer" style="margin-top:30px; padding:15px; border-top:1px solid #eee;">';
    echo '<p>✔️<strong>Brags & Partners Ltd act only as a payment processor and facilitator of this product</strong></p>';
    echo '<p>✔️<strong>Brags & Partners Ltd are not the seller of this product</strong></p>';
    echo '<p>✔️<strong>Brags & Partners Ltd are not responsible for VAT on the goods</strong></p>';
    echo '<p>✔️<strong>Any questions or issues, please contact the Seller directly</strong></p>';

    // Add Dokan Contact Seller button
    // echo '<button data-store_id="' . esc_attr($vendor_id) . '" class="dokan-store-support-btn-product dokan-store-support-btn button alt user_logged_out">Contact Seller</button>';
    echo '</div>';
}




add_action('woocommerce_checkout_before_terms_and_conditions', 'add_seller_checkout_notice_after_terms');

function add_seller_checkout_notice_after_terms() {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    $cart = WC()->cart->get_cart();
    $sellers = [];
    $admin_id = get_current_user_id();

    foreach ($cart as $cart_item) {
        $product_id = $cart_item['product_id'];
        $author_id = get_post_field('post_author', $product_id);
        if (!in_array($author_id, $sellers)) {
            $sellers[] = $author_id;
        }
    }

    $admin_id = 1;

    if (!empty($sellers) && !(count($sellers) === 1 && $sellers[0] == $admin_id)) {
        echo '<div class="woocommerce" style="margin-top: 20px;">';
        echo '✔️ Brags & Partners Ltd act only as a payment processor and facilitator of this product<br>';
        echo '✔️ Brags & Partners Ltd are not the seller of this product<br>';
        echo '✔️ Brags & Partners Ltd are not responsible for VAT on the goods<br>';
        echo '✔️ Any questions or issues, please contact the Seller directly';
        echo '</div>';
    }
}