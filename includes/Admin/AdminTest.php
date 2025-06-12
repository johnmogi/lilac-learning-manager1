<?php
namespace LilacLearningManager\Admin;

/**
 * Test class for admin menu debugging
 */
class AdminTest {
    /**
     * Initialize the test
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'debug_admin_menu'], 9999);
        add_action('admin_notices', [$this, 'show_admin_notice']);
    }
    
    /**
     * Show admin notice with debug info
     */
    public function show_admin_notice() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Only show on our admin pages
        $screen = get_current_screen();
        if (strpos($screen->id, 'lilac-') === false) {
            return;
        }
        
        global $menu, $submenu;
        
        echo '<div class="notice notice-info">';
        echo '<h3>Admin Menu Debug</h3>';
        
        // Show main menu
        echo '<h4>Main Menu Items:</h4><ul>';
        foreach ($menu as $item) {
            if (!empty($item[0])) {
                echo '<li>' . esc_html($item[0]) . ' | Slug: ' . esc_html($item[2]) . ' | Capability: ' . esc_html($item[1]) . '</li>';
            }
        }
        echo '</ul>';
        
        // Show submenus
        echo '<h4>Submenu Items:</h4>';
        foreach ($submenu as $parent_slug => $items) {
            echo '<h5>Parent: ' . esc_html($parent_slug) . '</h5><ul>';
            foreach ($items as $item) {
                echo '<li>' . esc_html($item[0]) . ' | Slug: ' . esc_html($item[2]) . ' | Capability: ' . esc_html($item[1]) . '</li>';
            }
            echo '</ul>';
        }
        
        echo '</div>';
    }
    
    /**
     * Debug admin menu structure
     */
    public function debug_admin_menu() {
        global $menu, $submenu;
        
        // Log the main menu
        error_log('=== MAIN MENU ITEMS ===');
        foreach ($menu as $item) {
            if (!empty($item[0])) {
                error_log(sprintf(
                    'Menu: %s | Slug: %s | Capability: %s',
                    $item[0],
                    $item[2],
                    $item[1]
                ));
            }
        }
        
        // Log submenus
        error_log('=== SUBMENU ITEMS ===');
        foreach ($submenu as $parent_slug => $items) {
            error_log('Parent: ' . $parent_slug);
            foreach ($items as $item) {
                error_log(sprintf(
                    '  - %s | Slug: %s | Capability: %s',
                    $item[0],
                    $item[2],
                    $item[1]
                ));
            }
        }
    }
}

// Only load in admin
if (is_admin()) {
    new AdminTest();
}
