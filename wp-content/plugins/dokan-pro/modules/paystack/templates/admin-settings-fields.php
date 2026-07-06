<?php

return apply_filters(
    'dokan_paystack_admin_gateway_settings', [
		'enabled' => [
			'title'       => __( 'Enable/Disable', 'dokan' ),
			'type'        => 'checkbox',
			'label'       => __( 'Enable Dokan Paystack', 'dokan' ),
			'default'     => 'no',
		],
		'title' => [
			'title'       => __( 'Title', 'dokan' ),
			'type'        => 'text',
			'class'       => 'input-text regular-input ',
			'description' => __( 'This controls the title which the user sees during checkout.', 'dokan' ),
			'default'     => __( 'Pay with Paystack', 'dokan' ),
			'desc_tip'    => true,
		],
		'description' => [
			'title'       => __( 'Description', 'dokan' ),
			'type'        => 'textarea',
			'description' => __( 'This controls the description which the user sees during checkout.', 'dokan' ),
			'default'     => __( 'Pay securely by Credit or Debit card or Internet Banking through Paystack.', 'dokan' ),
			'desc_tip'    => true,
		],
		'payment_page' => [
			'title'       => __( 'Payment Option', 'dokan' ),
			'type'        => 'select',
			'description' => __( 'Popup shows the payment popup on the page while Redirect will redirect the customer to Paystack to make payment.', 'dokan' ),
			'default'     => 'inline',
			'options'     => [
				'inline'    => __( 'Popup', 'dokan' ),
				'redirect'  => __( 'Redirect', 'dokan' ),
			],
		],
		'seller_pays_the_processing_fee' => [
			'title'       => __( 'Seller pays the processing fee', 'dokan' ),
			'label'       => __( 'If activated, Fees will be charged according to the share of the main account and subaccounts.', 'dokan' ),
			'type'        => 'checkbox',
			'description' => sprintf( // translators: %s: URL to Paystack documentation
                __(
					'By default, the Admin/Site Owner covers the Paystack processing fees. When enabled, the fees will be proportionally charged to the main account and subaccounts based on their earnings. For more details, see the full documentation <a href="%s" target="_blank">here</a>.',
					'dokan'
				),
				'https://paystack.com/docs/payments/multi-split-payments/#fees-on-multi-split'
            ),
			'default'     => 'yes',
		],
		'test_mode' => [
			'title'       => __( 'Test mode', 'dokan' ),
			'type'        => 'checkbox',
			'label'       => __( 'Enable Test mode', 'dokan' ),
			'default'     => 'no',
			/* translators: 1: Paystack dashboard developer url */
			'description' => sprintf( __( 'Test mode can be used to test payments. Sign up for a developer account <a href="%s">here</a>.', 'dokan' ), 'https://dashboard.paystack.com/' ),
		],
		'live_api_key' => [
			'title'       => __( 'Live Public Key', 'dokan' ),
			'type'        => 'text',
			'class'       => 'input-text regular-input ',
			'description' => wp_kses(
				sprintf(
					/* translators: 1: Paystack API Key Link */
					__( 'The Live Public Key can be generated from <a href="%1$s" target="_blank">here</a>.', 'dokan' ),
					'https://dashboard.paystack.com/#/settings/developers'
				),
				[
					'a'      => [
						'href'   => true,
						'target' => true,
					],
				]
			),
			'placeholder' => 'sk_xxx',
		],
		'live_api_secret' => [
			'title'       => __( 'Live Secret Key', 'dokan' ),
			'type'        => 'password',
			'class'       => 'input-text regular-input ',
			'description' => wp_kses(
				sprintf(
					/* translators: 1: Paystack API Key Link */
					__( 'The Live Secret Key can be generated from <a href="%1$s" target="_blank">here</a>.', 'dokan' ),
					'https://dashboard.paystack.com/#/settings/developers'
				),
				[
					'a'      => [
						'href'   => true,
						'target' => true,
					],
				]
			),
			'placeholder' => '****',
		],
		'test_api_key' => [
			'title'       => __( 'Test Public Key', 'dokan' ),
			'type'        => 'text',
			'class'       => 'input-text regular-input ',
			'description' => wp_kses(
				sprintf(
					/* translators: 1: Paystack API Key Link */
					__( 'The Test Public Key can be generated from <a href="%1$s" target="_blank">here</a>.', 'dokan' ),
					'https://dashboard.paystack.com/#/settings/developers'
				),
				[
					'a'      => [
						'href'   => true,
						'target' => true,
					],
				]
			),
			'placeholder' => 'sk_xxx',
		],
		'test_api_secret' => [
			'title'       => __( 'Test Secret Key', 'dokan' ),
			'type'        => 'password',
			'class'       => 'input-text regular-input ',
			'description' => wp_kses(
				sprintf(
					/* translators: 1: Paystack API Key Link */
					__( 'The Test Secret Key can be generated from <a href="%1$s" target="_blank">here</a>.', 'dokan' ),
					'https://dashboard.paystack.com/#/settings/developers'
				),
				[
					'a'      => [
						'href'   => true,
						'target' => true,
					],
				]
			),
			'placeholder' => '****',
		],
		'debug' => [
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
	]
);
