<?php
/**
 * Admin Menu Handler
 *
 * @package LilacLearningManager\Admin
 */

namespace LilacLearningManager\Admin;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Admin_Menu
 *
 * Handles the admin menu structure for the plugin.
 */
class Admin_Menu {
    /**
     * The plugin name.
     *
     * @var string
     */
    private $plugin_name;

    /**
     * The plugin version.
     *
     * @var string
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @param string $plugin_name The name of this plugin.
     * @param string $version The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        // Register menus
        add_action('admin_menu', array($this, 'register_menus'));
        
        // Enqueue admin assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Register the admin menu items.
     */
    public function register_menus() {
        // Main menu item
        add_menu_page(
            __('מנהל למידת לילך', 'lilac-learning-manager'),
            __('למידת לילך', 'lilac-learning-manager'),
            'manage_options',
            'lilac-learning-manager',
            array($this, 'render_dashboard_page'),
            'dashicons-welcome-learn-more',
            30
        );

        // Dashboard submenu
        add_submenu_page(
            'lilac-learning-manager',
            __('לוח בקרה', 'lilac-learning-manager'),
            __('לוח בקרה', 'lilac-learning-manager'),
            'manage_options',
            'lilac-learning-manager',
            array($this, 'render_dashboard_page')
        );

        // Programs submenu
        add_submenu_page(
            'lilac-learning-manager',
            __('תוכניות', 'lilac-learning-manager'),
            __('תוכניות', 'lilac-learning-manager'),
            'manage_categories',
            'edit-tags.php?taxonomy=llm_program&post_type=sfwd-courses',
            null
        );

        // Questions submenu
        add_submenu_page(
            'lilac-learning-manager',
            __('שאלות', 'lilac-learning-manager'),
            __('שאלות', 'lilac-learning-manager'),
            'edit_posts',
            'edit.php?post_type=sfwd-question',
            null
        );

        // Settings submenu
        add_submenu_page(
            'lilac-learning-manager',
            __('הגדרות', 'lilac-learning-manager'),
            __('הגדרות', 'lilac-learning-manager'),
            'manage_options',
            'lilac-learning-manager-settings',
            array($this, 'render_settings_page')
        );
        
        // Add RTL body class to our admin pages
        add_filter('admin_body_class', array($this, 'add_admin_body_class'));
    }

