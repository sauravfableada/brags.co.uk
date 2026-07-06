<?php
defined( 'ABSPATH' ) || exit;
?>
<ol class="dokan-order-shipment-notes-updates dokan-order-updates commentlist notes">
    <?php foreach ( $shipment_timeline as $note ) : ?>
        <li class="dokan-order-update comment note">
            <div class="dokan-order-update-inner comment_container">
                <div class="dokan-order-update-text comment-text">
                    <p class="dokan-order-update-meta meta"><?php echo esc_html( dokan_format_datetime( $note->comment_date ) ); ?></p>
                    <div class="dokan-order-update-description description">
                        <?php echo wpautop( wptexturize( $note->comment_content ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>
                    <div class="clear"></div>
                </div>
                <div class="clear"></div>
            </div>
        </li>
    <?php endforeach; ?>
</ol>
