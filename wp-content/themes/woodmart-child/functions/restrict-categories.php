<?php
// 1. Add the "Category Approval" menu item to the Dokan dashboard.
function add_dokan_category_approval_menu($urls)
{
    $urls['category-approval'] = array(
        'title'      => __('Category Evaluation', 'dokan'),
        'icon'       => '<i class="fas fa-file-upload"></i>',
        'url'        => dokan_get_navigation_url('category-approval'),
        'pos'        => 55,
        'permission' => 'dokan_view_product_menu',
    );
    // print_r($urls);
    return $urls;
}
add_filter('dokan_get_dashboard_nav', 'add_dokan_category_approval_menu');

// 2. Register the custom query variable.
function register_dokan_custom_query_vars($query_vars)
{
    $query_vars[] = 'category-approval';
    return $query_vars;
}
add_filter('query_vars', 'register_dokan_custom_query_vars');

// 3. Load the custom template.
function load_dokan_category_approval_template()
{
    global $wp_query;

    if (isset($wp_query->query_vars['category-approval'])) {
        $template_path = get_stylesheet_directory() . '/template-parts/dokan/category-approval.php';

        if (file_exists($template_path)) {
            include $template_path;
            exit;
        } else {
            wp_die('Category Evaluation template not found.');
        }
    }
}
add_action('template_redirect', 'load_dokan_category_approval_template');

// 4. Flush rewrite rules and add rewrite endpoint.
function my_flush_rules()
{
    add_rewrite_endpoint('category-approval', EP_ROOT | EP_PAGES);
    flush_rewrite_rules();
}
add_action('init', 'my_flush_rules');




// =====================================================================================================
// Add "Requires Approval?" checkbox to category add/edit form
function add_requires_approval_checkbox_to_category($term)
{
    $requires_approval = get_term_meta($term->term_id, 'requires_approval', true);
?>
    <tr class="form-field">

        <td>
            <input type="checkbox" name="requires_approval" id="requires_approval" value="yes" <?php checked($requires_approval, 'yes'); ?>>
            <span for="requires_approval"><?php esc_html_e('Requires Approval?', 'dokan-lite'); ?></span>
            <p class="description"><?php esc_html_e('Check this if products in this category require approval before publishing.', 'dokan-lite'); ?></p>
        </td>
    </tr>
<?php
}
add_action('product_cat_edit_form_fields', 'add_requires_approval_checkbox_to_category');
add_action('product_cat_add_form_fields', 'add_requires_approval_checkbox_to_category');

// Save "Requires Approval?" checkbox value
function save_requires_approval_checkbox($term_id)
{
    $requires_approval = isset($_POST['requires_approval']) ? 'yes' : 'no';
    update_term_meta($term_id, 'requires_approval', $requires_approval);
}
add_action('edited_product_cat', 'save_requires_approval_checkbox');
add_action('create_product_cat', 'save_requires_approval_checkbox');



function create_category_approval_requests_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'category_approval_requests';
    $charset_collate = $wpdb->get_charset_collate();

    // Check if the table already exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            category_id BIGINT UNSIGNED NOT NULL,
            documents TEXT NOT NULL,
            status ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES {$wpdb->prefix}terms(term_id) ON DELETE CASCADE
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
add_action('after_setup_theme', 'create_category_approval_requests_table');

function get_category_approval_requests($user_id = null, $status = null)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'category_approval_requests';

    $query = "SELECT * FROM $table_name WHERE 1=1";
    $params = array();

    if (!empty($user_id)) {
        $query .= " AND user_id = %d";
        $params[] = $user_id;
    }

    if (!empty($status)) {
        $query .= " AND status = %s";
        $params[] = $status;
    }

    return $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);
}

function handle_category_approval_request()
{
    global $wpdb, $current_user;
    $category = intval($_POST['category']);
    $upload_dir = wp_upload_dir();
    $uploaded_files = [];



    foreach ($_FILES['category_docs']['name'] as $key => $value) {
        if ($_FILES['category_docs']['error'][$key] == 0) {
            $file_name = basename($_FILES['category_docs']['name'][$key]);
            $target_path = $upload_dir['path'] . '/' . $file_name;

            if (move_uploaded_file($_FILES['category_docs']['tmp_name'][$key], $target_path)) {
                $uploaded_files[] = $upload_dir['url'] . '/' . $file_name;
            }
        }
    }

    if (!empty($uploaded_files)) {
        $wpdb->insert(
            "{$wpdb->prefix}category_approval_requests",
            array(
                'user_id' => $current_user->ID,
                'category_id' => $category,
                'documents' => json_encode($uploaded_files),
            ),
            array('%d', '%d', '%s')
        );
    }
    include(get_stylesheet_directory() . '/template-parts/dokan/category-approval-requests-list.php');
    wp_die();
}
add_action('wp_ajax_category_approval_request', 'handle_category_approval_request');
add_action('wp_ajax_nopriv_category_approval_request', 'handle_category_approval_request'); // For logged-in users only


