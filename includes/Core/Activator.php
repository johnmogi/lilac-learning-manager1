<?php
namespace LilacLearningManager\Core;

class Activator {
    /**
     * Plugin activation hook.
     */
    public static function activate() {
        // Verify user capabilities
        if (!current_user_can('activate_plugins')) {
            return;
        }

        // Create necessary database tables
        self::create_tables();
        
        // Set default options
        self::set_default_options();
        
        // Flush rewrite rules on next init
        flush_rewrite_rules();
    }

    /**
     * Create necessary database tables.
     */
    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Example table creation
        $sql = [];
        
        // Add your custom tables here
        // $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}lilac_custom_table (...)";
        
        // Execute SQL queries
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        foreach ($sql as $query) {
            dbDelta($query);
        }
    }

    /**
     * Set default plugin options.
     */
    private static function set_default_options() {
        $defaults = [
            'version' => LILAC_LEARNING_MANAGER_VERSION,
            // Add more default options here
        ];
        
        update_option('lilac_learning_manager_settings', $defaults);
    }
}
