jQuery(document).ready(function ($) {
    $("#toggle-add-seller").on("click", function () {
        $("#add-seller-popup").fadeIn();
    });

    $(".close-popup").on("click", function () {
        $("#add-seller-popup").fadeOut();
    });

    $(document).on("click", function (event) {
        if ($(event.target).is("#add-seller-popup")) {
            $("#add-seller-popup").fadeOut();
        }
    });


    $('#submit-add-seller').click(function() {
        var sellerField = $('select[name="seller_selection"]');
        var brandField = $('select[name="brand_selection"]');
        var seller_id = sellerField.val();
        var brand_id = brandField.val();
        
        // Remove previous error messages
        $('.error-message').remove();

        var hasError = false;

        if (!seller_id) {
            sellerField.after('<span class="error-message" style="color:red;display:block;margin-top:5px;">Please select a seller.</span>');
            hasError = true;
        }

        if (!brand_id) {
            brandField.after('<span class="error-message" style="color:red;display:block;margin-top:5px;">Please select a brand.</span>');
            hasError = true;
        }

        if (hasError) return; // Stop execution if there's an error

        $.ajax({
            url: ajaxurl, 
            type: 'POST',
            data: {
                action: 'submit_seller_request',
                seller_id: seller_id,
                brand_id: brand_id,
                security: seller_request_nonce
            },
            success: function(response) {
                if (response.success) {
                    //alert(response.data);
                    location.reload();
                } else {
                    // Show server error message
                    $('#submit-add-seller').after('<span class="error-message" style="color:red;display:block;margin-top:5px;">' + response.data + '</span>');
                }
            },
            error: function() {
                $('#submit-add-seller').after('<span class="error-message" style="color:red;display:block;margin-top:5px;">Something went wrong.</span>');
            }
        });
    });


    $('.approve-seller, .reject-seller, .delete-seller').click(function() {
        var requestId = $(this).data('id');
        var actionType = $(this).hasClass('approve-seller') ? 'approve' :
                         $(this).hasClass('reject-seller') ? 'reject' : 'delete';

        $.ajax({
            url: ajaxurl, // WordPress AJAX URL
            type: 'POST',
            data: {
                action: 'manage_seller_request',
                request_id: requestId,
                request_action: actionType,
                security: seller_request_nonce // Make sure this nonce is localized in WordPress
            },
            success: function(response) {
                if (response.success) {
                    if (actionType === 'delete') {
                        $('button[data-id="' + requestId + '"]').closest('tr').remove();
                    } else {
                        $('.status-' + requestId).text(response.data.status);
                    }
                } else {
                    //alert(response.data);
                    location.reload();
                }
            },
            error: function() {
                //alert('Something went wrong.');
                location.reload();
            }
        });
    });
});
