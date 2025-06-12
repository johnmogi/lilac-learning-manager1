<?php
namespace LilacLearningManager\Subscriptions;

/**
 * Bootstrap class for the subscription system
 * Initializes and connects all subscription components
 */
class Subscription_Bootstrap {
    /**
     * @var Subscription_Manager
     */
    private $subscription_manager;
    
    /**
     * @var Subscription_Types
     */
    private $subscription_types;
    
    /**
     * @var Access_Controller
     */
    private $access_controller;
    
    /**
     * @var Course_Meta
     */
    private $course_meta;
    
    /**
     * @var Database
     */
    private $database;
    
    /**
     * @var Subscription_UI
     */
    private $subscription_ui;
    
    /**
     * @var Subscription_Ajax
     */
    private $subscription_ajax;
    
    /**
     * @var Subscription_Shortcodes
     */
    private $subscription_shortcodes;
    
    /**
     * @var WooCommerce_Integration
     */
    private $woocommerce_integration;
    
    /**
     * Initialize the subscription system
     */
    public function __construct() {
        // Initialize components in dependency order
        $this->init_components();
        
        // Set up WordPress hooks
        $this->setup_hooks();
    }
    
    /**
     * Initialize subscription components
     */
    private function init_components() {
        // Initialize subscription types first (no dependencies)
        $this->subscription_types = new Subscription_Types();
        
        // Initialize database (depends on subscription types)
        $this->database = new Database($this->subscription_types);
        
        // Initialize course meta (depends on subscription types)
        $this->course_meta = new Course_Meta($this->subscription_types);
        
        // Initialize subscription manager (depends on database)
        $this->subscription_manager = new Subscription_Manager();
        
        // Initialize access controller (depends on subscription types and database)
        $this->access_controller = new Access_Controller(
            $this->subscription_types,
            $this->subscription_manager
        );
        
        // Initialize subscription UI (depends on subscription manager, types, and access controller)
        $this->subscription_ui = new Subscription_UI(
            $this->subscription_manager,
            $this->subscription_types,
            $this->access_controller
        );
        
        // Initialize subscription AJAX (depends on subscription manager, types, and access controller)
        $this->subscription_ajax = new Subscription_Ajax(
            $this->subscription_manager,
            $this->subscription_types,
            $this->access_controller
        );
        
        // Initialize subscription shortcodes (depends on subscription manager, types, and UI)
        if (class_exists('LilacLearningManager\\Subscriptions\\Subscription_Shortcodes')) {
            $this->subscription_shortcodes = new Subscription_Shortcodes(
                $this->subscription_manager,
                $this->subscription_types,
                $this->subscription_ui
            );
        }
        
        // Initialize WooCommerce integration if WooCommerce is active
        if (class_exists('WooCommerce') && class_exists('LilacLearningManager\\Subscriptions\\WooCommerce_Integration')) {
            $this->woocommerce_integration = new WooCommerce_Integration(
                $this->subscription_manager,
                $this->subscription_types,
                $this->subscription_ui,
                $this->course_meta
            );
        }
    }
    
    /**
     * Set up WordPress hooks
     */
    private function setup_hooks() {
        // Run database setup on plugin activation
        register_activation_hook(LILAC_LEARNING_MANAGER_FILE, [$this->database, 'create_tables']);
        
        // Schedule daily cron for subscription expiration check
        add_action('wp', [$this, 'schedule_expiration_check']);
        
        // Add admin notices for database migration
        add_action('admin_notices', [$this, 'migration_notice']);
        
        // Handle database migration
        add_action('admin_post_lilac_run_subscription_migration', [$this->database, 'run_migration']);
    }
    
    /**
     * Schedule daily cron for subscription expiration check
     */
    public function schedule_expiration_check() {
        if (!wp_next_scheduled('lilac_daily_subscription_check')) {
            wp_schedule_event(time(), 'daily', 'lilac_daily_subscription_check');
        }
    }
    
    /**
     * Display admin notice for database migration
     */
    public function migration_notice() {
        // Only show to admins
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Check if migration is needed
        if (!$this->database->needs_migration()) {
            return;
        }
        
        // Display migration notice
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong><?php _e('Lilac Learning Manager - Subscription System Update', 'lilac-learning-manager'); ?></strong>
            </p>
            <p>
                <?php _e('The subscription system has been updated and requires a database migration to work properly.', 'lilac-learning-manager'); ?>
                <?php _e('This will move existing subscription data to the new format.', 'lilac-learning-manager'); ?>
            </p>
            <p>
                <a href="<?php echo esc_url(admin_url('admin-post.php?action=lilac_run_subscription_migration')); ?>" 
                   class="button button-primary">
                    <?php _e('Run Migration', 'lilac-learning-manager'); ?>
                </a>
            </p>
        </div>
        <?php
    }
}
