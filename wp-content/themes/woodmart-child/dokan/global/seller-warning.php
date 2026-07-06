<div class="dokan-alert dokan-alert-warning">
    <strong><?php esc_html_e( 'Congratulations!', 'dokan-lite' ); ?></strong>
    <?php
    echo wp_kses(
        sprintf(
            /* translators: %s is the opening and closing anchor tag */
            __( 'You’re approved to Sell on Brags! %sPlease choose a Brags Selling Plan here to Start Selling%s', 'dokan-lite' ),
            '<a href="' . esc_url( site_url( '/dashboard/new/#/subscription?tab=packs' ) ) . '">',
            '</a>'
        ),
        array(
            'a' => array(
                'href' => array(),
            ),
        )
    );
    ?>
</div>
