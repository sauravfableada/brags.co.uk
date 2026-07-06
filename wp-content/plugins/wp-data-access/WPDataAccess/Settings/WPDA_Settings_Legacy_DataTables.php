<?php

namespace WPDataAccess\Settings;

use WPDataAccess\Utilities\WPDA_Message_Box;
use WPDataAccess\WPDA;
class WPDA_Settings_Legacy_DataTables extends WPDA_Settings_Legacy_Page {
    // jQuery DataTables language settings
    // DO NOT CHANGE THESE LANGUAGES!!!!
    // The language text is used in a URL. Changing a language results in a 404 for that language.
    const FRONTEND_LANG = array(
        'Afrikaans'              => 'af',
        'Albanian'               => 'sq',
        'Amharic'                => 'am',
        'Arabic'                 => 'ar',
        'Armenian'               => 'hy',
        'Azerbaijan'             => 'az-AZ',
        'Bangla'                 => 'bn',
        'Basque'                 => 'eu',
        'Belarusian'             => 'bg',
        'Bosnian'                => 'bs-BA',
        'Bulgarian'              => 'bg',
        'Catalan'                => 'ca',
        'Chinese'                => 'zh',
        'Chinese-traditional'    => 'zh-HANT',
        'Corsican'               => 'co',
        'Croatian'               => 'hr',
        'Czech'                  => 'cs',
        'Danish'                 => 'da',
        'Dutch'                  => 'nl-NL',
        'English'                => 'en-GB',
        'Esperanto'              => 'eo',
        'Estonian'               => 'et',
        'Filipino'               => 'fil',
        'Finnish'                => 'fi',
        'French'                 => 'fr-FR',
        'Galician'               => 'gl',
        'Georgian'               => 'ka',
        'German'                 => 'de-DE',
        'Greek'                  => 'el',
        'Gujarati'               => 'gu',
        'Hebrew'                 => 'he',
        'Hindi'                  => 'hi',
        'Hungarian'              => 'hu',
        'Icelandic'              => 'is',
        'Indonesian'             => 'id',
        'Indonesian-Alternative' => 'id-ALT',
        'Irish'                  => 'ga',
        'Italian'                => 'it-IT',
        'Japanese'               => 'ja',
        'Javanese'               => 'jv',
        'Kanada'                 => 'kn',
        'Kazakh'                 => 'kk',
        'Khmer'                  => 'km',
        'Korean'                 => 'ko',
        'Kurdish'                => 'ku',
        'Kyrgyz'                 => 'ky',
        'Lao'                    => 'lo',
        'Latvian'                => 'lv',
        'Lithuanian'             => 'lt',
        'Luganda'                => 'ug',
        'Macedonian'             => 'mk',
        'Malay'                  => 'ms',
        'Marathi'                => 'mr',
        'Mongolian'              => 'mn',
        'Nepali'                 => 'ne',
        'Norwegian-Bokmal'       => 'no-NB',
        'Norwegian-Nynorsk'      => 'no-NO',
        'Pashto'                 => 'ps',
        'Persian'                => 'fa',
        'Polish'                 => 'pl',
        'Portuguese'             => 'pt-PT',
        'Portuguese-Brasil'      => 'pt=BR',
        'Punjabi'                => 'pa',
        'Romanian'               => 'ro',
        'Rumantsch'              => 'rm',
        'Russian'                => 'ru',
        'Serbian'                => 'sr',
        'Serbian_latin'          => 'sr-SP',
        'Sinhala'                => 'si',
        'Slovak'                 => 'sk',
        'Slovenian'              => 'sl',
        'Spanish'                => 'es-ES',
        'Spanish-Argentina'      => 'es-AR',
        'Spanish-Chile'          => 'es-CL',
        'Spanish-Colombia'       => 'es-CO',
        'Spanish-Mexico'         => 'es-MX',
        'Swahili'                => 'sw',
        'Swedish'                => 'sv-SE',
        'Tajik'                  => 'tg',
        'Tamil'                  => 'ta',
        'telugu'                 => 'te',
        'Thai'                   => 'th',
        'Turkish'                => 'tr',
        'Ukrainian'              => 'ur',
        'Urdu'                   => 'ur',
        'Uzbek'                  => 'uz',
        'Uzbek-Cryllic'          => 'uz-CR',
        'Vietnamese'             => 'vi',
        'Welsh'                  => 'cy',
    );

