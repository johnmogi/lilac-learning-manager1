<?php
namespace LilacLearningManager\Core;

class Deactivator {
    /**
     * Plugin deactivation hook.
     */
    public static function deactivate() {
        // Verify user capabilities
        if (!current_user_can('activate_plugins')) {
            return;
        }

        // Clean up scheduled events
        self::clear_scheduled_events();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Clear any scheduled events created by the plugin.
     */
    private static function clear_scheduled_events() {
        // Example: Clear scheduled hooks
        // $timestamp = wp_next_scheduled('lilac_daily_event');
        // if ($timestamp) {
        //     wp_unschedule_event($timestamp, 'lilac_daily_event');
        // }
    }
}
