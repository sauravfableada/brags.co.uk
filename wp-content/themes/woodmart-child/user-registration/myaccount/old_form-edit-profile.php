<?php
/**
 * Edit account form
 *
 * This template can be overridden by copying it to yourtheme/user-registration/myaccount/form-edit-profile.php.
 *
 * HOWEVER, on occasion UserRegistration will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.wpuserregistration.com/docs/how-to-edit-user-registration-template-files-such-as-login-form/
 * @package UserRegistration/Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = get_current_user_id();
$form_id = ur_get_form_id_by_userid( $user_id );


    $user = get_userdata($user_id);
    $user_roles = $user->roles;

/**
 * Deprecated in version 3.1.3. Use 'user_registration_before_edit_profile_form_data' instead.
 *
 * @deprecated 3.1.3 Use 'user_registration_before_edit_profile_form_data' instead.
 *
 * @param array array value.
 * @param string deprecated_version.
 * @param string hook_name to be used instead.
 */
do_action_deprecated( 'user_registration_before_edit_profile_form', array(), '3.1.3', 'user_registration_before_edit_profile_form_data' );

/**
 * Fires before rendering the edit profile form with additional data.
 *
 * @param int $user_id User id of the current profile being edited.
 * @param int $form_id Form id through which user registered.
 */
do_action( 'user_registration_before_edit_profile_form_data', $user_id, $form_id );

$layout = get_option( 'user_registration_my_account_layout', 'horizontal' );

