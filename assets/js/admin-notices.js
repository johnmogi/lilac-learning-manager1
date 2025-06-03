jQuery(document).ready(function($) {
    'use strict';

    // Function to hide all notices
    function hideNotices() {
        $('body').addClass('notice-hidden');
        
        // Target only specific notice containers
        var noticeContainers = [
            '#wpbody-content > .notice',
            '#wpbody-content > .update-nag',
            '#wpbody-content > .updated',
            '#wpbody-content > .error',
            '.wrap > .notice',
            '.wrap > .update-nag',
            '.wrap > .updated',
            '.wrap > .error',
            '.e-notice',
            '.e-notice--dismissible',
            '.e-notice--extended',
            '[id*="notice"][class*="notice"]',
            '.notice',
            '.update-nag',
            '.updated',
            '.error',
            '.is-dismissible'
        ];
        
        // Hide each notice container
        $(noticeContainers.join(',')).not('.llm-notice').hide();
    }
    
    // Function to show all notices
    function showNotices() {
        $('body').removeClass('notice-hidden');
        
        // Show all notice containers
        var noticeContainers = [
            '#wpbody-content > .notice',
            '#wpbody-content > .update-nag',
            '#wpbody-content > .updated',
            '#wpbody-content > .error',
            '.wrap > .notice',
            '.wrap > .update-nag',
            '.wrap > .updated',
            '.wrap > .error',
            '.e-notice',
            '.e-notice--dismissible',
            '.e-notice--extended',
            '[id*="notice"][class*="notice"]',
            '.notice',
            '.update-nag',
            '.updated',
            '.error',
            '.is-dismissible'
        ];
        
        $(noticeContainers.join(',')).not('.llm-notice').show();
    }
    
    // Toggle notices function
    window.llmToggleNotices = function(show) {
        var $toggleButton = $('.llm-toggle-notice-btn, #llm-toggle-notices');
        
        if (show) {
            showNotices();
            $toggleButton.find('.llm-notice-text').text('הסתר התראות');
            $toggleButton.find('.dashicons')
                .removeClass('dashicons-visibility')
                .addClass('dashicons-hidden');
        } else {
            hideNotices();
            $toggleButton.find('.llm-notice-text').text('הצג התראות');
            $toggleButton.find('.dashicons')
                .removeClass('dashicons-hidden')
                .addClass('dashicons-visibility');
        }
        
        // Save preference
        try {
            localStorage.setItem('llm_notices_hidden', !show);
            // Also save to user meta for server-side use
            if (typeof ajaxurl !== 'undefined' && window.llmNotices && window.llmNotices.nonce) {
                $.post(ajaxurl, {
                    action: 'llm_save_notice_preference',
                    hide: show ? 0 : 1,
                    nonce: window.llmNotices.nonce
                });
            }
        } catch (e) {
            console.error('Failed to save notice preference:', e);
        }
    };

    // Initialize the notice toggle
    function initNoticeToggle() {
        // Set default state to hidden if not set
        if (localStorage.getItem('llm_notices_hidden') === null) {
            try {
                localStorage.setItem('llm_notices_hidden', 'true');
            } catch (e) {
                console.error('Failed to set default notice preference:', e);
            }
        }
        
        // Check if notices should be hidden
        var noticesHidden = localStorage.getItem('llm_notices_hidden') === 'true';
        
        // Set initial state (hidden by default)
        if (typeof window.llmToggleNotices === 'function') {
            // Use setTimeout to ensure the DOM is fully loaded
            setTimeout(function() {
                window.llmToggleNotices(!noticesHidden);
            }, 100);
        }
        
        // Toggle notices on button click
        $(document).on('click', '.llm-toggle-notice-btn, #llm-toggle-notices', function(e) {
            e.preventDefault();
            if (typeof window.llmToggleNotices === 'function') {
                window.llmToggleNotices($('body').hasClass('notice-hidden'));
            }
        });
    }

    // Initialize
    initNoticeToggle();
    
    // Handle dynamically added notices
    $(document).ajaxComplete(function() {
        if ($('body').hasClass('notice-hidden')) {
            hideNotices();
        }
    });

    // Make sure it works with the WordPress heartbeat API
    $(document).on('heartbeat-tick', function() {
        if ($('body').hasClass('notice-hidden')) {
            hideNotices();
        }
    });
});
