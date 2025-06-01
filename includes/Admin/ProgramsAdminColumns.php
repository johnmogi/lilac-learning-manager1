<?php
/**
 * Programs Admin Columns
 *
 * @package LilacLearningManager\Admin
 */

namespace LilacLearningManager\Admin;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ProgramsAdminColumns
 *
 * Handles custom admin columns for the Programs taxonomy.
 */
class ProgramsAdminColumns {
    /**
     * The taxonomy slug.
     *
     * @var string
     */
    private $taxonomy = 'llm_program';

    /**
     * Constructor.
     */
    public function __construct() {
        // Add custom columns
        add_filter("manage_edit-{$this->taxonomy}_columns", [$this, 'add_columns']);
        add_filter("manage_{$this->taxonomy}_custom_column", [$this, 'render_columns'], 10, 3);
        
        // Make columns sortable
        add_filter("manage_edit-{$this->taxonomy}_sortable_columns", [$this, 'make_columns_sortable']);
        
        // Add custom filters
        add_action('restrict_manage_posts', [$this, 'add_program_filters'], 10, 2);
        
        // Handle bulk actions
        add_filter('bulk_actions-edit-sfwd-courses', [$this, 'register_bulk_actions']);
        add_filter('handle_bulk_actions-edit-sfwd-courses', [$this, 'handle_bulk_actions'], 10, 3);
        
        // Add admin notices for bulk actions
        add_action('admin_notices', [$this, 'bulk_action_notices']);
    }

