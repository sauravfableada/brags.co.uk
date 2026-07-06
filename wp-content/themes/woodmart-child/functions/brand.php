<?php


function create_brands_post_type() {
    $labels = array(
        'name'               => _x( 'Brands', 'post type general name', 'textdomain' ),
        'singular_name'      => _x( 'Brand', 'post type singular name', 'textdomain' ),
        'menu_name'          => _x( 'Brands', 'admin menu', 'textdomain' ),
        'name_admin_bar'     => _x( 'Brand', 'add new on admin bar', 'textdomain' ),
        'add_new'            => _x( 'Add New', 'brand', 'textdomain' ),
        'add_new_item'       => __( 'Add New Brand', 'textdomain' ),
        'new_item'           => __( 'New Brand', 'textdomain' ),
        'edit_item'          => __( 'Edit Brand', 'textdomain' ),
        'view_item'          => __( 'View Brand', 'textdomain' ),
        'all_items'          => __( 'All Brands', 'textdomain' ),
        'search_items'       => __( 'Search Brands', 'textdomain' ),
        'parent_item_colon'  => __( 'Parent Brands:', 'textdomain' ),
        'not_found'          => __( 'No brands found.', 'textdomain' ),
        'not_found_in_trash' => __( 'No brands found in Trash.', 'textdomain' ),
    );

    $args = array(
        'labels'             => $labels,
        'description'        => __( 'Manage brands.', 'textdomain' ),
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'brand' ), // Change 'brand' to your desired slug
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 5, // Adjust menu position as needed
        'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ), // Add or remove supported features
        'taxonomies'         => array(), // Remove default taxonomies if needed
        'menu_icon' => 'dashicons-store',
    );

    register_post_type( 'brand', $args ); // 'brand' is the post type's identifier. Change it as needed.
}
add_action( 'init', 'create_brands_post_type', 0 );



function create_seller_requests_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'seller_requests';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id INT AUTO_INCREMENT PRIMARY KEY,
        seller_id BIGINT(20) UNSIGNED NOT NULL,
        brand_id BIGINT(20) UNSIGNED NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
add_action('after_setup_theme', 'create_seller_requests_table');


function custom_add_my_brands_menu( $items ) {
    
    $user_id = get_current_user_id();
    $user = get_userdata($user_id);
    $user_roles = $user->roles;
    if (in_array('brand_owner', $user_roles) || in_array('seller', $user_roles)) {
        // Define the new menu item
        $new_item = array( 'my-brands' => __( 'My Brands', 'user-registration' ) );
        // Find the position of 'edit-profile' and insert the new item after it
        $new_items = array();
        foreach ( $items as $key => $value ) {
            
            if ( $key != 'edit-profile' ) {
                $new_items[$key] = $value; 
            }
            if ( $key === 'dashboard' ) {
                $new_items = array_merge( $new_items, $new_item );
            }
            
        }

        return $new_items;
    }else{
        return $items;
    }
}
add_filter( 'user_registration_account_menu_items', 'custom_add_my_brands_menu' );

function custom_add_my_brands_endpoint() {
    add_rewrite_endpoint('my-brands', EP_ROOT | EP_PAGES);
    add_rewrite_endpoint('add-brand', EP_ROOT | EP_PAGES);
    add_rewrite_endpoint('edit-brand', EP_ROOT | EP_PAGES);
    
}
add_action('init', 'custom_add_my_brands_endpoint');

function custom_my_brands_page_content(){
    if (!is_user_logged_in()) {
        echo '<p>You must be logged in to view this page.</p>';
        return;
    }

    $user_id = get_current_user_id();
    $user = get_userdata($user_id);

    if (in_array('brand_owner', $user->roles) || in_array( 'seller', $user->roles)) {
        get_template_part('template-parts/my-brands');
    }else{
        echo '<p>You do not have permission to access this page.</p>';
        return;
    }

    
}
add_action('user_registration_account_my-brands_endpoint', 'custom_my_brands_page_content');

