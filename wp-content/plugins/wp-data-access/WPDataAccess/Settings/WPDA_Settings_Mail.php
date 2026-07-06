<?php

namespace WPDataAccess\Settings {

    use WPDataAccess\Utilities\WPDA_Mail;
    use WPDataAccess\Utilities\WPDA_Message_Box;
    use WPDataAccess\WPDA;

    class WPDA_Settings_Mail extends WPDA_Settings {

        protected function add_content() {

            if ( isset( $_REQUEST['action'] ) ) {
                $action = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ); // input var okay.

                // Security check.
                $wp_nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : ''; // input var okay.
                if ( ! wp_verify_nonce( $wp_nonce, 'wpda-mail-settings-' . WPDA::get_current_user_login() ) ) {
                    wp_die( __( 'ERROR: Not authorized', 'wp-data-access' ) );
                }

                if ( 'save' === $action ) {

                    $mail = array(
                        'activate'     => isset( $_POST['activate'] ) ? sanitize_text_field( wp_unslash( $_POST['activate'] ) ) : '',
                        'host'         => sanitize_text_field( wp_unslash( $_POST['host'] ) ),
                        'port'         => sanitize_text_field( wp_unslash( $_POST['port'] ) ),
                        'authenticate' => isset( $_POST['authenticate'] ) ? sanitize_text_field( wp_unslash( $_POST['authenticate'] ) ) : '',
                        'encryption'   => sanitize_text_field( wp_unslash( $_POST['encryption'] ) ),
                        'skip_verify'  => isset( $_POST['skip_verify'] ) ? sanitize_text_field( wp_unslash( $_POST['skip_verify'] ) ) : '',
                        'username'     => sanitize_text_field( wp_unslash( $_POST['username'] ) ),
                        'password'     => sanitize_text_field( wp_unslash( $_POST['password'] ) ),
                        'debug'        => sanitize_text_field( wp_unslash( $_POST['debug'] ) ),
                    );

                    WPDA_Mail::update_option( $mail );

                } elseif ( 'setdefaults' === $action ) {

                    WPDA_Mail::delete_option();
                }

                $msg = new WPDA_Message_Box(
                    array(
                        'message_text' => __( 'Settings saved', 'wp-data-access' ),
                    )
                );
                $msg->box();
            }

            $option = WPDA_Mail::get_option();

            $activate     = $option['activate'] ?? '';
            $host         = $option['host'] ?? '';
            $port         = $option['port'] ?? '';
            $authenticate = $option['authenticate'] ?? '';
            $encryption   = $option['encryption'] ?? '';
            $skip_verify  = $option['skip_verify'] ?? '';
            $username     = $option['username'] ?? '';
            $password     = $option['password'] ?? '';
            $debug        = $option['debug'] ?? '';

            ?>

