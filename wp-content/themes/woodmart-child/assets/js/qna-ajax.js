jQuery(document).ready(function($) {
    $('#qna-form').on('submit', function(e) {
        e.preventDefault();

        var questionText = $('#qna-question').val().trim();
        var productId = qna_ajax_obj.product_id;
        var nonce = qna_ajax_obj.nonce;
        var $submitBtn = $('#qna-submit');
        var $messageBox = $('#qna-message');

        $messageBox.hide().removeClass('success error').text('');

        if (questionText === '') {
            dev_showMessage('Please enter your question.', 'error');
            return;
        }

         // Disable submit button and show loading
        $submitBtn.prop('disabled', true).text('Submitting...');
        $.ajax({
            url: qna_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'submit_qna_question',
                question: questionText,
                product_id: productId,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    dev_showMessage('Your question has been submited successfully!', 'success');
                    $('#qna-form')[0].reset();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    dev_showMessage('Error: ' + (response.data || 'Unable to post question.'), 'error');
                }
            },
            error: function() {
                dev_showMessage('An unexpected error occurred. Please try again.', 'error');
            },
            complete: function() {
                // Enable button again
                $submitBtn.prop('disabled', false).text('Submit Question');
            }
        });

        function dev_showMessage(msg, type) {
            $('#qna-message')
                .text(msg)
                .addClass(type)
                .fadeIn();
        }
    });

   
});
