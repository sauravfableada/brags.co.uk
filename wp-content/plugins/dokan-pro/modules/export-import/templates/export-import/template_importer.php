<?php
$permalink = get_permalink();
$parser = new Dokan_WXR_Parser();

?>

<?php do_action( 'dokan_dashboard_wrap_start' ); ?>

<div class="dokan-dashboard-wrap">
    <?php dokan_get_template( 'dashboard-nav.php', array( 'active_menu' => 'tools' ) ); ?>

	<div class="dokan-dashboard-content dokan-withdraw-content">
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

			<header class="dokan-dashboard-header">
			    <h1 class="entry-title"><?php esc_html_e( 'Tools', 'dokan' ); ?></h1>
			</header><!-- .entry-header -->

			<div id="tab-container">
				<ul class="dokan_tabs">
				  	<li class="active"><a href="#import" data-toggle="tab"><?php esc_html_e( 'Import', 'dokan' ); ?></a></li>
				  	<li><a href="#export" data-toggle="tab"><?php esc_html_e( 'Export', 'dokan' ); ?></a></li>
				</ul>

				<!-- Tab panes -->
				<div class="tabs_container">
				  	<div class="import_div tab-pane active" id="import">
					  	<header class="entry-header dokan-import-export-header">
					    	<h1 class="entry-title"><?php _e( 'Import', 'dokan' ); ?></h1>
					    </header>

 					<?php

 					if ( isset( $_POST['import_xml'] ) ) {
                        // Verify nonce for CSRF protection
                        if ( ! isset( $_POST['dokan_import_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dokan_import_nonce'] ) ), 'dokan-import-products' ) ) {
                            echo '<div class="dokan-alert dokan-alert-danger">' . esc_html__( 'Security verification failed. Please try again.', 'dokan' ) . '</div>';
                        } elseif ( ! current_user_can( 'dokan_import_product' ) && ! current_user_can( 'manage_options' ) ) {
                            // Check user capability
                            echo '<div class="dokan-alert dokan-alert-danger">' . esc_html__( 'You do not have permission to import products.', 'dokan' ) . '</div>';
                        } elseif ( empty( $_FILES['import'] ) ) {
                            echo '<div class="dokan-alert dokan-alert-danger">' . esc_html__( 'Please select a xml file', 'dokan' ) . '</div>';
                        } else {
 							// ✅ SECURE: Validate file type and size
 							$file = $_FILES['import']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

 							// Check file type
 							$allowed_types = array( 'text/xml', 'application/xml', 'application/rss+xml' );
 							if ( ! in_array( $file['type'], $allowed_types, true ) ) {
 								echo '<div class="dokan-alert dokan-alert-danger">' . esc_html__( 'Invalid file type. Please upload an XML file.', 'dokan' ) . '</div>';
 							} elseif ( $file['size'] > 50 * 1024 * 1024 ) {
                                // Check file size (50MB limit)
                                echo '<div class="dokan-alert dokan-alert-danger">' . esc_html__( 'File too large. Maximum size is 50MB.', 'dokan' ) . '</div>';
 							} elseif ( $file['error'] !== UPLOAD_ERR_OK ) {
                                // Check for file upload errors
                                echo '<div class="dokan-alert dokan-alert-danger">' . esc_html__( 'File upload error occurred.', 'dokan' ) . '</div>';
 							} else {
                                // All validations passed, proceed with import
                                Dokan_Product_Importer::init()->import( $file['tmp_name'] );
 							}
 						}
 					}

 					?>
					    <p><?php esc_html_e( 'Click Browse button and choose a XML file that you want to import.', 'dokan' ); ?></p>
					    <form method='post' enctype='multipart/form-data' action="">
                            <?php wp_nonce_field( 'dokan-import-products', 'dokan_import_nonce' ); ?>
				        	<p><input type='file' name='import' /></p>
				        	<p><input type='submit' name='import_xml' value='<?php esc_html_e( 'Import', 'dokan' ); ?>' class="btn btn-danger" /></p>

					    </form>
				  	</div>
					<div class="export_div tab-pane" id="export">
						<header class="entry-header dokan-import-export-header">
							<h1 class="entry-title"><?php esc_html_e( 'Export', 'dokan' ); ?></h1>
						</header>


						<p><?php esc_html_e( 'Chose your type of product and click export button to export all data in XML form', 'dokan' ); ?></p>

						<form action="" method="POST">
                            <?php wp_nonce_field( 'dokan-export-products', 'dokan_export_nonce' ); ?>
							<p><input type="radio" name="content" value="all" id="export_all" checked="checked"> <label for="export_all"><?php esc_html_e( 'All', 'dokan' ); ?></label></p>
							<p><input type="radio" name="content" value="product" id="export_product"> <label for="export_product"><?php esc_html_e( 'Product', 'dokan' ); ?></label></p>
							<p><input type="radio" name="content" value="product_variation" id="export_variation_product"> <label for="export_variation_product"><?php esc_html_e( 'Variation', 'dokan' ); ?></label></p>
							<p><input type="submit" name="export_xml" value="<?php esc_attr_e( 'Export', 'dokan' ); ?>" class="btn btn-danger"></p>
						</form>

					</div>
				</div>
			</div>


		</article>
    </div><!-- .dokan-dashboard-content -->
</div><!-- .dokan-dashboard-wrap -->

<?php do_action( 'dokan_dashboard_wrap_end' ); ?>

<script>
    (function($){
        $(document).ready(function(){
            $('#tab-container').easytabs();
        });
    })(jQuery)
</script>