function custom_add_brand_page_content() {
    get_template_part('template-parts/add-brand');
}
add_action('user_registration_account_add-brand_endpoint', 'custom_add_brand_page_content');

function custom_edit_brand_page_content() {
    get_template_part('template-parts/edit-brand');
}
add_action('user_registration_account_edit-brand_endpoint', 'custom_edit_brand_page_content');


function enqueue_my_brands_scripts() {
    global $wp;
    if (isset($wp->request) && strpos($wp->request, 'brags-brand-network-account/my-brands') !== false) {
        wp_enqueue_script(
            'my-brands-js',
            get_stylesheet_directory_uri() . '/assets/js/my-brands.js',
            array('jquery'),
            null,
            true
        );

        wp_localize_script('my-brands-js', 'ajaxurl', admin_url('admin-ajax.php'));
        wp_localize_script('my-brands-js', 'my_brands_nonce', wp_create_nonce('my_brands_nonce'));

        wp_enqueue_style(
            'my-brands-css',
            get_stylesheet_directory_uri() . '/assets/css/my-brands.css'
        );
    }
    if (isset($wp->request) && strpos($wp->request, 'brags-brand-network-account/add-brand') !== false) {
        wp_enqueue_script(
            'add-brand-js',
            get_stylesheet_directory_uri() . '/assets/js/add-brand.js',
            array('jquery'),
            null,
            true
        );

        wp_localize_script('add-brand-js', 'ajaxurl', admin_url('admin-ajax.php'));
        wp_localize_script('add-brand-js', 'add_brand_nonce', wp_create_nonce('add_brand_nonce'));

        wp_enqueue_style(
            'add-brand-css',
            get_stylesheet_directory_uri() . '/assets/css/add-brand.css'
        );
    }
    if (isset($wp->request) && strpos($wp->request, 'brags-brand-network-account/edit-brand') !== false) {
        wp_enqueue_script(
            'edit-brand-js',
            get_stylesheet_directory_uri() . '/assets/js/edit-brand.js',
            array('jquery'),
            null,
            true
        );
    
        wp_localize_script('edit-brand-js', 'ajaxurl', admin_url('admin-ajax.php'));
        wp_localize_script('edit-brand-js', 'edit_brand_nonce', wp_create_nonce('edit_brand_nonce'));
    
        wp_enqueue_style(
            'edit-brand-css',
            get_stylesheet_directory_uri() . '/assets/css/edit-brand.css'
        );
    }
    
}
add_action('wp_enqueue_scripts', 'enqueue_my_brands_scripts');


