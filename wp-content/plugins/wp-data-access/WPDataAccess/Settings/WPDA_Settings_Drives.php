<?php

namespace WPDataAccess\Settings {

    use WPDataAccess\Drive\WPDA_Drives;
    use WPDataAccess\Drive\WPDA_Dropbox;
    use WPDataAccess\Drive\WPDA_Ftp;
    use WPDataAccess\Drive\WPDA_Google_Drive;
    use WPDataAccess\Drive\WPDA_Local;
    use WPDataAccess\Drive\WPDA_Sftp;
    use WPDataAccess\Utilities\WPDA_Message_Box;
    use WPDataAccess\WPDA;

    class WPDA_Settings_Drives extends WPDA_Settings {

        private $drives = null;

        private function get_drive( $drive_type ) {

            if ( is_array( $this->drives ) ) {
                return array_filter(
                    $this->drives,
                    function( $v, $k ) use ( $drive_type ) {
                        return $v['type'] === $drive_type;
                    }, ARRAY_FILTER_USE_BOTH
                );
            } else {
                return array();
            }

        }

        private function save_local_drive() {

            $local_file_system = trim( sanitize_text_field( wp_unslash( $_POST['local_file_system'] ) ) ); // input var okay.
            $local_enabled     = isset( $_POST['local_enabled'] ) && 'on' === $_POST['local_enabled'];

            if ( '' === $local_file_system ) {
                WPDA_Drives::delete_drive( 'local' );
            } else {
                $local_drive = new WPDA_Local();
                if (
                    ! $local_drive->authorize(
                        array(
                            'path' => $local_file_system,
                        ),
                        $local_enabled
                    )
                ) {
                    $msg = new WPDA_Message_Box(
                        array(
                            'message_text'           => 'Could not add local folder',
                            'message_type'           => 'error',
                            'message_is_dismissible' => false,
                        )
                    );
                    $msg->box();
                }
            }

        }

        private function save_ftp_server( $index ) {

            $ftp_server_name     = trim( sanitize_text_field( wp_unslash( $_POST['ftp_server_name'][ $index ] ) ) );
            $ftp_enabled         = isset( $_POST['ftp_enabled'] ) && 'on' === $_POST['ftp_enabled'][ $index ];
            $ftp_host            = sanitize_text_field( wp_unslash( $_POST['ftp_host'][ $index ] ) );
            $ftp_username        = sanitize_text_field( wp_unslash( $_POST['ftp_username'][ $index ] ) );
            $ftp_password        = sanitize_text_field( wp_unslash( $_POST['ftp_password'][ $index ] ) );
            $ftp_port            = sanitize_text_field( wp_unslash( $_POST['ftp_port'][ $index ] ) );
            $ftp_ssl             = isset( $_POST['ftp_ssl'] ) && 'on' === $_POST['ftp_ssl'][ $index ];
            $ftp_passive         = isset( $_POST['ftp_passive'] ) && 'on' === $_POST['ftp_passive'][ $index ];
            $ftp_timeout         = sanitize_text_field( wp_unslash( $_POST['ftp_timeout'][ $index ] ) );
            $ftp_directory       = sanitize_text_field( wp_unslash( $_POST['ftp_directory'][ $index ] ) );

            $ftp_drive = new WPDA_Ftp( $ftp_server_name );
            if (
                ! $ftp_drive->authorize(
                    array(
                        'host'      => $ftp_host,
                        'username'  => $ftp_username,
                        'password'  => $ftp_password,
                        'port'      => $ftp_port,
                        'ssl'       => $ftp_ssl,
                        'passive'   => $ftp_passive,
                        'timeout'   => $ftp_timeout,
                        'directory' => $ftp_directory,
                    ),
                    $ftp_enabled
                )
            ) {
                $msg = new WPDA_Message_Box(
                    array(
                        'message_text'           => 'Could not add FTP server',
                        'message_type'           => 'error',
                        'message_is_dismissible' => false,
                    )
                );
                $msg->box();
            }

        }

