<?php
/**
 * Plugin Name: Lilac Learning Manager
 * Plugin URI:  https://yourwebsite.com/lilac-learning-manager
 * Description: A powerful LearnDash extension for managing learning content with advanced features.
 * Version:     1.0.0
 * Author:      Your Name
 * Author URI:  https://yourwebsite.com
 * Text Domain: lilac-learning-manager
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * 
 * @package LilacLearningManager
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Load the early shortcode immediately
require_once plugin_dir_path( __FILE__ ) . 'includes/course-sidebar/early-shortcode.php';
error_log('Main Plugin: Early shortcode file loaded');

// Define plugin constants
define( 'LILAC_LEARNING_MANAGER_VERSION', '1.0.0' );
define( 'LILAC_LEARNING_MANAGER_PATH', plugin_dir_path( __FILE__ ) );
define( 'LILAC_LEARNING_MANAGER_URL', plugin_dir_url( __FILE__ ) );
define( 'LILAC_LEARNING_MANAGER_BASENAME', plugin_basename( __FILE__ ) );

// Autoloader
spl_autoload_register( function( $class ) {
    $prefix = 'LilacLearningManager\\';
    $base_dirs = [
        LILAC_LEARNING_MANAGER_PATH . 'includes/',
        LILAC_LEARNING_MANAGER_PATH . 'admin/'
    ];
    
    $len = strlen( $prefix );
    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        return;
    }
    
    $relative_class = substr( $class, $len );
    
    foreach ($base_dirs as $base_dir) {
        $file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';
        if ( file_exists( $file ) ) {
            require $file;
            return;
        }
    }
} );

// Define plugin version if not already defined
if (!defined('LILAC_LEARNING_MANAGER_VERSION')) {
    define('LILAC_LEARNING_MANAGER_VERSION', '1.0.0');
}

// Initialize the plugin
function lilac_learning_manager_init() {
    // Check if LearnDash is active
    if (!class_exists('SFWD_LMS')) {
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-error">
                <p><?php _e('Lilac Learning Manager requires LearnDash LMS to be installed and activated.', 'lilac-learning-manager'); ?></p>
            </div>
            <?php
        });
        return;
    }

    // Set up text domain for translations
    add_action('init', function() {
        // First try to load from WordPress languages directory
        $locale = apply_filters('plugin_locale', determine_locale(), 'lilac-learning-manager');
        $mofile = WP_LANG_DIR . '/plugins/lilac-learning-manager-' . $locale . '.mo';
        
        if (file_exists($mofile)) {
            load_textdomain('lilac-learning-manager', $mofile);
        } else {
            // Fallback to plugin languages directory
            load_plugin_textdomain(
                'lilac-learning-manager',
                false,
                dirname(plugin_basename(__FILE__)) . '/languages/'
            );
        }
    }, 5); // Priority 5 to load translations early but after other plugins
    
    // Add RTL stylesheet if needed
    add_action('admin_enqueue_scripts', function() {
        if (is_rtl()) {
            wp_enqueue_style(
                'lilac-learning-manager-rtl',
                plugins_url('assets/css/admin-rtl.css', __FILE__),
                array(),
                LILAC_LEARNING_MANAGER_VERSION
            );
        }
    });

    // Initialize the main plugin class
    $plugin = new \LilacLearningManager\Core\Plugin();
    $plugin->run();
    
    // Initialize Program Taxonomy
    if (class_exists('LilacLearningManager\\Taxonomies\\ProgramTaxonomy')) {
        new \LilacLearningManager\Taxonomies\ProgramTaxonomy();
    }
    
    // Initialize Subscription System
    if (class_exists('LilacLearningManager\\Subscriptions\\Subscription_Bootstrap')) {
        // Use the new bootstrap class to initialize the entire subscription system
        $subscription_bootstrap = new \LilacLearningManager\Subscriptions\Subscription_Bootstrap();
        
        // Initialize Admin
        if (is_admin() && class_exists('LilacLearningManager\\Subscriptions\\Subscription_Admin')) {
            new \LilacLearningManager\Subscriptions\Subscription_Admin();
        }
        
        // AJAX handlers are now initialized by the bootstrap class
        // No need to manually initialize AJAX handlers here as they're handled by the bootstrap
        
        // Initialize Shortcodes
        // Shortcodes are now initialized by the bootstrap class
        // No need to manually initialize shortcodes here
        
        // Add documentation link to plugin action links
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
            $docs_link = '<a href="' . admin_url('admin.php?page=lilac-subscriptions') . '">' . __('Documentation', 'lilac-learning-manager') . '</a>';
            array_unshift($links, $docs_link);
            return $links;
        });
    }
    
    // Initialize Thank You Settings if WooCommerce and LearnDash are active
    add_action('admin_menu', function() {
        if (class_exists('WooCommerce') && class_exists('SFWD_LMS') && is_admin()) {
            if (class_exists('LilacLearningManager\\ThankYou\\Thank_You_Settings')) {
                try {
                    new \LilacLearningManager\ThankYou\Thank_You_Settings();
                } catch (Exception $e) {
                    // Log error but don't break the admin
                    error_log('Failed to initialize Thank You Settings: ' . $e->getMessage());
                    
                    // Show admin notice if we're on an admin page
                    if (is_admin()) {
                        add_action('admin_notices', function() use ($e) {
                            ?>
                            <div class="notice notice-error">
                                <p><?php 
                                    printf(
                                        __('Error initializing Thank You Settings: %s', 'lilac-learning-manager'), 
                                        esc_html($e->getMessage())
                                    ); 
                                ?></p>
                            </div>
                            <?php
                        });
                    }
                }
            } else {
                error_log('Thank_You_Settings class not found');
            }
        } else {
            error_log('WooCommerce or LearnDash not active');
        }
    }, 20);
    
    // Initialize Programs Admin
    if (class_exists('LilacLearningManager\\Admin\\ProgramsAdmin') && !did_action('lilac_learning_manager_programs_admin_init')) {
        new \LilacLearningManager\Admin\ProgramsAdmin();
        do_action('lilac_learning_manager_programs_admin_init');
    }
    
    // Initialize Programs Meta Box
    if (class_exists('LilacLearningManager\\Admin\\ProgramsMetaBox') && !did_action('lilac_learning_manager_programs_metabox_init')) {
        new \LilacLearningManager\Admin\ProgramsMetaBox();
        do_action('lilac_learning_manager_programs_metabox_init');
    }
    
    // Initialize Admin Test (for debugging)
    if (defined('WP_DEBUG') && WP_DEBUG) {
        if (class_exists('LilacLearningManager\\Admin\\AdminTest')) {
            new \LilacLearningManager\Admin\AdminTest();
        }
        if (class_exists('LilacLearningManager\\Admin\\MenuDebugPage')) {
            new \LilacLearningManager\Admin\MenuDebugPage();
        }
    }
    
    // Initialize Programs Admin Columns
    if (class_exists('LilacLearningManager\\Admin\\ProgramsAdminColumns')) {
        new \LilacLearningManager\Admin\ProgramsAdminColumns();
    }
    
    // Initialize Programs Export/Import
    if (class_exists('LilacLearningManager\\Admin\\ProgramsExportImport')) {
        new \LilacLearningManager\Admin\ProgramsExportImport();
    }
    
    // Initialize Admin Menu
    if (is_admin() && class_exists('LilacLearningManager\\Admin\\Admin_Menu')) {
        new \LilacLearningManager\Admin\Admin_Menu(
            'Lilac Learning Manager',
            LILAC_LEARNING_MANAGER_VERSION
        );
    }
}

// Hook the initialization function
add_action('plugins_loaded', 'lilac_learning_manager_init');

/**
 * Plugin activation function.
 * Creates default programs and sets up initial options.
 */
