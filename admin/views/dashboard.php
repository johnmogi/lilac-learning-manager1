<?php
// Security check - exit if accessed directly
defined('ABSPATH') || exit;

// Get program terms
$programs = get_terms([
    'taxonomy' => 'llm_program',
    'hide_empty' => false,
]);
?>

<div class="wrap lilac-learning-manager-dashboard">
    <div class="lilac-dashboard-header">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <div class="lilac-header-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=lilac-learning-manager-tutorials')); ?>" class="button">
                <span class="dashicons dashicons-welcome-learn-more"></span>
                <?php esc_html_e('View Tutorials', 'lilac-learning-manager'); ?>
            </a>
        </div>
    </div>
    
    <div class="lilac-dashboard-widgets">
        <!-- Quick Stats Row -->
        <div class="lilac-stats-row">
            <div class="lilac-stat-card">
                <div class="stat-icon">
                    <span class="dashicons dashicons-welcome-learn-more"></span>
                </div>
                <div class="stat-content">
                    <h3><?php esc_html_e('Programs', 'lilac-learning-manager'); ?></h3>
                    <div class="stat-number"><?php echo esc_html(count($programs)); ?></div>
                    <a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=llm_program&post_type=sfwd-courses')); ?>" class="button-link">
                        <?php esc_html_e('Manage Programs', 'lilac-learning-manager'); ?>
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </a>
                </div>
            </div>
            
            <div class="lilac-stat-card">
                <div class="stat-icon">
                    <span class="dashicons dashicons-book"></span>
                </div>
                <div class="stat-content">
                    <h3><?php esc_html_e('Courses', 'lilac-learning-manager'); ?></h3>
                    <?php
                    $courses_count = wp_count_posts('sfwd-courses');
                    $total_courses = $courses_count->publish ?? 0;
                    ?>
                    <div class="stat-number"><?php echo esc_html($total_courses); ?></div>
                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=sfwd-courses')); ?>" class="button-link">
                        <?php esc_html_e('View All Courses', 'lilac-learning-manager'); ?>
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </a>
                </div>
            </div>
            
            <div class="lilac-stat-card">
                <div class="stat-icon">
                    <span class="dashicons dashicons-groups"></span>
                </div>
                <div class="stat-content">
                    <h3><?php esc_html_e('Students', 'lilac-learning-manager'); ?></h3>
                    <?php
                    $students_count = count_users();
                    $total_students = $students_count['total_users'] ?? 0;
                    ?>
                    <div class="stat-number"><?php echo esc_html($total_students); ?></div>
                    <a href="<?php echo esc_url(admin_url('users.php')); ?>" class="button-link">
                        <?php esc_html_e('Manage Users', 'lilac-learning-manager'); ?>
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Programs Overview -->
        <div class="lilac-dashboard-section">
            <div class="lilac-section-header">
                <h2><?php esc_html_e('Programs Overview', 'lilac-learning-manager'); ?></h2>
                <a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=llm_program&post_type=sfwd-courses')); ?>" class="button">
                    <?php esc_html_e('Manage All Programs', 'lilac-learning-manager'); ?>
                </a>
            </div>
            
            <div class="lilac-programs-grid">
                <?php if (!empty($programs)) : ?>
                    <?php foreach ($programs as $program) : 
                        $course_count = $program->count;
                        $program_link = get_edit_term_link($program->term_id, 'llm_program', 'sfwd-courses');
                        $courses_link = add_query_arg(['post_type' => 'sfwd-courses', 'llm_program' => $program->slug], admin_url('edit.php'));
                    ?>
                        <div class="lilac-program-card">
                            <h3><?php echo esc_html($program->name); ?></h3>
                            <div class="program-meta">
                                <span class="course-count">
                                    <?php 
                                    printf(
                                        _n('%d Course', '%d Courses', $course_count, 'lilac-learning-manager'),
                                        $course_count
                                    );
                                    ?>
                                </span>
                            </div>
                            <div class="program-actions">
                                <a href="<?php echo esc_url($program_link); ?>" class="button">
                                    <?php esc_html_e('Edit Program', 'lilac-learning-manager'); ?>
                                </a>
                                <a href="<?php echo esc_url($courses_link); ?>" class="button button-link">
                                    <?php esc_html_e('View Courses', 'lilac-learning-manager'); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <div class="lilac-no-programs">
                        <p><?php esc_html_e('No programs found. Create your first program to get started.', 'lilac-learning-manager'); ?></p>
                        <a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=llm_program&post_type=sfwd-courses')); ?>" class="button button-primary">
                            <?php esc_html_e('Add New Program', 'lilac-learning-manager'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="lilac-dashboard-section">
            <div class="lilac-section-header">
                <h2><?php esc_html_e('Recent Activity', 'lilac-learning-manager'); ?></h2>
            </div>
            
            <div class="lilac-activity-tabs">
                <ul class="nav-tab-wrapper">
                    <li><a href="#recent-courses" class="nav-tab nav-tab-active"><?php esc_html_e('Recent Courses', 'lilac-learning-manager'); ?></a></li>
                    <li><a href="#recent-students" class="nav-tab"><?php esc_html_e('Recent Students', 'lilac-learning-manager'); ?></a></li>
                    <li><a href="#system-status" class="nav-tab"><?php esc_html_e('System Status', 'lilac-learning-manager'); ?></a></li>
                </ul>
                
                <div class="lilac-tab-content">
                    <div id="recent-courses" class="tab-pane active">
                        <?php
                        $recent_courses = get_posts([
                            'post_type' => 'sfwd-courses',
                            'posts_per_page' => 5,
                            'orderby' => 'date',
                            'order' => 'DESC'
                        ]);
                        
                        if (!empty($recent_courses)) :
                            echo '<ul class="lilac-activity-list">';
                            foreach ($recent_courses as $course) {
                                $edit_link = get_edit_post_link($course->ID);
                                $programs = get_the_terms($course->ID, 'llm_program');
                                $programs_list = '';
                                
                                if (!empty($programs) && !is_wp_error($programs)) {
                                    $program_names = wp_list_pluck($programs, 'name');
                                    $programs_list = '<span class="program-tags">' . implode(', ', array_map('esc_html', $program_names)) . '</span>';
                                }
                                
                                echo '<li>';
                                echo '<a href="' . esc_url($edit_link) . '" class="activity-title">' . esc_html($course->post_title) . '</a>';
                                echo $programs_list;
                                echo '<span class="activity-date">' . esc_html(get_the_date('', $course->ID)) . '</span>';
                                echo '</li>';
                            }
                            echo '</ul>';
                        else :
                            echo '<p>' . esc_html__('No courses found.', 'lilac-learning-manager') . '</p>';
                        endif;
                        ?>
                        <div class="lilac-view-all">
                            <a href="<?php echo esc_url(admin_url('edit.php?post_type=sfwd-courses')); ?>" class="button">
                                <?php esc_html_e('View All Courses', 'lilac-learning-manager'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="lilac-dashboard-footer">
        <div class="lilac-footer-content">
            <div class="lilac-version">
                <?php
                printf(
                    /* translators: %s: Plugin version */
                    esc_html__('Lilac Learning Manager v%s', 'lilac-learning-manager'),
                    esc_html(LILAC_LEARNING_MANAGER_VERSION)
                );
                ?>
            </div>
            <div class="lilac-footer-links">
                <a href="#" target="_blank">
                    <span class="dashicons dashicons-book"></span>
                    <?php esc_html_e('Documentation', 'lilac-learning-manager'); ?>
                </a>
                <a href="#" target="_blank">
                    <span class="dashicons dashicons-sos"></span>
                    <?php esc_html_e('Support', 'lilac-learning-manager'); ?>
                </a>
                <a href="#" target="_blank">
                    <span class="dashicons dashicons-email"></span>
                    <?php esc_html_e('Feedback', 'lilac-learning-manager'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
/* Dashboard Layout */
.lilac-dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.lilac-dashboard-header h1 {
    margin: 0;
    padding: 9px 0 4px 0;
}

/* Stats Row */
.lilac-stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.lilac-stat-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    display: flex;
    align-items: flex-start;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
}

.stat-icon {
    background: #f0f6fc;
    border: 1px solid #c3d4e4;
    border-radius: 50%;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    flex-shrink: 0;
}

.stat-icon .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
    color: #2271b1;
}

.stat-content {
    flex: 1;
}

.stat-content h3 {
    margin: 0 0 5px 0;
    font-size: 14px;
    font-weight: 500;
    color: #646970;
}

.stat-number {
    font-size: 28px;
    font-weight: 600;
    margin: 0 0 10px 0;
    color: #1d2327;
}

.button-link {
    display: inline-flex;
    align-items: center;
    text-decoration: none;
    color: #2271b1;
    font-weight: 500;
}

.button-link .dashicons {
    margin-left: 3px;
    font-size: 16px;
    width: 16px;
    height: 16px;
}

/* Programs Section */
.lilac-dashboard-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin-bottom: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
}