// Handle Delete Request
add_action('wp_ajax_delete_category_request', function () {
    global $wpdb;
    $id = intval($_POST['id']);
    $wpdb->delete("{$wpdb->prefix}category_approval_requests", ['id' => $id]);
    echo "Deleted";
    wp_die();
});

// Handle Edit Request
add_action('wp_ajax_edit_category_request', function () {
    global $wpdb;
    $id = intval($_POST['id']);
    $category_id = intval($_POST['category']);

    // Retrieve existing documents
    $existing_docs = $wpdb->get_var($wpdb->prepare("SELECT documents FROM {$wpdb->prefix}category_approval_requests WHERE id = %d", $id));
    $docs = !empty($existing_docs) ? json_decode($existing_docs, true) : [];

    // Handle File Uploads (if new files uploaded)
    if (!empty($_FILES['category_docs']['name'][0])) {
        require_once ABSPATH . 'wp-admin/includes/file.php';

        foreach ($_FILES['category_docs']['name'] as $key => $name) {
            if ($_FILES['category_docs']['error'][$key] === UPLOAD_ERR_OK) {
                $file = [
                    'name'     => $name,
                    'type'     => $_FILES['category_docs']['type'][$key],
                    'tmp_name' => $_FILES['category_docs']['tmp_name'][$key],
                    'error'    => $_FILES['category_docs']['error'][$key],
                    'size'     => $_FILES['category_docs']['size'][$key],
                ];

                $uploaded_file = wp_handle_upload($file, ['test_form' => false]);

                if ($uploaded_file && !isset($uploaded_file['error'])) {
                    $docs[] = $uploaded_file['url']; // Append new file
                }
            }
        }
    }

    // Update Database
    $wpdb->update(
        "{$wpdb->prefix}category_approval_requests",
        ['category_id' => $category_id, 'documents' => json_encode($docs)],
        ['id' => $id]
    );

    echo json_encode(['status' => 'success', 'message' => 'Category request updated successfully']);
    wp_die();
});


function enqueue_category_approval_scripts()
{
    //if (is_page('category-approval')) { // or your check for the page.
    wp_enqueue_style('category-approval-style', get_stylesheet_directory_uri() . '/assets/css/category-approval.css');
    wp_enqueue_script('category-approval-script', get_stylesheet_directory_uri() . '/assets/js/category-approval.js', array('jquery'), null, true);
    wp_localize_script('category-approval-script', 'ajaxurl', admin_url('admin-ajax.php'));
    // }
}
add_action('wp_enqueue_scripts', 'enqueue_category_approval_scripts');


function restrict_categories_for_dokan_sellers($terms, $taxonomies, $args)
{
    if (!in_array('product_cat', (array) $taxonomies)) {
        return $terms; // Apply only to product categories
    }

    global $wpdb;
    $current_user = wp_get_current_user();

    // Allow admins full access
    if (in_array('administrator', $current_user->roles)) {
        return $terms;
    }

    // Check if the user is a Dokan seller and is on the dashboard products page
    if (in_array('seller', $current_user->roles) && is_dokan_dashboard_product_page()) {
        $restricted_categories = [];

        foreach ($terms as $key => $term) {
            if (!is_object($term)) {
                continue; // Ensure term is an object
            }

            // Check if category requires approval
            $requires_approval = get_term_meta($term->term_id, 'requires_approval', true);

            if ($requires_approval === 'yes') {
                // Check if user has approval
                $is_approved = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}category_approval_requests
                    WHERE user_id = %d AND category_id = %d AND status = %s",
                    $current_user->ID,
                    $term->term_id,
                    'Approved'
                ));

                if (!$is_approved) {
                    $restricted_categories[] = $term->term_id; // Store restricted category
                }
            }
        }

        // Remove restricted categories
        foreach ($terms as $key => $term) {
            if (in_array($term->term_id, $restricted_categories)) {
                unset($terms[$key]);
            }
        }
    }

    return $terms;
}

// Function to check if we are on Dokan's product-related pages
function is_dokan_dashboard_product_page()
{
    if (isset($_SERVER['REQUEST_URI'])) {
        $request_uri = $_SERVER['REQUEST_URI'];
        return (strpos($request_uri, '/dashboard/products') !== false);
    }
    return false;
}

// Apply filter to Dokan product edit page
add_filter('get_terms', 'restrict_categories_for_dokan_sellers', 10, 3);



// ----------------------------------------bragar-------------------------------------------

// Add "Category Approval" tab in WooCommerce My Account Menu
function add_category_approval_menu_item($items)
{
    $current_user = wp_get_current_user();
    //print_r($current_user->roles);
    // Show only for subscriber
    if (in_array('subscriber', (array) $current_user->roles)) {
        $items['manage-category-approval'] = __('Category Evaluation', 'textdomain'); // Add new tab
    }

    return $items;
}
add_filter('woocommerce_account_menu_items', 'add_category_approval_menu_item');




