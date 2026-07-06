<?php
/**
 * WPLA_ProfilesPage class
 * 
 */

class WPLA_ProfilesPage extends WPLA_Page {

	const slug = 'profiles';

	public $profilesTable;

	var $detail_fields = array(
		'price_add_amount',
		'price_add_percentage',
		'variations_mode',
		'b2b_price',
	);

	public function onWpInit() {

		// Add custom screen options
		$load_action = "load-".$this->main_admin_menu_slug."_page_wpla-".self::slug;
		add_action( $load_action, array( &$this, 'addScreenOptions' ) );

		$this->handleSubmitOnInit();
	}

	public function onWpAdminMenu() {
		parent::onWpAdminMenu();

		add_submenu_page( self::ParentMenuId, $this->getSubmenuPageTitle( 'Profiles' ), __( 'Profiles', 'wp-lister-for-amazon' ),
						  self::ParentPermissions, $this->getSubmenuId( 'profiles' ), array( &$this, 'displayProfilesPage' ) );
	}

	function addScreenOptions() {
		
		// render table options
		$option = 'per_page';
		$args = array(
	    	'label' => 'Profiles',
	        'default' => 20,
	        'option' => 'profiles_per_page'
	        );
		add_screen_option( $option, $args );
		$this->profilesTable = new WPLA_ProfilesTable();
	
		// load styles and scripts for this page only
		add_action( 'admin_print_styles', array( &$this, 'onWpPrintStyles' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'onWpEnqueueScripts' ) );		

	    // add_thickbox();
		wp_enqueue_script( 'thickbox' );
		wp_enqueue_style( 'thickbox' );

		wp_enqueue_script( 'wpla' );
		wp_enqueue_script( 'jquery-blockui' );

	}
	
	public function handleSubmitOnInit() {
		if ( ! current_user_can('manage_amazon_listings') ) return;

		// handle save profile
		if ( $this->requestAction() == 'wpla_save_profile' ) {
		    check_admin_referer( 'wpla_save_profile' );

			$this->saveProfile();
			if ( @$_POST['return_to'] == 'listings' ) {
				$return_url = get_admin_url().'admin.php?page=wpla';
		        if ( isset($_REQUEST['listing_status']) )	$return_url = add_query_arg( 'listing_status', 	wpla_clean($_REQUEST['listing_status']), $return_url );
		        if ( isset($_REQUEST['profile_id']) )		$return_url = add_query_arg( 'profile_id', 		wpla_clean($_REQUEST['profile_id']), 	 $return_url );
		        if ( isset($_REQUEST['account_id']) )		$return_url = add_query_arg( 'account_id', 		wpla_clean($_REQUEST['account_id']), 	 $return_url );
		        if ( isset($_REQUEST['s']) )				$return_url = add_query_arg( 's', 				wpla_clean($_REQUEST['s']), 			 $return_url );
				wp_redirect( $return_url );
			}
		}

		// handle duplicate profile
		if ( $this->requestAction() == 'wpla_duplicate_profile' ) {
		    check_admin_referer( 'wpla_duplicate_profile' );
			$this->duplicateProfile();
		}
		// handle upload profile
		if ( $this->requestAction() == 'wpla_upload_listing_profile' ) {
		    check_admin_referer( 'wpla_upload_listing_profile' );
			$this->uploadProfile();
		}
		// handle download profile
		if ( isset( $_REQUEST['profile'] ) && ( $this->requestAction() == 'wpla_download_listing_profile' ) ) {
		    check_admin_referer( 'wpla_download_listing_profile' );
			$this->downloadProfile( wpla_clean($_REQUEST['profile']) );
		}

	}
	
	public function handleActions() {
		if ( ! current_user_can('manage_amazon_listings') ) return;
	
		// handle delete action
		if ( $this->requestAction() == 'wpla_delete_profile' ) {
		    check_admin_referer( 'bulk-profiles' );
			$this->deleteProfiles( wpla_clean($_REQUEST['amazon_profile']) );
		}

		// handle reconvert profile fields action
		if ( $this->requestAction() == 'wpla_reconvert_profile_fields' ) {
		    check_admin_referer( 'bulk-profiles' );
			$this->reconvertProfileFields( wpla_clean($_REQUEST['amazon_profile']) );
		}

	}

