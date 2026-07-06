<?php
$new_dashboard = dokan_get_navigation_url( '', true );
$zone_link = dokan_get_navigation_url( 'settings/shipping' );

// trim last '/' in the new dashboard
$new_dashboard = rtrim( $new_dashboard, '/' );
$is_new_url = str_starts_with( $zone_link, $new_dashboard );

if ( ! $is_new_url ) {
    // Here the `zone` text in the url is hardcoded, and it's not translatable.'
    $zone_link = $zone_link . '#/zone/' . $zone_id;
} else {
    $zone_link = $zone_link . '/' . $zone_id;
}
?>
<div class="dokan-text-left">
    <p>&larr; <a href="<?php echo esc_url( $zone_link  ); ?>"><?php esc_html_e( 'Back to Zone', 'dokan' ); ?></a></p>
</div>
