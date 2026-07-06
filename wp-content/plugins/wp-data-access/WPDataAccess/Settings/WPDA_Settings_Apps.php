<?php

namespace WPDataAccess\Settings {

    use WPDataAccess\WPDA;

    class WPDA_Settings_Apps extends WPDA_Settings {

        /**
         * Add back-end tab content
         *
         * See class documentation for flow explanation.
         *
         * @since   5.5.71
         */
        protected function add_content() {

            if ( isset( $_REQUEST['action'] ) ) {
                $action = sanitize_text_field(wp_unslash($_REQUEST['action'])); // input var okay.

                // Security check.
                $wp_nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : ''; // input var okay.
                if ( ! wp_verify_nonce( $wp_nonce, 'wpda-apps-settings' ) ) {
                    wp_die( __( 'ERROR: Not authorized', 'wp-data-access' ) );
                }

                if ( 'save' === $action ) {

                    if (
                        isset( $_REQUEST['scroll_offset'] ) &&
                        '' !== $_REQUEST['scroll_offset']
                    ) {
                        $scroll_offset = sanitize_text_field( wp_unslash( $_REQUEST['scroll_offset'] ) ); // input var okay.
                        update_option( 'wpda_apps_scroll_offset', $scroll_offset );
                    }

                } elseif ( 'setdefaults' === $action ) {

                    WPDA::set_option( WPDA::OPTION_APPS_SCROLL_OFFSET );

                }
            }

            $scroll_offset = WPDA::get_option( WPDA::OPTION_APPS_SCROLL_OFFSET );

            ?>
            <form id="wpda_settings_backend" method="post"
                  action="?page=<?php echo esc_attr( $this->page ); ?>&tab=apps">
                <table class="wpda-table-settings">
                    <tr>
                        <th><?php echo __( 'Scroll Offset', 'wp-data-access' ); ?></th>
                        <td>
                            <input
                                type="number" step="1" min="0" max="999" name="scroll_offset" maxlength="3"
                                value="<?php echo esc_attr( $scroll_offset ); ?>">
                        </td>
                    </tr>
                </table>
                <div class="wpda-table-settings-button">
                    <input type="hidden" name="action" value="save"/>
                    <button type="submit" class="button button-primary">
                        <i class="fas fa-check wpda_icon_on_button"></i>
                        <?php echo __( 'Save Apps Settings', 'wp-data-access' ); ?>
                    </button>
                    <a href="javascript:void(0)"
                       onclick="if (confirm('<?php echo __( 'Reset to defaults?', 'wp-data-access' ); ?>')) {
                           jQuery('input[name=&quot;action&quot;]').val('setdefaults');
                           jQuery('#wpda_settings_backend').trigger('submit')
                           }"
                       class="button">
                        <i class="fas fa-times-circle wpda_icon_on_button"></i>
                        <?php echo __( 'Reset Apps Settings To Defaults', 'wp-data-access' ); ?>
                    </a>
                </div>
                <?php wp_nonce_field( 'wpda-apps-settings', '_wpnonce', false ); ?>
            </form>
            <?php

        }
    }

}