	public function displayProfilesPage() {
		$this->check_wplister_setup();
	
		// handle actions and show notes
		$this->handleActions();

		// edit profile
		if ( ( $this->requestAction() == 'edit' ) || ( $this->requestAction() == 'add_new_profile' ) ) {
			return $this->displayEditPage();			
		} 

	    // create table and fetch items to show
	    $this->profilesTable->prepare_items();
		$needs_conversion = WPLA_AmazonProfile::getProfilesThatNeedConversion();

		// process errors 		
		// if ($this->IC->message) $this->showMessage( $this->IC->message,1 );
		
		$aData = array(
			'plugin_url'				=> self::$PLUGIN_URL,
			'message'					=> $this->message,

			'profilesTable'				=> $this->profilesTable,
			'needs_conversion'          => count($needs_conversion),
			'form_action'				=> 'admin.php?page='.self::ParentMenuId.'-profiles'
		);
		$this->display( 'profiles_page', $aData );

	}


	public function displayEditPage() {
	
		// init model

		// get item
		if ( $this->requestAction() == 'add_new_profile' ) {
			$profile = new WPLA_AmazonProfile();
		} else {
			$profile = new WPLA_AmazonProfile( wpla_clean($_REQUEST['profile']) );
		}

		$account_id = $profile->account_id ?: 0;
		
		// $listingsModel = new ListingsModel();
		// $prepared_listings  = $listingsModel->getAllPreparedWithProfile( $item['profile_id'] );
		// $verified_listings  = $listingsModel->getAllVerifiedWithProfile( $item['profile_id'] );
		// $published_listings = $listingsModel->getAllPublishedWithProfile( $item['profile_id'] );
		// $ended_listings     = $listingsModel->getAllEndedWithProfile( $item['profile_id'] );

		$lm = new WPLA_ListingsModel();
		$listings  = $profile->profile_id ? $lm->findAllListingsByColumn( $profile->profile_id, 'profile_id' ) : array();

		$accounts  = WPLA_AmazonAccount::getAll();
		$templates = WPLA_AmazonFeedTemplate::getAll();

		// separate ListingLoader templates
		$category_templates = array();
		$liloader_templates = array();
		foreach ($templates as $tpl) {
			if ( $tpl->title == 'Offer' ) {
				$tpl->title = "Listing Loader";
				$liloader_templates[] = $tpl;
			} elseif ( $tpl->title == 'Inventory Loader' ) {
				$liloader_templates[] = $tpl;
			} else {
				$category_templates[] = $tpl;
			}
		}

		$aData = array(
			'plugin_url'				=> self::$PLUGIN_URL,
			'message'					=> $this->message,

			'profile'                   => $profile,
			'accounts'                  => $accounts,
			// 'templates'                 => $templates,
			'category_templates'        => $category_templates,
			'liloader_templates'        => $liloader_templates,
			'account_id'                => $account_id ?? 0,
			'product_types'             => \WPLab\Amazon\Models\AmazonProductTypesModel::getTypesAsDropdownOptions( $account_id ),
			'profile_listings'          => $listings,
			'profile_details'           => maybe_unserialize( $profile->details ),

			// 'prepared_listings'         => $prepared_listings,
			// 'verified_listings'         => $verified_listings,
			// 'published_listings'        => $published_listings,
			// 'ended_listings'            => $ended_listings,
			
			'form_action'				=> 'admin.php?page='.self::ParentMenuId.'-profiles'
		);
		// $this->display( 'profiles_edit_page', array_merge( $aData, $profile ) );
		$this->display( 'profiles_edit_page', $aData );
		
	}

