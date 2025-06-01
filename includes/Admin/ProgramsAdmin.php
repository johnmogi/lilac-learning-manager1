<?php
/**
 * Programs Admin
 *
 * @package LilacLearningManager\Admin
 */

namespace LilacLearningManager\Admin;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ProgramsAdmin
 *
 * Handles the admin interface for the Programs taxonomy.
 */
class ProgramsAdmin {
    /**
     * The taxonomy slug.
     *
     * @var string
     */
    private $taxonomy = 'llm_program';

    /**
     * Initialize the class and set up hooks.
     */
    public function __construct() {
        // Add form fields for adding a new term
        add_action("{$this->taxonomy}_add_form_fields", array($this, 'add_program_fields'), 10, 2);
        
        // Add form fields for editing an existing term
        add_action("{$this->taxonomy}_edit_form_fields", array($this, 'edit_program_fields'), 10, 2);
        
        // Save term meta data
        add_action("created_{$this->taxonomy}", array($this, 'save_program_fields'), 10, 2);
        add_action("edited_{$this->taxonomy}", array($this, 'save_program_fields'), 10, 2);
        
        // Add custom columns to the terms list table
        add_filter("manage_edit-{$this->taxonomy}_columns", array($this, 'add_program_columns'));
        add_filter("manage_{$this->taxonomy}_custom_column", array($this, 'render_program_columns'), 10, 3);
        
        // Add quick edit fields
        add_action('quick_edit_custom_box', array($this, 'add_quick_edit_fields'), 10, 3);
        
        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Handle quick edit save
        add_action('admin_init', array($this, 'handle_quick_edit_save'));
    }

    /**
     * Get the meta keys configuration.
     *
     * @return array
     */
    private function get_meta_keys() {
        return [
            'program_color' => [
                'type' => 'color',
                'label' => __('Color', 'lilac-learning-manager'),
                'description' => __('Color to represent this program in the dashboard.', 'lilac-learning-manager'),
                'default' => '#2271b1',
            ],
            'program_icon' => [
                'type' => 'text',
                'label' => __('Icon Class', 'lilac-learning-manager'),
                'description' => __('Enter a Dashicon class (e.g., dashicons-admin-site) or a custom icon class.', 'lilac-learning-manager'),
                'default' => 'dashicons-translation',
            ],
            'program_description' => [
                'type' => 'textarea',
                'label' => __('Description', 'lilac-learning-manager'),
                'description' => __('A detailed description of this program.', 'lilac-learning-manager'),
                'default' => '',
            ],
            'program_short_description' => [
                'type' => 'text',
                'label' => __('Short Description', 'lilac-learning-manager'),
                'description' => __('A brief description shown in program listings and tooltips.', 'lilac-learning-manager'),
                'default' => '',
            ],
            'program_featured' => [
                'type' => 'checkbox',
                'label' => __('Featured Program', 'lilac-learning-manager'),
                'description' => __('Check to feature this program in listings.', 'lilac-learning-manager'),
                'default' => '0',
            ],
            'program_visibility' => [
                'type' => 'select',
                'label' => __('Visibility', 'lilac-learning-manager'),
                'description' => __('Set the visibility of this program.', 'lilac-learning-manager'),
                'options' => [
                    'public' => __('Public', 'lilac-learning-manager'),
                    'private' => __('Private', 'lilac-learning-manager'),
                    'hidden' => __('Hidden', 'lilac-learning-manager'),
                ],
                'default' => 'public',
            ],
        ];
    }



    /**
     * Add fields to the add term form.
     */
    public function add_program_fields($taxonomy) {
        // Add a nonce field for security
        wp_nonce_field('llm_program_fields', 'llm_program_fields_nonce');
        
        // Output each field
        foreach ($this->meta_keys as $key => $field) {
            $field['id'] = $key;
            $field['value'] = $field['default'];
            $this->render_field($field, 'add');
        }
    }

    /**
     * Add fields to the edit term form.
     *
     * @param \WP_Term $term     The term object.
     * @param string   $taxonomy The taxonomy slug.
     */
    public function edit_program_fields($term, $taxonomy) {
        // Add a nonce field for security
        wp_nonce_field('llm_program_fields', 'llm_program_fields_nonce');
        
        // Output each field with its current value
        foreach ($this->meta_keys as $key => $field) {
            $field['id'] = $key;
            $field['value'] = get_term_meta($term->term_id, $key, true);
            
            // Use default if no value exists
            if ('' === $field['value'] && isset($field['default'])) {
                $field['value'] = $field['default'];
            }
            
            $this->render_field($field, 'edit', $term);
        }
    }