function add_update_brand() {
    // Ensure user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(['error' => ['general' => 'You must be logged in to add or update a brand.']]);
        wp_die();
    }

    $current_user_id = get_current_user_id();
    $currentstep = sanitize_text_field($_POST['currentstep']);
    $brand_name = sanitize_text_field($_POST['brand_name']);

    // Check if the brand already exists
    $existing_brand = get_page_by_title($brand_name, OBJECT, 'brand');
    if ($existing_brand) {
        wp_send_json_error([
            'error' => ['brand_name' => 'Brand already exists.'],
            'currentstep' => $currentstep
        ]);
        wp_die();
    }

    // Proceed to next step if step is 0
    if ($currentstep == 0) {
        wp_send_json_success(['currentstep' => $currentstep + 1]);
        wp_die();
    }
    if($currentstep>0 && $currentstep<5){
        $currentstep = $currentstep + 1;
        wp_send_json_success([
            'currentstep'=>$currentstep,
        ]);
        wp_die();
    }

    if ($currentstep >= 5) {
        // Create Brand Post
        $brand_post = [
            'post_title'   => $brand_name,
            'post_status'  => 'pending', // Pending for admin approval
            'post_type'    => 'brand',
            'post_author'  => $current_user_id
        ];
        $brand_id = wp_insert_post($brand_post);

        if (is_wp_error($brand_id)) {
            wp_send_json_error(['error' => ['brand_name' => 'Brand creation failed.']]);
            wp_die();
        }

        // Save Brand Meta Fields
        $meta_fields = [
            'trademark_office', 'trademark_number', 'brand_description',
            'business_name', 'business_address', 'phone_number', 'primary_contact',
            'website_url', 'manufacturing_locations', 'distribution_channels',
            'product_categories', 'product_ids', 'sell_on_brags', 'approve_resellers',
            'brags_seller_email', 'brags_store_url', 'country'
        ];

        foreach ($meta_fields as $field) {
            if (!empty($_POST[$field])) {
                update_post_meta($brand_id, $field, sanitize_text_field($_POST[$field]));
            }
        }

        // Upload and Store Brand Images
        $upload_fields = ['brand_logo', 'additional_documents'];
        foreach ($upload_fields as $file_field) {
            if (!empty($_FILES[$file_field]['name'])) {
                $file_url = upload_user_file($_FILES[$file_field], $file_field, $current_user_id);
                update_post_meta($brand_id, $file_field, $file_url);
            }
        }

        // Upload product images separately
        for ($i = 1; $i <= 5; $i++) {
            $file_field = "product_images_$i";
            if (!empty($_FILES[$file_field]['name'])) {
                $file_url = upload_user_file($_FILES[$file_field], $file_field, $current_user_id);
                update_post_meta($brand_id, $file_field, $file_url);
            }
        }

        // Store product IDs separately
        for ($i = 1; $i <= 5; $i++) {
            $id_field = "product_ids_$i";
            if (!empty($_POST[$id_field])) {
                update_post_meta($brand_id, $id_field, sanitize_text_field($_POST[$id_field]));
            }
        }

        wp_send_json_success([
            'message' => 'Brand registered successfully!',
            'brand_id' => $brand_id,
            'redirect_url'=> site_url('brags-brand-network-account/my-brands')
        ]);
    }

    wp_die();
}

add_action('wp_ajax_add_update_brand', 'add_update_brand');



function edit_update_brand_callback() {

    // Verify nonce
    // if (!isset($_POST['edit_brand_nonce']) || !wp_verify_nonce($_POST['edit_brand_nonce'], 'edit_brand_nonce')) {
    //     wp_send_json_error(['message' => 'Security check failed.']);
    // }

    // Ensure the user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'You must be logged in to edit a brand.']);
    }

    // Check brand ID
    if (!isset($_POST['brand_id']) || empty($_POST['brand_id'])) {
        wp_send_json_error(['message' => 'Brand ID is missing.']);
    }

    
    

    $brand_id = intval($_POST['brand_id']);
    $current_user_id = get_current_user_id();
    $brand = get_post($brand_id);

    

    // Validate brand ownership
    if (!$brand || $brand->post_type !== 'brand' || $brand->post_author != $current_user_id) {
        wp_send_json_error(['message' => 'You do not have permission to edit this brand.']);
    }

    
    // List of fields to update
    $fields = [
        'brand_name', 'trademark_office', 'trademark_number', 'brand_description',
        'business_name', 'business_address', 'phone_number', 'primary_contact', 'business_email',
        'website_url', 'manufacturing_locations', 'distribution_channels', 'product_categories',
        'sell_on_brags', 'approve_resellers', 'brags_seller_email', 'brags_store_url', 'country'
    ];

    // Validate required fields
    $errors = [];
    // foreach ($fields as $field) {
    //     if (isset($_POST[$field]) && empty(trim($_POST[$field]))) {
    //         $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " is required.";
    //     }
    // }


    // If there are errors, return them
    // if (!empty($errors)) {
    //     wp_send_json_error(['message' => 'Validation failed.', 'error' => $errors]);
    // }

    // Update post title (Brand Name)
    wp_update_post([
        'ID'         => $brand_id,
        'post_title' => sanitize_text_field($_POST['brand_name']),
    ]);

    // Update meta fields
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($brand_id, $field, sanitize_text_field($_POST[$field]));
        }
    }

    // Handle file uploads
    if (!empty($_FILES)) {
        $upload_dir = wp_upload_dir();
        foreach ($_FILES as $key => $file) {
            if ($file['size'] > 0) {
                $file_type = wp_check_filetype($file['name']);
                if (in_array($file_type['ext'], ['jpg', 'jpeg', 'png', 'gif', 'pdf'])) {
                    $uploaded_file = wp_handle_upload($file, ['test_form' => false]);
                    if (!isset($uploaded_file['error'])) {
                        update_post_meta($brand_id, $key, esc_url($uploaded_file['url']));
                    }
                }
            }
        }
    }

    // Update product IDs & images
    for ($i = 1; $i <= 5; $i++) {
        if (!empty($_POST["product_id_$i"])) {
            update_post_meta($brand_id, "product_id_$i", sanitize_text_field($_POST["product_id_$i"]));
        }

        if (!empty($_FILES["product_image_$i"]) && $_FILES["product_image_$i"]['size'] > 0) {
            $file_type = wp_check_filetype($_FILES["product_image_$i"]['name']);
            if (in_array($file_type['ext'], ['jpg', 'jpeg', 'png'])) {
                $uploaded_file = wp_handle_upload($_FILES["product_image_$i"], ['test_form' => false]);
                if (!isset($uploaded_file['error'])) {
                    update_post_meta($brand_id, "product_images_$i", esc_url($uploaded_file['url']));
                }
            }
        }
    }

    // Handle additional documents
    if (!empty($_FILES['additional_documents']) && $_FILES['additional_documents']['size'] > 0) {
        $file_type = wp_check_filetype($_FILES['additional_documents']['name']);
        if ($file_type['ext'] === 'pdf') {
            $uploaded_file = wp_handle_upload($_FILES['additional_documents'], ['test_form' => false]);
            if (!isset($uploaded_file['error'])) {
                update_post_meta($brand_id, "additional_documents", esc_url($uploaded_file['url']));
            }
        }
    }

    // Success response
    wp_send_json_success(['message' => 'Brand updated successfully!']);
}
add_action('wp_ajax_edit_update_brand', 'edit_update_brand_callback');