	private function saveProfile() {
		if ( ! current_user_can('manage_amazon_listings') ) return;

		// init profile
		$profile_id = $this->getValueFromPost( 'profile_id' );
		$profile = new WPLA_AmazonProfile( $profile_id );
		
		// Store old B2B price for comparison (to detect clearing)
		$old_b2b_price = '';
		if ( $profile_id && $profile->id ) {
			$old_profile_details = maybe_unserialize( $profile->details );
			$old_b2b_price = isset( $old_profile_details['b2b_price'] ) ? $old_profile_details['b2b_price'] : '';
		}

		// fill in post data
		$post_data = $this->getPreprocessedPostData();
		$profile->fillFromArray( $post_data );

		// add field data
		$profile->fields = maybe_serialize( $this->getPreprocessedPostData( 'tpl_col_', false ) );

		// insert or update
		if ( $profile_id ) {
			$profile->update();
			$this->showMessage( __( 'Profile updated.', 'wp-lister-for-amazon' ) );
		} else {
			$profile->add();
			$this->showMessage( __( 'Profile added.', 'wp-lister-for-amazon' ) );
		}
		
		// Check if B2B price was cleared and trigger PATCH feed if needed
		$new_b2b_price = isset( $_POST['wpla_b2b_price'] ) ? trim( $_POST['wpla_b2b_price'] ) : '';
		
		// If B2B price was cleared (had value, now empty), trigger PATCH feed for all products using this profile
		if ( $profile_id && ! empty( $old_b2b_price ) && empty( $new_b2b_price ) ) {
			WPLA()->logger->info('Profile B2B price cleared for profile #' . $profile_id . ' (was: "' . $old_b2b_price . '", now: "' . $new_b2b_price . '") - triggering PATCH feeds');
			$this->triggerB2BPriceDeletionFeedForProfile( $profile_id );
		}

		// error handling
		// if ($result===false) {
		// 	$this->showMessage( "There was a problem saving your profile.<br>SQL:<pre>".$wpdb->last_query.'</pre>'.$wpdb->last_error, true );	
		// } else {
		// }

		// prepare for updating items
		// $profile    = new WPLA_AmazonProfile( $profile_id );
		$listingsModel = new WPLA_ListingsModel();

		// re-apply profile to all published
		if ( ! $profile_id ) return;

        // handle delayed update option
        if ( isset( $_POST['wpla_delay_profile_application'] ) ) {
            update_option( 'wpla_job_reapply_profile_id', $profile_id );
            return;
        }

		$items = $listingsModel->getWhere( 'profile_id', $profile_id );
        $listingsModel->applyProfileToListings( $profile, $items );
		$this->showMessage( sprintf( __('%s items updated.','wplister'), count($items) ) );			

	} // saveProfile()

	public function getPreprocessedPostData( $prefix = 'wpla_', $skip_empty = false ) {
		$data 	 = array();
		$details = array();
		// echo "<pre>";print_r($_POST);echo"</pre>";die();

        //$postdata = WPLA_FeedTemplateHelper::get_real_input('post');
		$postdata = WPLA_AmazonWebHelper::getFlatInput('POST');
		foreach ( $postdata as $key => $val ) {
//		    $key = WPLA_FeedTemplateHelper::restore_field_name( $key );
			$key = WPLA_AmazonWebHelper::restoreFieldName( $key );

			// Skip empty fields if $skip_empty is TRUE AND the field is not a marketplace_id/language_tag field
			if ( strpos( $key, 'marketplace_id') === false && strpos( $key, 'language_tag') === false && empty($val) && !is_numeric($val) && $skip_empty ) {
				continue;
			}

			if ( substr( $key, 0, strlen($prefix) ) == $prefix ) {
				$field = substr( $key, strlen($prefix) );

				$val   = stripslashes_deep( $val );
				
				if ( in_array($field, $this->detail_fields) ) {
					// store in details column
					$details[$field] = trim($val);
				} else {
					// store as sql column
					$data[$field] = $val;	
				}
			}
		}

		// serialize details column
		$data['details'] = serialize($details);

		return $data;
	}

	private function duplicateProfile() {
				
		// duplicate profile
		$new_profile_id = WPLA_AmazonProfile::duplicateProfile( wpla_clean($_REQUEST['profile']) );
		
		// redirect to edit new profile
		wp_redirect( get_admin_url().'admin.php?page=wpla-profiles&action=edit&profile='.$new_profile_id );

	}


