<?php
/**
 * Admin View: Header
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
</div>
</div>

<?php
/**
 *  Dokan_dashboard_content_inside_after hook.
 *
 *  @since 2.4
 */
do_action( 'dokan_dashboard_content_inside_after' );
?>


</div><!-- .dokan-dashboard-content -->

<?php
/**
 *  Dokan_dashboard_content_after hook.
 *
 *  @since 2.4
 */
do_action( 'dokan_dashboard_content_after' );
?>

</div><!-- .dokan-dashboard-wrap -->

<?php do_action( 'dokan_dashboard_wrap_end' ); ?>

<style>
    .woocommerce-exporter-wrapper .wc-progress-steps li.done, .woocommerce-importer-wrapper .wc-progress-steps li.done, .woocommerce-progress-form-wrapper .wc-progress-steps li.done{
        border-color: #fd8f6f;
        color: #fd8f6f;
    }
    .woocommerce-exporter-wrapper .wc-progress-steps li.done::before, .woocommerce-importer-wrapper .wc-progress-steps li.done::before, .woocommerce-progress-form-wrapper .wc-progress-steps li.done::before,.woocommerce-progress-form-wrapper .wc-progress-steps li.active {
        border-color: #fd8f6f;
        color: #fd8f6f;
    }
    .woocommerce-exporter-wrapper .wc-progress-steps li.active::before, .woocommerce-importer-wrapper .wc-progress-steps li.active::before, .woocommerce-progress-form-wrapper .wc-progress-steps li.active::before {
        border-color: #fd8f6f;
    }
    .dokan-dashboard-wrap .dashboard-content-area .woocommerce-progress-form-wrapper .wc-progress-steps li.done::before {
        background-color: #fff !important;
    }
    .dokan-dashboard-wrap .dashboard-content-area .wc-actions button[type='submit'],
    .dokan-dashboard-wrap .dashboard-content-area .woocommerce-importer .wc-actions a.button,
    .dokan-dashboard-wrap .dashboard-content-area .woocommerce-importer .wc-actions a.button:hover {
        box-shadow: none;
        text-shadow: none;
    }
    .woocommerce-importer-advanced.hidden {
        display: none;
    }
</style>
