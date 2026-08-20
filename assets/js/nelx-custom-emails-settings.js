jQuery(document).ready(function($) {
    'use strict';
    
    // Cache for form fields per template
    var formFieldsCache = {};
    
    // Function to get correct title for collapsible headers
    function getItemTitle($item) {
        const $nameInput = $item.find('.template-name-input');
        if ($nameInput.length) {
            return $nameInput.val() || nelx_jetappt_data.i18n.new_email_template || 'New Template';
        }
        return 'New Item';
    }

    // Function to update item title in real-time
    function bindItemTitleUpdates($item) {
        const $titleSpan = $item.find('.item-title-text');
        const $nameInput = $item.find('.template-name-input');
        if ($nameInput.length) {
            $nameInput.off('input').on('input', function() {
                $titleSpan.text($(this).val() || nelx_jetappt_data.i18n.new_email_template || 'New Template');
            });
            $titleSpan.text($nameInput.val() || nelx_jetappt_data.i18n.new_email_template || 'New Template');
        }
    }

    // Function to destroy TinyMCE editor for a textarea
    function destroyTinyMCE(textareaId) {
        if (window.tinyMCE && window.tinyMCE.get(textareaId)) {
            window.tinyMCE.get(textareaId).remove();
            return true;
        }
        return false;
    }

    // Function to initialize TinyMCE editor for a textarea
    function initTinyMCE(textareaId, content) {
        if (typeof wp !== 'undefined' && wp.editor && typeof wp.editor.initialize === 'function') {
            if (window.tinyMCE && window.tinyMCE.get(textareaId)) {
                window.tinyMCE.get(textareaId).remove();
            }
            
            var settings = {
                tinymce: {
                    wpautop: true,
                    plugins: 'charmap,colorpicker,hr,lists,media,paste,tabfocus,textcolor,fullscreen,wordpress,wpautoresize,wpeditimage,wpgallery,wplink,wptextpattern',
                    toolbar1: 'formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,unlink,wp_more,spellchecker,fullscreen,wp_adv',
                    toolbar2: 'strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help',
                    toolbar3: '',
                    toolbar4: '',
                    height: 350,
                    min_height: 350,
                    max_height: 600,
                    resize: true
                },
                quicktags: true,
                mediaButtons: false
            };
            
            wp.editor.initialize(textareaId, settings);
            
            if (content !== undefined && content !== '') {
                setTimeout(function() {
                    if (window.tinyMCE && window.tinyMCE.get(textareaId)) {
                        window.tinyMCE.get(textareaId).setContent(content);
                    }
                }, 100);
            }
            
            return true;
        }
        return false;
    }

    // Function to initialize all editors within a repeater item
    function initializeEditorsInItem($item) {
        $item.find('textarea[id^="default_email_msg_"], textarea[id^="custom_email_msg_"]').each(function() {
            var textarea = $(this);
            var id = textarea.attr('id');
            var content = textarea.val();
            
            if (id && (!window.tinyMCE || !window.tinyMCE.get(id))) {
                if (textarea.closest('.wp-editor-wrap').length) {
                    textarea.unwrap().unwrap();
                }
                initTinyMCE(id, content);
            }
        });
    }
    
    // Function to clean up editors in a cloned item before re-initializing
    function cleanupEditorsInClone($clone) {
        $clone.find('textarea[id^="default_email_msg_"], textarea[id^="custom_email_msg_"]').each(function() {
            var textarea = $(this);
            var id = textarea.attr('id');
            
            if (textarea.closest('.wp-editor-wrap').length) {
                var content = '';
                if (window.tinyMCE && window.tinyMCE.get(id)) {
                    content = window.tinyMCE.get(id).getContent();
                    window.tinyMCE.get(id).remove();
                } else {
                    content = textarea.val();
                }
                
                textarea.val(content);
                textarea.removeClass('wp-editor-area');
                textarea.show();
                
                var $wpEditorWrap = textarea.closest('.wp-editor-wrap');
                if ($wpEditorWrap.length) {
                    $wpEditorWrap.replaceWith(textarea);
                }
            }
        });
    }

    // Show button success state
    function showButtonSuccess($button, successText) {
        const originalText = $button.text();
        const originalBg = $button.css('background-color');
        const originalBorder = $button.css('border-color');
        
        $button.text(successText)
               .css({
                   'background-color': '#4CAF50',
                   'border-color': '#45a049',
                   'color': 'white'
               })
               .prop('disabled', true);
        
        setTimeout(function() {
            $button.text(originalText)
                   .css({
                       'background-color': originalBg,
                       'border-color': originalBorder,
                       'color': ''
                   })
                   .prop('disabled', false);
        }, 3000);
    }
    
    // Show button error state
    function showButtonError($button, errorText) {
        const originalText = $button.text();
        const originalBg = $button.css('background-color');
        const originalBorder = $button.css('border-color');
        
        $button.text(errorText)
               .css({
                   'background-color': '#f44336',
                   'border-color': '#d32f2f',
                   'color': 'white'
               })
               .prop('disabled', true);
        
        setTimeout(function() {
            $button.text(originalText)
                   .css({
                       'background-color': originalBg,
                       'border-color': originalBorder,
                       'color': ''
                   })
                   .prop('disabled', false);
        }, 3000);
    }

    // Build form options HTML from localized data
    function getFormOptionsHTML() {
        if (nelx_jetappt_data.form_options_html) {
            return nelx_jetappt_data.form_options_html;
        }
        var html = '<option value="">' + nelx_jetappt_data.i18n.select_form + '</option>';
        return html;
    }

    // Initialize form selectors for all templates (both default and custom)
    function initFormSelectors() {
        $('.njet-form-select-wrapper').each(function() {
            var $wrapper = $(this);
            if ($wrapper.data('initialized')) return;
            $wrapper.data('initialized', true);
            
            var $trigger = $wrapper.find('.njet-form-select-trigger');
            var $dropdown = $wrapper.find('.njet-form-select-dropdown');
            var $select = $wrapper.find('.njet-form-select');
            var $triggerText = $trigger.find('.trigger-text');
            var $searchInput = $dropdown.find('.njet-form-search');
            var $list = $dropdown.find('.njet-form-list');
            var $repeaterItem = $wrapper.closest('.nelx-jetappt-repeater-item');
            var templateType = $repeaterItem.data('template-type');
            var templateIndex = $repeaterItem.data('template-index');
            
            // Build list
            function buildList() {
                var options = $select.find('option');
                var html = '<ul class="njet-form-list-items">';
                options.each(function() {
                    var $opt = $(this);
                    var value = $opt.val();
                    var text = $opt.text();
                    if (value === '') return true;
                    html += '<li class="njet-form-item" data-value="' + value + '">';
                    html += '<span class="njet-form-name">' + text + '</span>';
                    html += '<span class="njet-form-id">ID: ' + value + '</span>';
                    html += '</li>';
                });
                html += '</ul>';
                $list.html(html);
            }
            
            buildList();
            
            // Trigger click
            $trigger.on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                $('.njet-form-select-dropdown').not($dropdown).hide();
                
                if ($dropdown.is(':visible')) {
                    $dropdown.hide();
                } else {
                    var offset = $trigger.offset();
                    var windowHeight = $(window).height();
                    var scrollTop = $(window).scrollTop();
                    var spaceBelow = windowHeight - (offset.top - scrollTop + $trigger.outerHeight());
                    
                    if (spaceBelow < 300 && offset.top - scrollTop > 300) {
                        $dropdown.css({
                            top: 'auto',
                            bottom: '100%',
                            marginTop: 0,
                            marginBottom: '5px'
                        });
                    } else {
                        $dropdown.css({
                            top: '100%',
                            bottom: 'auto',
                            marginTop: '5px',
                            marginBottom: 0
                        });
                    }
                    
                    $dropdown.show();
                    $searchInput.focus();
                }
            });
            
            // Search filter
            $searchInput.on('input', function() {
                var searchTerm = $(this).val().toLowerCase();
                $list.find('.njet-form-item').each(function() {
                    var name = $(this).find('.njet-form-name').text().toLowerCase();
                    var id = $(this).data('value').toString().toLowerCase();
                    $(this).toggle(name.includes(searchTerm) || id.includes(searchTerm));
                });
            });
            
            // Select item
            $list.on('click', '.njet-form-item', function(e) {
                e.preventDefault();
                var value = $(this).data('value');
                var name = $(this).find('.njet-form-name').text();
                
                $select.val(value).trigger('change');
                $triggerText.text(name);
                $dropdown.hide();
                $searchInput.val('');
                $list.find('.njet-form-item').show();
                
                loadFieldsForTemplate($repeaterItem, value);
            });
            
            // Check for saved form ID from data attribute (for both default and custom templates)
            var savedFormId = $repeaterItem.data('saved-form-id');
            
            if (savedFormId && savedFormId !== '0' && savedFormId !== '') {
                var $savedOption = $select.find('option[value="' + savedFormId + '"]');
                if ($savedOption.length) {
                    $select.val(savedFormId);
                    $triggerText.text($savedOption.text());
                    loadFieldsForTemplate($repeaterItem, savedFormId);
                } else {
                    var $selected = $select.find('option:selected');
                    if ($selected.length && $selected.val()) {
                        $triggerText.text($selected.text());
                        if ($repeaterItem && $selected.val()) {
                            loadFieldsForTemplate($repeaterItem, $selected.val());
                        }
                    }
                }
            } else {
                var $selected = $select.find('option:selected');
                if ($selected.length && $selected.val()) {
                    $triggerText.text($selected.text());
                    if ($repeaterItem && $selected.val()) {
                        loadFieldsForTemplate($repeaterItem, $selected.val());
                    }
                }
            }
        });
    }
    
    // Load fields for a specific template
    function loadFieldsForTemplate($repeaterItem, formId) {
        var templateId = $repeaterItem.data('template-type') + '_' + $repeaterItem.data('template-index');
        var cacheKey = templateId + '_' + formId;
        
        if (formFieldsCache[cacheKey]) {
            $repeaterItem.data('form-id', formId);
            $repeaterItem.data('fields-loaded', true);
            return;
        }
        
        $.ajax({
            url: nelx_jetappt_data.ajax_url,
            type: 'POST',
            data: {
                action: 'nelx_jetappt_load_jfb_fields',
                form_id: formId,
                nonce: nelx_jetappt_data.nonce
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    formFieldsCache[cacheKey] = response.data.fields;
                    $repeaterItem.data('form-id', formId);
                    $repeaterItem.data('fields-loaded', true);
                } else {
                    console.error('Error loading fields:', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
            }
        });
    }
    
    // Get fields for a template
    function getFieldsForTemplate($repeaterItem) {
        var templateId = $repeaterItem.data('template-type') + '_' + $repeaterItem.data('template-index');
        var formId = $repeaterItem.data('form-id');
        
        if (!formId) return null;
        
        var cacheKey = templateId + '_' + formId;
        return formFieldsCache[cacheKey] || null;
    }
    
    // Populate dropdown with fields
    function populateFieldsDropdown($dropdown, $repeaterItem, targetId) {
        var fields = getFieldsForTemplate($repeaterItem);
        var $fieldsContainer = $dropdown.find('.njet-quick-insert-fields');
        
        if (!fields || fields.length === 0) {
            $fieldsContainer.html('<div class="njet-quick-insert-empty">' + (nelx_jetappt_data.i18n.no_fields || 'No fields found. Please select a form first.') + '</div>');
            return;
        }
        
        var grouped = {};
        fields.forEach(function(field) {
            var type = field.type || 'other';
            if (!grouped[type]) grouped[type] = [];
            grouped[type].push(field);
        });
        
        var html = '<ul class="njet-quick-insert-list">';
        var typeOrder = ['text', 'email', 'textarea', 'number', 'select', 'radio', 'checkbox', 'file', 'media', 'other'];
        
        typeOrder.forEach(function(type) {
            if (grouped[type] && grouped[type].length > 0) {
                html += '<li class="njet-quick-insert-group">';
                html += '<div class="njet-quick-insert-group-title">' + type.charAt(0).toUpperCase() + type.slice(1) + '</div>';
                html += '<ul>';
                grouped[type].forEach(function(field) {
                    html += '<li class="njet-quick-insert-field" data-field-name="' + field.name + '" data-field-label="' + field.label + '" data-target-id="' + targetId + '">';
                    html += '<span class="njet-quick-insert-field-name">{' + field.name + '}</span>';
                    html += '<span class="njet-quick-insert-field-label">' + field.label + '</span>';
                    html += '</li>';
                });
                html += '</ul>';
                html += '</li>';
                delete grouped[type];
            }
        });
        
        for (var type in grouped) {
            if (grouped.hasOwnProperty(type) && grouped[type].length > 0) {
                html += '<li class="njet-quick-insert-group">';
                html += '<div class="njet-quick-insert-group-title">' + type.charAt(0).toUpperCase() + type.slice(1) + '</div>';
                html += '<ul>';
                grouped[type].forEach(function(field) {
                    html += '<li class="njet-quick-insert-field" data-field-name="' + field.name + '" data-field-label="' + field.label + '" data-target-id="' + targetId + '">';
                    html += '<span class="njet-quick-insert-field-name">{' + field.name + '}</span>';
                    html += '<span class="njet-quick-insert-field-label">' + field.label + '</span>';
                    html += '</li>';
                });
                html += '</ul>';
                html += '</li>';
            }
        }
        
        html += '</ul>';
        $fieldsContainer.html(html);
    }
    
    // Insert text into target
    function insertIntoTarget($target, text) {
        var editorId = $target.attr('id');
        if (editorId && window.tinyMCE && window.tinyMCE.get(editorId) && !window.tinyMCE.get(editorId).isHidden()) {
            var editor = window.tinyMCE.get(editorId);
            editor.execCommand('mceInsertContent', false, text);
            editor.focus();
            return true;
        }
        
        if ($target.is('input, textarea')) {
            var input = $target[0];
            var startPos = input.selectionStart || 0;
            var endPos = input.selectionEnd || 0;
            var currentValue = input.value;
            
            input.value = currentValue.substring(0, startPos) + text + currentValue.substring(endPos);
            input.selectionStart = input.selectionEnd = startPos + text.length;
            input.focus();
            $target.trigger('change');
            return true;
        }
        
        return false;
    }
    
    // Handle insert button click
    $(document).on('click', '.njet-insert-field-button', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var $button = $(this);
        var $wrapper = $button.closest('.njet-insert-field-wrapper');
        var $dropdown = $wrapper.find('.njet-quick-insert-dropdown');
        var targetId = $button.data('target-id');
        var targetClass = $button.data('target-class');
        
        // Try to find target by ID first, then by class
        var $target = null;
        if (targetId) {
            $target = $('#' + targetId);
        }
        if ((!$target || !$target.length) && targetClass) {
            $target = $('.' + targetClass);
        }
        
        if (!$target || !$target.length) {
            console.error('Target not found for ID:', targetId, 'Class:', targetClass);
            alert('Target field not found. Please refresh the page and try again.');
            return;
        }
        
        var $repeaterItem = $button.closest('.nelx-jetappt-repeater-item');
        var formId = $repeaterItem.data('form-id');
        
        if (!formId) {
            alert(nelx_jetappt_data.i18n.select_form_first || 'Please select a form for this template first');
            return;
        }
        
        // Close all other dropdowns
        $('.njet-quick-insert-dropdown').not($dropdown).hide();
        
        if ($dropdown.is(':visible')) {
            $dropdown.hide();
            return;
        }
        
        var fields = getFieldsForTemplate($repeaterItem);
        
        if (!fields || fields.length === 0) {
            alert(nelx_jetappt_data.i18n.load_fields_first || 'Please wait for fields to load or select a different form');
            return;
        }
        
        $dropdown.data('target-id', targetId);
        $dropdown.data('target-class', targetClass);
        
        populateFieldsDropdown($dropdown, $repeaterItem, targetId);
        
        // Simple positioning - just show/hide, let CSS handle positioning
        $dropdown.show();
        $dropdown.find('.njet-quick-insert-search').focus();
    });
    
    // Search filter for dropdowns
    $(document).on('input', '.njet-quick-insert-search', function() {
        var $dropdown = $(this).closest('.njet-quick-insert-dropdown');
        var searchTerm = $(this).val().toLowerCase();
        var $fields = $dropdown.find('.njet-quick-insert-field');
        
        $fields.each(function() {
            var name = $(this).data('field-name').toLowerCase();
            var label = $(this).data('field-label').toLowerCase();
            $(this).toggle(name.includes(searchTerm) || label.includes(searchTerm));
        });
        
        $dropdown.find('.njet-quick-insert-group').each(function() {
            var $visibleFields = $(this).find('.njet-quick-insert-field:visible');
            $(this).toggle($visibleFields.length > 0);
        });
    });
    
    // Handle field selection from dropdown
    $(document).on('click', '.njet-quick-insert-field', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var $field = $(this);
        var fieldName = $field.data('field-name');
        var $dropdown = $field.closest('.njet-quick-insert-dropdown');
        var targetId = $dropdown.data('target-id');
        var targetClass = $dropdown.data('target-class');
        
        var $target = null;
        if (targetId) {
            $target = $('#' + targetId);
        }
        if ((!$target || !$target.length) && targetClass) {
            $target = $('.' + targetClass);
        }
        
        if ($target && $target.length) {
            var insertText = '{' + fieldName + '}';
            insertIntoTarget($target, insertText);
        }
        
        $dropdown.hide();
    });
    
    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.njet-insert-field-wrapper, .njet-quick-insert-dropdown, .njet-form-select-wrapper, .njet-form-select-dropdown').length) {
            $('.njet-quick-insert-dropdown, .njet-form-select-dropdown').hide();
        }
    });
    
    // Track focus on regular inputs
    $(document).on('focus', '.nelx-jetappt-repeater-item input[type="text"]:not(.nelx-jetappt-form-id), .nelx-jetappt-repeater-item textarea', function() {
        const $repeaterItem = $(this).closest('.nelx-jetappt-repeater-item');
        $repeaterItem.find('[data-last-focused]').removeData('last-focused').removeAttr('data-last-focused');
        $(this).data('last-focused', true).attr('data-last-focused', 'true');
        $repeaterItem.data('active-editor', null);
    });
    
    // Track focus on TinyMCE editors
    if (window.tinyMCE) {
        window.tinyMCE.on('focusin', function(e) {
            const editor = e.target;
            const $editorContainer = $('#' + editor.id).closest('.nelx-jetappt-repeater-item');
            if ($editorContainer.length) {
                $editorContainer.find('[data-last-focused]').removeData('last-focused').removeAttr('data-last-focused');
                $editorContainer.data('active-editor', editor);
            }
        });
        
        $(document).on('mousedown', function(e) {
            if (!$(e.target).closest('.mce-tinymce, .wp-editor-wrap, .nelx-jetappt-repeater-item textarea, .nelx-jetappt-repeater-item input[type="text"]').length) {
                $('.nelx-jetappt-repeater-item').each(function() {
                    $(this).data('active-editor', null);
                });
            }
        });
    }
    
    // Handle placeholder insert button click
    $(document).on('click', '.njet-placeholder-insert-button', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var $button = $(this);
        var $wrapper = $button.closest('.njet-insert-field-wrapper');
        var $dropdown = $wrapper.find('.njet-placeholder-dropdown');
        var targetId = $button.data('target-id');
        var targetClass = $button.data('target-class');
        
        // Try to find target by ID first, then by class
        var $target = null;
        if (targetId) {
            $target = $('#' + targetId);
        }
        if ((!$target || !$target.length) && targetClass) {
            $target = $('.' + targetClass);
        }
        
        if (!$target || !$target.length) {
            console.error('Target not found for ID:', targetId, 'Class:', targetClass);
            alert('Target field not found. Please refresh the page and try again.');
            return;
        }
        
        // Close all other dropdowns
        $('.njet-placeholder-dropdown, .njet-quick-insert-dropdown').not($dropdown).hide();
        
        if ($dropdown.is(':visible')) {
            $dropdown.hide();
            return;
        }
        
        $dropdown.data('target-id', targetId);
        $dropdown.data('target-class', targetClass);
        
        // Check position and add class if needed
        var $trigger = $button;
        var offset = $trigger.offset();
        var windowHeight = $(window).height();
        var scrollTop = $(window).scrollTop();
        var spaceBelow = windowHeight - (offset.top - scrollTop + $trigger.outerHeight());
        
        if (spaceBelow < 300 && offset.top - scrollTop > 300) {
            $dropdown.addClass('position-top');
        } else {
            $dropdown.removeClass('position-top');
        }
        
        $dropdown.show();
        $dropdown.find('.njet-placeholder-search').focus();
    });
    
    // Search filter for placeholder dropdowns
    $(document).on('input', '.njet-placeholder-search', function() {
        var $dropdown = $(this).closest('.njet-placeholder-dropdown');
        var searchTerm = $(this).val().toLowerCase();
        var $items = $dropdown.find('.njet-placeholder-item');
        
        $items.each(function() {
            var name = $(this).find('.njet-placeholder-name').text().toLowerCase();
            var desc = $(this).find('.njet-placeholder-desc').text().toLowerCase();
            $(this).toggle(name.includes(searchTerm) || desc.includes(searchTerm));
        });
        
        $dropdown.find('.njet-placeholder-group').each(function() {
            var $visibleItems = $(this).find('.njet-placeholder-item:visible');
            $(this).toggle($visibleItems.length > 0);
        });
    });
    
    // Handle placeholder selection from dropdown
    $(document).on('click', '.njet-placeholder-item', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var $item = $(this);
        var placeholderValue = $item.data('value');
        var $dropdown = $item.closest('.njet-placeholder-dropdown');
        var targetId = $dropdown.data('target-id');
        var targetClass = $dropdown.data('target-class');
        
        var $target = null;
        if (targetId) {
            $target = $('#' + targetId);
        }
        if ((!$target || !$target.length) && targetClass) {
            $target = $('.' + targetClass);
        }
        
        if ($target && $target.length) {
            insertIntoTarget($target, placeholderValue);
        }
        
        $dropdown.hide();
    });
    
    // Close placeholder dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.njet-insert-field-wrapper, .njet-placeholder-dropdown, .njet-quick-insert-dropdown, .njet-form-select-wrapper, .njet-form-select-dropdown').length) {
            $('.njet-placeholder-dropdown, .njet-quick-insert-dropdown, .njet-form-select-dropdown').hide();
        }
    });
    
    // ADD TEMPLATE
    $(document).on('click', '.nelx-jetappt-add-template', function() {
        const repeater = $(this).closest('.nelx-jetappt-email-repeater');
        const repeaterName = repeater.data('repeater-name');
        const itemsContainer = repeater.find('.nelx-jetappt-repeater-items');
        
        const customIndex = itemsContainer.find('.nelx-jetappt-repeater-item[data-template-type="custom"]').length;
        const messageId = 'custom_email_msg_' + Date.now() + '_' + customIndex;
        const uniqueSuffix = Date.now() + '_' + customIndex;
        
        const formOptionsHTML = getFormOptionsHTML();
        
        const newItem = $(`
            <div class="nelx-jetappt-repeater-item expanded" data-template-type="custom" data-template-index="${customIndex}" data-saved-form-id="0">
                <div class="nelx-jetappt-item-header">
                    <button type="button" class="nelx-jetappt-item-toggle">
                        <span class="dashicons dashicons-arrow-up"></span>
                        <span class="item-title-text">${nelx_jetappt_data.i18n.new_email_template}</span>
                    </button>
                    <div class="nelx-jetappt-item-actions">
                        <button type="button" class="button nelx-jetappt-duplicate-item" title="${nelx_jetappt_data.i18n.duplicate}">
                            <span class="dashicons dashicons-admin-page"></span>
                        </button>
                        <button type="button" class="button nelx-jetappt-remove-item" title="${nelx_jetappt_data.i18n.remove}">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
                <div class="nelx-jetappt-item-content" style="display:block;">
                    <!-- Template Name - Full width -->
                    <div class="nelx-two-column-grid">
                        <div class="nelx-grid-full">
                            <div class="nelx-field-card">
                                <div class="nelx-field-label">
                                    <label>${nelx_jetappt_data.i18n.template_name}</label>
                                </div>
                                <div class="nelx-field-content">
                                    <input type="text" name="${repeaterName}[${customIndex}][name]" value="${nelx_jetappt_data.i18n.new_email_template}" class="regular-text template-name-input" required>
                                    <p class="description">${nelx_jetappt_data.i18n.template_name_desc}</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- JetFormBuilder Form Selection - Full width -->
                        <div class="nelx-grid-full">
                            <div class="nelx-field-card">
                                <div class="nelx-field-label">
                                    <label>${nelx_jetappt_data.i18n.jetformbuilder_form_id}</label>
                                </div>
                                <div class="nelx-field-content">
                                    <div class="njet-form-select-wrapper" style="position: relative; max-width: 450px;">
                                        <select class="njet-form-select" style="display: none;" name="${repeaterName}[${customIndex}][form_id]">
                                            ${formOptionsHTML}
                                        </select>
                                        <div class="njet-form-select-trigger">
                                            <span class="trigger-text">${nelx_jetappt_data.i18n.select_form}</span>
                                            <span class="dashicons dashicons-arrow-down"></span>
                                        </div>
                                        <div class="njet-form-select-dropdown" style="display: none;">
                                            <div class="njet-form-search-header">
                                                <input type="text" class="njet-form-search" placeholder="${nelx_jetappt_data.i18n.search_forms}" autocomplete="off">
                                            </div>
                                            <div class="njet-form-list"></div>
                                        </div>
                                    </div>
                                    <p class="description">${nelx_jetappt_data.i18n.form_id_desc}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Two-column grid for To, CC, BCC, Subject, From -->
                    <div class="nelx-two-column-grid">
                        <!-- To Field -->
                        <div class="nelx-grid-half">
                            <div class="nelx-field-card">
                                <div class="nelx-field-label">
                                    <label>${nelx_jetappt_data.i18n.to}</label>
                                </div>
                                <div class="nelx-field-content">
                                    <div class="njet-insert-field-wrapper" style="display: flex; gap: 8px; align-items: flex-start; position: relative;">
                                        <input type="text" style="width:100%;" id="custom_to_${uniqueSuffix}" name="${repeaterName}[${customIndex}][email_settings][to]" value="" class="regular-text njet-insert-field-target njet-insert-target-custom_to_${customIndex}" data-field-type="input">
                                        <button type="button" class="button njet-insert-field-button" data-target-id="custom_to_${uniqueSuffix}" data-target-class="njet-insert-target-custom_to_${customIndex}" title="${nelx_jetappt_data.i18n.insert_field}">
                                            <span class="dashicons dashicons-plus-alt"></span>
                                        </button>
                                        <div class="njet-quick-insert-dropdown" style="display: none;">
                                            <div class="njet-quick-insert-header">
                                                <input type="text" class="njet-quick-insert-search" placeholder="${nelx_jetappt_data.i18n.search_fields}" autocomplete="off">
                                            </div>
                                            <div class="njet-quick-insert-fields">
                                                <div class="njet-quick-insert-loading">${nelx_jetappt_data.i18n.select_form_first}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="description">${nelx_jetappt_data.i18n.to_desc}</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- CC Field -->
                        <div class="nelx-grid-half">
                            <div class="nelx-field-card">
                                <div class="nelx-field-label">
                                    <label>${nelx_jetappt_data.i18n.cc}</label>
                                </div>
                                <div class="nelx-field-content">
                                    <div class="njet-insert-field-wrapper" style="display: flex; gap: 8px; align-items: flex-start; position: relative;">
                                        <input type="text" style="width:100%;" id="custom_cc_${uniqueSuffix}" name="${repeaterName}[${customIndex}][email_settings][cc]" value="" class="regular-text njet-insert-field-target njet-insert-target-custom_cc_${customIndex}" data-field-type="input">
                                        <button type="button" class="button njet-insert-field-button" data-target-id="custom_cc_${uniqueSuffix}" data-target-class="njet-insert-target-custom_cc_${customIndex}" title="${nelx_jetappt_data.i18n.insert_field}">
                                            <span class="dashicons dashicons-plus-alt"></span>
                                        </button>
                                        <div class="njet-quick-insert-dropdown" style="display: none;">
                                            <div class="njet-quick-insert-header">
                                                <input type="text" class="njet-quick-insert-search" placeholder="${nelx_jetappt_data.i18n.search_fields}" autocomplete="off">
                                            </div>
                                            <div class="njet-quick-insert-fields">
                                                <div class="njet-quick-insert-loading">${nelx_jetappt_data.i18n.select_form_first}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="description">${nelx_jetappt_data.i18n.cc_desc}</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- BCC Field -->
                        <div class="nelx-grid-half">
                            <div class="nelx-field-card">
                                <div class="nelx-field-label">
                                    <label>${nelx_jetappt_data.i18n.bcc}</label>
                                </div>
                                <div class="nelx-field-content">
                                    <div class="njet-insert-field-wrapper" style="display: flex; gap: 8px; align-items: flex-start; position: relative;">
                                        <input type="text" style="width:100%;" id="custom_bcc_${uniqueSuffix}" name="${repeaterName}[${customIndex}][email_settings][bcc]" value="" class="regular-text njet-insert-field-target njet-insert-target-custom_bcc_${customIndex}" data-field-type="input">
                                        <button type="button" class="button njet-insert-field-button" data-target-id="custom_bcc_${uniqueSuffix}" data-target-class="njet-insert-target-custom_bcc_${customIndex}" title="${nelx_jetappt_data.i18n.insert_field}">
                                            <span class="dashicons dashicons-plus-alt"></span>
                                        </button>
                                        <div class="njet-quick-insert-dropdown" style="display: none;">
                                            <div class="njet-quick-insert-header">
                                                <input type="text" class="njet-quick-insert-search" placeholder="${nelx_jetappt_data.i18n.search_fields}" autocomplete="off">
                                            </div>
                                            <div class="njet-quick-insert-fields">
                                                <div class="njet-quick-insert-loading">${nelx_jetappt_data.i18n.select_form_first}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="description">${nelx_jetappt_data.i18n.bcc_desc}</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Subject Field -->
                        <div class="nelx-grid-half">
                            <div class="nelx-field-card">
                                <div class="nelx-field-label">
                                    <label>${nelx_jetappt_data.i18n.subject}</label>
                                </div>
                                <div class="nelx-field-content">
                                    <div class="njet-insert-field-wrapper" style="display: flex; gap: 8px; align-items: flex-start; position: relative;">
                                        <input type="text" style="width:100%;" id="custom_subject_${uniqueSuffix}" name="${repeaterName}[${customIndex}][email_settings][subject]" value="" class="regular-text njet-insert-field-target njet-insert-target-custom_subject_${customIndex}" data-field-type="input">
                                        <button type="button" class="button njet-insert-field-button" data-target-id="custom_subject_${uniqueSuffix}" data-target-class="njet-insert-target-custom_subject_${customIndex}" title="${nelx_jetappt_data.i18n.insert_field}">
                                            <span class="dashicons dashicons-plus-alt"></span>
                                        </button>
                                        <div class="njet-quick-insert-dropdown" style="display: none;">
                                            <div class="njet-quick-insert-header">
                                                <input type="text" class="njet-quick-insert-search" placeholder="${nelx_jetappt_data.i18n.search_fields}" autocomplete="off">
                                            </div>
                                            <div class="njet-quick-insert-fields">
                                                <div class="njet-quick-insert-loading">${nelx_jetappt_data.i18n.select_form_first}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="description">${nelx_jetappt_data.i18n.subject_desc}</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- From Field -->
                        <div class="nelx-grid-half">
                            <div class="nelx-field-card">
                                <div class="nelx-field-label">
                                    <label>${nelx_jetappt_data.i18n.from}</label>
                                </div>
                                <div class="nelx-field-content">
                                    <div class="njet-insert-field-wrapper" style="display: flex; gap: 8px; align-items: flex-start; position: relative;">
                                        <input type="text" style="width:100%;" id="custom_from_${uniqueSuffix}" name="${repeaterName}[${customIndex}][email_settings][from]" value="${nelx_jetappt_data.default_from}" class="regular-text njet-insert-field-target njet-insert-target-custom_from_${customIndex}" data-field-type="input">
                                        <button type="button" class="button njet-insert-field-button" data-target-id="custom_from_${uniqueSuffix}" data-target-class="njet-insert-target-custom_from_${customIndex}" title="${nelx_jetappt_data.i18n.insert_field}">
                                            <span class="dashicons dashicons-plus-alt"></span>
                                        </button>
                                        <div class="njet-quick-insert-dropdown" style="display: none;">
                                            <div class="njet-quick-insert-header">
                                                <input type="text" class="njet-quick-insert-search" placeholder="${nelx_jetappt_data.i18n.search_fields}" autocomplete="off">
                                            </div>
                                            <div class="njet-quick-insert-fields">
                                                <div class="njet-quick-insert-loading">${nelx_jetappt_data.i18n.select_form_first}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="description">${nelx_jetappt_data.i18n.from_desc}</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Email Hook -->
                        <div class="nelx-grid-half">
                            <div class="nelx-field-card">
                                <div class="nelx-field-label">
                                    <label>${nelx_jetappt_data.i18n.email_hook}</label>
                                </div>
                                <div class="nelx-field-content">
                                    <div class="nelx-jetappt-hook-name-container">
                                        <input type="text" class="nelx-jetappt-hook-name" value="custom_email_${customIndex}" readonly>
                                        <button type="button" class="button nelx-jetappt-copy-hook" data-hook="custom_email_${customIndex}">
                                            ${nelx_jetappt_data.i18n.copy_hook}
                                        </button>
                                    </div>
                                    <p class="description">${nelx_jetappt_data.i18n.hook_desc}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Message Field - Full Width -->
                    <div class="nelx-two-column-grid">
                        <div class="nelx-grid-full">
                            <div class="nelx-field-card">
                                <div class="nelx-field-label">
                                    <label>${nelx_jetappt_data.i18n.message}</label>
                                </div>
                                <div class="nelx-field-content">
                                    <div class="njet-insert-field-wrapper" style="position: relative;">
                                        <textarea style="width:100%;" rows="15" id="${messageId}" name="${repeaterName}[${customIndex}][email_settings][message]" class="njet-insert-field-target njet-insert-target-${messageId}" data-field-type="textarea"></textarea>
                                        <div class="njet-insert-buttons-group" style="position: absolute; top: 5px; right: 5px; z-index: 10;">
                                            <button type="button" class="button njet-insert-field-button" data-target-id="${messageId}" data-target-class="njet-insert-target-${messageId}" title="${nelx_jetappt_data.i18n.insert_field}">
                                                <span class="dashicons dashicons-plus-alt"></span>
                                            </button>
                                        </div>
                                        <div class="njet-quick-insert-dropdown" style="display: none;">
                                            <div class="njet-quick-insert-header">
                                                <input type="text" class="njet-quick-insert-search" placeholder="${nelx_jetappt_data.i18n.search_fields}" autocomplete="off">
                                            </div>
                                            <div class="njet-quick-insert-fields">
                                                <div class="njet-quick-insert-loading">${nelx_jetappt_data.i18n.select_form_first}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="description">${nelx_jetappt_data.i18n.message_desc}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `);
        
        itemsContainer.append(newItem);
        bindItemTitleUpdates(newItem);
        
        setTimeout(function() {
            initFormSelectors();
            initializeEditorsInItem(newItem);
        }, 100);
    });
    
    // Duplicate template
    $(document).on('click', '.nelx-jetappt-duplicate-item', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const $originalItem = $(this).closest('.nelx-jetappt-repeater-item');
        const repeater = $originalItem.closest('.nelx-jetappt-email-repeater');
        const itemsContainer = repeater.find('.nelx-jetappt-repeater-items');
        
        const customIndex = itemsContainer.find('.nelx-jetappt-repeater-item[data-template-type="custom"]').length;
        const repeaterName = repeater.data('repeater-name');
        const uniqueSuffix = Date.now() + '_' + customIndex;
        
        // Get original content
        let originalContent = '';
        const originalTextarea = $originalItem.find('textarea[id^="custom_email_msg_"]');
        if (originalTextarea.length) {
            const originalId = originalTextarea.attr('id');
            if (window.tinyMCE && window.tinyMCE.get(originalId)) {
                originalContent = window.tinyMCE.get(originalId).getContent();
            } else {
                originalContent = originalTextarea.val();
            }
        }
        
        const $clone = $originalItem.clone();
        const newMessageId = 'custom_email_msg_' + uniqueSuffix;
        
        // Update textarea ID
        const $newTextarea = $clone.find('textarea[id^="custom_email_msg_"]');
        if ($newTextarea.length) {
            $newTextarea.attr('id', newMessageId);
            $newTextarea.val(originalContent);
        }
        
        // Clean up editors
        cleanupEditorsInClone($clone);
        
        // Update all names
        $clone.find('[name]').each(function() {
            const name = $(this).attr('name');
            if (name) {
                const newName = name.replace(/\[\d+\]/, '[' + customIndex + ']');
                $(this).attr('name', newName);
            }
        });
        
        // Update data attributes
        $clone.attr('data-template-index', customIndex);
        $clone.attr('data-saved-form-id', '0');
        
        // Update IDs for targets with unique suffix
        $clone.find('[id^="custom_to_"], [id^="custom_cc_"], [id^="custom_bcc_"], [id^="custom_subject_"], [id^="custom_from_"]').each(function() {
            const oldId = $(this).attr('id');
            if (oldId) {
                const newId = oldId.replace(/_\d+_\d+$/, '_' + uniqueSuffix);
                $(this).attr('id', newId);
            }
        });
        
        // Update target classes
        $clone.find('[class*="njet-insert-target-"]').each(function() {
            const classes = $(this).attr('class').split(' ');
            const newClasses = classes.map(cls => {
                if (cls.indexOf('njet-insert-target-') === 0) {
                    return cls.replace(/_\d+$/, '_' + customIndex);
                }
                return cls;
            });
            $(this).attr('class', newClasses.join(' '));
        });
        
        // Update button data attributes
        $clone.find('.njet-insert-field-button').each(function() {
            const $btn = $(this);
            const targetId = $btn.data('target-id');
            if (targetId && targetId.indexOf('custom_') === 0) {
                $btn.data('target-id', targetId.replace(/_\d+_\d+$/, '_' + uniqueSuffix));
            }
            const targetClass = $btn.data('target-class');
            if (targetClass) {
                $btn.data('target-class', targetClass.replace(/_\d+$/, '_' + customIndex));
            }
        });
        
        // Update hook
        $clone.find('.nelx-jetappt-hook-name').val('custom_email_' + customIndex);
        $clone.find('.nelx-jetappt-copy-hook').data('hook', 'custom_email_' + customIndex);
        
        // Update template name
        const $nameInput = $clone.find('.template-name-input');
        const currentName = $nameInput.val();
        $nameInput.val(currentName + ' ' + (nelx_jetappt_data.i18n.copy || 'Copy'));
        
        // Reset form selection
        $clone.find('.njet-form-select').val('');
        $clone.find('.njet-form-select-trigger .trigger-text').text(nelx_jetappt_data.i18n.select_form);
        $clone.removeData('form-id');
        $clone.removeData('fields-loaded');
        
        // Remove any editor wrappers
        $clone.find('.wp-editor-wrap').each(function() {
            const $textarea = $(this).find('textarea');
            $(this).replaceWith($textarea);
        });
        
        // Ensure expanded
        $clone.addClass('expanded');
        $clone.find('.nelx-jetappt-item-content').show();
        $clone.find('.nelx-jetappt-item-toggle .dashicons')
            .removeClass('dashicons-arrow-down')
            .addClass('dashicons-arrow-up');
        
        itemsContainer.append($clone);
        bindItemTitleUpdates($clone);
        
        setTimeout(function() {
            initFormSelectors();
            initializeEditorsInItem($clone);
        }, 150);
    });
    
    // Remove template
    $(document).on('click', '.nelx-jetappt-remove-item', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const $item = $(this).closest('.nelx-jetappt-repeater-item');
        
        if ($item.find('.default-template-badge').length) {
            alert(nelx_jetappt_data.i18n.cannot_remove_default);
            return;
        }
        
        if (confirm(nelx_jetappt_data.i18n.confirm_remove)) {
            $item.find('textarea[id^="custom_email_msg_"]').each(function() {
                const id = $(this).attr('id');
                if (window.tinyMCE && window.tinyMCE.get(id)) {
                    window.tinyMCE.get(id).remove();
                }
            });
            $item.remove();
        }
    });
    
    // Toggle item content
    $(document).on('click', '.nelx-jetappt-item-toggle', function() {
        const $item = $(this).closest('.nelx-jetappt-repeater-item');
        const $content = $item.find('.nelx-jetappt-item-content');
        const $icon = $(this).find('.dashicons');
        
        if ($content.is(':visible')) {
            $content.slideUp();
            $icon.removeClass('dashicons-arrow-up').addClass('dashicons-arrow-down');
            $item.removeClass('expanded');
        } else {
            $content.slideDown();
            $icon.removeClass('dashicons-arrow-down').addClass('dashicons-arrow-up');
            $item.addClass('expanded');
            bindItemTitleUpdates($item);
            
            setTimeout(function() {
                initializeEditorsInItem($item);
            }, 200);
        }
    });
    
    // Copy hook
    $(document).on('click', '.nelx-jetappt-copy-hook', function() {
        const $button = $(this);
        const hookName = $button.data('hook') || $button.siblings('.nelx-jetappt-hook-name').val();
        
        if (!hookName) return;
        
        navigator.clipboard.writeText(hookName).then(function() {
            const originalText = $button.text();
            $button.text(nelx_jetappt_data.i18n.copied || 'Copied!');
            setTimeout(function() {
                $button.text(originalText);
            }, 2000);
        });
    });
    
    // Function to load saved custom templates from PHP data
    function loadSavedCustomTemplates() {
        // This function is called on page load
        // The PHP should render custom templates with data-saved-form-id attributes
        // initFormSelectors will handle restoring the selected values
    }
    
    // Initialize all editors on page load
    function initializeAllEditors() {
        $('.nelx-jetappt-repeater-item').each(function() {
            const $item = $(this);
            bindItemTitleUpdates($item);
            initializeEditorsInItem($item);
        });
    }
    
    // Initialize form selectors
    function initializeAll() {
        initFormSelectors();
        initializeAllEditors();
        loadSavedCustomTemplates();
    }
    
    setTimeout(function() {
        initializeAll();
    }, 200);
});