if ( 'vertical' === $layout ) {
	?>
	<div class="user-registration-MyAccount-content__header">
		<h1><?php echo wp_kses_post( $endpoint_label ); ?></h1>
	</div>
	<?php
}
?>
<div class="user-registration-MyAccount-content__body">
	<div class="ur-frontend-form login ur-edit-profile" id="ur-frontend-form">
		<form id="user-profile-form" class="user-registration-EditProfileForm edit-profile" action="" method="post" enctype="multipart/form-data" data-form-id="<?php echo esc_attr( $form_id ); ?>">
			<div class="ur-form-row">
				<div class="ur-form-grid">
					<div class="user-registration-profile-fields">
						<?php
						/**
						 * Fires before rendering of profile detail title.
						 */
						do_action( 'user_registration_before_profile_detail_title' );
						?>
						<h2>
						<?php
						esc_html_e(
							/**
							 * Filter to modify the profile detail title.
							 *
							 * @param string Profile detail title content.
							 * @return string modified profile detail title.
							 */
							apply_filters( 'user_registation_profile_detail_title', __( 'Profile Detail', 'user-registration' ) ) ); //PHPCS:ignore ?></h2>
						<?php

						$is_sync_profile           = ur_option_checked( 'user_registration_sync_profile_picture', false );
						$is_profile_field_disabled = ur_option_checked( 'user_registration_disable_profile_picture', false );
						$is_profile_pic_on_form    = false;
						if ( $is_sync_profile ) {
							foreach ( $form_data_array as $data ) {
								foreach ( $data as $grid_key => $grid_data ) {
									foreach ( $grid_data as $grid_data_key => $single_item ) {
										if ( isset( $single_item->field_key ) && 'profile_picture' === $single_item->field_key ) {
											$is_profile_pic_on_form = true;
										}
									}
								}
							}
						} else {
							$is_profile_pic_on_form = ! $is_profile_field_disabled;
						}
						if ( $is_profile_pic_on_form ) {
							?>
						<!-- <div class="user-registration-profile-header"> -->
						<div class="">
							<div class="user-registration-img-container" style="width:100%">
								<?php
								$gravatar_image      = get_avatar_url( get_current_user_id(), $args = null );
								$profile_picture_url = get_user_meta( get_current_user_id(), 'user_registration_profile_pic_url', true );

								if ( is_numeric( $profile_picture_url ) ) {
									$profile_picture_url = wp_get_attachment_url( $profile_picture_url );
								}

								$profile_picture_url          = apply_filters( 'user_registration_profile_picture_url', $profile_picture_url, $user_id );
								$image                        = ( ! empty( $profile_picture_url ) ) ? $profile_picture_url : $gravatar_image;
								$max_size                     = wp_max_upload_size();
								$max_upload_size              = $max_size;
								$crop_picture                 = false;
								$profile_pic_args             = array();
								$edit_profile_valid_file_type = 'image/jpeg,image/gif,image/png';

								foreach ( $form_data_array as $data ) {
									foreach ( $data as $grid_key => $grid_data ) {
										foreach ( $grid_data as $grid_data_key => $single_item ) {

											if ( isset( $single_item->field_key ) && 'profile_picture' === $single_item->field_key ) {
												$profile_pic_args             = (array) $single_item->advance_setting;
												$edit_profile_valid_file_type = isset( $single_item->advance_setting->valid_file_type ) && '' !== $single_item->advance_setting->valid_file_type ? implode( ', ', $single_item->advance_setting->valid_file_type ) : $edit_profile_valid_file_type;
												$max_upload_size              = isset( $single_item->advance_setting->max_upload_size ) && '' !== $single_item->advance_setting->max_upload_size ? $single_item->advance_setting->max_upload_size : $max_size;
												$crop_picture                 = isset( $single_item->advance_setting->enable_crop_picture ) ? ur_string_to_bool( $single_item->advance_setting->enable_crop_picture ) : false;
											}
										}
									}
								}

								?>
									
								</div>
							<?php } ?>
						<?php
						/**
						 * Fires at the start of rendering user registration edit profile form.
						 */
						do_action( 'user_registration_edit_profile_form_start' );
						?>
						<div class="user-registration-profile-fields__field-wrapper">

							<?php
							$current_user = wp_get_current_user();
							$user_id = $current_user->ID;

							// Retrieve Product IDs
							$product_ids = [];
							for ($i = 1; $i <= 5; $i++) {
								$product_ids[$i] = get_user_meta($user_id, "product_id_$i", true);
							}
							// Retrieve Product Images
							$product_images = [];
							for ($i = 1; $i <= 5; $i++) {
								$product_images[$i] = get_user_meta($user_id, "product_image_$i", true);
							}

							// Retrieve text-based meta fields
							$meta_fields = [
								'brand_name', 'trademark_office', 'trademark_number', 'brand_description',
								'business_name', 'business_address', 'phone_number', 'primary_contact', 'business_email',
								'website_url', 'manufacturing_locations', 'distribution_channels',
								'product_categories', 'sell_on_brags', 'approve_resellers',
								'brags_seller_email', 'brags_store_url', 'country'
							];

							$user_meta = [];
							foreach ($meta_fields as $field) {
								$user_meta[$field] = get_user_meta($user_id, $field, true);
							}

							// Retrieve Brand Logo
							$brand_logo = get_user_meta($user_id, 'brand_logo', true);

							// Retrieve Product Images (stored separately)
							$product_images = [];
							for ($i = 1; $i <= 5; $i++) {
								$img = get_user_meta($user_id, "product_images_$i", true);
								if (!empty($img)) {
									$product_images[$i] = $img;
								}
							}
							// Retrieve Additional Documents (stored separately)
							$additional_documents = [];
							for ($i = 1; $i <= 5; $i++) {
								$doc = get_user_meta($user_id, "additional_documents_$i", true);
								if (!empty($doc)) {
									$additional_documents[] = $doc;
								}
							}

							// Retrieve Product IDs
							$product_ids = [];
							for ($i = 1; $i <= 5; $i++) {
								$id = get_user_meta($user_id, "product_ids_$i", true);
								if (!empty($id)) {
									$product_ids[] = $id;
								}
							}
							?>

							<!-- FORM FIELDS -->
							<label>Brand Name:</label>
							<input type="text" name="brand_name" value="<?= esc_attr($user_meta['brand_name']); ?>" required>

							<label>Trademark Office:</label>
							<input type="text" name="trademark_office" value="<?= esc_attr($user_meta['trademark_office']); ?>">

							<label>Trademark Number:</label>
							<input type="text" name="trademark_number" value="<?= esc_attr($user_meta['trademark_number']); ?>">

							<label>Brand Description:</label>
							<textarea name="brand_description"><?= esc_textarea($user_meta['brand_description']); ?></textarea>

							<label>Business Name:</label>
							<input type="text" name="business_name" value="<?= esc_attr($user_meta['business_name']); ?>" required>

							<label>Business Address:</label>
							<textarea name="business_address"><?= esc_textarea($user_meta['business_address']); ?></textarea>

							<label>Phone Number:</label>
							<div style="display: flex;">
								<input type="text" id="country" name="country" value="<?= esc_attr($user_meta['country']); ?>" required>
								<input type="text" id="phone" name="phone_number" value="<?= esc_attr($user_meta['phone_number']); ?>">
							</div>

							<label>Primary Contact:</label>
							<input type="text" name="primary_contact" value="<?= esc_attr($user_meta['primary_contact']); ?>">

							<label>Business Email:</label>
							<input type="email" name="business_email" value="<?= esc_attr($current_user->user_email); ?>" readonly>

							<label>Website URL:</label>
							<input type="url" name="website_url" value="<?= esc_url($user_meta['website_url']); ?>">

							<label>Manufacturing Locations:</label>
							<textarea name="manufacturing_locations"><?= esc_textarea($user_meta['manufacturing_locations']); ?></textarea>

							<label>Distribution Channels:</label>
							<textarea name="distribution_channels"><?= esc_textarea($user_meta['distribution_channels']); ?></textarea>

							<label>Product Categories:</label>
							<input type="text" name="product_categories" value="<?= esc_attr($user_meta['product_categories']); ?>">

							<!-- PRODUCT IDs (Stored Separately) -->
							<!-- <label>Product IDs:</label>
							<div>
								<?php //foreach ($product_ids as $index => $product_id): ?>
									<input type="text" name="product_ids_<?= $index + 1 ?>" value="<?= esc_attr($product_id); ?>">
								<?php //endforeach; ?>
							</div> -->

							<label>Sell on Brags:</label>
							<input type="checkbox" name="sell_on_brags" value="1" <?= $user_meta['sell_on_brags'] ? 'checked' : ''; ?>>

							<label>Approve Resellers:</label>
							<input type="checkbox" name="approve_resellers" value="1" <?= $user_meta['approve_resellers'] ? 'checked' : ''; ?>>

							<label>Brags Seller Email:</label>
							<input type="email" name="brags_seller_email" value="<?= esc_attr($user_meta['brags_seller_email']); ?>">

							<label>Brags Store URL:</label>
							<input type="url" name="brags_store_url" value="<?= esc_url($user_meta['brags_store_url']); ?>">

							<!-- BRAND LOGO -->
							<label>Brand Logo:</label>
							<?php if (!empty($brand_logo)): ?>
								<img src="<?= esc_url($brand_logo); ?>" width="100" height="100">
							<?php endif; ?>
							<input type="file" name="brand_logo">

							<!-- <h3>Edit Product IDs & Images</h3> -->

							<!-- Loop for Editing Product IDs & Images -->
							<?php for ($i = 1; $i <= 5; $i++): ?>
								<label>Product ID <?= $i ?>:</label>
								<input type="text" name="product_ids_<?= $i ?>" value="<?= esc_attr(get_user_meta($user_id, "product_id_$i", true)); ?>">

								<label>Product Image <?= $i ?>:</label>
								<?php 
									$image_url = get_user_meta($user_id, "product_image_$i", true);
									if (!empty($image_url)): 
								?>
									<img src="<?= esc_url($image_url); ?>" width="100" height="100">
								<?php endif; ?>
								<input type="file" name="product_images_<?= $i ?>">
							<?php endfor; ?>

							<br>

							<!-- Additional Documents (Support Multiple Files) -->
							<label>Additional Documents:</label>
                        <?php 
                            if (!empty($additional_documents) && is_array($additional_documents)) {
                                foreach ($additional_documents as $doc) {
                                    echo '<a href="' . esc_url($doc) . '" target="_blank">View Document</a><br>';
                                }
                            } 
                        ?>
                        <input type="file" name="additional_documents[]" multiple>
                                <br>
                            
							<button type="button" id="update_profile" name="update_profile">Update Profile</button>

							<div id="update-message"></div>

							</div>

						<?php
						do_action( 'user_registration_edit_profile_form' );
						$submit_btn_class =
						/**
						 * Filter to modify the form update button class.
						 *
						 * @param array array value.
						 * @return array form update button classes.
						 */
						apply_filters( 'user_registration_form_update_btn_class', array() );
						?>
						<p>
							<?php
							if ( ur_option_checked( 'user_registration_ajax_form_submission_on_edit_profile', false ) ) {
								?>
								<button type="submit" class="user-registration-submit-Button btn button <?php echo esc_attr( implode( ' ', $submit_btn_class ) ); ?>" name="save_account_details" ><span></span>
									<?php
									esc_html_e(
									/**
									 * Filter to modify the profile update button text.
									 *
									 * @param string Text content to be modified.
									 * @return string button text.
									 */
									apply_filters( 'user_registration_profile_update_button', __( 'Save changes', 'user-registration' ) ) ); //PHPCS:ignore?></button>
								<?php
							} else {
								wp_nonce_field( 'save_profile_details' );
								?>
								<!-- <input type="submit" class="user-registration-Button button <?php //echo esc_attr( implode( ' ', $submit_btn_class ) ); ?>" name="save_account_details" value="
								<?php
								// esc_attr_e(
								/**
								 * Filter to modify the profile update button text.
								 *
								 * @param string text content for button.
								 * @return string button text.
								 */
									//apply_filters( 'user_registration_profile_update_button', __( 'Save changes', 'user-registration' ) ) );//PHPCS:ignore ?>"
								/> -->
								<?php
								echo apply_filters( 'user_registration_edit_profile_extra_data_div', '', $form_id ); // phpcs:ignore.
								?>
								<input type="hidden" name="action" value="save_profile_details" />
								<?php
							}
							?>
						</p>
					</div>
				</div>

			</div>
		</form>
	</div>

	<?php
	/**
	 * Fires after rendering the user registration edit profile form.
	 */
	do_action( 'user_registration_after_edit_profile_form' );
	?>
