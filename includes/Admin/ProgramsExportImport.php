<?php
/**
 * Programs Export/Import Handler
 *
 * @package LilacLearningManager\Admin
 */

namespace LilacLearningManager\Admin;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ProgramsExportImport
 *
 * Handles the export and import of programs and their settings.
 */
class ProgramsExportImport {
    /**
     * The nonce action for export.
     *
     * @var string
     */
    private $export_nonce_action = 'llm_export_programs';

    /**
     * The nonce name for export.
     *
     * @var string
     */
    private $export_nonce_name = 'llm_export_nonce';

    /**
     * The nonce action for import.
     *
     * @var string
     */
    private $import_nonce_action = 'llm_import_programs';

    /**
     * The nonce name for import.
     *
     * @var string
     */
    private $import_nonce_name = 'llm_import_nonce';

    /**
     * The export file name.
     *
     * @var string
     */
    private $export_filename = 'lilac-programs-export.json';

    /**
     * Constructor.
     */
    public function __construct() {
        // Add admin actions
        add_action('admin_init', [$this, 'handle_export']);
        add_action('admin_init', [$this, 'handle_import']);
    }

    /**
     * Handle program export.
     */
    public function handle_export() {
        if (!isset($_GET['action']) || 'llm_export_programs' !== $_GET['action']) {
            return;
        }

        // Verify nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], $this->export_nonce_action)) {
            wp_die(__('Security check failed.', 'lilac-learning-manager'));
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'lilac-learning-manager'));
        }

        // Get all programs with their meta
        $programs = get_terms([
            'taxonomy' => 'llm_program',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);

        $export_data = [
            'version' => LILAC_LEARNING_MANAGER_VERSION,
            'export_date' => current_time('mysql'),
            'site_url' => get_site_url(),
            'programs' => [],
        ];

        foreach ($programs as $program) {
            $program_data = [
                'name' => $program->name,
                'slug' => $program->slug,
                'description' => $program->description,
                'parent' => $program->parent,
                'meta' => [],
            ];

            // Get program meta
            $meta_fields = get_term_meta($program->term_id);
            foreach ($meta_fields as $key => $values) {
                if (is_array($values) && count($values) === 1) {
                    $program_data['meta'][$key] = maybe_unserialize($values[0]);
                } else {
                    $program_data['meta'][$key] = array_map('maybe_unserialize', $values);
                }
            }

            $export_data['programs'][] = $program_data;
        }

        // Set headers for file download
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename=' . $this->export_filename);
        header('Pragma: no-cache');
        header('Expires: 0');

        // Output the JSON data
        echo wp_json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Handle program import.
     */
    public function handle_import() {
        if (!isset($_POST['action']) || 'llm_import_programs' !== $_POST['action']) {
            return;
        }

        // Verify nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], $this->import_nonce_action)) {
            wp_die(__('Security check failed.', 'lilac-learning-manager'));
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'lilac-learning-manager'));
        }

        // Check if file was uploaded
        if (!isset($_FILES['llm_import_file']) || !empty($_FILES['llm_import_file']['error'])) {
            $this->add_admin_notice(
                __('Please select a valid JSON file to import.', 'lilac-learning-manager'),
                'error'
            );
            return;
        }

        // Check file type
        $file = $_FILES['llm_import_file'];
        $file_type = wp_check_filetype($file['name'], ['json' => 'application/json']);
        
        if ('json' !== $file_type['ext']) {
            $this->add_admin_notice(
                __('Invalid file type. Please upload a JSON file.', 'lilac-learning-manager'),
                'error'
            );
            return;
        }

        // Read file contents
        $file_contents = file_get_contents($file['tmp_name']);
        $import_data = json_decode($file_contents, true);

        // Check if JSON is valid
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->add_admin_notice(
                __('Invalid JSON file. Please check the file and try again.', 'lilac-learning-manager'),
                'error'
            );
            return;
        }

        // Check if required data exists
        if (!isset($import_data['programs']) || !is_array($import_data['programs'])) {
            $this->add_admin_notice(
                __('Invalid import file format. No programs found.', 'lilac-learning-manager'),
                'error'
            );
            return;
        }

        $overwrite = isset($_POST['llm_import_overwrite']) && '1' === $_POST['llm_import_overwrite'];
        $imported = 0;
        $skipped = 0;
        $errors = [];

        // Process each program
        foreach ($import_data['programs'] as $program_data) {
            $result = $this->import_program($program_data, $overwrite);
            
            if (is_wp_error($result)) {
                $errors[] = sprintf(
                    /* translators: 1: Program name, 2: Error message */
                    __('Error importing program "%1$s": %2$s', 'lilac-learning-manager'),
                    $program_data['name'] ?? __('(unnamed)', 'lilac-learning-manager'),
                    $result->get_error_message()
                );
            } elseif (true === $result) {
                $imported++;
            } else {
                $skipped++;
            }
        }

        // Show success/error messages
        if ($imported > 0) {
            $message = sprintf(
                /* translators: %d: Number of programs imported */
                _n(
                    'Successfully imported %d program.',
                    'Successfully imported %d programs.',
                    $imported,
                    'lilac-learning-manager'
                ),
                $imported
            );
            
            if ($skipped > 0) {
                $message .= ' ' . sprintf(
                    /* translators: %d: Number of programs skipped */
                    _n(
                        '%d program was skipped (already exists).',
                        '%d programs were skipped (already exist).',
                        $skipped,
                        'lilac-learning-manager'
                    ),
                    $skipped
                );
            }
            
            $this->add_admin_notice($message, 'success');
        } else {
            $this->add_admin_notice(
                __('No programs were imported. All programs in the import file already exist.', 'lilac-learning-manager'),
                'warning'
            );
        }

        // Show any errors
        foreach ($errors as $error) {
            $this->add_admin_notice($error, 'error');
        }
    }

    /**
     * Import a single program.
     *
     * @param array $program_data The program data to import.
     * @param bool  $overwrite   Whether to overwrite existing programs.
     * @return bool|WP_Error True on success, false if skipped, WP_Error on failure.
     */
    private function import_program($program_data, $overwrite = false) {
        // Check if required fields exist
        if (empty($program_data['name']) || empty($program_data['slug'])) {
            return new \WP_Error('missing_fields', __('Missing required fields (name or slug).', 'lilac-learning-manager'));
        }

        // Check if program already exists
        $existing_term = get_term_by('slug', $program_data['slug'], 'llm_program');
        
        if ($existing_term && !$overwrite) {
            return false; // Skip existing programs if not overwriting
        }

        // Prepare term data
        $term_data = [
            'description' => $program_data['description'] ?? '',
            'slug' => $program_data['slug'],
            'parent' => 0, // We'll handle parent relationships after all terms are imported
        ];

        // Insert or update term
        if ($existing_term && $overwrite) {
            $result = wp_update_term($existing_term->term_id, 'llm_program', [
                'name' => $program_data['name'],
                'description' => $term_data['description'],
                'slug' => $term_data['slug'],
            ]);
            $term_id = $existing_term->term_id;
        } else {
            $result = wp_insert_term($program_data['name'], 'llm_program', $term_data);
            
            if (is_wp_error($result)) {
                return $result;
            }
            
            $term_id = $result['term_id'];
        }

        // Import meta data
        if (!empty($program_data['meta']) && is_array($program_data['meta'])) {
            foreach ($program_data['meta'] as $meta_key => $meta_value) {
                update_term_meta($term_id, $meta_key, $meta_value);
            }
        }

        return true;
    }

    /**
     * Add an admin notice.
     *
     * @param string $message The message to display.
     * @param string $type    The type of notice (error, warning, success, info).
     */
    private function add_admin_notice($message, $type = 'info') {
        add_action('admin_notices', function() use ($message, $type) {
            ?>
            <div class="notice notice-<?php echo esc_attr($type); ?> is-dismissible">
                <p><?php echo esc_html($message); ?></p>
            </div>
            <?php
        });
    }
}

// Initialize the export/import handler
function lilac_learning_manager_programs_export_import() {
    new ProgramsExportImport();
}
add_action('plugins_loaded', 'lilac_learning_manager_programs_export_import');