        private function save_sftp_server( $index ) {

            $sftp_server_name     = trim( sanitize_text_field( wp_unslash( $_POST['sftp_server_name'][ $index ] ) ) );
            $sftp_enabled         = isset( $_POST['sftp_enabled'] ) && 'on' === $_POST['sftp_enabled'][ $index ];
            $sftp_host            = sanitize_text_field( wp_unslash( $_POST['sftp_host'][ $index ] ) );
            $sftp_username        = sanitize_text_field( wp_unslash( $_POST['sftp_username'][ $index ] ) );
            $sftp_password        = sanitize_text_field( wp_unslash( $_POST['sftp_password'][ $index ] ) );
            $sftp_port            = sanitize_text_field( wp_unslash( $_POST['sftp_port'][ $index ] ) );
            $sftp_timeout         = sanitize_text_field( wp_unslash( $_POST['sftp_timeout'][ $index ] ) );
            $sftp_directory       = sanitize_text_field( wp_unslash( $_POST['sftp_directory'][ $index ] ) );

            $ftp_drive = new WPDA_Sftp( $sftp_server_name );
            if (
                ! $ftp_drive->authorize(
                    array(
                        'host'      => $sftp_host,
                        'username'  => $sftp_username,
                        'password'  => $sftp_password,
                        'port'      => $sftp_port,
                        'timeout'   => $sftp_timeout,
                        'directory' => $sftp_directory,
                    ),
                    $sftp_enabled
                )
            ) {
                $msg = new WPDA_Message_Box(
                    array(
                        'message_text'           => 'Could not add SFTP server',
                        'message_type'           => 'error',
                        'message_is_dismissible' => false,
                    )
                );
                $msg->box();
            }

        }

        private function save_dropbox() {

            $dropbox_authorization = trim( sanitize_text_field( wp_unslash( $_POST['dropbox_authorization'] ) ) ); // input var okay.

            if ( '' !== trim( $dropbox_authorization ) ) {
                $dropbox_drive = new WPDA_Dropbox();
                $response     = $dropbox_drive->authorize( $dropbox_authorization );
                if ( false === $response ) {
                    $msg = new WPDA_Message_Box(
                        array(
                            'message_text'           => 'Could not update Dropbox',
                            'message_type'           => 'error',
                            'message_is_dismissible' => false,
                        )
                    );
                    $msg->box();
                } elseif ( is_string( $response ) ) {
                    $msg = new WPDA_Message_Box(
                        array(
                            'message_text'           => $response,
                            'message_type'           => 'error',
                            'message_is_dismissible' => false,
                        )
                    );
                    $msg->box();
                }
            }

        }

        private function update_dropbox() {

            $dropbox_enabled = isset( $_POST['dropbox_enabled'] ) && 'on' === $_POST['dropbox_enabled'];

            $dropbox_drive = WPDA_Drives::get_drive( 'dropbox'  );
            if ( false !== $dropbox_drive ) {
                $dropbox_drive->toggle( $dropbox_enabled );
            }

        }

        private function save_google_drive() {

            $google_drive_authorization = trim( sanitize_text_field( wp_unslash( $_POST['google_drive_authorization'] ) ) ); // input var okay.

            if ( '' !== trim( $google_drive_authorization ) ) {
                $google_drive_drive = new WPDA_Google_Drive();
                $response           = $google_drive_drive->authorize( $google_drive_authorization );
                if ( false === $response ) {
                    $msg = new WPDA_Message_Box(
                        array(
                            'message_text'           => 'Could not update Google Drive',
                            'message_type'           => 'error',
                            'message_is_dismissible' => false,
                        )
                    );
                    $msg->box();
                } elseif ( is_string( $response ) ) {
                    $msg = new WPDA_Message_Box(
                        array(
                            'message_text'           => $response,
                            'message_type'           => 'error',
                            'message_is_dismissible' => false,
                        )
                    );
                    $msg->box();
                }
            }

        }

        private function update_google_drive() {

            $google_drive_enabled = isset( $_POST['google_drive_enabled'] ) && 'on' === $_POST['google_drive_enabled'];

            $google_drive_drive = WPDA_Drives::get_drive( 'google_drive'  );
            if ( false !== $google_drive_drive ) {
                $google_drive_drive->toggle( $google_drive_enabled );
            }

        }

