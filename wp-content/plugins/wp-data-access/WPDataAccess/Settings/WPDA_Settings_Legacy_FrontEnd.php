<?php

namespace WPDataAccess\Settings;

use WPDataAccess\Utilities\WPDA_Message_Box;
use WPDataAccess\WPDA;
class WPDA_Settings_Legacy_FrontEnd extends WPDA_Settings_Legacy_Page {
    /**
     * Available UI themes
     */
    const UI_THEMES = array(
        'ui-darkness',
        'ui-lightness',
        'swanky-purse',
        'sunny',
        'start',
        'smoothness',
        'black-tie',
        'blitzer',
        'cupertino',
        'dark-hive',
        'dot-luv',
        'eggplant',
        'excite-bike',
        'flick',
        'hot-sneaks',
        'humanity',
        'le-frog',
        'mint-choc',
        'overcast',
        'pepper-grinder',
        'redmond',
        'south-street',
        'trontastic',
        'vader'
    );

    public function show() {
        if ( isset( $_REQUEST['action'] ) ) {
            $action = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) );
            // input var okay.
            // Security check.
            $wp_nonce = ( isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '' );
            // input var okay.
            if ( !wp_verify_nonce( $wp_nonce, 'wpda-front-end-settings-' . WPDA::get_current_user_login() ) ) {
                wp_die( __( 'ERROR: Not authorized', 'wp-data-access' ) );
            }
            if ( 'save' === $action ) {
                WPDA::set_option( WPDA::OPTION_FE_PAGINATION, ( isset( $_REQUEST['pagination'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['pagination'] ) ) : null ) );
                if ( isset( $_REQUEST['ui_theme'] ) ) {
                    WPDA::set_option( WPDA::WPDA_DT_UI_THEME_DEFAULT, sanitize_text_field( wp_unslash( $_REQUEST['ui_theme'] ) ) );
                }
            } elseif ( 'setdefaults' === $action ) {
                // Set all front-end settings back to default
                WPDA::set_option( WPDA::OPTION_FE_PAGINATION );
                WPDA::set_option( WPDA::WPDA_DT_UI_THEME_DEFAULT );
            }
            $msg = new WPDA_Message_Box(array(
                'message_text' => __( 'Settings saved', 'wp-data-access' ),
            ));
            $msg->box();
        }
        // Get options
        $pagination = WPDA::get_option( WPDA::OPTION_FE_PAGINATION );
        $ui_theme_default = WPDA::get_option( WPDA::WPDA_DT_UI_THEME_DEFAULT );
        ?>

            <form id="wpda_settings_frontend" method="post"
                  action="?page=<?php 
        echo esc_attr( $this->page );
        ?>&tab=legacy&vtab=frontend">
                <table class="wpda-table-settings">
                    <tr style="border-top: 1px solid #ccc">
                        <th><?php 
        echo __( 'Default pagination value', 'wp-data-access' );
        ?></th>
                        <td>
                            <input
                                type="number" step="1" min="1" max="999" name="pagination" maxlength="3"
                                value="<?php 
        echo esc_attr( $pagination );
        ?>">
                            <div style="padding-top:10px">
                                Only for shortcode <strong>wpdadiehard</strong>
                            </div>
                        </td>
                    </tr>
                    <?php 
        ?>
                </table>
                <div class="wpda-table-settings-button">
                    <input type="hidden" name="action" value="save"/>
                    <button type="submit" class="button button-primary">
                        <i class="fas fa-check wpda_icon_on_button"></i>
                        <?php 
        echo __( 'Save Front-end Settings', 'wp-data-access' );
        ?>
                    </button>
                    <a href="javascript:void(0)"
                       onclick="if (confirm('<?php 
        echo __( 'Reset to defaults?', 'wp-data-access' );
        ?>')) {
                           jQuery('input[name=&quot;action&quot;]').val('setdefaults');
                           jQuery('#wpda_settings_frontend').trigger('submit')
                           }"
                       class="button">
                        <i class="fas fa-times-circle wpda_icon_on_button"></i>
                        <?php 
        echo __( 'Reset Front-end Settings To Defaults', 'wp-data-access' );
        ?>
                    </a>
                </div>
                <?php 
        wp_nonce_field( 'wpda-front-end-settings-' . WPDA::get_current_user_login(), '_wpnonce', false );
        ?>
            </form>

            <?php 
    }

}
