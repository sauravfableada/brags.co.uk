<?php
/**
 * Stripe Express Vendor Gateway Settings Template
 * This template can be overridden by copying it to yourtheme/dokan/stripe-express/vendor-gateway-settings.php.
 *
 * @package DokanPro
 *
 * @var int $user_id User ID.
 * @var WeDevs\DokanPro\Modules\StripeExpress\Processors\User $stripe_account Stripe account object.
 * @var string $gateway_title Gateway title.
 * @var string $current_site_name Current site name.
 * @var array<string,string> $address_data Vendor's store address data.
 * @var array<string,string> $supported_countries Supported countries list.
 * @var array<string> $restricted_countries Restricted countries list.
 */
?>

<?php do_action( 'dokan_stripe_express_vendor_settings_before', $user_id ); ?>

<div id="dokan-stripe-express-payment">
    <div class="dokan-alert dokan-alert-success dokan-text-middle signup-message" id="dokan-stripe-express-signup-message"></div>
    <div class="dokan-alert dokan-alert-danger dokan-text-middle signup-message" id="dokan-stripe-express-signup-error"></div>

    <?php if ( ! empty( $stripe_account->get_account_id() ) ) : ?>
        <?php if ( $stripe_account->is_connected() ) : ?>
            <div class="dokan-alert dokan-alert-success dokan-text-middle">
                <?php
                    echo wp_kses_post(
                        sprintf(
                            /* translators: 1) gateway title, 2) line break <br> tag, 3) merchant id, 4) line break <br> tag, 5) gateway title */
                            esc_html__( 'Your account is connected with %1$s.%2$sMerchant ID: %3$s.%4$sYou can visit your %5$s dashboard to track your payments and transactions.', 'dokan' ),
                            $gateway_title,
                            '<br>',
                            "<strong>{$stripe_account->get_account_id()}</strong>",
                            '<br>',
                            $gateway_title
                        )
                    );
                ?>
            </div>

            <div id="dokan-stripe-express-vendor-signup-message"></div>

            <button class="dokan-btn"
                id="dokan-stripe-express-dashboard-login">
                <?php esc_html_e( 'Visit Express Dashboard', 'dokan' ); ?>
            </button>

            <button class="dokan-btn dokan-btn-danger"
                id="dokan-stripe-express-account-disconnect">
                <?php esc_html_e( 'Disconnect', 'dokan' ); ?>
            </button>
        <?php else : ?>
            <div class="dokan-alert dokan-alert-warning dokan-text-middle">
                <?php
                    echo esc_html(
                        sprintf(
                            /* translators: gateway title */
                            __( 'You might not have completed the onboarding process or %s may take some time to verify your account. Click on the "Complete Onboarding" button once again to finish the verification process.', 'dokan' ),
                            $gateway_title
                        )
                    );
                ?>
            </div>

            <div id="dokan-stripe-express-vendor-signup-message"></div>

            <div id='dokan-stripe-express-vendor-onboarding-buttons'>
                <button class="dokan-btn dokan-btn-success" id="dokan-stripe-express-account-connect">
                    <?php esc_html_e( 'Complete Onboarding', 'dokan' ); ?>
                </button>
                <?php esc_html_e( 'Or', 'dokan' ); ?>
                <button class='dokan-btn dokan-btn-danger' id='dokan-stripe-express-account-cancel'>
                    <?php esc_html_e( 'Cancel Onboarding', 'dokan' ); ?>
                </button>
            </div>
        <?php endif; ?>
    <?php else : ?>

        <?php if ( ! empty( $address_data ) && ! empty( $address_data['country'] ) ) : ?>
            <?php if ( ! empty( $restricted_countries ) && in_array( $address_data['country'], $restricted_countries, true ) ) : ?>
                <div class="dokan-alert dokan-alert-danger dokan-text-middle">
                    <?php
                        echo esc_html(
                            sprintf(
                                /* translators: Current site name */
                                __( 'Your store is located in a country that is not supported by %s. Please contact with Admin.', 'dokan' ),
                                $current_site_name
                            )
                        );
                    ?>
                </div>
            <?php elseif ( ! empty( $supported_countries ) && ! array_key_exists( $address_data['country'], $supported_countries ) && ! $stripe_account->is_trashed() ) : ?>
                <div class="dokan-alert dokan-alert-danger dokan-text-middle">
                    <?php esc_html_e( 'Your store is located in a country that is not supported by Stripe.', 'dokan' ); ?>
                </div>
            <?php else : ?>
                <div class="dokan-alert dokan-alert-warning dokan-text-left" id="dokan-stripe-express-account-notice">
                    <?php
                    echo esc_html(
                        sprintf(
                        /* translators: gateway title */
                            __( 'Your account is not connected with %s. Click on the button below to sign up.', 'dokan' ),
                            $gateway_title
                        )
                    );
                    ?>
                </div>

                <?php if ( ! empty( $supported_countries ) && ! $stripe_account->is_trashed() ) : ?>
                    <div class="dokan-stripe-express-vendor-signup">
                        <?php

                        // Default country field args.
                        $vendor_country_field_args_default = [
                            'id'            => 'dokan_stripe_express_vendor_country',
                            'type'          => 'hidden',
                            'default'       => $address_data['country'],
                            'input_class'   => array( 'address-field' ),
                            'required'      => true,
                        ];

                        /**
                         * Filters the country field for vendor signup
                         *
                         * @since 3.11.2
                         *
                         * @param array<string,string>  $vendor_country_field   Country field data.
                         * @param int                   $user_id                Current logged-in user ID.
                         * @param array<string,string>  $supported_countries    Supported countries list.
                         * @param array<string,string>  $address_data           Vendor's store address data.
                         *
                         * @return array<string,string>
                         */
                        $vendor_country_field_args = apply_filters( 'dokan_stripe_express_vendor_onboard_country_field_args', $vendor_country_field_args_default, $user_id, $supported_countries, $address_data );

                        woocommerce_form_field( 'dokan_stripe_express_vendor_country', $vendor_country_field_args );

                        ?>
                    </div>
                <?php endif; ?>

                <div id="dokan-stripe-express-account-connect" data-user="<?php echo esc_attr( $user_id ); ?>">
                    <a href='#' class='stripe-connect slate' title="<?php esc_attr_e( 'Connect with Stripe', 'dokan' ); ?>">
                        <span><?php esc_html_e( 'Connect with', 'dokan' ); ?></span>
                    </a>
                </div>
            <?php endif; ?>
        <?php else : ?>
            <div class="dokan-alert dokan-alert-warning dokan-text-left">
                <?php echo esc_html__( 'Update your store address before proceeding to the Stripe Express onboarding process.', 'dokan' ); ?>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<?php do_action( 'dokan_stripe_express_vendor_settings_after', $user_id ); ?>
