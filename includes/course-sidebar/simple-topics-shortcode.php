<?php
/**
 * Simple Topics Shortcode
 * 
 * @package LilacLearningManager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Debug logging
error_log('Simple Topics Shortcode: File loaded');

/**
 * Register the simple topics shortcode
 */
function llm_register_simple_topics_shortcode() {
    add_shortcode('llm_simple_topics', 'llm_simple_topics_shortcode');
    error_log('Simple Topics Shortcode: Registered llm_simple_topics');
}
add_action('init', 'llm_register_simple_topics_shortcode');

/**
 * Simple topics shortcode callback
 * 
 * @param array $atts Shortcode attributes
 * @return string Shortcode output
 */
function llm_simple_topics_shortcode($atts) {
    error_log('Simple Topics Shortcode: Shortcode function called');
    
    // Parse attributes
    $atts = shortcode_atts(array(
        'debug' => false,
    ), $atts, 'llm_simple_topics');
    
    $debug = filter_var($atts['debug'], FILTER_VALIDATE_BOOLEAN);
    
    // Start output buffer
    ob_start();
    
    // Debug info
    if ($debug) {
        echo '<div style="background: #f8f9fa; padding: 15px; border: 1px solid #ddd; margin-bottom: 20px;">';
        echo '<h4>Simple Topics Debug Info</h4>';
    }
    
    // Get current post
    $current_post = get_queried_object();
    
    if (!$current_post) {
        if ($debug) {
            echo '<p>Error: No current post found.</p></div>';
        }
        return ob_get_clean();
    }
    
    if ($debug) {
        echo '<p>Current post: ' . $current_post->post_title . ' (ID: ' . $current_post->ID . ', Type: ' . $current_post->post_type . ')</p>';
    }
    
    // Get course ID
    $course_id = 0;
    if (is_a($current_post, 'WP_Post') && 'sfwd-courses' === $current_post->post_type) {
        $course_id = $current_post->ID;
    } elseif (is_singular(array('sfwd-lessons', 'sfwd-topic'))) {
        $course_id = get_post_meta($current_post->ID, 'course_id', true);
    }
    
    if (empty($course_id)) {
        if ($debug) {
            echo '<p>Error: Could not determine course ID.</p></div>';
        }
        return ob_get_clean();
    }
    
    if ($debug) {
        echo '<p>Course ID: ' . $course_id . '</p>';
    }
    
    // Get all topics for this course
    $topics = array();
    
    if (function_exists('learndash_get_course_lessons_list')) {
        $lessons = learndash_get_course_lessons_list($course_id);
        
        if ($debug) {
            echo '<p>Found ' . count($lessons) . ' lessons</p>';
        }
        
        foreach ($lessons as $lesson) {
            $lesson_id = $lesson['post']->ID;
            $lesson_topics = learndash_get_topic_list($lesson_id, $course_id);
            
            if (!empty($lesson_topics)) {
                foreach ($lesson_topics as $topic) {
                    $topics[] = array(
                        'id' => $topic->ID,
                        'title' => $topic->post_title,
                        'url' => get_permalink($topic->ID),
                        'lesson_id' => $lesson_id,
                        'lesson_title' => $lesson['post']->post_title
                    );
                }
            }
        }
    }
    
    if (empty($topics)) {
        if ($debug) {
            echo '<p>No topics found for this course.</p></div>';
        }
        return ob_get_clean();
    }
    
    if ($debug) {
        echo '<p>Found ' . count($topics) . ' topics</p>';
    }
    
    // Display topics
    echo '<ul class="llm-simple-topics-list">';
    foreach ($topics as $topic) {
        $current_class = ($current_post->ID === $topic['id']) ? ' class="current-topic"' : '';
        echo '<li' . $current_class . '>';
        echo '<a href="' . esc_url($topic['url']) . '">';
        echo '<span class="llm-lesson-name">' . esc_html($topic['lesson_title']) . ' &raquo; </span>';
        echo esc_html($topic['title']);
        echo '</a>';
        echo '</li>';
    }
    echo '</ul>';
    
    if ($debug) {
        echo '</div>';
    }
    
    // Return the output
    return ob_get_clean();
}
