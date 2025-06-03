<?php
/**
 * Admin Notices Handler
 *
 * @package LilacLearningManager
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

namespace LilacLearningManager\Admin;

class AdminNotices {
    /**
     * Whether notices should be hidden
     *
     * @var bool
     */
    private $hide_notices = false;

    /**
     * Constructor
     */
    public function __construct() {
        // Set initial state
        $this->hide_notices = $this->should_hide_notices();
        
        // Add admin bar menu
        add_action('admin_bar_menu', array($this, 'add_notice_toggle_to_admin_bar'), 999);
        
        // Handle admin notices
        add_action('admin_head', array($this, 'handle_admin_notices'), 1);
        
        // AJAX handler for saving notice preference
        add_action('wp_ajax_llm_save_notice_preference', array($this, 'save_notice_preference'));
        
        // Add admin footer script for handling the toggle
        add_action('admin_footer', array($this, 'add_admin_footer_script'));
    }
    
    /**
     * Check if notices should be hidden for current user
     * 
     * @return bool
     */
    private function should_hide_notices() {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $user_id = get_current_user_id();
        $hidden = get_user_meta($user_id, 'llm_notices_hidden', true);
        
        // Default to hidden if not set
        if ($hidden === '') {
            update_user_meta($user_id, 'llm_notices_hidden', '1');
            return true;
        }
        
        return $hidden === '1';
    }
    
    /**
     * Handle admin notices
     */
    public function handle_admin_notices() {
        if ($this->hide_notices) {
            // Remove all admin notices
            remove_all_actions('admin_notices');
            remove_all_actions('all_admin_notices');
            
            // Also remove from admin head and footer
            remove_all_actions('admin_footer', 1);
            remove_all_actions('in_admin_header');
            
            // Remove Elementor notices
            if (did_action('elementor/loaded')) {
                remove_all_actions('admin_notices', 10);
                remove_all_actions('admin_footer', 10);
            }
        }
    }

    /**
     * Add notice toggle to admin bar
     */
    public function add_notice_toggle_to_admin_bar($wp_admin_bar) {
        if (!current_user_can('manage_options')) {
            return;
        }

        $icon = $this->hide_notices ? 'visibility' : 'hidden';
        $text = $this->hide_notices ? 'הצג התראות' : 'הסתר התראות';

        $wp_admin_bar->add_node(array(
            'id'    => 'llm-notice-toggle',
            'title' => '<span class="ab-icon dashicons dashicons-' . $icon . '"></span><span class="ab-label">' . $text . '</span>',
            'href'  => '#',
            'meta'  => array(
                'class' => 'llm-notice-toggle',
                'title' => $text,
            ),
        ));
    }

    /**
     * Enqueue scripts and styles for the notice toggle
     */
    public function enqueue_global_notice_assets() {
        // Only enqueue if user can manage options
        if (!current_user_can('manage_options')) {
            return;
        }

        // Enqueue the script
        wp_enqueue_script(
            'llm-admin-notices',
            LILAC_LEARNING_MANAGER_PLUGIN_URL . 'assets/js/admin-notices.js',
            array('jquery'),
            LILAC_LEARNING_MANAGER_VERSION,
            true
        );

        // Localize the script with data
        wp_localize_script(
            'llm-admin-notices',
            'llmNotices',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('llm_notice_toggle_nonce'),
                'hidden'  => $this->hide_notices,
                'text'    => array(
                    'show' => 'הצג התראות',
                    'hide' => 'הסתר התראות'
                )
            )
        );
    }

    /**
     * Add inline styles for the notice toggle
     */
    private function add_inline_styles() {
        ?>
        <style>
        /* Admin bar button */
        #wpadminbar .quicklinks #wp-admin-bar-llm-notice-toggle .ab-icon:before {
            font: 20px/1 dashicons;
            padding: 4px 0;
        }

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

        /* Hide all admin notices by default */
        .notice:not(.llm-notice),
        .notice-error:not(.llm-notice),
        .notice-warning:not(.llm-notice),
        .notice-success:not(.llm-notice),
        .notice-info:not(.llm-notice),
        .update-nag:not(.llm-notice),
        .updated:not(.llm-notice),
        .error:not(.llm-notice),
        .is-dismissible:not(.llm-notice),
        .e-notice:not(.llm-notice),
        #wpbody-content > .update-nag:not(.llm-notice),
        #wpbody-content > .updated:not(.llm-notice),
        #wpbody-content > .error:not(.llm-notice),
        #wpbody-content > .notice:not(.llm-notice),
        .wrap > .notice:not(.llm-notice),
        .wrap > .updated:not(.llm-notice),
        .wrap > .error:not(.llm-notice),
        .wrap > .is-dismissible:not(.llm-notice) {
            display: none !important;
        }
        
        /* Hide Elementor notices */
        .e-notice:not(.llm-notice),
        .e-notice--dismissible:not(.llm-notice),
        .e-notice--extended:not(.llm-notice) {
            display: none !important;
        }

        /* Show notices only when explicitly toggled on */
        body:not(.llm-notices-hidden) .notice:not(.llm-notice),
        body:not(.llm-notices-hidden) .notice-error:not(.llm-notice),
        body:not(.llm-notices-hidden) .notice-warning:not(.llm-notice),
        body:not(.llm-notices-hidden) .notice-success:not(.llm-notice),
        body:not(.llm-notices-hidden) .notice-info:not(.llm-notice),
        body:not(.llm-notices-hidden) .update-nag:not(.llm-notice),
        body:not(.llm-notices-hidden) .updated:not(.llm-notice),
        body:not(.llm-notices-hidden) .error:not(.llm-notice),
        body:not(.llm-notices-hidden) .is-dismissible:not(.llm-notice),
        body:not(.llm-notices-hidden) #wpbody-content > .update-nag:not(.llm-notice),
        body:not(.llm-notices-hidden) #wpbody-content > .updated:not(.llm-notice),
        body:not(.llm-notices-hidden) #wpbody-content > .error:not(.llm-notice),
        body:not(.llm-notices-hidden) #wpbody-content > .notice:not(.llm-notice),
        body:not(.llm-notices-hidden) .wrap > .notice:not(.llm-notice),
        body:not(.llm-notices-hidden) .wrap > .updated:not(.llm-notice),
        body:not(.llm-notices-hidden) .wrap > .error:not(.llm-notice),
        body:not(.llm-notices-hidden) .wrap > .is-dismissible:not(.llm-notice) {
            display: block !important;
        }
        
        /* Show Elementor notices when toggled on */
        body:not(.llm-notices-hidden) .e-notice:not(.llm-notice),
        body:not(.llm-notices-hidden) .e-notice--dismissible:not(.llm-notice),
        body:not(.llm-notices-hidden) .e-notice--extended:not(.llm-notice) {
            display: block !important;
        }

        /* Always show our plugin notices */
        .llm-notice,
        .notice.llm-notice,
        .notice.is-dismissible.llm-notice {
            display: block !important;
        }

        /* Fix for admin bar position when notices are hidden */
        .llm-notices-hidden #wpcontent, 
        .llm-notices-hidden #wpfooter {
            margin-top: 0 !important;
        }
        </style>
        <?php
    }

    /**
     * Save the notice preference
     */
    public function save_notice_preference() {
        check_ajax_referer('llm_notice_toggle_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'אין הרשאה מתאימה'));
        }
        
        $hide = isset($_POST['hide']) ? '1' : '0';
        $user_id = get_current_user_id();
        update_user_meta($user_id, 'llm_notices_hidden', $hide);
        
        // Update the admin bar
        if (is_admin_bar_showing()) {
            global $wp_admin_bar;
            // Force refresh the admin bar
            $wp_admin_bar->remove_node('llm-notice-toggle');
            $this->add_notice_toggle_to_admin_bar($wp_admin_bar);
        }
        
        wp_send_json_success(array(
            'message' => $hide === '1' ? 'ההתראות הוסתרו' : 'ההתראות מוצגות',
            'hide' => $hide === '1'
        ));
    }
    
    /**
     * Add admin footer script
     */
    public function add_admin_footer_script() {
        // Only add if user can manage options
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $current_screen = get_current_screen();
        if (!$current_screen) {
            return;
        }
        
        // Don't add on the frontend
        if (!is_admin() || $current_screen->is_block_editor()) {
            return;
        }
        
        // Get current preference
        $notices_hidden = $this->hide_notices ? '1' : '0';
        $text = $notices_hidden === '1' ? 'הצג התראות' : 'הסתר התראות';
        $icon = $notices_hidden === '1' ? 'visibility' : 'hidden';
        
        // Add the toggle button to the admin bar
        if (!wp_script_is('llm-admin-notices', 'done')) {
            ?>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Add toggle button to admin bar
                var $adminBar = $('#wp-admin-bar-top-secondary');
                if ($adminBar.length && !$('#wp-admin-bar-llm-notice-toggle').length) {
                    $adminBar.prepend(
                        '<li id="wp-admin-bar-llm-notice-toggle">' +
                        '<a href="#" class="ab-item">' +
                        '<span class="ab-icon dashicons dashicons-<?php echo esc_js($icon); ?>"></span>' +
                        '<span class="ab-label"><?php echo esc_js($text); ?></span>' +
                        '</a>' +
                        '</li>'
                    );
                }
                
                // Handle toggle click
                $(document).on('click', '#wp-admin-bar-llm-notice-toggle a', function(e) {
                    e.preventDefault();
                    
                    var $button = $(this);
                    var $icon = $button.find('.dashicons');
                    var $label = $button.find('.ab-label');
                    var isHidden = $icon.hasClass('dashicons-visibility');
                    
                    // Toggle icon and text
                    if (isHidden) {
                        $icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
                        $label.text('הסתר התראות');
                    } else {
                        $icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
                        $label.text('הצג התראות');
                    }
                    
                    // Save preference
                    $.post(ajaxurl, {
                        action: 'llm_save_notice_preference',
                        hide: isHidden ? '1' : '0',
                        nonce: llmNotices.nonce
                    }).done(function(response) {
                        if (response.success) {
                            window.location.reload();
                        }
                    });
                });
            });
            </script>
            <?php
        }
    }
    
    /**
     * Add notice-hiding class to admin body
     */
    public function add_admin_body_class($classes) {
        if (!is_admin()) {
            return $classes;
        }
        
        if ($this->hide_notices) {
            $classes .= ' llm-notices-hidden';
        }
        
        return $classes;
    }
}

// Initialize the class
new AdminNotices();
