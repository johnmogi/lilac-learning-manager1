<?php
/**
 * Direct Topics Shortcode
 * This file registers a simple shortcode that works independently of the class structure
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Debug logging
error_log('Direct Topics Shortcode: File loaded');

/**
 * Enqueue styles for the direct topics shortcode
 */
function llm_direct_topics_enqueue_styles() {
    wp_enqueue_style(
        'llm-direct-topics',
        plugin_dir_url(__FILE__) . 'assets/css/llm-direct-topics.css',
        array(),
        '1.0.0'
    );
    error_log('Direct Topics Shortcode: CSS enqueued');
}
add_action('wp_enqueue_scripts', 'llm_direct_topics_enqueue_styles');

/**
 * Register the direct topics shortcode
 */
function llm_register_direct_topics_shortcode() {
    add_shortcode('llm_direct_topics', 'llm_direct_topics_shortcode');
    error_log('Direct Topics Shortcode: Registered llm_direct_topics');
}
add_action('init', 'llm_register_direct_topics_shortcode');

/**
 * Direct topics shortcode callback
 * 
 * @param array $atts Shortcode attributes
 * @return string Shortcode output
 */
function llm_direct_topics_shortcode($atts) {
    error_log('Direct Topics Shortcode: Shortcode function called');
    
    // Parse attributes
    $atts = shortcode_atts(array(
        'debug' => 'true',
    ), $atts, 'llm_direct_topics');
    
    $debug = filter_var($atts['debug'], FILTER_VALIDATE_BOOLEAN);
    
    // Start output buffer
    ob_start();
    
    // Debug info
    if ($debug) {
        echo '<div style="background: #f8f9fa; padding: 15px; border: 1px solid #ddd; margin-bottom: 20px;">';
        echo '<h4>Direct Topics Debug Info</h4>';
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
    echo '<ul class="llm-direct-topics-list">';
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

// Add direct shortcode to the main plugin
add_action('init', function() {
    error_log('Direct Topics Shortcode: Adding shortcode via init hook');
    if (!shortcode_exists('llm_direct_topics')) {
        add_shortcode('llm_direct_topics', 'llm_direct_topics_shortcode');
    }
}, 20);
