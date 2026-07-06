<?php

// Disable tax options for sellers without VAT Number
//add_action('woocommerce_product_options_tax', 'brags_restrict_tax_options_if_no_vat');

function brags_restrict_tax_options_if_no_vat() {
    if ( ! current_user_can( 'dokandar' ) ) return;

    $user_id = get_current_user_id();
    $vat_number = get_user_meta( $user_id, 'dokan_vat_number', true ); // Adjust key if needed

    if ( empty( $vat_number ) ) {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#_tax_status, #_tax_class').val('none').prop('disabled', true);
        });
        </script>
        <p class="form-field">
            <span class="description" style="color: #a00;">
                You must enter your VAT Number in <a href="<?php echo esc_url( dokan_get_navigation_url( 'settings/store' ) ); ?>" target="_blank">Store Settings</a> before you can charge VAT or change tax class.
            </span>
        </p>
        <?php
    }
}

// Enforce 'none' as tax status when saving if no VAT Number
//add_action('woocommerce_process_product_meta', 'brags_force_tax_none_without_vat');

function brags_force_tax_none_without_vat($post_id) {
    $author_id = get_post_field( 'post_author', $post_id );
    $vat_number = '';//get_user_meta( $author_id, 'dokan_vat_number', true ); // Adjust if different

    if (  $vat_number =='' ) {
        update_post_meta( $post_id, '_tax_status', 'none' );
        update_post_meta( $post_id, '_tax_class', '' );
    }
}

add_action( 'dokan_order_content_inside_before', 'brags_custom_seller_order_notice' );

function brags_custom_seller_order_notice() {
    ?>
    <div class="" style="margin-bottom: 20px; border-left: 4px solid #f0ad4e; padding: 15px; background: #fff3cd; color: #856404;">
        <!-- <h4>Important Information for Sellers</h4> -->
        <span><strong>As a Seller on Brags.co.uk, you are entirely responsible for compliance and setting the appropriate VAT rates for your products, as well as providing your own Invoices to customers.</strong></span>
        <span>Providing invoices for customers on Brags.co.uk is <strong>a requirement</strong>, and you have <strong>7 days from the order date</strong> to do so.</span>
        <span>You can choose to <strong>“Upload” your own Invoice</strong>, or if you have included your VAT number in your store settings, you can <strong>“Automate” invoices</strong> based on the VAT rate you set when listing your products.</>
        <span><strong> If you are not VAT registered, you must not charge VAT</strong> on your goods, and you should clearly state this on the invoice.</>
        <span>Please seek financial advice from a qualified professional if you are unsure about VAT on your goods and how invoices should be presented to customers.</span>
        <span><strong> To charge VAT and automate invoices, please include your VAT number in your <a href="<?php echo esc_url( dokan_get_navigation_url( 'settings/store' ) ); ?>">Store Settings</a>.</strong></span>
    </div>
    <?php
}


// ------------------------------------------------------------

// Add VAT rate field in Dokan product edit form
// function add_dokan_vat_field( $product_id) {
//     if ( isset( $_POST['_seller_vat_rate'] ) ) {
//         update_post_meta( $product_id, '_seller_vat_rate', sanitize_text_field( $_POST['_seller_vat_rate'] ) );
//     }
// }
// add_action( 'dokan_process_product_meta', 'add_dokan_vat_field');

function add_dokan_vat_field( $product_id ) {
    $seller_id = get_current_user_id();
    $vat_number = get_user_meta( $seller_id, 'dokan_vat_number', true );

    // Only process if VAT number is present
    if ( $vat_number !='' ) {

        // Save VAT Rate Selection
        if ( isset( $_POST['seller_vat_rate'] ) ) {
            update_post_meta( $product_id, '_seller_vat_rate', sanitize_text_field( $_POST['seller_vat_rate'] ) );
        }

        // Save Custom VAT Rate (if selected)
        if ( isset( $_POST['seller_vat_rate'] ) && $_POST['seller_vat_rate'] === 'custom' && isset( $_POST['seller_custom_vat_rate'] ) ) {
            update_post_meta( $product_id, '_seller_custom_vat_rate', floatval( $_POST['seller_custom_vat_rate'] ) );
        } else {
            delete_post_meta( $product_id, '_seller_custom_vat_rate' );
        }

        // Save Confirmation Checkbox
        $confirmation = isset( $_POST['seller_vat_confirmed'] ) ? '1' : '0';
        update_post_meta( $product_id, '_seller_vat_confirmed', $confirmation );

    }
}
add_action( 'dokan_process_product_meta', 'add_dokan_vat_field' );