        protected function add_content() {

            if ( isset( $_POST['action'] ) ) {
                $action = sanitize_text_field( wp_unslash( $_POST['action'] ) ); // input var okay.

                // Security check.
                $wp_nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : ''; // input var okay.
                if ( ! wp_verify_nonce( $wp_nonce, 'wpda-drives-settings-' . WPDA::get_current_user_login() ) ) {
                    wp_die( __( 'ERROR: Not authorized', 'wp-data-access' ) );
                }

                if ( 'save' === $action ) {
                    if ( isset( $_POST['local_file_system'] ) ) {
                        // Local file system
                        $this->save_local_drive();
                    }

                    if (
                        isset(
                            $_POST['ftp_server_name'],
                            $_POST['old_ftp_server_name'],
                            $_POST['ftp_host'],
                            $_POST['ftp_username'],
                            $_POST['ftp_password'],
                            $_POST['ftp_port'],
                            $_POST['ftp_timeout'],
                            $_POST['ftp_directory']
                        )
                    ) {
                        // FTP servers
                        $count = count( $_POST['ftp_server_name'] );
                        for ( $i = 0; $i < $count; $i++ ) {
                            $this->save_ftp_server( $i );
                        }
                    }

                    if (
                        isset(
                            $_POST['sftp_server_name'],
                            $_POST['old_sftp_server_name'],
                            $_POST['sftp_host'],
                            $_POST['sftp_username'],
                            $_POST['sftp_password'],
                            $_POST['sftp_port'],
                            $_POST['sftp_timeout'],
                            $_POST['sftp_directory']
                        )
                    ) {
                        // SFTP servers
                        $count = count( $_POST['sftp_server_name'] );
                        for ( $i = 0; $i < $count; $i++ ) {
                            $this->save_sftp_server( $i );
                        }
                    }

                    if ( isset( $_POST['dropbox_authorization'] ) ) {
                        $this->save_dropbox();
                    } else if ( isset( $_POST['dropbox_authorized'] ) ) {
                        $this->update_dropbox();
                    }

                    if ( isset( $_POST['google_drive_authorization'] ) ) {
                        $this->save_google_drive();
                    } else if ( isset( $_POST['google_drive_authorized'] ) ) {
                        $this->update_google_drive();
                    }

                    // Delete drives
                    if ( isset( $_POST['deleted_drives'] ) ) {
                        $deleted_drives = sanitize_text_field( wp_unslash( $_POST['deleted_drives'] ) );
                        $drive_names    = explode( ',', $deleted_drives );
                        foreach ( $drive_names as $drive_name ) {
                            WPDA_Drives::delete_drive( $drive_name);
                        }
                    }

                    WPDA_Drives::save();
                } elseif ( 'setdefaults' === $action ) {
                    // Set all back-end settings back to default.
                }

                $msg = new WPDA_Message_Box(
                    array(
                        'message_text' => __( 'Settings saved', 'wp-data-access' ),
                    )
                );
                $msg->box();
            }

            $this->drives = WPDA_Drives::get_drives();

            $this->css();
            $this->js();

            ?>

            <form
                id="wpda_settings_drives"
                method="post"
                action="?page=<?php echo esc_attr( $this->page ); ?>&tab=drives"
            >
                <table class="wpda-table-settings">

                    <tr>
                        <th></th>
                        <td>
                            Plugin drives for storing files generated by <strong>unattended scheduled exports</strong>.
                        </td>
                    </tr>

                    <?php
                    $local = $this->get_drive( 'local' );
                    if ( isset( $local['local'] ) ) {
                        $local_drive = $local['local'];
                    }
                    ?>
                    <tr id="local_main_container">
                        <th>
                            Local File System
                        </th>
                        <td>
                            <div>
                                <div>
                                    <i>Save files to the local file system.</i>
                                </div>

                                <div>
                                    <label style="display:block">Enter full local path:</label>
                                    <input type="text" name="local_file_system" style="width: 240px" value="<?php echo isset( $local_drive['drive']['path'] ) ? $local_drive['drive']['path'] : ''; ?>" />
                                    <div>&nbsp;</div>
                                    <div>
                                        Verify that the folder exists and that the server has permission to write files to it.
                                    </div>
                                    <div>&nbsp;</div>
                                    <label style="font-weight: normal">
                                        <input
                                                type="checkbox"
                                                name="local_enabled"
                                            <?php echo isset( $local_drive['enabled'] ) && $local_drive['enabled'] ? 'checked' : ''; ?>
                                        /> <?php echo __( 'Activate', 'wp-data-access' ); ?>
                                    </label>
                                </div>
                            </div>
                        </td>
                    </tr>

                    <tr id="ftp_main_container">
                        <th>
                            FTP Server
                        </th>
                        <td>
                            <div id="ftp_container">
                                <div>
                                    <i>Upload files to an FTP server.</i>
                                    <span>
                                        <a href="javascript:void(0)"
                                           onclick="ftpContainer(true)"
                                           style="text-decoration:none;"
                                           class="wpda_tooltip"
                                           title="Add new FTP Server">
                                            <span class="dashicons dashicons-plus-alt" style="font-size:18px;"></span>
                                        </a>
                                    </span>
                                </div>

                                <?php
                                    $ftp_drives = $this->get_drive( 'ftp' );
                                    foreach ( $ftp_drives as $drive_name => $ftp_drive ) {
                                        ?>
                                        <script>
                                            ftpContainer(
                                                false,
                                                "<?php echo esc_attr( $drive_name ); ?>",
                                                <?php echo isset( $ftp_drive['enabled'] ) && true === $ftp_drive['enabled'] ? 1 : 0; ?>,
                                                "<?php echo isset( $ftp_drive['drive']['host'] ) ? esc_attr( $ftp_drive['drive']['host'] ) : ''; ?>",
                                                "<?php echo isset( $ftp_drive['drive']['username'] ) ? esc_attr( $ftp_drive['drive']['username'] ) : ''; ?>",
                                                "<?php echo isset( $ftp_drive['drive']['password'] ) ? esc_attr( $ftp_drive['drive']['password'] ) : ''; ?>",
                                                "<?php echo isset( $ftp_drive['drive']['port'] ) ? esc_attr( $ftp_drive['drive']['port'] ) : ''; ?>",
                                                <?php echo isset( $ftp_drive['drive']['ssl'] ) && true === $ftp_drive['drive']['ssl'] ? 1 : 0; ?>,
                                                <?php echo isset( $ftp_drive['drive']['passive'] ) && true === $ftp_drive['drive']['passive'] ? 1 : 0; ?>,
                                                "<?php echo isset( $ftp_drive['drive']['timeout'] ) ? esc_attr( $ftp_drive['drive']['timeout'] ) : ''; ?>",
                                                "<?php echo isset( $ftp_drive['drive']['directory'] ) ? esc_attr( $ftp_drive['drive']['directory'] ) : '/'; ?>"
                                            )
                                        </script>
                                        <?php
                                    }
                                ?>
                            </div>
                        </td>
                    </tr>

                    <tr id="sftp_main_container">
                        <th>
                            SFTP Server
                        </th>
                        <td>
                            <div id="sftp_container">
                                <div>
                                    <i>Upload files securely via SFTP.</i>
                                    <span>
                                        <a href="javascript:void(0)"
                                           onclick="sftpContainer(true)"
                                           style="text-decoration:none;"
                                           class="wpda_tooltip"
                                           title="Add new SFTP Server">
                                            <span class="dashicons dashicons-plus-alt" style="font-size:18px;"></span>
                                        </a>
                                    </span>
                                </div>

                                <?php
                                    $ftp_drives = $this->get_drive( 'sftp' );
                                    foreach ( $ftp_drives as $drive_name => $ftp_drive ) {
                                        ?>
                                        <script>
                                            sftpContainer(
                                                false,
                                                "<?php echo esc_attr( $drive_name ); ?>",
                                                <?php echo isset( $ftp_drive['enabled'] ) && true === $ftp_drive['enabled'] ? 1 : 0; ?>,
                                                "<?php echo isset( $ftp_drive['drive']['host'] ) ? esc_attr( $ftp_drive['drive']['host'] ) : ''; ?>",
                                                "<?php echo isset( $ftp_drive['drive']['username'] ) ? esc_attr( $ftp_drive['drive']['username'] ) : ''; ?>",
                                                "<?php echo isset( $ftp_drive['drive']['password'] ) ? esc_attr( $ftp_drive['drive']['password'] ) : ''; ?>",
                                                "<?php echo isset( $ftp_drive['drive']['port'] ) ? esc_attr( $ftp_drive['drive']['port'] ) : ''; ?>",
                                                "<?php echo isset( $ftp_drive['drive']['timeout'] ) ? esc_attr( $ftp_drive['drive']['timeout'] ) : ''; ?>",
                                                "<?php echo isset( $ftp_drive['drive']['directory'] ) ? esc_attr( $ftp_drive['drive']['directory'] ) : '/'; ?>"
                                            )
                                        </script>
                                        <?php
                                    }
                                ?>
                            </div>
                        </td>
                    </tr>

                    <?php
                    $dropbox = $this->get_drive( 'dropbox' );
                    if ( isset( $dropbox['dropbox'] ) ) {
                        $dropbox_drive = $dropbox['dropbox'];
                    }
                    $dropbox_client_id = WPDA_Dropbox::DROPBOX_CLIENT_ID;
                    ?>
                    <tr id="dropbox_main_container">
                        <th>
                            Dropbox
                        </th>
                        <td>
                            <div>
                                <div>
                                    <i>Store files in your Dropbox folder.</i>
                                    <?php
                                    if (
                                        isset(
                                            $dropbox_drive['drive']['access_token'],
                                            $dropbox_drive['drive']['refresh_token']
                                        )
                                    ) {
                                        // Dropbox is already authorized
                                        ?>
                                        <a href="javascript:void(0)"
                                           id="dropbox_delete"
                                           onclick="if (confirm('Are you sure you want to delete your Dropbox drive? This action cannot be undone!')) { jQuery('#dropbox_delete').hide(); jQuery('#dropbox_container').hide(); jQuery('#dropbox_container_unauthorize').show(); deleteDrive('dropbox'); }"
                                           style="text-decoration:none;"
                                           class="wpda_tooltip"
                                           title="Delete Dropbox drive">
                                            <span class="dashicons dashicons-trash" style="font-size:18px;"></span>
                                        </a>
                                        <?php
                                    }
                                    ?>
                                </div>

                                <div id="dropbox_container">
                                    <?php
                                    if (
                                        isset(
                                            $dropbox_drive['drive']['access_token'],
                                            $dropbox_drive['drive']['refresh_token']
                                        )
                                    ) {
                                        // Dropbox is already authorized
                                        ?>
                                        <label style="font-weight: normal">
                                            <input
                                                type="checkbox"
                                                name="dropbox_enabled"
                                                <?php echo isset( $dropbox_drive['enabled'] ) && $dropbox_drive['enabled'] ? 'checked' : ''; ?>
                                            /> <?php echo __( 'Activate', 'wp-data-access' ); ?>
                                        </label>
                                        <input type="hidden" name="dropbox_authorized" />
                                        <?php
                                    } else {
                                        // Dropbox not authorized
                                        ?>
                                        <p style="margin-top: 0">
                                            Storing export files in a Dropbox folder requires a Dropbox account.
                                            <a href="https://www.dropbox.com/" target="_blank">Create a Dropbox account if you don't have one.</a>
                                        </p>

                                        <ol>
                                            <li>Click the button below to authorize the Dropbox app</li>
                                            <li>Enter the authorization code in the text box below</li>
                                            <li>Press the <strong>Save Drive Settings</strong> button to activate</li>
                                        </ol>

                                        <div>&nbsp;</div>

                                        <a
                                            href="https://www.dropbox.com/oauth2/authorize?client_id=<?php echo esc_attr( $dropbox_client_id ); ?>&response_type=code&token_access_type=offline"
                                            target="_blank"
                                            class="button button-primary"
                                        >
                                            GET DROPBOX AUTHORIZATION CODE
                                        </a>

                                        <input type="text" name="dropbox_authorization" style="width: 240px" />
                                        <?php
                                    }
                                    ?>
                                </div>

                                <div id="dropbox_container_unauthorize" style="display:none">
                                    <div>
                                        Press the <strong>Save Drive Settings</strong> button to complete this action.
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>

                    <?php
                    $google_drive = $this->get_drive( 'google_drive' );
                    if ( isset( $google_drive['google_drive'] ) ) {
                        $google_drive_drive = $google_drive['google_drive'];
                    }
                    $google_drive_client_id    = WPDA_Google_Drive::GOOGLE_CLIENT_ID;
                    $google_drive_redirect_uri = 'http://localhost';
                    ?>
                    <tr id="google_drive_main_container">
                        <th>
                            Google Drive
                        </th>
                        <td>
                            <div>
                                <div>
                                    <i>Save files to your Google Drive.</i>
                                    <?php
                                    if (
                                        isset(
                                            $google_drive_drive['drive']['access_token'],
                                            $google_drive_drive['drive']['refresh_token']
                                        )
                                    ) {
                                        // Google Drive is already authorized
                                        ?>
                                        <a href="javascript:void(0)"
                                           id="google_drive_delete"
                                           onclick="if (confirm('Are you sure you want to delete your Google Drive? This action cannot be undone!')) { jQuery('#google_drive_delete').hide(); jQuery('#google_drive_container').hide(); jQuery('#google_drive_container_unauthorize').show(); deleteDrive('google_drive'); }"
                                           style="text-decoration:none;"
                                           class="wpda_tooltip"
                                           title="Delete Google Drive">
                                            <span class="dashicons dashicons-trash" style="font-size:18px;"></span>
                                        </a>
                                        <?php
                                    }
                                    ?>
                                </div>

                                <div id="google_drive_container">
                                    <?php
                                    if (
                                        isset(
                                            $google_drive_drive['drive']['access_token'],
                                            $google_drive_drive['drive']['refresh_token']
                                        )
                                    ) {
                                        // Google Drive is already authorized
                                        ?>
                                        <label style="font-weight: normal">
                                            <input
                                                    type="checkbox"
                                                    name="google_drive_enabled"
                                                <?php echo isset( $google_drive_drive['enabled'] ) && $google_drive_drive['enabled'] ? 'checked' : ''; ?>
                                            /> <?php echo __( 'Activate', 'wp-data-access' ); ?>
                                        </label>
                                        <input type="hidden" name="google_drive_authorized" />
                                        <?php
                                    } else {
                                        // Google Drive not authorized
                                        ?>
                                        <p style="margin-top: 0">
                                            Storing export files in a Google Drive folder requires a Google account.
                                            <a href="https://accounts.google.com/signup/v2/webcreateaccount?hl=en-GB&flowName=GlifWebSignIn&flowEntry=SignUp/" target="_blank">Create a Google account if you don't have one.</a>
                                        </p>

                                        <ol>
                                            <li>Click the button below to authorize the Google Drive app</li>
                                            <li>Enter the authorization code in the text box below</li>
                                            <li>Press the <strong>Save Drive Settings</strong> button to activate</li>
                                        </ol>

                                        <div>&nbsp;</div>

                                        <a
                                            href="https://accounts.google.com/o/oauth2/v2/auth?client_id=<?php echo esc_attr( $google_drive_client_id ); ?>&redirect_uri=<?php echo esc_attr( $google_drive_redirect_uri ); ?>&response_type=code&scope=https://www.googleapis.com/auth/drive.file%20https://www.googleapis.com/auth/drive.readonly&access_type=offline&prompt=consent"
                                            target="_blank"
                                            class="button button-primary"
                                        >
                                            GET GOOGLE DRIVE AUTHORIZATION CODE
                                        </a>

                                        <input type="text" name="google_drive_authorization" style="width: 240px" />
                                        <?php
                                    }
                                    ?>
                                </div>

                                <div id="google_drive_container_unauthorize" style="display:none">
                                    <div>
                                        Press the <strong>Save Drive Settings</strong> button to complete this action.
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>

                </table>

                <div class="wpda-table-settings-button">
                    <input type="hidden" name="action" value="save"/>
                    <input type="hidden" name="deleted_drives" id="deleted_drives" />
                    <button type="submit" class="button button-primary">
                        <i class="fas fa-check wpda_icon_on_button"></i>
                        <?php echo __( 'Save Drive Settings', 'wp-data-access' ); ?>
                    </button>
                    <a href="javascript:void(0)"
                       onclick="if (confirm('<?php echo __( 'Reset to defaults?', 'wp-data-access' ); ?>')) {
                           jQuery('input[name=\'action\']').val('setdefaults');
                           jQuery('#wpda_settings_drives').trigger('submit');
                           }"
                       class="button button-secondary">
                        <i class="fas fa-times-circle wpda_icon_on_button"></i>
                        <?php echo __( 'Reset Drive Settings To Defaults', 'wp-data-access' ); ?>
                    </a>
                </div>