    /**
     * Add data tables tab content
     *
     * See class documentation for flow explanation.
     *
     * @since   2.0.15
     */
    public function show() {
        if ( isset( $_REQUEST['action'] ) ) {
            $action = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) );
            // input var okay.
            // Security check.
            $wp_nonce = ( isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '' );
            // input var okay.
            if ( !wp_verify_nonce( $wp_nonce, 'wpda-datatables-settings-' . WPDA::get_current_user_login() ) ) {
                wp_die( __( 'ERROR: Not authorized', 'wp-data-access' ) );
            }
            if ( 'save' === $action ) {
                // Save options.
                if ( isset( $_REQUEST['publication_roles'] ) ) {
                    $publication_roles_request = ( isset( $_REQUEST['publication_roles'] ) ? $_REQUEST['publication_roles'] : null );
                    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
                    if ( is_array( $publication_roles_request ) ) {
                        $publication_roles = sanitize_text_field( wp_unslash( implode( ',', $publication_roles_request ) ) );
                    } else {
                        $publication_roles = '';
                    }
                } else {
                    $publication_roles = '';
                }
                WPDA::set_option( WPDA::OPTION_DP_PUBLICATION_ROLES, $publication_roles );
                if ( isset( $_REQUEST['json_editing'] ) ) {
                    WPDA::set_option( WPDA::OPTION_DP_JSON_EDITING, sanitize_text_field( wp_unslash( $_REQUEST['json_editing'] ) ) );
                }
                if ( isset( $_REQUEST['publication_style'] ) ) {
                    WPDA::set_option( WPDA::OPTION_DP_STYLE, sanitize_text_field( wp_unslash( $_REQUEST['publication_style'] ) ) );
                }
                if ( isset( $_REQUEST['load_datatables'] ) ) {
                    $load_datatables_request = sanitize_text_field( wp_unslash( $_REQUEST['load_datatables'] ) );
                    // input var okay.
                    if ( 'both' === $load_datatables_request || 'be' === $load_datatables_request ) {
                        WPDA::set_option( WPDA::OPTION_BE_LOAD_DATATABLES, 'on' );
                    } else {
                        WPDA::set_option( WPDA::OPTION_BE_LOAD_DATATABLES, 'off' );
                    }
                    if ( 'both' === $load_datatables_request || 'fe' === $load_datatables_request ) {
                        WPDA::set_option( WPDA::OPTION_FE_LOAD_DATATABLES, 'on' );
                    } else {
                        WPDA::set_option( WPDA::OPTION_FE_LOAD_DATATABLES, 'off' );
                    }
                }
                if ( isset( $_REQUEST['load_datatables_responsive'] ) ) {
                    $load_datatables_responsive_request = sanitize_text_field( wp_unslash( $_REQUEST['load_datatables_responsive'] ) );
                    // input var okay.
                    if ( 'both' === $load_datatables_responsive_request || 'be' === $load_datatables_responsive_request ) {
                        WPDA::set_option( WPDA::OPTION_BE_LOAD_DATATABLES_RESPONSE, 'on' );
                    } else {
                        WPDA::set_option( WPDA::OPTION_BE_LOAD_DATATABLES_RESPONSE, 'off' );
                    }
                    if ( 'both' === $load_datatables_responsive_request || 'fe' === $load_datatables_responsive_request ) {
                        WPDA::set_option( WPDA::OPTION_FE_LOAD_DATATABLES_RESPONSE, 'on' );
                    } else {
                        WPDA::set_option( WPDA::OPTION_FE_LOAD_DATATABLES_RESPONSE, 'off' );
                    }
                }
                if ( isset( $_REQUEST['language'] ) ) {
                    WPDA::set_option( WPDA::OPTION_DP_LANGUAGE, sanitize_text_field( wp_unslash( $_REQUEST['language'] ) ) );
                }
            } elseif ( 'setdefaults' === $action ) {
                // Set all datatables settings back to default.
                WPDA::set_option( WPDA::OPTION_DP_PUBLICATION_ROLES );
                WPDA::set_option( WPDA::OPTION_DP_STYLE );
                WPDA::set_option( WPDA::OPTION_DP_JSON_EDITING );
                WPDA::set_option( WPDA::OPTION_BE_LOAD_DATATABLES );
                WPDA::set_option( WPDA::OPTION_FE_LOAD_DATATABLES );
                WPDA::set_option( WPDA::OPTION_BE_LOAD_DATATABLES_RESPONSE );
                WPDA::set_option( WPDA::OPTION_FE_LOAD_DATATABLES_RESPONSE );
                WPDA::set_option( WPDA::OPTION_DP_LANGUAGE );
            }
            $msg = new WPDA_Message_Box(array(
                'message_text' => __( 'Settings saved', 'wp-data-access' ),
            ));
            $msg->box();
        }
        global $wp_roles;
        $roles = $wp_roles->roles;
        unset($roles['administrator']);
        $lov_roles = array();
        foreach ( $wp_roles->roles as $role => $val ) {
            array_push( $lov_roles, $role );
            //phpcs:ignore - 8.1 proof
        }
        $publication_roles = WPDA::get_option( WPDA::OPTION_DP_PUBLICATION_ROLES );
        $publication_style = WPDA::get_option( WPDA::OPTION_DP_STYLE );
        $json_editing = WPDA::get_option( WPDA::OPTION_DP_JSON_EDITING );
        $datatables_version = WPDA::get_option( WPDA::OPTION_WPDA_DATATABLES_VERSION );
        $be_load_datatables = WPDA::get_option( WPDA::OPTION_BE_LOAD_DATATABLES );
        $fe_load_datatables = WPDA::get_option( WPDA::OPTION_FE_LOAD_DATATABLES );
        if ( 'on' === $be_load_datatables && 'on' === $fe_load_datatables ) {
            $load_datatables = 'both';
        } elseif ( 'on' === $be_load_datatables ) {
            $load_datatables = 'be';
        } elseif ( 'on' === $fe_load_datatables ) {
            $load_datatables = 'fe';
        } else {
            $load_datatables = '';
        }
        $datatables_responsive_version = WPDA::get_option( WPDA::OPTION_WPDA_DATATABLES_RESPONSIVE_VERSION );
        $be_load_datatables_responsive = WPDA::get_option( WPDA::OPTION_BE_LOAD_DATATABLES_RESPONSE );
        $fe_load_datatables_responsive = WPDA::get_option( WPDA::OPTION_FE_LOAD_DATATABLES_RESPONSE );
        if ( 'on' === $be_load_datatables_responsive && 'on' === $fe_load_datatables_responsive ) {
            $load_datatables_responsive = 'both';
        } elseif ( 'on' === $be_load_datatables_responsive ) {
            $load_datatables_responsive = 'be';
        } elseif ( 'on' === $fe_load_datatables_responsive ) {
            $load_datatables_responsive = 'fe';
        } else {
            $load_datatables_responsive = '';
        }
        $current_language = WPDA::get_option( WPDA::OPTION_DP_LANGUAGE );
        ?>

            <form id="wpda_settings_datatables" method="post"
                  action="?page=<?php 
        echo esc_attr( $this->page );
        ?>&tab=legacy&vtab=datatables">
                <table class="wpda-table-settings">
                    <tr style="border-top: 1px solid #ccc">
                        <th><?php 
        echo __( 'JSON Editing', 'wp-data-access' );
        ?></th>
                        <td>
                            <label>
                                <input type="radio" name="json_editing" value="validate"
                                    <?php 
        echo ( 'validate' === $json_editing ? 'checked' : '' );
        ?>
                                ><?php 
        echo __( 'Use code editor with JSON validation', 'wp-data-access' );
        ?>
                            </label>
                            <br/>
                            <label>
                                <input type="radio" name="json_editing" value="text"
                                    <?php 
        echo ( 'text' === $json_editing ? 'checked' : '' );
        ?>
                                ><?php 
        echo __( 'Use textarea without JSON validation', 'wp-data-access' );
        ?>
                            </label>
                        </td>
                    </tr>
                    <?php 
        ?>
                    <tr>
                        <th>Data Tables Tool Access</th>
                        <td><div style="padding-bottom:10px">
                                <?php 
        echo __( 'Select WordPress roles allowed to access Data Tables', 'wp-data-access' );
        ?>
                            </div>
                            <select name="publication_roles[]" multiple size="6">
                                <?php 
        foreach ( $roles as $key => $role ) {
            $selected = ( false !== strpos( $publication_roles, $key ) ? 'selected' : '' );
            ?>
                                    <option value="<?php 
            echo esc_attr( $key );
            ?>" <?php 
            echo esc_attr( $selected );
            ?>>
                                        <?php 
            echo esc_attr( $role['name'] );
            ?>
                                    </option>
                                    <?php 
        }
        ?>
                            </select>
                            <div style="margin-top:10px">
                                Administrators have access by default
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th><span class="dashicons dashicons-info" style="float:right;font-size:300%;"></span></th>
                        <td>
                            <span class="dashicons dashicons-yes"></span>
                            <?php 
        echo __( 'Users have readonly access to tables to which you have granted access in Front-end Settings only', 'wp-data-access' );
        ?>
                            <br/>
                            <span class="dashicons dashicons-yes"></span>
                            <?php 
        echo __( 'Table access is automatically granted to tables used in Data Tables', 'wp-data-access' );
        ?>
                        </td>
                    </tr>
                    <tr>
                        <th>jQuery DataTables</th>
                        <td>
                            <label>
                                <?php 
        echo sprintf( __( 'Load jQuery DataTables (version %s) scripts and styles', 'wp-data-access' ), esc_attr( $datatables_version ) );
        ?>
                            </label>
                            <div style="height:10px"></div>
                            <labeL>
                                <input type="radio" name="load_datatables" value="both"
                                    <?php 
        echo ( 'both' === $load_datatables ? 'checked' : '' );
        ?>
                                ><?php 
        echo __( 'In WordPress Back-end and Front-end', 'wp-data-access' );
        ?>
                            </labeL>
                            <br/>
                            <labeL>
                                <input type="radio" name="load_datatables" value="be"
                                    <?php 
        echo ( 'be' === $load_datatables ? 'checked' : '' );
        ?>
                                ><?php 
        echo __( 'In WordPress Back-end only ', 'wp-data-access' );
        ?>
                            </labeL>
                            <br/>
                            <labeL>
                                <input type="radio" name="load_datatables" value="fe"
                                    <?php 
        echo ( 'fe' === $load_datatables ? 'checked' : '' );
        ?>
                                ><?php 
        echo __( 'In WordPress Front-end only', 'wp-data-access' );
        ?>
                            </labeL>
                            <br/>
                            <labeL>
                                <input type="radio" name="load_datatables" value=""
                                    <?php 
        echo ( '' === $load_datatables ? 'checked' : '' );
        ?>
                                ><?php 
        echo __( 'Do not load jQuery DataTables', 'wp-data-access' );
        ?>
                            </labeL>
                        </td>
                    </tr>
                    <tr>
                        <th>jQuery DataTables Responsive</th>
                        <td>
                            <label>
                                <?php 
        echo sprintf( __( 'Load jQuery DataTables Responsive (version %s) scripts and styles', 'wp-data-access' ), esc_attr( $datatables_responsive_version ) );
        ?>
                            </label>
                            <div style="height:10px"></div>
                            <label>
                                <input type="radio" name="load_datatables_responsive" value="both"
                                    <?php 
        echo ( 'both' === $load_datatables_responsive ? 'checked' : '' );
        ?>
                                ><?php 
        echo __( 'In WordPress Back-end and Front-end', 'wp-data-access' );
        ?>
                            </label>
                            <br/>
                            <label>
                                <input type="radio" name="load_datatables_responsive" value="be"
                                    <?php 
        echo ( 'be' === $load_datatables_responsive ? 'checked' : '' );
        ?>
                                ><?php 
        echo __( 'In WordPress Back-end only ', 'wp-data-access' );
        ?>
                            </label>
                            <br/>
                            <label>
                                <input type="radio" name="load_datatables_responsive" value="fe"
                                    <?php 
        echo ( 'fe' === $load_datatables_responsive ? 'checked' : '' );
        ?>
                                ><?php 
        echo __( 'In WordPress Front-end only', 'wp-data-access' );
        ?>
                            </label>
                            <br/>
                            <label>
                                <input type="radio" name="load_datatables_responsive" value=""
                                    <?php 
        echo ( '' === $load_datatables_responsive ? 'checked' : '' );
        ?>
                                ><?php 
        echo __( 'Do not load jQuery DataTables Responsive', 'wp-data-access' );
        ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php 
        echo __( 'Front-End Language', 'wp-data-access' );
        ?></th>
                        <td>
                            <select name="language">
                                <?php 
        foreach ( self::FRONTEND_LANG as $language => $code ) {
            $checked = ( $current_language === $language ? ' selected' : '' );
            echo "<option value='{$language}'{$checked}>{$language}</option>";
            // phpcs:ignore WordPress.Security.EscapeOutput
        }
        ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><span class="dashicons dashicons-info" style="float:right;font-size:300%;"></span></th>
                        <td>
                            <span class="dashicons dashicons-yes"></span>
                            <?php 
        echo __( 'jQuery DataTables (+Responsive) is needed in the Front-end to support data tables on your website', 'wp-data-access' );
        ?>
                            <br/>
                            <span class="dashicons dashicons-yes"></span>
                            <?php 
        echo __( 'jQuery DataTables (+Responsive) is needed in the Back-end to test data tables in the WordPress dashboard', 'wp-data-access' );
        ?>
                            <br/>
                            <span class="dashicons dashicons-yes"></span>
                            <?php 
        echo __( 'If you have already loaded jQuery DataTables for other purposes disable loading them to prevent duplication errors', 'wp-data-access' );
        ?>
                        </td>
                    </tr>
                </table>
                <div class="wpda-table-settings-button">
                    <input type="hidden" name="action" value="save"/>
                    <button type="submit" class="button button-primary">
                        <i class="fas fa-check wpda_icon_on_button"></i>
                        <?php 
        echo __( 'Save DataTables Settings', 'wp-data-access' );
        ?>
                    </button>
                    <a href="javascript:void(0)"
                       onclick="if (confirm('<?php 
        echo __( 'Reset to defaults?', 'wp-data-access' );
        ?>')) {
                           jQuery('input[name=&quot;action&quot;]').val('setdefaults');
                           jQuery('#wpda_settings_datatables').trigger('submit')
                           }"
                       class="button">
                        <i class="fas fa-times-circle wpda_icon_on_button"></i>
                        <?php 
        echo __( 'Reset DataTables Settings To Defaults', 'wp-data-access' );
        ?>
                    </a>
                </div>
                <?php 
        wp_nonce_field( 'wpda-datatables-settings-' . WPDA::get_current_user_login(), '_wpnonce', false );
        ?>
            </form>

            <?php 
    }

}
