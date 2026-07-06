<?php
/*
Plugin Name: Dompdf
Description: Generates VAT invoices as PDFs using Dompdf.
Version: 1.0
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Load Composer dependencies
require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;
function test_generate_pdf() {
    $options = new Options();
    $options->set('defaultFont', 'Arial');

    $dompdf = new Dompdf($options);
    $html = '<h1>Test PDF</h1><p>This is a test invoice.</p>';

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $pdf_output = $dompdf->output();
    file_put_contents(WP_CONTENT_DIR . "/uploads/test_invoice.pdf", $pdf_output);

    echo "PDF Generated! Check wp-content/uploads/test_invoice.pdf";
}

// add_action('admin_menu', function() {
//     add_menu_page('Test PDF', 'Test PDF', 'manage_options', 'test-pdf', 'test_generate_pdf');
// });

add_action('wp_enqueue_scripts', 'my_plugin_enqueue_scripts');

function my_plugin_enqueue_scripts() {
    if (is_account_page()) { // Only on My Account page
        wp_enqueue_script(
            'request-invoice-js',
            plugin_dir_url(__FILE__) . 'assets/js/request-invoice.js',
            ['jquery'],
            null,
            true
        );

        wp_localize_script('request-invoice-js', 'requestInvoice', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('request_invoice_nonce')
        ]);
    }

    wp_enqueue_script('automate-invoice-script', plugin_dir_url(__FILE__) . 'assets/js/automate-invoice.js', ['jquery'], null, true);
    wp_localize_script('automate-invoice-script', 'automate_invoice_obj', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('automate_invoice_nonce')
    ]);


    wp_enqueue_script('upload-invoice-script', plugin_dir_url(__FILE__) . 'assets/js/upload-invoice.js', ['jquery'], null, true);
    wp_localize_script('upload-invoice-script', 'invoice_upload_obj', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('upload_invoice_nonce')
    ]);
}

add_action('wp_ajax_request_vat_invoice', 'handle_request_vat_invoice');
function handle_request_vat_invoice() {
    check_ajax_referer('request_invoice_nonce', 'nonce');
    $order_id = intval($_POST['order_id']);
    $order = wc_get_order($order_id);
    if (!$order || get_current_user_id() !== $order->get_customer_id()) {
        wp_send_json_error('Unauthorized', 403);
    }
    update_post_meta($order_id, '_vat_invoice_requested', 'yes');

    // Add a note or email notification
    $order->add_order_note('The customer has requested a VAT invoice.');
    $order->add_order_note('You have requested a VAT invoice. The seller has been notified.', true);

    wp_send_json_success('Invoice request has been sent to the seller.');
}

add_action('wp_ajax_automate_vat_invoice', 'handle_automate_vat_invoice');
function handle_automate_vat_invoice() {
    check_ajax_referer('automate_invoice_nonce', 'nonce');

    $order_id = intval($_POST['order_id']);
    $order    = wc_get_order($order_id);

    if (!$order) {
        wp_send_json_error('Order not found.');
    }

    $seller_id = get_current_user_id();
    $items     = $order->get_items();
    $has_product = false;

    foreach ($items as $item) {
        $product_id = $item->get_product_id();
        $product_seller_id = get_post_field('post_author', $product_id);

        if ($product_seller_id == $seller_id) {
            $has_product = true;
            break;
        }
    }

    if (!$has_product) {
        wp_send_json_error('Unauthorized.');
    }

    // $vat_invoice_path = get_post_meta($order_id, '_vat_invoice', true);
    // if ($vat_invoice_path) {
    //     wp_send_json_error('Invoice already uploaded.');
    // }

    if(generate_vat_invoice($order_id)){
        // Assuming the VAT invoice is generated successfully and saved to a path
        $vat_invoice_path = get_post_meta($order_id, '_vat_invoice', true);
        
        // Notify the customer that the invoice is uploaded
        brags_notify_customer_invoice_uploaded($order_id, $vat_invoice_path);
        wp_send_json_success('Invoice automation activated.');
    }

    wp_send_json_error('Something wrong.');
    // update_post_meta($order_id, '_vat_invoice_automated', 'yes');
    // $order->add_order_note("Seller chose to automate VAT Invoice.");
    
    //wp_send_json_success('Invoice automation activated.');
}

add_action('wp_ajax_upload_vat_invoice', 'handle_upload_vat_invoice');
function handle_upload_vat_invoice() {
    check_ajax_referer('upload_invoice_nonce', 'nonce');

    $order_id = intval($_POST['order_id']);
    $order    = wc_get_order($order_id);

   

    if (!$order) {
        wp_send_json_error('Order not found.'. $order_id);
    }

    $file = $_FILES['file'];
    if (!$file || $file['error']) {
        wp_send_json_error('Upload failed. Please try again.');
    }

    $allowed_types = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/png'
    ];

    if (!in_array($file['type'], $allowed_types)) {
        wp_send_json_error('Invalid file format. Only PDF, Word, JPEG, PNG allowed.');
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        wp_send_json_error('File too large. Max 5MB allowed.');
    }

    $upload_dir = wp_upload_dir();
    $target_dir = trailingslashit($upload_dir['basedir']);
    $filename = 'vat-invoice-order-' . $order_id . '-' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    $target_path = $target_dir . $filename;

    // Move the uploaded file
    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        wp_send_json_error('Failed to move uploaded file.');
    }

    // Save the file path relative to basedir (same as automated logic)
    update_post_meta($order_id, '_vat_invoice', $target_path);
    update_post_meta($order_id, '_vat_invoice_automated', ''); // Reset automation if manual
      // Notify the customer that the invoice is uploaded
      brags_notify_customer_invoice_uploaded($order_id, $target_path);

    // Order Notes
    // $order->add_order_note("Seller uploaded VAT invoice.");
    // $order->add_order_note("Your VAT invoice is now available to download/view.", true);

    wp_send_json_success('Invoice uploaded and saved.');
}




function get_vat_amount_by_order_id($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return 0;

    $total_vat = 0;

    foreach ($order->get_items() as $item) {
        $product_id = $item->get_product_id();
        $price = floatval($item->get_total()); // Actual item price
        $vat_rate = get_post_meta($product_id, '_seller_vat_rate', true);

        if (!$vat_rate) $vat_rate = 0;

        $vat_amount = ($price * $vat_rate) / 100;
        $total_vat += $vat_amount;
    }

    return $total_vat;
}

function get_vat_invoice_status( $order_id ) {
    $invoice         = get_post_meta( $order_id, '_vat_invoice', true );
    $invoice_request = get_post_meta( $order_id, '_vat_invoice_requested', true );
    $order           = wc_get_order( $order_id );

    if ( ! $order ) {
        return 'Invalid Order';
    }

    // 1. Sent
    if ( ! empty( $invoice ) ) {
        return 'Sent';
    }

    // 2. Requested (but not sent yet)
    if ( $invoice_request === 'yes' ) {
        $notes = $order->get_customer_order_notes();
        $requested_date = null;

        // Try to find the date when invoice was requested
        foreach ( $notes as $note ) {
            if ( strpos( strtolower( $note->content ), 'requested a vat invoice' ) !== false ) {
                $requested_date = $note->date_created;
                break;
            }
        }

        if ( $requested_date ) {
            $now      = new DateTime();
            $interval = $now->diff( $requested_date );
            $days     = (int) $interval->format('%a');

            // 3. Overdue if > 7 days
            if ( $days > 7 ) {
                return 'Overdue';
            }
        }

        return 'Requested';
    }

    return 'Awaiting Invoice';
}



function generate_vat_invoice($order_id) {
    if (!class_exists('Dompdf\Dompdf')) {
        require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
    }

    $order = wc_get_order($order_id);
    if (!$order) return;

    // Get seller ID and store info via Dokan
    $seller_id = dokan_get_seller_id_by_order($order_id);
    $store_info = dokan_get_store_info($seller_id);

    $seller_name = $store_info['store_name'] ?? 'Seller';
    $seller_vat_number = get_user_meta($seller_id, 'dokan_vat_number', true);

    // Format seller address
    $full_address = '';
    if (!empty($store_info['address'])) {
        $address = $store_info['address'];
        $full_address = implode(', ', array_filter([
            $address['street_1'] ?? '',
            $address['street_2'] ?? '',
            $address['city'] ?? '',
            $address['state'] ?? '',
            $address['zip'] ?? '',
            $address['country'] ?? ''
        ]));
    }

    // Customer info
    $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
    $customer_email = $order->get_billing_email();

    // Order & Payment info
    $order_date = $order->get_date_created()->format('Y-m-d');
    $payment_date = $order->get_date_paid() ? $order->get_date_paid()->format('Y-m-d') : 'N/A';
    $payment_method = $order->get_payment_method_title();

    $items = $order->get_items();
    $total_vat = 0;
    $subtotal = 0;
    $grand_total = 0;

    // Start HTML
    $html = "<style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .text-right { text-align: right; }
        th { background: #eee; font-weight: bold; }
        td, th { padding: 6px; border: 1px solid #000; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
    </style>";

    $html .= "<table>
        <tr>
            <td colspan='7'>
                <b>{$seller_name}</b><br>{$full_address}
                <br>VAT Registration Number: {$seller_vat_number}
                <br><br><b>Date of Order:</b> {$order_date}<br>
                <b>Order Number:</b> {$order_id}<br>
                <b>Payment Date:</b> {$payment_date}<br>
            </td>
        </tr>
        <tr>
            <td colspan='7'>
                <b>Bill To</b><br>
                <b>Customer Name:</b> {$customer_name}<br>
                <b>Customer Email:</b> {$customer_email}<br><br>
            </td>
        </tr>
        <tr>
            <td colspan='7'><b>Order Summary</b></td>
        </tr>
        <tr>
            <th>Description</th>
            <th>Quantity</th>
            <th>Unit Price</th>
            <th>VAT Rate (%)</th>
            <th>Total (Excl. VAT)</th>
            <th>VAT Amount</th>
            <th>Total (Incl. VAT)</th>
        </tr>";

        foreach ($items as $item) {
            $product_id = $item->get_product_id();
            $product = wc_get_product($product_id);
    
            $vat_rate = get_post_meta($product_id, '_seller_vat_rate', true);
            if (!$vat_rate) $vat_rate = 0;
    
            $qty = intval($item->get_quantity());
            $total_incl_vat = floatval($item->get_total());
    
            // Calculate VAT correctly
            $vat_amount = ($total_incl_vat * $vat_rate) / (100 + $vat_rate);
            $item_total_excl_vat = $total_incl_vat - $vat_amount;
            $price_excl_vat = $qty ? $item_total_excl_vat / $qty : 0;
    
            $subtotal += $item_total_excl_vat;
            $total_vat += $vat_amount;
            $grand_total += $total_incl_vat;
    
            $html .= "<tr>
                <td>{$product->get_name()}</td>
                <td class='text-right'>{$qty}</td>
                <td class='text-right'>" . number_format($price_excl_vat, 2) . "</td>
                <td class='text-right'>" . number_format($vat_rate, 2) . "</td>
                <td class='text-right'>" . number_format($item_total_excl_vat, 2) . "</td>
                <td class='text-right'>" . number_format($vat_amount, 2) . "</td>
                <td class='text-right'>" . number_format($total_incl_vat, 2) . "</td>
            </tr>";
        }

    $html .= "
        <tr><td colspan='4'></td><td class='text-right'><b>Subtotal (Excl. VAT):</b></td><td colspan='2' class='text-right'>" . number_format($subtotal, 2) . "</td></tr>
        <tr><td colspan='4'></td><td class='text-right'><b>Total VAT Amount:</b></td><td colspan='2' class='text-right'>" . number_format($total_vat, 2) . "</td></tr>
        <tr><td colspan='4'></td><td class='text-right'><b>Total (Incl. VAT):</b></td><td colspan='2' class='text-right'>" . number_format($grand_total, 2) . "</td></tr>
    </table><br>";

    $html .= "<p><b>Payment Information</b><br>
        Payment Method: {$payment_method}<br>
        Payment Status: Paid<br>
        Payment Date: {$payment_date}
    </p>";

    // Generate PDF
    $options = new Options();
    $options->set('defaultFont', 'Arial');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Save PDF to disk
    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['basedir'] . "/vat_invoice_{$order_id}.pdf";
    file_put_contents($file_path, $dompdf->output());

    // Email to customer with the invoice PDF attached
    $email_subject = "Your VAT Invoice for Order #{$order_id}";
    $email_message = "Dear {$customer_name},\n\nPlease find attached your VAT invoice for Order #{$order_id}.\n\nBest regards,\nYour Store Team";

    wp_mail($customer_email, $email_subject, $email_message, [], [$file_path]);

    // Update order meta with invoice file path and automated flag
    update_post_meta($order_id, '_vat_invoice', $file_path);
    update_post_meta($order_id, '_vat_invoice_automated', 'yes');

    // Optional: Notify customer via custom function if defined
    if (function_exists('brags_notify_customer_invoice_uploaded')) {
        brags_notify_customer_invoice_uploaded($order_id, $file_path);
    }

    return true;
}



function generate_pdf_invoice_for_customer_subscription_order($order_id) {
    // Retrieve the order object
    $order = wc_get_order($order_id);
    if (!$order) return;

    // Check if the order contains subscription products
    $has_subscription_product = false;
    foreach ($order->get_items() as $item_id => $item) {
        $product = $item->get_product();
        
        // Check if the product is associated with a subscription (using PMS-specific meta key)
        if ($product && get_post_meta($product->get_id(), '_pms_woo_subscription_id', true)) {
            $has_subscription_product = true;
            break;
        }
    }

    // If the order doesn't contain subscription products, exit
    if (!$has_subscription_product) return;

    // Proceed with generating the PDF invoice
    $pdf_invoice = generate_vat_invoice($order_id);
}

add_action('woocommerce_order_status_completed', 'generate_pdf_invoice_for_customer_subscription_order');




function generate_monthly_vat_invoice_for_seller($order_id) {
    if (!class_exists('Dompdf\Dompdf')) {
        require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
    }

    $order = wc_get_order($order_id);
    if (!$order) return;

    $seller_id = $order->get_customer_id();
    $user = get_userdata($seller_id);
    // Proceed only if user is a vendor
    if (!in_array('seller', (array) $user->roles)) {
        return;
    }

   
    // Confirm it's a Dokan subscription order
    // $is_dokan_subscription = get_post_meta($order_id, '_dokan_vendor_subscription_order', true) === 'yes'
    //     || get_post_meta($order_id, '_pack_validity', true);
    // if (!$is_dokan_subscription) return;

    //print_r('sdsdsd');exit();

    
    $store = dokan_get_store_info($seller_id);
    $store_name = $store['store_name'] ?? 'N/A';
    $seller_email = $user->user_email ?? '';
    $vat_number = get_user_meta($seller_id, 'dokan_vat_number', true);
    $billing_period = $order->get_date_created()->format('1st F Y') . ' to ' . $order->get_date_created()->format('t F Y');
    $invoice_number = 'INV-BRAGS-' . $order_id;
    $invoice_date = $order->get_date_created()->format('jS F Y');
    $payment_date = $order->get_date_paid() ? $order->get_date_paid()->format('jS F Y') : $invoice_date;
    $transaction_id = $order->get_transaction_id() ?: 'N/A';

    $total_excl_vat = $order->get_total();
    $vat_rate = 20;
    $vat_amount = $total_excl_vat * $vat_rate / 100;
    $total_incl_vat = $total_excl_vat + $vat_amount;

    $html = "
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; color: #000; }
        h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        .section { margin-bottom: 30px; }
        .label { font-weight: bold; }
    </style>
    <h2>Brags.co.uk Monthly Seller Fee Invoice (Paid)</h2>
    <div class='section'>
        <p><strong>BRAGS & PARTNERS LTD</strong><br>
        Company Number: 15971543<br>
        71-75 Shelton Street, Covent Garden, London, United Kingdom, WC2H 9JQ<br>
        VAT Registration Number: GB 489 1909 35<br>
        Email: sellers@brags.co.uk</p>
        <p><strong>Invoice Date:</strong> {$invoice_date}<br>
        <strong>Invoice Number:</strong> {$invoice_number}<br>
        <strong>Billing Period:</strong> {$billing_period}<br>
        <strong>Payment Date:</strong> {$payment_date}</p>
    </div>
    <div class='section'>
        <p><strong>Bill To:</strong><br>
        Seller Name: {$user->display_name}<br>
        Seller Business Name: {$store_name}<br>
        VAT Registration Number: {$vat_number}<br>
        Seller Email: {$seller_email}</p>
    </div>
    <div class='section'>
        <p><strong>Seller Fee Summary</strong></p>
        <table>
            <thead>
                <tr>
                    <th>Fee Description</th>
                    <th>Monthly Fee (Excl. VAT)</th>
                    <th>VAT Rate (%)</th>
                    <th>VAT Amount</th>
                    <th>Total (Incl. VAT)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Bragsy Seller Plan Subscription</td>
                    <td>£" . number_format($total_excl_vat, 2) . "</td>
                    <td>{$vat_rate}%</td>
                    <td>£" . number_format($vat_amount, 2) . "</td>
                    <td>£" . number_format($total_incl_vat, 2) . "</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class='section'>
        <p><strong>Subtotal (Excl. VAT):</strong> £" . number_format($total_excl_vat, 2) . "<br>
        <strong>Total VAT Amount:</strong> £" . number_format($vat_amount, 2) . "<br>
        <strong>Total Paid (Incl. VAT):</strong> £" . number_format($total_incl_vat, 2) . "</p>
    </div>
    <div class='section'>
        <p><strong>Payment Information</strong><br>
        Payment Status: Paid<br>
        Payment Amount: £" . number_format($total_incl_vat, 2) . "<br>
        Payment Date: {$payment_date}<br>
        Transaction Reference: {$transaction_id}</p>
    </div>
    <p><em>This Invoice has been generated automatically. For any issues or discrepancies, please contact the Brags Seller Team!</em></p>
    ";

    // Generate PDF
    //$options = new Dompdf\Options();
    $options = new Options();
    $options->set('defaultFont', 'Arial');
    $dompdf = new Dompdf($options);
    
    //$dompdf = new Dompdf\Dompdf($options);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Save PDF
    $upload_dir = wp_upload_dir();
    $pdf_dir = $upload_dir['basedir'] . '/bragsy-invoices/';
    $pdf_filename = "bragsy-invoice-{$order_id}.pdf";
    $pdf_path = $pdf_dir . $pdf_filename;

    if (!file_exists($pdf_dir)) {
        wp_mkdir_p($pdf_dir);
    }

    file_put_contents($pdf_path, $dompdf->output());

    // Save to meta
    update_post_meta($order_id, '_bragsy_vat_invoice_pdf', $pdf_path);
    update_post_meta($order_id, '_bragsy_invoice_number', $invoice_number);
}


add_action('woocommerce_order_status_completed', 'generate_monthly_vat_invoice_for_seller');



// add columns for customer
add_filter('woocommerce_account_orders_columns', 'custom_add_invoice_and_actions_columns');
function custom_add_invoice_and_actions_columns($columns) {
    if (!current_user_can('customer')) {
        return $columns;
    }
    // Insert after "order-total"
    $new_columns = [];

    foreach ($columns as $key => $label) {
        $new_columns[$key] = $label;
        if ($key === 'order-total') {
            $new_columns['invoice'] = __('Invoice', 'woocommerce');
        }
    }

    return $new_columns;
}

add_action( 'dokan_order_listing_header_before_action_column', 'custom_dokan_order_add_columns' );
function custom_dokan_order_add_columns() {
    ?>
    <th><?php _e( 'Customer Type', 'woocommerce' ); ?></th>
    <th><?php _e( 'VAT/Tax Rate', 'woocommerce' ); ?></th>
    <th><?php _e( 'Upload Invoice', 'woocommerce' ); ?></th>
    <th><?php _e( 'Automate Invoice', 'woocommerce' ); ?></th>
    <th><?php _e( 'Invoice Status', 'woocommerce' ); ?></th>
    <?php
}

add_action( 'dokan_order_listing_row_before_action_field', 'custom_dokan_order_add_column_data', 10, 1 );
function custom_dokan_order_add_column_data( $order ) {

    // if(isset($_GET['dev']) && $_GET['dev']=='k2'){
    //     generate_vat_invoice(18008);
    //     echo "done";
    //     exit();
    // }
    
    $order_id = $order->get_id();
    $customer_id  = $order->get_customer_id();
    $seller_id   = get_current_user_id();

    // update_post_meta($order_id, '_vat_invoice', '');
    // update_post_meta($order_id, '_vat_invoice_automated', '');

    // Fetch the VAT rate for the seller's products in this order
    $items = $order->get_items();
    $seller_vat_rates = [];

    foreach ( $items as $item ) {
        $product_id  = $item->get_product_id();
        $product_seller_id = get_post_field( 'post_author', $product_id );

        if ( $product_seller_id == $seller_id ) {
            $vat_rate = get_post_meta( $product_id, '_seller_vat_rate', true );
            if ( $vat_rate !== '' ) {
                $seller_vat_rates[] = $vat_rate;
            }
        }
    }

    $vat_rate_display = !empty( $seller_vat_rates ) ? implode( ', ', array_unique( $seller_vat_rates ) ) . '%' : '-';

    // Example data placeholders
    $customer_type   = get_user_meta( $customer_id, 'dokan_custom_business_type', true );
    $invoice_status  = get_vat_invoice_status( $order_id );
    $automated  = get_post_meta( $order_id, '_vat_invoice_automated', true );
    $invoice = get_post_meta($order_id, '_vat_invoice', true);

    echo '<td>' . esc_html( $customer_type ?: 'Individual' ) . '</td>';
    echo '<td>' . esc_html( $vat_rate_display ?: '-' ) . '</td>';

    if($invoice!=''){
        $url  = wp_upload_dir()['baseurl'] . '/' . basename($invoice);

        if($automated=='yes'){
            echo '<td>-</td>';
        }else{
             // Upload Invoice (just a placeholder button)
         echo '<td><a href="'.$url.'" class="dokan-btn dokan-btn-sm dokan-btn-default" target="_blanck">View</a></td>';
        }
        

    }else{
        // Upload Invoice (just a placeholder button)
        echo '<td><a href="javascript:void(0)" class="dokan-btn dokan-btn-sm dokan-btn-default upload-invoice-btn" data-order-id="' . esc_attr( $order_id ) . '">Upload</a></td>';
      
    }

    
    if($invoice!=''){
        $url  = wp_upload_dir()['baseurl'] . '/' . basename($invoice);
        echo '<td>';
        if($automated=='yes'){
             // Automate Invoice (just a toggle or link)
         echo '<a href="'.$url.'" class="dokan-btn dokan-btn-sm dokan-btn-success" target="_blanck">View</a>';
            
        }else{
             // Upload Invoice (just a placeholder button)
             echo '-';
        }
        
        //echo '<a href="javascript:void(0)" class="dokan-btn dokan-btn-sm dokan-btn-success automate-invoice-btn" data-order-id="' . esc_attr( $order_id ) . '">Automate</a>';
        echo '</td>';
    }else{
        
       $vat_number = get_user_meta($seller_id,'dokan_vat_number',true);
        echo '<td>';
        if($vat_number!=""){
             // Automate Invoice (just a toggle or link)
        echo '<a href="javascript:void(0)" class="dokan-btn dokan-btn-sm dokan-btn-success automate-invoice-btn" data-order-id="' . esc_attr( $order_id ) . '">Automate</a>';
        }
        
        echo '</td>';
       
    }

    
   

    
    // Invoice Status
    echo '<td>' . esc_html( $invoice_status ?: 'Not Generated' ) . '</td>';
}




function custom_invoice_column_content($order) {
    // Only show to customers
    if (!current_user_can('customer')) {
        return;
    }

     // Get the saved VAT invoice path from order meta
     $invoice = get_post_meta($order->get_id(), '_vat_invoice', true);

     if ($invoice) {
         // Generate full URL
         $url = wp_upload_dir()['baseurl'] . '/' . basename($invoice);
 
         echo '<a class="woocommerce-button button" href="' . esc_url($url) . '" target="_blank">' . __('Download', 'woocommerce') . '</a>';
     } else {
         // Add a Request Invoice button with AJAX trigger
         $order_id = $order->get_id();
         $requested = get_post_meta($order_id, '_vat_invoice_requested', true);
         if($requested=='yes'){
            echo '<button class="woocommerce-button button alt" data-order-id="' . esc_attr($order_id) . '">' . __('Requested', 'woocommerce') . '</button>';
         }else{
            echo '<button class="woocommerce-button button alt request-invoice-btn" data-order-id="' . esc_attr($order_id) . '">' . __('Request', 'woocommerce') . '</button>';
         }

        
         echo '<div class="request-invoice-response" style="margin-top: 5px;"></div>';
     }
}
add_action('woocommerce_my_account_my_orders_column_invoice', 'custom_invoice_column_content');




// ------------------ download link -----------------------
function add_vat_invoice_download_link($actions, $order) {
    $invoice = get_post_meta($order->get_id(), '_vat_invoice', true);
    if ($invoice) {
        $actions['download_vat'] = array(
            'url'  => wp_upload_dir()['baseurl'] . '/' . basename($invoice),
            'name' => __('Download VAT Invoice', 'woocommerce')
        );
    }
    return $actions;
}
//add_filter('woocommerce_my_account_my_orders_actions', 'add_vat_invoice_download_link', 10, 2);

function add_vat_invoice_column($columns) {
    $columns['vat_invoice'] = __('VAT Invoice', 'woocommerce');
    return $columns;
}
add_filter('manage_edit-shop_order_columns', 'add_vat_invoice_column');

function show_vat_invoice_link($column, $post_id) {
    if ($column === 'vat_invoice') {
        $invoice = get_post_meta($post_id, '_vat_invoice', true);
        if ($invoice) {
            echo '<a href="' . wp_upload_dir()['baseurl'] . '/' . basename($invoice) . '" target="_blank">Download</a>';
        } else {
            echo 'Not Generated';
        }
    }
}
add_action('manage_shop_order_posts_custom_column', 'show_vat_invoice_link', 10, 2);



add_action('woocommerce_before_account_orders', 'brags_show_invoice_notice');
function brags_show_invoice_notice() {
    echo '<div class="" style="margin-bottom:20px; ">
        <strong>Notice:</strong> Sellers on <strong>Brags.co.uk</strong> are entirely responsible for providing customers with invoices and must upload them within <strong>7 days</strong> of the order date.<br>
        If the seller has uploaded an invoice, you can simply click <strong>‘Download’</strong> next to the order. If the seller has not yet provided an invoice, click <strong>‘Request’</strong> to send them a polite reminder.<br>
        You can also <strong>contact the seller</strong> directly through their store.
    </div>';
}


// crons for invoice remainder to seller

// Schedule the daily cron event
add_action('init', function () {
    if (!wp_next_scheduled('brags_check_invoice_reminders')) {
        wp_schedule_event(time(), 'daily', 'brags_check_invoice_reminders');
    }
});

// Hook for the cron job
add_action('brags_check_invoice_reminders', 'brags_send_invoice_reminder_emails');


/**
 * Main function to check for invoice requests and send reminders to sellers
 */