    /**
     * Render the dashboard page with RTL support
     */
    public function render_dashboard_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('אין לך הרשאות מספיקות לגשת לדף זה.', 'lilac-learning-manager'));
        }

        // Get counts
        $program_count = wp_count_terms('llm_program', array('hide_empty' => false));
        $course_count = wp_count_posts('sfwd-courses')->publish;
        $question_count = wp_count_posts('sfwd-question')->publish;
        
        // Get recent questions
        $recent_questions = get_posts(array(
            'post_type' => 'sfwd-question',
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        ?>
        <div class="wrap" dir="rtl">
            <h1><?php _e('מנהל למידת לילך', 'lilac-learning-manager'); ?></h1>
            
            <div class="llm-admin-header">
                <div class="llm-admin-header-content">
                    <h2><?php _e('ברוכים הבאים למנהל למידת לילך', 'lilac-learning-manager'); ?></h2>
                    <p class="about-description">
                        <?php _e('ניהול תוכניות הלמידה, הקורסים והשאלות שלך מלוח הבקרה הזה.', 'lilac-learning-manager'); ?>
                    </p>
                </div>
            </div>
            
            <div class="llm-dashboard-widgets">
                <!-- Quick Stats Widget -->
                <div class="llm-dashboard-widget">
                    <h3><?php _e('סטטיסטיקות מהירות', 'lilac-learning-manager'); ?></h3>
                    <ul class="llm-stats-list">
                        <?php if (!is_wp_error($program_count)) : ?>
                        <li>
                            <a href="<?php echo admin_url('edit-tags.php?taxonomy=llm_program&post_type=sfwd-courses'); ?>">
                                <span class="dashicons dashicons-category"></span>
                                <?php 
                                printf(
                                    _n('%d תוכנית', '%d תוכניות', $program_count, 'lilac-learning-manager'),
                                    $program_count
                                );
                                ?>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <li>
                            <a href="<?php echo admin_url('edit.php?post_type=sfwd-courses'); ?>">
                                <span class="dashicons dashicons-welcome-learn-more"></span>
                                <?php 
                                printf(
                                    _n('%d קורס', '%d קורסים', $course_count, 'lilac-learning-manager'),
                                    $course_count
                                );
                                ?>
                            </a>
                        </li>
                        
                        <li>
                            <a href="<?php echo admin_url('edit.php?post_type=sfwd-question'); ?>">
                                <span class="dashicons dashicons-editor-help"></span>
                                <?php 
                                printf(
                                    _n('%d שאלה', '%d שאלות', $question_count, 'lilac-learning-manager'),
                                    $question_count
                                );
                                ?>
                            </a>
                        </li>
                    </ul>
                </div>
                
                <!-- Recent Activity Widget -->
                <div class="llm-dashboard-widget">
                    <h3><?php _e('פעילות אחרונה', 'lilac-learning-manager'); ?></h3>
                    <div class="llm-activity-feed">
                        <?php if (!empty($recent_questions)) : ?>
                            <ul>
                                <?php foreach ($recent_questions as $question) : ?>
                                    <li>
                                        <span class="dashicons dashicons-editor-help"></span>
                                        <a href="<?php echo get_edit_post_link($question->ID); ?>">
                                            <?php echo esc_html(get_the_title($question->ID)); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else : ?>
                            <p><?php _e('לא נמצאו שאלות אחרונות.', 'lilac-learning-manager'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="llm-dashboard-footer">
                <p>
                    <?php printf(
                        __('גרסה %s', 'lilac-learning-manager'),
                        LILAC_LEARNING_MANAGER_VERSION
                    ); ?>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Add custom body class to our admin pages
     *
     * @param string $classes Space-separated list of CSS classes.
     * @return string Modified list of CSS classes.
     */
    public function add_admin_body_class($classes) {
        // Add RTL class if the site is RTL
        if (is_rtl()) {
            $classes .= ' rtl';
        }
        
        // Add our plugin specific class
        $classes .= ' lilac-learning-manager-admin';
        
        return $classes;
    }
    
    /**
     * Display registration codes notice
     */
    public function registration_codes_notice() {
        if (current_user_can('manage_options')) {
            $manage_url = admin_url('admin.php?page=registration-codes');
            echo '<div class="notice notice-success"><p>';
            printf(
                /* translators: %s: URL to manage registration codes */
                esc_html__('מערכת קודי הרישום פעילה. %s', 'lilac-learning-manager'),
                '<a href="' . esc_url($manage_url) . '">' . esc_html__('ניהול קודי רישום', 'lilac-learning-manager') . '</a>'
            );
            echo '</p></div>';
        }
    }
    
    /**
     * Render the settings page.
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('אין לך הרשאות מספיקות לגשת לדף זה.', 'lilac-learning-manager'));
        }

        // Create the settings page wrapper
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('הגדרות מנהל למידת לילך', 'lilac-learning-manager') . '</h1>';
        
        // Show registration codes notice if needed
        if (class_exists('LilacLearningManager\Registration_Codes')) {
            $this->registration_codes_notice();
        }
        
        echo '<form method="post" action="options.php">';
        
        // Output security fields
        settings_fields('lilac_learning_manager_options');
        
        // Output setting sections and fields
        do_settings_sections('lilac-learning-manager-settings');
        
        // Submit button
        submit_button(__('שמור הגדרות', 'lilac-learning-manager'));
        
        echo '</form>';
        echo '</div>';
    }

    /**
     * Enqueue admin styles and scripts.
     * Highlight the correct submenu item for custom post type submenus.
     *
     * @param string $submenu_file The submenu file.
     * @param string $parent_file The parent file.
     * @return string
     */
    public function highlight_submenu_item($submenu_file, $parent_file) {
        global $current_screen;

        // Get the post type from the current screen
        $post_type = $current_screen->post_type;
        $taxonomy = $current_screen->taxonomy;

        // Highlight the correct submenu item for Schools
        if ('llm_school' === $post_type) {
            $submenu_file = 'edit.php?post_type=llm_school';
        }

        // Highlight the correct submenu item for Topics
        if ('llm_topic' === $taxonomy) {
            $submenu_file = 'edit-tags.php?taxonomy=llm_topic&post_type=llm_school';
        }

        return $submenu_file;
    }
}

// Initialize the admin menu
function lilac_learning_manager_admin_menu() {
    $admin_menu = new Admin_Menu('lilac-learning-manager', '1.0.0');
    add_action('admin_menu', [$admin_menu, 'register_menus']);
    add_filter('parent_file', [$admin_menu, 'highlight_menu_item']);
    add_filter('submenu_file', [$admin_menu, 'highlight_submenu_item'], 10, 2);
}
add_action('plugins_loaded', 'lilac_learning_manager_admin_menu');