                <?php wp_nonce_field( 'wpda-drives-settings-' . WPDA::get_current_user_login(), '_wpnonce', false ); ?>

            </form>

            <?php

        }

        private function css() {
            ?>

            <style>
                table.wpda-table-settings tr th,
                table.wpda-table-settings tr td {
                    vertical-align: text-top;
                }

                table.wpda-table-settings tr th {
                    white-space: nowrap;
                }

                table.wpda-table-settings tr td > div {
                    display: grid;
                    grid-gap: 20px;
                }

                table.wpda-table-settings tr td fieldset.wpda_fieldset {
                    margin-right: 20px;
                }

                .form-control {
                    line-height: 1.8rem;
                }

                .form-control > *:first-child {
                    display: inline-block;
                    width: 110px;
                    text-align: right;
                }
                .form-control > *:last-child {
                    display: inline-block;
                    width: 240px;
                }

                .form-control-toolbar {
                    display: inline-flex !important;
                    grid-template-columns: auto auto;
                    justify-content: space-between;
                    align-items: center;
                }
                .form-control-toolbar div {
                    display: flex;
                }
                .form-control-toolbar a {
                    display: flex;
                }

                .form-control-toolbar a.form-control-details,
                .form-control-details {
                    display: none;
                }

                .wpda-is-new .form-control-toolbar a.form-control-details {
                    display: flex;
                }
                .wpda-is-new .form-control-details {
                    display: block;
                }

                #sftp_main_container,
                #dropbox_main_container,
                #google_drive_main_container {
                    display: none;
                }
            </style>

