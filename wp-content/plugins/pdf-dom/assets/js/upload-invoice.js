
jQuery(document).ready(function ($) {
    let fileInput = $('<input type="file" accept=".pdf,.doc,.docx,.jpeg,.jpg,.png" style="display:none;">');
    $('body').append(fileInput);

    $('.upload-invoice-btn').on('click', function () {
        const orderId = $(this).data('order-id'); // FIXED LINE ✅
        fileInput.data('order-id', orderId).click();
    });

    fileInput.on('change', function () {
        const file = this.files[0];
        const orderId = $(this).data('order-id');

        if (!file) return;

        const formData = new FormData();
        formData.append('action', 'upload_vat_invoice');
        formData.append('nonce', invoice_upload_obj.nonce);
        formData.append('order_id', orderId);
        formData.append('file', file);

        $.ajax({
            url: invoice_upload_obj.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success) {
                    //alert('✅ Invoice uploaded & sent to the customer successfully.');
                    location.reload();
                } else {
                    //alert('❌ Error: ' + response.data);
                }
            },
            error: function () {
                //alert('❌ Server error while uploading.');
            }
        });
    });
});
