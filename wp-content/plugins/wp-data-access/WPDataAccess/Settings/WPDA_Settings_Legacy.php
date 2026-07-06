<?php

namespace WPDataAccess\Settings;

class WPDA_Settings_Legacy extends WPDA_Settings {
    private $vtab;

    private $legacy_tabs = array();

    protected final function add_content() {
        $this->vtab = ( isset( $_REQUEST['vtab'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['vtab'] ) ) : 'plugin' );
        // phpcs:ignore WordPress.Security.NonceVerification
        $this->legacy_tabs = array(
            'plugin'     => 'Plugin',
            'backend'    => 'Back-end',
            'frontend'   => 'Front-end',
            'datatables' => 'Data Tables',
        );
        $this->legacy_tabs['databackup'] = 'Data Backup';
        $this->legacy_tabs['roles'] = 'Roles';
        ?>

            <div class="wpda-legacy-container">
                <?php 
        $this->add_legacy_tabs();
        switch ( $this->vtab ) {
            case 'backend':
                $legacy_settings = new WPDA_Settings_Legacy_BackEnd();
                $legacy_settings->show();
                break;
            case 'frontend':
                $legacy_settings = new WPDA_Settings_Legacy_FrontEnd();
                $legacy_settings->show();
                break;
            case 'datatables':
                $legacy_settings = new WPDA_Settings_Legacy_DataTables();
                $legacy_settings->show();
                break;
            case 'dataforms':
            case 'dashboard':
            case 'databackup':
                $legacy_settings = new WPDA_Settings_Legacy_DataBackup();
                $legacy_settings->show();
                break;
            case 'roles':
                $legacy_settings = new WPDA_Settings_Legacy_ManageRoles();
                $legacy_settings->show();
                break;
            default:
                $legacy_settings = new WPDA_Settings_Legacy_Plugin();
                $legacy_settings->show();
                break;
        }
        ?>
            </div>

            <?php 
        $this->css();
    }

    private function add_legacy_tabs() {
        ?>

            <h2 class="nav-tab-wrapper nav-vertical">
                <?php 
        foreach ( $this->legacy_tabs as $tab => $name ) {
            $class = ( $tab === $this->vtab ? ' nav-tab-active' : '' );
            echo '<a class="nav-tab' . esc_attr( $class ) . '" href="?page=' . esc_attr( $this->page ) . '&tab=legacy&vtab=' . esc_attr( $tab ) . '" style="margin-left: 0">' . esc_attr( $name ) . '</a>';
        }
        ?>
            </h2>

            <?php 
    }

    private function css() {
        ?>

            <style>
                .wpda-legacy-container {
                    margin-top: 20px;
                    display: grid;
                    grid-template-columns: auto 1fr;
                    justify-content: space-between;
                    align-items: start;
                    gap: 20px;
                }

                h2.nav-tab-wrapper.nav-vertical {
                    display: flex;
                    flex-direction: column;
                    width: 180px;
                    margin-top: 0;
                    padding-top: 0;
                }
                .nav-tab-wrapper.nav-vertical .nav-tab.nav-tab-active {
                    margin-bottom: 0;
                }

                table.wpda-table-settings tr td {
                    padding-right: 20px;
                }
            </style>

            <?php 
    }

}
