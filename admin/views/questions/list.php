<?php
/**
 * Questions List View
 *
 * @package LilacLearningManager\Admin\Views
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Get programs for filter
$programs = get_terms(array(
    'taxonomy' => 'ld_program',
    'hide_empty' => false,
    'orderby' => 'name',
));

// Get courses for filter
$courses = get_posts(array(
    'post_type' => 'sfwd-courses',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
));

// Get topics for filter
$topics = get_terms(array(
    'taxonomy' => 'ld_topic',
    'hide_empty' => false,
    'orderby' => 'name',
));
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php esc_html_e('Questions', 'lilac-learning-manager'); ?>
    </h1>
    
    <a href="<?php echo esc_url(admin_url('post-new.php?post_type=sfwd-question')); ?>" class="page-title-action">
        <?php esc_html_e('Add New', 'lilac-learning-manager'); ?>
    </a>
    
    <hr class="wp-header-end">
    
    <div class="llm-questions-wrapper">
        <div class="llm-questions-header">
            <h2 class="llm-questions-title">
                <?php esc_html_e('Manage Questions', 'lilac-learning-manager'); ?>
            </h2>
            
            <div class="llm-questions-filters">
                <!-- Search -->
                <div class="llm-questions-filter">
                    <input type="search" 
                           class="llm-questions-search" 
                           placeholder="<?php esc_attr_e('Search questions...', 'lilac-learning-manager'); ?>">
                </div>
                
                <!-- Program Filter -->
                <div class="llm-questions-filter">
                    <select name="program_id" class="llm-questions-select">
                        <option value=""><?php esc_html_e('All Programs', 'lilac-learning-manager'); ?></option>
                        <?php foreach ($programs as $program) : ?>
                            <option value="<?php echo esc_attr($program->term_id); ?>">
                                <?php echo esc_html($program->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Course Filter -->
                <div class="llm-questions-filter">
                    <select name="course_id" class="llm-questions-select">
                        <option value=""><?php esc_html_e('All Courses', 'lilac-learning-manager'); ?></option>
                        <?php foreach ($courses as $course) : ?>
                            <option value="<?php echo esc_attr($course->ID); ?>">
                                <?php echo esc_html($course->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Topic Filter -->
                <div class="llm-questions-filter">
                    <select name="topic_id" class="llm-questions-select">
                        <option value=""><?php esc_html_e('All Topics', 'lilac-learning-manager'); ?></option>
                        <?php foreach ($topics as $topic) : ?>
                            <option value="<?php echo esc_attr($topic->term_id); ?>">
                                <?php echo esc_html($topic->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="llm-questions-table-container">
            <table class="wp-list-table widefat fixed striped table-view-list llm-questions-table">
                <thead>
                    <tr>
                        <th class="column-primary"><?php esc_html_e('Question', 'lilac-learning-manager'); ?></th>
                        <th><?php esc_html_e('Course', 'lilac-learning-manager'); ?></th>
                        <th><?php esc_html_e('Program', 'lilac-learning-manager'); ?></th>
                        <th width="100"><?php esc_html_e('Points', 'lilac-learning-manager'); ?></th>
                        <th width="100"><?php esc_html_e('Actions', 'lilac-learning-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="5" class="loading">
                            <span class="spinner is-active"></span>
                            <?php esc_html_e('Loading questions...', 'lilac-learning-manager'); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="llm-pagination" style="display: none;">
            <!-- Pagination will be loaded via JavaScript -->
        </div>
    </div>
</div>

<!-- Add some inline styles for the loading state -->
<style>
    .llm-questions-wrapper {
        position: relative;
        min-height: 200px;
    }
    
    .llm-questions-wrapper.llm-loading:after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.7);
        z-index: 10;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .llm-questions-wrapper .spinner {
        float: none;
        margin-top: 0;
    }
    
    .llm-questions-wrapper .loading {
        text-align: center;
        padding: 20px;
    }
    
    .llm-questions-filters {
        margin: 15px 0;
    }
    
    .llm-questions-filter {
        margin-bottom: 10px;
    }
    
    .llm-questions-select,
    .llm-questions-search {
        width: 100%;
        max-width: 100%;
    }
    
    @media screen and (min-width: 782px) {
        .llm-questions-filters {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .llm-questions-filter {
            margin-bottom: 0;
            min-width: 200px;
        }
        
        .llm-questions-search {
            min-width: 250px;
        }
    }
</style>
