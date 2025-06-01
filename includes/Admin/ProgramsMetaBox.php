<?php
/**
 * Programs Meta Box
 *
 * @package LilacLearningManager\Admin
 */

namespace LilacLearningManager\Admin;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ProgramsMetaBox
 *
 * Handles the meta box for assigning programs to courses.
 */
class ProgramsMetaBox {
    /**
     * The taxonomy slug.
     *
     * @var string
     */
    private $taxonomy = 'llm_program';

    /**
     * The post types to show the meta box on.
     *
     * @var array
     */
    private $post_types = ['sfwd-courses'];

    /**
     * Constructor.
     */
    public function __construct() {
        // Add meta box
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        
        // Save meta box data
        add_action('save_post', [$this, 'save_meta_box_data'], 10, 2);
        
        // Add admin scripts and styles
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // Add custom columns to the Programs list table
        add_filter("manage_edit-{$this->taxonomy}_columns", [$this, 'add_program_columns']);
        add_filter("manage_{$this->taxonomy}_custom_column", [$this, 'render_program_columns'], 10, 3);
    }

    /**
     * Add the meta box to the course editor.
     */
    public function add_meta_box() {
        foreach ($this->post_types as $post_type) {
            add_meta_box(
                'llm_programs_metabox',
                __('Programs', 'lilac-learning-manager'),
                [$this, 'render_meta_box'],
                $post_type,
                'side',
                'default',
                [
                    '__back_compat_meta_box' => true,
                ]
            );
        }
    }