// Display content for "Category Approval" tab
function category_approval_content()
{
    global $wpdb, $current_user;

    echo "<style>.uap-ap-wrap h3{     display: none; }</style>";

    // Fetch requests from the database

    // $requests = $wpdb->get_results(
    //     $wpdb->prepare(
    //         "SELECT * FROM wp_category_approval_requests"
    //     ),
    //     ARRAY_A
    // );
    $requests = $wpdb->get_results(
        "SELECT * FROM {$wpdb->prefix}category_approval_requests",
        ARRAY_A
    );
?>

    <?php if (!empty($requests)): ?>
        <!-- <table class="woocommerce-table"> -->
        <table class="wp-list-table widefat striped woocommerce-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Documents</th>
                    <th>Seller Email</th> <!-- New Column -->
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $request): ?>
                    <tr>
                        <td><?php echo esc_html(get_term($request['category_id'])->name); ?></td>
                        <td>
                            <span class="status-badge <?php echo strtolower($request['status']); ?>">
                                <?php echo esc_html($request['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $docs = json_decode($request['documents'], true);
                            foreach ($docs as $doc): ?>
                                <a href="<?php echo esc_url($doc); ?>" target="_blank" class="doc-link">
                                    View Document
                                </a><br>
                            <?php endforeach; ?>
                        </td>
                        <td>
                            <?php
                            $user = get_user_by('id', $request['user_id']);
                            echo esc_html($user ? $user->user_email : 'Unknown');
                            ?>
                        </td>
                        <td>
                            <?php if ($request['status'] == 'Pending'): ?>
                                <button class="approve-btn" data-id="<?php echo esc_attr($request['id']); ?>"> Approve</button>
                                <button class="reject-btn" data-id="<?php echo esc_attr($request['id']); ?>"> Reject</button>
                            <?php else: ?>
                                <span class="status-<?php echo strtolower($request['status']); ?>">
                                    <?php echo esc_html(ucfirst($request['status'])); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>

        </table>
    <?php else: ?>
        <p>No category evaluation requests found.</p>
    <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.approve-btn, .reject-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const requestId = this.getAttribute('data-id');
                    const actionType = this.classList.contains('approve-btn') ? 'approve' : 'reject';

                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: `action=handle_category_approval&id=${requestId}&status=${actionType}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                location.reload();
                            } else {
                                alert('Something went wrong! Please try again.');
                            }
                        })
                        .catch(error => console.error('Error:', error));
                });
            });
        });
    </script>
<?php
}
add_action('woocommerce_account_manage-category-approval_endpoint', 'category_approval_content');


function handle_category_approval()
{
    global $wpdb;

    if (!isset($_POST['id'], $_POST['status'])) {
        wp_send_json_error(['message' => 'Invalid request.']);
    }

    $id = intval($_POST['id']);
    $status = ($_POST['status'] === 'approve') ? 'approved' : 'rejected';

    $updated = $wpdb->update(
        "{$wpdb->prefix}category_approval_requests",
        ['status' => $status],
        ['id' => $id],
        ['%s'],
        ['%d']
    );

    if ($updated) {
        wp_send_json_success(['message' => 'Request updated successfully.']);
    } else {
        wp_send_json_error(['message' => 'Failed to update request.']);
    }
}
add_action('wp_ajax_handle_category_approval', 'handle_category_approval');
add_action('wp_ajax_nopriv_handle_category_approval', 'handle_category_approval');


// Register custom endpoint
function add_category_approval_endpoint()
{
    add_rewrite_endpoint('manage-category-approval', EP_ROOT | EP_PAGES);
}
add_action('init', 'add_category_approval_endpoint');

// Flush rewrite rules on plugin activation (Only needed once)
function category_approval_flush_rewrite_rules()
{
    add_category_approval_endpoint();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'category_approval_flush_rewrite_rules');

add_action('admin_menu', 'register_category_approval_admin_menu');

function register_category_approval_admin_menu()
{
    add_submenu_page(
        'edit.php?post_type=product',             // WooCommerce "Products" menu slug
        'Category Evaluation Requests',             // Page title
        'Category Evaluation',                      // Menu title
        'manage_options',                         // Capability
        'category-approval-requests',             // Slug
        'render_category_approval_admin_page'     // Callback
    );
}
add_action('admin_menu', 'register_category_approval_admin_menu');

function render_category_approval_admin_page()
{
    echo '<div class="wrap"><h1>Category Evaluation Requests</h1>';
    category_approval_content(); // Your custom function to show content
    echo '</div>';
}
// function enqueue_category_approval_admin_css($hook)
// {
//     if ($hook !== 'product_page_category-approval-requests') {
//         return;
//     }

//     wp_enqueue_style(
//         'category-approval-css',
//         get_stylesheet_directory_uri() . '/woodmart-child/assets/css/category-approval.css',
//         array(),
//         '1.0'
//     );
// }
// add_action('admin_enqueue_scripts', 'enqueue_category_approval_admin_css');

function enqueue_category_approval_admin_css($hook)
{
    // Only load on our custom Category Approval admin page
    if ($hook !== 'product_page_category-approval-requests') {
        return;
    }

    // Load CSS from your child theme (correct path)
    wp_enqueue_style(
        'category-approval-css',
        get_stylesheet_directory_uri() . '/assets/css/category-approval.css',
        array(),
        '1.0'
    );
}
add_action('admin_enqueue_scripts', 'enqueue_category_approval_admin_css');
