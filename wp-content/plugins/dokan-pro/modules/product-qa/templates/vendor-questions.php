<?php
/**
 * Dokan Product QA main Template
 *
 * @since 3.11.0
 */

defined( 'ABSPATH' ) || exit;
?>

<?php do_action( 'dokan_dashboard_wrap_start' ); ?>

    <div class="dokan-dashboard-wrap">

        <?php

        /**
         *  dokan_dashboard_content_before hook
         *
         *  @hooked get_dashboard_side_navigation
         *
         *  @since 3.11.0
         */
        do_action( 'dokan_dashboard_content_before' );
        do_action( 'dokan_product_qa_content_before' );

        ?>

        <div class="dokan-dashboard-content dokan-orders-content">

            <?php

            /**
             *  dokan_product_qa_content_inside_before hook
             *
             *  @hooked show_seller_enable_message
             *
             *  @since 3.11.0
             */
            do_action( 'dokan_product_qa_content_inside_before' );
            ?>


            <article class="dokan-orders-area dokan-product-qa-listing-area">

                <?php

                /**
                 *  Added dokan_product_qa_inside_content Hook
                 *
                 *
                 *  @since 3.11.0
                 */
                do_action( 'dokan_product_qa_inside_content' );

                ?>


            </article>


            <?php

            /**
             *  dokan_product_qa_content_inside_after hook
             *
             *  @since 3.11.0
             */
            do_action( 'dokan_product_qa_content_inside_after' );
            ?>

        </div> <!-- #primary .content-area -->

        <?php

        /**
         *  dokan_dashboard_content_after hook
         *  dokan_product_qa_content_after hook
         *
         *  @since 3.11.0
         */
        do_action( 'dokan_dashboard_content_after' );
        do_action( 'dokan_product_qa_content_after' );

        ?>

    </div><!-- .dokan-dashboard-wrap -->

<?php do_action( 'dokan_dashboard_wrap_end' ); ?>
