<?php
// Security check - exit if accessed directly
defined('ABSPATH') || exit;

// Get the current action
$action = isset($_REQUEST['action']) ? sanitize_text_field($_REQUEST['action']) : '';

// Handle bulk actions
if ($action === 'delete' && !empty($_REQUEST['school_ids'])) {
    // Handle bulk delete
    $deleted = 0;
    $school_ids = array_map('intval', (array)$_REQUEST['school_ids']);
    
    foreach ($school_ids as $school_id) {
        if (current_user_can('delete_post', $school_id)) {
            wp_delete_post($school_id, true);
            $deleted++;
        }
    }
    
    if ($deleted > 0) {
        echo '<div class="notice notice-success"><p>';
        printf(
            _n('%s school deleted successfully.', '%s schools deleted successfully.', $deleted, 'lilac-learning-manager'),
            number_format_i18n($deleted)
        );
        echo '</p></div>';
    }
}

// Prepare the schools list table
$schools_table = new LLM_Schools_List_Table();
$schools_table->prepare_items();
?>

<div class="wrap">
    <h2><?php esc_html_e('Manage Schools', 'lilac-learning-manager'); ?></h2>
    
    <form id="schools-filter" method="get">
        <input type="hidden" name="page" value="lilac-learning-schools" />
        <input type="hidden" name="tab" value="all" />
        
        <?php 
        // Add search box
        $schools_table->search_box(__('Search Schools', 'lilac-learning-manager'), 'school-search-input');
        
        // Display the list table
        $schools_table->display(); 
        ?>
    </form>
</div>

<?php
// Custom List Table class for Schools
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class LLM_Schools_List_Table extends WP_List_Table {
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct([
            'singular' => 'school',
            'plural'   => 'schools',
            'ajax'     => false,
        ]);
    }
    
    /**
     * Get the columns
     */
    public function get_columns() {
        return [
            'cb'           => '<input type="checkbox" />',
            'title'        => __('Name', 'lilac-learning-manager'),
            'code'         => __('School Code', 'lilac-learning-manager'),
            'type'         => __('Type', 'lilac-learning-manager'),
            'students'     => __('Students', 'lilac-learning-manager'),
            'date_created' => __('Date Created', 'lilac-learning-manager'),
        ];
    }
    
    /**
     * Get sortable columns
     */
    protected function get_sortable_columns() {
        return [
            'title'        => ['title', false],
            'code'         => ['code', false],
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
                        'school_ids[]' => $item->ID,
                    ]),
                    'bulk-schools'
                );
                
                $actions = [
                    'edit' => sprintf('<a href="%s">%s</a>', $edit_link, __('Edit', 'lilac-learning-manager')),
                    'delete' => sprintf('<a href="%s" class="submitdelete" onclick="return confirm(\'%s\')">%s</a>', 
                        $delete_link, 
                        esc_js(__('Are you sure you want to delete this school?', 'lilac-learning-manager')), 
                        __('Delete', 'lilac-learning-manager')
                    ),
                ];
                
                return sprintf(
                    '<strong><a href="%s" class="row-title">%s</a></strong>%s',
                    $edit_link,
                    get_the_title($item->ID),
                    $this->row_actions($actions)
                );
                
            case 'code':
                return get_post_meta($item->ID, '_llm_school_code', true);
                
            case 'type':
                $types = get_the_terms($item->ID, 'llm_school_type');
                if ($types && !is_wp_error($types)) {
                    $type_links = [];
                    foreach ($types as $type) {
                        $type_links[] = $type->name;
                    }
                    return implode(', ', $type_links);
                }
                return '—';
                
            case 'students':
                $student_count = 0; // TODO: Get actual student count
                return number_format_i18n($student_count);
                
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
            '<input type="checkbox" name="school_ids[]" value="%s" />', 
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
        $per_page = $this->get_items_per_page('schools_per_page', 20);
        
        $args = [
            'post_type'      => 'llm_school',
            'posts_per_page' => $per_page,
            'paged'          => $paged,
            'orderby'        => isset($_REQUEST['orderby']) ? sanitize_text_field($_REQUEST['orderby']) : 'date',
            'order'          => isset($_REQUEST['order']) ? sanitize_text_field($_REQUEST['order']) : 'DESC',
        ];
        
        // Handle search
        if (!empty($_REQUEST['s'])) {
            $args['s'] = sanitize_text_field($_REQUEST['s']);
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
        _e('No schools found.', 'lilac-learning-manager');
    }
}
?>

<style>
.lilac-learning-manager-schools .tablenav.top { margin-bottom: 15px; }
.lilac-learning-manager-schools .tablenav-pages { float: right; }
.lilac-learning-manager-schools .search-box { float: right; margin: 0 0 10px 10px; }
.lilac-learning-manager-schools .tablenav .actions { margin-right: 10px; }
.lilac-learning-manager-schools .wp-list-table { clear: both; }
</style>
