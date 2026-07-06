<?php
/**
 * User Registration Form
 *
 * Shows user registration form
 *
 * This template can be overridden by copying it to yourtheme/user-registration/form-registration.php.
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

/**
 * Template for Registration Form.
 *
 * @var $form_data_array array
 * @var $form_id         int
 * @var $is_field_exists boolean
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$frontend       = UR_Frontend::instance();
$form_template  = ur_get_form_setting_by_key( $form_id, 'user_registration_form_template', 'Default' );
$custom_class   = ur_get_form_setting_by_key( $form_id, 'user_registration_form_custom_class', '' );
$redirect_url   = ur_get_form_redirect_url( $form_id );
$template_class = '';

if ( 'Bordered' === $form_template ) {
	$template_class = 'ur-frontend-form--bordered';

} elseif ( 'Flat' === $form_template ) {
	$template_class = 'ur-frontend-form--flat';

} elseif ( 'Rounded' === $form_template ) {
	$template_class = 'ur-frontend-form--rounded';

} elseif ( 'Rounded Edge' === $form_template ) {
	$template_class = 'ur-frontend-form--rounded ur-frontend-form--rounded-edge';
}

$custom_class =
/**
 * Filter to modify the user registration form custom class.
 *
 * @param string $custom class Custom class for user registration form.
 * @param int $form_id Id of the class to add the custom class.
 *
 * @return string custom class for user registration form.
 */
apply_filters( 'user_registration_form_custom_class', $custom_class, $form_id );

require_once UR()->plugin_path() . '/includes/functions-ur-notice.php';

$notices =
/**
 * Filter to modify the user registration form notice before the rendering of form.
 *
 * @param function Function to modify the notices.
 *
 * @return function.
 */
apply_filters( 'user_registration_before_registration_form_notice', ur_print_notices() );
echo esc_html( $notices );
/**
 * Hook for Before registration form
 *
 * @since 1.5.1
 */
