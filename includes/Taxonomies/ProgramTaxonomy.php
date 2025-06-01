<?php
/**
 * Register Program Taxonomy
 *
 * @package LilacLearningManager\Includes\Taxonomies
 */

namespace LilacLearningManager\Taxonomies;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ProgramTaxonomy
 *
 * Handles the registration and management of the Program taxonomy.
 */
class ProgramTaxonomy {
    /**
     * The taxonomy slug.
     *
     * @var string
     */
    private $taxonomy = 'llm_program';

    /**
     * The post types this taxonomy is associated with.
     *
     * @var array
     */
    private $post_types = ['sfwd-courses'];

    /**
     * Constructor.
     */
    public function __construct() {
        add_action('init', [$this, 'register_taxonomy']);
        add_action('admin_init', [$this, 'add_program_meta_fields']);
        add_action('edited_' . $this->taxonomy, [$this, 'save_program_meta_fields'], 10, 2);
        add_action('created_' . $this->taxonomy, [$this, 'save_program_meta_fields'], 10, 2);
        add_filter('manage_edit-' . $this->taxonomy . '_columns', [$this, 'modify_program_columns']);
        add_filter('manage_' . $this->taxonomy . '_custom_column', [$this, 'modify_program_column_content'], 10, 3);
    }

    /**
     * Register the Program taxonomy.
     */
    public function register_taxonomy() {
        $labels = [
            'name'                       => _x('Programs', 'taxonomy general name', 'lilac-learning-manager'),
            'singular_name'              => _x('Program', 'taxonomy singular name', 'lilac-learning-manager'),
            'search_items'               => __('Search Programs', 'lilac-learning-manager'),
            'popular_items'              => __('Popular Programs', 'lilac-learning-manager'),
            'all_items'                  => __('All Programs', 'lilac-learning-manager'),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __('Edit Program', 'lilac-learning-manager'),
            'update_item'                => __('Update Program', 'lilac-learning-manager'),
            'add_new_item'               => __('Add New Program', 'lilac-learning-manager'),
            'new_item_name'              => __('New Program Name', 'lilac-learning-manager'),
            'separate_items_with_commas' => __('Separate programs with commas', 'lilac-learning-manager'),
            'add_or_remove_items'        => __('Add or remove programs', 'lilac-learning-manager'),
            'choose_from_most_used'      => __('Choose from the most used programs', 'lilac-learning-manager'),
            'not_found'                  => __('No programs found.', 'lilac-learning-manager'),
            'menu_name'                  => __('Programs', 'lilac-learning-manager'),
        ];


        $args = [
            'hierarchical'          => true,
            'labels'                => $labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'update_count_callback' => '_update_post_term_count',
            'query_var'             => true,
            'rewrite'               => ['slug' => 'program'],
            'show_in_rest'          => true,
            'capabilities'          => [
                'manage_terms' => 'manage_categories',
                'edit_terms'   => 'manage_categories',
                'delete_terms' => 'manage_categories',
                'assign_terms' => 'edit_posts',
            ],
        ];

        register_taxonomy($this->taxonomy, $this->post_types, $args);
        
        // Register default programs if none exist
        $this->register_default_programs();
    }

    
    /**
     * Register default programs.
     */
    private function register_default_programs() {
        $default_programs = [
            'transportation-education' => __('חינוך תעבורתי', 'lilac-learning-manager'),
            'private-vehicle'          => __('רכב פרטי', 'lilac-learning-manager'),
            'bike-scooter'             => __('אופניים/קורקינט', 'lilac-learning-manager'),
            'truck-up-to-12t'          => __('משאית עד 12 טון', 'lilac-learning-manager'),
        ];
        
        foreach ($default_programs as $slug => $name) {
            if (!term_exists($slug, $this->taxonomy)) {
                wp_insert_term($name, $this->taxonomy, ['slug' => $slug]);
            }
        }
    }
    
    /**
     * Add custom meta fields to the program taxonomy.
     */
    public function add_program_meta_fields() {
        // Add form fields for adding a new term
        add_action($this->taxonomy . '_add_form_fields', [$this, 'render_add_program_fields'], 10, 2);
        
        // Add form fields for editing an existing term
        add_action($this->taxonomy . '_edit_form_fields', [$this, 'render_edit_program_fields'], 10, 2);
    }
    
    /**
     * Render fields for adding a new program.
     */
    public function render_add_program_fields($taxonomy) {
        ?>
        <div class="form-field term-color-wrap">
            <label for="program_color"><?php _e('Color', 'lilac-learning-manager'); ?></label>
            <input type="color" name="program_color" id="program_color" value="#2271b1">
            <p class="description"><?php _e('Color to represent this program in the dashboard.', 'lilac-learning-manager'); ?></p>
        </div>
        
        <div class="form-field term-description-wrap">
            <label for="program_description"><?php _e('Description', 'lilac-learning-manager'); ?></label>
            <textarea name="program_description" id="program_description" rows="5" cols="40"></textarea>
            <p class="description"><?php _e('A short description of this program.', 'lilac-learning-manager'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Render fields for editing an existing program.
     */
    public function render_edit_program_fields($term, $taxonomy) {
        $color = get_term_meta($term->term_id, 'program_color', true);
        $description = get_term_meta($term->term_id, 'program_description', true);
        
        if (empty($color)) {
            $color = '#2271b1'; // Default color
        }
        ?>
        <tr class="form-field term-color-wrap">
            <th scope="row">
                <label for="program_color"><?php _e('Color', 'lilac-learning-manager'); ?></label>
            </th>
            <td>
                <input type="color" name="program_color" id="program_color" value="<?php echo esc_attr($color); ?>">
                <p class="description"><?php _e('Color to represent this program in the dashboard.', 'lilac-learning-manager'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field term-description-wrap">
            <th scope="row">
                <label for="program_description"><?php _e('Description', 'lilac-learning-manager'); ?></label>
            </th>
            <td>
                <textarea name="program_description" id="program_description" rows="5" cols="50" class="large-text"><?php echo esc_textarea($description); ?></textarea>
                <p class="description"><?php _e('A short description of this program.', 'lilac-learning-manager'); ?></p>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Save custom meta fields for programs.
     */
    public function save_program_meta_fields($term_id) {
        if (isset($_POST['program_color'])) {
            update_term_meta($term_id, 'program_color', sanitize_hex_color($_POST['program_color']));
        }
        
        if (isset($_POST['program_description'])) {
            update_term_meta($term_id, 'program_description', sanitize_textarea_field($_POST['program_description']));
        }
    }
    
    /**
     * Modify the columns in the Programs list table.
     */
    public function modify_program_columns($columns) {
        $new_columns = [
            'cb' => $columns['cb'],
            'name' => $columns['name'],
            'color' => __('Color', 'lilac-learning-manager'),
            'description' => $columns['description'],
            'slug' => $columns['slug'],
            'posts' => $columns['posts'],
        ];
        
        return $new_columns;
    }
    
    /**
     * Add content to custom columns in the Programs list table.
     */
    public function modify_program_column_content($content, $column_name, $term_id) {
        if ('color' === $column_name) {
            $color = get_term_meta($term_id, 'program_color', true);
            if (empty($color)) {
                $color = '#2271b1'; // Default color
            }
            return '<span class="program-color-preview" style="display:inline-block; width:20px; height:20px; background-color:' . esc_attr($color) . '; border:1px solid #ddd; border-radius:3px;"></span>';
        }
        
        return $content;
    }
}

// Initialize the class
new ProgramTaxonomy();
