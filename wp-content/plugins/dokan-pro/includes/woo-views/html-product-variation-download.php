<tr>
	<td class="file_name"><input type="text" class="input_text" placeholder="<?php esc_attr_e( 'File Name', 'dokan' ); ?>" name="_wc_variation_file_names[<?php echo absint( $variation_id ); ?>][]" value="<?php echo esc_attr( $file['name'] ); ?>" /></td>
	<td class="file_url"><input type="text" class="input_text wc_file_url" placeholder="http://" name="_wc_variation_file_urls[<?php echo absint( $variation_id ); ?>][]" value="<?php echo esc_attr( $file['file'] ); ?>" /></td>
	<td class="file_url_choose" width="1%"><a href="#" class="dokan-btn dokan-btn-sm dokan-btn-default upload_file_button" data-choose="<?php esc_attr_e( 'Choose file', 'dokan' ); ?>" data-update="<?php esc_attr_e( 'Insert file URL', 'dokan' ); ?>"><?php echo str_replace( ' ', '&nbsp;', __( 'Choose file', 'dokan' ) ); ?></a></td>
	<td width="1%"><a href="#" class="dokan-btn dokan-btn-sm dokan-btn-danger dokan-product-delete"><?php esc_html_e( 'Delete', 'dokan' ); ?></a></td>
</tr>
