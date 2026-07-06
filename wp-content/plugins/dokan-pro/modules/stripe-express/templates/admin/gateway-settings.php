<?php

defined( 'ABSPATH' ) || exit;

use WeDevs\DokanPro\Modules\StripeExpress\Support\Helper;
use WeDevs\DokanPro\Modules\StripeExpress\Support\Settings;

// Collect test mode endpoint if user on test mode.
$stripe_env = Settings::is_test_mode() ? '/test' : '';

$form_fields = [
    'enabled'                          => [
        'title'       => __( 'Enable/Disable', 'dokan' ),
        'label'       => __( 'Enable Stripe Express', 'dokan' ),
        'type'        => 'checkbox',
        'description' => '',
        'default'     => 'no',
    ],
    'title'                            => [
        'title'       => __( 'Title', 'dokan' ),
        'type'        => 'text',
        'description' => __( 'This controls the title which the user sees during checkout. This title will be shown only when multiple payment methods are enabled for Stripe Express', 'dokan' ),
        'default'     => __( 'Stripe Express', 'dokan' ),
        'desc_tip'    => true,
    ],
    'description'                      => [
        'title'       => __( 'Description', 'dokan' ),
        'type'        => 'textarea',
        'description' => __( 'This controls the description which the user sees during checkout.', 'dokan' ),
        'default'     => __( 'Pay with your credit card via Stripe.', 'dokan' ),
        'desc_tip'    => true,
    ],
    'api_details'                      => [
        'title'       => __( 'API Credentials', 'dokan' ),
        'type'        => 'title',
        'description' => Helper::get_api_keys_description(),
    ],
    'test_environment' => [
        'title'       => __( 'Test Environment', 'dokan' ),
        'type'        => 'title',
        'description' => __( 'Both Test Mode and Sandbox Mode are test environments. Choose one, or leave both unchecked to use Live Mode.', 'dokan' ),
    ],
    'testmode' => [
        'title'       => __( 'Stripe Test Mode', 'dokan' ),
        'type'        => 'checkbox',
        'label'       => __( 'Enable Stripe Test Mode', 'dokan' ),
        'default'     => 'no',
        'description' => __( 'Use Stripe test mode to simulate transactions with test cards.', 'dokan' ),
    ],
    'sandbox_mode' => [
        'title'       => __( 'Stripe Sandbox Mode', 'dokan' ),
        'type'        => 'checkbox',
        'label'       => __( 'Enable Stripe Sandbox Mode', 'dokan' ),
        'default'     => 'no',
        'description' => __( 'Use Stripe sandbox mode to simulate transactions using sandbox API credentials.', 'dokan' ),
    ],
    'sandbox_publishable_key'          => [
        'title'       => __( 'Sandbox Publishable Key', 'dokan' ),
        'type'        => 'text',
        'description' => __( 'Your sandbox publishable key', 'dokan' ),
        'default'     => '',
        'desc_tip'    => true,
    ],
    'sandbox_secret_key'               => [
        'title'       => __( 'Sandbox Secret Key', 'dokan' ),
        'type'        => 'password',
        'description' => __( 'Your sandbox secret key', 'dokan' ),
        'default'     => '',
        'desc_tip'    => true,
    ],
    'publishable_key'                  => [
        'title'       => __( 'Publishable Key', 'dokan' ),
        'type'        => 'password',
        'class'       => 'input-text regular-input ',
        'description' => __( 'Publishable key for Stripe', 'dokan' ),
        'default'     => '',
        'desc_tip'    => true,
        'placeholder' => __( 'Publishable Key', 'dokan' ),
    ],
    'secret_key'                       => [
        'title'       => __( 'Secret Key', 'dokan' ),
        'type'        => 'password',
        'class'       => 'input-text regular-input ',
        'description' => __( 'Secret key for Stripe', 'dokan' ),
        'default'     => '',
        'desc_tip'    => true,
        'placeholder' => __( 'Secret key', 'dokan' ),
    ],
    'test_publishable_key'             => [
        'title'       => __( 'Test Publishable Key', 'dokan' ),
        'type'        => 'text',
        'class'       => 'input-text regular-input ',
        'description' => __( 'Test Publishable key for Stripe', 'dokan' ),
        'default'     => '',
        'desc_tip'    => true,
        'placeholder' => __( 'Test Publishable Key', 'dokan' ),
    ],
    'test_secret_key'                  => [
        'title'       => __( 'Test Secret Key', 'dokan' ),
        'type'        => 'password',
        'class'       => 'input-text regular-input ',
        'description' => __( 'Test Secret key for Stripe', 'dokan' ),
        'default'     => '',
        'desc_tip'    => true,
        'placeholder' => __( 'Test Secret key', 'dokan' ),
    ],
    'webhook'                          => [
        'title'       => __( 'Webhook Endpoints', 'dokan' ),
        'type'        => 'title',
        'description' => Helper::get_webhook_description(),
    ],
    'webhook_key'                      => [
        'title'       => __( 'Webhook Secret', 'dokan' ),
        'type'        => 'password',
        'description' => __( 'Get your webhook signing secret from the webhooks section in your stripe account.', 'dokan' ),
        'default'     => '',
        'desc_tip'    => true,
    ],
    'sandbox_webhook_key'              => [
        'title'       => __( 'Sandbox Webhook Secret', 'dokan' ),
        'type'        => 'password',
        'description' => __( 'Get your webhook signing secret from the webhooks section in your stripe account.', 'dokan' ),
        'default'     => '',
        'desc_tip'    => true,
    ],
    'test_webhook_key'                 => [
        'title'       => __( 'Test Webhook Secret', 'dokan' ),
        'type'        => 'password',
        'description' => __( 'Get your webhook signing secret from the webhooks section in your stripe account.', 'dokan' ),
        'default'     => '',
        'desc_tip'    => true,
    ],
    'payment_options'                  => [
        'title'       => __( 'Payment and Disbursement', 'dokan' ),
        'type'        => 'title',
        'description' => __( 'Manage the payment and fund disbursements.', 'dokan' ),
    ],
    'payment_method_configuration'     => [
        'title'       => __( 'Payment method configuration', 'dokan' ),
        'type'        => 'text',
        'class'       => 'input-text regular-input ',
        'placeholder' => 'default',
        'description' => Helper::get_payment_method_configuration_description( false ),
    ],
    'test_payment_method_configuration'     => [
        'title'       => __( 'Test Payment method configuration', 'dokan' ),
        'type'        => 'text',
        'class'       => 'input-text regular-input ',
        'placeholder' => 'default',
        'description' => Helper::get_payment_method_configuration_description(),
    ],
    'sellers_pay_processing_fee'       => [
        'title'       => __( 'Take Processing Fees from Sellers', 'dokan' ),
        'label'       => __( 'If activated, Sellers will pay the Stripe processing fee instead of Admin/Site Owner.', 'dokan' ),
        'type'        => 'checkbox',
        'description' => __( 'By default Admin/Site Owner pays the Stripe processing fee.', 'dokan' ),
        'default'     => 'no',
    ],
    'saved_cards'                      => [
        'title'       => __( 'Saved Cards', 'dokan' ),
        'type'        => 'checkbox',
        'label'       => __( 'Enable payment via saved cards', 'dokan' ),
        'description' => __( 'If enabled, customers will be able to save cards during checkout. Card data will be saved on Stripe server, not on the store.', 'dokan' ),
        'default'     => 'no',
    ],
    'capture'                          => [
        'title'       => __( 'Capture Payments Manually', 'dokan' ),
        'type'        => 'checkbox',
        'label'       => __( 'Issue an authorization on checkout, and capture later', 'dokan' ),
        'description' => __( 'Only cards support manual capture. When enabled, all other payment methods will be hidden from checkout. Charge must be captured on the order details screen within 7 days of authorization, otherwise the authorization and order will be canceled.', 'dokan' ),
        'default'     => 'no',
    ],
    'disburse_mode'                    => [
        'title'       => __( 'Disburse Funds', 'dokan' ),
        'type'        => 'select',
        'class'       => 'wc-enhanced-select',
        'label'       => __( 'Choose when you want to disburse funds to the vendors', 'dokan' ),
        'default'     => 'no',
        'description' => __(
            'You can choose when whether you want to transfer funds to vendors after the order is completed, or immediately after the payment is completed, or delay the transfer even if the order is processing or completed.',
            'dokan'
        ),
        'options'     => [
            'ON_ORDER_PROCESSING' => __( 'On payment completed', 'dokan' ),
            'ON_ORDER_COMPLETED'  => __( 'On order completed', 'dokan' ),
            'DELAYED'             => __( 'Delayed', 'dokan' ),
        ],
    ],
    'disbursement_delay_period'        => [
        'title'             => __( 'Delay Period (Days)', 'dokan' ),
        'type'              => 'number',
        'class'             => 'input-text regular-input ',
        'description'       => __( 'Specify after how many days funds will be disburse to corresponding vendor. The funcds will be transferred to vendors after this period automatically', 'dokan' ),
        'default'           => '14',
        'desc_tip'          => true,
        'placeholder'       => __( 'Delay Period', 'dokan' ),
        'custom_attributes' => [
            'min' => 1,
        ],
    ],
    'statement_descriptor'             => [
        'title'       => __( 'Customer Bank Statement', 'dokan' ),
        'type'        => 'text',
        'class'       => 'input-text regular-input ',
        'description' => __( 'Enter the name your customers will see on their transactions. Use a recognizable name – e.g. the legal entity name or website address–to avoid potential disputes and chargebacks.', 'dokan' ),
    ],
    'appearance'                       => [
        'title'       => __( 'Payment Element Appearance', 'dokan' ),
        'type'        => 'title',
        'description' => __( 'Customize theme and appearance of Payment Element', 'dokan' ),
    ],
    'element_theme'                    => [
        'title'       => __( 'Theme', 'dokan' ),
        'type'        => 'select',
        'description' => __( 'Select the theme you would like to choose for Stripe element.', 'dokan' ),
        'default'     => 'stripe',
        'desc_tip'    => true,
        'options'     => [
            'stripe'    => __( 'Default', 'dokan' ),
            'flat'      => __( 'Flat', 'dokan' ),
            'night'     => __( 'Dark', 'dokan' ),
            'none'      => __( 'None', 'dokan' ),
        ],
    ],
    'payment_request_options'          => [
        'title'       => __( 'Payment Request Options (Apple Pay / Google Pay)', 'dokan' ),
        'type'        => 'title',
        'description' => sprintf(
            /* translators: 1) br tag 2) Stripe anchor tag 3) Apple anchor tag 4) Stripe dashboard opening anchor tag 5) Stripe dashboard closing anchor tag */
            __( 'Enable payment via Apple Pay and Google Pay. %1$sBy using Apple Pay, you agree to %2$s and %3$s\'s terms of service. (Apple Pay domain verification is performed automatically in live mode; configuration can be found on the %4$sStripe dashboard%5$s.)', 'dokan' ),
            '<br />',
            '<a href="https://stripe.com/legal/apple-pay" target="_blank">Stripe</a>',
            '<a href="https://developer.apple.com/apple-pay/acceptable-use-guidelines-for-websites/" target="_blank">Apple</a>',
            "<a href='https://dashboard.stripe.com{$stripe_env}/dashboard' target='_blank'>",
            '</a>'
        ),
    ],
    'payment_request'                  => [
        'title'       => __( 'Payment Request Buttons', 'dokan' ),
        'label'       => __( 'Enable Payment Request Buttons. (Apple Pay/Google Pay)', 'dokan' ),
        'type'        => 'checkbox',
        'description' => __( 'If enabled, users will be able to pay using Apple Pay or Chrome Payment Request if supported by the browser. Depending on the web browser and wallet configurations, your customers will see either Apple Pay or Google Pay, but not both.', 'dokan' ),
        'default'     => 'yes',
        'desc_tip'    => true,
    ],
    'payment_request_button_type'      => [
        'title'       => __( 'Button Type', 'dokan' ),
        'type'        => 'select',
        'description' => __( 'Select the button type you would like to show.', 'dokan' ),
        'default'     => 'default',
        'desc_tip'    => true,
        'options'     => [
            'default' => __( 'Only Icon', 'dokan' ),
            'buy'     => __( 'Buy', 'dokan' ),
            'donate'  => __( 'Donate', 'dokan' ),
            'book'    => __( 'Book', 'dokan' ),
        ],
    ],
    'payment_request_button_theme'     => [
        'title'       => __( 'Button Theme', 'dokan' ),
        'type'        => 'select',
        'description' => __( 'Select the button theme you would like to show.', 'dokan' ),
        'default'     => 'dark',
        'desc_tip'    => true,
        'options'     => [
            'dark'          => __( 'Dark', 'dokan' ),
            'light'         => __( 'Light', 'dokan' ),
            'light-outline' => __( 'Light-Outline', 'dokan' ),
        ],
    ],
    'payment_request_button_locations' => [
        'title'             => __( 'Button Locations', 'dokan' ),
        'type'              => 'multiselect',
        'description'       => __( 'Select where you would like Payment Request Buttons to be displayed', 'dokan' ),
        'desc_tip'          => true,
        'class'             => 'wc-enhanced-select',
        'options'           => [
            'product'  => __( 'Product', 'dokan' ),
            'cart'     => __( 'Cart', 'dokan' ),
            'checkout' => __( 'Checkout', 'dokan' ),
        ],
        'default'           => [ 'product', 'checkout' ],
        'custom_attributes' => [
            'data-placeholder' => __( 'Select pages', 'dokan' ),
        ],
    ],
    'payment_request_button_size'      => [
        'title'       => __( 'Button Size', 'dokan' ),
        'type'        => 'select',
        'description' => __( 'Select the size of the button.', 'dokan' ),
        'default'     => 'default',
        'desc_tip'    => true,
        'options'     => [
            'default' => __( 'Default (40px)', 'dokan' ),
            'medium'  => __( 'Medium (48px)', 'dokan' ),
            'large'   => __( 'Large (56px)', 'dokan' ),
        ],
    ],
    'cross_border_transfer_options'    => [
        'title'       => __( 'Cross-border Transfers and Onboarding', 'dokan' ),
        'type'        => 'title',
        'description' => __( 'Transfer options outside the marketplace\'s country under European Union or SEPA.', 'dokan' ),
    ],
    'cross_border_transfer'            => [
        'title'       => __( 'Cross-border Transfer', 'dokan' ),
        'label'       => __( 'Enable onboarding for vendors outside the country/region of the marketplace inside EU and SEPA or US Platforms only.', 'dokan' ),
        'type'        => 'checkbox',
        'description' => __( 'Enable this to allow vendors from the EU, SEPA, or US to select their country during signup, based on the countries supported for transfers by Stripe.', 'dokan' ),
        'default'     => 'no',
    ],
    'restricted_countries'             => [
        'title'       => __( 'Restrict Countries/Regions', 'dokan' ),
        'type'        => 'multiselect',
        'class'       => 'wc-enhanced-select',
        'description' => __( 'Select the countries where you will not be able to onboard connected accounts.', 'dokan' ),
        'options'     => [],
    ],
    'disconnect_restricted_vendors'            => [
        'title'       => __( 'Disconnect Vendors', 'dokan' ),
        'label'       => __( 'Disconnect all connected vendors from Restricted Countries/Regions.', 'dokan' ),
        'type'        => 'checkbox',
        'description' => __( 'This feature allows administrators to automatically disconnect vendor accounts from restricted countries or regions. By specifying a list of restricted locations, the system will identify and bulk disconnect any existing vendor accounts associated with those areas.', 'dokan' ),
        'default'     => 'no',
    ],
    'disconnect_connected_vendors'            => [
        'title'       => __( 'Disconnect Vendors', 'dokan' ),
        'label'       => __( 'Disconnect Cross Border Vendors with Existing Connections.', 'dokan' ),
        'type'        => 'checkbox',
        'description' => __( 'Disconnect all cross border vendors with existing payment connections. This will sever their payment integration, regardless of prior approval or onboarding.', 'dokan' ),
        'default'     => 'no',
    ],
    'advanced'                         => [
        'title'       => __( 'Advanced Settings', 'dokan' ),
        'type'        => 'title',
        'description' => __( 'Set up advanced settings to manage some extra options.', 'dokan' ),
    ],
    'notice_on_vendor_dashboard'       => [
        'title' => __( 'Display Notice to Non-connected Sellers', 'dokan' ),
        'label' => __(
            'If checked, non-connected sellers will see a notice to sign up for a Stripe Express account on their vendor dashboard.',
            'dokan'
        ),
        'type'        => 'checkbox',
        'description' => __(
            'If this is enabled, non-connected sellers will see a notice to sign up for a Stripe Express account on their vendor dashboard.',
            'dokan'
        ),
        'default'  => 'no',
        'desc_tip' => true,
    ],
    'announcement_to_sellers'          => [
        'title'       => __( 'Send Announcement to Non-connected Sellers', 'dokan' ),
        'label'       => __( 'If checked, non-connected sellers will receive announcement notice to sign up for a Stripe Express account. ', 'dokan' ),
        'type'        => 'checkbox',
        'description' => __(
            'If this is enabled non-connected sellers will receive announcement notice to sign up for a Stripe Express account.',
            'dokan'
        ),
        'default'     => 'no',
        'desc_tip'    => true,
    ],
    'notice_interval'                  => [
        'title'             => __( 'Announcement Interval', 'dokan' ),
        'type'              => 'number',
        'description'       => __(
            'If Send Announcement to Connect Seller setting is enabled, non-connected sellers will receive announcement notice to sign up for a Stripe Express account once in a week by default. You can control notice display interval from here. The interval value will be considered in days.',
            'dokan'
        ),
        'default'           => '7',
        'desc_tip'          => false,
        'custom_attributes' => [
            'min' => 1,
        ],
    ],
    'debug'                            => [
        'title'       => __( 'Debug Log', 'dokan' ),
        'type'        => 'checkbox',
        'label'       => __( 'Enable logging', 'dokan' ),
        'default'     => 'no',
        'description' => sprintf(
            /* translators: %s: URL */
            __( 'Log gateway events such as Webhook requests, Payment operations etc. inside %s. Note: this may log personal information. We recommend using this for debugging purposes only and deleting the logs when finished.', 'dokan' ),
            '<code>' . \WC_Log_Handler_File::get_log_file_path( 'dokan' ) . '</code>'
        ),
    ],
];

// Update the restricted countries based on the platform country.
if ( Helper::is_api_ready() ) {
    $supported_countries = Helper::get_supported_countries_for_vendors( true );
    if ( ! empty( $supported_countries ) ) {
        $form_fields['restricted_countries']['options'] = $supported_countries;
    } else {
        unset( $form_fields['restricted_countries'] );
    }
} else {
    $form_fields['restricted_countries']['custom_attributes'] = array( 'disabled' => 'disabled' );
}

return apply_filters( 'dokan_stripe_express_admin_settings_fields', $form_fields );
