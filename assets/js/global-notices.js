(function($) {
    'use strict';

    // Make sure llmNotices is defined
    window.llmNotices = window.llmNotices || {
        ajax_url: ajaxurl || '',
        nonce: '',
        is_hidden: false,
        i18n: {
            show: 'הצג התראות',
            hide: 'הסתר התראות'
        }
    };

    // Initialize the notice toggle functionality
    function initNoticeToggle() {
        // Toggle notices when clicking the admin bar button
        $('#wp-admin-bar-llm-notice-toggle').on('click', function(e) {
            e.preventDefault();
            const $button = $(this);
            const isHidden = $('body').hasClass('notice-hidden');
            
            toggleNotices(!isHidden);
        });

        // Toggle notices when clicking the floating button
        $(document).on('click', '#llm-toggle-notices', function(e) {
            e.preventDefault();
            const isHidden = $('body').hasClass('notice-hidden');
            toggleNotices(!isHidden);
        });

        // Set initial state
        if (window.llmNotices.is_hidden) {
            $('body').addClass('notice-hidden');
        }
    }

    // Toggle notices visibility
    function toggleNotices(hide) {
        const $body = $('body');
        const $adminBarButton = $('#wp-admin-bar-llm-notice-toggle');
        
        if (hide) {
            $body.addClass('notice-hidden');
            $adminBarButton.find('.ab-icon')
                .removeClass('dashicons-hidden')
                .addClass('dashicons-visibility');
            $adminBarButton.find('.ab-label')
                .text(window.llmNotices.i18n.show);
        } else {
            $body.removeClass('notice-hidden');
            $adminBarButton.find('.ab-icon')
                .removeClass('dashicons-visibility')
                .addClass('dashicons-hidden');
            $adminBarButton.find('.ab-label')
                .text(window.llmNotices.i18n.hide);
        }

        // Save the preference via AJAX
        $.ajax({
            url: window.llmNotices.ajax_url,
            type: 'POST',
            data: {
                action: 'llm_toggle_notices',
                nonce: window.llmNotices.nonce,
                hide: hide ? 1 : 0
            },
            success: function(response) {
                if (response && response.success) {
                    // Update the floating button if it exists
                    const $toggleBtn = $('#llm-toggle-notices');
                    if ($toggleBtn.length) {
                        $toggleBtn.find('.llm-notice-text')
                            .text(hide ? window.llmNotices.i18n.show : window.llmNotices.i18n.hide);
                        $toggleBtn.find('.dashicons')
                            .toggleClass('dashicons-visibility', hide)
                            .toggleClass('dashicons-hidden', !hide);
                    }
                }
            }
        });
    }

    // Add floating toggle button to all admin pages
    function addFloatingToggleButton() {
        if ($('#llm-notice-toggle-wrap').length === 0) {
            const isHidden = $('body').hasClass('notice-hidden');
            $('body').append(`
                <div id="llm-notice-toggle-wrap">
                    <a href="#" id="llm-toggle-notices" class="button">
                        <span class="dashicons dashicons-${isHidden ? 'visibility' : 'hidden'}"></span>
                        <span class="llm-notice-text">
                            ${isHidden ? window.llmNotices.i18n.show : window.llmNotices.i18n.hide}
                        </span>
                    </a>
                </div>
            `);
        }
    }

    // Initialize everything when the admin bar is ready
    function waitForAdminBar() {
        if ($('#wpadminbar').length) {
            initNoticeToggle();
            addFloatingToggleButton();
        } else if (typeof wp !== 'undefined' && wp.customize && wp.customize.selectiveRefresh) {
            // Handle customizer preview
            wp.customize.selectiveRefresh.bind('partial-content-rendered', function() {
                if ($('#wpadminbar').length) {
                    initNoticeToggle();
                    addFloatingToggleButton();
                }
            });
        } else {
            setTimeout(waitForAdminBar, 100);
        }
    }

    // Wait for jQuery to be fully loaded
    if (typeof jQuery === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js';
        script.onload = function() {
            jQuery.noConflict();
            waitForAdminBar();
        };
        document.head.appendChild(script);
    } else {
        // Start initialization when DOM is ready
        $(document).ready(function() {
            waitForAdminBar();
            
            // Handle dynamically added notices
            $(document).ajaxComplete(function() {
                if ($('body').hasClass('notice-hidden')) {
                    $('.notice:not(.llm-notice)').hide();
                }
            });
        });
    }

})(jQuery);
