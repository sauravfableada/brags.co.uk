<table class='form-table quote-info-table announcement-meta-options'>
    <tbody>
    <tr>
        <td>
            <?php esc_html_e( 'Full Name', 'dokan' ); ?> <span class="required">*</span>
        </td>
        <td>
            <input type='text' size='50' placeholder='<?php esc_html_e( 'Full Name', 'dokan' ); ?>' name='name_field' required='required' value='<?php echo esc_attr( $full_name ); ?>' />
        </td>
    </tr>
    <tr>
        <td>
            <?php esc_html_e( 'Email', 'dokan' ); ?> <span class="required">*</span>
        </td>
        <td>
            <input type='email' size='50' placeholder='<?php esc_html_e( 'Email', 'dokan' ); ?>' name='email_field' required='required' value='<?php echo esc_attr( $user_email ); ?>' />
        </td>
    </tr>
    <tr>
        <td>
            <?php esc_html_e( 'Phone Number', 'dokan' ); ?> <span class="required">*</span>
        </td>
        <td>
            <input type='text' size='50' placeholder='<?php esc_html_e( 'Phone Number', 'dokan' ); ?>' required='required' name='phone_field' value='<?php echo esc_attr( $phone_number ); ?>' />
        </td>
    </tr>
    </tbody>
</table>
