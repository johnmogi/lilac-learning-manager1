<?php
/**
 * Question Admin
 *
 * Handles the admin interface for managing LearnDash questions.
 *
 * @package LilacLearningManager\Admin
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

class QuestionAdmin {
    /**
     * The page slug for the questions page.
     *
     * @var string
     */
    private $page_slug = 'lilac-questions';

    /**
     * The capability required to access this admin area.
     *
     * @var string
     */
    private $capability = 'manage_options';

    /**
     * Constructor.
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_llm_update_question', array($this, 'ajax_update_question'));
        add_action('wp_ajax_llm_get_questions', array($this, 'ajax_get_questions'));
    }

    /**
     * Add the questions page to the admin menu.
     */
    public function add_admin_menu() {
        add_submenu_page(
            'lilac-learning-manager',
            __('Questions', 'lilac-learning-manager'),
            __('Questions', 'lilac-learning-manager'),
            $this->capability,
            $this->page_slug,
            array($this, 'render_page')
        );
    }

    /**
     * Enqueue admin scripts and styles.
     *
     * @param string $hook The current admin page.
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, $this->page_slug) === false) {
            return;
        }

        // Enqueue styles
        wp_enqueue_style(
            'llm-question-admin',
            plugins_url('../../assets/css/question-admin.css', __FILE__),
            array(),
            LILAC_LEARNING_MANAGER_VERSION
        );

        // Enqueue scripts
        wp_enqueue_script(
            'llm-question-admin',
            plugins_url('../../assets/js/question-admin.js', __FILE__),
            array('jquery', 'wp-util'),
            LILAC_LEARNING_MANAGER_VERSION,
            true
        );

        // Localize script with AJAX URL and nonce
        wp_localize_script(
            'llm-question-admin',
            'llmQuestionAdmin',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('llm_question_admin_nonce'),
                'i18n' => array(
                    'saving' => __('Saving...', 'lilac-learning-manager'),
                    'saved' => __('Saved!', 'lilac-learning-manager'),
                    'error' => __('Error saving changes.', 'lilac-learning-manager'),
                ),
            )
        );
    }

    /**
     * Render the questions page.
     */
    public function render_page() {
        if (!current_user_can($this->capability)) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'lilac-learning-manager'));
        }

        // Load the template
        include plugin_dir_path(__FILE__) . '../../admin/views/questions/list.php';
    }

    /**
     * AJAX handler for updating a question.
     */
    public function ajax_update_question() {
        check_ajax_referer('llm_question_admin_nonce', 'nonce');

        if (!current_user_can($this->capability)) {
            wp_send_json_error(array('message' => __('Permission denied.', 'lilac-learning-manager')));
        }

        $question_id = isset($_POST['question_id']) ? intval($_POST['question_id']) : 0;
        $field = isset($_POST['field']) ? sanitize_text_field($_POST['field']) : '';
        $value = isset($_POST['value']) ? wp_kses_post($_POST['value']) : '';

        if (!$question_id || !$field) {
            wp_send_json_error(array('message' => __('Invalid request.', 'lilac-learning-manager')));
        }

        // Update the question field
        $result = $this->update_question_field($question_id, $field, $value);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array(
            'message' => __('Question updated successfully.', 'lilac-learning-manager'),
            'data' => $result,
        ));
    }

    /**
     * AJAX handler for getting filtered questions.
     */
    public function ajax_get_questions() {
        check_ajax_referer('llm_question_admin_nonce', 'nonce');

        if (!current_user_can($this->capability)) {
            wp_send_json_error(array('message' => __('Permission denied.', 'lilac-learning-manager')));
        }

        $filters = array(
            'search' => isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '',
            'program_id' => isset($_GET['program_id']) ? intval($_GET['program_id']) : 0,
            'course_id' => isset($_GET['course_id']) ? intval($_GET['course_id']) : 0,
            'topic_id' => isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0,
            'paged' => isset($_GET['paged']) ? intval($_GET['paged']) : 1,
            'per_page' => isset($_GET['per_page']) ? intval($_GET['per_page']) : 20,
        );

        $questions = $this->get_questions($filters);
        $total = $this->count_questions($filters);

        wp_send_json_success(array(
            'questions' => $questions,
            'pagination' => array(
                'total' => $total,
                'pages' => ceil($total / $filters['per_page']),
                'current' => $filters['paged'],
                'per_page' => $filters['per_page'],
            ),
        ));
    }

    /**
     * Update a question field.
     *
     * @param int    $question_id The question ID.
     * @param string $field       The field to update.
     * @param mixed  $value       The new value.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    private function update_question_field($question_id, $field, $value) {
        $question = get_post($question_id);

        if (!$question || $question->post_type !== 'sfwd-question') {
            return new WP_Error('invalid_question', __('Invalid question ID.', 'lilac-learning-manager'));
        }

        $result = false;

        switch ($field) {
            case 'post_title':
                $result = wp_update_post(array(
                    'ID' => $question_id,
                    'post_title' => $value,
                ));
                break;

            case 'post_content':
                $result = wp_update_post(array(
                    'ID' => $question_id,
                    'post_content' => $value,
                ));
                break;

            case 'hint':
                $result = update_post_meta($question_id, '_llm_question_hint', $value);
                break;

            case 'points':
                $result = update_post_meta($question_id, 'question_points', floatval($value));
                break;

            default:
                return new WP_Error('invalid_field', __('Invalid field specified.', 'lilac-learning-manager'));
        }

        if (is_wp_error($result)) {
            return $result;
        }

        if ($result === 0) {
            return new WP_Error('update_failed', __('Failed to update question.', 'lilac-learning-manager'));
        }

        return true;
    }

    /**
     * Get questions based on filters.
     *
     * @param array $filters The filters to apply.
     * @return array The filtered questions.
     */
    private function get_questions($filters = array()) {
        $args = array(
            'post_type' => 'sfwd-question',
            'post_status' => 'publish',
            'posts_per_page' => $filters['per_page'],
            'paged' => $filters['paged'],
            's' => $filters['search'],
        );

        // Add taxonomy query if needed
        $tax_query = array();

        if (!empty($filters['program_id'])) {
            $tax_query[] = array(
                'taxonomy' => 'ld_program',
                'field' => 'term_id',
                'terms' => $filters['program_id'],
            );
        }

        if (!empty($filters['course_id'])) {
            $tax_query[] = array(
                'taxonomy' => 'ld_course',
                'field' => 'term_id',
                'terms' => $filters['course_id'],
            );
        }

        if (!empty($filters['topic_id'])) {
            $tax_query[] = array(
                'taxonomy' => 'ld_topic',
                'field' => 'term_id',
                'terms' => $filters['topic_id'],
            );
        }

        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }

        $query = new WP_Query($args);
        $questions = array();

        if ($query->have_posts()) {
            foreach ($query->posts as $post) {
                $questions[] = $this->format_question($post);
            }
        }

        return $questions;
    }

    /**
     * Count questions based on filters.
     *
     * @param array $filters The filters to apply.
     * @return int The number of questions.
     */
    private function count_questions($filters = array()) {
        $args = array(
            'post_type' => 'sfwd-question',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            's' => $filters['search'],
        );

        // Add taxonomy query if needed
        $tax_query = array();

        if (!empty($filters['program_id'])) {
            $tax_query[] = array(
                'taxonomy' => 'ld_program',
                'field' => 'term_id',
                'terms' => $filters['program_id'],
            );
        }

        if (!empty($filters['course_id'])) {
            $tax_query[] = array(
                'taxonomy' => 'ld_course',
                'field' => 'term_id',
                'terms' => $filters['course_id'],
            );
        }

        if (!empty($filters['topic_id'])) {
            $tax_query[] = array(
                'taxonomy' => 'ld_topic',
                'field' => 'term_id',
                'terms' => $filters['topic_id'],
            );
        }

        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }

        $query = new WP_Query($args);
        return $query->found_posts;
    }

    /**
     * Format a question for display.
     *
     * @param WP_Post $post The question post object.
     * @return array The formatted question data.
     */
    private function format_question($post) {
        $question_data = array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'content' => $post->post_content,
            'hint' => get_post_meta($post->ID, '_llm_question_hint', true),
            'points' => get_post_meta($post->ID, 'question_points', true) ?: 1,
            'edit_url' => get_edit_post_link($post->ID, 'raw'),
        );

        // Get course and program information
        $courses = wp_get_post_terms($post->ID, 'ld_course');
        if (!empty($courses) && !is_wp_error($courses)) {
            $course = $courses[0];
            $question_data['course'] = array(
                'id' => $course->term_id,
                'name' => $course->name,
                'edit_url' => get_edit_term_link($course->term_id, 'ld_course'),
            );

            // Get program if available
            $programs = get_terms(array(
                'taxonomy' => 'ld_program',
                'object_ids' => $post->ID,
                'fields' => 'all',
            ));

            if (!empty($programs) && !is_wp_error($programs)) {
                $program = $programs[0];
                $question_data['program'] = array(
                    'id' => $program->term_id,
                    'name' => $program->name,
                    'edit_url' => get_edit_term_link($program->term_id, 'ld_program'),
                );
            }
        }

        return $question_data;
    }
}

// Initialize the question admin
function lilac_learning_manager_question_admin() {
    return new QuestionAdmin();
}
add_action('plugins_loaded', 'lilac_learning_manager_question_admin');
