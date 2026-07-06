<?php
/**
 *  Profile Progressbar template
 *
 *  @since 2.4
 *
 *  @package dokan
 */
?>
<div class="dokan-panel dokan-panel-default dokan-profile-completeness">
    <div class="dokan-panel-body">
    <span class="fa fa-times dokan-right" style="margin-left: 10px; cursor: pointer;" id="dokan-profile-progressbar-full-close" data-nonce="<?php echo wp_create_nonce( 'dokan_user_closed_progressbar' ); ?>"></span>
    <div class="dokan-progress">
        <div class="dokan-progress-bar dokan-progress-bar-info dokan-progress-bar-striped" role="progressbar"
             aria-valuenow="50" aria-valuemin="0" aria-valuemax="100" style="width:<?php echo $progress ?>%">
            <?php echo $progress . __( '% Profile complete', 'dokan' ) ?>
        </div>
    </div>
    <div class="dokan-alert dokan-alert-info dokan-panel-alert"><?php echo wp_kses_post( dokan_progressbar_translated_string( $next_todo, $value, $progress ) ); ?>
        <?php if ( $progress >= 100 ) : ?>
        <sapn class="fa fa-times dokan-right" id="dokan-profile-progressbar-closer" data-nonce="<?php echo wp_create_nonce( 'dokan_user_closed_progressbar' ); ?>"></sapn>
        <?php endif; ?>
    </div>
   </div>
</div>