    /**
     * Render a form field.
     *
     * @param array    $field   The field configuration.
     * @param string   $context The context (add or edit).
     * @param \WP_Term $term    The term object (for edit context).
     */
    private function render_field($field, $context = 'add', $term = null) {
        $field = wp_parse_args($field, [
            'type' => 'text',
            'label' => '',
            'description' => '',
            'options' => [],
            'value' => '',
            'default' => '',
            'placeholder' => '',
        ]);
        
        $field_id = $field['id'];
        $field_name = $field_id;
        $field_value = $field['value'];
        $field_class = 'form-field term-' . $field_id . '-wrap';
        $label = $field['label'];
        $description = $field['description'];
        
        if ('edit' === $context) {
            echo '<tr class="' . esc_attr($field_class) . '">';
            echo '<th scope="row">';
            echo '<label for="' . esc_attr($field_id) . '">' . esc_html($label) . '</label>';
            echo '</th>';
            echo '<td>';
        } else {
            echo '<div class="' . esc_attr($field_class) . '">';
            echo '<label for="' . esc_attr($field_id) . '">' . esc_html($label) . '</label>';
        }
        
        // Output the appropriate input type
        switch ($field['type']) {
            case 'color':
                echo '<input type="text" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" value="' . esc_attr($field_value) . '" class="llm-color-picker" data-default-color="' . esc_attr($field['default']) . '">';
                break;
                
            case 'textarea':
                echo '<textarea id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" rows="5" cols="40" class="large-text">' . esc_textarea($field_value) . '</textarea>';
                break;
                
            case 'select':
                echo '<select id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" class="postform">';
                foreach ($field['options'] as $value => $label) {
                    echo '<option value="' . esc_attr($value) . '" ' . selected($field_value, $value, false) . '>' . esc_html($label) . '</option>';
                }
                echo '</select>';
                break;
                
            case 'checkbox':
                echo '<label><input type="checkbox" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" value="1" ' . checked('1', $field_value, false) . '> ' . esc_html__('Yes', 'lilac-learning-manager') . '</label>';
                break;
                
            case 'text':
            default:
                echo '<input type="text" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" value="' . esc_attr($field_value) . '" class="regular-text">';
                break;
        }
        
        // Output the description
        if (!empty($description)) {
            echo '<p class="description">' . wp_kses_post($description) . '</p>';
        }
        
        if ('edit' === $context) {
            echo '</td></tr>';
        } else {
            echo '</div>';
        }
    }

    /**
     * Save term meta data.
     *
     * @param int $term_id The term ID.
     */
    public function save_program_fields($term_id) {
        // Verify nonce
        if (!isset($_POST['llm_program_fields_nonce']) || !wp_verify_nonce($_POST['llm_program_fields_nonce'], 'llm_program_fields')) {
            return;
        }
        
        // Check user capabilities
        if (!current_user_can('manage_categories')) {
            return;
        }
        
        // Save each field
        foreach ($this->meta_keys as $key => $field) {
            if (isset($_POST[$key])) {
                $value = $_POST[$key];
                
                // Sanitize based on field type
                switch ($field['type']) {
                    case 'color':
                        $value = sanitize_hex_color($value);
                        break;
                        
                    case 'checkbox':
                        $value = '1' === $value ? '1' : '0';
                        break;
                        
                    case 'textarea':
                        $value = sanitize_textarea_field($value);
                        break;
                        
                    case 'select':
                        $value = array_key_exists($value, $field['options']) ? $value : $field['default'];
                        break;
                        
                    case 'text':
                    default:
                        $value = sanitize_text_field($value);
                        break;
                }
                
                update_term_meta($term_id, $key, $value);
            } else if ('checkbox' === $field['type']) {
                // For checkboxes, if not set, save as '0'
                update_term_meta($term_id, $key, '0');
            }
        }
    }

