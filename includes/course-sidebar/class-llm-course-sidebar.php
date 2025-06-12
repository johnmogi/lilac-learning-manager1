<?php
/**
 * LLM Course Sidebar
 *
 * @package LilacLearningManager
 * @since 1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

class LLM_Course_Sidebar {
    /**
     * Plugin version.
     *
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * Instance of this class.
     *
     * @var LLM_Course_Sidebar
     */
    private static $instance = null;

    /**
     * Get the singleton instance of this class.
     *
     * @return LLM_Course_Sidebar
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks.
     */
    private function init_hooks() {
        add_shortcode('llm_topic_categories', array($this, 'render_topic_categories_shortcode'));
    }

    /**
     * Render the topic categories shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string Shortcode output.
     */
    public function render_topic_categories_shortcode($atts) {
        // Parse shortcode attributes
        $atts = shortcode_atts(
            array(
                'category_id' => 0,
                'show_count'  => true,
                'hierarchical' => true,
            ),
            $atts,
            'llm_topic_categories'
        );

        // Ensure LearnDash is active
        if (!class_exists('SFWD_LMS')) {
            return '<p>' . esc_html__('LearnDash LMS is required for this shortcode to work.', 'lilac-learning-manager') . '</p>';
        }

        // Get the current post
        $current_post = get_queried_object();
        if (!$current_post) {
            return '';
        }

        // Get the course ID
        $course_id = 0;
        if (is_a($current_post, 'WP_Post') && 'sfwd-courses' === $current_post->post_type) {
            $course_id = $current_post->ID;
        } elseif (is_singular(array('sfwd-lessons', 'sfwd-topic'))) {
            $course_id = get_post_meta($current_post->ID, 'course_id', true);
        }

        if (empty($course_id)) {
            return '';
        }

        // Get topics for the course
        $topics = learndash_get_course_topics($course_id, array($current_post->ID));
        
        if (empty($topics['topics'])) {
            return '<p>' . esc_html__('No topics found for this course.', 'lilac-learning-manager') . '</p>';
        }

        // Get topic categories
        $topic_categories = $this->get_topic_categories($topics['topics']);

        if (empty($topic_categories)) {
            return '<p>' . esc_html__('No topic categories found.', 'lilac-learning-manager') . '</p>';
        }

        // Filter by category_id if provided
        if (!empty($atts['category_id'])) {
            $category_ids = array_map('trim', explode(',', $atts['category_id']));
            $topic_categories = array_intersect_key(
                $topic_categories,
                array_flip($category_ids)
            );
        }

        // Start output buffering
        ob_start();
        
        // Include the template
        include plugin_dir_path(__FILE__) . 'templates/topic-categories.php';
        
        // Return the buffered content
        return ob_get_clean();
    }

    /**
     * Get topic categories from an array of topics.
     *
     * @param array $topics Array of topic objects.
     * @return array Array of topic categories with their topics.
     */
    private function get_topic_categories($topics) {
        $categories = array();
        
        foreach ($topics as $topic) {
            $topic_id = $topic->ID;
            $topic_categories = get_the_terms($topic_id, 'ld_topic_category');
            
            if (!empty($topic_categories) && !is_wp_error($topic_categories)) {
                foreach ($topic_categories as $category) {
                    if (!isset($categories[$category->term_id])) {
                        $categories[$category->term_id] = array(
                            'name' => $category->name,
                            'slug' => $category->slug,
                            'count' => 0,
                            'topics' => array(),
                        );
                    }
                    
                    $categories[$category->term_id]['count']++;
                    $categories[$category->term_id]['topics'][] = $topic;
                }
            }
        }
        
        return $categories;
    }
}

// Initialize the plugin
function llm_course_sidebar_init() {
    return LLM_Course_Sidebar::get_instance();
}
add_action('plugins_loaded', 'llm_course_sidebar_init');
