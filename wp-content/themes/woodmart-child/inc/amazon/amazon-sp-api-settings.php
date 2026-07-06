<?php
/**
 * Amazon SP-API Admin Settings
 * Stores LWA credentials for the Brags SP-API application
 * Path: inc/amazon/amazon-sp-api-settings.php
 */

// Add admin menu for Amazon SP-API settings
add_action('admin_menu', function () {
    add_submenu_page(
        'wpla',
        __('Amazon SP-API Settings', 'brags'),
        __('Amazon SP-API', 'brags'),
        'manage_options',
        'brags-amazon-sp-api',
        'brags_render_sp_api_settings_page'
    );
}, 999);

/**
 * Register settings
 */
add_action('admin_init', function () {
    register_setting('brags_amazon_sp_api', 'brags_amazon_sp_api_app_id');
    register_setting('brags_amazon_sp_api', 'brags_amazon_sp_api_client_id');
    register_setting('brags_amazon_sp_api', 'brags_amazon_sp_api_client_secret', [
        'sanitize_callback' => 'brags_encrypt_sp_api_secret'
    ]);
    register_setting('brags_amazon_sp_api', 'brags_amazon_sp_api_beta_mode');
});

/**
 * Encrypt the client secret before saving
 */
function brags_encrypt_sp_api_secret($value) {
    if (empty($value)) {
        return $value;
    }
    
    // If the value starts with 'amzn1.oa2-cs', it's a new unencrypted value
    if (strpos($value, 'amzn1.oa2-cs') === 0) {
        return brags_sp_api_encrypt($value);
    }
    
    // Already encrypted, return as-is
    return $value;
}

/**
 * Simple encryption using WordPress auth keys
 */
function brags_sp_api_encrypt($data) {
    if (empty($data)) return $data;
    
    $key = wp_salt('auth');
    $iv = substr(hash('sha256', wp_salt('secure_auth')), 0, 16);
    
    $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
    return base64_encode($encrypted);
}

/**
 * Decrypt the client secret
 */
function brags_sp_api_decrypt($data) {
    if (empty($data)) return $data;
    
    $key = wp_salt('auth');
    $iv = substr(hash('sha256', wp_salt('secure_auth')), 0, 16);
    
    $decrypted = openssl_decrypt(base64_decode($data), 'AES-256-CBC', $key, 0, $iv);
    return $decrypted;
}

/**
 * Get SP-API credentials helper function
 */
function brags_get_sp_api_credentials() {
    return [
        'app_id' => get_option('brags_amazon_sp_api_app_id', ''),
        'client_id' => get_option('brags_amazon_sp_api_client_id', ''),
        'client_secret' => brags_sp_api_decrypt(get_option('brags_amazon_sp_api_client_secret', '')),
    ];
}

/**
 * Check if SP-API is configured
 */
function brags_is_sp_api_configured() {
    $creds = brags_get_sp_api_credentials();
    return !empty($creds['app_id']) && !empty($creds['client_id']) && !empty($creds['client_secret']);
}

/**
 * Get the OAuth authorization URL for vendors
 */
function brags_get_sp_api_oauth_url() {
    $creds = brags_get_sp_api_credentials();
    
    if (empty($creds['app_id'])) {
        return false;
    }
    
    $state = wp_create_nonce('brags_sp_api_oauth');
    $redirect_uri = home_url('/amazon-callback/');
    
    // Amazon UK Seller Central OAuth URL
    $oauth_url = add_query_arg([
        'application_id' => $creds['app_id'],
        'state' => $state,
        'redirect_uri' => urlencode($redirect_uri),
    ], 'https://sellercentral.amazon.co.uk/apps/authorize/consent');

    // Add version=beta if beta mode is enabled
    if (get_option('brags_amazon_sp_api_beta_mode')) {
        $oauth_url = add_query_arg('version', 'beta', $oauth_url);
    }
    
    return $oauth_url;
}

/**
 * Render the settings page
 */
