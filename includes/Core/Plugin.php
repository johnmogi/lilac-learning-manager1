<?php
namespace LilacLearningManager\Core;

class Plugin {
    /**
     * The plugin's text domain.
     *
     * @var string
     */
    private $text_domain;
    
    /**
     * The loader that's responsible for maintaining and registering all hooks.
     *
     * @var Loader
     */
    protected $loader;

    /**
     * Initialize the plugin and set up hooks.
     */
    public function run() {
        $this->text_domain = 'lilac-learning-manager';
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->init_components();
    }
    
    /**
     * Initialize plugin components
     */
    private function init_components() {
        // Initialize roles
        Roles::init();
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
        if (!is_admin()) {
            return;
        }
        
        // Admin menu
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // User management
        add_action('show_user_profile', [User::class, 'add_role_profile_fields']);
        add_action('edit_user_profile', [User::class, 'add_role_profile_fields']);
        add_action('personal_options_update', [User::class, 'save_role_profile_fields']);
        add_action('edit_user_profile_update', [User::class, 'save_role_profile_fields']);
        
        // User list columns
        add_filter('manage_users_columns', [User::class, 'add_role_column']);
        add_filter('manage_users_custom_column', [User::class, 'show_role_column'], 10, 3);
        
        // Admin assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if ('users.php' !== $hook && 'user-edit.php' !== $hook && 'profile.php' !== $hook) {
            return;
        }
        
        wp_enqueue_style(
            'lilac-admin-users',
            LILAC_LEARNING_MANAGER_URL . 'assets/css/admin-users.css',
            [],
            LILAC_LEARNING_MANAGER_VERSION
        );
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
        // Main menu item
        add_menu_page(
            __('Lilac Learning Manager', 'lilac-learning-manager'),
            __('Lilac Learning', 'lilac-learning-manager'),
            'manage_options',
            'lilac-learning-manager',
            [$this, 'render_main_page'],
            'dashicons-welcome-learn-more',
            30
        );
        
        // Add dashboard as first submenu item
        add_submenu_page(
            'lilac-learning-manager',
            __('Dashboard', 'lilac-learning-manager'),
            __('Dashboard', 'lilac-learning-manager'),
            'manage_options',
            'lilac-learning-manager',
            [$this, 'render_main_page']
        );
    }
    
    /**
     * Render the main admin page
     */
    public function render_main_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Add admin header if the file exists
        $header_path = LILAC_LEARNING_MANAGER_PATH . 'admin/partials/header.php';
        if (file_exists($header_path)) {
            include_once $header_path;
        }
        
        // Main content
        echo '<div class="wrap">';
        echo '<h1>' . esc_html(get_admin_page_title()) . '</h1>';
        echo '<div class="lilac-admin-dashboard">';
        echo '<p>' . __('Welcome to Lilac Learning Manager. Use the menu to the left to navigate to different sections.', 'lilac-learning-manager') . '</p>';
        echo '</div>';
        echo '</div>';
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
