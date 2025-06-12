<?php
namespace LilacLearningManager\Core;

class Roles {
    /**
     * Custom role slugs and names
     */
    const ROLE_SCHOOL_TEACHER = 'school_teacher';
    const ROLE_STUDENT_SCHOOL = 'student_school';
    const ROLE_STUDENT_PRIVATE = 'student_private';

    /**
     * Initialize roles
     */
    public static function init() {
        add_action('init', [__CLASS__, 'register_roles']);
    }

    /**
     * Register custom roles
     */
    public static function register_roles() {
        // School Teacher (מורה / רכז)
        add_role(
            self::ROLE_SCHOOL_TEACHER,
            __('מורה / רכז', 'lilac-learning-manager'),
            [
                'read' => true,
                'upload_files' => true,
                'edit_posts' => true,
                'delete_posts' => false,
                'publish_posts' => false,
                'edit_courses' => true,
                'edit_others_courses' => true,
                'publish_courses' => true,
                'read_private_courses' => true,
                'delete_courses' => true,
                'list_users' => true,
                'edit_users' => true,
                'promote_users' => false,
                'create_users' => true,
                'delete_users' => false,
                'enroll_users' => true,
                'manage_course_categories' => true,
            ]
        );

        // School Student (תלמיד חינוך תעבורתי)
        add_role(
            self::ROLE_STUDENT_SCHOOL,
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
            self::ROLE_STUDENT_PRIVATE,
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
     * Get all custom roles
     */
    public static function get_roles() {
        return [
            self::ROLE_SCHOOL_TEACHER => __('מורה / רכז', 'lilac-learning-manager'),
            self::ROLE_STUDENT_SCHOOL => __('תלמיד חינוך תעבורתי', 'lilac-learning-manager'),
            self::ROLE_STUDENT_PRIVATE => __('תלמיד עצמאי', 'lilac-learning-manager'),
        ];
    }

    /**
     * Check if a user has a student role
     */
    public static function is_student($user = null) {
        if (is_null($user)) {
            $user = wp_get_current_user();
        } elseif (is_numeric($user)) {
            $user = get_userdata($user);
        }

        if (!$user || !$user->exists()) {
            return false;
        }

        $student_roles = [self::ROLE_STUDENT_SCHOOL, self::ROLE_STUDENT_PRIVATE];
        return !empty(array_intersect($student_roles, $user->roles));
    }

    /**
     * Check if a user is a teacher
     */
    public static function is_teacher($user = null) {
        if (is_null($user)) {
            $user = wp_get_current_user();
        } elseif (is_numeric($user)) {
            $user = get_userdata($user);
        }

        if (!$user || !$user->exists()) {
            return false;
        }

        return in_array(self::ROLE_SCHOOL_TEACHER, $user->roles);
    }
}
