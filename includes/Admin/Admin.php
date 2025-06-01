<?php
namespace LilacLearningManager\Admin;

/**
 * The admin-specific functionality of the plugin.
 */
class Admin {
    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_pages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_styles']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    /**
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles($hook) {
        if (strpos($hook, 'lilac-learning-manager') === false) {
            return;
        }
        
        wp_enqueue_style(
            'lilac-learning-manager-admin',
            LILAC_LEARNING_MANAGER_URL . 'assets/css/admin.css',
            [],
            LILAC_LEARNING_MANAGER_VERSION,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'lilac-learning-manager') === false) {
            return;
        }

        wp_enqueue_script(
            'lilac-learning-manager-admin',
            LILAC_LEARNING_MANAGER_URL . 'assets/js/admin.js',
            ['jquery'],
            LILAC_LEARNING_MANAGER_VERSION,
            true
        );
    }

    /**
     * Add admin pages.
     */
    public function add_admin_pages() {
        // Main menu
        add_menu_page(
            __('Lilac Learning Manager', 'lilac-learning-manager'),
            __('Lilac Learning', 'lilac-learning-manager'),
            'manage_options',
            'lilac-learning-manager',
            [$this, 'display_dashboard_page'],
            'dashicons-welcome-learn-more',
            30
        );

        // Dashboard submenu
        add_submenu_page(
            'lilac-learning-manager',
            __('Dashboard', 'lilac-learning-manager'),
            __('Dashboard', 'lilac-learning-manager'),
            'manage_options',
            'lilac-learning-manager',
            [$this, 'display_dashboard_page']
        );

        // Schools submenu
        add_submenu_page(
            'lilac-learning-manager',
            __('School Management', 'lilac-learning-manager'),
            __('Schools', 'lilac-learning-manager'),
            'manage_options',
            'lilac-learning-schools',
            [$this, 'display_schools_page']
        );

        // Topics submenu
        add_submenu_page(
            'lilac-learning-manager',
            __('Topic Management', 'lilac-learning-manager'),
            __('Topics', 'lilac-learning-manager'),
            'manage_options',
            'lilac-learning-topics',
            [$this, 'display_topics_page']
        );
    }

    /**
     * Display the dashboard page.
     */
    public function display_dashboard_page() {
        include_once LILAC_LEARNING_MANAGER_PATH . 'admin/views/dashboard.php';
    }

    /**
     * Display the schools management page.
     */
    public function display_schools_page() {
        include_once LILAC_LEARNING_MANAGER_PATH . 'admin/views/schools.php';
    }

    /**
     * Display the topics management page.
     */
    public function display_topics_page() {
        include_once LILAC_LEARNING_MANAGER_PATH . 'admin/views/topics.php';
    }
}
