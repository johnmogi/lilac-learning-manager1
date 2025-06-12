/**
 * Admin JavaScript for Lilac Learning Manager
 */
(function($) {
    'use strict';

    // Document ready
    $(document).ready(function() {
        console.log('Lilac Learning Manager admin script loaded');
        
        // Add any admin-specific JavaScript here
        
    });

    // Example AJAX function
    function lilacDoSomething(data, callback) {
        $.ajax({
            url: lilacLearningManager.ajaxUrl,
            type: 'POST',
            data: {
                action: 'lilac_learning_manager_do_something',
                nonce: lilacLearningManager.nonce,
                data: data
            },
            success: function(response) {
                if (response.success && typeof callback === 'function') {
                    callback(response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
            }
        });
    }

})(jQuery);
