/**
 * Lilac Learning Manager - Subscription UI
 * Handles frontend subscription activation and extension interactions
 */

(function($) {
    'use strict';

    // Main subscription handler object
    const LilacSubscriptions = {
        /**
         * Initialize the subscription UI handlers
         */
        init: function() {
            // Bind event handlers
            this.bindEvents();
            
            // Initialize any subscription forms on page load
            this.initSubscriptionForms();
        },

        /**
         * Bind event handlers to DOM elements
         */
        bindEvents: function() {
            // Subscription type selection change
            $(document).on('change', '.lilac-subscription-type-select', this.handleTypeChange);
            
            // Subscription option selection change
            $(document).on('change', '.lilac-subscription-option-select', this.handleOptionChange);
            
            // Subscription activation form submission
            $(document).on('submit', '.lilac-subscription-form', this.handleActivation);
            
            // Subscription extension form submission
            $(document).on('submit', '.lilac-subscription-extension-form', this.handleExtension);
        },

        /**
         * Initialize any subscription forms on page load
         */
        initSubscriptionForms: function() {
            $('.lilac-subscription-form').each(function() {
                const $form = $(this);
                const courseId = $form.data('course-id');
                
                // Load subscription options if course ID is available
                if (courseId) {
                    LilacSubscriptions.loadSubscriptionOptions(courseId, $form);
                }
            });
        },

        /**
         * Handle subscription type selection change
         */
        handleTypeChange: function(e) {
            const $select = $(this);
            const $form = $select.closest('form');
            const courseId = $form.data('course-id');
            const subscriptionType = $select.val();
            
            // Update options based on selected type
            LilacSubscriptions.loadTypeOptions(courseId, subscriptionType, $form);
        },

        /**
         * Handle subscription option selection change
         */
        handleOptionChange: function(e) {
            const $select = $(this);
            const $form = $select.closest('form');
            const $submitBtn = $form.find('button[type="submit"]');
            
            // Enable submit button if an option is selected
            if ($select.val()) {
                $submitBtn.prop('disabled', false);
            } else {
                $submitBtn.prop('disabled', true);
            }
        },

        /**
         * Handle subscription activation form submission
         */
        handleActivation: function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const courseId = $form.data('course-id');
            const subscriptionType = $form.find('.lilac-subscription-type-select').val();
            const optionId = $form.find('.lilac-subscription-option-select').val();
            const nonce = $form.find('input[name="lilac_subscription_nonce"]').val();
            const $statusContainer = $form.find('.lilac-subscription-status');
            
            // Validate form data
            if (!courseId || !subscriptionType || !optionId) {
                LilacSubscriptions.showMessage($statusContainer, lilac_subscription_i18n.select_option, 'error');
                return;
            }
            
            // Disable form during submission
            LilacSubscriptions.setFormLoading($form, true);
            
            // Send AJAX request
            $.ajax({
                url: lilac_subscription_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'lilac_activate_subscription',
                    course_id: courseId,
                    subscription_type: subscriptionType,
                    option_id: optionId,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        LilacSubscriptions.showMessage($statusContainer, response.data.message, 'success');
                        
                        // Update UI to show active subscription
                        LilacSubscriptions.updateSubscriptionUI($form, {
                            status: 'active',
                            expires_at: response.data.expires_at,
                            expiry_date: response.data.expiry_date
                        });
                        
                        // Trigger custom event for other scripts to hook into
                        $(document).trigger('lilac_subscription_activated', [courseId, response.data]);
                    } else {
                        // Show error message
                        LilacSubscriptions.showMessage($statusContainer, response.data.message, 'error');
                    }
                },
                error: function() {
                    // Show generic error message
                    LilacSubscriptions.showMessage($statusContainer, lilac_subscription_i18n.ajax_error, 'error');
                },
                complete: function() {
                    // Re-enable form
                    LilacSubscriptions.setFormLoading($form, false);
                }
            });
        },

        /**
         * Handle subscription extension form submission
         */
        handleExtension: function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const courseId = $form.data('course-id');
            const subscriptionType = $form.find('.lilac-subscription-type-select').val();
            const optionId = $form.find('.lilac-subscription-option-select').val();
            const nonce = $form.find('input[name="lilac_subscription_nonce"]').val();
            const $statusContainer = $form.find('.lilac-subscription-status');
            
            // Validate form data
            if (!courseId || !subscriptionType || !optionId) {
                LilacSubscriptions.showMessage($statusContainer, lilac_subscription_i18n.select_option, 'error');
                return;
            }
            
            // Disable form during submission
            LilacSubscriptions.setFormLoading($form, true);
            
            // Send AJAX request
            $.ajax({
                url: lilac_subscription_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'lilac_extend_subscription',
                    course_id: courseId,
                    subscription_type: subscriptionType,
                    option_id: optionId,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        LilacSubscriptions.showMessage($statusContainer, response.data.message, 'success');
                        
                        // Update UI to show extended subscription
                        LilacSubscriptions.updateSubscriptionUI($form, {
                            status: 'active',
                            expires_at: response.data.expires_at,
                            expiry_date: response.data.expiry_date
                        });
                        
                        // Trigger custom event for other scripts to hook into
                        $(document).trigger('lilac_subscription_extended', [courseId, response.data]);
                    } else {
                        // Show error message
                        LilacSubscriptions.showMessage($statusContainer, response.data.message, 'error');
                    }
                },
                error: function() {
                    // Show generic error message
                    LilacSubscriptions.showMessage($statusContainer, lilac_subscription_i18n.ajax_error, 'error');
                },
                complete: function() {
                    // Re-enable form
                    LilacSubscriptions.setFormLoading($form, false);
                }
            });
        },

        /**
         * Load subscription options for a course
         */
        loadSubscriptionOptions: function(courseId, $form) {
            const $statusContainer = $form.find('.lilac-subscription-status');
            const nonce = $form.find('input[name="lilac_subscription_nonce"]').val();
            
            // Disable form during loading
            LilacSubscriptions.setFormLoading($form, true);
            
            // Send AJAX request
            $.ajax({
                url: lilac_subscription_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'lilac_get_subscription_options',
                    course_id: courseId,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update subscription type select
                        const $typeSelect = $form.find('.lilac-subscription-type-select');
                        $typeSelect.val(response.data.subscription_type);
                        
                        // Update option select with available options
                        LilacSubscriptions.populateOptionSelect($form, response.data.options);
                    } else {
                        // Show error message
                        LilacSubscriptions.showMessage($statusContainer, response.data.message, 'error');
                    }
                },
                error: function() {
                    // Show generic error message
                    LilacSubscriptions.showMessage($statusContainer, lilac_subscription_i18n.ajax_error, 'error');
                },
                complete: function() {
                    // Re-enable form
                    LilacSubscriptions.setFormLoading($form, false);
                }
            });
        },

        /**
         * Load options for a specific subscription type
         */
        loadTypeOptions: function(courseId, subscriptionType, $form) {
            const $statusContainer = $form.find('.lilac-subscription-status');
            const $optionSelect = $form.find('.lilac-subscription-option-select');
            
            // Clear existing options
            $optionSelect.empty().append($('<option>').val('').text(lilac_subscription_i18n.select_option));
            
            // Disable form during loading
            LilacSubscriptions.setFormLoading($form, true);
            
            // Send AJAX request
            $.ajax({
                url: lilac_subscription_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'lilac_get_subscription_options',
                    course_id: courseId,
                    subscription_type: subscriptionType,
                    nonce: $form.find('input[name="lilac_subscription_nonce"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        // Update option select with available options
                        LilacSubscriptions.populateOptionSelect($form, response.data.options);
                    } else {
                        // Show error message
                        LilacSubscriptions.showMessage($statusContainer, response.data.message, 'error');
                    }
                },
                error: function() {
                    // Show generic error message
                    LilacSubscriptions.showMessage($statusContainer, lilac_subscription_i18n.ajax_error, 'error');
                },
                complete: function() {
                    // Re-enable form
                    LilacSubscriptions.setFormLoading($form, false);
                }
            });
        },

        /**
         * Populate option select dropdown with available options
         */
        populateOptionSelect: function($form, options) {
            const $optionSelect = $form.find('.lilac-subscription-option-select');
            
            // Clear existing options
            $optionSelect.empty().append($('<option>').val('').text(lilac_subscription_i18n.select_option));
            
            // Add new options
            $.each(options, function(id, option) {
                $optionSelect.append($('<option>').val(id).text(option.label));
            });
            
            // Trigger change event to update UI
            $optionSelect.trigger('change');
        },

        /**
         * Update subscription UI after activation/extension
         */
        updateSubscriptionUI: function($form, subscriptionData) {
            const $container = $form.closest('.lilac-subscription-container');
            const $statusDisplay = $container.find('.lilac-subscription-status-display');
            
            // Update status display
            if ($statusDisplay.length) {
                $statusDisplay.html(
                    '<div class="lilac-subscription-active">' +
                    lilac_subscription_i18n.active_until + ' ' +
                    subscriptionData.expiry_date +
                    '</div>'
                );
            }
            
            // Hide activation form if this was an activation (not extension)
            if ($form.hasClass('lilac-subscription-form')) {
                $form.slideUp();
                
                // Show extension form if it exists
                const $extensionForm = $container.find('.lilac-subscription-extension-form');
                if ($extensionForm.length) {
                    $extensionForm.slideDown();
                }
            }
            
            // Trigger page reload if specified in settings
            if (lilac_subscription_vars.reload_after_activation === '1') {
                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            }
        },

        /**
         * Show a status message
         */
        showMessage: function($container, message, type) {
            // Clear existing messages
            $container.empty();
            
            // Add new message with appropriate class
            $container.append(
                $('<div>')
                    .addClass('lilac-message lilac-message-' + type)
                    .html(message)
            ).slideDown();
            
            // Auto-hide success messages after delay
            if (type === 'success') {
                setTimeout(function() {
                    $container.slideUp();
                }, 5000);
            }
        },

        /**
         * Set form loading state
         */
        setFormLoading: function($form, isLoading) {
            const $submitBtn = $form.find('button[type="submit"]');
            const $selects = $form.find('select');
            
            if (isLoading) {
                // Disable form elements
                $submitBtn.prop('disabled', true).addClass('loading');
                $selects.prop('disabled', true);
                
                // Add loading indicator
                if (!$submitBtn.data('original-text')) {
                    $submitBtn.data('original-text', $submitBtn.html());
                    $submitBtn.html('<span class="lilac-spinner"></span> ' + lilac_subscription_i18n.processing);
                }
            } else {
                // Re-enable form elements
                $selects.prop('disabled', false);
                $submitBtn.removeClass('loading');
                
                // Restore button text
                if ($submitBtn.data('original-text')) {
                    $submitBtn.html($submitBtn.data('original-text'));
                }
                
                // Only enable submit if an option is selected
                const hasOption = $form.find('.lilac-subscription-option-select').val() !== '';
                $submitBtn.prop('disabled', !hasOption);
            }
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        LilacSubscriptions.init();
    });

})(jQuery);