function submit_seller_request() {
    // Verify nonce for security
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'seller_request_nonce')) {
        wp_send_json_error('Security check failed.');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'seller_requests';
    
    // Sanitize input
    $seller_id = intval($_POST['seller_id']);
    $brand_id = intval($_POST['brand_id']);

    // Check for duplicate entry
    $existing_request = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE seller_id = %d AND brand_id = %d",
        $seller_id,
        $brand_id
    ));

    if ($existing_request > 0) {
        wp_send_json_error('This seller is already associated with the selected brand.');
    }

    // Insert into database
    $inserted = $wpdb->insert(
        $table_name,
        array(
            'seller_id' => $seller_id,
            'brand_id'  => $brand_id,
            'status'    => 'approved',//pending
            'created_at' => current_time('mysql'),
        ),
        array('%d', '%d', '%s', '%s')
    );

    if ($inserted) {
        wp_send_json_success('Seller request submitted successfully.');
    } else {
        wp_send_json_error('Failed to submit request.');
    }
}

// Register AJAX handlers
add_action('wp_ajax_submit_seller_request', 'submit_seller_request'); // For logged-in users
add_action('wp_ajax_nopriv_submit_seller_request', 'submit_seller_request'); // For guests (if needed)


function manage_seller_request() {
    check_ajax_referer('seller_request_nonce', 'security'); // Security check

    global $wpdb;
    $table_name = $wpdb->prefix . 'seller_requests';

    $request_id = intval($_POST['request_id']);
    $request_action = sanitize_text_field($_POST['request_action']);

    if (!$request_id || !in_array($request_action, ['approve', 'reject', 'delete'])) {
        wp_send_json_error('Invalid request.');
    }

    $current_request = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $request_id));

    if ($request_action === 'delete') {
        do_action('before_seller_status_change', $request_id, 'delete', $current_request);
        $wpdb->delete($table_name, ['id' => $request_id]);
        do_action('after_seller_status_change', $request_id, 'deleted', $current_request);
        wp_send_json_success('Deleted successfully');
    } else {
        $new_status = ($request_action === 'approve') ? 'approved' : 'rejected';
        do_action('before_seller_status_change', $request_id, $new_status, $current_request);
        $wpdb->update($table_name, ['status' => $new_status], ['id' => $request_id]);
        // Fetch updated data
        $updated_request = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $request_id));

        do_action('after_seller_status_change', $request_id, $new_status, $updated_request);
        wp_send_json_success(['status' => ucfirst($new_status)]);
    }

    wp_die();
}
add_action('wp_ajax_manage_seller_request', 'manage_seller_request');



