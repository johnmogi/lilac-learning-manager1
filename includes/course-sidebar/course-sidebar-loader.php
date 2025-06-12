<?php
/**
 * LLM Course Sidebar Loader
 *
 * @package LilacLearningManager
 * @since 1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('LLM_COURSE_SIDEBAR_VERSION', '1.0.0');
define('LLM_COURSE_SIDEBAR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LLM_COURSE_SIDEBAR_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include the main plugin class
require_once LLM_COURSE_SIDEBAR_PLUGIN_DIR . 'class-llm-course-sidebar.php';

/**
 * Enqueue frontend scripts and styles.
 */
function llm_course_sidebar_enqueue_assets() {
    // Only load on single course, lesson, or topic pages
    if (is_singular(array('sfwd-courses', 'sfwd-lessons', 'sfwd-topic'))) {
        $suffix = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';
        
        // Enqueue styles
        wp_enqueue_style(
            'llm-course-sidebar',
            LLM_COURSE_SIDEBAR_PLUGIN_URL . 'assets/css/llm-course-sidebar.css',
            array(),
            LLM_COURSE_SIDEBAR_VERSION
        );
        
        // Add RTL support
        if (is_rtl()) {
            wp_style_add_data('llm-course-sidebar', 'rtl', 'replace');
        }
    }
}
add_action('wp_enqueue_scripts', 'llm_course_sidebar_enqueue_assets');

/**
 * Initialize the course sidebar functionality.
 */
function llm_course_sidebar_init() {
    return LLM_Course_Sidebar::get_instance();
}
add_action('plugins_loaded', 'llm_course_sidebar_init');

/**
 * Activation hook.
 */
function llm_course_sidebar_activate() {
    // Set a transient to show activation notice
    set_transient('llm_course_sidebar_activated', true, 30);
}
register_activation_hook(__FILE__, 'llm_course_sidebar_activate');

/**
 * Display admin notice on activation.
 */
function llm_course_sidebar_activation_notice() {
    if (get_transient('llm_course_sidebar_activated')) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('LLM Course Sidebar has been activated. Use the shortcode <code>[llm_topic_categories]</code> to display topic categories in your sidebar.', 'lilac-learning-manager'); ?></p>
        </div>
        <?php
        delete_transient('llm_course_sidebar_activated');
    }
}
add_action('admin_notices', 'llm_course_sidebar_activation_notice');
