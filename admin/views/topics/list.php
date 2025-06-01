<?php
// Security check - exit if accessed directly
defined('ABSPATH') || exit;

// Get the current action
$action = isset($_REQUEST['action']) ? sanitize_text_field($_REQUEST['action']) : '';

// Handle bulk actions
if ($action === 'delete' && !empty($_REQUEST['topic_ids'])) {
    // Handle bulk delete
    $deleted = 0;
    $topic_ids = array_map('intval', (array)$_REQUEST['topic_ids']);
    
    foreach ($topic_ids as $topic_id) {
        if (current_user_can('delete_post', $topic_id)) {
            wp_delete_post($topic_id, true);
            $deleted++;
        }
    }
    
    if ($deleted > 0) {
        echo '<div class="notice notice-success"><p>';
        printf(
            _n('%s topic deleted successfully.', '%s topics deleted successfully.', $deleted, 'lilac-learning-manager'),
            number_format_i18n($deleted)
        );
        echo '</p></div>';
    }
}

// Prepare the topics list table
$topics_table = new LLM_Topics_List_Table();
$topics_table->prepare_items();
?>

<div class="wrap">
    <h2><?php esc_html_e('Manage Topics', 'lilac-learning-manager'); ?></h2>
    
    <form id="topics-filter" method="get">
        <input type="hidden" name="page" value="lilac-learning-topics" />
        <input type="hidden" name="tab" value="all" />
        
        <?php 
        // Add search box
        $topics_table->search_box(__('Search Topics', 'lilac-learning-manager'), 'topic-search-input');
        
        // Display the list table
        $topics_table->display(); 
        ?>
    </form>
</div>

<?php
// Custom List Table class for Topics
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class LLM_Topics_List_Table extends WP_List_Table {
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct([
            'singular' => 'topic',
            'plural'   => 'topics',
            'ajax'     => false,
        ]);
    }
    
    /**
     * Get the columns
     */
    public function get_columns() {
        return [
            'cb'           => '<input type="checkbox" />',
            'title'        => __('Title', 'lilac-learning-manager'),
            'categories'   => __('Categories', 'lilac-learning-manager'),
            'difficulty'   => __('Difficulty', 'lilac-learning-manager'),
            'questions'    => __('Questions', 'lilac-learning-manager'),
            'date_created' => __('Date Created', 'lilac-learning-manager'),
        ];
    }
    
    /**
     * Get sortable columns
     */
    protected function get_sortable_columns() {
        return [
            'title'        => ['title', false],
            'date_created' => ['date', true],
        ];
    }
    
    /**
     * Column default
     */
    protected function column_default($item, $column_name) {
        switch ($column_name) {
            case 'title':
                $edit_link = get_edit_post_link($item->ID);
                $delete_link = wp_nonce_url(
                    add_query_arg([
                        'action' => 'delete',
                        'topic_ids[]' => $item->ID,
                    ]),
                    'bulk-topics'
                );
                
                $actions = [
                    'edit' => sprintf('<a href="%s">%s</a>', $edit_link, __('Edit', 'lilac-learning-manager')),
                    'delete' => sprintf('<a href="%s" class="submitdelete" onclick="return confirm(\'%s\')">%s</a>', 
                        $delete_link, 
                        esc_js(__('Are you sure you want to delete this topic?', 'lilac-learning-manager')), 
                        __('Delete', 'lilac-learning-manager')
                    ),
                ];
                
                return sprintf(
                    '<strong><a href="%s" class="row-title">%s</a></strong>%s',
                    $edit_link,
                    get_the_title($item->ID),
                    $this->row_actions($actions)
                );
                
            case 'categories':
                $categories = get_the_terms($item->ID, 'llm_topic_category');
                if ($categories && !is_wp_error($categories)) {
                    $category_links = [];
                    foreach ($categories as $category) {
                        $category_links[] = sprintf(
                            '<a href="%s">%s</a>',
                            esc_url(add_query_arg('llm_topic_category', $category->slug)),
                            esc_html($category->name)
                        );
                    }
                    return implode(', ', $category_links);
                }
                return '—';
                
            case 'difficulty':
                $difficulty = get_post_meta($item->ID, '_llm_topic_difficulty', true);
                $difficulty_levels = [
                    'beginner' => __('Beginner', 'lilac-learning-manager'),
                    'intermediate' => __('Intermediate', 'lilac-learning-manager'),
                    'advanced' => __('Advanced', 'lilac-learning-manager'),
                ];
                return isset($difficulty_levels[$difficulty]) ? $difficulty_levels[$difficulty] : '—';
                
            case 'questions':
                $questions = 0; // TODO: Get actual question count
                return number_format_i18n($questions);
                
            case 'date_created':
                return get_the_date('', $item->ID);
                
            default:
                return '—';
        }
    }
    
    /**
     * Column cb
     */
    protected function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="topic_ids[]" value="%s" />', 
            $item->ID
        );
    }
    
    /**
     * Get bulk actions
     */
    protected function get_bulk_actions() {
        return [
            'delete' => __('Delete', 'lilac-learning-manager'),
        ];
    }
    
    /**
     * Prepare items
     */
    public function prepare_items() {
        // Set up column headers
        $this->_column_headers = [
            $this->get_columns(),
            [], // Hidden columns
            $this->get_sortable_columns(),
        ];
        
        // Process bulk actions
        $this->process_bulk_action();
        
        // Query parameters
        $paged = isset($_REQUEST['paged']) ? max(1, intval($_REQUEST['paged'])) : 1;
        $per_page = $this->get_items_per_page('topics_per_page', 20);
        
        $args = [
            'post_type'      => 'llm_topic',
            'posts_per_page' => $per_page,
            'paged'          => $paged,
            'orderby'        => isset($_REQUEST['orderby']) ? sanitize_text_field($_REQUEST['orderby']) : 'date',
            'order'          => isset($_REQUEST['order']) ? sanitize_text_field($_REQUEST['order']) : 'DESC',
        ];
        
        // Handle search
        if (!empty($_REQUEST['s'])) {
            $args['s'] = sanitize_text_field($_REQUEST['s']);
        }
        
        // Filter by category
        if (!empty($_REQUEST['llm_topic_category'])) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'llm_topic_category',
                    'field'    => 'slug',
                    'terms'    => sanitize_text_field($_REQUEST['llm_topic_category']),
                ],
            ];
        }
        
        // Run the query
        $query = new WP_Query($args);
        
        // Set the items
        $this->items = $query->posts;
        
        // Set pagination
        $this->set_pagination_args([
            'total_items' => $query->found_posts,
            'per_page'    => $per_page,
            'total_pages' => ceil($query->found_posts / $per_page),
        ]);
    }
    
    /**
     * Display when no items are found
     */
    public function no_items() {
        _e('No topics found.', 'lilac-learning-manager');
    }
}
?>

<style>
.lilac-learning-manager-topics .tablenav.top { margin-bottom: 15px; }
.lilac-learning-manager-topics .tablenav-pages { float: right; }
.lilac-learning-manager-topics .search-box { float: right; margin: 0 0 10px 10px; }
.lilac-learning-manager-topics .tablenav .actions { margin-right: 10px; }
.lilac-learning-manager-topics .wp-list-table { clear: both; }
</style>
