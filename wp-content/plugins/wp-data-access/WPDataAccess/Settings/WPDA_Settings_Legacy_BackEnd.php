<?php

namespace WPDataAccess\Settings {

    use WPDataAccess\Utilities\WPDA_Message_Box;
    use WPDataAccess\WPDA;

    class WPDA_Settings_Legacy_BackEnd extends WPDA_Settings_Legacy_Page {

        public function show() {

            if ( isset( $_REQUEST['action'] ) ) {
                $action = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ); // input var okay.

                // Security check.
                $wp_nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : ''; // input var okay.
                if ( ! wp_verify_nonce( $wp_nonce, 'wpda-back-end-settings' ) ) {
                    wp_die( __( 'ERROR: Not authorized', 'wp-data-access' ) );
                }

                if ( 'save' === $action ) {
                    // Save options.
                    WPDA::set_option(
                        WPDA::OPTION_BE_VIEW_LINK,
                        isset( $_REQUEST['view_link'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['view_link'] ) ) : 'off' // input var okay.
                    );

                    WPDA::set_option(
                        WPDA::OPTION_BE_ALLOW_INSERT,
                        isset( $_REQUEST['allow_insert'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['allow_insert'] ) ) : 'off' // input var okay.
                    );
                    WPDA::set_option(
                        WPDA::OPTION_BE_ALLOW_UPDATE,
                        isset( $_REQUEST['allow_update'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['allow_update'] ) ) : 'off' // input var okay.
                    );
                    WPDA::set_option(
                        WPDA::OPTION_BE_ALLOW_DELETE,
                        isset( $_REQUEST['allow_delete'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['allow_delete'] ) ) : 'off' // input var okay.
                    );

                    WPDA::set_option(
                        WPDA::OPTION_BE_EXPORT_ROWS,
                        isset( $_REQUEST['export_rows'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['export_rows'] ) ) : 'off' // input var okay.
                    );
                    WPDA::set_option(
                        WPDA::OPTION_BE_EXPORT_VARIABLE_PREFIX,
                        isset( $_REQUEST['export_variable_rows'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['export_variable_rows'] ) ) : 'off' // input var okay.
                    );

                    WPDA::set_option(
                        WPDA::OPTION_BE_ALLOW_IMPORTS,
                        isset( $_REQUEST['allow_imports'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['allow_imports'] ) ) : 'off' // input var okay.
                    );

                    WPDA::set_option(
                        WPDA::OPTION_BE_CONFIRM_EXPORT,
                        isset( $_REQUEST['confirm_export'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['confirm_export'] ) ) : 'off' // input var okay.
                    );
                    WPDA::set_option(
                        WPDA::OPTION_BE_CONFIRM_VIEW,
                        isset( $_REQUEST['confirm_view'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['confirm_view'] ) ) : 'off' // input var okay.
                    );

                    WPDA::set_option(
                        WPDA::OPTION_BE_PAGINATION,
                        isset( $_REQUEST['pagination'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['pagination'] ) ) : null // input var okay.
                    );

                    WPDA::set_option(
                        WPDA::OPTION_BE_REMEMBER_SEARCH,
                        isset( $_REQUEST['remember_search'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['remember_search'] ) ) : 'off' // input var okay.
                    );

                    WPDA::set_option(
                        WPDA::OPTION_BE_INNODB_COUNT,
                        isset( $_REQUEST['innodb_count'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['innodb_count'] ) ) : 100000 // input var okay.
                    );

                    WPDA::set_option(
                        WPDA::OPTION_BE_DESIGN_MODE,
                        isset( $_REQUEST['design_mode'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['design_mode'] ) ) : 'basic' // input var okay.
                    );

                    WPDA::set_option(
                        WPDA::OPTION_BE_TEXT_WRAP_SWITCH,
                        isset( $_REQUEST['text_wrap_switch'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['text_wrap_switch'] ) ) : 'off' // input var okay.
                    );

                    WPDA::set_option(
                        WPDA::OPTION_BE_TEXT_WRAP,
                        isset( $_REQUEST['text_wrap'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['text_wrap'] ) ) : 400 // input var okay.
                    );

                    WPDA::set_option(
                        WPDA::OPTION_BE_HIDE_BUTTON_ICONS,
                        isset( $_REQUEST['hide_button_icons'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['hide_button_icons'] ) ) : 'off' // input var okay.
                    );
                } elseif ( 'setdefaults' === $action ) {
                    // Set all back-end settings back to default.
                    WPDA::set_option( WPDA::OPTION_BE_VIEW_LINK );
                    WPDA::set_option( WPDA::OPTION_BE_ALLOW_INSERT );
                    WPDA::set_option( WPDA::OPTION_BE_ALLOW_UPDATE );
                    WPDA::set_option( WPDA::OPTION_BE_ALLOW_DELETE );
                    WPDA::set_option( WPDA::OPTION_BE_EXPORT_ROWS );
                    WPDA::set_option( WPDA::OPTION_BE_EXPORT_VARIABLE_PREFIX );
                    WPDA::set_option( WPDA::OPTION_BE_ALLOW_IMPORTS );
                    WPDA::set_option( WPDA::OPTION_BE_CONFIRM_EXPORT );
                    WPDA::set_option( WPDA::OPTION_BE_CONFIRM_VIEW );
                    WPDA::set_option( WPDA::OPTION_BE_PAGINATION );
                    WPDA::set_option( WPDA::OPTION_BE_REMEMBER_SEARCH );
                    WPDA::set_option( WPDA::OPTION_BE_INNODB_COUNT );
                    WPDA::set_option( WPDA::OPTION_BE_DESIGN_MODE );
                    WPDA::set_option( WPDA::OPTION_BE_TEXT_WRAP_SWITCH );
                    WPDA::set_option( WPDA::OPTION_BE_TEXT_WRAP );
                    WPDA::set_option( WPDA::OPTION_BE_HIDE_BUTTON_ICONS );
                }

                $msg = new WPDA_Message_Box(
                    array(
                        'message_text' => __( 'Settings saved', 'wp-data-access' ),
                    )
                );
                $msg->box();
            }

