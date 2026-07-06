/**
 * Amazon Product Mapping UI Enhancements - JavaScript
 * Path: inc/amazon/assets/amazon-product-mapping.js
 * 
 * Handles ASIN validation, status updates, and interactive features
 */

(function ($) {
    'use strict';

    // Verify globals are available from localized script
    if (typeof wpla_mapping_vars === 'undefined') {
        console.warn('Amazon Mapping: wpla_mapping_vars is not defined. Using fallback values.');
        window.wpla_mapping_vars = { ajax_url: '', nonce: '', wpla_admin_url: '' };
    }

    const ajaxUrl = wpla_mapping_vars.ajax_url;
    const nonce = wpla_mapping_vars.nonce;
    const adminUrl = wpla_mapping_vars.wpla_admin_url || '';

    // ASIN Validation
    function validateASINFormat(asin) {
        // ASIN format: 10 characters, starts with B followed by alphanumeric
        const asinRegex = /^B[A-Z0-9]{9}$/;
        return asinRegex.test(asin.toUpperCase());
    }

    // Initialize ASIN validation
    function initASINValidation() {
        const $asinInput = $('#_wpla_asin');

        if ($asinInput.length === 0) return;

        // Wrap input in validation wrapper
        if (!$asinInput.parent().hasClass('asin-input-wrapper')) {
            $asinInput.wrap('<div class="asin-input-wrapper"></div>');
            $asinInput.after('<span class="asin-validation-icon"><i class="fas fa-check-circle"></i></span>');
            $asinInput.parent().after('<span class="asin-validation-message"></span>');
        }

        const $validationIcon = $asinInput.siblings('.asin-validation-icon');
        const $validationMessage = $asinInput.parent().siblings('.asin-validation-message');

        // Validate on input
        $asinInput.on('input', function () {
            const asin = $(this).val().trim();

            // Clear validation if empty
            if (asin === '') {
                $validationIcon.removeClass('valid invalid checking').hide();
                $validationMessage.text('').removeClass('success error');
                return;
            }

            // Show checking state
            $validationIcon.removeClass('valid invalid').addClass('checking').show();
            $validationIcon.html('<i class="fas fa-spinner"></i>');

            // Debounce validation
            clearTimeout(window.asinValidationTimeout);
            window.asinValidationTimeout = setTimeout(function () {
                if (!validateASINFormat(asin)) {
                    $validationIcon.removeClass('checking valid').addClass('invalid');
                    $validationIcon.html('<i class="fas fa-times-circle"></i>');
                    $validationMessage.text('Invalid ASIN format. Should be 10 characters starting with B.').removeClass('success').addClass('error');
                    return;
                }

                // Call real validation API
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'wpla_validate_asin_api',
                        asin: asin,
                        security: nonce
                    },
                    success: function (response) {
                        if (response.success) {
                            $validationIcon.removeClass('checking invalid').addClass('valid');
                            $validationIcon.html('<i class="fas fa-check-circle"></i>');
                            $validationMessage.html(`Valid ASIN: <strong>${response.data.title}</strong>`).removeClass('error').addClass('success');
                        } else {
                            $validationIcon.removeClass('checking valid').addClass('invalid');
                            $validationIcon.html('<i class="fas fa-times-circle"></i>');
                            $validationMessage.text(response.data.message || 'ASIN not found on Amazon.').removeClass('success').addClass('error');
                        }
                    },
                    error: function () {
                        $validationIcon.removeClass('checking valid').addClass('invalid');
                        $validationIcon.html('<i class="fas fa-exclamation-triangle"></i>');
                        $validationMessage.text('API error occurred.').removeClass('success').addClass('error');
                    }
                });
            }, 800);
        });

        // Trigger validation on page load if ASIN exists
        if ($asinInput.val().trim() !== '') {
            $asinInput.trigger('input');
        }
    }

    // Enhanced Profile Selection Toggle
    function initProfileSelection() {
        const $listCheckbox = $('input[name="wpla_list_on_amazon"]');
        const $profileSelection = $('#wpla_profile_selection');

        if ($listCheckbox.length === 0) return;

        $listCheckbox.on('change', function () {
            if ($(this).is(':checked')) {
                $profileSelection.slideDown(300);
            } else {
                $profileSelection.slideUp(300);
            }
        });

        // Show if already checked
        if ($listCheckbox.is(':checked')) {
            $profileSelection.show();
        }
    }

    // Add visual feedback for sync checkbox
    function initSyncCheckbox() {
        const $syncCheckbox = $('input[name="_wpla_list_on_amazon_sync"]');

        if ($syncCheckbox.length === 0) return;

        // Wrap in enhanced wrapper if not already
        if (!$syncCheckbox.parent().hasClass('amazon-checkbox-wrapper')) {
            $syncCheckbox.parent().addClass('amazon-checkbox-wrapper');
        }

        // Add sync status indicator
        if ($syncCheckbox.is(':checked')) {
            if (!$syncCheckbox.siblings('.sync-status-indicator').length) {
                $syncCheckbox.after('<span class="sync-status-indicator active"><i class="fas fa-sync-alt"></i> Active</span>');
            }
        }

        $syncCheckbox.on('change', function () {
            const $indicator = $(this).siblings('.sync-status-indicator');

            if ($(this).is(':checked')) {
                if ($indicator.length) {
                    $indicator.removeClass('inactive').addClass('active').html('<i class="fas fa-sync-alt"></i> Active');
                } else {
                    $(this).after('<span class="sync-status-indicator active"><i class="fas fa-sync-alt"></i> Active</span>');
                }
            } else {
                $indicator.removeClass('active').addClass('inactive').html('<i class="fas fa-times"></i> Inactive');
            }
        });
    }

    // Add quick action buttons
    function addQuickActions() {
        const $amazonSection = $('.amazon-mapping-section');

        if ($amazonSection.length === 0) return;

        const postIdInput = $('input[name="post_ID"]').val(); // From our hidden field or Dokan default
        const postId = postIdInput || $('#_wpla_asin').data('post-id');
        const asin = $('#_wpla_asin').val();

        // Even if postId is missing, we might still want to show some actions if ASIN is present
        // but for syncing we definitely need the ID.

        // Check if quick actions already exist
        if ($amazonSection.find('.amazon-quick-actions').length > 0) return;

        let actionsHTML = '<div class="amazon-quick-actions">';

        // View on Amazon button (if ASIN exists)
        if (asin && validateASINFormat(asin)) {
            actionsHTML += `<a href="https://www.amazon.co.uk/dp/${asin}" target="_blank" class="amazon-quick-action-btn primary">
                <i class="fas fa-external-link-alt"></i> View on Amazon
            </a>`;
        }

        // Sync now button
        actionsHTML += `<button type="button" class="amazon-quick-action-btn success" id="amazon-sync-now">
            <i class="fas fa-sync-alt"></i> Sync from Amazon
        </button>`;

        // View listing details
        actionsHTML += `<a href="${wpla_mapping_vars.wpla_admin_url}admin.php?page=wpla&tab=listings&post_id=${postId}" target="_blank" class="amazon-quick-action-btn secondary">
            <i class="fas fa-list"></i> View Listing Details
        </a>`;

        actionsHTML += '</div>';

        $amazonSection.append(actionsHTML);

        // Bind sync now button
        $('#amazon-sync-now').on('click', function () {
            const $btn = $(this);
            const currentAsin = $('#_wpla_asin').val().trim();

            if (!currentAsin) {
                showMessage('Please enter an ASIN first.', 'error');
                return;
            }

            $btn.prop('disabled', true).html('<span class="amazon-loading"></span> Syncing...');

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wpla_sync_data_from_amazon',
                    post_id: postId,
                    asin: currentAsin,
                    security: nonce
                },
                success: function (response) {
                    if (response.success) {
                        $btn.html('<i class="fas fa-check"></i> Synced!');
                        showMessage(response.data.message, 'success');

                        // Update UI fields if returned
                        if (response.data.updates.title) {
                            $('#wpl_amazon_title').val(response.data.updates.title);
                        }
                        if (response.data.updates.price) {
                            $('#wpl_amazon_price').val(response.data.updates.price);
                        }

                        setTimeout(function () {
                            $btn.prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Sync from Amazon');
                        }, 2000);
                    } else {
                        $btn.prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Sync from Amazon');
                        showMessage(response.data.message, 'error');
                    }
                },
                error: function () {
                    $btn.prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Sync from Amazon');
                    showMessage('API error occurred during sync.', 'error');
                }
            });
        });
    }

    // Show message function
    function showMessage(message, type) {
        const iconMap = {
            'success': 'fa-check-circle',
            'error': 'fa-exclamation-circle',
            'info': 'fa-info-circle'
        };

        const icon = iconMap[type] || 'fa-info-circle';

        const $message = $(`<div class="amazon-message ${type}">
            <i class="fas ${icon}"></i>
            <span>${message}</span>
        </div>`);

        $('.amazon-mapping-section').prepend($message);

        // Scroll to message
        $('html, body').animate({
            scrollTop: $('.amazon-mapping-section').offset().top - 100
        }, 300);

        setTimeout(function () {
            $message.fadeOut(300, function () {
                $(this).remove();
            });
        }, 5000);
    }

    // Enhance status display
    function enhanceStatusDisplay() {
        // Find by text content to be more flexible (dokan might use different casing)
        const $statusLabel = $('.amazon-mapping-section label').filter(function () {
            return $(this).text().indexOf('Amazon Status:') !== -1;
        });

        if ($statusLabel.length === 0) {
            console.log('Amazon Product Mapping: Status label not found.');
            return;
        }

        const statusText = $statusLabel.find('strong').text().toLowerCase();

        // Add badge
        const badgeClass = `status-${statusText.replace(/\s+/g, '-')}`;
        $statusLabel.find('strong').wrap(`<span class="amazon-status-badge ${badgeClass}"></span>`);

        // Add info box
        let infoClass = 'info';
        let infoMessage = 'This product is linked to Amazon.';

        if (statusText === 'online' || statusText === 'published' || statusText === 'active') {
            infoClass = 'success';
            infoMessage = 'This product is live on Amazon and available for purchase.';
        } else if (statusText === 'failed' || statusText === 'error' || statusText === 'inactive') {
            infoClass = 'error';
            infoMessage = 'There was an error with this listing or it is inactive. Please check the details.';
        } else if (statusText === 'pending' || statusText === 'prepared') {
            infoClass = 'warning';
            infoMessage = 'This listing is being processed or prepared for Amazon.';
        }

        $statusLabel.after(`
            <div class="amazon-status-info ${infoClass}">
                <strong>Status Information</strong>
                <p>${infoMessage}</p>
            </div>
        `);
    }

    // Auto-uppercase ASIN
    function initASINAutoUppercase() {
        $('#_wpla_asin').on('input', function () {
            const cursorPos = this.selectionStart;
            this.value = this.value.toUpperCase();
            this.setSelectionRange(cursorPos, cursorPos);
        });
    }

    // Initialize all enhancements
    $(document).ready(function () {
        console.log('Amazon Product Mapping: Checking for ASIN input...');

        // Check if we're on product edit page
        if ($('#_wpla_asin').length === 0) {
            console.log('Amazon Product Mapping: ASIN input not found.');
            return;
        }

        console.log('Amazon Product Mapping: Initializing UI Enhancements...');

        initASINValidation();
        initASINAutoUppercase();
        initProfileSelection();
        initSyncCheckbox();
        enhanceStatusDisplay();
        addQuickActions();

        console.log('Amazon Product Mapping: UI Enhancements loaded successfully!');
    });

})(jQuery);