    /**
     * Add custom columns to the Programs list table.
     *
     * @param array $columns The existing columns.
     * @return array Modified columns.
     */
    public function add_columns($columns) {
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
     * Render custom column content.
     *
     * @param string $content     The column content.
     * @param string $column_name The column name.
     * @param int    $term_id     The term ID.
     * @return string The column content.
     */
    public function render_columns($content, $column_name, $term_id) {
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

    /**
     * Make columns sortable.
     *
     * @param array $columns The sortable columns.
     * @return array Modified sortable columns.
     */
    public function make_columns_sortable($columns) {
        $columns['courses'] = 'count';
        return $columns;
    }

    /**
     * Add program filters to the Courses list table.
     *
     * @param string $post_type The post type.
     * @param string $which     The location of the filters ('top' or 'bottom').
     */
    public function add_program_filters($post_type, $which) {
        if ('sfwd-courses' !== $post_type) {
            return;
        }
        
        $programs = get_terms([
            'taxonomy' => $this->taxonomy,
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);
        
        if (empty($programs) || is_wp_error($programs)) {
            return;
        }
        
        $selected = isset($_GET[$this->taxonomy]) ? sanitize_text_field($_GET[$this->taxonomy]) : '';
        
        echo '<select name="' . esc_attr($this->taxonomy) . '" id="filter-by-program" class="postform">';
        echo '<option value="">' . esc_html__('All Programs', 'lilac-learning-manager') . '</option>';
        
        foreach ($programs as $program) {
            printf(
                '<option value="%s"%s>%s (%s)</option>',
                esc_attr($program->slug),
                selected($selected, $program->slug, false),
                esc_html($program->name),
                number_format_i18n($program->count)
            );
        }
        
        echo '</select>';
    }

    /**
     * Register bulk actions for the Courses list table.
     *
     * @param array $actions The existing bulk actions.
     * @return array Modified bulk actions.
     */
    public function register_bulk_actions($actions) {
        $programs = get_terms([
            'taxonomy' => $this->taxonomy,
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);
        
        if (!empty($programs) && !is_wp_error($programs)) {
            $actions['llm_assign_program'] = __('Assign to Program', 'lilac-learning-manager');
            $actions['llm_remove_program'] = __('Remove from Program', 'lilac-learning-manager');
        }
        
        return $actions;
    }

    /**
     * Handle bulk actions.
     *
     * @param string $redirect_to The redirect URL.
     * @param string $doaction    The action being taken.
     * @param array  $post_ids    The post IDs to process.
     * @return string The modified redirect URL.
     */
    public function handle_bulk_actions($redirect_to, $doaction, $post_ids) {
        if (!in_array($doaction, ['llm_assign_program', 'llm_remove_program'], true)) {
            return $redirect_to;
        }
        
        // Check nonce and user capabilities
        if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], 'bulk-posts')) {
            return $redirect_to;
        }
        
        if (!current_user_can('edit_posts')) {
            return $redirect_to;
        }
        
        // Get the program ID from the form
        $program_id = isset($_REQUEST['llm_program_id']) ? intval($_REQUEST['llm_program_id']) : 0;
        if (!$program_id) {
            return add_query_arg('llm_message', 'no_program_selected', $redirect_to);
        }
        
        $program = get_term($program_id, $this->taxonomy);
        if (!$program || is_wp_error($program)) {
            return add_query_arg('llm_message', 'invalid_program', $redirect_to);
        }
        
        $updated = 0;
        
        foreach ($post_ids as $post_id) {
            if ('llm_assign_program' === $doaction) {
                $result = wp_set_object_terms($post_id, $program->term_id, $this->taxonomy, true);
            } else {
                $result = wp_remove_object_terms($post_id, $program->term_id, $this->taxonomy);
            }
            
            if (!is_wp_error($result)) {
                $updated++;
            }
        }
        
        $message = ('llm_assign_program' === $doaction) ? 'programs_assigned' : 'programs_removed';
        
        return add_query_arg(
            [
                'llm_message' => $message,
                'updated' => $updated,
                'program_id' => $program_id,
            ],
            $redirect_to
        );
    }

    /**
     * Display admin notices for bulk actions.
     */
    public function bulk_action_notices() {
        if (empty($_REQUEST['llm_message'])) {
            return;
        }
        
        $message = '';
        $type = 'updated';
        
        switch ($_REQUEST['llm_message']) {
            case 'no_program_selected':
                $message = __('Please select a program.', 'lilac-learning-manager');
                $type = 'error';
                break;
                
            case 'invalid_program':
                $message = __('The selected program is invalid.', 'lilac-learning-manager');
                $type = 'error';
                break;
                
            case 'programs_assigned':
                $updated = isset($_REQUEST['updated']) ? intval($_REQUEST['updated']) : 0;
                $program_id = isset($_REQUEST['program_id']) ? intval($_REQUEST['program_id']) : 0;
                $program = get_term($program_id, $this->taxonomy);
                $program_name = $program && !is_wp_error($program) ? $program->name : __('Program', 'lilac-learning-manager');
                
                $message = sprintf(
                    /* translators: 1: Number of courses, 2: Program name */
                    _n(
                        'Assigned %1$d course to %2$s.',
                        'Assigned %1$d courses to %2$s.',
                        $updated,
                        'lilac-learning-manager'
                    ),
                    $updated,
                    $program_name
                );
                break;
                
            case 'programs_removed':
                $updated = isset($_REQUEST['updated']) ? intval($_REQUEST['updated']) : 0;
                $program_id = isset($_REQUEST['program_id']) ? intval($_REQUEST['program_id']) : 0;
                $program = get_term($program_id, $this->taxonomy);
                $program_name = $program && !is_wp_error($program) ? $program->name : __('Program', 'lilac-learning-manager');
                
                $message = sprintf(
                    /* translators: 1: Number of courses, 2: Program name */
                    _n(
                        'Removed %1$d course from %2$s.',
                        'Removed %1$d courses from %2$s.',
                        $updated,
                        'lilac-learning-manager'
                    ),
                    $updated,
                    $program_name
                );
                break;
        }
        
        if (!empty($message)) {
            printf(
                '<div class="%s notice is-dismissible"><p>%s</p></div>',
                esc_attr($type),
                esc_html($message)
            );
        }
    }
}

// The class is initialized in the main plugin file
