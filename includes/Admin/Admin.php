<?php
namespace LilacLearningManager\Admin;

use LilacLearningManager\Admin\UserManagement;

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
        
        // Initialize user management
        add_action('init', [$this, 'init_user_management']);
    }
    
    /**
     * Initialize user management functionality
     */
    public function init_user_management() {
        if (is_admin()) {
            UserManagement::init();
        }
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
        // Debug: Log current user capabilities
        if (current_user_can('manage_options')) {
            error_log('Current user can manage_options');
        } else {
            error_log('Current user CANNOT manage_options');
            $current_user = wp_get_current_user();
            error_log('Current user roles: ' . print_r($current_user->roles, true));
            error_log('Current user capabilities: ' . print_r($current_user->allcaps, true));
        }

        // Main menu
        $hook = add_menu_page(
            __('Lilac Learning Manager', 'lilac-learning-manager'),
            __('Lilac Learning', 'lilac-learning-manager'),
            'manage_options',
            'lilac-learning-manager',
            [$this, 'display_dashboard_page'],
            'dashicons-welcome-learn-more',
            30
        );
        
        // Debug page - only for admins
        $debug_hook = add_submenu_page(
            'lilac-learning-manager',
            __('Debug Info', 'lilac-learning-manager'),
            __('Debug', 'lilac-learning-manager'),
            'manage_options',
            'lilac-learning-debug',
            [$this, 'display_debug_page']
        );
        
        // Log the menu hooks for debugging
        error_log('Main menu hook: ' . $hook);
        error_log('Debug menu hook: ' . $debug_hook);

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
    
    /**
     * Display the debug information page.
     */
    public function display_debug_page() {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }
        
        // Get all roles and capabilities
        $wp_roles = wp_roles();
        $roles = $wp_roles->roles;
        
        // Get current user info
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        $user_capabilities = $current_user->allcaps;
        
        // Output the debug information
        echo '<div class="wrap">';
        echo '<h1>Debug Information</h1>';
        
        // Current user info
        echo '<h2>Current User</h2>';
        echo '<p><strong>Username:</strong> ' . esc_html($current_user->user_login) . '</p>';
        echo '<p><strong>Display Name:</strong> ' . esc_html($current_user->display_name) . '</p>';
        echo '<p><strong>Email:</strong> ' . esc_html($current_user->user_email) . '</p>';
        echo '<p><strong>Roles:</strong> ' . implode(', ', $user_roles) . '</p>';
        
        // Current user capabilities
        echo '<h3>User Capabilities</h3>';
        echo '<ul>';
        foreach ($user_capabilities as $cap => $has) {
            if ($has) {
                echo '<li>' . esc_html($cap) . '</li>';
            }
        }
        echo '</ul>';
        
        // All roles and capabilities
        echo '<h2>Available User Roles</h2>';
        foreach ($roles as $role_name => $role_info) {
            echo '<div style="margin-bottom: 20px; padding: 10px; background: #fff; border: 1px solid #ccd0d4;">';
            echo '<h3>Role: ' . esc_html($role_name) . '</h3>';
            echo '<p><strong>Display Name:</strong> ' . esc_html($role_info['name']) . '</p>';
            
            echo '<h4>Capabilities:</h4>';
            echo '<ul style="column-count: 3; -webkit-column-count: 3; -moz-column-count: 3;">';
            foreach ($role_info['capabilities'] as $cap => $has_cap) {
                echo '<li>' . esc_html($cap) . ' <code>' . ($has_cap ? 'true' : 'false') . '</code></li>';
            }
            echo '</ul>';
            echo '</div>';
        }
        
        echo '</div>'; // .wrap
    }
}
