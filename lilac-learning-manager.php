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

// Define plugin constants
define( 'LILAC_LEARNING_MANAGER_VERSION', '1.0.0' );
define( 'LILAC_LEARNING_MANAGER_PATH', plugin_dir_path( __FILE__ ) );
define( 'LILAC_LEARNING_MANAGER_URL', plugin_dir_url( __FILE__ ) );
define( 'LILAC_LEARNING_MANAGER_BASENAME', plugin_basename( __FILE__ ) );

// Autoloader
spl_autoload_register( function( $class ) {
    $prefix = 'LilacLearningManager\\';
    $base_dir = LILAC_LEARNING_MANAGER_PATH . 'includes/';
    
    $len = strlen( $prefix );
    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        return;
    }
    
    $relative_class = substr( $class, $len );
    $file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';
    
    if ( file_exists( $file ) ) {
        require $file;
    }
} );

// Initialize the plugin
function lilac_learning_manager_init() {
    // Check if LearnDash is active
    if ( ! class_exists( 'SFWD_LMS' ) ) {
        add_action( 'admin_notices', function() {
            ?>
            <div class="notice notice-error">
                <p><?php _e( 'Lilac Learning Manager requires LearnDash LMS to be installed and activated.', 'lilac-learning-manager' ); ?></p>
            </div>
            <?php
        } );
        return;
    }

    // Initialize the main plugin class
    $plugin = new \LilacLearningManager\Core\Plugin();
    $plugin->run();
}

// Hook the initialization function
add_action( 'plugins_loaded', 'lilac_learning_manager_init' );

// Activation and deactivation hooks
register_activation_hook( __FILE__, function() {
    require_once LILAC_LEARNING_MANAGER_PATH . 'includes/Core/Activator.php';
    \LilacLearningManager\Core\Activator::activate();
} );

register_deactivation_hook( __FILE__, function() {
    require_once LILAC_LEARNING_MANAGER_PATH . 'includes/Core/Deactivator.php';
    \LilacLearningManager\Core\Deactivator::deactivate();
} );