.lilac-section-header {
    padding: 15px 20px;
    border-bottom: 1px solid #dcdcde;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.lilac-section-header h2 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.lilac-programs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    padding: 20px;
}

.lilac-program-card {
    border: 1px solid #dcdcde;
    border-radius: 4px;
    padding: 20px;
    transition: all 0.2s ease;
}

.lilac-program-card:hover {
    border-color: #b4b9be;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.lilac-program-card h3 {
    margin: 0 0 10px 0;
    font-size: 16px;
    color: #1d2327;
}

.program-meta {
    margin-bottom: 15px;
}

.course-count {
    display: inline-block;
    background: #f0f0f1;
    color: #50575e;
    font-size: 12px;
    padding: 2px 8px;
    border-radius: 10px;
}

.program-actions {
    display: flex;
    gap: 10px;
}

/* Activity Tabs */
.lilac-activity-tabs {
    padding: 0 20px 20px;
}

.nav-tab-wrapper {
    margin: 0 0 20px 0;
    padding: 0;
    border-bottom: 1px solid #ccd0d4;
}

.nav-tab {
    margin-bottom: -1px;
    margin-left: 0;
    padding: 8px 15px;
    font-weight: 500;
    color: #646970;
    text-decoration: none;
    border: 1px solid transparent;
    border-bottom: none;
    background: none;
    cursor: pointer;
}

