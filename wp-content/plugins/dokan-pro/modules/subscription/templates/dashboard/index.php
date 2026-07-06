<?php
/**
 * Dashboard Subscription Index Template.
 *
 * @since 3.13.0
 *
 * @var string $link               Link URL for Dashboard Subscription Page.
 * @var string $active_tab         Active Tab.
 * @var int    $user_id            User ID.
 * @var object $subscription_packs Subscription Packs.
 */

use DokanPro\Modules\Subscription\Helper;
?>

<div class="dokan-dashboard-subscription-wrap">
    <ul class="dokan_tabs">
        <li class="<?php echo 'subscription_packs' === $active_tab ? 'active' : ''; ?>">
            <a href="<?php echo esc_url( add_query_arg( [ 'tab' => 'subscription_packs' ], $link ) ); ?>"><?php esc_html_e( 'Subscription Packs', 'dokan' ); ?></a>
        </li>
        <li class="<?php echo 'subscription_orders' === $active_tab ? 'active' : ''; ?>">
            <a href="<?php echo esc_url( add_query_arg( [ 'tab' => 'subscription_orders' ], $link ) ); ?>"><?php esc_html_e( 'Subscription Orders', 'dokan' ); ?></a>
        </li>
    </ul>

    <div id="dokan_tabs_container">
        <?php if ( 'subscription_orders' === $active_tab ) : ?>
            <div class="tab-pane active" id="dokan-dashboard-subscription-orders">
                <?php
                $page     = ! empty( $_GET['pagenum'] ) ? absint( wp_unslash( $_GET['pagenum'] ) ) : 1;
                $orders   = Helper::get_paginated_subscription_orders_by_vendor_id( dokan_get_current_user_id(), $page );

                dokan_get_template_part(
                    'dashboard/order-listing', '',
                    [
                        'is_subscription'     => true,
                        'subscription_orders' => $orders,
                    ]
                );
                ?>
            </div>
        <?php else : ?>
            <div class="tab-pane active" id="dokan-dashboard-subscription-packs">
                <?php
                dokan_get_template_part(
                    'dashboard/pack-listing', '',
                    [
                        'is_subscription'    => true,
                        'user_id'            => $user_id,
                        'subscription_packs' => $subscription_packs,
                    ]
                );
                ?>
            </div>
        <?php endif ?>
    </div>
</div>
<?php