function notify_brand_delet_($request_id, $new_status, $request_data) {
    $taxonomy = 'product_brand';

    // Get brand name associated with the seller request
    $brand_name = get_user_meta($request_data->seller_id, 'brand_name', true);

    if (!$brand_name) {
        return; // No brand name found, exit early
    }

    if ($new_status === 'approved') {
        // Create taxonomy if it doesn't exist
        if (!term_exists($brand_name, $taxonomy)) {
            wp_insert_term($brand_name, $taxonomy);
        }
    } elseif ($new_status === 'rejected') {
        // Delete taxonomy if it exists
        $term = get_term_by('name', $brand_name, $taxonomy);
        if ($term) {
            wp_delete_term($term->term_id, $taxonomy);
        }
    }
}





function handle_create_brand_post($user_id, $new_status) {
    if ($new_status === 'pending') {
        // Example: Auto-create a "Brand" post in pending status
        $brand_name = get_user_meta($user_id, 'brand_name', true);
        
        if (!empty($brand_name)) {
            $existing_brand = get_page_by_title($brand_name, OBJECT, 'brand');
            if (!$existing_brand) {
                $brand_data = array(
                    'post_title'   => sanitize_text_field($brand_name),
                    'post_content' => 'Brand created for user ID: ' . $user_id,
                    'post_status'  => 'pending',
                    'post_type'    => 'brand',
                    'post_author'  => $user_id,
                );
                wp_insert_post($brand_data);
            }
        }
    }
}
add_action('create_brand_post', 'handle_create_brand_post', 10, 2);

function manage_brand_taxonomy($post_id, $post, $update) {
    // Ensure this runs only for the "brand" post type
    if ($post->post_type !== 'brand') {
        return;
    }

    $brand_name = get_the_title($post_id); // Get brand name from post title
    $taxonomy = 'product_brand';

    // Ensure brand name is not empty
    if (empty($brand_name)) {
        return;
    }

    // Check if post status is being changed
    if ($update) {
        $old_status = get_post_meta($post_id, '_previous_status', true);
        $new_status = $post->post_status;

        // If status changes to "approved", create taxonomy term
        if ($new_status === 'publish' && $old_status !== 'publish') {
            if (!term_exists($brand_name, $taxonomy)) {
                wp_insert_term($brand_name, $taxonomy);
            }
        }

        // If status changes to "rejected" or post is deleted, remove taxonomy term
        if (($new_status === 'trash' || $new_status === 'draft') && term_exists($brand_name, $taxonomy)) {
            $term = get_term_by('name', $brand_name, $taxonomy);
            if ($term) {
                wp_delete_term($term->term_id, $taxonomy);
            }
        }
    }

    // Save current status for future checks
    update_post_meta($post_id, '_previous_status', $post->post_status);
    $term = get_term_by('name', $brand_name, $taxonomy);
    
    if($term){
        
        update_post_meta($post_id, '_brand_term_id', $term->term_id);
    }
    
}
add_action('save_post', 'manage_brand_taxonomy', 10, 3);



function add_meta_to_duplicated_product($duplicate, $product) {
    // Add a custom meta key to mark the product as copied
    $duplicate->update_meta_data('_is_copied_product', 'yes');
    $duplicate->set_catalog_visibility('hidden');
}
add_action('woocommerce_product_duplicate_before_save', 'add_meta_to_duplicated_product', 10, 2);







