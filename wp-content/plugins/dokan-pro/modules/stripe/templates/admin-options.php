<h3><?php echo esc_html__( 'Stripe Connect', 'dokan' ); ?></h3>
<p><?php echo esc_html__( 'Stripe works by adding credit card fields on the checkout and then sending the details to Stripe for verification.', 'dokan' ); ?></p>
<p>
    <?php
    // Collect test mode endpoint if user on test mode.
    $stripe_env = \WeDevs\DokanPro\Modules\Stripe\Helper::is_test_mode() ? '/test' : '';

    /**
     * Actions before URL translation in Dokan for WPML compatibility.
     *
     * @since 3.13.0
     */
    do_action( 'dokan_disable_url_translation' );

    echo wp_kses(
        sprintf(
            /* translators: 1) Webhook Navigation URI, 2) OAuth URI */
            __( 'Set your authorize redirect uri <code>%1$s</code><span class="dokan-copy-to-clipboard" data-copy="%1$s"></span>in your Stripe <a href="%2$s" target="_blank">application settings</a> for Redirects.', 'dokan' ),
            dokan_get_navigation_url( 'settings/payment-manage-dokan-stripe-connect' ),
            "https://dashboard.stripe.com{$stripe_env}/settings/connect/onboarding-options/oauth"
        ),
        [
            'a'    => [
                'href'   => true,
                'target' => true,
            ],
            'code' => [],
            'span' => [
                'class' => true,
                'data-copy' => true,
            ],
        ]
    );

    /**
     * Actions after URL translation is re-enabled in Dokan for WPML compatibility.
     *
     * @since 3.13.0
     */
    do_action( 'dokan_enable_url_translation' );
    ?>
</p>
<p>
    <?php
    /**
     * Actions before URL translation in Dokan for WPML compatibility.
     *
     * @since 3.13.0
     */
    do_action( 'dokan_disable_url_translation' );

    echo wp_kses(
        sprintf(
            /* translators: 1) Webhook Navigation URI, 2) Webhook URL */
            __( 'Recurring subscription requires webhooks to be configured. Go to <a href="%1$s" target="_blank">webhook</a> and set your webhook url <code>%2$s</code><span class="dokan-copy-to-clipboard" data-copy="%2$s"></span> (if not automatically set). Otherwise recurring payment will not work automatically.', 'dokan' ),
            "https://dashboard.stripe.com{$stripe_env}/webhooks",
            home_url( 'wc-api/dokan_stripe' )
        ),
        [
            'a'    => [
                'href'   => true,
                'target' => true,
            ],
            'code' => [],
            'span' => [
                'class' => true,
                'data-copy' => true,
            ],
        ]
    );

    /**
     * Actions after URL translation is re-enabled in Dokan for WPML compatibility.
     *
     * @since 3.13.0
     */
    do_action( 'dokan_enable_url_translation' );
    ?>
</p>
<table class="form-table">
    <?php $gateway->generate_settings_html(); ?>
</table>
