/**
 * Lilac Learning Manager - Admin Scripts
 *
 * Handles the interactive elements in the admin interface.
 */
(function($) {
    'use strict';

    // Document ready
    $(function() {
        // Initialize color pickers
        initColorPickers();
        
        // Handle tab navigation
        initTabs();
        
        // Handle form submissions
        handleFormSubmissions();
        
        // Handle bulk actions
        handleBulkActions();
        
        // Handle quick edit
        handleQuickEdit();
    });

    /**
     * Initialize color picker fields.
     */
    function initColorPickers() {
        $('.llm-color-picker').wpColorPicker({
            defaultColor: $(this).data('default-color') || '#2271b1',
            change: function(event, ui) {
                $(event.target).val(ui.color.toString());
            },
            clear: function() {
                $(this).val('');
            }
        });
    }

    /**
     * Initialize tabbed interfaces.
     */
    function initTabs() {
        $('.llm-tab').on('click', function(e) {
            e.preventDefault();
            
            var $this = $(this);
            var tabId = $this.attr('href');
            
            // Hide all tab content
            $('.llm-tab-content').removeClass('active');
            
            // Deactivate all tabs
            $('.llm-tab').removeClass('llm-tab-active');
            
            // Activate current tab
            $this.addClass('llm-tab-active');
            
            // Show current tab content
            $(tabId).addClass('active');
            
            // Update URL hash
            if (history.pushState) {
                history.pushState(null, null, tabId);
            } else {
                window.location.hash = tabId;
            }
        });
        
        // Activate tab based on URL hash
        var hash = window.location.hash;
        if (hash) {
            $('.llm-tab[href="' + hash + '"]').trigger('click');
        } else {
            // Activate first tab by default
            $('.llm-tab:first').trigger('click');
        }
    }

    /**
     * Handle form submissions with AJAX.
     */
    function handleFormSubmissions() {
        $('.llm-ajax-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitButton = $form.find('button[type="submit"]');
            var $spinner = $form.find('.llm-spinner');
            var $message = $form.find('.llm-message');
            
            // Reset message
            $message.removeClass('llm-notice-success llm-notice-error').empty().hide();
            
            // Disable submit button and show spinner
            $submitButton.prop('disabled', true);
            $spinner.addClass('visible');
            
            // Get form data
            var formData = new FormData($form[0]);
            
            // Add nonce if not already in form data
            if (!formData.has('_wpnonce')) {
                formData.append('_wpnonce', llmAdminVars.nonce);
            }
            
            // Add action
            formData.append('action', $form.data('action') || 'llm_handle_form');
            
            // Send AJAX request
            $.ajax({
                url: llmAdminVars.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json'
            })
            .done(function(response) {
                if (response.success) {
                    $message.addClass('llm-notice-success')
                           .html('<p>' + response.data.message + '</p>')
                           .show();
                    
                    // Reload the page if needed
                    if (response.data.reload) {
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    }
                    
                    // Call a callback function if provided
                    if (typeof response.data.callback === 'function') {
                        response.data.callback(response);
                    }
                } else {
                    var errorMessage = response.data && response.data.message 
                        ? response.data.message 
                        : llmAdminVars.i18n.unknownError;
                    
                    $message.addClass('llm-notice-error')
                           .html('<p>' + errorMessage + '</p>')
                           .show();
                }
            })
            .fail(function(xhr, status, error) {
                $message.addClass('llm-notice-error')
                       .html('<p>' + llmAdminVars.i18n.requestFailed + '</p>')
                       .show();
                
                console.error('AJAX Error:', status, error);
            })
            .always(function() {
                // Re-enable submit button and hide spinner
                $submitButton.prop('disabled', false);
                $spinner.removeClass('visible');
                
                // Scroll to the message
                $('html, body').animate({
                    scrollTop: $message.offset().top - 100
                }, 300);
            });
        });
    }

    /**
     * Handle bulk actions.
     */
    function handleBulkActions() {
        // Toggle checkboxes in the list table
        $('.llm-toggle-checkbox').on('change', function() {
            var $checkboxes = $('input[name^="llm_programs"]');
            
            if ($(this).is(':checked')) {
                $checkboxes.prop('checked', true);
            } else {
                $checkboxes.prop('checked', false);
            }
        });
        
        // Confirm bulk deletion
        $('select[name="action"], select[name="action2"]').on('change', function() {
            var $select = $(this);
            var action = $select.val();
            
            if (action.indexOf('delete') !== -1) {
                if (!confirm(llmAdminVars.i18n.confirmDelete)) {
                    $select.val('');
                    return false;
                }
            }
            
            return true;
        });
        
        // Handle program assignment in bulk
        $('.llm-bulk-assign-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitButton = $form.find('button[type="submit"]');
            var $spinner = $form.find('.llm-spinner');
            var $message = $form.find('.llm-message');
            var $programSelect = $form.find('select[name="program_id"]');
            
            // Reset message
            $message.removeClass('llm-notice-success llm-notice-error').empty().hide();
            
            // Validate program selection
            if (!$programSelect.val()) {
                $message.addClass('llm-notice-error')
                       .html('<p>' + llmAdminVars.i18n.selectProgram + '</p>')
                       .show();
                return false;
            }
            
            // Get selected course IDs
            var courseIds = [];
            $('input[name="post[]"]:checked').each(function() {
                courseIds.push($(this).val());
            });
            
            if (courseIds.length === 0) {
                $message.addClass('llm-notice-error')
                       .html('<p>' + llmAdminVars.i18n.selectCourses + '</p>')
                       .show();
                return false;
            }
            
            // Disable submit button and show spinner
            $submitButton.prop('disabled', true);
            $spinner.addClass('visible');
            
            // Prepare data
            var data = {
                action: 'llm_bulk_assign_programs',
                nonce: llmAdminVars.nonce,
                program_id: $programSelect.val(),
                course_ids: courseIds,
                _wp_http_referer: $form.find('input[name="_wp_http_referer"]').val()
            };
            
            // Send AJAX request
            $.post(llmAdminVars.ajaxUrl, data, function(response) {
                if (response.success) {
                    $message.addClass('llm-notice-success')
                           .html('<p>' + response.data.message + '</p>')
                           .show();
                    
                    // Reload the page after a short delay
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                } else {
                    var errorMessage = response.data && response.data.message 
                        ? response.data.message 
                        : llmAdminVars.i18n.unknownError;
                    
                    $message.addClass('llm-notice-error')
                           .html('<p>' + errorMessage + '</p>')
                           .show();
                }
            }, 'json')
            .fail(function() {
                $message.addClass('llm-notice-error')
                       .html('<p>' + llmAdminVars.i18n.requestFailed + '</p>')
                       .show();
            })
            .always(function() {
                // Re-enable submit button and hide spinner
                $submitButton.prop('disabled', false);
                $spinner.removeClass('visible');
            });
        });
    }

    /**
     * Handle quick edit functionality.
     */
    function handleQuickEdit() {
        // Handle quick edit button click
        $(document).on('click', '.editinline', function() {
            var $inlineEditRow = $('#inline-edit');
            var $editRow = $('#edit-' + inlineEditPost.id);
            var termId = inlineEditPost.id.replace('tag-', '');
            
            // Get the current values
            var color = $('td.color span.llm-color-swatch', '#tag-' + termId).attr('title');
            var featured = $('td.featured', '#tag-' + termId).text().trim() === '✓';
            var visibility = $('td.visibility', '#tag-' + termId).text().trim().toLowerCase();
            
            // Set the values in the quick edit form
            $('input[name="program_color"]', $inlineEditRow).val(color || '');
            $('input[name="program_featured"]', $inlineEditRow).prop('checked', featured);
            $('select[name="program_visibility"]', $inlineEditRow).val(visibility);
            
            // Re-initialize color picker
            $('input[name="program_color"]', $inlineEditRow).wpColorPicker('color', color || '');
        });
        
        // Handle quick edit form submission
        $(document).on('click', '#save-tag', function() {
            var termId = $('#tag_ID').val();
            var $form = $('#edittag');
            
            // Get the values from the form
            var color = $('input[name="program_color"]', $form).val();
            var featured = $('input[name="program_featured"]', $form).is(':checked') ? '1' : '0';
            var visibility = $('select[name="program_visibility"]', $form).val();
            
            // Update the term meta
            $.post(ajaxurl, {
                action: 'llm_save_program_meta',
                nonce: llmAdminVars.nonce,
                term_id: termId,
                program_color: color,
                program_featured: featured,
                program_visibility: visibility
            }, function(response) {
                if (!response.success) {
                    console.error('Failed to save program meta:', response);
                }
                
                // Reload the page to reflect changes
                window.location.reload();
            }, 'json');
        });
    }
    
    // Make functions available globally if needed
    window.llmAdmin = {
        initColorPickers: initColorPickers,
        initTabs: initTabs,
        handleFormSubmissions: handleFormSubmissions,
        handleBulkActions: handleBulkActions,
        handleQuickEdit: handleQuickEdit
    };
    
})(jQuery);