    /**
     * Add custom columns to the terms list table.
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
        
        // Add icon column
        $new_columns['icon'] = __('Icon', 'lilac-learning-manager');
        
        // Add featured column
        $new_columns['featured'] = __('Featured', 'lilac-learning-manager');
        
        // Add visibility column
        $new_columns['visibility'] = __('Visibility', 'lilac-learning-manager');
        
        // Add posts count column
        if (isset($columns['posts'])) {
            $new_columns['posts'] = $columns['posts'];
            unset($columns['posts']);
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
    public function render_program_columns($content, $column_name, $term_id) {
        switch ($column_name) {
            case 'color':
                $color = get_term_meta($term_id, 'program_color', true);
                if (empty($color)) {
                    $color = $this->meta_keys['program_color']['default'];
                }
                
                $content = sprintf(
                    '<span class="llm-color-swatch" style="display:inline-block;width:20px;height:20px;background-color:%s;border:1px solid #ddd;border-radius:3px;" title="%s"></span>',
                    esc_attr($color),
                    esc_attr($color)
                );
                break;
                
            case 'icon':
                $icon = get_term_meta($term_id, 'program_icon', true);
                if (empty($icon)) {
                    $icon = $this->meta_keys['program_icon']['default'];
                }
                
                $content = sprintf(
                    '<span class="%s" style="font-size:20px;vertical-align:middle;" title="%s"></span>',
                    esc_attr($icon),
                    esc_attr($icon)
                );
                break;
                
            case 'featured':
                $featured = get_term_meta($term_id, 'program_featured', true);
                $content = '1' === $featured ? '✓' : '—';
                break;
                
            case 'visibility':
                $visibility = get_term_meta($term_id, 'program_visibility', true);
                $visibilities = $this->meta_keys['program_visibility']['options'];
                $content = isset($visibilities[$visibility]) ? $visibilities[$visibility] : $visibilities['public'];
                break;
        }
        
        return $content;
    }

    /**
     * Add fields to the quick edit form.
     *
     * @param string $column_name The column name.
     * @param string $screen      The screen name.
     * @param string $name        The taxonomy name.
     */
    public function add_quick_edit_fields($column_name, $screen, $name) {
        if ($this->taxonomy !== $name) {
            return;
        }
        
        static $print_nonce = true;
        
        if ($print_nonce) {
            $print_nonce = false;
            wp_nonce_field('llm_program_quick_edit', 'llm_program_quick_edit_nonce');
        }
        
        switch ($column_name) {
            case 'color':
                ?>
                <fieldset>
                    <div class="inline-edit-col">
                        <label>
                            <span class="title"><?php esc_html_e('Color', 'lilac-learning-manager'); ?></span>
                            <span class="input-text-wrap">
                                <input type="text" name="program_color" value="" class="llm-color-picker" data-default-color="<?php echo esc_attr($this->meta_keys['program_color']['default']); ?>">
                            </span>
                        </label>
                    </div>
                </fieldset>
                <?php
                break;
                
            case 'featured':
                ?>
                <fieldset class="inline-edit-col-right">
                    <div class="inline-edit-col">
                        <label class="alignleft">
                            <input type="checkbox" name="program_featured" value="1">
                            <span class="checkbox-title"><?php esc_html_e('Featured', 'lilac-learning-manager'); ?></span>
                        </label>
                    </div>
                </fieldset>
                <?php
                break;
                
            case 'visibility':
                $visibilities = $this->meta_keys['program_visibility']['options'];
                ?>
                <fieldset>
                    <div class="inline-edit-col">
                        <label>
                            <span class="title"><?php esc_html_e('Visibility', 'lilac-learning-manager'); ?></span>
                            <span class="input-text-wrap">
                                <select name="program_visibility">
                                    <?php foreach ($visibilities as $value => $label) : ?>
                                        <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </span>
                        </label>
                    </div>
                </fieldset>
                <?php
                break;
        }
    }

    /**
     * Handle quick edit form submission.
     */
    public function handle_quick_edit_save() {
        if (empty($_POST) || !isset($_POST['taxonomy'])) {
            return;
        }
        
        if ($_POST['taxonomy'] !== $this->taxonomy) {
            return;
        }
        
        if (!current_user_can('manage_categories')) {
            return;
        }
        
        if (!isset($_POST['llm_program_quick_edit_nonce']) || !wp_verify_nonce($_POST['llm_program_quick_edit_nonce'], 'llm_program_quick_edit')) {
            return;
        }
        
        $term_id = isset($_POST['tag_ID']) ? (int) $_POST['tag_ID'] : 0;
        
        if (!$term_id) {
            return;
        }
        
        // Update color if provided
        if (isset($_POST['program_color'])) {
            $color = sanitize_hex_color($_POST['program_color']);
            update_term_meta($term_id, 'program_color', $color);
        }
        
        // Update featured status
        $featured = isset($_POST['program_featured']) ? '1' : '0';
        update_term_meta($term_id, 'program_featured', $featured);
        
        // Update visibility
        if (isset($_POST['program_visibility'])) {
            $visibility = sanitize_text_field($_POST['program_visibility']);
            $visibilities = $this->meta_keys['program_visibility']['options'];
            
            if (array_key_exists($visibility, $visibilities)) {
                update_term_meta($term_id, 'program_visibility', $visibility);
            }
        }
    }

    /**
     * Enqueue admin scripts and styles.
     *
     * @param string $hook The current admin page.
     */
    public function enqueue_admin_assets($hook) {
        $screen = get_current_screen();
        
        // Only load on our taxonomy edit screens
        if ('edit-tags' !== $screen->base || $this->taxonomy !== $screen->taxonomy) {
            return;
        }
        
        // Enqueue WordPress color picker
        wp_enqueue_style('wp-color-picker');
        
        // Enqueue custom admin CSS
        wp_enqueue_style(
            'lilac-learning-manager-admin',
            plugins_url('../../assets/css/admin.css', __FILE__),
            [],
            LILAC_LEARNING_MANAGER_VERSION
        );
        
        // Enqueue custom admin JS
        wp_enqueue_script(
            'lilac-learning-manager-admin',
            plugins_url('../../assets/js/admin.js', __FILE__),
            ['jquery', 'wp-color-picker'],
            LILAC_LEARNING_MANAGER_VERSION,
            true
        );
        
        // Localize script with translations
        wp_localize_script(
            'lilac-learning-manager-admin',
            'llmAdminVars',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('llm_admin_nonce'),
                'i18n' => [
                    'selectProgram' => __('Select a program', 'lilac-learning-manager'),
                    'noPrograms' => __('No programs found.', 'lilac-learning-manager'),
                    'confirmDelete' => __('Are you sure you want to delete the selected programs?', 'lilac-learning-manager'),
                ],
            ]
        );
    }
}
