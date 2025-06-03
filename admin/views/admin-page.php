<?php
/**
 * Admin page template
 *
 * @package LilacLearningManager\Admin\Views
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap" dir="rtl">
    <div class="llm-admin-notice-controls">
        <button type="button" id="llm-toggle-notices" class="button button-secondary">
            <span class="dashicons dashicons-hidden"></span>
            <span class="llm-notice-text">הסתר התראות</span>
        </button>
    </div>
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="llm-admin-header">
        <div class="llm-admin-header-content">
            <h2>ברוכים הבאים למנהל למידת לילך</h2>
            <p class="about-description">
                ניהול תוכניות למידה, קורסים ושאלות שלך מהלוח הבקרה
            </p>
        </div>
    </div>
    
    <div class="llm-dashboard-widgets">
        <div class="llm-dashboard-widget">
            <h3>נתונים מהירים</h3>
            <ul class="llm-stats-list">
                <?php
                // Get program count
                $program_count = wp_count_terms('llm_program', array('hide_empty' => false));
                if (!is_wp_error($program_count)) : ?>
                    <li>
                        <a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=llm_program&post_type=sfwd-courses')); ?>">
                            <span class="dashicons dashicons-category"></span>
                            <?php 
                            echo $program_count . ' ' . _n('תוכנית', 'תוכניות', $program_count, 'lilac-learning-manager');
                            ?>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php
                // Get course count
                $course_count = wp_count_posts('sfwd-courses')->publish;
                ?>
                <li>
                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=sfwd-courses')); ?>">
                        <span class="dashicons dashicons-welcome-learn-more"></span>
                        <?php 
                        echo $course_count . ' ' . _n('קורס', 'קורסים', $course_count, 'lilac-learning-manager');
                        ?>
                    </a>
                </li>
                
                <?php
                // Get question count
                $question_count = wp_count_posts('sfwd-question')->publish;
                ?>
                <li>
                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=sfwd-question')); ?>">
                        <span class="dashicons dashicons-editor-help"></span>
                        <?php 
                        echo $question_count . ' ' . _n('שאלה', 'שאלות', $question_count, 'lilac-learning-manager');
                        ?>
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="llm-dashboard-widget">
            <h3>פעילות אחרונה</h3>
            <div class="llm-activity-feed">
                <?php
                // Get recent activity
                $recent_posts = wp_get_recent_posts(array(
                    'post_type' => array('sfwd-courses', 'sfwd-question'),
                    'numberposts' => 5,
                    'post_status' => 'publish',
                ));
                
                if (!empty($recent_posts)) :
                    echo '<ul>';
                    foreach ($recent_posts as $post) {
                        $post_type = get_post_type_object($post['post_type']);
                        $post_type_name = $post_type ? $post_type->labels->singular_name : 'פריט';
                        echo sprintf(
                            '<li><span class="dashicons %s"></span> %s: <a href="%s">%s</a></li>',
                            $post['post_type'] === 'sfwd-courses' ? 'dashicons-welcome-learn-more' : 'dashicons-editor-help',
                            esc_html($post_type_name),
                            esc_url(get_edit_post_link($post['ID'])),
                            esc_html(get_the_title($post['ID']))
                        );
                    }
                    echo '</ul>';
                else :
                    echo '<p>' . esc_html__('No recent activity found.', 'lilac-learning-manager') . '</p>';
                endif;
                ?>
            </div>
        </div>
    </div>
    
    <div class="llm-dashboard-footer">
        <p>
            <?php
            printf(
                /* translators: %s: Plugin version */
                esc_html__('Lilac Learning Manager version %s', 'lilac-learning-manager'),
                esc_html(LILAC_LEARNING_MANAGER_VERSION)
            );
            ?>
        </p>
    </div>
</div>

<style>
/* Notice toggle button */
#llm-notice-toggle-wrap {
    position: fixed;
    top: 32px;
    right: 20px;
    z-index: 99999;
}

#llm-toggle-notices {
    display: flex;
    align-items: center;
    gap: 4px;
    text-decoration: none;
    padding: 0 12px;
    height: 30px;
    line-height: 28px;
    border-radius: 4px;
    background: #f0f0f1;
    border: 1px solid #dcdcde;
    color: #1d2327;
    font-size: 13px;
    transition: all 0.2s ease;
}

#llm-toggle-notices:hover {
    background: #f6f7f7;
    border-color: #c3c4c7;
    color: #1d2327;
}

#llm-toggle-notices .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

/* RTL support */
[dir="rtl"] #llm-notice-toggle-wrap {
    right: auto;
    left: 20px;
}

/* Hide notices when toggled */
.notice-hidden .notice:not(.llm-notice),
.notice-hidden #wpbody-content > .notice:not(.llm-notice),
.notice-hidden .wrap > .notice:not(.llm-notice),
.notice-hidden #wpbody-content > .updated,
.notice-hidden #wpbody-content > .error,
.notice-hidden #wpbody-content > .update-nag,
.notice-hidden #wpbody-content > .updated,
.notice-hidden #wpbody-content > .notice-error,
.notice-hidden #wpbody-content > .notice-warning,
.notice-hidden #wpbody-content > .notice-success,
.notice-hidden #wpbody-content > .notice-info {
    display: none !important;
}

/* Fix for admin bar position when notices are hidden */
.notice-hidden #wpcontent, 
.notice-hidden #wpfooter {
    margin-top: 0 !important;
}

/* Ensure this applies to all admin pages */
#wpbody-content > .notice,
.wrap > .notice,
#wpfooter + .notice,
.update-nag,
.updated,
.error,
.notice {
    transition: opacity 0.3s ease;
}
</style>

<script>
// Wrap everything in a self-executing function to avoid global scope pollution
(function($) {
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
            '.e-notice--extended'
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
            '.e-notice--extended'
        ];
        
        $(noticeContainers.join(',')).not('.llm-notice').show();
    }
    
    // Make the function globally available but safe
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
            if (typeof ajaxurl !== 'undefined') {
                $.post(ajaxurl, {
                    action: 'llm_save_notice_preference',
                    hide: show ? 0 : 1,
                    nonce: (window.llmNotices && window.llmNotices.nonce) ? window.llmNotices.nonce : ''
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

    // Initialize when document is ready
    $(document).ready(function() {
        // Initialize the toggle
        initNoticeToggle();
        
        // Handle dynamically added notices
        $(document).ajaxComplete(function() {
            if ($('body').hasClass('notice-hidden')) {
                hideNotices();
            }
        });
    });

    // Make sure it works with the WordPress heartbeat API
    $(document).on('heartbeat-tick', function() {
        if ($('body').hasClass('notice-hidden')) {
            hideNotices();
        }
    });

})(jQuery);
</script>
