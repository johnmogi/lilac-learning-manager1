<?php
/**
 * Programs Management Page
 *
 * @package LilacLearningManager\Admin\Views\Programs
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Check user capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'lilac-learning-manager'));
}

// Get all programs
$programs = get_terms([
    'taxonomy' => 'llm_program',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC'
]);

// Get all LearnDash courses
$courses = get_posts([
    'post_type' => 'sfwd-courses',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
    'post_status' => 'publish'
]);

// Handle form submission
if (isset($_POST['llm_save_programs']) && check_admin_referer('llm_save_programs_nonce', 'llm_programs_nonce')) {
    $program_assignments = isset($_POST['program_assignments']) ? (array) $_POST['program_assignments'] : [];
    
    // Clear all program relationships
    foreach ($courses as $course) {
        wp_set_object_terms($course->ID, [], 'llm_program');
    }
    
    // Set new program relationships
    foreach ($program_assignments as $course_id => $program_ids) {
        if (is_array($program_ids) && !empty($program_ids)) {
            $program_ids = array_map('intval', $program_ids);
            wp_set_object_terms($course_id, $program_ids, 'llm_program', false);
        }
    }
    
    // Add success message
    add_settings_error(
        'llm_programs_messages',
        'llm_programs_updated',
        __('Program assignments have been saved.', 'lilac-learning-manager'),
        'updated'
    );
}

// Get all courses with their program assignments
$course_programs = [];
foreach ($courses as $course) {
    $assigned_programs = wp_get_object_terms($course->ID, 'llm_program', ['fields' => 'ids']);
    $course_programs[$course->ID] = !is_wp_error($assigned_programs) ? $assigned_programs : [];
}

// Get the current tab
$current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'programs';
$tabs = [
    'programs' => __('Programs', 'lilac-learning-manager'),
    'settings' => __('Settings', 'lilac-learning-manager'),
];
?>

<div class="wrap lilac-learning-manager">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php settings_errors('llm_programs_messages'); ?>
    
    <nav class="nav-tab-wrapper wp-clearfix">
        <?php foreach ($tabs as $tab_key => $tab_label) : ?>
            <a href="?page=llm-programs&tab=<?php echo esc_attr($tab_key); ?>" 
               class="nav-tab <?php echo $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html($tab_label); ?>
            </a>
        <?php endforeach; ?>
    </nav>
    
    <div class="tab-content">
        <?php if ('programs' === $current_tab) : ?>
            <form method="post" action="" class="llm-programs-form">
                <?php wp_nonce_field('llm_save_programs_nonce', 'llm_programs_nonce'); ?>
                
                <div class="tablenav top">
                    <div class="alignleft actions">
                        <button type="submit" name="llm_save_programs" class="button button-primary">
                            <?php esc_html_e('Save Changes', 'lilac-learning-manager'); ?>
                        </button>
                    </div>
                    <div class="tablenav-pages">
                        <span class="displaying-num">
                            <?php 
                            printf(
                                _n('%s course', '%s courses', count($courses), 'lilac-learning-manager'),
                                number_format_i18n(count($courses))
                            );
                            ?>
                        </span>
                    </div>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th class="column-course"><?php esc_html_e('Course', 'lilac-learning-manager'); ?></th>
                            <th class="column-programs"><?php esc_html_e('Programs', 'lilac-learning-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($courses)) : ?>
                            <?php foreach ($courses as $course) : 
                                $assigned_programs = $course_programs[$course->ID] ?? [];
                                ?>
                                <tr>
                                    <td class="column-course">
                                        <strong>
                                            <a href="<?php echo esc_url(get_edit_post_link($course->ID)); ?>">
                                                <?php echo esc_html($course->post_title); ?>
                                            </a>
                                        </strong>
                                    </td>
                                    <td class="column-programs">
                                        <div class="llm-program-checkboxes">
                                            <?php foreach ($programs as $program) : 
                                                $color = get_term_meta($program->term_id, 'program_color', true);
                                                $color_style = $color ? 'style="background-color: ' . esc_attr($color) . ';"' : '';
                                                ?>
                                                <label class="llm-program-checkbox">
                                                    <input type="checkbox" 
                                                           name="program_assignments[<?php echo esc_attr($course->ID); ?>][]" 
                                                           value="<?php echo esc_attr($program->term_id); ?>"
                                                           <?php checked(in_array($program->term_id, $assigned_programs)); ?>>
                                                    <span class="program-color" <?php echo $color_style; ?>></span>
                                                    <span class="program-name"><?php echo esc_html($program->name); ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="2">
                                    <?php esc_html_e('No courses found.', 'lilac-learning-manager'); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <div class="tablenav bottom">
                    <div class="alignleft actions">
                        <button type="submit" name="llm_save_programs" class="button button-primary">
                            <?php esc_html_e('Save Changes', 'lilac-learning-manager'); ?>
                        </button>
                    </div>
                </div>
            </form>
            
        <?php elseif ('settings' === $current_tab) : ?>
            <div class="llm-settings-section">
                <h2><?php esc_html_e('Program Settings', 'lilac-learning-manager'); ?></h2>
                <p><?php esc_html_e('Configure program-related settings here.', 'lilac-learning-manager'); ?></p>
                
                <form method="post" action="options.php">
                    <?php 
                    settings_fields('llm_programs_settings');
                    do_settings_sections('llm-programs-settings');
                    submit_button();
                    ?>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.llm-program-checkboxes {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin: 5px 0;
}

.llm-program-checkbox {
    display: flex;
    align-items: center;
    margin-right: 15px;
    cursor: pointer;
}

.llm-program-checkbox input[type="checkbox"] {
    margin-right: 5px;
}

.program-color {
    display: inline-block;
    width: 16px;
    height: 16px;
    border-radius: 3px;
    margin-right: 5px;
    border: 1px solid #ddd;
}

.program-name {
    font-size: 13px;
}

/* Responsive styles */
@media screen and (max-width: 782px) {
    .llm-program-checkboxes {
        flex-direction: column;
        gap: 5px;
    }
    
    .llm-program-checkbox {
        margin-right: 0;
        margin-bottom: 5px;
    }
}
</style>