            // Get options.
            $view_link = WPDA::get_option( WPDA::OPTION_BE_VIEW_LINK );

            $allow_insert = WPDA::get_option( WPDA::OPTION_BE_ALLOW_INSERT );
            $allow_update = WPDA::get_option( WPDA::OPTION_BE_ALLOW_UPDATE );
            $allow_delete = WPDA::get_option( WPDA::OPTION_BE_ALLOW_DELETE );

            $export_rows          = WPDA::get_option( WPDA::OPTION_BE_EXPORT_ROWS );
            $export_variable_rows = WPDA::get_option( WPDA::OPTION_BE_EXPORT_VARIABLE_PREFIX );

            $allow_imports = WPDA::get_option( WPDA::OPTION_BE_ALLOW_IMPORTS );

            $confirm_export = WPDA::get_option( WPDA::OPTION_BE_CONFIRM_EXPORT );
            $confirm_view   = WPDA::get_option( WPDA::OPTION_BE_CONFIRM_VIEW );

            $pagination = WPDA::get_option( WPDA::OPTION_BE_PAGINATION );

            $remember_search = WPDA::get_option( WPDA::OPTION_BE_REMEMBER_SEARCH );

            $innodb_count = WPDA::get_option( WPDA::OPTION_BE_INNODB_COUNT );

            $design_mode = WPDA::get_option( WPDA::OPTION_BE_DESIGN_MODE );

            $text_wrap_switch = WPDA::get_option( WPDA::OPTION_BE_TEXT_WRAP_SWITCH );
            $text_wrap        = WPDA::get_option( WPDA::OPTION_BE_TEXT_WRAP );

            $hide_button_icons = WPDA::get_option( WPDA::OPTION_BE_HIDE_BUTTON_ICONS );
            ?>

