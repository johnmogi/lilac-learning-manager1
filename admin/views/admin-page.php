<?php
/**
 * Admin page template
 *
 * @package LilacLearningManager\Admin\Views
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="llm-admin-header">
        <div class="llm-admin-header-content">
            <h2><?php esc_html_e('Welcome to Lilac Learning Manager', 'lilac-learning-manager'); ?></h2>
            <p class="about-description">
                <?php esc_html_e('Manage your learning programs, courses, and questions from this dashboard.', 'lilac-learning-manager'); ?>
            </p>
        </div>
    </div>
    
    <div class="llm-dashboard-widgets">
        <div class="llm-dashboard-widget">
            <h3><?php esc_html_e('Quick Stats', 'lilac-learning-manager'); ?></h3>
            <ul class="llm-stats-list">
                <?php
                // Get program count
                $program_count = wp_count_terms('llm_program', array('hide_empty' => false));
                if (!is_wp_error($program_count)) : ?>
                    <li>
                        <a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=llm_program&post_type=sfwd-courses')); ?>">
                            <span class="dashicons dashicons-category"></span>
                            <?php 
                            printf(
                                _n('%d Program', '%d Programs', $program_count, 'lilac-learning-manager'),
                                $program_count
                            );
                            ?>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php
                // Get course count
                $course_count = wp_count_posts('sfwd-courses')->publish;
                ?>
                <li>
                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=sfwd-courses')); ?>">
                        <span class="dashicons dashicons-welcome-learn-more"></span>
                        <?php 
                        printf(
                            _n('%d Course', '%d Courses', $course_count, 'lilac-learning-manager'),
                            $course_count
                        );
                        ?>
                    </a>
                </li>
                
                <?php
                // Get question count
                $question_count = wp_count_posts('sfwd-question')->publish;
                ?>
                <li>
                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=sfwd-question')); ?>">
                        <span class="dashicons dashicons-editor-help"></span>
                        <?php 
                        printf(
                            _n('%d Question', '%d Questions', $question_count, 'lilac-learning-manager'),
                            $question_count
                        );
                        ?>
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="llm-dashboard-widget">
            <h3><?php esc_html_e('Recent Activity', 'lilac-learning-manager'); ?></h3>
            <div class="llm-activity-feed">
                <?php
                // Get recent activity
                $recent_posts = wp_get_recent_posts(array(
                    'post_type' => array('sfwd-courses', 'sfwd-question'),
                    'numberposts' => 5,
                    'post_status' => 'publish',
                ));
                
                if (!empty($recent_posts)) :
                    echo '<ul>';
                    foreach ($recent_posts as $post) {
                        $post_type = get_post_type_object($post['post_type']);
                        $post_type_name = $post_type ? $post_type->labels->singular_name : __('Item', 'lilac-learning-manager');
                        echo sprintf(
                            '<li><span class="dashicons %s"></span> %s: <a href="%s">%s</a></li>',
                            $post['post_type'] === 'sfwd-courses' ? 'dashicons-welcome-learn-more' : 'dashicons-editor-help',
                            esc_html($post_type_name),
                            esc_url(get_edit_post_link($post['ID'])),
                            esc_html(get_the_title($post['ID']))
                        );
                    }
                    echo '</ul>';
                else :
                    echo '<p>' . esc_html__('No recent activity found.', 'lilac-learning-manager') . '</p>';
                endif;
                ?>
            </div>
        </div>
    </div>
    
    <div class="llm-dashboard-footer">
        <p>
            <?php
            printf(
                /* translators: %s: Plugin version */
                esc_html__('Lilac Learning Manager version %s', 'lilac-learning-manager'),
                esc_html(LILAC_LEARNING_MANAGER_VERSION)
            );
            ?>
        </p>
    </div>
</div>
