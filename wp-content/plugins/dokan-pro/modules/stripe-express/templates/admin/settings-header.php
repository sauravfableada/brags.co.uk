<h2>
    <?php
    echo esc_html( $gateway->get_method_title() );
    wc_back_link( __( 'Return to payments', 'dokan' ), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) );
    ?>
</h2>

<?php echo wp_kses_post( wpautop( $gateway->get_method_description() ) ); ?>

<p>
    <?php
    // Collect test mode endpoint if user on test mode.
    $stripe_env = WeDevs\DokanPro\Modules\StripeExpress\Support\Settings::is_test_mode() ? '/test' : '';

    echo wp_kses(
        sprintf(
            /* translators: 1) formatted payment settings url, 2) opening anchor tag stripe dashboard url, 3) closing anchor tag */
            __( 'Set your authorize redirect uri %1$s in your Stripe %2$sapplication settings%3$s for Redirects.', 'dokan' ),
            sprintf( '<code>%s</code><span class="dokan-copy-to-clipboard" data-copy="%s"></span>', $dashboard_url, $dashboard_url ),
            "<a href='https://dashboard.stripe.com{$stripe_env}/settings/connect/onboarding-options/oauth' target='_blank'>",
            '</a>'
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
    )
    ?>
</p>

<table class="form-table">
    <?php $gateway->generate_settings_html(); ?>
</table>
