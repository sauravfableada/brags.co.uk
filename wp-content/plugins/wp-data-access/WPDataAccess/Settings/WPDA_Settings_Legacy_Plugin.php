<?php

namespace WPDataAccess\Settings;

use WPDataAccess\Utilities\WPDA_Message_Box;
use WPDataAccess\WPDA;
class WPDA_Settings_Legacy_Plugin extends WPDA_Settings_Legacy_Page {
    public function show() {
        // Add datetimepicker
        wp_enqueue_style( 'datetimepicker' );
        wp_enqueue_script( 'datetimepicker' );
        if ( isset( $_REQUEST['action'] ) ) {
            $action = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) );
            // input var okay.
            // Security check.
            $wp_nonce = ( isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '' );
            // input var okay.
            if ( !wp_verify_nonce( $wp_nonce, 'wpda-plugin-settings-' . WPDA::get_current_user_login() ) ) {
                wp_die( __( 'ERROR: Not authorized', 'wp-data-access' ) );
            }
            if ( 'save' === $action ) {
                WPDA::set_option( WPDA::OPTION_PLUGIN_WPDATAACCESS_POST, ( isset( $_REQUEST['wpdataaccess_post'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['wpdataaccess_post'] ) ) : 'off' ) );
                WPDA::set_option( WPDA::OPTION_PLUGIN_WPDATAACCESS_PAGE, ( isset( $_REQUEST['wpdataaccess_page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['wpdataaccess_page'] ) ) : 'off' ) );
                WPDA::set_option( WPDA::OPTION_PLUGIN_WPDADIEHARD_POST, ( isset( $_REQUEST['wpdadiehard_post'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['wpdadiehard_post'] ) ) : 'off' ) );
                WPDA::set_option( WPDA::OPTION_PLUGIN_WPDADIEHARD_PAGE, ( isset( $_REQUEST['wpdadiehard_page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['wpdadiehard_page'] ) ) : 'off' ) );
                WPDA::set_option( WPDA::OPTION_PLUGIN_WPDADATAFORMS_POST, ( isset( $_REQUEST['wpdadataforms_post'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['wpdadataforms_post'] ) ) : 'off' ) );
                WPDA::set_option( WPDA::OPTION_PLUGIN_WPDADATAFORMS_PAGE, ( isset( $_REQUEST['wpdadataforms_page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['wpdadataforms_page'] ) ) : 'off' ) );
                if ( isset( $_REQUEST['date_format'] ) ) {
                    WPDA::set_option( WPDA::OPTION_PLUGIN_DATE_FORMAT, sanitize_text_field( wp_unslash( $_REQUEST['date_format'] ) ) );
                }
                if ( isset( $_REQUEST['date_placeholder'] ) ) {
                    WPDA::set_option( WPDA::OPTION_PLUGIN_DATE_PLACEHOLDER, sanitize_text_field( wp_unslash( $_REQUEST['date_placeholder'] ) ) );
                }
                if ( isset( $_REQUEST['time_format'] ) ) {
                    WPDA::set_option( WPDA::OPTION_PLUGIN_TIME_FORMAT, sanitize_text_field( wp_unslash( $_REQUEST['time_format'] ) ) );
                }
                if ( isset( $_REQUEST['time_placeholder'] ) ) {
                    WPDA::set_option( WPDA::OPTION_PLUGIN_TIME_PLACEHOLDER, sanitize_text_field( wp_unslash( $_REQUEST['time_placeholder'] ) ) );
                }
                if ( isset( $_REQUEST['set_format'] ) ) {
                    WPDA::set_option( WPDA::OPTION_PLUGIN_SET_FORMAT, sanitize_text_field( wp_unslash( $_REQUEST['set_format'] ) ) );
                }
            } elseif ( 'setdefaults' === $action ) {
                // Set all back-end settings back to default.
                WPDA::set_option( WPDA::OPTION_PLUGIN_WPDATAACCESS_POST );
                WPDA::set_option( WPDA::OPTION_PLUGIN_WPDATAACCESS_PAGE );
                WPDA::set_option( WPDA::OPTION_PLUGIN_WPDADIEHARD_POST );
                WPDA::set_option( WPDA::OPTION_PLUGIN_WPDADIEHARD_PAGE );
                WPDA::set_option( WPDA::OPTION_PLUGIN_WPDADATAFORMS_POST );
                WPDA::set_option( WPDA::OPTION_PLUGIN_WPDADATAFORMS_PAGE );
                WPDA::set_option( WPDA::OPTION_PLUGIN_DATE_FORMAT );
                WPDA::set_option( WPDA::OPTION_PLUGIN_DATE_PLACEHOLDER );
                WPDA::set_option( WPDA::OPTION_PLUGIN_TIME_FORMAT );
                WPDA::set_option( WPDA::OPTION_PLUGIN_TIME_PLACEHOLDER );
                WPDA::set_option( WPDA::OPTION_PLUGIN_SET_FORMAT );
            }
            $msg = new WPDA_Message_Box(array(
                'message_text' => __( 'Settings saved', 'wp-data-access' ),
            ));
            $msg->box();
        } elseif ( isset( $_REQUEST['msg'] ) && 'ok' === $_REQUEST['msg'] ) {
            $msg = new WPDA_Message_Box(array(
                'message_text' => __( 'Settings saved', 'wp-data-access' ),
            ));
            $msg->box();
        }
        // Get options.
        $wpdataaccess_post = WPDA::get_option( WPDA::OPTION_PLUGIN_WPDATAACCESS_POST );
        $wpdataaccess_page = WPDA::get_option( WPDA::OPTION_PLUGIN_WPDATAACCESS_PAGE );
        $wpdadiehard_post = WPDA::get_option( WPDA::OPTION_PLUGIN_WPDADIEHARD_POST );
        $wpdadiehard_page = WPDA::get_option( WPDA::OPTION_PLUGIN_WPDADIEHARD_PAGE );
        $wpdadataforms_post = WPDA::get_option( WPDA::OPTION_PLUGIN_WPDADATAFORMS_POST );
        $wpdadataforms_page = WPDA::get_option( WPDA::OPTION_PLUGIN_WPDADATAFORMS_PAGE );
        $date_format = WPDA::get_option( WPDA::OPTION_PLUGIN_DATE_FORMAT );
        $date_placeholder = WPDA::get_option( WPDA::OPTION_PLUGIN_DATE_PLACEHOLDER );
        $time_format = WPDA::get_option( WPDA::OPTION_PLUGIN_TIME_FORMAT );
        $time_placeholder = WPDA::get_option( WPDA::OPTION_PLUGIN_TIME_PLACEHOLDER );
        $set_format = WPDA::get_option( WPDA::OPTION_PLUGIN_SET_FORMAT );
        ?>

            <style>
                .settings_line {
                    line-height: 2.4;
                }

                .settings_label {
                    display: inline-block;
                    width: 7em;
                    font-weight: bold;
                }

                .item_width {
                    width: 14em;
                }

                .item_label {
                    width: 14.9em;
                    display: inline-block;
                    padding-left: 0.3em;
                }

                .item_label_text {
                    width: 7em;
                    display: inline-block;
                }

                .item_label_format {
                    width: 5em;
                    padding: 0.6em;
                    border-radius: 4px;
                }

                .item_label_align {
                    float: right;
                }
            </style>

            <script>
                jQuery(function () {
                    jQuery('.radio_date_format').on('click', function() {
                        jQuery('#date_format').val(jQuery(this).val());
                    });

                    jQuery('.radio_time_format').on('click', function() {
                        jQuery('#time_format').val(jQuery(this).val());
                    });

                    jQuery.datetimepicker.setLocale('<?php 
        echo esc_attr( substr( get_locale(), 0, 2 ) );
        ?>');
                    jQuery('#test_datetime').attr('autocomplete', 'off');
                    jQuery('#init_datetime').on('click', function() {
                        jQuery('#test_datetime').datetimepicker({
                            format: jQuery('#date_format').val() + ' ' + jQuery('#time_format').val(),
                            datepicker: true,
                            timepicker: true
                        });
                        jQuery('#init_datetime').toggle();
                        jQuery('#test_datetime').toggle();
                        jQuery('#test_datetime').val('');
                        jQuery('#test_datetime').attr('placeholder', jQuery('#date_placeholder').val() + ' ' + jQuery('#time_placeholder').val());
                    });
                    jQuery('#test_datetime').on('blur', function() {
                        jQuery('#test_datetime').toggle();
                        jQuery('#init_datetime').toggle();
                    });
                });
            </script>

            <form id="wpda_settings_plugin" method="post"
                  action="?page=<?php 
        echo esc_attr( $this->page );
        ?>&tab=legacy&vtab=plugin">
                <table class="wpda-table-settings" id="wpda_table_plugin">
                    <tr style="border-top: 1px solid #ccc">
                        <th><?php 
        echo __( 'Shortcode [wpdataaccess]' );
        ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="wpdataaccess_post" <?php 
        echo ( 'on' === $wpdataaccess_post ? 'checked="checked"' : '' );
        ?>/>
                                Allow in posts
                            </label>
                            <br/>
                            <label>
                                <input type="checkbox" name="wpdataaccess_page" <?php 
        echo ( 'on' === $wpdataaccess_page ? 'checked="checked"' : '' );
        ?>/>
                                Allow in pages
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php 
        echo __( 'Shortcode [wpdadiehard]' );
        ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="wpdadiehard_post" <?php 
        echo ( 'on' === $wpdadiehard_post ? 'checked="checked"' : '' );
        ?>/>
                                Allow in posts
                            </label>
                            <br/>
                            <label>
                                <input type="checkbox" name="wpdadiehard_page" <?php 
        echo ( 'on' === $wpdadiehard_page ? 'checked="checked"' : '' );
        ?>/>
                                Allow in pages
                            </label>
                        </td>
                    </tr>
                    <?php 
        ?>
                    <tr>
                        <th><?php 
        echo __( 'Date format' );
        ?></th>
                        <td>
                            <span class="settings_label"><?php 
        echo __( 'Output', 'wp-data-access' );
        ?></span>
                            <input type="text" value="<?php 
        echo esc_attr( get_option( 'date_format' ) );
        ?>" class="item_width"
                                   readonly/>
                            <?php 
        echo __( '(WordPress format)', 'wp-data-access' );
        ?>
                            <br/>
                            <span class="settings_line">
								<span class="settings_label"><?php 
        echo __( 'Input', 'wp-data-access' );
        ?></span>
								<label class="item_label">
									<input type="radio" name="radio_date_format" class="radio_date_format"
                                           value="Y-m-d" <?php 
        echo ( 'Y-m-d' === $date_format ? 'checked="checked"' : '' );
        ?>/>
									<span class="item_label_text"><?php 
        echo esc_attr( ( new \DateTime() )->format( 'Y-m-d' ) );
        ?></span>
									<span class="item_label_align">
										<input type="text" class="item_label_format" value="Y-m-d" readonly/>
									</span>
								</label>
							</span>
                            <?php 
        echo __( '(JavaScript format)', 'wp-data-access' );
        ?>
                            <br/>
                            <span class="settings_line">
								<span class="settings_label"></span>
								<label class="item_label">
									<input type="radio" name="radio_date_format" class="radio_date_format"
                                           value="m/d/Y" <?php 
        echo ( 'm/d/Y' === $date_format ? 'checked="checked"' : '' );
        ?>/>
									<span class="item_label_text"><?php 
        echo esc_attr( ( new \DateTime() )->format( 'm/d/Y' ) );
        ?></span>
									<span class="item_label_align">
										<input type="text" class="item_label_format" value="m/d/Y" readonly/>
									</span>
								</label>
							</span>
                            <br/>
                            <span class="settings_line">
								<span class="settings_label"></span>
								<label class="item_label">
									<input type="radio" name="radio_date_format" class="radio_date_format"
                                           value="d/m/Y" <?php 
        echo ( 'd/m/Y' === $date_format ? 'checked="checked"' : '' );
        ?>/>
									<span class="item_label_text"><?php 
        echo esc_attr( ( new \DateTime() )->format( 'd/m/Y' ) );
        ?></span>
									<span class="item_label_align">
										<input type="text" class="item_label_format" value="d/m/Y" readonly/>
									</span>
								</label>
							</span>
                            <br/>
                            <span class="settings_line">
								<span class="settings_label"></span>
								<label class="item_label">
									<input type="radio" name="radio_date_format" name="date_format"
                                           value="custom" <?php 
        echo ( 'Y-m-d' !== $date_format && 'd/m/Y' !== $date_format && 'm/d/Y' !== $date_format ? 'checked="checked"' : '' );
        ?>/>
									<span class="item_label_text"><?php 
        echo __( 'Custom:', 'wp-data-access' );
        ?></span>
									<span class="item_label_align">
										<input class="item_label_format" type="text" name="date_format" id="date_format"
                                               value="<?php 
        echo esc_attr( $date_format );
        ?>" class="item_label_format"/>
									</span>
								</label>
							</span>
                            <br/>
                            <span class="settings_label"><?php 
        echo __( 'Placeholder', 'wp-data-access' );
        ?></span>
                            <input type="text" name="date_placeholder" id="date_placeholder"
                                   value="<?php 
        echo esc_attr( $date_placeholder );
        ?>" class="item_width"/>
                            <?php 
        echo __( '(user info)', 'wp-data-access' );
        ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php 
        echo __( 'Time format' );
        ?></th>
                        <td>
                            <span class="settings_label"><?php 
        echo __( 'Output', 'wp-data-access' );
        ?></span>
                            <input type="text" value="<?php 
        echo esc_attr( get_option( 'time_format' ) );
        ?>" class="item_width"
                                   readonly/>
                            <?php 
        echo __( '(WordPress format)', 'wp-data-access' );
        ?>
                            <br/>
                            <span class="settings_line">
								<span class="settings_label"><?php 
        echo __( 'Input', 'wp-data-access' );
        ?></span>
								<label class="item_label">
									<input type="radio" name="radio_time_format" class="radio_time_format"
                                           value="H:i" <?php 
        echo ( 'H:i' === $time_format ? 'checked="checked"' : '' );
        ?>/>
									<span class="item_label_text"><?php 
        echo esc_attr( ( new \DateTime() )->format( 'H:i' ) );
        ?></span>
									<span class="item_label_align">
										<input type="text" class="item_label_format" value="H:i" readonly/>
									</span>
								</label>
							</span>
                            <?php 
        echo __( '(JavaScript format)', 'wp-data-access' );
        ?>
                            <br/>
                            <span class="settings_line">
								<span class="settings_label"></span>
								<label class="item_label">
									<input type="radio" name="radio_time_format" name="time_format"
                                           value="custom" <?php 
        echo ( 'H:i' !== $time_format ? 'checked="checked"' : '' );
        ?>/>
									<span class="item_label_text"><?php 
        echo __( 'Custom:', 'wp-data-access' );
        ?></span>
									<span class="item_label_align">
										<input class="item_label_format" type="text" name="time_format" id="time_format"
                                               value="<?php 
        echo esc_attr( $time_format );
        ?>" class="item_label_format"/>
									</span>
								</label>
							</span>
                            <br/>
                            <span class="settings_label"><?php 
        echo __( 'Placeholder', 'wp-data-access' );
        ?></span>
                            <input type="text" name="time_placeholder" id="time_placeholder"
                                   value="<?php 
        echo esc_attr( $time_placeholder );
        ?>" class="item_width"/>
                        </td>
                    </tr>
                    <tr>
                        <th><?php 
        echo __( 'Date/time test' );
        ?></th>
                        <td>
                            <input type="button" id="init_datetime" value="Test DateTimePicker" class="button item_width"/>
                            <input type="text" class="item_width" id="test_datetime" style="display:none;" />
                        </td>
                    </tr>
                    <tr>
                        <th><?php 
        echo __( 'Set format' );
        ?></th>
                        <td>
                            <span><?php 
        echo __( 'Show columns of data type set in list table as' );
        ?></span>
                            <select name="set_format">
                                <option value="csv" <?php 
        echo ( 'csv' === $set_format ? 'selected' : '' );
        ?>>Comma separated values</option>
                                <option value="ul" <?php 
        echo ( 'ul' === $set_format ? 'selected' : '' );
        ?>>Unordered list</option>
                                <option value="ol" <?php 
        echo ( 'ol' === $set_format ? 'selected' : '' );
        ?>>Ordered list</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><span class="dashicons dashicons-info" style="float:right;font-size:300%;"></span></th>
                        <td>
                            <span class="dashicons dashicons-yes"></span>
                            <?php 
        echo __( 'The plugin uses your WordPress general settings to format your date and time output', 'wp-data-access' );
        ?>
                            <br/>
                            <span class="dashicons dashicons-yes"></span>
                            <?php 
        echo __( 'The plugin uses the jQuery DateTimePicker plugin for data entry validation', 'wp-data-access' );
        ?>
                            <br/>
                            <span class="dashicons dashicons-yes"></span>
                            <a href="https://xdsoft.net/jqplugins/datetimepicker/" target="_blank">
                                <?php 
        echo __( 'Input formats can be found on the XDSoft DateTimePicker page', 'wp-data-access' );
        ?>
                            </a>
                        </td>
                    </tr>
                </table>
                <div class="wpda-table-settings-button">
                    <input type="hidden" name="action" value="save"/>
                    <button type="submit" class="button button-primary">
                        <i class="fas fa-check wpda_icon_on_button"></i>
                        <?php 
        echo __( 'Save Plugin Settings', 'wp-data-access' );
        ?>
                    </button>
                    <a href="javascript:void(0)"
                       onclick="if (confirm('<?php 
        echo __( 'Reset to defaults?', 'wp-data-access' );
        ?>')) {
                           jQuery('input[name=&quot;action&quot;]').val('setdefaults');
                           jQuery('#wpda_settings_plugin').trigger('submit')
                           }"
                       class="button">
                        <i class="fas fa-times-circle wpda_icon_on_button"></i>
                        <?php 
        echo __( 'Reset Plugin Settings To Defaults', 'wp-data-access' );
        ?>
                    </a>
                </div>
                <?php 
        wp_nonce_field( 'wpda-plugin-settings-' . WPDA::get_current_user_login(), '_wpnonce', false );
        ?>
            </form>
            <?php 
    }

}
