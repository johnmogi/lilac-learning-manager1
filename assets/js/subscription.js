(function($) {
    'use strict';

    /**
     * Subscription Manager
     * Handles all frontend subscription functionality
     */
    class SubscriptionManager {
        constructor() {
            this.ajaxUrl = lilacSubscription.ajaxurl;
            this.nonce = lilacSubscription.nonce;
            this.i18n = lilacSubscription.i18n || {};
            
            this.initEvents();
        }

        /**
         * Initialize event listeners
         */
        initEvents() {
            // Handle subscription button click
            $(document).on('click', '.lilac-subscription-button', (e) => this.handleSubscriptionButtonClick(e));
            
            // Handle confirm activation button
            $(document).on('click', '.lilac-confirm-activation', (e) => this.handleConfirmActivation(e));
            
            // Close modal when clicking outside content
            $(document).on('click', '.lilac-subscription-modal', (e) => {
                if (e.target === e.currentTarget) {
                    this.closeModal();
                }
            });
            
            // Close modal with close button
            $(document).on('click', '.lilac-subscription-modal-close', () => this.closeModal());
            
            // Handle duration selection
            $(document).on('click', '.lilac-duration-option', (e) => {
                const $option = $(e.currentTarget);
                $('.lilac-duration-option').removeClass('active');
                $option.addClass('active');
                $option.find('input[type="radio"]').prop('checked', true);
            });
        }

        /**
         * Handle subscription button click
         */
        handleSubscriptionButtonClick(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const courseId = $button.data('course-id');
            
            // Show duration options
            const $container = $button.closest('.lilac-subscription-activation');
            $container.find('.lilac-duration-options').slideDown();
        }

        /**
         * Handle confirm activation button click
         */
        handleConfirmActivation(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const $container = $button.closest('.lilac-subscription-activation');
            const $durationSelect = $container.find('.lilac-duration-select');
            const courseId = $container.find('.lilac-subscription-button').data('course-id');
            const duration = $durationSelect.val();
            
            if (!duration) {
                this.showMessage($container, 'error', this.i18n.select_duration || 'Please select a duration.');
                return;
            }
            
            this.activateSubscription(courseId, duration, $container);
        }

        /**
         * Activate subscription via AJAX
         */
        activateSubscription(courseId, duration, $container) {
            const $button = $container.find('.lilac-confirm-activation');
            const originalText = $button.html();
            
            // Show loading state
            $button.prop('disabled', true).html(`
                ${this.i18n.activating || 'Activating...'} 
                <span class="lilac-loading"></span>
            `);
            
            // Make AJAX request
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'lilac_activate_subscription',
                    nonce: this.nonce,
                    course_id: courseId,
                    duration: duration
                },
                dataType: 'json',
                success: (response) => {
                    if (response.success) {
                        this.showSuccess($container, response.data.message);
                        
                        // Update UI to show active subscription
                        $container.html(`
                            <div class="lilac-message lilac-message-success">
                                <p>${response.data.message}</p>
                                <p>${this.i18n.expires_on || 'Expires on'}: ${response.data.end_date}</p>
                            </div>
                        `);
                        
                        // Trigger event for other scripts
                        $(document).trigger('lilac:subscription_activated', {
                            courseId: courseId,
                            duration: duration,
                            endDate: response.data.end_date
                        });
                    } else {
                        this.showMessage($container, 'error', response.data.message || this.i18n.error);
                        $button.prop('disabled', false).html(originalText);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Subscription activation failed:', error);
                    this.showMessage($container, 'error', this.i18n.error || 'An error occurred. Please try again.');
                    $button.prop('disabled', false).html(originalText);
                }
            });
        }

        /**
         * Show a message to the user
         */
        showMessage($container, type, message) {
            const $message = $(`
                <div class="lilac-message lilac-message-${type}">
                    ${message}
                </div>
            `);
            
            $container.find('.lilac-message').remove();
            $container.prepend($message);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                $message.fadeOut(500, () => $message.remove());
            }, 5000);
        }

        /**
         * Show success message
         */
        showSuccess($container, message) {
            this.showMessage($container, 'success', message);
        }

        /**
         * Show error message
         */
        showError($container, message) {
            this.showMessage($container, 'error', message);
        }

        /**
         * Close modal
         */
        closeModal() {
            $('.lilac-subscription-modal').fadeOut(200);
        }

        /**
         * Show modal
         */
        showModal(content) {
            const $modal = $('.lilac-subscription-modal');
            
            if ($modal.length === 0) {
                $('body').append(`
                    <div class="lilac-subscription-modal">
                        <div class="lilac-subscription-modal-content">
                            <span class="lilac-subscription-modal-close">&times;</span>
                            ${content}
                        </div>
                    </div>
                `);
            } else {
                $modal.find('.lilac-subscription-modal-content').html(content);
            }
            
            $modal.fadeIn(200);
        }
    }

    // Initialize when document is ready
    $(document).ready(() => {
        // Only initialize if the lilacSubscription object exists
        if (typeof lilacSubscription !== 'undefined') {
            window.lilacSubscriptionManager = new SubscriptionManager();
        }
    });

})(jQuery);
