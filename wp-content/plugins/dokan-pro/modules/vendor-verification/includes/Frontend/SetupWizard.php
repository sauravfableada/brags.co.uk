<?php

namespace WeDevs\DokanPro\Modules\VendorVerification\Frontend;

use WeDevs\DokanPro\Modules\VendorVerification\Models\VerificationMethod;

defined( 'ABSPATH' ) || exit();

/**
 * Verification Setup Wizard.
 *
 * @since 3.11.1
 */
class SetupWizard {

    /**
     * SetupWizard class constructor.
     *
     * @since 3.11.1
     */
    public function __construct() {
        add_filter( 'dokan_seller_wizard_steps', [ $this, 'add_wizard_steps' ] );
        add_action( 'dokan_setup_wizard_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'dokan_setup_wizard_enqueue_scripts', 'wp_print_media_templates' );
        add_action( 'dokan_seller_wizard_after_store_setup_form', [ $this, 'make_store_step_required' ] );
    }

    /**
     * Adds a wizard step.
     *
     * @since 3.11.1
     *
     * @param array $steps Step array.
     *
     * @return array
     */
    public function add_wizard_steps( array $steps ): array {
        $verification_methods = ( new VerificationMethod() )->query( [ 'status' => VerificationMethod::STATUS_ENABLED ] );

        if ( ! $verification_methods ) {
            return $steps;
        }

        $offset = array_search( 'next_steps', array_keys( $steps ), true );

        $verification_step = [
            'verifications' => [
                'name'    => __( 'Verifications', 'dokan' ),
                'view'    => [ $this, 'dokan_setup_verifications_view' ],
                'handler' => '',
            ],
        ];

        // todo: replace with existing method `dokan_array_insert_after`
        return array_merge(
            array_slice( $steps, 0, $offset ),
            $verification_step,
            array_slice( $steps, $offset, null )
        );
    }

    /**
     * Verification template views.
     *
     * @since 3.11.1
     *
     * @return void
     */
    public function dokan_setup_verifications_view() {
        $next = add_query_arg(
            [
                'step'            => 'next_steps',
                '_admin_sw_nonce' => wp_create_nonce( 'dokan_admin_setup_wizard_nonce' ),
            ]
        );
        dokan_get_template_part(
            'vendor-verification/setup-wizard', '', [
                'is_vendor_verification' => true,
                'next_step_link'         => $next,
            ]
        );
    }

    /**
     * Enqueue style and script for setup wizard.
     *
     * @since 3.11.1
     *
     * @return void
     */
    public function enqueue_scripts() {
        /**
         * @var \WeDevs\Dokan\Assets $dokan_assets Dokan Assets class.
         */
        $dokan_assets = dokan()->scripts;

        add_filter( 'dokan_force_load_extra_args', '__return_true' );

        wp_enqueue_script( 'jquery-ui-datepicker' );
        $dokan_assets->enqueue_front_scripts();
        $dokan_assets->load_dokan_global_scripts();
        wp_enqueue_media();
        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_script( 'dokan-tooltip' );
        wp_enqueue_script( 'dokan-form-validate' );
        wp_enqueue_script( 'dokan-script' );
        dokan_pro()->module->vendor_verification->assets->enqueue_scripts();
    }

    /**
     * Make store step required.
     * This is required for the vendor verification setup wizard.
     *
     * @since 3.11.1
     *
     * @return void
     */
    public function make_store_step_required() {
        $required_verifications = ( new VerificationMethod() )->query(
            [
				'status' => VerificationMethod::STATUS_ENABLED,
				'required' => true,
                'limit' => 1,
			]
        );

        $required_verification = reset( $required_verifications );

        if ( ! $required_verification ) {
            return;
        }

        ?>
        <script type="text/javascript">
            (function($){
                $(function() {
                    let skip_button = $('.store-step-skip-btn');
                    skip_button.hide();
                });
            })(jQuery);
        </script>
        <?php
    }
}