            <form id="wpda_settings_backend" method="post"
                  action="?page=<?php echo esc_attr( $this->page ); ?>&tab=legacy&vtab=backend">
                <table class="wpda-table-settings">
                    <tr style="border-top: 1px solid #ccc">
                        <th><?php echo __( 'Row access', 'wp-data-access' ); ?></th>
                        <td>
                            <label>
                                <input
                                    type="checkbox"
                                    name="view_link"
                                    <?php echo 'on' === $view_link ? 'checked' : ''; ?>
                                ><?php echo __( 'Add view link to list table', 'wp-data-access' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo __( 'Allow transactions?', 'wp-data-access' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="allow_insert"
                                    <?php echo 'on' === $allow_insert ? 'checked' : ''; ?> /><?php echo __( 'Allow insert', 'wp-data-access' ); ?>
                            </label>
                            <br/>
                            <label>
                                <input type="checkbox" name="allow_update"
                                    <?php echo 'on' === $allow_update ? 'checked' : ''; ?> /><?php echo __( 'Allow update', 'wp-data-access' ); ?>
                            </label>
                            <br/>
                            <label>
                                <input type="checkbox" name="allow_delete"
                                    <?php echo 'on' === $allow_delete ? 'checked' : ''; ?> /><?php echo __( 'Allow delete', 'wp-data-access' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo __( 'Allow exports?', 'wp-data-access' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="export_rows"
                                    <?php echo 'on' === $export_rows ? 'checked' : ''; ?> /><?php echo __( 'Allow row export', 'wp-data-access' ); ?>
                            </label>
                            <br/>
                            <label>
                                <input type="checkbox" name="export_variable_rows"
                                    <?php echo 'on' === $export_variable_rows ? 'checked' : ''; ?> /><?php echo __( 'Export with variable WP prefix', 'wp-data-access' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo __( 'Allow imports?', 'wp-data-access' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="allow_imports"
                                    <?php echo 'on' === $allow_imports ? 'checked' : ''; ?> /><?php echo __( 'Allow to import scripts from Data Explorer table pages', 'wp-data-access' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo __( 'Ask for confirmation?', 'wp-data-access' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="confirm_export"
                                    <?php echo 'on' === $confirm_export ? 'checked' : ''; ?> /><?php echo __( 'When starting export', 'wp-data-access' ); ?>
                            </label>
                            <br/>
                            <label>
                                <input type="checkbox" name="confirm_view"
                                    <?php echo 'on' === $confirm_view ? 'checked' : ''; ?> /><?php echo __( 'When viewing non WPDA table', 'wp-data-access' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo __( 'Default pagination value', 'wp-data-access' ); ?></th>
                        <td>
                            <input
                                type="number" step="1" min="1" max="999" name="pagination" maxlength="3"
                                value="<?php echo esc_attr( $pagination ); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo __( 'Search box', 'wp-data-access' ); ?></th>
                        <td>
                            <label>
                                <input
                                    type="checkbox"
                                    name="remember_search" <?php echo 'on' === $remember_search ? 'checked' : ''; ?>
                                ><?php echo __( 'Remember last search', 'wp-data-access' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo __( 'Max row count', 'wp-data-access' ); ?></th>
                        <td>
                            <input
                                type="number" step="1" min="1" max="999999" name="innodb_count" maxlength="3"
                                value="<?php echo esc_attr( $innodb_count ); ?>">
                            <p>
                                <strong>This works for InnoDB tables and views only!</strong>
                            </p>
                            <p>
                                The real row count is shown for other table types.
                            </p>
                            <p>
                                <strong>BEHAVIOUR</strong><br/>
                                IF estimated row count > max row count:<br/>
                                &nbsp;&nbsp;&nbsp;&nbsp;use estimated row count<br/>
                                ELSE<br/>
                                &nbsp;&nbsp;&nbsp;&nbsp;user real row count
                            </p>
                            <p>
                                Showing the estimated row count instead of the real row count <strong>improves performance</strong>
                                for <strong>large tables and views</strong>.
                                An estimated row count is <strong>less accurate</strong> than a real row count.
                            </p>
                            <p>
                                This option can be changed for InnoDB tables and views in the Data Explorer:<br/>
                                WP Data Access > Data Explorer > YOUR TABLE > Manage > Settings > Table Settings > Row count
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo __( 'Default designer mode', 'wp-data-access' ); ?></th>
                        <td>
                            <select name="design_mode">
                                <option value="basic" <?php echo 'basic' === $design_mode ? 'selected' : ''; ?>>Basic
                                </option>
                                <option value="advanced" <?php echo 'advanced' === $design_mode ? 'selected' : ''; ?>>
                                    Advanced
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo __( 'Content wrap', 'wp-data-access' ); ?></th>
                        <td>
                            <label>
                                <input
                                    type="checkbox"
                                    name="text_wrap_switch" <?php echo 'on' === $text_wrap_switch ? 'checked' : ''; ?>
                                ><?php echo __( 'No content wrap', 'wp-data-access' ); ?>
                            </label>
                            <br/>
                            <input
                                type="number" step="1" min="1" max="999999" name="text_wrap" maxlength="3"
                                value="<?php echo esc_attr( $text_wrap ); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo __( 'Hide button icons', 'wp-data-access' ); ?></th>
                        <td>
                            <label>
                                <input
                                    type="checkbox"
                                    name="hide_button_icons" <?php echo 'on' === $hide_button_icons ? 'checked' : ''; ?>
                                ><?php echo __( 'Hide icons on admin buttons', 'wp-data-access' ); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                <div class="wpda-table-settings-button">
                    <input type="hidden" name="action" value="save"/>
                    <button type="submit" class="button button-primary">
                        <i class="fas fa-check wpda_icon_on_button"></i>
                        <?php echo __( 'Save Back-end Settings', 'wp-data-access' ); ?>
                    </button>
                    <a href="javascript:void(0)"
                       onclick="if (confirm('<?php echo __( 'Reset to defaults?', 'wp-data-access' ); ?>')) {
                           jQuery('input[name=&quot;action&quot;]').val('setdefaults');
                           jQuery('#wpda_settings_backend').trigger('submit')
                           }"
                       class="button">
                        <i class="fas fa-times-circle wpda_icon_on_button"></i>
                        <?php echo __( 'Reset Back-end Settings To Defaults', 'wp-data-access' ); ?>
                    </a>
                </div>
                <?php wp_nonce_field( 'wpda-back-end-settings', '_wpnonce', false ); ?>
            </form>

            <?php


        }

    }

}