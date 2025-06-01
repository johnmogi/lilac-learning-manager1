<?php
/**
 * Settings Page
 *
 * @package LilacLearningManager\Admin\Views
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Check user capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'lilac-learning-manager'));
}

// Get the current tab
$current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
$tabs = [
    'general' => __('General', 'lilac-learning-manager'),
    'programs' => __('Programs', 'lilac-learning-manager'),
    'import_export' => __('Import/Export', 'lilac-learning-manager'),
];
?>

<div class="wrap lilac-learning-manager-settings">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php settings_errors('llm_settings_messages'); ?>
    
    <nav class="nav-tab-wrapper wp-clearfix">
        <?php foreach ($tabs as $tab_key => $tab_label) : ?>
            <a href="?page=llm-settings&tab=<?php echo esc_attr($tab_key); ?>" 
               class="nav-tab <?php echo $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html($tab_label); ?>
            </a>
        <?php endforeach; ?>
    </nav>
    
    <div class="tab-content">
        <form method="post" action="options.php">
            <?php
            switch ($current_tab) {
                case 'general':
                    settings_fields('llm_general_settings');
                    do_settings_sections('llm-general-settings');
                    break;
                    
                case 'programs':
                    settings_fields('llm_programs_settings');
                    do_settings_sections('llm-programs-settings');
                    break;
                    
                case 'import_export':
                    settings_fields('llm_import_export_settings');
                    do_settings_sections('llm-import-export-settings');
                    break;
            }
            
            submit_button();
            ?>
        </form>
        
        <?php if ('programs' === $current_tab) : ?>
            <div class="llm-settings-section">
                <h2><?php esc_html_e('Program Display Settings', 'lilac-learning-manager'); ?></h2>
                <p><?php esc_html_e('Customize how programs are displayed throughout the site.', 'lilac-learning-manager'); ?></p>
                
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="llm_program_archive_title"><?php esc_html_e('Archive Page Title', 'lilac-learning-manager'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="llm_program_archive_title" id="llm_program_archive_title" 
                                       class="regular-text" value="<?php echo esc_attr(get_option('llm_program_archive_title', __('Programs', 'lilac-learning-manager'))); ?>">
                                <p class="description">
                                    <?php esc_html_e('The title displayed on the programs archive page.', 'lilac-learning-manager'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <?php esc_html_e('Enable Program Filtering', 'lilac-learning-manager'); ?>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="llm_enable_program_filtering" value="1" 
                                           <?php checked(1, get_option('llm_enable_program_filtering', 1)); ?>>
                                    <?php esc_html_e('Enable program filtering on course archive pages', 'lilac-learning-manager'); ?>
                                </label>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <h3><?php esc_html_e('Default Program Colors', 'lilac-learning-manager'); ?></h3>
                <p class="description">
                    <?php esc_html_e('Set default colors for new programs. These can be customized per program.', 'lilac-learning-manager'); ?>
                </p>
                
                <table class="form-table">
                    <tbody>
                        <?php
                        $default_programs = [
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
                        
                        foreach ($default_programs as $slug => $program) {
                            $option_name = 'llm_program_color_' . $slug;
                            $current_color = get_option($option_name, $program['default_color']);
                            ?>
                            <tr>
                                <th scope="row">
                                    <label for="<?php echo esc_attr($option_name); ?>">
                                        <?php echo esc_html($program['name']); ?>
                                    </label>
                                </th>
                                <td>
                                    <input type="color" 
                                           name="<?php echo esc_attr($option_name); ?>" 
                                           id="<?php echo esc_attr($option_name); ?>" 
                                           value="<?php echo esc_attr($current_color); ?>"
                                           data-default-color="<?php echo esc_attr($program['default_color']); ?>">
                                    <button type="button" class="button button-secondary llm-reset-color" 
                                            data-target="#<?php echo esc_attr($option_name); ?>">
                                        <?php esc_html_e('Reset', 'lilac-learning-manager'); ?>
                                    </button>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <?php if ('import_export' === $current_tab) : ?>
            <div class="llm-import-export-section">
                <div class="postbox">
                    <h2 class="hndle">
                        <span><?php esc_html_e('Export Programs', 'lilac-learning-manager'); ?></span>
                    </h2>
                    <div class="inside">
                        <p><?php esc_html_e('Export your programs and their settings to a JSON file.', 'lilac-learning-manager'); ?></p>
                        <p>
                            <a href="<?php echo esc_url(admin_url('admin-post.php?action=llm_export_programs')); ?>" class="button button-primary">
                                <?php esc_html_e('Export Programs', 'lilac-learning-manager'); ?>
                            </a>
                        </p>
                    </div>
                </div>
                
                <div class="postbox">
                    <h2 class="hndle">
                        <span><?php esc_html_e('Import Programs', 'lilac-learning-manager'); ?></span>
                    </h2>
                    <div class="inside">
                        <p><?php esc_html_e('Import programs from a JSON file.', 'lilac-learning-manager'); ?></p>
                        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <input type="hidden" name="action" value="llm_import_programs">
                            <?php wp_nonce_field('llm_import_programs_nonce', 'llm_import_nonce'); ?>
                            <p>
                                <input type="file" name="llm_import_file" accept=".json">
                            </p>
                            <p>
                                <label>
                                    <input type="checkbox" name="llm_import_overwrite" value="1">
                                    <?php esc_html_e('Overwrite existing programs', 'lilac-learning-manager'); ?>
                                </label>
                            </p>
                            <p>
                                <button type="submit" class="button button-primary">
                                    <?php esc_html_e('Import Programs', 'lilac-learning-manager'); ?>
                                </button>
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.llm-settings-section {
    margin-top: 20px;
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
}

.llm-settings-section h2 {
    margin-top: 0;
    padding-top: 0;
}

.llm-import-export-section .postbox {
    margin-bottom: 20px;
}

.llm-import-export-section .inside {
    padding: 0 12px 12px;
}

.llm-import-export-section h2.hndle {
    padding: 8px 12px;
    margin: 0;
    line-height: 1.4;
}

.llm-import-export-section .button {
    margin-top: 10px;
}

/* Responsive styles */
@media screen and (max-width: 782px) {
    .llm-settings-section {
        padding: 15px;
    }
    
    .form-table th {
        display: block;
        width: 100%;
        padding-bottom: 0;
    }
    
    .form-table td {
        display: block;
        width: 100%;
        padding-top: 0;
    }
    
    .llm-import-export-section .button {
        width: 100%;
        text-align: center;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle color reset buttons
    $('.llm-reset-color').on('click', function(e) {
        e.preventDefault();
        var $button = $(this);
        var $input = $($button.data('target'));
        var defaultColor = $input.data('default-color');
        
        if (defaultColor) {
            $input.val(defaultColor).trigger('change');
        }
    });
    
    // Initialize color pickers
    if ($.fn.wpColorPicker) {
        $('.color-picker').wpColorPicker();
    }
});
</script>
