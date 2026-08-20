(function($) {
    'use strict';

    /* helpers */
    function toInt(v, d) { d = d || 0; return (v === undefined || v === null || v === '') ? d : parseInt(v, 10) || d; }
    function pad2(n) { return (n < 10 ? '0' + n : '' + n); }
    function secondsToHHMM(s) { var hh = Math.floor(s / 3600), mm = Math.floor((s % 3600) / 60); return pad2(hh) + ':' + pad2(mm); }
    function hhmmToSeconds(hm) { if (!hm) return 0; if (/^\d+$/.test(hm)) return parseInt(hm, 10); var p = String(hm).split(':'); if (p.length === 2) return parseInt(p[0]) * 3600 + parseInt(p[1]) * 60; return parseInt(hm, 10); }
    
    function getCloseIconSVG() {
        return '<svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>';
    }

    /* Cached time options HTML */
    var timeOptionsHTML = null;
    
    /* Generate time options HTML (15-minute increments) */
    function generateTimeOptions() {
        if (timeOptionsHTML) return timeOptionsHTML;
        
        var options = '<option value="" selected>HH:MM</option>';
        for (var h = 0; h < 24; h++) {
            for (var m = 0; m < 60; m += 15) {
                var hour = pad2(h);
                var minute = pad2(m);
                var timeValue = hour + ':' + minute;
                options += '<option value="' + timeValue + '">' + timeValue + '</option>';
            }
        }
        
        timeOptionsHTML = options;
        return options;
    }
    
    /* Ensure all time dropdowns have skeletons */
    function ensureAllTimeDropdownSkeletons(context) {
        context = context || $(document);
        var $dropdowns = context.find('.nelx-time-dropdown');
        
        $dropdowns.each(function() {
            var $container = $(this);
            if ($container.find('select').length === 0 && $container.find('.nelx-skeleton-line').length === 0) {
                $container.append('<div class="nelx-skeleton-line" style="height: 40px; width: 100%;"></div>');
            }
        });
    }
    
    /* Observe DOM for late-rendered time dropdowns and inject skeletons immediately */
    function observeTimeDropdowns(context) {
        var $ctx = context ? $(context) : $(document);
        var target = $ctx.get(0);
        if (!target || !window.MutationObserver) return;
        
        if ($ctx.data('nelx-time-observer')) return;
        
        var observer = new MutationObserver(function(mutations) {
            var shouldCheck = false;
            
            mutations.forEach(function(m) {
                if (m.addedNodes && m.addedNodes.length > 0) {
                    shouldCheck = true;
                }
            });
            
            if (shouldCheck) {
                requestAnimationFrame(function() {
                    ensureAllTimeDropdownSkeletons($ctx);
                });
            }
        });
        
        observer.observe(target, { 
            childList: true, 
            subtree: true,
            attributes: false,
            characterData: false
        });
        
        $ctx.data('nelx-time-observer', observer);
    }
    
    /* Initialize a single time dropdown */
    function initSingleTimeDropdown($container) {
        var currentValue = $container.data('value') || '';
        
        var $select = $('<select class="nelx-input">').html(generateTimeOptions());
        
        if (currentValue) {
            $select.val(currentValue);
        }
        
        $select.css({ opacity: 0 });
        
        var $skeleton = $container.find('.nelx-skeleton-line');
        if ($skeleton.length) {
            $skeleton.replaceWith($select);
            setTimeout(function() {
                $select.addClass('initialized').css({ opacity: 1 });
            }, 100);
        } else {
            $container.append($select);
            $select.addClass('initialized').css({ opacity: 1 });
        }
        
        $select.on('change', function() {
            $container.trigger('timechange', [$(this).val()]);
        });
    }
    
    /* Initialize all time dropdowns with skeleton loading */
    function initTimeDropdowns(context) {
        context = context || $(document);
        var $dropdowns = context.find('.nelx-time-dropdown');
        
        ensureAllTimeDropdownSkeletons(context);
        
        setTimeout(function() {
            $dropdowns.each(function() {
                var $container = $(this);
                if ($container.find('select').length > 0) {
                    return;
                }
                initSingleTimeDropdown($container);
            });
        }, 1000);
    }

    /* spinner helper */
    function showButtonSpinner($btn) {
        $btn.attr('disabled', true);
        $btn.find('.nelx-btn-text').hide();
        $btn.find('.nelx-spinner').show();
    }
    function hideButtonSpinner($btn) {
        $btn.attr('disabled', false);
        $btn.find('.nelx-spinner').hide();
        $btn.find('.nelx-btn-text').show();
    }

    /* slide-in alert */
    function showAlert(msg, cls) {
        cls = cls || 'ok';
        var $root = $('#nelx-alert-root');
        if (!$root.length) $('body').append('<div id="nelx-alert-root" style="margin-top: 20px;"></div>');
        $root = $('#nelx-alert-root');
        var $alert = $('<div class="nelx-alert ' + cls + '"></div>');
        $alert.append('<svg viewBox="0 0 24 24">' + (cls === 'ok' ? '<path d="M9 16.17L4.83 12 3.41 13.41 9 19 21 7 19.59 5.59z"/>' : '<path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z"/>') + '</svg>');
        $alert.append('<span>' + msg + '</span>');
        $root.empty().append($alert);
        
        var timeoutDuration = $(window).width() <= 768 ? 5000 : 3000;
        setTimeout(function() { $alert.remove(); }, timeoutDuration);
    }
    
    /* Function to update empty slots message visibility */
    function updateEmptySlotsMessage($day) {
        var $slotsContainer = $day.find('.nelx-slots');
        var $existingSlots = $slotsContainer.find('.nelx-slot-row');
        var $emptyMessage = $slotsContainer.find('.nelx-empty-slots-message');
        
        if ($existingSlots.length === 0) {
            if ($emptyMessage.length === 0) {
                $slotsContainer.append('<div class="nelx-empty-slots-message">No working hours</div>');
            }
            $slotsContainer.attr('data-has-slots', 'false');
        } else {
            $emptyMessage.remove();
            $slotsContainer.attr('data-has-slots', 'true');
        }
    }
    
    function modal(html) {
        var $root = $('#nelx-modal-root');
        if (!$root.length) {
            $('body').append('<div id="nelx-modal-root"></div>');
            $root = $('#nelx-modal-root');
        }
    
        var $wrap = $(
            '<div class="nelx-modal" aria-hidden="false">' +
                '<div class="nelx-modal-backdrop"></div>' +
                '<div class="nelx-modal-card"></div>' +
            '</div>'
        );
    
        $wrap.find('.nelx-modal-card').html(html);
        $root.empty().append($wrap);
    
        // FIX: Apply corner rounding to ALL modals
        $wrap.find('.nelx-modal-card').css({
            'border-radius': '12px',
            'overflow': 'hidden'
        });
        $wrap.find('.nelx-modal-backdrop').css({
            'border-radius': '12px'
        });

        var $focusable = $wrap.find(
            'button:not(:disabled), [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        var firstFocusable = $focusable.first()[0];
        var lastFocusable = $focusable.last()[0];

        if (firstFocusable) {
            setTimeout(function () {
                firstFocusable.focus({ preventScroll: true });
            }, 50);
        }

        $wrap.on('keydown', function (e) {
            if (e.key === 'Tab' && $focusable.length) {
                if (e.shiftKey && document.activeElement === firstFocusable) {
                    e.preventDefault();
                    lastFocusable.focus({ preventScroll: true });
                } else if (!e.shiftKey && document.activeElement === lastFocusable) {
                    e.preventDefault();
                    firstFocusable.focus({ preventScroll: true });
                }
            }
        });

        $wrap.on('click', '.nelx-modal-backdrop', function (e) {
            if (e.target === this) closeModal($wrap);
        });

        $wrap.on('click', '.nelx-modal-close, [data-close="1"]', function (e) {
            e.preventDefault();
            closeModal($wrap);
        });

        $(document).on('keydown.nelx-modal', function (e) {
            if (e.key === 'Escape') closeModal($wrap);
        });

        return $wrap;
    }

    function closeModal($wrap) {
        $wrap.attr('aria-hidden', 'true');
        $(document).off('keydown.nelx-modal');
        $wrap.remove();
    }

    /* attach pickers with past dates blocked, year selection, and scrollable time */
    function attachPickers($ctx) {
        $ctx = $ctx || $(document);
        if (window.flatpickr) {
            var today = new Date(NELXJAF.today);
            today.setHours(0, 0, 0, 0);
            var maxDate = new Date('5030-09-01');
            
            // Get working hours from localized data
            var workingHours = NELXJAF.provider_schedule || NELXJAF.global_working_hours;
            var daysOffList = NELXJAF.days_off || [];
            
            // Function to check if a date has any working hours
            function dateHasWorkingHours(date) {
                var weekday = date.toLocaleDateString('en-US', { weekday: 'long' }).toLowerCase();
                var dateStr = date.toISOString().split('T')[0];
                
                // Check if date is in days off
                for (var i = 0; i < daysOffList.length; i++) {
                    // Use Date objects for comparison to avoid string issues
                    var startDate = new Date(daysOffList[i].start + 'T00:00:00Z');
                    var endDate = new Date(daysOffList[i].end + 'T00:00:00Z');
                    startDate.setHours(0, 0, 0, 0);
                    endDate.setHours(23, 59, 59, 999);
                    
                    if (date >= startDate && date <= endDate) {
                        return false;
                    }
                }
                
                // Check if weekday has working hours
                if (workingHours[weekday] && workingHours[weekday].length > 0) {
                    return true;
                }
                
                return false;
            }
        
            $ctx.find('input.nelx-date').each(function() {
                if (!this._flatpickr) {
                    flatpickr(this, { 
                        dateFormat: 'Y-m-d', 
                        defaultDate: this.value || undefined,
                        minDate: today,
                        maxDate: maxDate,
                        yearSelectorType: 'dropdown',
                        disable: [
                            function(date) {
                                // Disable past dates
                                if (date < today) return true;
                                // Disable dates without working hours
                                return !dateHasWorkingHours(date);
                            }
                        ]
                    });
                }
            });
        
            $ctx.find('input.nelx-datetime').each(function() {
                if (!this._flatpickr) {
                    flatpickr(this, { 
                        enableTime: true, 
                        dateFormat: 'Y-m-d H:i', 
                        time_24hr: true, 
                        defaultDate: this.value || undefined,
                        minDate: today,
                        maxDate: maxDate,
                        yearSelectorType: 'dropdown',
                        time_24hr: true,
                        hourIncrement: 1,
                        minuteIncrement: 1,
                        scrollable: true
                    });
                }
            });
        }
    }

    /* tiny pill builder */
    function pill(name, sub, data) {
        var $el = $('<span class="nelx-tag"></span>');
        $el.append('<span>' + name + (sub ? ' — ' + sub : '') + '</span>');
        $el.append('<button type="button" class="nelx-icon-btn nelx-edit-day" title="Edit"><svg viewBox="0 0 24 24" width="14" height="14"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25z" fill="currentColor"></path></svg></button>');
        $el.append('<button type="button" class="nelx-icon-btn nelx-remove-tag" title="Remove"><svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 4 21 20"></polyline><polyline points="3 20 5 22 21 6"></polyline></svg></button>');
        if (data) $el.attr('data-item', JSON.stringify(data));
        return $el;
    }

    /* default slot row */
    function daySlotRow(from, to, prevEnd, isFirstSlot) {
        // For first slot on a day, default to 09:00-17:00
        if (isFirstSlot) {
            from = '09:00';
            to = '17:00';
        } else if (prevEnd) {
            var prevEndTime = new Date('1970-01-01 ' + prevEnd);
            var nextStartTime = new Date(prevEndTime.getTime() + 3600000);
            from = pad2(nextStartTime.getHours()) + ':' + pad2(nextStartTime.getMinutes());
            to = pad2(nextStartTime.getHours() + 1) + ':' + pad2(nextStartTime.getMinutes());
        } else if (!from && !to) {
            from = '09:00';
            to = '17:00';
        }
        
        from = from || '09:00';
        to = to || '17:00';
        
        var $r = $('<div class="nelx-slot-row"></div>');
        $r.append($('<div class="nelx-time-dropdown" data-value="' + from + '"><div class="nelx-skeleton-line" style="height: 40px; width: 100%;"></div></div>'));
        $r.append('<span class="sep">–</span>');
        $r.append($('<div class="nelx-time-dropdown" data-value="' + to + '"><div class="nelx-skeleton-line" style="height: 40px; width: 100%;"></div></div>'));
        $r.append('<button type="button" class="nelx-icon-btn nelx-danger nelx-remove-slot" title="Remove"><svg viewBox="0 0 24 24"><path d="M6 7h12l-1 14H7L6 7zm3-3h6l1 2H8l1-2zM4 7h16v2H4z" fill="currentColor"/></svg></button>');
        return $r;
    }

    /* detect appointment id */
    function detectAppointmentId($wrap) {
        var id = toInt($wrap.attr('data-appointment-id') || $wrap.data('appointment-id'), 0);
        if (id) {
            return id;
        }
        
        id = toInt($wrap.attr('data-appointment') || $wrap.data('appointment'), 0);
        if (id) {
            return id;
        }
    
        var $listing = $wrap.closest('.jet-listing-grid__item, .jet-listing-dynamic-post, li, tr, .listing-item');
        if ($listing.length) {
            var $idField = $listing.find('.jet-listing-dynamic-field__content').filter(function() {
                var txt = $(this).text().trim();
                return /^\d+$/.test(txt);
            }).first();
            if ($idField.length) {
                id = toInt($idField.text().trim(), 0);
                return id;
            }
        }
    
        var dpid = toInt($wrap.closest('[data-post-id]').attr('data-post-id') || 0, 0);
        if (dpid && /^\d+$/.test(dpid)) {
            return dpid;
        }
    
        return 0;
    }

    /* fetch appointment info */
    function fetchAppointmentInfo(id) {
        return $.ajax({ 
            url: NELXJAF.root + 'appointments/' + id + '/info', 
            method: 'GET', 
            beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', NELXJAF.nonce); }
        });
    }

    /* fetch appointment info with timezone */
    function fetchAppointmentWithTimezone(id, isProvider) {
        return $.ajax({ 
            url: NELXJAF.root + 'appointments/' + id + '/with-timezone', 
            method: 'POST',
            data: JSON.stringify({ is_provider: isProvider }),
            contentType: 'application/json',
            beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', NELXJAF.nonce); }
        });
    }

    /* fetch batch appointment data */
    function fetchBatchAppointments(ids) {
        return $.ajax({
            url: NELXJAF.root + 'appointments/batch',
            method: 'POST',
            data: JSON.stringify({ ids: ids }),
            contentType: 'application/json',
            beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', NELXJAF.nonce); }
        });
    }

    /* fetch available slots */
    function fetchAvailableSlots(provider_post_id, date) {
        return $.ajax({ 
            url: NELXJAF.root + 'available-slots?provider_post_id=' + encodeURIComponent(provider_post_id) + '&date=' + encodeURIComponent(date), 
            method: 'GET', 
            beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', NELXJAF.nonce); }
        });
    }
    
    /* fetch available slots in timezone */
    function fetchAvailableSlotsInTimezone(provider_post_id, date, timezone) {
        return $.ajax({ 
            url: NELXJAF.root + 'available-slots-in-timezone',
            method: 'POST',
            data: JSON.stringify({ 
                provider_post_id: provider_post_id, 
                date: date, 
                timezone: timezone 
            }),
            contentType: 'application/json',
            beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', NELXJAF.nonce); }
        });
    }
    
    function isReschedulingAllowed(appointment, providerSettings) {
        var currentTime = Math.floor(Date.now() / 1000);
        var appointmentStart = appointment.slot;
        var lockedTime = getLockedTime(providerSettings);
        var cutoffTime = appointmentStart - lockedTime;
        
        return currentTime < cutoffTime;
    }
    
    function getLockedTime(providerSettings) {
        var useCustomSchedule = providerSettings.use_custom_schedule || false;
        
        if (useCustomSchedule && providerSettings.custom_schedule) {
            return parseInt(providerSettings.custom_schedule.locked_time) || 0;
        } else {
            return NELXJAF.global_locked_time || 0;
        }
    }

    /* get provider name */
    function get_provider_name(staffId) {
        var deferred = $.Deferred();
        if (!staffId || staffId === '-') {
            deferred.resolve('-');
        } else {
            $.ajax({
                url: NELXJAF.staffAppointmentsUrl + staffId,
                method: 'GET',
                beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', NELXJAF.nonce); }
            }).done(function(data) {
                deferred.resolve(data.post_title || 'Unknown');
            }).fail(function() {
                deferred.resolve('Unknown');
            });
        }
        return deferred.promise();
    }

    /* get service title */
    function get_service_title(postId) {
        if (!postId || postId === '-') return 'Unknown';
        return $.ajax({
            url: NELXJAF.root + 'get-service-title',
            method: 'POST',
            data: JSON.stringify({ service_id: postId }),
            contentType: 'application/json',
            beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', NELXJAF.nonce); }
        }).then(function(response) {
            return response.title || 'Unknown';
        }, function() {
            return 'Unknown';
        });
    }

    /* update appointment status */
    function updateAppointmentStatus(id, status) {
        return $.ajax({
            url: NELXJAF.root + 'appointments/' + id + '/status',
            method: 'POST',
            data: JSON.stringify({ status: status }),
            contentType: 'application/json',
            beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', NELXJAF.nonce); }
        });
    }

    /* update button states */
    function updateButtonStates($wrap, status, isPast, reschedulingAllowed) {
        $wrap.data('status', status);
        $wrap.data('is-past', isPast ? '1' : '0');
        $wrap.data('rescheduling-allowed', reschedulingAllowed);
        
        var $editButtons = $wrap.find('.nelx-edit');
        var $cancelButtons = $wrap.find('.nelx-reject');
        var $confirmButtons = $wrap.find('.nelx-confirm');
        
        if (isPast) {
            $editButtons.prop('disabled', true).css('opacity', '0.5');
            $cancelButtons.prop('disabled', true).css('opacity', '0.5');
            $confirmButtons.prop('disabled', true).css('opacity', '0.5');
        } else {
            if (!reschedulingAllowed) {
                $editButtons.prop('disabled', true).css('opacity', '0.5');
            } else {
                $editButtons.prop('disabled', false).css('opacity', '1');
            }
            if (status === 'accepted' || status === 'canceled') {
                $cancelButtons.prop('disabled', true).css('opacity', '0.5');
                $confirmButtons.prop('disabled', true).css('opacity', '0.5');
            } else {
                $cancelButtons.prop('disabled', false).css('opacity', '1');
                $confirmButtons.prop('disabled', false).css('opacity', '1');
            }
        }
    }
    
    function setButtonsLoadingState($wraps, isLoading) {
        $wraps.each(function() {
            var $wrap = $(this);
            if (isLoading) {
                $wrap.data('loading', true);
                $wrap.find('.nelx-edit, .nelx-confirm, .nelx-reject').each(function() {
                    var $btn = $(this);
                    $btn.prop('disabled', true);
                    if (!$btn.find('.nelx-micro-spinner').length) {
                        $btn.append('<span class="nelx-micro-spinner"></span>');
                        $btn.css('position', 'relative');
                    }
                });
            } else {
                $wrap.data('loading', false);
                $wrap.find('.nelx-micro-spinner').remove();
                $wrap.find('.nelx-edit, .nelx-confirm, .nelx-reject').css('position', '');
            }
        });
    }

    /* Load timezone dropdown from WordPress */
    function loadTimezoneDropdown($container, providerId, selectedTimezone, isProvider) {
        if (isProvider) {
            $container.html('<input type="text" class="nelx-input" value="' + (selectedTimezone || 'Not set') + '" disabled>');
            return;
        }
        
        $container.html('<div class="nelx-skeleton-line" style="height: 40px; width: 100%;"></div>');
        
        // Store provider ID on the container
        $container.data('provider-id', providerId);
        
        $.ajax({
            url: NELXJAF.root + 'get-timezone-dropdown',
            method: 'POST',
            data: JSON.stringify({ selected: selectedTimezone || '' }),
            contentType: 'application/json',
            beforeSend: function(xhr) { 
                xhr.setRequestHeader('X-WP-Nonce', NELXJAF.nonce); 
            }
        }).done(function(response) {
            $container.html(response.html);
            
            // Get the select element
            var $select = $container.find('.nelx-timezone-select');
            
            // Set the initial value
            if (selectedTimezone) {
                $select.val(selectedTimezone);
            }
            
            // Bind change event
            $select.off('change').on('change', function(e) {
                e.preventDefault();
                var newTimezone = $(this).val();
                
                // Get provider ID from the container (not the select)
                var providerId = $container.data('provider-id');
                
                // Get the modal and current date
                var $modal = $container.closest('.nelx-modal-card');
                var selectedDate = $modal.find('#edit_date').val();
                
                if (selectedDate && providerId && newTimezone) {
                    // Clear current slots to show loading state
                    $modal.find('#edit_start_time').html('<option value="">Loading slots...</option>');
                    $modal.find('#edit_end_time').html('<option value="">Loading slots...</option>');
                    // Load slots for the new timezone
                    loadAvailableSlotsForClient($modal, providerId, selectedDate, newTimezone);
                } else {
                    console.log('Missing data - date:', selectedDate, 'providerId:', providerId, 'timezone:', newTimezone);
                }
            });
            
            // Trigger initial load if needed
            var $modal = $container.closest('.nelx-modal-card');
            var currentDate = $modal.find('#edit_date').val();
            if (currentDate && providerId && selectedTimezone && $modal.find('#edit_start_time option').length <= 1) {
                loadAvailableSlotsForClient($modal, providerId, currentDate, selectedTimezone);
            }
        }).fail(function() {
            $container.html('<input type="text" class="nelx-input" value="Error loading timezones" disabled>');
        });
    }
    
    /* Load available slots for provider (no conversion) */
    function loadAvailableSlotsForProvider($modal, providerId, date, selectedStartTime, selectedEndTime) {
        var $startSelect = $modal.find('#edit_start_time');
        var $endSelect = $modal.find('#edit_end_time');
        
        $startSelect.html('<option value="">Loading...</option>');
        $endSelect.html('<option value="">Loading...</option>');
        
        fetchAvailableSlots(providerId, date).done(function(slotsResponse) {
            $startSelect.empty();
            $endSelect.empty();
            
            var slots = slotsResponse.slots || [];
            
            if (slots.length > 0) {
                slots.forEach(function(slot) {
                    var selected = (slot.start === selectedStartTime) ? ' selected' : '';
                    $startSelect.append('<option value="' + slot.start + '" data-end="' + slot.end + '" data-start-ts="' + slot.start_ts + '" data-end-ts="' + slot.end_ts + '"' + selected + '>' + slot.start + '</option>');
                });
                
                var initialEnd = $startSelect.find('option:selected').data('end') || selectedEndTime;
                if (initialEnd) {
                    $endSelect.append('<option value="' + initialEnd + '" selected>' + initialEnd + '</option>');
                }
                
                $startSelect.off('change').on('change', function() {
                    var endTime = $(this).find('option:selected').data('end');
                    $endSelect.html('<option value="' + endTime + '">' + endTime + '</option>');
                });
            } else {
                $startSelect.append('<option value="">No available slots</option>');
                $endSelect.append('<option value="">No available slots</option>');
            }
            
        }).fail(function(xhr) {
            console.error('Failed to fetch available slots:', xhr.responseText);
            $startSelect.html('<option value="">Error loading slots</option>');
            $endSelect.html('<option value="">Error loading slots</option>');
            
        });
    }
    
    /* Load available slots for client (with timezone conversion) */
    function loadAvailableSlotsForClient($modal, providerId, date, timezone) {
        var $startSelect = $modal.find('#edit_start_time');
        var $endSelect = $modal.find('#edit_end_time');
        
        $startSelect.html('<option value="">Loading...</option>');
        $endSelect.html('<option value="">Loading...</option>');
        
        // If no timezone selected or timezone is empty string, show error
        if (!timezone || timezone === '') {
            $startSelect.html('<option value="">Please select a timezone first</option>');
            $endSelect.html('<option value="">Please select a timezone first</option>');
            return;
        }
        
        // Convert slots to client timezone
        fetchAvailableSlotsInTimezone(providerId, date, timezone).done(function(response) {
            $startSelect.empty();
            $endSelect.empty();
            
            var slots = response.slots || [];
            
            if (slots.length > 0) {
                slots.forEach(function(slot) {
                    // Store provider-local timestamps for backend
                    $startSelect.append('<option value="' + slot.start + '" data-end="' + slot.end + '" data-start-ts="' + slot.start_ts + '" data-end-ts="' + slot.end_ts + '">' + slot.start + '</option>');
                });
                
                var initialEnd = $startSelect.find('option:selected').data('end');
                if (initialEnd) {
                    $endSelect.append('<option value="' + initialEnd + '">' + initialEnd + '</option>');
                }
                
                $startSelect.off('change').on('change', function() {
                    var endTime = $(this).find('option:selected').data('end');
                    $endSelect.html('<option value="' + endTime + '">' + endTime + '</option>');
                });
            } else {
                $startSelect.append('<option value="">No available slots for this date and timezone</option>');
                $endSelect.append('<option value="">No available slots</option>');
            }
            
        }).fail(function(xhr) {
            console.error('Failed to fetch available slots in timezone:', xhr.responseText);
            $startSelect.html('<option value="">Error loading slots</option>');
            $endSelect.html('<option value="">Error loading slots</option>');
            
        });
    }
    
    /* attach pickers for edit modals - no date blocking, but highlight existing days off */
    function attachPickersForEdit($ctx) {
        $ctx = $ctx || $(document);
        if (window.flatpickr) {
            var today = new Date(NELXJAF.today);
            today.setHours(0, 0, 0, 0);
            var maxDate = new Date('5030-09-01');
            
            // Get days off for highlighting
            var daysOffList = NELXJAF.days_off || [];
            
            // Function to check if a date is a day off (for highlighting only)
            function isDayOff(date) {
                for (var i = 0; i < daysOffList.length; i++) {
                    var startDate = new Date(daysOffList[i].start + 'T00:00:00Z');
                    var endDate = new Date(daysOffList[i].end + 'T00:00:00Z');
                    startDate.setHours(0, 0, 0, 0);
                    endDate.setHours(23, 59, 59, 999);
                    
                    if (date >= startDate && date <= endDate) {
                        return true;
                    }
                }
                return false;
            }
            
            $ctx.find('input.nelx-date').each(function() {
                if (!this._flatpickr) {
                    var config = {
                        dateFormat: 'Y-m-d',
                        defaultDate: this.value || undefined,
                        minDate: today,
                        maxDate: maxDate,
                        yearSelectorType: 'dropdown'
                        // NO disable array - all dates are selectable
                    };
                    
                    // Add the onDayCreate event to highlight days off
                    config.onDayCreate = function(dObj, dStr, fp, dayElem) {
                        var date = dayElem.dateObj;
                        if (date && isDayOff(date)) {
                            // Add a light blue background and border
                            dayElem.style.backgroundColor = '#e3f2fd';
                            dayElem.style.borderRadius = '50px';
                            dayElem.style.border = '1px solid #90caf9';
                            dayElem.style.color = '#0d47a1';
                            dayElem.style.fontWeight = 'bold';
                        }
                    };
                    
                    flatpickr(this, config);
                }
            });
        }
    }

    /* schedule editor wiring */
    $(function() {
        
        ensureAllTimeDropdownSkeletons();
        observeTimeDropdowns();
        
        var $editor = $('.nelx-schedule-editor');
        if ($editor.length) {
            attachPickers($editor);
            
            if ($editor.find('.nelx-sched-body').is(':visible')) {
                setTimeout(function() {
                    initTimeDropdowns($editor);
                }, 0);
            }
            
            $editor.on('change', '#nelx_use_custom_schedule', function() {
                var isChecked = $(this).is(':checked');
                $editor.find('.nelx-sched-body').toggle(isChecked);
                $editor.find('.nelx-save-top').toggle(isChecked);
                
                if (isChecked) {
                    ensureAllTimeDropdownSkeletons($editor);
                    observeTimeDropdowns($editor);
                    setTimeout(function() {
                        initTimeDropdowns($editor);
                    }, 0);
                }
            });
    
            $editor.on('click', '.nelx-add-slot', function() {
                var $day = $(this).closest('.nelx-day');
                var $slotsContainer = $day.find('.nelx-slots');
                var $existingSlots = $slotsContainer.find('.nelx-slot-row');
                var isFirstSlot = ($existingSlots.length === 0);
                var $lastSlot = $existingSlots.last();
                var prevEnd = $lastSlot.length ? ($lastSlot.find('select').last().val() || '18:00') : null;
                
                var $newRow = daySlotRow(null, null, prevEnd, isFirstSlot);
                $slotsContainer.append($newRow);
                
                $slotsContainer.find('.nelx-empty-slots-message').remove();
                $slotsContainer.attr('data-has-slots', 'true');
                
                ensureAllTimeDropdownSkeletons($newRow);
                setTimeout(function() {
                    initTimeDropdowns($newRow);
                }, 0);
                
                updateEmptySlotsMessage($day);
            });
            
            $editor.on('click', '.nelx-remove-slot', function() { 
                var $slotRow = $(this).closest('.nelx-slot-row');
                var $day = $slotRow.closest('.nelx-day');
                $slotRow.remove();
                updateEmptySlotsMessage($day);
            });

            $editor.on('click', '#nelx_add_day_off', function() {
                var $btn = $(this);
                var html = '<div class="nelx-modal-head"><h3>Days Off</h3><button class="nelx-modal-close">' + getCloseIconSVG() + '</button></div>';
                    html += '<div class="nelx-modal-body">';
                    html += '<div class="nelx-field"><label>DAY NAME</label><input class="nelx-input" id="nelx_do_name"></div>';
                    html += '<div class="nelx-info-grid">';
                    html += '<div><label>Start Date *</label><input class="nelx-input nelx-date" id="nelx_do_start"></div>';
                    html += '<div><label>End Date</label><input class="nelx-input nelx-date" id="nelx_do_end"></div>';
                    html += '</div>';
                    html += '</div><div class="nelx-modal-foot"><button class="nelx-btn nelx-primary" id="nelx_do_save">Save</button><button class="nelx-btn" data-close="1">Cancel</button></div>';
                var $m = modal(html, $btn);
                attachPickers($m);
                $m.on('click', '#nelx_do_save', function() {
                    var start = $m.find('#nelx_do_start').val();
                    var end = $m.find('#nelx_do_end').val() || start;
                    var it = {
                        name: $m.find('#nelx_do_name').val() || '',
                        start: start,
                        startTimeStamp: start ? (new Date(start).setHours(0, 0, 0, 0)).toString() : '0',
                        end: end,
                        endTimeStamp: end ? (new Date(end).setHours(23, 59, 59, 999)).toString() : start ? (new Date(start).setHours(23, 59, 59, 999)).toString() : '0',
                        type: 'days_off',
                        editIndex: ''
                    };
                    var $tag = pill(it.name, it.start + (it.end ? ' – ' + it.end : ''), it);
                    $('#nelx_days_off_list').append($tag);
                    closeModal($m);
                });
            });

            $editor.on('click', '#nelx_add_working_days', function() {
                var $btn = $(this);
                var html = '<div class="nelx-modal-head"><h3>Working Days (custom)</h3><button class="nelx-modal-close">' + getCloseIconSVG() + '</button></div>';
                    html += '<div class="nelx-modal-body">';
                    html += '<div class="nelx-field"><label>DAY NAME</label><input class="nelx-input" id="nelx_wd_name"></div>';
                    html += '<div class="nelx-info-grid">';
                    html += '<div><label>Start Date *</label><input class="nelx-input nelx-date" id="nelx_wd_start"></div>';
                    html += '<div><label>End Date</label><input class="nelx-input nelx-date" id="nelx_wd_end"></div>';
                    html += '</div>';
                    html += '<div class="nelx-subtitle">Custom Schedule</div>';
                    html += '<div id="nelx_wd_slots" class="nelx-slots"></div>';
                    html += '<button class="nelx-btn nelx-outline nelx-small" id="nelx_wd_add">+ Add slot</button>';
                    html += '</div><div class="nelx-modal-foot"><button class="nelx-btn nelx-primary" id="nelx_wd_save">Save</button><button class="nelx-btn" data-close="1">Cancel</button></div>';
                var $m = modal(html, $btn);
                var $box = $m.find('#nelx_wd_slots');
                $box.append(daySlotRow('09:00', '17:00'));
                attachPickers($m);
                initTimeDropdowns($box);
                observeTimeDropdowns($m);
                $m.on('click', '#nelx_wd_add', function() {
                    var $box = $('#nelx_wd_slots');
                    var $existingSlots = $box.find('.nelx-slot-row');
                    var isFirstSlot = ($existingSlots.length === 0);
                    var $lastSlot = $existingSlots.last();
                    var prevEnd = $lastSlot.length ? ($lastSlot.find('select').last().val() || '17:00') : null;
                    
                    var $newRow = daySlotRow(null, null, prevEnd, isFirstSlot);
                    $box.append($newRow);
                    
                    initTimeDropdowns($box.find('.nelx-slot-row:last'));
                    updateEmptySlotsMessage($box.closest('.nelx-day'));
                });
                $m.on('click', '.nelx-remove-slot', function() { $(this).closest('.nelx-slot-row').remove(); });
                $m.on('click', '#nelx_wd_save', function() {
                    var slots = [];
                    $box.find('.nelx-slot-row').each(function() { 
                        var from = $(this).find('select').first().val();
                        var to = $(this).find('select').last().val();
                        if (from && to) {
                            slots.push({ from: from, to: to }); 
                        }
                    });
                    var start = $m.find('#nelx_wd_start').val();
                    var end = $m.find('#nelx_wd_end').val() || start;
                    var it = {
                        name: $m.find('#nelx_wd_name').val() || '',
                        start: start,
                        startTimeStamp: start ? (new Date(start).setHours(0, 0, 0, 0)).toString() : '0',
                        end: end,
                        endTimeStamp: end ? (new Date(end).setHours(23, 59, 59, 999)).toString() : start ? (new Date(start).setHours(23, 59, 59, 999)).toString() : '0',
                        type: 'working_days',
                        editIndex: '',
                        schedule: slots
                    };
                    var sub = it.start + (it.end ? ' – ' + it.end : '');
                    if (slots.length) sub += ' • ' + slots.map(function(s) { return s.from + '-' + s.to; }).join(', ');
                    var $tag = pill(it.name, sub, it);
                    $('#nelx_working_days_list').append($tag);
                    closeModal($m);
                });
            });

            $editor.on('click', '.nelx-edit-day', function(e) {
                e.stopPropagation();
                var $btn = $(this);
                var $tag = $btn.closest('.nelx-tag');
                var data = $tag.attr('data-item') ? JSON.parse($tag.attr('data-item')) : null;
                if (!data) return;
            
                var isWorking = $tag.closest('#nelx_working_days_list').length > 0;
                var title = isWorking ? 'Edit Working Day' : 'Edit Day Off';
                var html = '<div class="nelx-modal-head"><h3>' + title + '</h3><button class="nelx-modal-close">' + getCloseIconSVG() + '</button></div>';
                    html += '<div class="nelx-modal-body">';
                    html += '<div class="nelx-field"><label>DAY NAME</label><input class="nelx-input" id="edit_name" value="' + (data.name || '') + '"></div>';
                    html += '<div class="nelx-info-grid">';
                    html += '<div><label>Start Date *</label><input class="nelx-input nelx-date" id="edit_start" value="' + (data.start || '') + '"></div>';
                    html += '<div><label>End Date</label><input class="nelx-input nelx-date" id="edit_end" value="' + (data.end || '') + '"></div>';
                    html += '</div>';
                    if (isWorking) {
                        html += '<div class="nelx-subtitle">Custom Schedule</div><div id="edit_slots" class="nelx-slots"></div><button class="nelx-btn nelx-outline nelx-small" id="edit_add_slot">+ Add slot</button>';
                    }
                    html += '</div><div class="nelx-modal-foot"><button class="nelx-btn nelx-primary" id="edit_save">Save</button><button class="nelx-btn" data-close="1">Cancel</button></div>';
                var $m = modal(html, $btn);
                $m.data('editingTag', $tag);
                attachPickersForEdit($m);
                observeTimeDropdowns($m);
            
                if (isWorking && Array.isArray(data.schedule)) {
                    var $box = $m.find('#edit_slots');
                    data.schedule.forEach(function(s) {
                        $box.append(daySlotRow(s.from || '09:00', s.to || '17:00'));
                    });
                    initTimeDropdowns($box);
                    $m.on('click', '#edit_add_slot', function() {
                        var $box = $('#edit_slots');
                        var $existingSlots = $box.find('.nelx-slot-row');
                        var isFirstSlot = ($existingSlots.length === 0);
                        var $lastSlot = $existingSlots.last();
                        var prevEnd = $lastSlot.length ? ($lastSlot.find('select').last().val() || '17:00') : null;
                        
                        var $newRow = daySlotRow(null, null, prevEnd, isFirstSlot);
                        $box.append($newRow);
                        
                        initTimeDropdowns($box.find('.nelx-slot-row:last'));
                        updateEmptySlotsMessage($box.closest('.nelx-day'));
                    });
                    $m.on('click', '.nelx-remove-slot', function() { $(this).closest('.nelx-slot-row').remove(); });
                }
            
                $m.on('click', '#edit_save', function() {
                    var start = $m.find('#edit_start').val();
                    var end = $m.find('#edit_end').val() || start;
                    var newObj = {
                        name: $m.find('#edit_name').val() || '',
                        start: start,
                        startTimeStamp: start ? (new Date(start).setHours(0, 0, 0, 0)).toString() : data.startTimeStamp || '0',
                        end: end,
                        endTimeStamp: end ? (new Date(end).setHours(23, 59, 59, 999)).toString() : start ? (new Date(start).setHours(23, 59, 59, 999)).toString() : data.endTimeStamp || '0'
                    };
                    if (isWorking) {
                        newObj.schedule = [];
                        $m.find('#edit_slots .nelx-slot-row').each(function() {
                            var from = $(this).find('select').first().val();
                            var to = $(this).find('select').last().val();
                            if (from && to) {
                                newObj.schedule.push({ from: from, to: to });
                            }
                        });
                        newObj.type = 'working_days';
                        newObj.editIndex = '';
                    } else {
                        newObj.type = 'days_off';
                        newObj.editIndex = '';
                    }
                    var sub = newObj.start + (newObj.end ? ' – ' + newObj.end : '');
                    if (isWorking && newObj.schedule && newObj.schedule.length) {
                        sub += ' • ' + newObj.schedule.map(function(s) { return s.from + '-' + s.to; }).join(', ');
                    }
                    var $newTag = pill(newObj.name, sub, newObj);
                    $tag.replaceWith($newTag);
                    closeModal($m);
                });
            });

            $editor.on('click', '.nelx-tag .nelx-remove-tag, .nelx-tag .x', function(e) {
                e.stopPropagation();
                $(this).closest('.nelx-tag').remove();
            });
            $editor.on('click', '.nelx-tag .nelx-edit-day, .nelx-tag .x', function(e) {
                $(this).css('background', 'transparent');
                $(this).hover(function() { $(this).find('svg, .x').css('color', '#444'); }, function() { $(this).find('svg, .x').css('color', '#666'); });
            });

            $editor.on('click', '.nelx-save-top, .nelx-save-bottom', function() {
                var $btn = $(this);
                showButtonSpinner($btn);
            
                var payload = {
                    use_custom_schedule: $('#nelx_use_custom_schedule').is(':checked'),
                    custom_schedule: {
                        default_slot: hhmmToSeconds($('#nelx_default_slot').find('select').val()),
                        buffer_before: hhmmToSeconds($('#nelx_buffer_before').find('select').val()),
                        buffer_after: hhmmToSeconds($('#nelx_buffer_after').find('select').val()),
                        locked_time: hhmmToSeconds($('#nelx_locked_time').find('select').val()),
                        appointments_range: {
                            type: $('input[name="nelx_appt_range_type"]:checked').val() || 'all',
                            range_num: $('#nelx_range_num').val() || '60',
                            range_unit: $('#nelx_range_unit').val() || 'days'
                        },
                        working_hours: {},
                        days_off: [],
                        working_days: [],
                        working_days_mode: $('#nelx_working_days_mode').val() || 'default'
                    }
                };
            
                $editor.find('.nelx-day').each(function() {
                    var day = $(this).data('day');
                    var slots = [];
                    $(this).find('.nelx-slot-row').each(function() {
                        var from = $(this).find('select').first().val();
                        var to = $(this).find('select').last().val();
                        if (from && to) {
                            slots.push({ from: from, to: to });
                        }
                    });
                    payload.custom_schedule.working_hours[day] = slots;
                });
            
                $('#nelx_days_off_list .nelx-tag').each(function() {
                    var it = $(this).attr('data-item') ? JSON.parse($(this).attr('data-item')) : null;
                    if (it) payload.custom_schedule.days_off.push(it);
                });
            
                $('#nelx_working_days_list .nelx-tag').each(function() {
                    var it = $(this).attr('data-item') ? JSON.parse($(this).attr('data-item')) : null;
                    if (it) payload.custom_schedule.working_days.push(it);
                });
            
                $.ajax({
                    url: NELXJAF.root + 'schedule',
                    method: 'POST',
                    data: JSON.stringify(payload),
                    contentType: 'application/json',
                    beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', NELXJAF.nonce); }
                }).done(function() {
                    showAlert(NELXJAF.i18n.saved, 'ok');
                }).fail(function(xhr) {
                    showAlert(NELXJAF.i18n.error || 'Error saving', 'error');
                }).always(function() {
                    hideButtonSpinner($btn);
                    $('body').removeClass('nelx-modal-open').css({
                        'overflow': '',
                        'position': '',
                        'height': ''
                    });
                    $('#nelx-modal-root').empty();
                });
            });
            
            setTimeout(function() {
                initTimeDropdowns($editor);
            }, 500);
        }
        
        /* provider client action buttons */
        var appointmentIds = [];
        
        $('.nelx-actions-inline, .nelx-client-actions-inline').each(function() {
            var $wrap = $(this);
            var id = detectAppointmentId($wrap);
            if (!id && $wrap.attr('data-need-id') === '1') {
                $wrap.hide().after('<div class="nelx-notice">' + NELXJAF.i18n.no_id + '</div>');
                return;
            }
        
            $wrap.data('appointment-id', id);
            var status = $wrap.data('status') || 'pending';
            var isPast = $wrap.data('is-past') === '1';
            
            updateButtonStates($wrap, status, isPast, true);
            
            if (id) appointmentIds.push(id);
        });
        
        if (appointmentIds.length > 0) {
            setButtonsLoadingState($('.nelx-actions-inline, .nelx-client-actions-inline'), true);
            
            fetchBatchAppointments(appointmentIds).done(function(data) {
                $('.nelx-actions-inline, .nelx-client-actions-inline').each(function() {
                    var $wrap = $(this);
                    var id = $wrap.data('appointment-id');
                    if (data[id]) {
                        var status = data[id].status || 'pending';
                        var isPast = data[id].is_past || false;
                        var reschedulingAllowed = data[id].rescheduling_allowed || false;
                        
                        $wrap.data('slot', data[id].slot);
                        $wrap.data('rescheduling-allowed', reschedulingAllowed);
                        $wrap.data('status', status);
                        $wrap.data('is-past', isPast);
                        
                        updateButtonStates($wrap, status, isPast, reschedulingAllowed);
                    }
                });
            }).fail(function(xhr) {
                console.error('Batch fetch failed:', xhr.responseText);
            }).always(function() {
                setButtonsLoadingState($('.nelx-actions-inline, .nelx-client-actions-inline'), false);
            });
        }

        /* Public bridge for native appointment listings. Reuses the same legacy state logic. */
    window.NELXJAF_refreshActionButtons = function($scope) {
        var $wraps = ($scope && $scope.jquery) ? $scope : $($scope || '.nelx-actions-inline, .nelx-client-actions-inline');
        if (!$wraps.length) return;
        var appointmentIds = [];
        $wraps.each(function() {
            var $wrap = $(this);
            var id = detectAppointmentId($wrap);
            if (!id) return;
            $wrap.data('appointment-id', id);
            if (appointmentIds.indexOf(id) === -1) appointmentIds.push(id);
        });
        if (!appointmentIds.length) return;
        setButtonsLoadingState($wraps, true);
        fetchBatchAppointments(appointmentIds).done(function(data) {
            $wraps.each(function() {
                var $wrap = $(this), id = $wrap.data('appointment-id'), state = data[id];
                if (!state) return;
                var status = state.status || 'pending';
                var isPast = !!state.is_past;
                var allowed = !!state.rescheduling_allowed;
                $wrap.data('slot', state.slot).data('rescheduling-allowed', allowed).data('status', status).data('is-past', isPast ? '1' : '0');
                $wrap.attr('data-status', status).attr('data-is-past', isPast ? '1' : '0');
                updateButtonStates($wrap, status, isPast, allowed);
            });
        }).always(function() {
            setButtonsLoadingState($wraps, false);
        });
    };

    /* Reschedule modal */
        $(document).on('click', '.nelx-actions-inline .nelx-edit, .nelx-client-actions-inline .nelx-edit', function() {
            if ($(this).is(':disabled')) return;
        
            var $btn = $(this);
            var $wrap = $btn.closest('.nelx-actions-inline, .nelx-client-actions-inline');
            var id = detectAppointmentId($wrap);
            var isProvider = $wrap.closest('.nelx-actions-inline').length > 0;
            
            var $m = modal('<div class="nelx-modal-head"><h3>Reschedule Appointment</h3><button class="nelx-modal-close">' + getCloseIconSVG() + '</button></div><div class="nelx-modal-body"></div><div class="nelx-modal-foot"><button class="nelx-btn nelx-primary" id="edit_save">Save</button><button class="nelx-btn" data-close="1">Cancel</button></div>');
            
            // Show per-field skeletons
            var skeletonHtml = '<div class="nelx-info-grid">';
            skeletonHtml += '<div><label><div class="nelx-skeleton-line" style="height: 20px; width: 100px; margin-bottom: 8px;"></div></label><div class="nelx-skeleton-line" style="height: 40px; width: 100%;"></div></div>';
            skeletonHtml += '<div><label><div class="nelx-skeleton-line" style="height: 20px; width: 100px; margin-bottom: 8px;"></div></label><div class="nelx-skeleton-line" style="height: 40px; width: 100%;"></div></div>';
            skeletonHtml += '<div><label><div class="nelx-skeleton-line" style="height: 20px; width: 100px; margin-bottom: 8px;"></div></label><div class="nelx-skeleton-line" style="height: 40px; width: 100%;"></div></div>';
            skeletonHtml += '<div><label><div class="nelx-skeleton-line" style="height: 20px; width: 100px; margin-bottom: 8px;"></div></label><div class="nelx-skeleton-line" style="height: 40px; width: 100%;"></div></div>';
            skeletonHtml += '</div>';
            $m.find('.nelx-modal-body').html(skeletonHtml);
            
            fetchAppointmentWithTimezone(id, isProvider).done(function(data) {
                // Store provider ID in a data attribute on the modal that will persist
                $m.data('provider-id', data.appt.provider);
                $m.data('appointment-id', id);
                
                // Also store the appointment data for reference
                $m.data('appointment-data', data);
                
                // Build the form with 2x2 grid
                var formHtml = '<div class="nelx-info-grid">';
                
                // Timezone field
                formHtml += '<div><label>' + (isProvider ? 'Client Timezone' : 'Your Timezone') + '</label>';
                formHtml += '<div id="timezone_container" data-provider-id="' + data.appt.provider + '"></div>';
                // Only show note for provider (once)
                if (isProvider && data.client_info && data.client_info.timezone) {
                    formHtml += '<p class="nelx-note" style="font-size: 12px; margin-top: 5px;">The client will see the rescheduled appointment in their local time</p>';
                }
                formHtml += '</div>';
                
                // Date field - get the date from the appointment (provider local time)
                var startDate = new Date(data.display.start);
                var formattedDate = startDate.toISOString().split('T')[0];
                formHtml += '<div><label>Date</label><input class="nelx-input nelx-date" id="edit_date" value="' + formattedDate + '"></div>';
                
                // Start Time field
                formHtml += '<div><label>Start Time</label><select class="nelx-input" id="edit_start_time"></select></div>';
                
                // End Time field
                formHtml += '<div><label>End Time</label><select class="nelx-input" id="edit_end_time"></select></div>';
                
                formHtml += '</div>';
                
                $m.find('.nelx-modal-body').html(formHtml);
                $('#edit_save').prop('disabled', false);
                
                // Initialize date picker
                if (window.flatpickr) {
                    var $dateInput = $m.find('#edit_date');
                    var today = new Date(NELXJAF.today);
                    today.setHours(0, 0, 0, 0);
                    var maxDate = new Date('5030-09-01');
                    var providerId = data.appt.provider;
                    
                    // Store current working hours and days off for the current month
                    var currentWorkingHours = NELXJAF.provider_schedule || NELXJAF.global_working_hours;
                    var currentDaysOff = NELXJAF.days_off || [];
                    var currentWorkingDays = [];
                    var currentWorkingDaysMode = 'default';
                    
                    // Function to check if a date has any working hours
                    function dateHasWorkingHours(date) {
                        var weekday = date.toLocaleDateString('en-US', { weekday: 'long' }).toLowerCase();
                        
                        // First check custom working days
                        if (currentWorkingDaysMode !== 'default' && currentWorkingDays.length > 0) {
                            for (var i = 0; i < currentWorkingDays.length; i++) {
                                var wd = currentWorkingDays[i];
                                var startDate = wd.startTimeStamp ? new Date(parseInt(wd.startTimeStamp)) : (wd.start ? new Date(wd.start + 'T00:00:00Z') : null);
                                var endDate = wd.endTimeStamp ? new Date(parseInt(wd.endTimeStamp)) : (wd.end ? new Date(wd.end + 'T00:00:00Z') : null);
                                
                                if (startDate && endDate) {
                                    startDate.setHours(0, 0, 0, 0);
                                    endDate.setHours(23, 59, 59, 999);
                                    if (date >= startDate && date <= endDate) {
                                        if (wd.schedule && wd.schedule.length > 0) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                } else if (startDate) {
                                    startDate.setHours(0, 0, 0, 0);
                                    if (date.toDateString() === startDate.toDateString()) {
                                        if (wd.schedule && wd.schedule.length > 0) {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                }
                            }
                            
                            if (currentWorkingDaysMode === 'override_full') {
                                return false;
                            }
                        }
                        
                        // Check days off - use proper Date comparison
                        for (var i = 0; i < currentDaysOff.length; i++) {
                            var startDate = new Date(currentDaysOff[i].start + 'T00:00:00Z');
                            var endDate = new Date(currentDaysOff[i].end + 'T00:00:00Z');
                            startDate.setHours(0, 0, 0, 0);
                            endDate.setHours(23, 59, 59, 999);
                            
                            if (date >= startDate && date <= endDate) {
                                return false;
                            }
                        }
                        
                        // Fall back to weekly working hours
                        if (currentWorkingHours[weekday] && currentWorkingHours[weekday].length > 0) {
                            return true;
                        }
                        
                        return false;
                    }
                    
                    // Function to fetch schedule for a specific month
                    function fetchScheduleForMonth(year, month, callback) {
                        $.ajax({
                            url: NELXJAF.root + 'provider/' + providerId + '/working-days',
                            method: 'GET',
                            beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', NELXJAF.nonce); }
                        }).done(function(scheduleData) {
                            currentWorkingDaysMode = scheduleData.working_days_mode || 'default';
                            currentWorkingDays = scheduleData.working_days || [];
                            currentWorkingHours = scheduleData.working_hours || NELXJAF.global_working_hours;
                            
                            // Format days off
                            currentDaysOff = [];
                            var daysOff = scheduleData.days_off || [];
                            daysOff.forEach(function(dayOff) {
                                if (dayOff.startTimeStamp && dayOff.endTimeStamp) {
                                    var startDate = new Date(parseInt(dayOff.startTimeStamp));
                                    var endDate = new Date(parseInt(dayOff.endTimeStamp));
                                    var startStr = startDate.toISOString().split('T')[0];
                                    var endStr = endDate.toISOString().split('T')[0];
                                    currentDaysOff.push({ start: startStr, end: endStr });
                                } else if (dayOff.start && dayOff.end) {
                                    currentDaysOff.push({ start: dayOff.start, end: dayOff.end });
                                }
                            });
                            
                            if (callback) callback();
                        }).fail(function() {
                            if (callback) callback();
                        });
                    }
                    
                    var fp = flatpickr($dateInput[0], { 
                        dateFormat: 'Y-m-d',
                        defaultDate: formattedDate,
                        minDate: today,
                        maxDate: maxDate,
                        yearSelectorType: 'dropdown',
                        disable: [
                            function(date) {
                                if (date < today) return true;
                                return !dateHasWorkingHours(date);
                            }
                        ],
                        onMonthChange: function(selectedDates, dateStr, instance) {
                            // Fetch schedule for the new month and refresh
                            var year = instance.currentYear;
                            var month = instance.currentMonth + 1;
                            fetchScheduleForMonth(year, month, function() {
                                instance.redraw();
                            });
                        },
                        onChange: function(selectedDates, dateStr) {
                            if (dateStr) {
                                $m.find('#timezone_container').attr('data-date', dateStr);
                                if (isProvider) {
                                    loadAvailableSlotsForProvider($m, providerId, dateStr, null, null);
                                } else {
                                    var timezone = $m.find('#timezone_container select').val() || '';
                                    loadAvailableSlotsForClient($m, providerId, dateStr, timezone);
                                }
                            }
                        }
                    });
                    
                    // Initial fetch for the current month
                    var currentYear = fp.currentYear;
                    var currentMonth = fp.currentMonth + 1;
                    fetchScheduleForMonth(currentYear, currentMonth, function() {
                        fp.redraw();
                    });
                }
                
                // Load timezone dropdown
                loadTimezoneDropdown($m.find('#timezone_container'), data.appt.provider, (data.client_info && data.client_info.timezone) ? data.client_info.timezone : '', isProvider);
                
                // Load initial slots
                if (isProvider) {
                    loadAvailableSlotsForProvider($m, data.appt.provider, formattedDate, null, null);
                } else {
                    var initialTimezone = (data.client_info && data.client_info.timezone) ? data.client_info.timezone : '';
                    loadAvailableSlotsForClient($m, data.appt.provider, formattedDate, initialTimezone);
                }
                
                // Save handler
                $m.on('click', '#edit_save', function() {
                    var $saveBtn = $(this);
                    showButtonSpinner($saveBtn);
                    
                    var selectedDate = $m.find('#edit_date').val();
                    var selectedStart = $m.find('#edit_start_time').val();
                    var selectedEnd = $m.find('#edit_end_time').val();
                    var selectedTimezone = !isProvider ? $m.find('#timezone_container select').val() : '';
                    
                    if (!selectedDate || !selectedStart || !selectedEnd) {
                        showAlert('Please select date and times', 'error');
                        hideButtonSpinner($saveBtn);
                        return;
                    }
                    
                    var payload = {
                        id: id
                    };
                    
                    if (isProvider) {
                        // Provider rescheduling - send date and time separately
                        payload.provider_date = selectedDate;
                        payload.provider_start_time = selectedStart;
                        payload.provider_end_time = selectedEnd;
                        
                    } else {
                        // Client rescheduling - send client-local date/time without UTC conversion
                        // Format the date as displayed (no UTC conversion)
                        var clientDateDisplay = new Date(selectedDate).toLocaleDateString(undefined, {
                            month: 'long',
                            day: 'numeric',
                            year: 'numeric'
                        });
                        
                        payload.provider_date = selectedDate;
                        payload.provider_start_time = selectedStart;
                        payload.provider_end_time = selectedEnd;
                        payload.client_timezone = selectedTimezone;
                        payload.client_local_date = clientDateDisplay;
                        payload.client_local_time = selectedStart + '-' + selectedEnd;
                    }
                    
                    $.ajax({
                        url: NELXJAF.root + 'appointments/reschedule-with-timezone',
                        method: 'POST',
                        data: JSON.stringify(payload),
                        contentType: 'application/json',
                        beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', NELXJAF.nonce); }
                    }).done(function(response) {
                        if (response.ok) {
                            showAlert('Appointment rescheduled successfully', 'ok');
                            closeModal($m);
                            setTimeout(function() {
                                window.location.reload();
                            }, 2000);
                        } else {
                            showAlert('Error rescheduling appointment', 'error');
                            hideButtonSpinner($saveBtn);
                        }
                    }).fail(function(xhr) {
                        console.error('Reschedule failed:', xhr.responseText);
                        var errorMsg = xhr.responseJSON && xhr.responseJSON.error === 'collision' ? 'Time slot conflict. Please select another time.' : 'Error rescheduling appointment';
                        showAlert(errorMsg, 'error');
                        hideButtonSpinner($saveBtn);
                    });
                });
            }).fail(function(xhr) {
                console.error('Failed to fetch appointment info:', xhr.responseText);
                $m.find('.nelx-modal-body').html('<div class="nelx-error">Error loading appointment information</div>');
            });
        });

        /* Appointment info modal */
        function nelxEscapeHtml(value) {
            return $('<div>').text(value == null ? '' : String(value)).html();
        }

        function nelxHumanizeValue(value) {
            if (value == null || value === '') return '—';
            var text = String(value).replace(/[_-]+/g, ' ').replace(/\s+/g, ' ').trim();
            return text ? text.replace(/\b\w/g, function(letter) { return letter.toUpperCase(); }) : '—';
        }

        function nelxPaymentStatusOptions() {
            return {
                pending: 'Pending payment',
                processing: 'Processing',
                'on-hold': 'On hold',
                completed: 'Completed',
                cancelled: 'Cancelled',
                refunded: 'Refunded',
                failed: 'Failed'
            };
        }

        function nelxNormalizePaymentStatus(value) {
            value = String(value || '').toLowerCase();
            var aliases = {
                pending_payment: 'pending',
                'pending-payment': 'pending',
                on_hold: 'on-hold',
                canceled: 'cancelled'
            };
            return aliases[value] || value;
        }

        function nelxRenderPaymentStatus(data, isProvider, id) {
            var status = nelxNormalizePaymentStatus(data.payment_status || (data.appt && data.appt.status) || 'pending');
            var labels = nelxPaymentStatusOptions();
            var label = data.payment_status_label || labels[status] || nelxHumanizeValue(status);

            if (!isProvider) {
                return '<span class="nelx-modal-status-readonly">' + nelxEscapeHtml(label) + '</span>';
            }

            var html = '<select class="nelx-modal-status-select" data-appointment-id="' + nelxEscapeHtml(id) + '" data-previous="' + nelxEscapeHtml(status) + '" aria-label="Appointment status">';
            if (!labels[status]) {
                html += '<option value="' + nelxEscapeHtml(status) + '" selected>' + nelxEscapeHtml(label) + '</option>';
            }
            $.each(labels, function(value, text) {
                html += '<option value="' + value + '"' + (value === status ? ' selected' : '') + '>' + nelxEscapeHtml(text) + '</option>';
            });
            html += '</select>';
            return html;
        }

        function nelxModalFieldValue(field, data, providerName, meetHtml) {
            var appt = data.appt || {};
            var meta = data.meta || {};
            var display = data.display || {};
            var clientInfo = data.client_info || {};
            switch (field) {
                case 'id': return appt.ID || appt.id || '—';
                case 'start': return display.start || '—';
                case 'end': return display.end || '—';
                case 'slot': return display.start ? String(display.start).split(/\s+/).slice(-1)[0] : '—';
                case 'slot_end': return display.end ? String(display.end).split(/\s+/).slice(-1)[0] : '—';
                case 'appointment_date': return (data.display && data.display.start) ? String(data.display.start).replace(/\s+[^\s]+$/, '') : '—';
                case 'timezone': return display.timezone || '—';
                case 'date': return display.start ? String(display.start).replace(/\s+[^\s]+$/, '') : '—';
                case 'time':
                    if (display.start && display.end) return String(display.start).split(/\s+/).slice(-1)[0] + '–' + String(display.end).split(/\s+/).slice(-1)[0];
                    return clientInfo.local_time || '—';
                case 'service': return data.service_title || '—';
                case 'client': return appt.user_name || '—';
                case 'client_email': return appt.user_email || '—';
                case 'client_phone': return appt.client_phone || meta.client_phone || '—';
                case 'provider': return providerName || '—';
                case 'google_meet': return meetHtml;
                case 'client_local_date': return clientInfo.local_date || '—';
                case 'client_local_time': return clientInfo.local_time || '—';
                case 'client_local_timezone': return clientInfo.timezone || '—';
                case 'appointment_status': return data.appointment_status_label || nelxHumanizeValue(data.appointment_status || appt.appointment_status);
                case 'staff': return data.staff_name || nelxHumanizeValue(appt.staff_id || '');
                case 'notes': return meta._notes || meta.notes || appt.notes || '—';
                default:
                    if (Object.prototype.hasOwnProperty.call(appt, field)) {
                        var raw = appt[field];
                        if (field === 'status') return data.payment_status_label || nelxHumanizeValue(raw);
                        if (field === 'service') return data.service_title || nelxHumanizeValue(raw);
                        if (field === 'provider') return providerName || '—';
                        if (field === 'google_meet_url') return raw || '—';
                        return nelxHumanizeValue(raw);
                    }
                    return '—';
            }
        }

        function nelxRenderInfoField(field, data, isProvider, providerName, meetHtml, id, customLabels) {
            var labels = {
                id: 'ID', start: 'Start', end: 'End', date: 'Date', appointment_date: 'Appointment Date', time: 'Time',
                slot: 'Slot', slot_end: 'Slot End', timezone: 'Timezone', service: 'Service', client: 'Client', staff: 'Staff',
                client_email: 'Client Email', client_phone: 'Client Phone', provider: 'Provider', google_meet: 'Google Meet',
                status: 'Payment Status', appointment_status: 'Appointment Status',
                client_local_date: 'Client Local Date', client_local_time: 'Client Local Time', client_local_timezone: 'Client Local Timezone', notes: 'Notes'
            };
            customLabels = customLabels || {};
            var customLabel = customLabels[field];
            // Ignore stale/invalid labels such as the literal string "undefined".
            if (typeof customLabel !== 'string' || !customLabel.trim() || customLabel.trim().toLowerCase() === 'undefined' || customLabel.trim().toLowerCase() === 'null') {
                customLabel = '';
            }
            var label = customLabel || labels[field] || nelxHumanizeValue(field);
            var value;
            if (field === 'status') {
                value = nelxRenderPaymentStatus(data, isProvider, id);
            } else {
                value = nelxModalFieldValue(field, data, providerName, meetHtml);
                if (field !== 'google_meet') value = nelxEscapeHtml(value);
            }
            if (field === 'notes') {
                return '<div class="nelx-full-width" style="margin-top: 15px;"><label>' + nelxEscapeHtml(label) + '</label><p>' + value + '</p></div>';
            }
            return '<div><label>' + nelxEscapeHtml(label) + '</label><span>' + value + '</span></div>';
        }

        $(document).on('change', '.nelx-modal-status-select', function() {
            var $select = $(this);
            var id = parseInt($select.data('appointment-id'), 10);
            var newStatus = nelxNormalizePaymentStatus($select.val());
            if (!id || !newStatus) return;
            if ($select.data('saving')) return;

            var previous = $select.data('previous') || '';
            $select.data('saving', true).prop('disabled', true);
            $.ajax({
                url: NELXJAF.root + 'appointments/' + id + '/payment-status',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ status: newStatus }),
                beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', NELXJAF.nonce); }
            }).done(function(response) {
                $select.data('previous', response.status || newStatus);
                showAlert(response.status_label ? 'Status updated to ' + response.status_label : 'Status updated', 'ok');
            }).fail(function(xhr) {
                if (previous) $select.val(previous);
                var message = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'Failed to update appointment status';
                showAlert(message, 'error');
            }).always(function() {
                $select.data('saving', false).prop('disabled', false);
            });
        });

        $(document).on('click', '.nelx-actions-inline .nelx-info, .nelx-client-actions-inline .nelx-info', function() {
            if ($(this).is(':disabled')) return;
            
            var $btn = $(this);
            var $wrap = $btn.closest('.nelx-actions-inline, .nelx-client-actions-inline');
            var id = detectAppointmentId($wrap);
            var isProvider = $wrap.closest('.nelx-actions-inline').length > 0;
            var modalView = isProvider ? 'staff' : 'client';
            var modalFields = (NELXJAF.appointment_info_modal && NELXJAF.appointment_info_modal[modalView]) || (isProvider ? ['start','end','timezone','service','client','client_email','client_phone','google_meet','appointment_status','client_local_date','client_local_time','client_local_timezone','notes'] : ['date','time','timezone','service','provider','google_meet','appointment_status','notes']);
            var modalLabels = (NELXJAF.appointment_info_modal_labels && NELXJAF.appointment_info_modal_labels[modalView]) || {};
            
            var $m = modal('<div class="nelx-modal-head"><h3>Appointment Information</h3><button class="nelx-modal-close">' + getCloseIconSVG() + '</button></div><div class="nelx-modal-body"><div class="nelx-info-grid"><div><label><div class="nelx-skeleton-line" style="height: 20px; width: 80px;"></div></label><div class="nelx-skeleton-line" style="height: 30px;"></div></div><div><label><div class="nelx-skeleton-line" style="height: 20px; width: 80px;"></div></label><div class="nelx-skeleton-line" style="height: 30px;"></div></div></div></div><div class="nelx-modal-foot"><button class="nelx-btn" data-close="1">Close</button></div>', $btn);
            
            $m.find('.nelx-modal-card').css({'border-radius': '12px','overflow': 'hidden'});
            $m.find('.nelx-modal-backdrop').css({'border-radius': '12px'});
            
            fetchAppointmentInfo(id).done(function(data) {
                var providerName = 'Unknown';
                $.ajax({
                    url: NELXJAF.root + 'get-provider-name',
                    method: 'POST',
                    data: JSON.stringify({ provider_id: data.appt.provider }),
                    contentType: 'application/json',
                    async: false,
                    beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', NELXJAF.nonce); }
                }).done(function(response) {
                    providerName = response.name || 'Unknown';
                }).fail(function() {
                    providerName = 'Unknown';
                });
                
                var googleMeetLink = data.meta && data.meta.google_meet_link ? data.meta.google_meet_link : '';
                var appointmentStatus = data.appointment_status || 'pending';
                var isCanceled = appointmentStatus === 'canceled';
                var meetHtml = '';
                if (googleMeetLink) {
                    if (isCanceled) {
                        meetHtml = '<a href="#" class="nelx-disabled-meet-link" style="opacity: 0.5; cursor: not-allowed; pointer-events: none; text-decoration: none; color: inherit;">Join Meeting</a>';
                    } else {
                        meetHtml = '<a href="' + nelxEscapeHtml(googleMeetLink) + '" target="_blank" rel="noopener" class="nelx-meet-link">Join Meeting</a>';
                    }
                } else {
                    meetHtml = 'Google Meet not set';
                }

                var infoHtml = '';
                var regularFields = [];
                var localFields = [];
                var hasLocalSection = false;
                $.each(modalFields, function(_, field) {
                    if ($.inArray(field, ['client_local_date', 'client_local_time', 'client_local_timezone']) !== -1) {
                        localFields.push(field);
                    } else {
                        regularFields.push(field);
                    }
                });

                infoHtml += '<div class="nelx-info-grid">';
                $.each(regularFields, function(_, field) {
                    if ($.inArray(field, ['notes']) !== -1) return;
                    infoHtml += nelxRenderInfoField(field, data, isProvider, providerName, meetHtml, id, modalLabels);
                });
                infoHtml += '</div>';

                if (localFields.length) {
                    hasLocalSection = true;
                    infoHtml += '<div class="nelx-full-width" style="margin-top: 15px;"><hr><h4 style="font-size: 16px; font-weight: bold; margin: 15px 0 10px 0;">Client Local Time</h4></div>';
                    infoHtml += '<div class="nelx-info-grid">';
                    $.each(localFields, function(_, field) {
                        infoHtml += nelxRenderInfoField(field, data, isProvider, providerName, meetHtml, id, modalLabels);
                    });
                    infoHtml += '</div>';
                }

                $.each(regularFields, function(_, field) {
                    if (field === 'notes') infoHtml += nelxRenderInfoField(field, data, isProvider, providerName, meetHtml, id, modalLabels);
                });
                
                $m.find('.nelx-modal-body').html(infoHtml);
                $m.find('.nelx-modal-card').css({'border-radius': '12px','overflow': 'hidden'});
                $m.find('.nelx-modal-backdrop').css({'border-radius': '12px'});
                
                if ($('#nelx-modal-corner-style').length === 0) {
                    $('head').append('<style id="nelx-modal-corner-style">.nelx-modal-card,.nelx-modal .nelx-modal-card{border-radius:12px!important;overflow:hidden!important}.nelx-modal-backdrop,.nelx-modal .nelx-modal-backdrop{border-radius:12px!important}</style>');
                }
            }).fail(function(xhr) { 
                console.error('Failed to fetch appointment info:', xhr.responseText);
                $m.find('.nelx-modal-body').html('<div class="nelx-error">Error loading appointment information</div>');
                $m.find('.nelx-modal-card').css({'border-radius': '12px','overflow': 'hidden'});
                $m.find('.nelx-modal-backdrop').css({'border-radius': '12px'});
            });
        });

        $(document).on('click', '.nelx-actions-inline .nelx-confirm, .nelx-actions-inline .nelx-reject', function() {
            if ($(this).is(':disabled')) return;
        
            var $btn = $(this);
            var $wrap = $btn.closest('.nelx-actions-inline');
            var id = detectAppointmentId($wrap);
            
            if ($btn.hasClass('nelx-confirm')) {
                var status = 'accepted';
                showButtonSpinner($btn);
        
                updateAppointmentStatus(id, status).done(function(response) {
                    if (response.ok) {
                        updateButtonStates($wrap, status, $wrap.data('is-past') === '1', true);
                        showAlert('Appointment ' + status, 'ok');
                    } else {
                        showAlert('Error updating appointment status', 'error');
                    }
                }).fail(function(xhr) {
                    console.error('Failed to update status for ID:', id, xhr.responseText);
                    showAlert('Error updating appointment status', 'error');
                }).always(function() {
                    hideButtonSpinner($btn);
                });
            } else {
                var status = 'canceled';
                showButtonSpinner($btn);
        
                $.ajax({
                    url: NELXJAF.root + 'appointments/' + id + '/cancel',
                    method: 'POST',
                    contentType: 'application/json',
                    beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', NELXJAF.nonce); }
                }).done(function(response) {
                    if (response.ok) {
                        updateButtonStates($wrap, status, $wrap.data('is-past') === '1', true);
                        showAlert('Appointment cancelled', 'ok');
                    } else {
                        showAlert('Error cancelling appointment', 'error');
                    }
                }).fail(function(xhr) {
                    console.error('Failed to cancel appointment for ID:', id, xhr.responseText);
                    showAlert('Error cancelling appointment', 'error');
                }).always(function() {
                    hideButtonSpinner($btn);
                });
            }
        });
        
        $(document).on('click', '.nelx-client-actions-inline .nelx-reject:not([href])', function() {
            if ($(this).is(':disabled')) return;
        
            var $btn = $(this);
            var $wrap = $btn.closest('.nelx-client-actions-inline');
            var id = detectAppointmentId($wrap);
            var status = 'canceled';
        
            showButtonSpinner($btn);
        
            $.ajax({
                url: NELXJAF.root + 'appointments/' + id + '/cancel',
                method: 'POST',
                contentType: 'application/json',
                beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', NELXJAF.nonce); }
            }).done(function(response) {
                if (response.ok) {
                    updateButtonStates($wrap, status, $wrap.data('is-past') === '1', true);
                    showAlert('Appointment cancelled', 'ok');
                } else {
                    showAlert('Error cancelling appointment', 'error');
                }
            }).fail(function(xhr) {
                console.error('Failed to cancel appointment for ID:', id, xhr.responseText);
                showAlert('Error cancelling appointment', 'error');
            }).always(function() {
                hideButtonSpinner($btn);
            });
        });
    });
})(jQuery);