function brags_send_invoice_reminder_emails() {
    // $args = [
    //     'limit'  => -1,
    //     'status' => ['wc-completed', 'wc-processing'],
    //     'meta_query' => [
    //         [
    //             'key'     => '_vat_invoice_requested',
    //             'value'   => 'yes',
    //             'compare' => '='
    //         ],
    //     ],
    // ];
    $args = [
        'limit'  => -1,
        'status' => ['wc-completed', 'wc-processing'],
    ];

    $orders = wc_get_orders($args);
    // 

    foreach ($orders as $order) {
        $order_id = $order->get_id();
        $seller_id = dokan_get_seller_id_by_order($order_id);
        $seller_user = get_userdata($seller_id);

        $email = $seller_user->user_email;
        //$email = 'kirtan.fableadtechnolabs@gmail.com';

        // Skip if no seller found or already uploaded
        if (!$seller_user || get_post_meta($order_id, '_vat_invoice', true)) continue;

        if(get_post_meta($order_id, '_vat_invoice_requested', true)=='yes'){

            $order_date = $order->get_date_created();
            $days_since_order = (new DateTime())->diff($order_date)->days;

            // Send 7-day reminder
            if ($days_since_order == 7 ) {
                $message = "<p>Hi Brags Seller,</p>
                <p>Just to let you know, the Invoice for Order ID <strong>#{$order_id}</strong> is now due for upload.</p>
                <p>We require you to provide an Invoice within 7 days from the Order date to all Customers. Please either ‘Upload’ an Invoice or ‘Automate’ an Invoice for this Order from your Order page below.</p>
                <p><strong><a href='" . site_url('/dashboard/') . "' target='_blank'>Brags Seller Dashboard – Brags</a></strong></p>
                <p>Any issues, just contact the Brags Seller Team!</p>
                <p>Many thanks!</p>";
                
                wp_mail(
                    $email,
                    "Reminder: VAT Invoice Due for Order #{$order_id}",
                    $message,
                    ['Content-Type: text/html; charset=UTF-8']
                );
            }

            // Send 14-day final reminder
            if ($days_since_order == 14) {
                $message = "<p>Hi Brags Seller,</p>
                <p>We sent you a reminder recently that Order ID <strong>#{$order_id}</strong> is due for upload.</p>
                <p>We require you to provide an Invoice within 7 days from the Order date to all Customers and this Invoice is now <strong>‘overdue’</strong>.</p>
                <p>Please upload today. Failure to do so can result in your Seller Score being affected and even possible Account Suspension.</p>
                <p>Please either ‘Upload’ an Invoice or ‘Automate’ an Invoice for this Order from your Order page below.</p>
                
                <p><strong><a href='" . site_url('/dashboard/') . "' target='_blank'>Brags Seller Dashboard – Brags</a></strong></p>
                <p>Any issues, just contact the Brags Seller Team!</p>";

                wp_mail(
                    $email,
                    "Final Reminder: Overdue VAT Invoice for Order #{$order_id}",
                    $message,
                    ['Content-Type: text/html; charset=UTF-8']
                );
            }
        }
    }
}



/**
 * Send invoice to customer when invoice is uploaded
 * Call this inside your upload/automate logic where you update '_vat_invoice'
 */
function brags_notify_customer_invoice_uploaded($order_id, $file_path) {
    $order = wc_get_order($order_id);
    if (!$order) return;

    $customer_email = $order->get_billing_email();
    //$customer_email='kirtan.fableadtechnolabs@gmail.com';

    $message = "<p>Hi Brags Customer,</p>
<p>Your Invoice for Order ID <strong>#{$order_id}</strong> has been provided by the Seller. Please see attached.</p>
<p>Sellers on Brags.co.uk are entirely responsible for providing customers with invoices and must upload them within 7 days of the order date.</p>
<p>If you have any queries regarding this invoice, please contact the seller directly through their store page.</p>
<p>Thanks for Shopping on Brags!</p>";

    wp_mail(
        $customer_email,
        "Your VAT Invoice for Order #{$order_id}",
        $message,
        ['Content-Type: text/html; charset=UTF-8'],
        [$file_path] // Attachment
    );
}
