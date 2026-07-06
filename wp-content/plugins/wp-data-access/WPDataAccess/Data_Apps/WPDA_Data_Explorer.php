<?php

namespace WPDataAccess\Data_Apps;

use WPDataAccess\Connection\WPDADB;
use WPDataAccess\Utilities\WPDA_Message_Box;
use WPDataAccess\Utilities\WPDA_Remote_Database;
use WPDataAccess\WPDA;
class WPDA_Data_Explorer extends WPDA_Container {
    private $dbs = null;

    private $pds = null;

    public function __construct( $args = array(), $shortcode_args = array() ) {
        parent::__construct( $args, $shortcode_args );
        $this->dbs = new WPDA_Remote_Database();
    }

    public function show() {
        if ( !WPDA::current_user_is_admin() ) {
            if ( !is_admin() && !$this->send_feedback() ) {
                return;
            }
            $this->show_feedback( __( 'Not authorized', 'wp-data-access' ) );
            return;
        }
        if ( is_admin() ) {
            $this->show_on_backend();
        } else {
            $this->show_on_frontend();
        }
        $this->add_client( 'de' );
    }

    private function show_on_backend() {
        ?>

			<div class="wrap wpda-explorer-backend">

				<h1 class="wp-heading-inline" style="margin-bottom: 18px">
					<?php 
        ?>
					Data Explorer
				</h1>

				<?php 
        $this->dbs->show();
        $this->show_on_frontend();
        ?>

			</div>

			<script>
				jQuery(function() {
					jQuery("#wpda_toolbar_icon_go_backup").on("click", function() {
						jQuery("#wpda_goto_backup").submit();
					});
				});
			</script>

			<?php 
    }

    private function show_on_frontend() {
        ?>

			<div class="wpda-pp-container">
				<div class="pp-container-explorer"></div>
			</div>

			<?php 
    }

    private function js() {
        // Add pds event
        ?>
			<script>
				jQuery(function() {
					jQuery("#wpda_pds_canvas .wpda_manage_databases_close").on("click", function() {
						jQuery('#wpda_pds_canvas').hide();
					});
				});
			</script>
			<?php 
    }

    private function css() {
        // Overwrite pds canvas styling
        ?>
			<style>
                #wpda_pds_canvas,
				#wpda_pds_message_box {
                    margin: 5px 0;
					position: relative;
                    border-radius: 4px;
                    background-color: #fff;
                    color: rgba(0, 0, 0, 0.87);
                    -webkit-transition: box-shadow 300ms cubic-bezier(0.4, 0, 0.2, 1) 0ms;
                    transition: box-shadow 300ms cubic-bezier(0.4, 0, 0.2, 1) 0ms;
                    box-shadow: 0px 2px 4px -1px rgba(0, 0, 0, 0.2), 0px 4px 5px 0px rgba(0, 0, 0, 0.14), 0px 1px 10px 0px rgba(0, 0, 0, 0.12);
				}
                #wpda_pds_canvas > div {
                    margin: 0;
                    border-radius: 4px;
                }
                #wpda_pds_canvas .container_pds {
					padding: 24px;
                    margin: 0;
                    border-radius: 4px;
				}
				#form_pds {
					margin-top: 30px;
                    margin-bottom: -10px;
				}
                #wpda_pds_canvas .container_pds fieldset > div > label:first-of-type,
                #wpda_pds_canvas .container_pds .database_item_label{
                	font-weight: normal;
					width: 160px;
					text-align: right;
                }
                #wpda_pds_canvas .wpda_manage_databases_close {
					display: block !important;
					position: absolute;
                    top: 22px;
                    right: 22px;
                    z-index: 9999999;
                    font-size: 1rem;
                }
                #wpda_pds_canvas .wpda_manage_databases_close .fas {
                    color: rgb(60, 67, 74);
                }
                #wpda_pds_message_box {
					padding: 24px;
                }
				#wpda_pds_message_box > div > strong {
					font-weight: 700;
				}
			</style>
			<?php 
    }

}