	private function downloadProfile( $profile_id ) {

		// load profile
		$profile_id = intval( $profile_id );
		$data = WPLA_AmazonProfile::getProfile( $profile_id );
		$data = get_object_vars( $data ); // cast object into an array

		// preprocess data
		unset( $data['profile_id'] );			// profile id will be generated on upload
		$data['details'] = maybe_unserialize( $data['details'] );
		$data['fields']  = maybe_unserialize( $data['fields'] );
		$profile_name = str_replace( '_', ' ', sanitize_file_name( str_replace( ' ', '_', $data['profile_name'] ) ) );

    	// send as json
    	$filename = "WPLA profile $profile_id - $profile_name"; 
        header('Content-Disposition: attachment; filename='.$filename.'.json');
        echo json_encode( $data );
        exit;	
	}


    private function uploadProfile() {

        $uploaded_file = $this->process_upload();
        if ( ! $uploaded_file ) return;

        $result = $this->import_json( $uploaded_file );

        if ( $result ) {
            wpla_show_message( 'Profile "' . $result . '" was uploaded and restored successfully.');
        } else {
            wpla_show_message( 'The uploaded file could not be imported. Please make sure you use a JSON backup file exported from this plugin.','warn');                
        }

        // clean up
        if ( file_exists($uploaded_file) ) unlink($uploaded_file);
    }

    // process content of JSON file
    private function import_json( $uploaded_file ) {
        global $wpdb;

        $json = file_get_contents( $uploaded_file );
        $data = json_decode( $json, true );

        // prepare data
        $profile_name = $data['profile_name'];
        $data['profile_name'] .= ' (restored)';
        $data['details'] = maybe_serialize( $data['details'] );
        $data['fields']  = maybe_serialize( $data['fields'] );
		if ( ! $profile_name ) return false;

        // insert into db
		$result = $wpdb->insert( $wpdb->prefix.'amazon_profiles', $data );
		if ( ! $result ) return false;

		return $profile_name;
    }

    // process file upload
    private function process_upload() {

        if ( isset( $_FILES['wpla_file_upload_profile'] ) ) {

			// set target path
			$upload_dir  = wp_upload_dir(); // Array of key => value pairs
            $target_path = $upload_dir['basedir'].'/wpla-tmp-import-file.json';

            // delete last import
            if ( file_exists($target_path) ) unlink($target_path);

            if ( move_uploaded_file( $_FILES['wpla_file_upload_profile']['tmp_name'], $target_path ) ) {
                return $target_path;
            } else {
                echo "There was an error uploading the file, please try again!";
            }
            return false;
        }
        echo "no file_upload set";
        return false;
    }


	public function deleteProfiles( $profiles ) {
		if ( ! is_array($profiles) ) $profiles = array( $profiles );
		$count = 0;

		foreach ($profiles as $id) {
			if ( ! $id ) continue;
			
			// check if there are listings using this profile
			$lm = new WPLA_ListingsModel();
			$listings = $lm->findAllListingsByColumn( $id, 'profile_id' );
			if ( ! empty($listings) ) {
				$this->showMessage('This profile is applied to '.count($listings).' listings and can not be deleted.',1,1);
				continue;
			}

			$profile = new WPLA_AmazonProfile( $id );
			$profile->delete();
			$count++;
		}

		if ( $count )
			$this->showMessage( sprintf( __( '%s profile(s) were removed.', 'wp-lister-for-amazon' ), $count ) );
	}

	/**
	 * Re-convert profile fields using stored old field data and updated mappings
	 *
	 * @param array|int $profiles Profile ID(s) to reconvert
	 */
	public function reconvertProfileFields( $profiles ) {
		if ( ! is_array($profiles) ) $profiles = array( $profiles );
		$count = 0;
		$skipped = 0;

		foreach ($profiles as $id) {
			if ( ! $id ) continue;
			
			$profile = new WPLA_AmazonProfile( $id );
			if ( ! $profile->profile_id ) {
				$skipped++;
				continue;
			}

			// Check if profile has old fields data
			$fields = maybe_unserialize( $profile->fields );
			if ( empty( $fields['__old_fields'] ) ) {
				$skipped++;
				continue;
			}

			// Reconstruct original fields from old data
			$old_fields = $fields['__old_fields'];
			
			// Create converter instance
			$converter = new \WPLab\Amazon\Helper\ProfileProductTypeConverter( $profile, $profile->product_type );
			
			// Re-run conversion with updated mappings
			$converted_fields = $converter->convertFromArray( $old_fields );
			
			// Update profile with new converted fields
			$profile->fields = maybe_serialize( $converted_fields );
			$profile->update();
			
			$count++;
		}

		// Show results
		if ( $count ) {
			$this->showMessage( sprintf( __( '%s profile(s) were re-converted with updated field mappings.', 'wp-lister-for-amazon' ), $count ) );
		}
		
		if ( $skipped ) {
			$this->showMessage( sprintf( __( '%s profile(s) were skipped (no conversion data available or not converted profiles).', 'wp-lister-for-amazon' ), $skipped ), 'warn' );
		}
	}
	
