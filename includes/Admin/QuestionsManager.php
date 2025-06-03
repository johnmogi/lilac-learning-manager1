<?php
/**
 * Handles the Questions management in admin
 *
 * @package LilacLearningManager\Admin
 */

namespace LilacLearningManager\Admin;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

class QuestionsManager {
    /**
     * Initialize the class
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_llm_get_questions', array($this, 'ajax_get_questions'));
        add_action('wp_ajax_llm_get_question', array($this, 'ajax_get_question'));
        add_action('wp_ajax_llm_save_question', array($this, 'ajax_save_question'));
        add_action('wp_ajax_llm_delete_questions', array($this, 'ajax_delete_questions'));
    }

    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        add_submenu_page(
            'lilac-learning-manager',
            'שאלות',
            'שאלות',
            'manage_options',
            'llm-questions',
            array($this, 'render_questions_page')
        );
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts($hook) {
        if ('lilac-learning-manager_page_llm-questions' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'llm-questions-admin',
            plugin_dir_url(dirname(__DIR__)) . 'assets/css/questions-admin.css',
            array(),
            LILAC_LEARNING_MANAGER_VERSION
        );

        wp_enqueue_script(
            'llm-questions-admin',
            plugin_dir_url(dirname(__DIR__)) . 'assets/js/questions-admin.js',
            array('jquery'),
            LILAC_LEARNING_MANAGER_VERSION,
            true
        );

        wp_localize_script('llm-questions-admin', 'llmQuestions', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('llm_questions_nonce'),
            'i18n' => array(
                'saving' => 'שומר...',
                'saved' => 'נשמר',
                'error' => 'שגיאה בשמירה',
                'confirm_delete' => 'האם אתה בטוח שברצונך למחוק את השאלה הנבחרת? פעולה זו אינה ניתנת לביטול.'
            )
        ));
    }

    /**
     * Render the questions management page
     */
    public function render_questions_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Include the template
        include_once plugin_dir_path(dirname(__DIR__)) . 'admin/views/questions/manage.php';
    }

    /**
     * AJAX: Get questions list
     */
    public function ajax_get_questions() {
        check_ajax_referer('llm_questions_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'אין הרשאה מתאימה'));
        }

        $paged = isset($_POST['paged']) ? absint($_POST['paged']) : 1;
        $program_id = isset($_POST['program_id']) ? intval($_POST['program_id']) : 0;
        $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
        $per_page = 20;
        
        $args = array(
            'post_type' => 'sfwd-question',
            'posts_per_page' => $per_page,
            'paged' => $paged,
            'orderby' => 'date',
            'order' => 'DESC',
            'suppress_filters' => false
        );

        // Add program filter if set
        if ($program_id) {
            $args['meta_query'][] = array(
                'key' => '_llm_question_program',
                'value' => $program_id,
                'compare' => '='
            );
        }

        // Add course filter if set
        if ($course_id) {
            $args['meta_query'][] = array(
                'key' => 'course_id',
                'value' => $course_id,
                'compare' => '='
            );
        }

        $query = new \WP_Query($args);
        $questions = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                $questions[] = array(
                    'id' => $post_id,
                    'title' => get_the_title(),
                    'hint' => get_post_meta($post_id, '_llm_question_hint', true),
                    'program' => $this->get_program_name(get_post_meta($post_id, '_llm_question_program', true)),
                    'course' => $this->get_course_name(get_post_meta($post_id, 'course_id', true)),
                    'date_created' => get_the_date('d/m/Y'),
                    'edit_link' => get_edit_post_link($post_id, '')
                );
            }
            wp_reset_postdata();
        }

        wp_send_json_success(array(
            'questions' => $questions,
            'max_num_pages' => $query->max_num_pages,
            'found_posts' => $query->found_posts
        ));
    }

    /**
     * AJAX: Get single question data
     */
    public function ajax_get_question() {
        check_ajax_referer('llm_questions_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'אין הרשאה מתאימה'));
        }

        $question_id = isset($_POST['question_id']) ? intval($_POST['question_id']) : 0;
        
        if (!$question_id) {
            wp_send_json_error(array('message' => 'מזהה שאלה לא תקין'));
        }

        $question = get_post($question_id);
        
        if (!$question || $question->post_type !== 'sfwd-question') {
            wp_send_json_error(array('message' => 'שאלה לא נמצאה'));
        }

        $program_id = get_post_meta($question_id, '_llm_question_program', true);
        $course_id = get_post_meta($question_id, 'course_id', true);
        
        $response = array(
            'id' => $question_id,
            'title' => $question->post_title,
            'hint' => get_post_meta($question_id, '_llm_question_hint', true),
            'program_id' => $program_id,
            'course_id' => $course_id,
            'programs' => $this->get_programs_list($program_id),
            'courses' => $this->get_courses_list($course_id, $program_id)
        );

        wp_send_json_success($response);
    }

    /**
     * AJAX: Save question
     */
    public function ajax_save_question() {
        check_ajax_referer('llm_questions_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'אין הרשאה מתאימה'));
        }

        $question_id = isset($_POST['question_id']) ? intval($_POST['question_id']) : 0;
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $hint = isset($_POST['hint']) ? sanitize_textarea_field($_POST['hint']) : '';
        $program_id = isset($_POST['program_id']) ? intval($_POST['program_id']) : 0;
        $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;

        if (empty($title)) {
            wp_send_json_error(array('message' => 'כותרת השאלה חובה'));
        }

        $post_data = array(
            'ID' => $question_id,
            'post_title' => $title,
            'post_type' => 'sfwd-question',
            'post_status' => 'publish'
        );

        if ($question_id) {
            // Update existing question
            $result = wp_update_post($post_data, true);
        } else {
            // Create new question
            $result = wp_insert_post($post_data, true);
        }

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        // Save meta
        update_post_meta($result, '_llm_question_hint', $hint);
        update_post_meta($result, '_llm_question_program', $program_id);
        update_post_meta($result, 'course_id', $course_id);

        wp_send_json_success(array(
            'message' => 'השאלה נשמרה בהצלחה',
            'question_id' => $result
        ));
    }

    /**
     * AJAX: Delete questions
     */
    public function ajax_delete_questions() {
        check_ajax_referer('llm_questions_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'אין הרשאה מתאימה'));
        }

        if (empty($_POST['question_ids']) || !is_array($_POST['question_ids'])) {
            wp_send_json_error(array('message' => 'לא נבחרו שאלות למחיקה'));
        }

        $deleted = 0;
        $question_ids = array_map('intval', $_POST['question_ids']);
        
        foreach ($question_ids as $question_id) {
            if (wp_delete_post($question_id, true)) {
                $deleted++;
            }
        }

        if ($deleted === 0) {
            wp_send_json_error(array('message' => 'לא נמחקו שאלות'));
        }

        wp_send_json_success(array(
            'message' => sprintf('נמחקו %d שאלות', $deleted),
            'deleted' => $deleted
        ));
    }

    /**
     * Get programs list for select
     */
    private function get_programs_list($selected_id = 0) {
        $programs = get_terms(array(
            'taxonomy' => 'llm_program',
            'hide_empty' => false,
        ));

        $options = array();
        foreach ($programs as $program) {
            $options[] = array(
                'id' => $program->term_id,
                'name' => $program->name,
                'selected' => ($program->term_id == $selected_id)
            );
        }
        return $options;
    }

    /**
     * Get courses list for select
     */
    private function get_courses_list($selected_id = 0, $program_id = 0) {
        $args = array(
            'post_type' => 'sfwd-courses',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        );

        if ($program_id) {
            $args['meta_query'] = array(
                array(
                    'key' => '_llm_course_program',
                    'value' => $program_id,
                    'compare' => '='
                )
            );
        }

        $query = new \WP_Query($args);
        $courses = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $course_id = get_the_ID();
                $courses[] = array(
                    'id' => $course_id,
                    'name' => get_the_title(),
                    'selected' => ($course_id == $selected_id)
                );
            }
            wp_reset_postdata();
        }

        return $courses;
    }

    /**
     * Get program name by ID
     */
    private function get_program_name($program_id) {
        if (!$program_id) return '';
        $program = get_term($program_id, 'llm_program');
        return $program && !is_wp_error($program) ? $program->name : '';
    }

    /**
     * Get course name by ID
     */
    private function get_course_name($course_id) {
        if (!$course_id) return '';
        $course = get_post($course_id);
        return $course ? $course->post_title : '';
    }
}

// Initialize the class
new QuestionsManager();