function lilac_learning_manager_activate() {
    // Initialize user roles
    require_once(plugin_dir_path(__FILE__) . 'includes/Core/Roles.php');
    LilacLearningManager\Core\Roles::register_roles();
    
    // Create default programs if they don't exist
    $default_programs = [
        [
            'name'        => __('Hebrew', 'lilac-learning-manager'),
            'slug'        => 'hebrew',
            'description' => __('Hebrew language program', 'lilac-learning-manager'),
            'color'       => '#1e73be',
        ],
        [
            'name'        => __('English', 'lilac-learning-manager'),
            'slug'        => 'english',
            'description' => __('English language program', 'lilac-learning-manager'),
            'color'       => '#dd3333',
        ],
        [
            'name'        => __('Spanish', 'lilac-learning-manager'),
            'slug'        => 'spanish',
            'description' => __('Spanish language program', 'lilac-learning-manager'),
            'color'       => '#8224e3',
        ],
    ];
    
    foreach ($default_programs as $program) {
        if (!term_exists($program['slug'], 'llm_program')) {
            $term = wp_insert_term(
                $program['name'],
                'llm_program',
                [
                    'description' => $program['description'],
                    'slug'        => $program['slug'],
                ]
            );
            
            if (!is_wp_error($term)) {
                update_term_meta($term['term_id'], 'program_color', $program['color']);
                update_term_meta($term['term_id'], 'program_icon', 'dashicons-translation');
                update_term_meta($term['term_id'], 'program_featured', '1');
                update_term_meta($term['term_id'], 'program_visibility', 'public');
            }
        }
    }
    
    // Set default options
    $default_options = [
        'llm_program_archive_title'    => __('Programs', 'lilac-learning-manager'),
        'llm_program_show_filter'      => '1',
        'llm_program_default_color'    => '#2271b1',
        'llm_program_featured_color'   => '#ffb900',
    ];
    
    foreach ($default_options as $option => $value) {
        if (get_option($option) === false) {
            add_option($option, $value);
        }
    }
    
    // Flush rewrite rules on next page load
    set_transient('lilac_learning_manager_flush_rewrite_rules', true);
}