function brags_render_sp_api_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $app_id = get_option('brags_amazon_sp_api_app_id', '');
    $client_id = get_option('brags_amazon_sp_api_client_id', '');
    $client_secret_encrypted = get_option('brags_amazon_sp_api_client_secret', '');
    $is_configured = brags_is_sp_api_configured();
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <?php if ($is_configured): ?>
        <div class="notice notice-success">
            <p><strong>✅ SP-API Credentials Configured</strong> - Vendors can now connect their Amazon accounts.</p>
        </div>
        <?php else: ?>
        <div class="notice notice-warning">
            <p><strong>⚠️ SP-API Not Configured</strong> - Enter your Amazon SP-API application credentials below.</p>
        </div>
        <?php endif; ?>
        
        <form method="post" action="options.php">
            <?php settings_fields('brags_amazon_sp_api'); ?>
            
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="brags_amazon_sp_api_app_id">SP-API App ID</label>
                    </th>
                    <td>
                        <input type="text" id="brags_amazon_sp_api_app_id" 
                               name="brags_amazon_sp_api_app_id" 
                               value="<?php echo esc_attr($app_id); ?>" 
                               class="regular-text"
                               placeholder="amzn1.sp.solution.xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" />
                        <p class="description">Your Amazon SP-API Application ID</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="brags_amazon_sp_api_client_id">LWA Client ID</label>
                    </th>
                    <td>
                        <input type="text" id="brags_amazon_sp_api_client_id" 
                               name="brags_amazon_sp_api_client_id" 
                               value="<?php echo esc_attr($client_id); ?>" 
                               class="regular-text"
                               placeholder="amzn1.application-oa2-client.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" />
                        <p class="description">Login with Amazon (LWA) Client ID</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="brags_amazon_sp_api_client_secret">LWA Client Secret</label>
                    </th>
                    <td>
                        <input type="password" id="brags_amazon_sp_api_client_secret" 
                               name="brags_amazon_sp_api_client_secret" 
                               value="" 
                               class="regular-text"
                               placeholder="<?php echo $client_secret_encrypted ? '••••••••••••••••••••' : 'amzn1.oa2-cs.v1.xxxxx...'; ?>" />
                        <p class="description">
                            <?php if ($client_secret_encrypted): ?>
                                <span style="color: green;">✓ Secret is stored (encrypted). Leave blank to keep current value.</span>
                            <?php else: ?>
                                Login with Amazon (LWA) Client Secret - This will be encrypted before storage.
                            <?php endif; ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="brags_amazon_sp_api_beta_mode">Beta Mode</label>
                    </th>
                    <td>
                        <input type="checkbox" id="brags_amazon_sp_api_beta_mode" 
                               name="brags_amazon_sp_api_beta_mode" 
                               value="1" 
                               <?php checked(1, get_option('brags_amazon_sp_api_beta_mode'), true); ?> />
                        <p class="description">Enable Beta Mode if your application is still in "Draft" status in Amazon Seller Central.</p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('Save Credentials'); ?>
        </form>
        
        <hr>
        
        <h2>Vendor Authorization</h2>
        <?php if ($is_configured): ?>
        <p>Once credentials are saved, vendors can authorize their Amazon accounts from their dashboard at:</p>
        <p><code><?php echo esc_url(dokan_get_navigation_url('settings/amazon-mcf')); ?></code></p>
        
        <h3>OAuth Authorization URL</h3>
        <p>Vendors should use this URL to authorize your app in their Seller Central:</p>
        <p><code><?php echo esc_url(brags_get_sp_api_oauth_url()); ?></code></p>
        <?php else: ?>
        <p class="description">Save your SP-API credentials above to enable vendor authorization.</p>
        <?php endif; ?>
        
        <hr>
        
        <h2>Setup Instructions</h2>
        <ol>
            <li>Register as an Amazon Developer at <a href="https://developer.amazonservices.co.uk/" target="_blank">developer.amazonservices.co.uk</a></li>
            <li>Create an SP-API application in Amazon Seller Central → Partner Network → Develop Apps</li>
            <li>Note down your App ID and LWA credentials</li>
            <li>Set the OAuth Redirect URI to: <code><?php echo esc_url(home_url('/amazon-callback/')); ?></code></li>
            <li>Enter the credentials above and save</li>
        </ol>
    </div>
    <?php
}

// Handle saving - preserve existing secret if new one is empty
add_filter('pre_update_option_brags_amazon_sp_api_client_secret', function($new_value, $old_value) {
    if (empty($new_value) && !empty($old_value)) {
        return $old_value; // Keep existing encrypted value
    }
    return $new_value;
}, 10, 2);
