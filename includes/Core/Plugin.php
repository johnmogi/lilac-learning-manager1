<?php
namespace LilacLearningManager\Core;

class Plugin {
    /**
     * The loader that's responsible for maintaining and registering all hooks.
     */
    protected $loader;

    /**
     * Initialize the plugin and set up hooks.
     */
    public function run() {
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load required dependencies.
     */
    private function load_dependencies() {
        // Load any additional files or classes here
    }

    /**
     * Register all admin-related hooks.
     */
    private function define_admin_hooks() {
        // Admin hooks will be registered here
        if (is_admin()) {
            // Add admin menu
            add_action('admin_menu', [$this, 'add_admin_menu']);
            
            // Register settings
            add_action('admin_init', [$this, 'register_settings']);
        }
    }

    /**
     * Register all public-facing hooks.
     */
    private function define_public_hooks() {
        // Public hooks will be registered here
    }

    /**
     * Add admin menu items.
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Lilac Learning Manager', 'lilac-learning-manager'),
            __('Lilac Learning', 'lilac-learning-manager'),
            'manage_options',
            'lilac-learning-manager',
            [$this, 'display_admin_page'],
            'dashicons-welcome-learn-more',
            30
        );
    }

    /**
     * Register plugin settings.
     */
    public function register_settings() {
        // Register settings here
        register_setting('lilac_learning_manager_options', 'lilac_learning_manager_settings');
    }

    /**
     * Display the admin page.
     */
    public function display_admin_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Include the admin template
        include_once LILAC_LEARNING_MANAGER_PATH . 'admin/views/admin-page.php';
    }
}
