<?php
/**
 * Customer Authentication API Endpoints
 * 
 * Provides endpoints for registration, login, forgot password, and change password.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Hook to register the custom REST API routes
add_action('rest_api_init', 'brags_register_customer_auth_routes');

function brags_register_customer_auth_routes() {
    $namespace = 'brags/v1/customer';

    // 1. Register Endpoint
    register_rest_route($namespace, '/register', [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'brags_customer_register_callback',
        'permission_callback' => '__return_true',
    ]);

    // 2. Login Endpoint
    register_rest_route($namespace, '/login', [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'brags_customer_login_callback',
        'permission_callback' => '__return_true',
    ]);

    // 3. Forgot Password Endpoint
    register_rest_route($namespace, '/forgot-password', [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'brags_customer_forgot_password_callback',
        'permission_callback' => '__return_true',
    ]);

    // 4. Change Password Endpoint
    register_rest_route($namespace, '/change-password', [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'brags_customer_change_password_callback',
        'permission_callback' => '__return_true', // Authentication and validation handled inside callback
    ]);
}

/**
 * Register Callback
 */
function brags_customer_register_callback(WP_REST_Request $request) {
    $params = $request->get_json_params();
    if (empty($params)) {
        $params = $request->get_body_params();
    }

    $first_name   = isset($params['first_name']) ? sanitize_text_field($params['first_name']) : '';
    $last_name    = isset($params['last_name']) ? sanitize_text_field($params['last_name']) : '';
    $email        = isset($params['email']) ? sanitize_email($params['email']) : '';
    $password     = isset($params['password']) ? $params['password'] : '';
    $account_type = isset($params['account_type']) ? sanitize_text_field($params['account_type']) : '';

    // Validation: Missing fields
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($account_type)) {
        return new WP_REST_Response([
            'status'  => 'error',
            'code'    => 'missing_fields',
            'message' => 'First name, last name, email, password, and account type are required.',
        ], 400);
    }

    // Validation: Invalid email format
    if (!is_email($email)) {
        return new WP_REST_Response([
            'status'  => 'error',
            'code'    => 'invalid_email',
            'message' => 'Please provide a valid email address.',
        ], 400);
    }

    // Validation: Invalid account type
    $allowed_account_types = ['individual', 'business'];
    if (!in_array(strtolower($account_type), $allowed_account_types, true)) {
        return new WP_REST_Response([
            'status'  => 'error',
            'code'    => 'invalid_account_type',
            'message' => 'Account type must be either "individual" or "business".',
        ], 400);
    }

    // Validation: Email already exists
    if (email_exists($email)) {
        return new WP_REST_Response([
            'status'  => 'error',
            'code'    => 'email_exists',
            'message' => 'This email address is already registered.',
        ], 409);
    }

    // Create user using email as the username
    $username = $email;
    $user_id = wp_create_user($username, $password, $email);

    if (is_wp_error($user_id)) {
        return new WP_REST_Response([
            'status'  => 'error',
            'code'    => 'registration_failed',
            'message' => $user_id->get_error_message(),
        ], 500);
    }

    // Update first name, last name, and account type meta
    wp_update_user([
        'ID'         => $user_id,
        'first_name' => $first_name,
        'last_name'  => $last_name,
        'display_name'=> $first_name . ' ' . $last_name,
        'role'       => 'customer', // Assign customer role
    ]);

    update_user_meta($user_id, 'account_type', strtolower($account_type));

    // Optional: Log the user in or return success response with user details
    return new WP_REST_Response([
        'status'  => 'success',
        'message' => 'Customer registered successfully.',
        'data'    => [
            'user_id'      => $user_id,
            'first_name'   => $first_name,
            'last_name'    => $last_name,
            'email'        => $email,
            'account_type' => $account_type,
        ]
    ], 201);
}

/**
 * Login Callback
 */
