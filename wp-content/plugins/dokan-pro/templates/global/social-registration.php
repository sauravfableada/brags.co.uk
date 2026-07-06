<div class="jssocials-shares">
<?php foreach ( $providers as $key => $provider ) : ?>
    <div class="jssocials-share jssocials-share-<?php echo esc_attr( $key ); ?>">
        <?php
        $query_args = [
            'vendor_social_reg' => $key,
            'is_checkout'       => is_checkout(),
        ];
        ?>
        <a href="<?php echo add_query_arg( $query_args, $base_url ); ?>" class="jssocials-share-link">
            <i class="fab <?php echo esc_attr( $provider['icon'] ); ?> jssocials-share-logo"></i>
        </a>
    </div>
<?php endforeach; ?>
</div>