    /**
     * Render the meta box content.
     *
     * @param \WP_Post $post The post object.
     */
    public function render_meta_box($post) {
        // Add a nonce field
        wp_nonce_field('llm_save_programs_metabox', 'llm_programs_metabox_nonce');
        
        // Get all programs
        $programs = get_terms([
            'taxonomy' => $this->taxonomy,
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);
        
        if (is_wp_error($programs) || empty($programs)) {
            echo '<p>' . esc_html__('No programs found.', 'lilac-learning-manager') . '</p>';
            
            // Add a link to create a new program
            $create_url = admin_url('edit-tags.php?taxonomy=' . $this->taxonomy . '&post_type=sfwd-courses');
            echo '<p>';
            echo '<a href="' . esc_url($create_url) . '" class="button">';
            esc_html_e('Add New Program', 'lilac-learning-manager');
            echo '</a>';
            echo '</p>';
            
            return;
        }
        
        // Get the currently assigned programs
        $assigned_programs = wp_get_object_terms($post->ID, $this->taxonomy, ['fields' => 'ids']);
        if (is_wp_error($assigned_programs)) {
            $assigned_programs = [];
        }
        
        echo '<div class="llm-programs-checkbox-container">';
        
        foreach ($programs as $program) {
            $color = get_term_meta($program->term_id, 'program_color', true);
            $color_style = $color ? 'style="background-color: ' . esc_attr($color) . ';"' : '';
            $checked = in_array($program->term_id, $assigned_programs) ? ' checked="checked"' : '';
            
            echo '<div class="llm-program-checkbox">';
            echo '<label>';
            echo '<input type="checkbox" name="llm_programs[]" value="' . esc_attr($program->term_id) . '" ' . $checked . '> ';
            echo '<span class="llm-program-color" ' . $color_style . '></span> ';
            echo '<span class="llm-program-name">' . esc_html($program->name) . '</span>';
            echo '</label>';
            echo '</div>';
        }
        
        echo '</div>';
        
        // Add a link to manage programs
        $manage_url = admin_url('edit-tags.php?taxonomy=' . $this->taxonomy . '&post_type=sfwd-courses');
        echo '<p style="margin-top: 10px;">';
        echo '<a href="' . esc_url($manage_url) . '" class="button button-small">';
        esc_html_e('Manage Programs', 'lilac-learning-manager');
        echo '</a>';
        echo '</p>';
    }

    /**
     * Save the meta box data.
     *
     * @param int     $post_id The post ID.
     * @param \WP_Post $post    The post object.
     */
    public function save_meta_box_data($post_id, $post) {
        // Check if our nonce is set and verify it.
        if (!isset($_POST['llm_programs_metabox_nonce']) || 
            !wp_verify_nonce($_POST['llm_programs_metabox_nonce'], 'llm_save_programs_metabox')) {
            return;
        }
        
        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check the user's permissions.
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Check if our post type is allowed.
        if (!in_array($post->post_type, $this->post_types, true)) {
            return;
        }
        
        // Get the submitted programs
        $programs = isset($_POST['llm_programs']) ? array_map('intval', (array) $_POST['llm_programs']) : [];
        
        // Save the programs
        wp_set_object_terms($post_id, $programs, $this->taxonomy);
    }

    /**
     * Enqueue admin scripts and styles.
     */
    public function enqueue_admin_assets($hook) {
        global $post_type;
        
        // Only load on course editor and programs list screens
        if (!in_array($hook, ['post.php', 'post-new.php', 'edit-tags.php']) || 
            ($hook === 'edit-tags.php' && isset($_GET['taxonomy']) && $_GET['taxonomy'] !== $this->taxonomy)) {
            return;
        }
        
        if (in_array($post_type, $this->post_types) || 
            (isset($_GET['post_type']) && in_array($_GET['post_type'], $this->post_types))) {
            
            // Enqueue the color picker script and style
            wp_enqueue_style('wp-color-picker');
            
            // Enqueue custom admin CSS
            wp_enqueue_style(
                'llm-admin-styles',
                plugins_url('../../assets/css/admin.css', __FILE__),
                [],
                LILAC_LEARNING_MANAGER_VERSION
            );
            
            // Enqueue custom admin JS
            wp_enqueue_script(
                'llm-admin-scripts',
                plugins_url('../../assets/js/admin.js', __FILE__),
                ['jquery', 'wp-color-picker'],
                LILAC_LEARNING_MANAGER_VERSION,
                true
            );
            
            // Localize script with translations
            wp_localize_script('llm-admin-scripts', 'llmAdminVars', [
                'confirmDelete' => __('Are you sure you want to remove this program? This will not delete the program, only remove it from this course.', 'lilac-learning-manager'),
            ]);
        }
    }

    /**
     * Add custom columns to the Programs list table.
     *
     * @param array $columns The existing columns.
     * @return array Modified columns.
     */
    public function add_program_columns($columns) {
        $new_columns = [];
        
        // Add checkbox column
        if (isset($columns['cb'])) {
            $new_columns['cb'] = $columns['cb'];
            unset($columns['cb']);
        }
        
        // Add name column
        if (isset($columns['name'])) {
            $new_columns['name'] = $columns['name'];
            unset($columns['name']);
        }
        
        // Add color column
        $new_columns['color'] = __('Color', 'lilac-learning-manager');
        
        // Add course count column
        if (isset($columns['posts'])) {
            $new_columns['courses'] = $columns['posts'];
            unset($columns['posts']);
        }
        
        // Add description column if it exists
        if (isset($columns['description'])) {
            $new_columns['description'] = $columns['description'];
            unset($columns['description']);
        }
        
        // Add slug column if it exists
        if (isset($columns['slug'])) {
            $new_columns['slug'] = $columns['slug'];
            unset($columns['slug']);
        }
        
        // Add any remaining columns
        return array_merge($new_columns, $columns);
    }

    /**
     * Render custom column content for Programs list table.
     *
     * @param string $content     The column content.
     * @param string $column_name The column name.
     * @param int    $term_id     The term ID.
     * @return string The column content.
     */
    public function render_program_columns($content, $column_name, $term_id) {
        switch ($column_name) {
            case 'color':
                $color = get_term_meta($term_id, 'program_color', true);
                if (empty($color)) {
                    $color = '#cccccc'; // Default color if none set
                }
                
                $content = sprintf(
                    '<span class="llm-program-color" style="display:inline-block;width:20px;height:20px;background-color:%s;border:1px solid #ddd;border-radius:3px;" title="%s"></span>',
                    esc_attr($color),
                    esc_attr($color)
                );
                break;
                
            case 'courses':
                $term = get_term($term_id, $this->taxonomy);
                if ($term && !is_wp_error($term)) {
                    $content = sprintf(
                        '<a href="%s">%s</a>',
                        admin_url("edit.php?post_type=sfwd-courses&{$this->taxonomy}={$term->slug}"),
                        number_format_i18n($term->count)
                    );
                }
                break;
        }
        
        return $content;
    }
}

// Initialize the meta box
function lilac_learning_manager_programs_meta_box() {
    new ProgramsMetaBox();
}
add_action('plugins_loaded', 'lilac_learning_manager_programs_meta_box');
