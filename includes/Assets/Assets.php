<?php
namespace LilacLearningManager\Assets;

/**
 * Handles the registration and enqueuing of plugin assets.
 */
class Assets {
    /**
     * The plugin version.
     *
     * @var string
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @param string $version The version of this plugin.
     */
    public function __construct($version) {
        $this->version = $version;
        
        add_action('wp_enqueue_scripts', [$this, 'register_public_assets']);
        add_action('admin_enqueue_scripts', [$this, 'register_admin_assets']);
    }

    /**
     * Register public-facing styles and scripts.
     */
    public function register_public_assets() {
        // Public styles
        wp_register_style(
            'lilac-learning-manager-public',
            LILAC_LEARNING_MANAGER_URL . 'assets/css/public.css',
            [],
            $this->version,
            'all'
        );

        // Public scripts
        wp_register_script(
            'lilac-learning-manager-public',
            LILAC_LEARNING_MANAGER_URL . 'assets/js/public.js',
            ['jquery'],
            $this->version,
            true
        );

        // Localize script with AJAX URL and nonce
        wp_localize_script(
            'lilac-learning-manager-public',
            'lilacLearningManager',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('lilac_learning_manager_nonce'),
            ]
        );
    }

    /**
     * Register admin-specific styles and scripts.
     */
    public function register_admin_assets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'lilac-learning-manager') === false) {
            return;
        }

        // Admin styles
        wp_enqueue_style(
            'lilac-learning-manager-admin',
            LILAC_LEARNING_MANAGER_URL . 'assets/css/admin.css',
            ['wp-color-picker'],
            $this->version,
            'all'
        );

        // Admin scripts
        wp_enqueue_script(
            'lilac-learning-manager-admin',
            LILAC_LEARNING_MANAGER_URL . 'assets/js/admin.js',
            ['jquery', 'jquery-ui-sortable', 'wp-color-picker'],
            $this->version,
            true
        );

        // Localize script with AJAX URL and nonce
        wp_localize_script(
            'lilac-learning-manager-admin',
            'lilacLearningManagerAdmin',
            [
                'ajaxUrl'   => admin_url('admin-ajax.php'),
                'nonce'     => wp_create_nonce('lilac_learning_manager_admin_nonce'),
                'confirmDelete' => __('Are you sure you want to delete this item?', 'lilac-learning-manager'),
            ]
        );
    }

    /**
     * Enqueue public styles.
     */
    public function enqueue_public_styles() {
        wp_enqueue_style('lilac-learning-manager-public');
    }

    /**
     * Enqueue public scripts.
     */
    public function enqueue_public_scripts() {
        wp_enqueue_script('lilac-learning-manager-public');
    }

    /**
     * Enqueue admin styles.
     */
    public function enqueue_admin_styles() {
        wp_enqueue_style('lilac-learning-manager-admin');
    }

    /**
     * Enqueue admin scripts.
     */
    public function enqueue_admin_scripts() {
        wp_enqueue_script('lilac-learning-manager-admin');
    }
}
