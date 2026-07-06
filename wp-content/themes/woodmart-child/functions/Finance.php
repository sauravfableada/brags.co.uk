<?php
add_filter( 'dokan_get_dashboard_nav', 'add_finance_tab_to_dokan_dashboard', 99 );
function add_finance_tab_to_dokan_dashboard( $urls ) {
    $urls['finance'] = array(
        'title' => __( 'Finance', 'dokan' ),
        'icon'  => '<i class="fas fa-file-invoice-dollar"></i>',
        'url'   => dokan_get_navigation_url( 'finance' ),
        'pos'   => 8
    );
    return $urls;
}

add_action( 'dokan_load_custom_template', 'load_finance_template' );

function register_dokan_custom_query_finance_vars( $query_vars ) {
    $query_vars[] = 'finance';
    return $query_vars;
}
add_filter( 'query_vars', 'register_dokan_custom_query_finance_vars' );


function load_dokan_finance_template() {
    global $wp_query;

    if ( isset( $wp_query->query_vars['finance'] ) ) {
        $template_path = get_stylesheet_directory() . '/template-parts/dokan/finance.php';

        if ( file_exists( $template_path ) ) {
            include $template_path;
            exit;
        } else {
            wp_die( 'Finance template not found.' );
        }
    }
}
add_action( 'template_redirect', 'load_dokan_finance_template' );

function add_finance_rewrite_endpoint() {
    add_rewrite_endpoint( 'finance', EP_ROOT | EP_PAGES );
    flush_rewrite_rules();
}
add_action( 'init', 'add_finance_rewrite_endpoint' );


// Enqueue custom scripts
function brags_enqueue_export_script() {
    if (is_user_logged_in()) {
        wp_enqueue_script(
            'brags-export-script',
            get_stylesheet_directory_uri() . '/assets/js/export-csv.js',
            ['jquery'],
            '1.0',
            true
        );

        wp_localize_script('brags-export-script', 'brags_export_ajax', [
            'ajax_url' => admin_url('admin-ajax.php')
        ]);

        wp_enqueue_style('export-csv-style', get_stylesheet_directory_uri() . '/assets/css/export-csv-style.css');
    }
}
add_action('wp_enqueue_scripts', 'brags_enqueue_export_script');


// Handle the AJAX request for CSV export
function handle_csv_export() {
    $from = isset($_POST['from']) ? sanitize_text_field($_POST['from']) : '';
    $to   = isset($_POST['to']) ? sanitize_text_field($_POST['to']) : '';
    $seller_id = get_current_user_id();

    $args = [
        'limit'  => -1,
        'paged'  => 1,
        'status' => ['wc-completed', 'wc-processing'],
    ];

    $orders = dokan_get_seller_orders($seller_id, $args);
    $csv_data = [];

    foreach ($orders as $order_id) {
        $order = wc_get_order($order_id);
        if (!$order) continue;

        $order_date = $order->get_date_created() ? $order->get_date_created()->format('Y-m-d') : '';
        if ($from && $order_date < $from) continue;
        if ($to && $order_date > $to) continue;

        $order_total = $order->get_total();
        $currency = '';//get_woocommerce_currency_symbol();

        $seller_fee_ex_vat = $order_total * 0.10;
        $seller_fee_vat    = $seller_fee_ex_vat * 0.20;
        $seller_fee_inc    = $seller_fee_ex_vat + $seller_fee_vat;

        $other_fee_ex_vat  = $order_total * 0.03;
        $other_fee_vat     = $other_fee_ex_vat * 0.20;
        $other_fee_inc     = $other_fee_ex_vat + $other_fee_vat;

        $csv_data[] = [
            $order->get_id(),
            $currency . number_format($order_total, 2),
            $currency . number_format($seller_fee_ex_vat, 2),
            $currency . number_format($seller_fee_vat, 2),
            $currency . number_format($seller_fee_inc, 2),
            $currency . number_format($other_fee_ex_vat, 2),
            $currency . number_format($other_fee_vat, 2),
            $currency . number_format($other_fee_inc, 2),
        ];
    }

    if (!empty($csv_data)) {
        ob_start();
        $output = fopen('php://output', 'w');

        fputcsv($output, []);
        fputcsv($output, ['Any issues with this report or have any queries regarding this report, please contact the Brags Seller Team.']);
        fputcsv($output, []);

        fputcsv($output, [
            'Order ID',
            'Product Charges',
            'Seller Fees (ex VAT)',
            'VAT on Seller Fees (20%)',
            'Seller Fees (inc VAT)',
            'Other Fees (ex VAT)',
            'VAT on Other Fees (20%)',
            'Other Fees (inc VAT)',
        ]);

        foreach ($csv_data as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        $csv_string = ob_get_clean();

        echo $csv_string;
        exit;
    }

    wp_send_json_error('No data found.');
}
add_action('wp_ajax_export_transaction_report', 'handle_csv_export');


