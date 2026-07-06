<?php

use WeDevs\DokanPro\Modules\VendorVerification\Models\VerificationMethod;

extract( $data, EXTR_SKIP );

$title = apply_filters( 'widget_title', $instance['title'] );

echo $before_widget;

if ( ! empty( $title ) ) {
    echo $before_title . $title . $after_title;
}
?>
<div id="dokan-verification-list">
    <ul class="fa-ul">
        <?php

        /**
         * @var VerificationMethod[] $methods Verified methods.
         */
        foreach ( $methods as $key => $item ) {
            $widget->print_item( 'verification_method', $item );
        }

        if ( ! empty( $store_info['dokan_verification'] ) ) {
            foreach ( $store_info['dokan_verification'] as $key => $item ) {
                $widget->print_item( $key, $item );
            }
        }
        ?>
    </ul>
</div>
<?php
echo $after_widget;
