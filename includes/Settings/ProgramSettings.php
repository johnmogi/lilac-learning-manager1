<?php
/**
 * Program Settings
 *
 * @package LilacLearningManager\Includes\Settings
 */

namespace LilacLearningManager\Settings;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ProgramSettings
 *
 * Handles the registration and management of program-related settings.
 */
class ProgramSettings {
    /**
     * The option group.
     *
     * @var string
     */
    private $option_group = 'llm_programs_settings';

    /**
     * The option name.
     *
     * @var string
     */
    private $option_name = 'llm_programs_options';

    /**
     * The settings page slug.
     *
     * @var string
     */
    private $page = 'llm-programs-settings';

    /**
     * The settings section ID.
     *
     * @var string
     */
    private $section = 'llm_programs_section';

    /**
     * Constructor.
     */
    public function __construct() {
        // Register settings
        add_action('admin_init', [$this, 'register_settings']);
        
        // Add settings section
        add_action('admin_init', [$this, 'add_settings_section']);
        
        // Add settings fields
        add_action('admin_init', [$this, 'add_settings_fields']);
        
        // Sanitize settings
        add_filter("sanitize_option_{$this->option_name}", [$this, 'sanitize_settings']);
    }

    /**
     * Register settings.
     */
    public function register_settings() {
        register_setting(
            $this->option_group,
            $this->option_name,
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'default' => $this->get_default_settings(),
            ]
        );
    }

    /**
     * Add settings section.
     */
    public function add_settings_section() {
        add_settings_section(
            $this->section,
            __('Program Display Settings', 'lilac-learning-manager'),
            [$this, 'render_section_description'],
            $this->page
        );
    }

    /**
     * Add settings fields.
     */
    public function add_settings_fields() {
        // Archive page title
        add_settings_field(
            'llm_program_archive_title',
            __('Archive Page Title', 'lilac-learning-manager'),
            [$this, 'render_archive_title_field'],
            $this->page,
            $this->section
        );

        // Enable program filtering
        add_settings_field(
            'llm_enable_program_filtering',
            __('Enable Program Filtering', 'lilac-learning-manager'),
            [$this, 'render_enable_filtering_field'],
            $this->page,
            $this->section
        );

        // Default program colors
        $default_programs = $this->get_default_programs();
        foreach ($default_programs as $slug => $program) {
            add_settings_field(
                "llm_program_color_{$slug}",
                sprintf(__('Default Color: %s', 'lilac-learning-manager'), $program['name']),
                [$this, 'render_program_color_field'],
                $this->page,
                $this->section,
                [
                    'slug' => $slug,
                    'name' => $program['name'],
                    'default_color' => $program['default_color'],
                ]
            );
        }
    }

    /**
     * Render section description.
     */
    public function render_section_description() {
        echo '<p>' . esc_html__('Customize how programs are displayed throughout the site.', 'lilac-learning-manager') . '</p>';
    }

    /**
     * Render archive title field.
     */
    public function render_archive_title_field() {
        $value = get_option('llm_program_archive_title', __('Programs', 'lilac-learning-manager'));
        ?>
        <input type="text" 
               name="llm_program_archive_title" 
               id="llm_program_archive_title" 
               class="regular-text" 
               value="<?php echo esc_attr($value); ?>">
        <p class="description">
            <?php esc_html_e('The title displayed on the programs archive page.', 'lilac-learning-manager'); ?>
        </p>
        <?php
    }

    /**
     * Render enable filtering field.
     */
    public function render_enable_filtering_field() {
        $enabled = (bool) get_option('llm_enable_program_filtering', 1);
        ?>
        <label>
            <input type="checkbox" 
                   name="llm_enable_program_filtering" 
                   value="1" 
                   <?php checked(true, $enabled); ?>>
            <?php esc_html_e('Enable program filtering on course archive pages', 'lilac-learning-manager'); ?>
        </label>
        <?php
    }

    /**
     * Render program color field.
     *
     * @param array $args Field arguments.
     */
    public function render_program_color_field($args) {
        $option_name = "llm_program_color_{$args['slug']}";
        $current_color = get_option($option_name, $args['default_color']);
        ?>
        <input type="color" 
               name="<?php echo esc_attr($option_name); ?>" 
               id="<?php echo esc_attr($option_name); ?>" 
               value="<?php echo esc_attr($current_color); ?>"
               data-default-color="<?php echo esc_attr($args['default_color']); ?>">
        <button type="button" class="button button-secondary llm-reset-color" 
                data-target="#<?php echo esc_attr($option_name); ?>">
            <?php esc_html_e('Reset', 'lilac-learning-manager'); ?>
        </button>
        <?php
    }

    /**
     * Sanitize settings.
     *
     * @param array $input The input array.
     * @return array Sanitized settings.
     */
    public function sanitize_settings($input) {
        $sanitized = [];
        
        // Sanitize archive title
        if (isset($input['llm_program_archive_title'])) {
            $sanitized['llm_program_archive_title'] = sanitize_text_field($input['llm_program_archive_title']);
        }
        
        // Sanitize enable filtering
        $sanitized['llm_enable_program_filtering'] = isset($input['llm_enable_program_filtering']) ? 1 : 0;
        
        // Sanitize program colors
        $default_programs = $this->get_default_programs();
        foreach ($default_programs as $slug => $program) {
            $option_name = "llm_program_color_{$slug}";
            if (isset($input[$option_name])) {
                $sanitized[$option_name] = sanitize_hex_color($input[$option_name]);
            }
        }
        
        return $sanitized;
    }

    /**
     * Get default settings.
     *
     * @return array Default settings.
     */
    private function get_default_settings() {
        $defaults = [
            'llm_program_archive_title' => __('Programs', 'lilac-learning-manager'),
            'llm_enable_program_filtering' => 1,
        ];
        
        // Add default program colors
        $default_programs = $this->get_default_programs();
        foreach ($default_programs as $slug => $program) {
            $defaults["llm_program_color_{$slug}"] = $program['default_color'];
        }
        
        return $defaults;
    }
    
    /**
     * Get default programs.
     *
     * @return array Default programs.
     */
    private function get_default_programs() {
        return [
            'transportation-education' => [
                'name' => __('חינוך תעבורתי', 'lilac-learning-manager'),
                'default_color' => '#1e73be',
            ],
            'private-vehicle' => [
                'name' => __('רכב פרטי', 'lilac-learning-manager'),
                'default_color' => '#dd3333',
            ],
            'bike-scooter' => [
                'name' => __('אופניים/קורקינט', 'lilac-learning-manager'),
                'default_color' => '#81d742',
            ],
            'truck-up-to-12t' => [
                'name' => __('משאית עד 12 טון', 'lilac-learning-manager'),
                'default_color' => '#8224e3',
            ],
        ];
    }
}

// Initialize the settings
function lilac_learning_manager_program_settings() {
    new ProgramSettings();
}
add_action('plugins_loaded', 'lilac_learning_manager_program_settings');
