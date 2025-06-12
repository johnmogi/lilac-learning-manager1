<?php
namespace LilacLearningManager\Admin;

/**
 * Class to debug admin menu structure
 */
class MenuDebugPage {
    /**
     * Menu slug for the debug page
     */
    const PAGE_SLUG = 'lilac-menu-debug';
    
    /**
     * Initialize the debug page
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_debug_page']);
    }
    
    /**
     * Add debug page to the admin menu
     */
    public function add_debug_page() {
        add_submenu_page(
            'lilac-learning-manager',
            __('Menu Debug', 'lilac-learning-manager'),
            __('Menu Debug', 'lilac-learning-manager'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'render_debug_page']
        );
    }
    
    /**
     * Render the debug page
     */
    public function render_debug_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        global $menu, $submenu;
        
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Admin Menu Debug', 'lilac-learning-manager') . '</h1>';
        
        // Show main menu
        echo '<h2>Main Menu Items</h2>';
        echo '<table class="widefat fixed" cellspacing="0">';
        echo '<thead><tr>';
        echo '<th>Position</th>';
        echo '<th>Menu Title</th>';
        echo '<th>Capability</th>';
        echo '<th>Menu Slug</th>';
        echo '<th>Icon</th>';
        echo '<th>Position</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        
        foreach ($menu as $position => $item) {
            if (empty($item[0])) continue;
            
            echo '<tr>';
            echo '<td>' . esc_html($position) . '</td>';
            echo '<td>' . wp_kses_post($item[0]) . '</td>';
            echo '<td>' . esc_html($item[1]) . '</td>';
            echo '<td>' . esc_html($item[2]) . '</td>';
            echo '<td>' . esc_html($item[6] ?? '') . '</td>';
            echo '<td>' . esc_html($item[5] ?? '') . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        
        // Show submenus
        echo '<h2>Submenu Items</h2>';
        
        foreach ($submenu as $parent_slug => $items) {
            echo '<h3>Parent: ' . esc_html($parent_slug) . '</h3>';
            
            if (empty($items)) {
                echo '<p>No submenu items found.</p>';
                continue;
            }
            
            echo '<table class="widefat fixed" cellspacing="0">';
            echo '<thead><tr>';
            echo '<th>Menu Title</th>';
            echo '<th>Capability</th>';
            echo '<th>Menu Slug</th>';
            echo '<th>Hook</th>';
            echo '</tr></thead>';
            echo '<tbody>';
            
            foreach ($items as $item) {
                echo '<tr>';
                echo '<td>' . wp_kses_post($item[0]) . '</td>';
                echo '<td>' . esc_html($item[1]) . '</td>';
                echo '<td>' . esc_html($item[2]) . '</td>';
                echo '<td>' . esc_html($item[2] === $parent_slug ? 'Same as parent' : 'Different') . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        }
        
        echo '</div>'; // .wrap
    }
}

// Only load in admin
if (is_admin()) {
    new MenuDebugPage();
}