            <form
                id="wpda_settings_mail"
                method="post"
                action="?page=<?php echo esc_attr( $this->page ); ?>&tab=mail"
            >
                <table class="wpda-table-settings">
                    <tr>
                        <th>
                            SMTP Mail Server Settings
                            <br/><br/>
                            <label style="font-weight: normal">
                                <input
                                    type="checkbox"
                                    name="activate"
                                    <?php echo 'on' === $activate ? 'checked' : ''; ?>
                                /> <?php echo __( 'Activate', 'wp-data-access' ); ?>
                            </label>
                        </th>
                        <td>
                            SMTP mail server settings are used to send scheduled SQL Query Builder results.
                            <br/><br/>
                            Test your mail settings using the SEND TEST MAIL button before activating.
                        </td>
                    </tr>
                    <tr>
                        <th>
                            Host
                        </th>
                        <td>
                            <input
                                type="text"
                                name="host"
                                value="<?php echo esc_attr( $host ); ?>"
                                style="min-width: 300px"
                            />
                        </td>
                    </tr>
                    <tr>
                        <th>
                            Port
                        </th>
                        <td>
                            <input
                                type="number"
                                name="port"
                                value="<?php echo esc_attr( $port ); ?>"
                                style="min-width: 300px"
                            />
                        </td>
                    </tr>
                    <tr>
                        <th></th>
                        <td>
                            <label style="font-weight: bold">
                                <input
                                    type="checkbox"
                                    name="authenticate"
                                    <?php echo 'on' === $authenticate ? 'checked' : ''; ?>
                                /> SMTP authentication
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            Username
                        </th>
                        <td>
                            <input
                                type="text"
                                name="username"
                                value="<?php echo esc_attr( $username ); ?>"
                                style="min-width: 300px"
                            />
                        </td>
                    </tr>
                    <tr>
                        <th>
                            Password
                        </th>
                        <td>
                            <input
                                type="password"
                                name="password"
                                value="<?php echo esc_attr( $password ); ?>"
                                style="min-width: 300px"
                            />
                        </td>
                    </tr>
                    <tr>
                        <th>
                            Encryption
                        </th>
                        <td>
                            <label style="font-weight: bold">
                                <select name="encryption">
                                    <option key="" value="" <?php echo '' === $encryption ? 'selected' : ''; ?>>none</option>
                                    <option key="ssl" value="ssl" <?php echo 'ssl' === $encryption ? 'selected' : ''; ?>>ssl</option>
                                    <option key="tls" value="tls" <?php echo 'tls' === $encryption ? 'selected' : ''; ?>>tls</option>
                                </select>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th></th>
                        <td>
                            <label style="font-weight: bold">
                                <input
                                    type="checkbox"
                                    name="skip_verify"
                                    <?php echo 'on' === $skip_verify ? 'checked' : ''; ?>
                                /> Skip peer verification
                            </label><br>
                            <p style="padding-bottom: 0; margin-bottom: 0">
                                Use for testing on development servers. <strong>Disable in production!</strong>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            Test
                        </th>
                        <td>
                            <input
                                type="email"
                                id="email"
                                style="min-width: 300px"
                                placeholder="example@domain.com"
                            />
                            <button
                                    class="button button-primary"
                                    onclick="jQuery('#mailSpinner').show(); sendMail(jQuery('#email').val(), 'WP Data Acces', 'Test message from WP Data Access.', <?php echo '0' === $debug ? 'false' : 'true'; ?>); return false;"
                            >
                                SEND TEST MAIL
                            </button>
                            <span id="mailSpinner" style="font-size: 16px; margin-left: 2px; display: none;">
                                <i class="fa-solid fa-sync fa-spin"></i>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            Debug mode
                        </th>
                        <td>
                            <label>
                                <select name="debug">
                                    <option key="0" value="0" <?php echo '' === $debug || '1' === $debug ? 'selected' : ''; ?>>off</option>
                                    <option key="1" value="1" <?php echo '1' === $debug ? 'selected' : ''; ?>>client</option>
                                    <option key="2" value="2" <?php echo '2' === $debug ? 'selected' : ''; ?>>server</option>
                                    <option key="3" value="3" <?php echo '3' === $debug ? 'selected' : ''; ?>>connection</option>
                                    <option key="4" value="4" <?php echo '4' === $debug ? 'selected' : ''; ?>>all</option>
                                </select> Save before sending a test mail. <strong>Turn off in production!</strong>
                            </label>
                        </td>
                    </tr>
                    <tr style="display: none;">
                        <th>
                            Debug info
                        </th>
                        <td>
                            <div id="debugInfoContainer"></div>
                        </td>
                    </tr>
                </table>

                <div class="wpda-table-settings-button">
                    <input type="hidden" name="action" value="save"/>
                    <button type="submit" class="button button-primary">
                        <i class="fas fa-check wpda_icon_on_button"></i>
                        <?php echo __( 'Save Mail Settings', 'wp-data-access' ); ?>
                    </button>
                    <a href="javascript:void(0)"
                       onclick="if (confirm('<?php echo __( 'Reset to defaults?', 'wp-data-access' ); ?>')) {
                           jQuery('input[name=\'action\']').val('setdefaults');
                           jQuery('#wpda_settings_mail').trigger('submit');
                           }"
                       class="button button-secondary">
                        <i class="fas fa-times-circle wpda_icon_on_button"></i>
                        <?php echo __( 'Reset Mail Settings To Defaults', 'wp-data-access' ); ?>
                    </a>
                </div>

                <?php wp_nonce_field( 'wpda-mail-settings-' . WPDA::get_current_user_login(), '_wpnonce', false ); ?>

            </form>

            <?php

        }

    }

}