<?php
namespace LilacLearningManager\ThankYou;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles manual subscription activation functionality
 */
class Manual_Subscription {
    /**
     * Activation meta key
     */
    const META_KEY_NEEDS_ACTIVATION = '_needs_subscription_activation';

    /**
     * Activation page slug
     */
    const ACTIVATION_PAGE_SLUG = 'activate-subscription';

    /**
     * @var Manual_Subscription_Admin
     */
    private $admin;

    /**
     * @var Manual_Subscription_Frontend
     */
    private $frontend;
    
    /**
     * @var Manual_Subscription_Interceptor
     */
    private $interceptor;

    /**
     * Initialize the plugin
     */
    public function __construct() {
        // Load dependencies
        $this->load_dependencies();
        
        // Initialize components
        if (is_admin()) {
            $this->admin = new Manual_Subscription_Admin();
        }
        
        $this->frontend = new Manual_Subscription_Frontend();
        
        // Initialize interceptor - CRUCIAL for preventing automatic course redirect
        $this->interceptor = new Manual_Subscription_Interceptor();
        
        // Register activation hook
        register_activation_hook(LILAC_LEARNING_MANAGER_FILE, [$this, 'activation']);
    }
    
    /**
     * Load required files
     */
    private function load_dependencies() {
        $path = plugin_dir_path(__FILE__);
        
        require_once $path . 'class-manual-subscription-admin.php';
        require_once $path . 'class-manual-subscription-frontend.php';
        require_once $path . 'class-manual-subscription-interceptor.php';
    }
    
    /**
     * Plugin activation
     */
    public function activation() {
        // Create activation page if it doesn't exist
        $this->create_activation_page();
    }
    
    /**
     * Create the activation page if it doesn't exist
     */
    private function create_activation_page() {
        $page_exists = get_page_by_path(self::ACTIVATION_PAGE_SLUG);
        
        if (!$page_exists) {
            wp_insert_post([
                'post_title'     => __('Activate Subscription', 'lilac-learning-manager'),
                'post_name'      => self::ACTIVATION_PAGE_SLUG,
                'post_content'   => '[lilac_activate_subscription]',
                'post_status'    => 'publish',
                'post_type'      => 'page',
                'comment_status' => 'closed',
                'ping_status'    => 'closed',
            ]);
        }
    }
}