function brags_customer_login_callback(WP_REST_Request $request) {
    $params = $request->get_json_params();
    if (empty($params)) {
        $params = $request->get_body_params();
    }

    $username = isset($params['username']) ? sanitize_text_field($params['username']) : '';
    if (empty($username) && isset($params['email'])) {
        $username = sanitize_text_field($params['email']);
    }
    $password = isset($params['password']) ? $params['password'] : '';

    // Validation: Missing fields
    if (empty($username) || empty($password)) {
        return new WP_REST_Response([
            'status'  => 'error',
            'code'    => 'missing_credentials',
            'message' => 'Username/email and password are required.',
        ], 400);
    }

    // Authenticate user
    $user = wp_authenticate($username, $password);

    if (is_wp_error($user)) {
        return new WP_REST_Response([
            'status'  => 'error',
            'code'    => 'invalid_credentials',
            'message' => 'Invalid username/email or password.',
        ], 401);
    }

    // Verify user role
    $roles = (array) $user->roles;
    // Check if customer or administrator (for testing convenience)
    if (!in_array('customer', $roles, true) && !in_array('administrator', $roles, true)) {
        return new WP_REST_Response([
            'status'  => 'error',
            'code'    => 'unauthorized_role',
            'message' => 'Access denied. Only customers can log in here.',
        ], 403);
    }

    $account_type = get_user_meta($user->ID, 'account_type', true) ?: 'individual';

    // Successful login - return user information
    return new WP_REST_Response([
        'status'  => 'success',
        'message' => 'Login successful.',
        'data'    => [
            'user_id'      => $user->ID,
            'username'     => $user->user_login,
            'email'        => $user->user_email,
            'first_name'   => $user->first_name,
            'last_name'    => $user->last_name,
            'display_name' => $user->display_name,
            'roles'        => $roles,
            'account_type' => $account_type,
        ]
    ], 200);
}

/**
 * Forgot Password Callback
 */
function brags_customer_forgot_password_callback(WP_REST_Request $request) {
    $params = $request->get_json_params();
    if (empty($params)) {
        $params = $request->get_body_params();
    }

    $email_or_username = isset($params['email']) ? sanitize_text_field($params['email']) : '';
    if (empty($email_or_username) && isset($params['username'])) {
        $email_or_username = sanitize_text_field($params['username']);
    }

    // Validation: Missing field
    if (empty($email_or_username)) {
        return new WP_REST_Response([
            'status'  => 'error',
            'code'    => 'missing_email',
            'message' => 'Email address or username is required.',
        ], 400);
    }

    // Find user by email or username
    $user = get_user_by('email', $email_or_username);
    if (!$user) {
        $user = get_user_by('login', $email_or_username);
    }

    // Validation: User not found
    if (!$user) {
        return new WP_REST_Response([
            'status'  => 'error',
            'code'    => 'user_not_found',
            'message' => 'No user found with that email or username.',
        ], 404);
    }

    // Check role to ensure it is a customer or admin
    $roles = (array) $user->roles;
    if (!in_array('customer', $roles, true) && !in_array('administrator', $roles, true)) {
        return new WP_REST_Response([
            'status'  => 'error',
            'code'    => 'unauthorized_user',
            'message' => 'Access denied for this user type.',
        ], 403);
    }

    // Send reset password email using native WordPress function
    // retrieve_password uses the user_login
    $result = retrieve_password($user->user_login);

    if (is_wp_error($result)) {
        return new WP_REST_Response([
            'status'  => 'error',
            'code'    => 'email_send_failed',
            'message' => $result->get_error_message(),
        ], 500);
    }

    return new WP_REST_Response([
        'status'  => 'success',
        'message' => 'Password reset link has been sent to your email.',
    ], 200);
}

/**
 * Change Password Callback
 */
function brags_customer_change_password_callback(WP_REST_Request $request) {
    $params = $request->get_json_params();
    if (empty($params)) {
        $params = $request->get_body_params();
    }

    $current_password = isset($params['current_password']) ? $params['current_password'] : '';
    $new_password     = isset($params['new_password']) ? $params['new_password'] : '';
    
    // Resolve user ID: Check if user is authenticated (via session/cookie) or if user_id is explicitly passed.
    $user_id = get_current_user_id();
    if (!$user_id && isset($params['user_id'])) {
        $user_id = intval($params['user_id']);
    }

    // Validation: Missing fields
    if (empty($user_id) || empty($current_password) || empty($new_password)) {
        return new WP_REST_Response([
            'status'  => 'error',
            'code'    => 'missing_fields',
            'message' => 'User identity, current password, and new password are required.',
        ], 400);
    }

    // Get user object
    $user = get_user_by('id', $user_id);
    if (!$user) {
        return new WP_REST_Response([
            'status'  => 'error',
            'code'    => 'user_not_found',
            'message' => 'User not found.',
        ], 404);
    }

    // Verify current password
    if (!wp_check_password($current_password, $user->user_pass, $user->ID)) {
        return new WP_REST_Response([
            'status'  => 'error',
            'code'    => 'incorrect_password',
            'message' => 'The current password you entered is incorrect.',
        ], 401);
    }

    // Update password
    wp_set_password($new_password, $user->ID);

    return new WP_REST_Response([
        'status'  => 'success',
        'message' => 'Password changed successfully.',
    ], 200);
}