.nav-tab-active {
    background: #fff;
    border-color: #ccd0d4;
    border-bottom: 1px solid #fff;
    color: #1d2327;
}

.tab-pane {
    display: none;
}

.tab-pane.active {
    display: block;
}

.lilac-activity-list {
    margin: 0;
    padding: 0;
    list-style: none;
}

.lilac-activity-list li {
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f1;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
}

.lilac-activity-list li:last-child {
    border-bottom: none;
}

.activity-title {
    font-weight: 500;
    color: #2271b1;
    text-decoration: none;
    flex: 1;
}

.program-tags {
    font-size: 12px;
    color: #50575e;
    background: #f0f0f1;
    padding: 2px 8px;
    border-radius: 10px;
}

.activity-date {
    font-size: 12px;
    color: #646970;
}

.lilac-view-all {
    margin-top: 15px;
    text-align: right;
}

/* Footer */
.lilac-dashboard-footer {
    margin-top: 30px;
    padding: 15px 0;
    border-top: 1px solid #dcdcde;
    color: #646970;
    font-size: 13px;
}

.lilac-footer-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.lilac-footer-links {
    display: flex;
    gap: 15px;
}

.lilac-footer-links a {
    display: flex;
    align-items: center;
    color: #646970;
    text-decoration: none;
}

.lilac-footer-links .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
    margin-right: 5px;
}

/* Responsive */
@media screen and (max-width: 782px) {
    .lilac-stats-row {
        grid-template-columns: 1fr;
    }
    
    .lilac-programs-grid {
        grid-template-columns: 1fr;
    }
    
    .lilac-footer-content {
        flex-direction: column;
        gap: 10px;
        text-align: center;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab functionality
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        // Remove active class from all tabs and panes
        $('.nav-tab').removeClass('nav-tab-active');
        $('.tab-pane').removeClass('active');
        
        // Add active class to clicked tab
        $(this).addClass('nav-tab-active');
        
        // Show corresponding pane
        var target = $(this).attr('href');
        $(target).addClass('active');
    });
    
    // Activate first tab by default
    $('.nav-tab:first').trigger('click');
});
</script>
