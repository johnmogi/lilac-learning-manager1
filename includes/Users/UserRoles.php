<?php
namespace LilacLearningManager\Users;

class UserRoles {
    /**
     * Initialize the user roles
     */
    public static function init() {
        add_action('init', [__CLASS__, 'add_user_roles']);
    }

    /**
     * Add custom user roles
     */
    public static function add_user_roles() {
        // School Teacher (מורה / רכז)
        add_role(
            'school_teacher',
            __('מורה / רכז', 'lilac-learning-manager'),
            [
                // Basic capabilities
                'read' => true,
                'upload_files' => true,
                'edit_posts' => true,
                'delete_posts' => false,
                'publish_posts' => false,
                
                // Course management
                'edit_courses' => true,
                'edit_others_courses' => true,
                'publish_courses' => true,
                'read_private_courses' => true,
                'delete_courses' => true,
                
                // Student management
                'list_users' => true,
                'edit_users' => true,
                'promote_users' => false,
                'create_users' => true,
                'delete_users' => false,
                
                // LearnDash specific
                'enroll_users' => true,
                'manage_course_categories' => true,
            ]
        );

        // School Student (תלמיד חינוך תעבורתי)
        add_role(
            'student_school',
            __('תלמיד חינוך תעבורתי', 'lilac-learning-manager'),
            [
                'read' => true,
                'upload_files' => true,
                'edit_posts' => false,
                'publish_posts' => false,
                'delete_posts' => false,
                'enroll_in_courses' => true,
                'submit_assignments' => true,
                'view_course_content' => true,
            ]
        );

        // Private Student (תלמיד עצמאי)
        add_role(
            'student_private',
            __('תלמיד עצמאי', 'lilac-learning-manager'),
            [
                'read' => true,
                'upload_files' => false,
                'edit_posts' => false,
                'publish_posts' => false,
                'delete_posts' => false,
                'enroll_in_courses' => true,
                'view_course_content' => true,
            ]
        );
    }

    /**
     * Get all custom roles with their display names
     * 
     * @return array Array of role => display_name
     */
    public static function get_custom_roles() {
        return [
            'school_teacher' => __('מורה / רכז', 'lilac-learning-manager'),
            'student_school' => __('תלמיד חינוך תעבורתי', 'lilac-learning-manager'),
            'student_private' => __('תלמיד עצמאי', 'lilac-learning-manager'),
        ];
    }

    /**
     * Get role display name by role slug
     * 
     * @param string $role Role slug
     * @return string Role display name or empty string if not found
     */
    public static function get_role_display_name($role) {
        $roles = self::get_custom_roles();
        return $roles[$role] ?? '';
    }

    /**
     * Get all roles that should be considered students
     * 
     * @return array Array of student role slugs
     */
    public static function get_student_roles() {
        return ['student_school', 'student_private'];
    }

    /**
     * Check if a user has a student role
     * 
     * @param int $user_id User ID (optional, defaults to current user)
     * @return bool
     */
    public static function is_student($user_id = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        return !empty(array_intersect(self::get_student_roles(), $user->roles));
    }

    /**
     * Check if a user is a teacher
     * 
     * @param int $user_id User ID (optional, defaults to current user)
     * @return bool
     */
    public static function is_teacher($user_id = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        return in_array('school_teacher', $user->roles);
    }
}