do_action( 'user_registration_before_registration_form', $form_id );
$is_theme_style = get_post_meta( $form_id, 'user_registration_enable_theme_style', true );
if ( 'default' === $is_theme_style ) {
	$default_class = 'ur-frontend-form--default';
	wp_register_style( 'ur-frontend-default-css', UR()->plugin_url() . '/assets/css/user-registration-default-frontend.css', array(), UR()->version );
	wp_enqueue_style( 'ur-frontend-default-css' );
} else {
	$default_class = '';
}
// For small screen
wp_register_style( 'ur-frontend-small-screen', UR()->plugin_url() . '/assets/css/user-registration-smallscreen.css', array(), UR()->version );
wp_enqueue_style( 'ur-frontend-small-screen' );
?>
	<div class='user-registration ur-frontend-form  <?php echo esc_attr( $template_class ) . ' ' . esc_attr( $custom_class ) . '' . esc_attr( $default_class ); ?>' id='user-registration-form-<?php echo absint( $form_id ); ?>'>
		<?php
		$form_status = get_post_status( $form_id );

		$form_data = UR()->form->get_form( $form_id );

		if ( empty( $form_data ) ) {
			?>
			<div class="user-registration-info">
				<?php
				printf(
					/* translators: %s: Form Status. */
					esc_html__( 'Form not Found. Please contact your site administrator.', 'user-registration' ),
					esc_html( ucfirst( $form_status ) )
				)
				?>
			</div>
			<?php
		} elseif ( 'publish' !== get_post_status( $form_id ) ) {
			?>
			<div class="user-registration-info">
				<?php
				printf(
					/* translators: %s: Form Status. */
					esc_html__( 'The form is in %s. Please contact your site administrator.', 'user-registration' ),
					esc_html( ucfirst( $form_status ) )
				)
				?>
			</div>
			<?php
		} else {
			?>
			<?php
			$is_title_description_enabled = ur_string_to_bool( ur_get_single_post_meta( $form_id, 'user_registration_enable_form_title_description', false ) );
			if ( $is_title_description_enabled ) {
				$registration_title_label       = ur_get_single_post_meta( $form_id, 'user_registration_form_title' );
				$registration_title_description = ur_get_single_post_meta( $form_id, 'user_registration_form_description' );
				/* translators: %s - registration Title. */
				echo wp_kses_post( sprintf( __( '<span class="user-registration-registration-title"> %s </span> </br>', 'user-registration' ), $registration_title_label ) );
				echo wp_kses_post( sprintf( __( '<p class="user-registration-registration-description"> %s </p>', 'user-registration' ), $registration_title_description ) );
			}
			?>
            

         




			<form enctype="multipart/form-data" method='post' class='register' id="ur-multi-step-form-fa" data-form-id="<?php echo absint( $form_id ); ?>"
				data-enable-strength-password="<?php echo esc_attr( $enable_strong_password ); ?>" data-minimum-password-strength="<?php echo esc_attr( $minimum_password_strength ); ?>"
															<?php
															echo /**
				 * Filter to modify the user registration form paramaters.
				 *
				 * @param string paramater for user registration form.
				 * @return string.
				 */
				apply_filters( 'user_registration_form_params', '' );  //phpcs:ignore ?> data-captcha-enabled="<?php echo esc_attr( $recaptcha_enabled ); ?>">

				<?php
				/**
				 * Action to fire before rendering form field.
				 *
				 * @param array $form_data_array Form data.
				 * @param int $form_id Form ID.
				 */
				do_action( 'user_registration_before_form_fields', $form_data_array, $form_id );

				// foreach loop removed from here 
				
                
				/**
				 * Action to fire after rendering of the form fields.
				 *
				 * @param array $form_data_aaray Array of form data.
				 * @param int $form_id Form ID.
				 */
				do_action( 'user_registration_after_form_fields', $form_data_array, $form_id );

				if ( $is_field_exists ) {
					?>
						<?php
						if ( ! empty( $recaptcha_node ) ) {
							echo '<div id="ur-recaptcha-node"> ' . $recaptcha_node . '</div>'; //phpcs:ignore
						}

						$btn_container_class = apply_filters( 'user_registration_form_btn_container_class', array(), $form_id );
						?>
						<div class="ur-button-container <?php echo esc_attr( implode( ' ', $btn_container_class ) ); ?>" >
							<?php
							do_action( 'user_registration_before_form_buttons', $form_id );

							$submit_btn_class =
							/**
							 * Filter to modify the class of form submit button.
							 *
							 * @param array Array of classes for submit button.
							 * @param int $form_id Form ID.
							 *
							 * @return array Form submit button class.
							 */
							apply_filters( 'user_registration_form_submit_btn_class', array(), $form_id );
							$condition_submit_settings = ur_maybe_unserialize( get_post_meta( $form_id, 'user_registration_submit_condition', true ) );

							$submit_btn_class = array_merge( $submit_btn_class, (array) ur_get_form_setting_by_key( $form_id, 'user_registration_form_setting_form_submit_class' ) );
							?>
							<button type="submit" class="btn button ur-submit-button <?php echo esc_attr( implode( ' ', $submit_btn_class ) ); ?>"  conditional_rules="<?php echo ur_string_to_bool( ur_get_single_post_meta( $form_id, 'user_registration_form_setting_enable_submit_conditional_logic', true ) ) ? esc_attr( wp_json_encode( $condition_submit_settings ) ) : ''; ?>">
								<span></span>
								<?php
								$submit = ur_get_form_setting_by_key( $form_id, 'user_registration_form_setting_form_submit_label' );
									echo esc_html( ur_string_translation( $form_id, 'user_registration_form_setting_form_submit_label', $submit ) );
								?>
							</button>
							<?php
							/**
							 * Action to fire after rendering of form buttons.
							 *
							 * @param int $form_id Form ID.
							 */
							do_action( 'user_registration_after_form_buttons', $form_id );
							?>
							<?php
							/**
							 * Action to fire after the submit buttons.
							 *
							 * @param int $form_id Form ID.
							 */
							do_action( 'user_registration_after_submit_buttons', $form_id );
							?>
						</div>
						<?php
				}

				if ( count( $form_data_array ) == 0 ) {
					?>
							<h2><?php echo esc_html__( 'Form not found, form id :' . $form_id, 'user-registration' ); //phpcs:ignore ?></h2>
						<?php
				}
				$enable_field_icon = ur_string_to_bool( ur_get_single_post_meta( $form_id, 'user_registration_enable_field_icon' ) );
				?>

				<div style="clear:both"></div>
				<?php if ( $enable_field_icon ) { ?>
				<input type="hidden" id="ur-form-field-icon" name="ur-field-icon" value="<?php echo esc_attr( $enable_field_icon ); ?>"/>
					<?php
				}
				$current_language = ur_get_current_language();
				?>
				<input type="hidden" name="ur-registration-language" value="<?php echo esc_attr( $current_language ); ?>"/>
				<input type="hidden" name="ur-user-form-id" value="<?php echo absint( $form_id ); ?>"/>
				<input type="hidden" name="ur-redirect-url" value="<?php echo esc_url( ur_string_translation( $form_id, 'user_registration_form_setting_redirect_options', $redirect_url ) ); ?>"/>
				<?php wp_nonce_field( 'ur_frontend_form_id-' . $form_id, 'ur_frontend_form_nonce', false ); ?>

				<?php
				/**
				 * Action to fire at the end of rendering the regsitration form.
				 *
				 * @param int $form_id Form ID.
				 */
				do_action( 'user_registration_form_registration_end', $form_id );
				?>
			</form>
			<?php
		}
		?>

		<div style="clear:both"></div>
	</div>
<?php

/**
 * User registration form template.
 *
 * @since 1.0.0
 */
do_action( 'user_registration_form_registration', $form_id );

/* Omit closing PHP tag at the end of PHP files to avoid "headers already sent" issues. */