// Display VAT rate field in product edit form
function display_dokan_vat_field() {
    global $post;
    $vat_rate = get_post_meta( $post->ID, '_seller_vat_rate', true );
    ?>
    <div class="dokan-form-group">
        <label for="seller_vat_rate"><?php _e( 'VAT Rate (%)', 'dokan' ); ?></label>
        <input type="number" name="_seller_vat_rate" id="seller_vat_rate" class="dokan-form-control" value="<?php echo esc_attr( $vat_rate ); ?>" step="0.01">
    </div>

    <?php
    
}
//add_action( 'dokan_product_edit_after_pricing', 'display_dokan_vat_field' );


// ------------------------------------------------------------
function apply_seller_vat_rate( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;

    foreach ( $cart->get_cart() as $cart_item ) {
        $product_id = $cart_item['product_id'];
        $vat_rate = get_post_meta( $product_id, '_seller_vat_rate', true );

        if ( ! empty( $vat_rate ) ) {
            // Calculate tax based on WooCommerce tax rules
            $tax_amount = ( $cart_item['line_total'] * $vat_rate ) / 100;
            $cart_item['line_tax'] = $tax_amount;

            // Add VAT to WooCommerce tax system
            $cart_item['line_subtotal_tax'] = $tax_amount;
            $cart_item['line_total_tax'] = $tax_amount;
        }
    }
}
add_action( 'woocommerce_before_calculate_totals', 'apply_seller_vat_rate' );
function add_seller_vat_fee() {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;

    $cart = WC()->cart;
    $vat_total = 0;

    foreach ( $cart->get_cart() as $cart_item ) {
        $product_id = $cart_item['product_id'];
        $vat_rate = get_post_meta( $product_id, '_seller_vat_rate', true );

        if ( ! empty( $vat_rate ) ) {
            $vat_total += ( $cart_item['line_total'] * $vat_rate ) / 100;
        }
    }

    if ( $vat_total > 0 ) {
        $cart->add_fee( __( 'VAT', 'woocommerce' ), $vat_total, true ); // Add VAT as a fee
    }
}
add_action( 'woocommerce_cart_calculate_fees', 'add_seller_vat_fee' );
// function debug_cart_vat() {
//     $cart = WC()->cart;
//     error_log( print_r( $cart->get_totals(), true ) );
// }
// add_action( 'woocommerce_cart_calculate_fees', 'debug_cart_vat' );




// ------------------------------------------------------------------------

function vat_invoices_add_my_account_endpoint() {
    add_rewrite_endpoint('vat_invoices', EP_ROOT | EP_PAGES);
}
add_action('init', 'vat_invoices_add_my_account_endpoint');

function add_vat_invoice_tab( $items ) {
    $user = wp_get_current_user();
    if (in_array('customer', (array) $user->roles) ) {
        $items['vat_invoices'] = __( 'Invoices', 'dokan-lite' );
    }

    return $items;
}
add_filter( 'woocommerce_account_menu_items', 'add_vat_invoice_tab' );

function vat_invoice_content() {
    $user_id = get_current_user_id();
    $orders = wc_get_orders(array('customer' => $user_id));

    echo '<h2>Your VAT Invoices</h2>';

    if (empty($orders)) {
        echo '<p>No VAT invoices available.</p>';
        return;
    }

    echo '<div class="woocommerce vat-invoices-list">';
    foreach ($orders as $order) {
        $_vat_invoice = get_post_meta($order->get_id(), '_vat_invoice', true);

        if (!empty($_vat_invoice)) {
            $invoice_url = wp_upload_dir()['baseurl'] . '/' . basename($_vat_invoice);
            $order_date = $order->get_date_created()->format('F j, Y');
            $order_total = $order->get_total();

            echo '<div class="vat-invoice-card">';
            echo '  <div class="vat-invoice-header">';
            echo '    <h3>Order #'. esc_html($order->get_id()) .'</h3>';
            echo '    <p class="order-date">'. esc_html($order_date) .'</p>';
            echo '  </div>';
            echo '  <div class="vat-invoice-body">';
            echo '    <p><strong>Total:</strong> £'. number_format($order_total, 2) .'</p>';
            echo '    <a href="' . esc_url($invoice_url) . '" class="vat-invoice-btn" target="_blank">Download Invoice</a>';
            echo '  </div>';
            echo '</div>';
        }
    }
    echo '</div>';

    echo '<style>
        .vat-invoices-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .vat-invoice-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background: #fff;
            box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.05);
        }
        .vat-invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        .vat-invoice-body p {
            margin: 5px 0;
            font-size: 14px;
        }
        .vat-invoice-btn {
            display: inline-block;
            padding: 8px 12px;
            background: #0073aa;
            color: #fff !important;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
        }
        .vat-invoice-btn:hover {
            background: #005177;
            color: #fff !important;
        }
    </style>';
}
add_action('woocommerce_account_vat_invoices_endpoint', 'vat_invoice_content');