	public function onWpPrintStyles() {

		// jqueryFileTree
		wp_register_style('jqueryFileTree_style', self::$PLUGIN_URL.'js/jqueryFileTree/jqueryFileTree.css' );
		wp_enqueue_style('jqueryFileTree_style'); 

	}

	public function onWpEnqueueScripts() {

		// jqueryFileTree
		wp_register_script( 'jqueryFileTree', self::$PLUGIN_URL.'js/jqueryFileTree/jqueryFileTree.js', array( 'jquery' ) );
		wp_enqueue_script( 'jqueryFileTree' );

        if ( !wp_style_is( 'select2', 'registered' ) ) {
            wp_register_style( 'select2', plugins_url( 'assets/css/select2.css', WC_PLUGIN_FILE ) );
        }
        //$reg = wp_style_is( 'select2', 'registered' );

        wp_enqueue_script( 'selectWoo' );
        wp_enqueue_style( 'select2' );

	}
	
	/**
	 * Trigger PATCH feeds to remove B2B pricing from all products using a profile
	 *
	 * @param int $profile_id Profile ID that had B2B pricing removed
	 * @return void
	 */
	private function triggerB2BPriceDeletionFeedForProfile( $profile_id ) {
		try {
			WPLA()->logger->info('Triggering B2B price deletion PATCH feeds for all products using profile #' . $profile_id);

			// Get all listings using this profile
			$listingsModel = new WPLA_ListingsModel();
			$all_listings = $listingsModel->getWhere( 'profile_id', $profile_id );
			
			// Filter for listings that can be updated (online, changed status)
			$active_listings = array_filter( $all_listings, function( $listing ) {
				return in_array( $listing->status, ['online', 'changed'] );
			});
			
			// Convert objects to arrays for the feed builder
			$listings = array_map( function( $listing ) {
				return is_object( $listing ) ? (array) $listing : $listing;
			}, $active_listings );

			if ( empty( $listings ) ) {
				WPLA()->logger->info('No updatable listings found for profile #' . $profile_id . ' - skipping B2B deletion PATCH');
				return;
			}

			// Group listings by account to create separate feeds
			$listings_by_account = array();
			foreach ( $listings as $listing ) {
				$account_id = $listing['account_id'];
				if ( ! isset( $listings_by_account[ $account_id ] ) ) {
					$listings_by_account[ $account_id ] = array();
				}
				$listings_by_account[ $account_id ][] = $listing;
			}

			// Create PATCH feeds for each account
			foreach ( $listings_by_account as $account_id => $account_listings ) {
				if ( ! isset( WPLA()->accounts[ $account_id ] ) ) {
					WPLA()->logger->error('Invalid Amazon account #' . $account_id . ' found for B2B deletion PATCH');
					continue;
				}

				$account = WPLA()->accounts[ $account_id ];
				WPLA()->logger->info('Creating B2B deletion PATCH feed for account #' . $account_id . ' with ' . count( $account_listings ) . ' listings');

				// Create the PATCH feed to remove B2B pricing
				$builder = new \WPLab\Amazon\Helper\JsonFeedDataBuilder();
				$builder->removeB2BPricing( $account_listings, $account, 'PRODUCT' );
			}

			WPLA()->logger->info('B2B price deletion PATCH feeds triggered successfully for profile #' . $profile_id . ' (' . count($listings) . ' listings affected)');

		} catch ( Exception $e ) {
			WPLA()->logger->error('Failed to trigger B2B price deletion PATCH feeds for profile #' . $profile_id . ': ' . $e->getMessage());
		}
	}	

}
