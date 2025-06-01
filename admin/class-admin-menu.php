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
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_filter('submenu_file', array($this, 'highlight_submenu_item'), 10, 2);
        
        // Initialize admin notice suppression
        add_action('admin_init', array($this, 'maybe_suppress_admin_notices'));
        
        // Add RTL body class
        add_filter('admin_body_class', array($this, 'add_rtl_body_class'));
    }
    
    /**
     * Add RTL body class if needed
     */
    public function add_rtl_body_class($classes) {
        if (is_rtl()) {
            $classes .= ' rtl';
        }
        return $classes;
    }

    /**
     * Register the admin menu items.
     */
    public function register_menus() {
        // Main menu item
        add_menu_page(
            'מנהל למידת לילך',
            'למידת לילך',
            'manage_options',
            'lilac-learning-manager',
            array($this, 'render_dashboard_page'),
            'dashicons-welcome-learn-more',
            30
        );

        // Dashboard submenu
        add_submenu_page(
            'lilac-learning-manager',
            'לוח בקרה',
            'לוח בקרה',
            'manage_options',
            'lilac-learning-manager',
            array($this, 'render_dashboard_page')
        );

        // Programs submenu
        add_submenu_page(
            'lilac-learning-manager',
            'תוכניות',
            'תוכניות',
            'manage_options',
            'edit-tags.php?taxonomy=llm_program&post_type=sfwd-courses',
            ''
        );

        // Questions submenu
        add_submenu_page(
            'lilac-learning-manager',
            'שאלות',
            'שאלות',
            'manage_options',
            'edit.php?post_type=llm_question',
            ''
        );

        // Settings submenu
        add_submenu_page(
            'lilac-learning-manager',
            'הגדרות',
            'הגדרות',
            'manage_options',
            'lilac-learning-manager-settings',
            array($this, 'render_settings_page')
        );
        
        // Add RTL body class to our admin pages
        add_filter('admin_body_class', array($this, 'add_admin_body_class'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Render the dashboard page.
     */
    public function render_dashboard_page() {
        if (!current_user_can('manage_options')) {
            wp_die('אין לך הרשאות מספיקות לגשת לדף זה.');
        }

        // Get counts
        $programs_count = wp_count_terms('llm_program', array('hide_empty' => false));
        $courses_count = wp_count_posts('sfwd-courses')->publish;
        $questions_count = wp_count_posts('llm_question')->publish;

        // Get recent questions
        $recent_questions = get_posts(array(
            'post_type' => 'llm_question',
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC',
        ));

        // Start output
        echo '<div class="wrap llm-admin-wrap" dir="rtl">';
        
        // Admin header
        echo '<div class="llm-admin-header">';
        echo '<h1>ברוכים הבאים למנהל למידת לילך</h1>';
        echo '<p class="description">ניהול תוכניות למידה, קורסים ושאלות שלך מהלוח הבקרה</p>';
        echo '</div>';

        // Stats widgets
        echo '<div class="llm-dashboard-widgets">';
        
        // Quick Stats
        echo '<div class="llm-dashboard-widget">';
        echo '<h2><span class="dashicons dashicons-chart-bar"></span> נתונים מהירים</h2>';
        echo '<div class="llm-stats-container">';
        echo '<a href="' . admin_url('edit-tags.php?taxonomy=llm_program&post_type=sfwd-courses') . '" class="llm-stat-box">';
        echo '<span class="llm-stat-number">' . number_format_i18n($programs_count) . '</span>';
        echo '<span class="llm-stat-label">' . ($programs_count == 1 ? 'תוכנית' : 'תוכניות') . '</span>';
        echo '</a>';
        
        echo '<a href="' . admin_url('edit.php?post_type=sfwd-courses') . '" class="llm-stat-box">';
        echo '<span class="llm-stat-number">' . number_format_i18n($courses_count) . '</span>';
        echo '<span class="llm-stat-label">' . ($courses_count == 1 ? 'קורס' : 'קורסים') . '</span>';
        echo '</a>';
        
        echo '<a href="' . admin_url('edit.php?post_type=llm_question') . '" class="llm-stat-box">';
        echo '<span class="llm-stat-number">' . number_format_i18n($questions_count) . '</span>';
        echo '<span class="llm-stat-label">' . ($questions_count == 1 ? 'שאלה' : 'שאלות') . '</span>';
        echo '</a>';
        echo '</div>'; // End .llm-stats-container
        echo '</div>'; // End .llm-dashboard-widget

        // Recent Activity
        echo '<div class="llm-dashboard-widget">';
        echo '<h2><span class="dashicons dashicons-update"></span> פעילות אחרונה</h2>';
        if (!empty($recent_questions)) {
            echo '<ul class="llm-activity-feed">';
            foreach ($recent_questions as $question) {
                $time_diff = human_time_diff(get_the_modified_date('U', $question->ID), current_time('timestamp'));
                echo '<li class="llm-activity-item">';
                echo '<span class="dashicons dashicons-format-chat"></span>';
                echo '<div class="llm-activity-content">';
                echo '<a href="' . get_edit_post_link($question->ID) . '" class="llm-activity-title">' . esc_html($question->post_title) . '</a>';
                echo '<span class="llm-activity-time">לפני ' . $time_diff . '</span>';
                echo '</div>'; // End .llm-activity-content
                echo '</li>';
            }
            echo '</ul>';
            echo '<div class="llm-view-all">';
            echo '<a href="' . admin_url('edit.php?post_type=llm_question') . '">צפה בכל השאלות <span class="dashicons dashicons-arrow-left"></span></a>';
            echo '</div>';
        } else {
            echo '<div class="llm-no-activity">';
            echo '<p>לא נמצאה פעילות אחרונה.</p>';
            echo '<a href="' . admin_url('post-new.php?post_type=llm_question') . '" class="button button-primary">הוסף שאלה חדשה</a>';
            echo '</div>';
        }
        echo '</div>'; // End .llm-dashboard-widget

        echo '</div>'; // End .llm-dashboard-widgets

        // Footer
        echo '<div class="llm-dashboard-footer">';
        echo '<p class="llm-version">גרסה ' . esc_html(LILAC_LEARNING_MANAGER_VERSION) . ' | <a href="https://example.com/docs" target="_blank">תיעוד</a> | <a href="' . admin_url('admin.php?page=lilac-learning-manager-settings') . '">הגדרות</a></p>';
        echo '</div>';

        echo '</div>'; // End .wrap
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
    /**
     * Register settings for the plugin
     */
    public function register_settings() {
        // Register a setting for our options
        register_setting(
            'lilac_learning_manager',
            'lilac_learning_manager_options',
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_options'),
                'default' => array()
            )
        );
        
        // Add a section to our settings (empty since we're handling the form directly)
        add_settings_section(
            'lilac_learning_manager_general',
            '', // Empty title since we're not using it
            '__return_false', // No callback needed
            'lilac-learning-manager-settings'
        );
    }
    
    /**
     * Sanitize plugin options
     */
    public function sanitize_options($input) {
        $sanitized = array();
        
        if (isset($input['hide_admin_notices'])) {
            $sanitized['hide_admin_notices'] = absint($input['hide_admin_notices']);
        }
        
        return $sanitized;
    }
    
    /**
     * Maybe suppress admin notices.
     */
    public function maybe_suppress_admin_notices() {
        // Only run on admin pages
        if (!is_admin()) {
            return;
        }
        
        // Get options
        $options = get_option('lilac_learning_manager_options', array());
        $hide_notices = isset($options['hide_admin_notices']) ? $options['hide_admin_notices'] : 0;
        
        // Check if we should hide notices
        if (!$hide_notices) {
            return;
        }
        
        // Don't hide notices on our plugin pages or the main dashboard
        $current_screen = get_current_screen();
        $is_llm_page = isset($_GET['page']) && strpos($_GET['page'], 'lilac-learning-manager') !== false;
        $is_dashboard = $current_screen && $current_screen->id === 'dashboard';
        
        if ($is_llm_page || $is_dashboard) {
            return;
        }
        
        // Remove all admin notices
        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');
        remove_all_actions('user_admin_notices');
        remove_all_actions('network_admin_notices');
        
        // Remove update nags and other notices
        remove_action('admin_notices', 'update_nag', 3);
        remove_action('admin_notices', 'maintenance_nag');
        remove_action('admin_notices', 'site_admin_notice');
        
        // Add our custom CSS to hide notices with RTL support
        add_action('admin_head', function() {
            ?>
            <style id="llm-hide-notices">
                /* Hide all notices except ours */
                .notice:not(.llm-notice),
                .update-nag,
                .updated,
                .error,
                .notice-error,
                .notice-warning,
                .notice-success,
                .notice-info,
                #wpbody-content > .update-nag,
                #wpbody-content > .updated,
                #wpbody-content > .error,
                #wpbody-content > .notice,
                #wpbody-content > .wrap > .notice:not(.llm-notice),
                .update-nag,
                .updated,
                .notice,
                div.error,
                div.updated,
                .notice-warning,
                #setting-error-tgmpa,
                #wpfooter {
                    display: none !important;
                }
                
                /* RTL specific fixes */
                body.rtl .wrap {
                    margin: 0 15px 0 0;
                }
                
                /* Keep our plugin notices visible */
                .notice.llm-notice,
                .notice.lilac-notice,
                .notice.is-dismissible.llm-notice,
                .notice.is-dismissible.lilac-notice {
                    display: block !important;
                }
            </style>
            <?php
        }, 999);
    }
    
    /**
     * Render the settings page.
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die('אין לך הרשאות מספיקות לגשת לדף זה.');
        }

        // Get options
        $options = get_option('lilac_learning_manager_options', array());
        $hide_notices = isset($options['hide_admin_notices']) ? (bool) $options['hide_admin_notices'] : false;

        // Save settings if form was submitted
        if (isset($_POST['lilac_learning_manager_settings_nonce']) && 
            wp_verify_nonce($_POST['lilac_learning_manager_settings_nonce'], 'lilac_learning_manager_save_settings')) {
            
            // Update options
            $options['hide_admin_notices'] = isset($_POST['hide_admin_notices']) ? 1 : 0;
            update_option('lilac_learning_manager_options', $options);
            
            // Show success message
            echo '<div class="notice notice-success"><p>ההגדרות נשמרו בהצלחה!</p></div>';
            
            // Refresh options
            $hide_notices = (bool) $options['hide_admin_notices'];
        }
        ?>
        <div class="wrap llm-settings-wrap" dir="rtl">
            <h1>הגדרות מנהל למידת לילך</h1>
            
            <div class="llm-settings-content">
                <form method="post" action="">
                    <?php wp_nonce_field('lilac_learning_manager_save_settings', 'lilac_learning_manager_settings_nonce'); ?>
                    
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">הגדרות כלליות</th>
                                <td>
                                    <fieldset>
                                        <label for="hide_admin_notices">
                                            <input type="checkbox" name="hide_admin_notices" id="hide_admin_notices" value="1" <?php checked($hide_notices, true); ?>>
                                            הסתר הודעות מערכת בלוח הבקרה
                                        </label>
                                        <p class="description">
                                            הפעל אפשרות זו כדי להסתיר הודעות מערכת לא רצויות מלוח הבקרה של וורדפרס.
                                        </p>
                                    </fieldset>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <?php submit_button('שמור שינויים'); ?>
                </form>
            </div>
            
            <div class="llm-settings-sidebar">
                <div class="llm-settings-box">
                    <h3>על התוסף</h3>
                    <p>מנהל למידת לילך הוא תוסף לניהול מערכי למידה, קורסים ושעורים בוורדפרס.</p>
                    <p>גרסה: <?php echo esc_html(LILAC_LEARNING_MANAGER_VERSION); ?></p>
                </div>
                
                <div class="llm-settings-box">
                    <h3>תמיכה</h3>
                    <p>לשאלות או בעיות, אנא צור קשר עם צוות התמיכה שלנו.</p>
                </div>
            </div>
        </div>
        <?php
    }

        // Only load the rest on our plugin pages
        if (strpos($hook, 'lilac-learning-manager') === false) {
            return;
        }
        
        // Enqueue WordPress media scripts
        wp_enqueue_media();
        
        // Enqueue admin styles
        wp_enqueue_style(
            $this->plugin_name . '-admin',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/admin.css',
            array(),
            $this->version,
            'all'
        );
        
        // Enqueue admin scripts
        wp_enqueue_script(
            $this->plugin_name . '-admin',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/admin.js',
            array('jquery'),
            $this->version,
            true
        );
        
        // Add inline styles for RTL
        if (is_rtl()) {
            $custom_css = "
                .llm-settings-sidebar {
                    float: left !important;
                    margin-right: 20px;
                }
                .llm-settings-content {
                    float: right !important;
                }
                .form-table th {
                    text-align: right !important;
                    padding-right: 0 !important;
                    padding-left: 10px !important;
                }
            ";
            wp_add_inline_style($this->plugin_name . '-admin-rtl', $custom_css);
        }
        
        // Localize script with ajax url
        wp_localize_script(
            $this->plugin_name . '-admin',
            'llm_admin',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('llm-admin-nonce'),
                'is_rtl' => is_rtl() ? 1 : 0,
                'i18n' => array(
                    'confirm_delete' => __('Are you sure you want to delete this item? This action cannot be undone.', 'lilac-learning-manager'),
                )
            )
        );
    }
    
    /**
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