// ----------------------Email VAT Invoice to Customer----------------------------
// function send_vat_invoice_email( $order_id ) {
//     $order = wc_get_order( $order_id );
//     $email = $order->get_billing_email();
//     $invoice_url = get_post_meta( $order_id, '_vat_invoice_url', true );

//     $subject = 'Your VAT Invoice for Order #' . $order_id;
//     $message = 'Thank you for your order. You can download your VAT invoice here: ' . $invoice_url;
//     $email = 'kirtan.fableadtechnolabs@gmail.com';
//     wp_mail( $email, $subject, $message );
// }
// add_action( 'woocommerce_order_status_completed', 'send_vat_invoice_email' );

function send_vat_invoice_email_new( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return;
    }
    $invoice_url = '';
    $invoice = get_post_meta($order_id, '_vat_invoice', true);
    if($invoice!=''){
        $invoice_url  = wp_upload_dir()['baseurl'] . '/' . basename($invoice);
    }
    if ($invoice_url=='') {
        return;
    }

    // Get billing email (or override for testing)
    //$email_to = 'kirtan.fableadtechnolabs@gmail.com'; // for testing
    $email_to = $order->get_billing_email(); // for production

    $subject = sprintf( __( 'Your VAT Invoice for Order #%s', 'dokan' ), $order->get_order_number() );

    // Load WooCommerce email classes
    $mailer = WC()->mailer();
    $email_heading = __( 'Your VAT Invoice', 'dokan' );

    ob_start();
    ?>
    <p><?php esc_html_e( 'Thank you for your purchase.', 'dokan' ); ?></p>
    <p><?php esc_html_e( 'You can download your VAT invoice using the button below:', 'dokan' ); ?></p>
    <p>
        <!-- <a href="<?php //echo esc_url( $invoice_url ); ?>" target="_blank"
           style="display: inline-block; padding: 10px 20px; background: #0071a1; color: #fff; text-decoration: none; border-radius: 4px;">
            <?php //esc_html_e( 'Download VAT Invoice', 'dokan' ); ?>
        </a> -->

        <a href="<?php echo esc_url( $invoice_url ); ?>" target="_blank"
        style="">
            <?php esc_html_e( 'Download VAT Invoice', 'dokan' ); ?>
        </a>
    </p>
    
    <?php
    $body_content = ob_get_clean();

    // Wrap content using WooCommerce email template
    $message = $mailer->wrap_message( $email_heading, $body_content );

    // Get headers
    $headers = array( 'Content-Type: text/html; charset=UTF-8' );

    // Send email
    wp_mail( $email_to, $subject, $message, $headers );
}
add_action( 'woocommerce_order_status_completed', 'send_vat_invoice_email_new' );




// ------------------------------------------------------------------

function add_vat_invoices_menu( $menus ) {

    $menus['seller-vat-invoices'] = array(
        'title'      => __( 'VAT Invoices', 'dokan' ),
        'icon'       => '<i class="dashicons dashicons-media-document"></i>',
        'url'        => dokan_get_navigation_url( 'seller-vat-invoices' ),
        'position'   => 55,
        //'permission' => 'dokan_view_sales'
    );

    return $menus;
}
//add_filter( 'dokan_get_dashboard_nav', 'add_vat_invoices_menu' );

// 2. Register the custom query variable.
function register_dokan_vat_invoices_query_vars( $query_vars ) {
    $query_vars[] = 'seller-vat-invoices';
    return $query_vars;
}
add_filter( 'query_vars', 'register_dokan_vat_invoices_query_vars' );
// 3. Load the custom template.
function load_dokan_vat_invoices_template() {
    global $wp_query;

    if ( isset( $wp_query->query_vars['seller-vat-invoices'] ) ) {

         $template_path = get_stylesheet_directory() . '/template-parts/dokan/seller-vat-invoices.php';

        if ( file_exists( $template_path ) ) {
            include $template_path;
            exit;
        } else {
            wp_die( 'invoice template not found.' );
        }
    }
}
add_action( 'template_redirect', 'load_dokan_vat_invoices_template' );
// 4. Flush rewrite rules and add rewrite endpoint.
function _vat_invoices_flush_rules() {
    add_rewrite_endpoint( 'seller-vat-invoices', EP_ROOT | EP_PAGES );
    flush_rewrite_rules();
}
add_action( 'init', '_vat_invoices_flush_rules' );


function get_seller_orders( $seller_id ) {
    $args = array(
        'post_type'      => 'shop_order',
        'post_status'    => array( 'wc-completed', 'wc-processing' ), // Get completed & processing orders
        'posts_per_page' => -1, // Get all orders
        'meta_query'     => array(
            array(
                'key'     => '_dokan_vendor_id',
                'value'   => $seller_id,
                'compare' => '=',
            ),
        ),
    );

    $orders = get_posts( $args );
    return $orders;
}





