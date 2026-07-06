<?php

defined( 'ABSPATH' ) || exit;

/**
 * Dokan Manage Reviews Template.
 *
 * @since 3.7.13
 *
 * @package dokan
 */

 $current_user = wp_get_current_user();
?>

<form id="dokan_comments-form" action="" method="post">
    <div class="dokan-form-group">
        <select name="comment_status">
            <option value="none"><?php esc_html_e( 'Bulk Actions', 'dokan' ); ?></option>
            <?php
            if ( $comment_status === 'hold' ) {
                ?>
                <option value="approve"><?php esc_html_e( 'Mark Approve', 'dokan' ); ?></option>
                <option value="spam"><?php esc_html_e( 'Mark Spam', 'dokan' ); ?></option>

                <?php 
                if (!in_array('seller', $current_user->roles)){
                    ?>
                    <option value="trash"><?php esc_html_e( 'Mark Trash', 'dokan' ); ?></option>
                    <?php
                }
                if (in_array('seller', $current_user->roles)){
                    ?>
                    <option value="request_removal"><?php esc_html_e( 'Report & Request Removal', 'dokan' ); ?></option>
                    <?php
                }
                ?>
                
            <?php } elseif ( $comment_status === 'spam' ) { ?>
                <option value="approve"><?php esc_html_e( 'Mark Not Spam', 'dokan' ); ?></option>
                <option value="delete"><?php esc_html_e( 'Delete permanently', 'dokan' ); ?></option>
            <?php } elseif ( $comment_status === 'trash' ) { ?>
                <option value="approve"><?php esc_html_e( 'Restore', 'dokan' ); ?></option>
                <option value="delete"><?php esc_html_e( 'Delete permanently', 'dokan' ); ?></option>
            <?php } else { ?>
                <option value="hold"><?php esc_html_e( 'Mark Pending', 'dokan' ); ?></option>
                <option value="spam"><?php esc_html_e( 'Mark Spam', 'dokan' ); ?></option>
                <?php 
                if (!in_array('seller', $current_user->roles)){
                    ?>
                    <option value="trash"><?php esc_html_e( 'Mark Trash', 'dokan' ); ?></option>
                    <?php
                }
                if (in_array('seller', $current_user->roles)){
                    ?>
                    <option value="request_removal"><?php esc_html_e( 'Report & Request Removal', 'dokan' ); ?></option>
                    <?php
                }
                ?>
                
                <?php
            }
            ?>
        </select>

        <?php wp_nonce_field( 'dokan_comment_nonce_action', 'dokan_comment_nonce' ); ?>

        <input type="submit" value="<?php esc_html_e( 'Apply', 'dokan' ); ?>" class="dokan-btn dokan-btn-sm" name="comt_stat_sub">
    </div>
