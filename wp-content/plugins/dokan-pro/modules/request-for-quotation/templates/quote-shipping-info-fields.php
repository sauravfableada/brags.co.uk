<table class='form-table shipping-info-table announcement-meta-options dokan-address-fields'>
    <tbody>
    <tr>
        <td>
            <?php esc_html_e( 'Country', 'dokan' ); ?> <span class="required">*</span>
        </td>
        <td>
            <select name='country' class='wc-enhanced-select country_to_state'>
                <?php dokan_country_dropdown( $countries, $country ); ?>
            </select>
        </td>
    </tr>
    <tr>
        <td>
            <?php esc_html_e( 'State', 'dokan' ); ?> <span class="required">*</span>
        </td>
        <td>
            <?php

            $address_state_class = '';
            $is_input            = false;
            $no_states           = false;
            if ( isset( $states[ $country ] ) ) {
                if ( empty( $states[ $country ] ) ) {
                    $address_state_class = 'dokan-hide';
                    $no_states           = true;
                }
            } else {
                $is_input = true;
            }
            ?>
            <div id="dokan-states-box" class="dokan-form-group">
                <?php if ( $is_input ) : ?>
                    <input required type="text" name="state_address" id="dokan_address_state" placeholder='<?php esc_html_e( 'Alabama, Alaska etc', 'dokan' ); ?>' value="<?php echo esc_attr( $state ); ?>" class="<?php echo esc_attr( $address_state_class ); ?>" />
                <?php else : ?>
                    <select required id="dokan_address_state" name="state_address" class="wc-enhanced-select">
                        <?php dokan_state_dropdown( $states[ $country ], $state ); ?>
                    </select>
                <?php endif; ?>
            </div>
        </td>
    </tr>
    <tr>
        <td>
            <?php esc_html_e( 'City', 'dokan' ); ?>
        </td>
        <td>
            <input type='text' size='50' placeholder='<?php esc_html_e( 'Montgomery, Auburn etc', 'dokan' ); ?>' name='city' value='<?php echo esc_attr( $city ); ?>' />
        </td>
    </tr>
    <tr>
        <td>
            <?php esc_html_e( 'Postal Code', 'dokan' ); ?>
        </td>
        <td>
            <input type='text' size='50' placeholder='<?php esc_html_e( 'ex - 12345', 'dokan' ); ?>' name='post_code' value='<?php echo esc_attr( $post_code ); ?>' />
        </td>
    </tr>
    <tr>
        <td>
            <?php esc_html_e( 'Address Line 1', 'dokan' ); ?>
        </td>
        <td>
            <input type='text' size='50' placeholder='<?php esc_html_e( 'Road, Area etc.', 'dokan' ); ?>' name='addr_line_1' value='<?php echo esc_attr( $address_1 ); ?>' />
        </td>
    </tr>
    <tr>
        <td>
            <?php esc_html_e( 'Address Line 2', 'dokan' ); ?>
        </td>
        <td>
            <input type='text' size='50' placeholder='<?php esc_html_e( 'Apartment, Suite etc.', 'dokan' ); ?>' name='addr_line_2' value='<?php echo esc_attr( $address_2 ); ?>' />
        </td>
    </tr>
    <tr>
        <td>
            <?php esc_html_e( 'Expected Delivery', 'dokan' ); ?>
        </td>
        <td>
            <input type='text' size='50' class='quote-datepicker' placeholder='<?php esc_html_e( 'Select Date', 'dokan' ); ?>' name='expected_delivery_date' />
        </td>
    </tr>
    </tbody>
</table>