/**
 * Plugin deactivation function.
 * Cleans up scheduled hooks and flushes rewrite rules.
 */
function lilac_learning_manager_deactivate() {
    // Clear any scheduled hooks
    wp_clear_scheduled_hook('lilac_learning_manager_daily_cleanup');
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Plugin uninstall function.
 * Removes all plugin data when the plugin is uninstalled.
 */
function lilac_learning_manager_uninstall() {
    // Remove roles
    require_once(plugin_dir_path(__FILE__) . 'includes/Core/Roles.php');
    
    // Get all WordPress roles
    $wp_roles = wp_roles();
    $roles = LilacLearningManager\Core\Roles::get_roles();
    
    // Remove custom roles
    foreach (array_keys($roles) as $role) {
        if (isset($wp_roles->roles[$role])) {
            remove_role($role);
        }
    }
    
    // Delete all program terms and their meta
    $programs = get_terms([
        'taxonomy'   => 'llm_program',
        'hide_empty' => false,
        'fields'     => 'ids',
    ]);
    
    if (!is_wp_error($programs)) {
        foreach ($programs as $program_id) {
            wp_delete_term($program_id, 'llm_program');
            
            // Delete all meta for this term
            global $wpdb;
            $wpdb->delete(
                $wpdb->termmeta,
                ['term_id' => $program_id],
                ['%d']
            );
        }
    }
    
    // Delete options
    $options = [
        'llm_program_archive_title',
        'llm_program_show_filter',
        'llm_program_default_color',
        'llm_program_featured_color',
    ];
    
    foreach ($options as $option) {
        delete_option($option);
    }
    
    // Delete transients
    delete_transient('lilac_learning_manager_flush_rewrite_rules');
}

// Register activation, deactivation, and uninstall hooks
register_activation_hook(__FILE__, 'lilac_learning_manager_activate');
register_deactivation_hook(__FILE__, 'lilac_learning_manager_deactivate');
register_uninstall_hook(__FILE__, 'lilac_learning_manager_uninstall');
