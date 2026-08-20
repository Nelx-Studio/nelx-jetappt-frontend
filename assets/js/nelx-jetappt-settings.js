(function($){
    $(function(){
        var isSaving = false;
        
        // Show notification function
        window.showNotification = function(message, type) {
            if ($('#nelx-jetappt-notification-bar').length === 0) {
                $('body').append('<div id="nelx-jetappt-notification-bar" style="display:none;"></div>');
            }
            const notification = $('#nelx-jetappt-notification-bar');
            notification.html(`<span class="dashicons dashicons-${type === 'success' ? 'yes-alt' : 'warning'}"></span> ${message}`)
                .removeClass('success error')
                .addClass(type)
                .stop(true, true)
                .fadeIn(200)
                .delay(3000)
                .fadeOut(200);
        };
        
        // Sticky header functionality
        function handleStickyHeader() {
            var $wrapper = $('.nelx-jetappt-admin-tabs-wrapper');
            if ($wrapper.length) {
                var offsetTop = $wrapper.offset().top;
                if ($(window).scrollTop() > offsetTop) {
                    $wrapper.addClass('is-sticky');
                } else {
                    $wrapper.removeClass('is-sticky');
                }
            }
        }
        
        var scrollTimeout;
        $(window).on('scroll', function() {
            if (scrollTimeout) clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(function() {
                handleStickyHeader();
                scrollTimeout = null;
            }, 10);
        });
        handleStickyHeader();
        
        // Initialize color pickers
        function initColorPickers() {
            $('.nelx-jetappt-color-picker').each(function() {
                var $this = $(this);
                if (!$this.hasClass('wp-color-picker') || !$this.data('wpWpColorPicker')) {
                    $this.wpColorPicker({
                        change: function(event, ui) {
                            $this.trigger('change');
                        },
                        clear: function() {
                            $this.trigger('change');
                        },
                        palettes: true
                    });
                }
            });
        }
        
        // Initialize color picker close on click outside
        $(document).off('click.color-picker-close');
        $(document).on('click.color-picker-close', function(e) {
            var $pickers = $('.nelx-jetappt-color-picker');
            if ($pickers.length) {
                $pickers.each(function() {
                    var $picker = $(this);
                    var $container = $picker.closest('.wp-picker-container');
                    if ($container.length && $picker.hasClass('wp-color-picker') && !$container.is(e.target) && 0 === $container.has(e.target).length) {
                        var $pickerInput = $container.find('.wp-color-picker');
                        if ($pickerInput.length && $pickerInput.is(':visible')) {
                            $picker.wpColorPicker('close');
                        }
                    }
                });
            }
        });
        
        // Tab functionality
        $('.nelx-jetappt-nav-item').on('click', function(e) {
            e.preventDefault();
            $('.nelx-jetappt-nav-item').removeClass('active');
            $('.nelx-jetappt-tab-content').removeClass('active');
            $(this).addClass('active');
            var tabId = $(this).data('tab');
            $('#' + tabId).addClass('active');
            
            // Reinitialize color pickers for the active tab
            initColorPickers();
            
            // Trigger resize for any editors
            if (typeof tinyMCE !== 'undefined') {
                setTimeout(function() {
                    $(window).trigger('resize');
                }, 100);
            }
            
            setTimeout(handleStickyHeader, 50);
        });
        
        // Initialize the first tab as active if none is active
        if ($('.nelx-jetappt-nav-item.active').length === 0) {
            $('.nelx-jetappt-nav-item').first().addClass('active');
            $('.nelx-jetappt-tab-content').first().addClass('active');
        }
        
        // Initialize color pickers
        initColorPickers();
        
        // Create a tooltip container appended to the body
        var $tooltip = $('<div class="nelx-custom-tooltip"></div>').appendTo('body').hide();
    
        // Use event delegation for dynamically added elements
        $(document).on('mouseenter', '.nelx-tooltip-icon', function() {
            var $this = $(this);
            var tooltipText = $this.data('tooltip-text') || $this.attr('title');
            $this.data('tooltip-text', tooltipText).removeAttr('title');
            $tooltip.html(tooltipText);
            var offset = $this.offset();
            var iconWidth = $this.outerWidth();
            var tooltipWidth = $tooltip.outerWidth();
            var tooltipHeight = $tooltip.outerHeight();
            var leftPos = offset.left + iconWidth + 10;
            var topPos = offset.top - (tooltipHeight / 2) + ($this.outerHeight() / 2);
            $tooltip.css({
                left: leftPos + 'px',
                top: topPos + 'px'
            }).fadeIn(200);
        }).on('mouseleave', '.nelx-tooltip-icon', function() {
            var $this = $(this);
            $this.attr('title', $this.data('tooltip-text'));
            $tooltip.fadeOut(200);
        });
        
        // Copy button functionality
        $('.nelx-copy-btn').on('click', function() {
            var text = $(this).data('copy');
            var $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(text).select();
            document.execCommand('copy');
            $temp.remove();
            var originalText = $(this).text();
            $(this).text('Copied!');
            setTimeout(function() {
                $(this).text(originalText);
            }.bind(this), 2000);
        });
        
        // Shortcode copy functionality
        $(document).on('click', '.nelx-copy-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var button = $(this);
            var shortcodeItem = button.closest('.nelx-shortcode-item');
            var shortcode = shortcodeItem.find('.nelx-shortcode-label').text().trim();
            
            if (!shortcode) {
                console.error('No shortcode text found');
                return;
            }
            
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(shortcode).then(function() {
                    showCopySuccess(button);
                }).catch(function(err) {
                    console.error('Clipboard API failed: ', err);
                    fallbackCopyText(shortcode, button);
                });
            } else {
                fallbackCopyText(shortcode, button);
            }
        });
        
        function fallbackCopyText(text, button) {
            var $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(text).select();
            try {
                var successful = document.execCommand('copy');
                if (successful) {
                    showCopySuccess(button);
                } else {
                    console.error('Fallback copy failed');
                    showCopyError(button);
                }
            } catch (err) {
                console.error('Fallback copy error: ', err);
                showCopyError(button);
            }
            $temp.remove();
        }
        
        function showCopySuccess(button) {
            var originalHtml = button.html();
            var originalClasses = button.attr('class');
            
            button.html('<span class="dashicons dashicons-yes"></span> ' + (nelx_jetappt_data.i18n.copied || 'Copied!'));
            button.addClass('copied');
            
            button.data('original-html', originalHtml);
            button.data('original-classes', originalClasses);
            
            setTimeout(function() {
                button.html(button.data('original-html'));
                button.attr('class', button.data('original-classes'));
            }, 2000);
        }
        
        function showCopyError(button) {
            var originalHtml = button.html();
            button.html('<span class="dashicons dashicons-no"></span> Error');
            button.addClass('copy-error');
            
            setTimeout(function() {
                button.html(originalHtml);
                button.removeClass('copy-error');
            }, 2000);
        }
        
        // Logo uploader functionality
        $(document).on('click', '.nelx-jetappt-change-logo, .nelx-jetappt-select-logo, .nelx-jetappt-upload-icon, .nelx-jetappt-change-social-icon', function() {
            var button = $(this);
            var isIcon = button.hasClass('nelx-jetappt-upload-icon') || button.hasClass('nelx-jetappt-change-social-icon');
            var uploader = wp.media({
                title: isIcon ? nelx_jetappt_data.i18n.select_image : nelx_jetappt_data.i18n.select_image,
                button: {
                    text: isIcon ? nelx_jetappt_data.i18n.use_image : nelx_jetappt_data.i18n.use_image
                },
                library: {
                    type: isIcon ? ['image/svg+xml', 'image/png'] : 'image'
                },
                multiple: false
            }).on('select', function() {
                var attachment = uploader.state().get('selection').first().toJSON();
                if (isIcon) {
                    var container = button.closest('.nelx-jetappt-icon-uploader');
                    container.find('.nelx-jetappt-icon-url').val(attachment.url);
                    container.find('.nelx-jetappt-icon-preview').html('<img src="' + attachment.url + '" style="max-width: 24px; max-height: 24px;">' +
                        '<button type="button" class="button nelx-jetappt-change-social-icon">' + nelx_jetappt_data.i18n.change_image + '</button>' +
                        '<button type="button" class="button nelx-jetappt-remove-icon">' + nelx_jetappt_data.i18n.remove + '</button>');
                } else {
                    $('#email_logo_url').val(attachment.url);
                    $('#nelx-jetappt-logo-dropzone').removeClass('empty').html(
                        '<img src="' + attachment.url + '" class="logo-preview">' +
                        '<div class="dropzone-overlay">' +
                        '<span class="dashicons dashicons-update"></span>' +
                        '<span>' + nelx_jetappt_data.i18n.click_or_drag + '</span>' +
                        '</div>'
                    );
                    $('.nelx-jetappt-logo-actions').show();
                    $('.nelx-jetappt-logo-fallback').hide();
                }
            }).open();
        });
        
        $(document).on('click', '.nelx-jetappt-remove-logo', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to remove this logo?')) {
                $('#email_logo_url').val('');
                $('#nelx-jetappt-logo-dropzone').addClass('empty').html(
                    '<div class="dropzone-content">' +
                    '<span class="dashicons dashicons-format-image"></span>' +
                    '<span>' + nelx_jetappt_data.i18n.drag_drop_or + '</span>' +
                    '<button type="button" class="button button-primary nelx-jetappt-select-logo">' + nelx_jetappt_data.i18n.select_image + '</button>' +
                    '<small>' + nelx_jetappt_data.i18n.recommended_size + '</small>' +
                    '</div>'
                );
                $('.nelx-jetappt-logo-actions').hide();
                $('.nelx-jetappt-logo-fallback').show();
            }
        });
        
        $(document).on('click', '.nelx-jetappt-remove-icon', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to remove this logo icon?')) {
                var container = $(this).closest('.nelx-jetappt-icon-uploader');
                var $preview = container.find('.nelx-jetappt-icon-preview');
                container.find('.nelx-jetappt-icon-url').val('');
                container.find('img').remove();
                $preview.html(`
                    <button type="button" class="button nelx-jetappt-upload-icon">${nelx_jetappt_data.i18n.select_image}</button>
                    <button type="button" class="button nelx-jetappt-change-social-icon" style="display: none;">${nelx_jetappt_data.i18n.change_image}</button>
                    <button type="button" class="button nelx-jetappt-remove-icon" style="display: none;">${nelx_jetappt_data.i18n.remove}</button>
                `);
            }
        });
        
        $(document).on('click', '.nelx-jetappt-remove-social-icon', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to remove this social icon?')) {
                $(this).closest('.nelx-jetappt-social-icon').remove();
            }
        });
        
        // Add new social icon with proper field names
        $('#nelx-jetappt-add-social-icon').on('click', function() {
            var container = $('#nelx-jetappt-social-icons-container');
            var index = container.children().length;
            var template = `
                <div class="nelx-jetappt-social-icon" style="margin-bottom: 15px; padding: 10px; border: 1px solid #ddd; background: #f9f9f9;">
                    <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                        <div style="flex: 1;">
                            <label>${nelx_jetappt_data.i18n.url}</label>
                            <input type="text" 
                                name="nelx_email_branding_settings[email_social_icons][${index}][url]" 
                                value="" 
                                class="regular-text" 
                                placeholder="https://example.com">
                        </div>
                        <div style="flex: 1;">
                            <label>${nelx_jetappt_data.i18n.icon}</label>
                            <div class="nelx-jetappt-icon-uploader">
                                <input type="hidden" 
                                    name="nelx_email_branding_settings[email_social_icons][${index}][icon]" 
                                    class="nelx-jetappt-icon-url" 
                                    value="">
                                <div class="nelx-jetappt-icon-preview" style="display: flex; align-items: center; gap: 10px;">
                                    <button type="button" class="button nelx-jetappt-upload-icon">${nelx_jetappt_data.i18n.select_image}</button>
                                    <button type="button" class="button nelx-jetappt-remove-icon" style="display:none;">${nelx_jetappt_data.i18n.remove}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="button nelx-jetappt-remove-social-icon">${nelx_jetappt_data.i18n.remove_icon}</button>
                </div>
            `;
            container.append(template);
        });
        
        // Email templates subtabs
        $('.nelx-appt-template-tabs-nav a').on('click', function(e) {
            e.preventDefault();
            var tab = $(this).data('tab');
            
            $('.nelx-appt-template-tabs-nav a').removeClass('active');
            $(this).addClass('active');
            $('.nelx-appt-template-tab-content').removeClass('active');
            $('#' + tab).addClass('active');
        });
        
        // Copy cron command functionality
        $('#nelx-appt-copy-cron').on('click', function() {
            var button = $(this);
            var originalText = button.text();
            var command = $('#nelx-appt-cron-command').text();
            
            navigator.clipboard.writeText(command).then(function() {
                button.text(nelx_jetappt_data.i18n.copied || 'Copied!');
                setTimeout(function() {
                    button.text(originalText);
                }, 2000);
            }).catch(function() {
                var $temp = $('<textarea>');
                $('body').append($temp);
                $temp.val(command).select();
                document.execCommand('copy');
                $temp.remove();
                button.text(nelx_jetappt_data.i18n.copied || 'Copied!');
                setTimeout(function() {
                    button.text(originalText);
                }, 2000);
            });
        });
        
        // Test cron functionality
        $('#nelx-appt-test-cron').on('click', function() {
            var button = $(this);
            var originalText = button.text();
            var resultContainer = $('#nelx-appt-cron-test-result');
            var manualConfirm = $('#nelx-appt-cron-manual-confirm');
            
            button.prop('disabled', true).text(nelx_jetappt_data.i18n.test_running || 'Testing...');
            resultContainer.html('<div class="notice notice-info"><p>' + (nelx_jetappt_data.i18n.test_running || 'Testing cron setup...') + '</p></div>');
            manualConfirm.hide();
            
            $.post(ajaxurl, {
                action: 'nelx_test_appointment_automation',
                nonce: nelx_jetappt_data.nonce
            }, function(response) {
                var messageClass = response.success ? 'notice-success' : 'notice-error';
                var message = response.data.message;
                
                // Build the log display
                var logHtml = '';
                if (response.data.log) {
                    logHtml = '<pre style="background:#f5f5f5;padding:10px;border-radius:4px;overflow-x:auto;margin-top:10px;font-size:12px;line-height:1.6;">' + 
                              response.data.log.replace(/\n/g, '<br>') + '</pre>';
                }
                
                // Build setup instructions if available
                var instructionsHtml = '';
                if (response.data.setup_instructions) {
                    instructionsHtml = '<pre style="background:#f5f5f5;padding:10px;border-radius:4px;overflow-x:auto;margin-top:10px;font-size:12px;line-height:1.6;border:1px solid #ddd;">' + 
                                       response.data.setup_instructions.replace(/\n/g, '<br>') + '</pre>';
                }
                
                resultContainer.html('<div class="notice ' + messageClass + '"><p>' + message + '</p>' + logHtml + instructionsHtml + '</div>');
                
                // Show manual confirm button if required
                if (response.data.requires_manual_confirmation) {
                    manualConfirm.show();
                } else {
                    manualConfirm.hide();
                }
                
                button.prop('disabled', false).text(originalText);
                
                // Auto-hide after 30 seconds (only if not showing manual confirm)
                if (!response.data.requires_manual_confirmation) {
                    setTimeout(function() {
                        resultContainer.fadeOut(500, function() {
                            $(this).html('').show();
                        });
                    }, 30000);
                }
            }).fail(function(xhr, status, error) {
                resultContainer.html('<div class="notice notice-error"><p>' + (nelx_jetappt_data.i18n.test_error || 'Error testing cron setup: ') + error + '</p></div>');
                button.prop('disabled', false).text(originalText);
                manualConfirm.hide();
            });
        });
        
        // Manual confirmation handler
        $('#nelx-appt-confirm-cron').on('click', function() {
            var button = $(this);
            var originalText = button.html();
            var resultContainer = $('#nelx-appt-cron-test-result');
            var manualConfirm = $('#nelx-appt-cron-manual-confirm');
            
            button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> ' + (nelx_jetappt_data.i18n.confirming || 'Confirming...'));
            resultContainer.html('<div class="notice notice-info"><p>' + (nelx_jetappt_data.i18n.confirming || 'Confirming cron setup...') + '</p></div>');
            
            $.post(ajaxurl, {
                action: 'nelx_test_appointment_automation',
                nonce: nelx_jetappt_data.nonce,
                manual_confirm: 1
            }, function(response) {
                var messageClass = response.success ? 'notice-success' : 'notice-error';
                var message = response.data.message;
                
                var logHtml = '';
                if (response.data.log) {
                    logHtml = '<pre style="background:#f5f5f5;padding:10px;border-radius:4px;overflow-x:auto;margin-top:10px;font-size:12px;line-height:1.6;">' + 
                              response.data.log.replace(/\n/g, '<br>') + '</pre>';
                }
                
                resultContainer.html('<div class="notice ' + messageClass + '"><p>' + message + '</p>' + logHtml + '</div>');
                
                if (response.success) {
                    manualConfirm.hide();
                }
                
                button.prop('disabled', false).html(originalText);
                
                // Auto-hide after 30 seconds on success
                if (response.success) {
                    setTimeout(function() {
                        resultContainer.fadeOut(500, function() {
                            $(this).html('').show();
                        });
                    }, 30000);
                }
            }).fail(function(xhr, status, error) {
                resultContainer.html('<div class="notice notice-error"><p>' + (nelx_jetappt_data.i18n.confirm_error || 'Error confirming cron setup: ') + error + '</p></div>');
                button.prop('disabled', false).html(originalText);
            });
        });
        
        // Toggle notifications fields wrapper based on checkbox
        $('#notifications-enabled').on('change', function() {
            if ($(this).is(':checked')) {
                $('#notifications-fields-wrapper').slideDown(200);
            } else {
                $('#notifications-fields-wrapper').slideUp(200);
            }
            
            // Auto-save when toggled
            saveNotificationsSettings();
        });
        
        // Initialize field visibility on page load
        if ($('#notifications-enabled').is(':checked')) {
            $('#notifications-fields-wrapper').show();
        } else {
            $('#notifications-fields-wrapper').hide();
        }
        
        // Function to save notifications settings immediately
        function saveNotificationsSettings() {
            var isEnabled = $('#notifications-enabled').is(':checked');
            var notificationsEnabled = isEnabled ? '1' : '0';
            var providerAppointmentsPage = $('#provider-appointments-page').val() || '';
            var clientAppointmentsPage = $('#client-appointments-page').val() || '';
            
            // Show saving indicator
            var savingIndicator = $('#notifications-enabled').closest('.nelx-field-card').find('.nelx-saving-indicator');
            if (!savingIndicator.length) {
                savingIndicator = $('<span class="nelx-saving-indicator" style="margin-left: 10px; color: #0073aa; font-style: italic;"></span>');
                $('#notifications-enabled').closest('.nelx-field-card').append(savingIndicator);
            }
            
            savingIndicator.text('Saving...');
            
            // Create notification container if it doesn't exist
            if (!$('#nelx-jetappt-notification-bar').length) {
                $('body').append('<div id="nelx-jetappt-notification-bar" style="display:none;"></div>');
            }
            
            // Build form data as URL-encoded string
            var formData = new FormData();
            formData.append('action', 'nelx_save_notifications_settings');
            formData.append('_wpnonce', $('input[name="_nelx_wpnonce"]').val());
            formData.append('_wp_http_referer', $('input[name="_wp_http_referer"]').val());
            formData.append('nelx_jetappt_settings[notifications_enabled]', notificationsEnabled);
            formData.append('nelx_jetappt_settings[provider_appointments_page]', providerAppointmentsPage);
            formData.append('nelx_jetappt_settings[client_appointments_page]', clientAppointmentsPage);
            
            // Submit via AJAX
            $.ajax({
                url: nelx_jetappt_data.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        savingIndicator.text('Saved!');
                        setTimeout(function() {
                            savingIndicator.fadeOut(500, function() {
                                $(this).text('');
                                $(this).show();
                            });
                        }, 2000);
                        showNotification(response.data || 'Notifications settings saved successfully!', 'success');
                    } else {
                        savingIndicator.text('Error!');
                        showNotification(response.data || 'Error saving notifications settings', 'error');
                        console.error('Save error:', response);
                    }
                },
                error: function(xhr, status, error) {
                    savingIndicator.text('Error!');
                    showNotification('Error saving notifications settings: ' + error, 'error');
                    console.error('AJAX error:', xhr, status, error);
                }
            });
        }
        
        // Save when page URLs change (on blur)
        $('#provider-appointments-page, #client-appointments-page').on('blur', function() {
            saveNotificationsSettings();
        });
        
        // Also save when pressing Enter in these fields
        $('#provider-appointments-page, #client-appointments-page').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                saveNotificationsSettings();
            }
        });
        
        // Toggle automatic delete past appointments settings
        $('#auto-delete-past').on('change', function() {
            if ($(this).is(':checked')) {
                $('#auto-delete-past-settings').show();
            } else {
                $('#auto-delete-past-settings').hide();
            }
            saveAutomationSettings();
        });
        
        // Toggle automatic delete canceled appointments settings
        $('#auto-delete-canceled').on('change', function() {
            if ($(this).is(':checked')) {
                $('#auto-delete-canceled-settings').show();
            } else {
                $('#auto-delete-canceled-settings').hide();
            }
            saveAutomationSettings();
        });
        
        // Toggle custom days input
        $('#auto-delete-past-days').on('change', function() {
            if ($(this).val() === 'custom') {
                $('#auto-delete-past-custom-container').show();
            } else {
                $('#auto-delete-past-custom-container').hide();
            }
            saveAutomationSettings();
        });
        
        // Auto-save automation settings
        function saveAutomationSettings() {
            var autoDeletePast = $('#auto-delete-past').is(':checked') ? '1' : '0';
            var autoDeletePastDays = $('#auto-delete-past-days').val();
            var autoDeletePastCustom = $('#auto-delete-past-custom').val();
            var autoDeleteCanceled = $('#auto-delete-canceled').is(':checked') ? '1' : '0';
            var reminderTiming = $('#nelx-reminder-timing').val();
            
            var savingIndicator = $('#auto-delete-past').closest('td').find('.nelx-saving-indicator');
            if (!savingIndicator.length) {
                savingIndicator = $('<span class="nelx-saving-indicator" style="margin-left: 10px; color: #0073aa; font-style: italic;"></span>');
                $('#auto-delete-past').closest('td').append(savingIndicator);
            }
            
            savingIndicator.text('Saving...');
            
            var formData = {
                'nelx_jetappt_settings[auto_delete_past]': autoDeletePast,
                'nelx_jetappt_settings[auto_delete_past_days]': autoDeletePastDays,
                'nelx_jetappt_settings[auto_delete_past_custom]': autoDeletePastCustom,
                'nelx_jetappt_settings[auto_delete_canceled]': autoDeleteCanceled,
                'nelx_jetappt_settings[reminder_timing]': reminderTiming
            };
            
            $.ajax({
                url: nelx_jetappt_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'nelx_save_automation_settings',
                    _wpnonce: $('input[name="_nelx_wpnonce"]').val(),
                    _wp_http_referer: $('input[name="_wp_http_referer"]').val(),
                    data: $.param(formData)
                },
                success: function(response) {
                    if (response.success) {
                        savingIndicator.text('Saved!');
                        setTimeout(function() {
                            savingIndicator.fadeOut(500, function() {
                                $(this).text('');
                                $(this).show();
                            });
                        }, 2000);
                        showNotification(response.data || 'Automation settings saved successfully!', 'success');
                    } else {
                        savingIndicator.text('Error!');
                        showNotification(response.data || 'Error saving automation settings', 'error');
                    }
                },
                error: function(xhr) {
                    savingIndicator.text('Error!');
                    showNotification('Error saving automation settings: ' + xhr.statusText, 'error');
                }
            });
        }
        
        // Auto-save when reminder timing changes
        $('#nelx-reminder-timing').on('change', function() {
            saveAutomationSettings();
        });
        
        // Auto-save when custom days input changes
        $('#auto-delete-past-custom').on('blur', function() {
            saveAutomationSettings();
        });
        
        // Manual delete past appointments
        $('#nelx-manual-delete-past').on('click', function() {
            var days = $('#manual-delete-past-days').val();
            
            if (!days || days < 3) {
                alert('Please enter at least 3 days');
                return;
            }
            
            if (!confirm('Are you sure you want to delete appointments older than ' + days + ' days? This action is irreversible!')) {
                return;
            }
            
            var button = $(this);
            var originalText = button.text();
            
            button.prop('disabled', true).text('Deleting...');
            
            $.post(ajaxurl, {
                action: 'nelx_manual_delete_past_appointments',
                nonce: nelx_jetappt_data.nonce,
                days: days
            }, function(response) {
                if (response.success) {
                    showNotification(response.data || 'Past appointments deleted successfully!', 'success');
                } else {
                    showNotification(response.data || 'Error deleting past appointments', 'error');
                }
                button.prop('disabled', false).text(originalText);
            }).fail(function() {
                showNotification('Error deleting past appointments', 'error');
                button.prop('disabled', false).text(originalText);
            });
        });
        
        // Manual delete canceled appointments
        $('#nelx-manual-delete-canceled').on('click', function() {
            if (!confirm('Are you sure you want to delete all canceled appointments? This action is irreversible!')) {
                return;
            }
            
            var button = $(this);
            var originalText = button.text();
            
            button.prop('disabled', true).text('Deleting...');
            
            $.post(ajaxurl, {
                action: 'nelx_manual_delete_canceled_appointments',
                nonce: nelx_jetappt_data.nonce
            }, function(response) {
                if (response.success) {
                    showNotification(response.data || 'Canceled appointments deleted successfully!', 'success');
                } else {
                    showNotification(response.data || 'Error deleting canceled appointments', 'error');
                }
                button.prop('disabled', false).text(originalText);
            }).fail(function() {
                showNotification('Error deleting canceled appointments', 'error');
                button.prop('disabled', false).text(originalText);
            });
        });
        
        // Handle save button click
        $('#nelx-jetappt-tabs-save-button').on('click', function(e) {
            e.preventDefault();
            $('#nelx-jetappt-settings-form').submit();
        });
        
        // Function to collect default templates data
        function collectDefaultTemplatesData() {
            var templates = [];
            
            $('.nelx-jetappt-repeater-item[data-template-type="default"]').each(function(index) {
                var $item = $(this);
                var $formSelect = $item.find('.njet-form-select');
                var formId = $formSelect.length ? $formSelect.val() || '0' : '0';
                var templateName = $item.find('.item-title-text').text().trim();
                
                var toValue = $item.find('input[name$="[email_settings][to]"]').val() || '';
                var ccValue = $item.find('input[name$="[email_settings][cc]"]').val() || '';
                var bccValue = $item.find('input[name$="[email_settings][bcc]"]').val() || '';
                var subjectValue = $item.find('input[name$="[email_settings][subject]"]').val() || '';
                var fromValue = $item.find('input[name$="[email_settings][from]"]').val() || '';
                
                var messageValue = '';
                var $textarea = $item.find('textarea[id^="default_email_msg_"]');
                if ($textarea.length) {
                    var editorId = $textarea.attr('id');
                    if (editorId && window.tinyMCE && window.tinyMCE.get(editorId) && !window.tinyMCE.get(editorId).isHidden()) {
                        messageValue = window.tinyMCE.get(editorId).getContent();
                    } else {
                        messageValue = $textarea.val();
                    }
                }
                
                templates.push({
                    name: templateName,
                    form_id: formId,
                    email_settings: {
                        to: toValue,
                        cc: ccValue,
                        bcc: bccValue,
                        subject: subjectValue,
                        message: messageValue,
                        from: fromValue
                    }
                });
            });
            
            return templates;
        }
        
        // Function to collect custom templates data
        function collectCustomTemplatesData() {
            var templates = [];
            
            $('.nelx-jetappt-repeater-item[data-template-type="custom"]').each(function(index) {
                var $item = $(this);
                var $formSelect = $item.find('.njet-form-select');
                var formId = $formSelect.length ? $formSelect.val() || '0' : '0';
                var templateName = $item.find('.template-name-input').val().trim();
                if (!templateName) {
                    templateName = 'Untitled Template';
                }
                
                var toValue = $item.find('input[name$="[email_settings][to]"]').val() || '';
                var ccValue = $item.find('input[name$="[email_settings][cc]"]').val() || '';
                var bccValue = $item.find('input[name$="[email_settings][bcc]"]').val() || '';
                var subjectValue = $item.find('input[name$="[email_settings][subject]"]').val() || '';
                var fromValue = $item.find('input[name$="[email_settings][from]"]').val() || '';
                
                var messageValue = '';
                var $textarea = $item.find('textarea[id^="custom_email_msg_"]');
                if ($textarea.length) {
                    var editorId = $textarea.attr('id');
                    if (editorId && window.tinyMCE && window.tinyMCE.get(editorId) && !window.tinyMCE.get(editorId).isHidden()) {
                        messageValue = window.tinyMCE.get(editorId).getContent();
                    } else {
                        messageValue = $textarea.val();
                    }
                }
                
                templates.push({
                    name: templateName,
                    form_id: formId,
                    email_settings: {
                        to: toValue,
                        cc: ccValue,
                        bcc: bccValue,
                        subject: subjectValue,
                        message: messageValue,
                        from: fromValue
                    }
                });
            });
            
            return templates;
        }
        
        // Handle form submit with AJAX
        $('#nelx-jetappt-settings-form').on('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (isSaving) return;
            
            // Validation
            $('.nelx-jetappt-validation-message').remove();
            let hasEmptyFields = false;
            let firstInvalidField = null;
            
            $(this).find('.nelx-jetappt-tab-content.active [required]').each(function() {
                if ($(this).val().trim() === '') {
                    hasEmptyFields = true;
                    $(this).css('border-color', '#dc3232');
                    if (!firstInvalidField) {
                        firstInvalidField = this;
                    }
                } else {
                    $(this).css('border-color', '');
                }
            });
            
            if (hasEmptyFields) {
                if (firstInvalidField) {
                    $('html, body').animate({
                        scrollTop: $(firstInvalidField).offset().top - 100
                    }, 500);
                }
                
                $(this).prepend(
                    '<div class="nelx-jetappt-validation-message notice notice-error" style="padding: 10px; margin-bottom: 20px;">' +
                    '<p>' + (nelx_jetappt_data.i18n.fill_required_fields || 'Please fill in all required fields in the current tab.') + '</p>' +
                    '</div>'
                );
                return false;
            }
            
            isSaving = true;
            
            // Save TinyMCE content
            if (typeof tinyMCE !== 'undefined') {
                tinyMCE.triggerSave();
            }
            
            var $form = $(this);
            var $saveButton = $('#nelx-jetappt-tabs-save-button');
            var originalButtonText = $saveButton.val();
            
            // Show saving state
            $saveButton.addClass('is-saving').val('Saving...').prop('disabled', true);
            
            // Collect form data
            var formData = $form.serializeArray();
            
            // Handle unchecked checkboxes
            $form.find('input[type="checkbox"]').each(function() {
                var $checkbox = $(this);
                var name = $checkbox.attr('name');
                if (!$checkbox.is(':checked') && !formData.some(function(item) { return item.name === name; })) {
                    formData.push({ name: name, value: '0' });
                }
            });
            
            // Collect templates data
            var defaultTemplates = collectDefaultTemplatesData();
            var customTemplates = collectCustomTemplatesData();
            
            var serializedData = $.param(formData);
            
            // Create notification container if it doesn't exist
            if (!$('#nelx-jetappt-notification-bar').length) {
                $('body').append('<div id="nelx-jetappt-notification-bar" style="display:none;"></div>');
            }
            
            // Submit via AJAX
            $.ajax({
                url: nelx_jetappt_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'nelx_save_jetappt_settings',
                    nonce: nelx_jetappt_data.nonce,
                    data: serializedData,
                    default_templates: JSON.stringify(defaultTemplates),
                    custom_templates: JSON.stringify(customTemplates)
                },
                success: function(response) {
                    if (response.success) {
                        showNotification(response.data || nelx_jetappt_data.i18n.settings_saved || 'Settings saved successfully!', 'success');
                    } else {
                        showNotification(response.data || nelx_jetappt_data.i18n.ajax_error || 'Error saving settings', 'error');
                    }
                },
                error: function(xhr) {
                    showNotification(nelx_jetappt_data.i18n.ajax_error + ': ' + xhr.statusText, 'error');
                },
                complete: function() {
                    $saveButton.removeClass('is-saving').val(originalButtonText).prop('disabled', false);
                    isSaving = false;
                    setTimeout(handleStickyHeader, 100);
                }
            });
        });
    });
})(jQuery);