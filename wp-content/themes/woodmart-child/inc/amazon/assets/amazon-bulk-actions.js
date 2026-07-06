/**
 * Amazon Bulk Actions - JavaScript
 * Path: inc/amazon/assets/amazon-bulk-actions.js
 * 
 * Handles bulk ASIN assignment and other bulk actions in Dokan product list
 */

(function ($) {
    'use strict';

    $(document).ready(function () {
        const $bulkActionSelector = $('#bulk-product-action-selector');
        const $bulkActionSubmit = $('#bulk-product-action');
        const $productFilterForm = $('#product-filter');

        if ($bulkActionSelector.length === 0 || $bulkActionSubmit.length === 0) return;

        // Hijack the submit button for our custom action
        $bulkActionSubmit.on('click', function (e) {
            const selectedAction = $bulkActionSelector.val();

            if (selectedAction === 'assign_amazon_asin') {
                e.preventDefault();
                e.stopImmediatePropagation();

                const productIds = [];
                $('.cb-select-items:checked').each(function () {
                    productIds.push($(this).val());
                });

                if (productIds.length === 0) {
                    alert('Please select at least one product.');
                    return;
                }

                const asin = prompt(wpla_bulk_vars.confirm_text);

                if (asin === null) return; // Cancelled

                if (asin.trim() === '') {
                    alert('ASIN cannot be empty.');
                    return;
                }

                // Validate ASIN format
                const asinRegex = /^B[A-Z0-9]{9}$/;
                if (!asinRegex.test(asin.toUpperCase())) {
                    alert('Invalid ASIN format. It should be 10 characters starting with B.');
                    return;
                }

                // Send AJAX request
                const $btn = $(this);
                const originalVal = $btn.val();
                $btn.val('Processing...').prop('disabled', true);

                $.ajax({
                    url: wpla_bulk_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpla_bulk_assign_asin',
                        product_ids: productIds,
                        asin: asin.toUpperCase(),
                        security: wpla_bulk_vars.nonce
                    },
                    success: function (response) {
                        if (response.success) {
                            alert(response.data.message);
                            location.reload();
                        } else {
                            alert(response.data.message);
                            $btn.val(originalVal).prop('disabled', false);
                        }
                    },
                    error: function () {
                        alert('An error occurred during bulk assignment.');
                        $btn.val(originalVal).prop('disabled', false);
                    }
                });
            }
        });
    });

})(jQuery);