            <?php
        }

        private function js() {
            $drive_names = array();
            foreach ( $this->drives as $drive_name => $drive ) {
                $drive_names[] = $drive_name;
            }
            ?>

            <script>
                const driveNames = <?php echo json_encode( $drive_names ); ?>;

                jQuery(function() {
                    jQuery( "#wpda_settings_drives" ).on(
                        "submit",
                        function() {
                            let doSumbit = true;

                            const ftpServerNames = jQuery("input[name='ftp_server_name[]']");
                            const oldFtpServerNames = jQuery("input[name='old_ftp_server_name[]']");
                            for (let i = 0; i < ftpServerNames.length; i++) {
                                const ftpServerName = jQuery(ftpServerNames[i]).val().trim();
                                const oldFtpServerName = jQuery(oldFtpServerNames[i]).val().trim();

                                if (ftpServerName.trim() === "") {
                                    alert("FTP Server Name is required!");
                                    doSumbit = false;
                                } else if (driveNames.includes(ftpServerName) && oldFtpServerName !== ftpServerName) {
                                    alert("Drive Name is already taken!");
                                    doSumbit = false;
                                }
                            }

                            const sftpServerNames = jQuery("input[name='sftp_server_name[]']");
                            const oldSftpServerNames = jQuery("input[name='old_sftp_server_name[]']");
                            for (let i = 0; i < sftpServerNames.length; i++) {
                                const sftpServerName = jQuery(sftpServerNames[i]).val().trim();
                                const oldSftpServerName = jQuery(oldSftpServerNames[i]).val().trim();

                                if (sftpServerName.trim() === "") {
                                    alert("SFTP Server Name is required!");
                                    doSumbit = false;
                                } else if (driveNames.includes(sftpServerName) && oldSftpServerName !== sftpServerName) {
                                    alert("Drive Name is already taken!");
                                    doSumbit = false;
                                }
                            }

                            // Make sure to send unchecked checkbox values
                            jQuery("input[type=checkbox]").each(
                                function() {
                                    if (!jQuery(this).is(":checked")) {
                                        jQuery(this).prop("checked", true);
                                        jQuery(this).val("off");
                                    }
                                }
                            )

                            return doSumbit;
                        }
                    );
                });

                function deleteDrive(driveName) {
                    if (jQuery("#deleted_drives").val() === "") {
                        jQuery("#deleted_drives").val(
                            jQuery("#deleted_drives").val() + driveName
                        );
                    } else {
                        jQuery("#deleted_drives").val(
                            jQuery("#deleted_drives").val() + "," + driveName
                        );
                    }
                }

                function ftpContainer(
                    isNew,
                    driveName = '',
                    isChecked = false,
                    host = '',
                    username = '',
                    password = '',
                    port = 21,
                    ssl = false,
                    passive = false,
                    timeout = 90,
                    directory = '/'
                ) {
                    jQuery("#ftp_container").append(`
                        <fieldset class="wpda_fieldset${isNew ? ' wpda-is-new' : ''}">

                            <div class="form-control">
                                <label>Server Name</label>
                                <input type="hidden" name="old_ftp_server_name[]" value="${driveName}" />
                                <input type="text" name="ftp_server_name[]" value="${driveName}" placeholder="New FTP Server" />
                            </div>

                            <div class="form-control">
                                <div></div>
                                <div class="form-control-toolbar">
                                    <label>
                                        <input type="checkbox" name="ftp_enabled[]" ${isChecked ? 'checked' : ''} />
                                        Activate
                                    </label>

                                    <div>
                                        <a href="javascript:void(0)"
                                           onclick="jQuery(this).closest('fieldset').find('.form-control-details').show(); jQuery(this).closest('fieldset').find('.form-control-details').css('display', 'block'); jQuery(this).closest('fieldset').find('a.form-control-details').css('display', 'flex'); jQuery(this).closest('fieldset').find('.form-control-icons').hide();"
                                           style="text-decoration:none;${isNew ? 'display:none;' : ''}"
                                           class="wpda_tooltip form-control-icons"
                                           title="Show FTP Server details">
                                            <span class="dashicons dashicons-visibility" style="font-size:18px;"></span>
                                        </a>

                                        <a href="javascript:void(0)"
                                           onclick="jQuery(this).closest('fieldset').find('.form-control-details').hide(); jQuery(this).closest('fieldset').find('.form-control-icons').show();"
                                           style="text-decoration:none;${isNew ? 'display:none;' : ''}"
                                           class="wpda_tooltip form-control-details"
                                           title="Show FTP Server details">
                                            <span class="dashicons dashicons-hidden" style="font-size:18px;"></span>
                                        </a>

                                        <a href="javascript:void(0)"
                                           onclick="if (confirm('Are you sure you want to delete this FTP Server? This action cannot be undone!')) { jQuery(this).closest('fieldset').remove(); deleteDrive('${driveName}'); }"
                                           style="text-decoration:none;"
                                           class="wpda_tooltip"
                                           title="Delete FTP Server">
                                            <span class="dashicons dashicons-trash" style="font-size:18px;"></span>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="form-control form-control-details">
                                <label>Host</label>
                                <input type="text" name="ftp_host[]" value="${host}" />
                            </div>

                            <div class="form-control form-control-details">
                                <label>Username</label>
                                <input type="text" name="ftp_username[]" value="${username}" />
                            </div>

                            <div class="form-control form-control-details">
                                <label>Password</label>
                                <input type="password" name="ftp_password[]" value="${password}" />
                            </div>

                            <div class="form-control form-control-details">
                                <label>Port</label>
                                <input type="number" name="ftp_port[]" value="${port}" />
                            </div>

                            <div class="form-control form-control-details">
                                <div></div>
                                <div>
                                    <label>
                                        <input type="checkbox" name="ftp_ssl[]" ${ssl ? 'checked' : ''} />
                                        SSL
                                    </label>
                                    &nbsp;
                                    <label>
                                        <input type="checkbox" name="ftp_passive[]" ${passive ? 'checked' : ''} />
                                        Passive
                                    </label>
                                </div>
                            </div>

                            <div class="form-control form-control-details">
                                <label>Timeout</label>
                                <input type="number" name="ftp_timeout[]" value="${timeout}" />
                            </div>

                            <div class="form-control form-control-details">
                                <label>Directory</label>
                                <input type="text" name="ftp_directory[]" value="${directory}" />
                            </div>

                        </fieldset>
                    `);
                }

                function sftpContainer(
                    isNew,
                    driveName = '',
                    isChecked = false,
                    host = '',
                    username = '',
                    password = '',
                    port = 21,
                    timeout = 90,
                    directory = '/'
                ) {
                    jQuery("#sftp_container").append(`
                        <fieldset class="wpda_fieldset${isNew ? ' wpda-is-new' : ''}">

                            <div class="form-control">
                                <label>Server Name</label>
                                <input type="hidden" name="old_sftp_server_name[]" value="${driveName}" />
                                <input type="text" name="sftp_server_name[]" value="${driveName}" placeholder="New SFTP Server" />
                            </div>

                            <div class="form-control">
                                <div></div>
                                <div class="form-control-toolbar">
                                    <label>
                                        <input type="checkbox" name="sftp_enabled[]" ${isChecked ? 'checked' : ''} />
                                        Activate
                                    </label>

                                    <div>
                                        <a href="javascript:void(0)"
                                           onclick="jQuery(this).closest('fieldset').find('.form-control-details').show(); jQuery(this).closest('fieldset').find('.form-control-details').css('display', 'block'); jQuery(this).closest('fieldset').find('a.form-control-details').css('display', 'flex'); jQuery(this).closest('fieldset').find('.form-control-icons').hide();"
                                           style="text-decoration:none;${isNew ? 'display:none;' : ''}"
                                           class="wpda_tooltip form-control-icons"
                                           title="Show FTP Server details">
                                            <span class="dashicons dashicons-visibility" style="font-size:18px;"></span>
                                        </a>

                                        <a href="javascript:void(0)"
                                           onclick="jQuery(this).closest('fieldset').find('.form-control-details').hide(); jQuery(this).closest('fieldset').find('.form-control-icons').show();"
                                           style="text-decoration:none;${isNew ? 'display:none;' : ''}"
                                           class="wpda_tooltip form-control-details"
                                           title="Show FTP Server details">
                                            <span class="dashicons dashicons-hidden" style="font-size:18px;"></span>
                                        </a>

                                        <a href="javascript:void(0)"
                                           onclick="if (confirm('Are you sure you want to delete this SFTP Server? This action cannot be undone!')) { jQuery(this).closest('fieldset').remove(); deleteDrive('${driveName}'); }"
                                           style="text-decoration:none;"
                                           class="wpda_tooltip"
                                           title="Delete SFTP Server">
                                            <span class="dashicons dashicons-trash" style="font-size:18px;"></span>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="form-control form-control-details">
                                <label>Host</label>
                                <input type="text" name="sftp_host[]" value="${host}" />
                            </div>

                            <div class="form-control form-control-details">
                                <label>Username</label>
                                <input type="text" name="sftp_username[]" value="${username}" />
                            </div>

                            <div class="form-control form-control-details">
                                <label>Password</label>
                                <input type="password" name="sftp_password[]" value="${password}" />
                            </div>

                            <div class="form-control form-control-details">
                                <label>Port</label>
                                <input type="number" name="sftp_port[]" value="${port}" />
                            </div>

                            <div class="form-control form-control-details">
                                <label>Timeout</label>
                                <input type="number" name="sftp_timeout[]" value="${timeout}" />
                            </div>

                            <div class="form-control form-control-details">
                                <label>Directory</label>
                                <input type="text" name="sftp_directory[]" value="${directory}" />
                            </div>

                        </fieldset>
                    `);
                }
            </script>

            <?php
        }

    }

}