</div>

<script>

jQuery(document).ready(function ($) {
    // Initialize country dropdown
    $("#country").countrySelect({
      preferredCountries: ["us", "gb", "in", "de", "au"],
    });

    // Form validation
    $("#phone-form").on("submit", function (e) {
      e.preventDefault();

      let countryData = $("#country").countrySelect("getSelectedCountryData");
      let countryCode = countryData.iso2.toUpperCase();
      let phoneNumber = $("#phone").val();

      let parsedNumber = libphonenumber.parsePhoneNumber(phoneNumber, countryCode);
      
      if (!parsedNumber || !parsedNumber.isValid()) {
        $("#phone-error").text("Invalid phone number").show();
      } else {
        $("#phone-error").hide();
        alert("Phone number is valid!");
      }
    });
  });
jQuery(document).ready(function($) {

	// Function to display error messages
	function showError(element, message) {
		let errorSpan = document.createElement('span');
		errorSpan.classList.add('bf_error', 'error');
		errorSpan.innerText = message;
		element.after(errorSpan);
		if (isValid) element.focus(); // Focus on the first invalid field
	}

	// Function to validate email format
	function validateEmail(email) {
		let emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
		return emailRegex.test(email);
	}

	// Function to validate phone number (Only numbers, 10-15 digits)
	function validatePhoneNumber(phone) {
		let phoneRegex = /^[0-9]{10,15}$/;
		return phoneRegex.test(phone);
	}

    $('#update_profile').on('click', function(e) {
        e.preventDefault();

		// Clear previous error messages
		document.querySelectorAll('.bf_error').forEach(error => error.remove());
		 // Define required fields
		 const requiredFields = [
			{ name: 'brand_name', message: 'Brand Name is required' },
			{ name: 'trademark_office', message: 'Trademark Office is required' },
			{ name: 'trademark_number', message: 'Trademark Number is required' },
			{ name: 'brand_description', message: 'Brand Description is required' },
			{ name: 'business_name', message: 'Business Name is required' },
			{ name: 'business_address', message: 'Business Address is required' },
			{ name: 'phone_number', message: 'Phone Number is required', type: 'phone' },
			{ name: 'primary_contact', message: 'Primary Contact is required' },
			{ name: 'business_email', message: 'Business Email is required', type: 'email' },
			{ name: 'manufacturing_locations', message: 'Manufacturing Locations is required' },
			{ name: 'distribution_channels', message: 'Distribution Channels is required' },
			{ name: 'product_categories', message: 'Product Category is required' },
			// { name: 'product_ids', message: 'Product ID is required' }
		];

		let isValid = true;

		requiredFields.forEach(field => {
			let inputElement = document.querySelector(`input[name="${field.name}"], textarea[name="${field.name}"]`);

			if (inputElement) {
				let value = inputElement.value.trim();

				// Remove any existing error message for the field
				if (inputElement.nextElementSibling && inputElement.nextElementSibling.classList.contains('bf_error')) {
					inputElement.nextElementSibling.remove();
				}

				// Required field validation
				if (value === '') {
					showError(inputElement, field.message);
					isValid = false;
				} 
				// Email validation
				else if (field.type === 'email' && !validateEmail(value)) {
					showError(inputElement, 'Enter a valid email address');
					isValid = false;
				} 
				// Phone number validation (Only numbers, 10-15 digits)
				else if (field.type === 'phone' && !validatePhoneNumber(value)) {
					showError(inputElement, 'Enter a valid phone number (10-15 digits)');
					isValid = false;
				}
			}
		});

		if (!isValid) return false;
		
			
			let formData = new FormData($('#user-profile-form')[0]);
			formData.append('action','update_user_profile');

			$.ajax({
				url: '<?php echo admin_url('admin-ajax.php'); ?>',
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				beforeSend: function() {
					$('#update-message').html('<p>Updating...</p>');
				},
				success: function(response) {
					if (response.success) {
						$('#update-message').html('<p style="color:green;">' + response.data.message + '</p>');
						setTimeout(function() {
						    location.reload();
						}, 2000);
					} else {
						brandName = document.querySelector('input[name="brand_name"]');
						
						if(response.data.error.brand_name){
								brandName.style.border = '2px solid red';

								if (brandName.nextElementSibling && brandName.nextElementSibling.classList.contains('bf_error')) {
									brandName.nextElementSibling.remove();
								}
								let errorSpan = document.createElement('span');
								errorSpan.classList.add('bf_error', 'error');
								errorSpan.innerText = response.data.error.brand_name;
								brandName.after(errorSpan);
								brandName.focus();
							}else{
								brandName.style.border = '';
								if (brandName.nextElementSibling && brandName.nextElementSibling.classList.contains('bf_error')) {
									brandName.nextElementSibling.remove();
								}
						}
						if(response.data.message){
							$('#update-message').html('<p style="color:red;">' + response.data.message + '</p>');
						}
						
					}
				},
				error: function() {
					$('#update-message').html('<p style="color:red;">An error occurred. Please try again.</p>');
				}
			});

		
    });
});
</script>