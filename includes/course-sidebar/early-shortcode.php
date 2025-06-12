<?php
/**
 * Early Topics Shortcode
 * This file registers a shortcode very early in the WordPress lifecycle
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Debug logging
error_log('Early Topics Shortcode: File loaded');

/**
 * Enqueue styles for the early topics shortcode
 */
function llm_early_topics_enqueue_styles() {
    wp_enqueue_style(
        'llm-early-topics',
        plugin_dir_url(__FILE__) . 'assets/css/llm-early-topics.css',
        array(),
        '1.0.0'
    );
    error_log('Early Topics Shortcode: CSS enqueued');
}
add_action('wp_enqueue_scripts', 'llm_early_topics_enqueue_styles');

/**
 * Early topics shortcode callback
 * 
 * @param array $atts Shortcode attributes
 * @return string Shortcode output
 */
function llm_early_topics_shortcode($atts) {
    error_log('Early Topics Shortcode: Function called');
    
    // Parse attributes
    $atts = shortcode_atts(array(
        'debug' => 'false',
        'category_id' => 0,
        'category' => '',
        'acf_field' => '',
        'show_lesson' => true,
        'show_count' => true,
    ), $atts, 'llm_early_topics');
    
    $debug = filter_var($atts['debug'], FILTER_VALIDATE_BOOLEAN);
    $category_id = intval($atts['category_id']);
    $category_name = sanitize_text_field($atts['category']);
    $acf_field = sanitize_text_field($atts['acf_field']);
    $show_lesson = filter_var($atts['show_lesson'], FILTER_VALIDATE_BOOLEAN);
    $show_count = filter_var($atts['show_count'], FILTER_VALIDATE_BOOLEAN);
    
    // Check for ACF field if specified and ACF is active
    if (!empty($acf_field) && function_exists('get_field')) {
        // Get the current post ID (or course ID if we found it)
        $acf_post_id = $course_id ? $course_id : get_the_ID();
        
        // Get the ACF field value
        $acf_value = get_field($acf_field, $acf_post_id);
        
        if ($debug) {
            error_log('Early Topics Shortcode: ACF field ' . $acf_field . ' value: ' . print_r($acf_value, true));
        }
        
        // ACF field could be a category ID, term ID, or name
        if (!empty($acf_value)) {
            // If it's a number, use it as category_id
            if (is_numeric($acf_value)) {
                $category_id = intval($acf_value);
                if ($debug) {
                    echo '<p>Using ACF field value as category ID: ' . $category_id . '</p>';
                }
            } 
            // If it's a string, use it as category name
            else if (is_string($acf_value)) {
                $category_name = $acf_value;
                if ($debug) {
                    echo '<p>Using ACF field value as category name: ' . $category_name . '</p>';
                }
            }
            // If it's an array (like from a checkbox or select multiple)
            else if (is_array($acf_value) && !empty($acf_value[0])) {
                // Use the first value
                if (is_numeric($acf_value[0])) {
                    $category_id = intval($acf_value[0]);
                    if ($debug) {
                        echo '<p>Using first ACF array value as category ID: ' . $category_id . '</p>';
                    }
                } else {
                    $category_name = $acf_value[0];
                    if ($debug) {
                        echo '<p>Using first ACF array value as category name: ' . $category_name . '</p>';
                    }
                }
            }
        }
    }
    
    // Start output buffer
    ob_start();
    
    // Get current post
    $current_post = get_queried_object();
    $course_id = 0;
    
    // Debug info
    if ($debug) {
        echo '<div style="background: #f8f9fa; padding: 15px; border: 1px solid #ddd; margin-bottom: 20px;">';
        echo '<h4>Early Topics Debug Info</h4>';
        echo '<p>Shortcode executed successfully!</p>';
        
        // Show all registered shortcodes
        global $shortcode_tags;
        echo '<p>Registered shortcodes at execution time: ' . count($shortcode_tags) . '</p>';
        
        // Show current post info
        if ($current_post) {
            echo '<p>Current post: ' . $current_post->post_title . ' (ID: ' . $current_post->ID . ', Type: ' . $current_post->post_type . ')</p>';
        } else {
            echo '<p>No current post found.</p>';
        }
    }
    
    // Determine course ID
    if ($current_post) {
        if (is_a($current_post, 'WP_Post') && 'sfwd-courses' === $current_post->post_type) {
            $course_id = $current_post->ID;
        } elseif (is_singular(array('sfwd-lessons', 'sfwd-topic'))) {
            $course_id = get_post_meta($current_post->ID, 'course_id', true);
        }
        
        if ($debug) {
            echo '<p>Course ID: ' . $course_id . '</p>';
        }
    }
    
    if (empty($course_id)) {
        if ($debug) {
            echo '<p>Error: Could not determine course ID.</p></div>';
        }
        return ob_get_clean();
    }
    
    // Get all topics for this course
    $topics = array();
    $topic_categories = array();
    
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
                    // Get topic categories
                    $terms = wp_get_object_terms($topic->ID, 'ld_topic_category');
                    $topic_cats = array();
                    
                    if (!empty($terms) && !is_wp_error($terms)) {
                        foreach ($terms as $term) {
                            $topic_cats[] = $term->term_id;
                            
                            // Add to categories array if not already there
                            if (!isset($topic_categories[$term->term_id])) {
                                $topic_categories[$term->term_id] = array(
                                    'id' => $term->term_id,
                                    'name' => $term->name,
                                    'slug' => $term->slug,
                                    'count' => 1,
                                    'topics' => array(),
                                );
                            } else {
                                $topic_categories[$term->term_id]['count']++;
                            }
                            
                            // Add topic to this category
                            $topic_categories[$term->term_id]['topics'][] = array(
                                'id' => $topic->ID,
                                'title' => $topic->post_title,
                                'url' => get_permalink($topic->ID),
                                'lesson_id' => $lesson_id,
                                'lesson_title' => $lesson['post']->post_title
                            );
                        }
                    }
                    
                    // Add topic to main array
                    $topics[] = array(
                        'id' => $topic->ID,
                        'title' => $topic->post_title,
                        'url' => get_permalink($topic->ID),
                        'lesson_id' => $lesson_id,
                        'lesson_title' => $lesson['post']->post_title,
                        'categories' => $topic_cats,
                    );
                }
            }
        }
    }
    
    if ($debug) {
        echo '<p>Found ' . count($topics) . ' topics</p>';
        echo '<p>Found ' . count($topic_categories) . ' topic categories</p>';
        
        if ($category_id > 0) {
            echo '<p>Filtering by category ID: ' . $category_id . '</p>';
        }
        if (!empty($category_name)) {
            echo '<p>Filtering by category name: ' . $category_name . '</p>';
        }
    }
    
    // Filter topics by category if specified
    $filtered_topics = array();
    $target_category_id = 0;
    
    // First check if we have a category name to filter by
    if (!empty($category_name)) {
        // Find category ID by name
        foreach ($topic_categories as $cat_id => $cat_data) {
            if (strtolower($cat_data['name']) === strtolower($category_name) || 
                strtolower($cat_data['slug']) === strtolower($category_name)) {
                $target_category_id = $cat_id;
                break;
            }
        }
        
        if ($debug && $target_category_id > 0) {
            echo '<p>Found category ID ' . $target_category_id . ' for name "' . $category_name . '"</p>';
        }
    } else if ($category_id > 0) {
        // Use directly provided category ID
        $target_category_id = $category_id;
    }
    
    // Now filter by the determined category ID
    if ($target_category_id > 0) {
        if (isset($topic_categories[$target_category_id])) {
            $filtered_topics = $topic_categories[$target_category_id]['topics'];
            $category_id = $target_category_id; // Set for display purposes
            
            if ($debug) {
                echo '<p>After filtering: ' . count($filtered_topics) . ' topics</p>';
            }
        } else {
            if ($debug) {
                echo '<p>Category ID ' . $target_category_id . ' not found</p>';
            }
        }
    } else {
        $filtered_topics = $topics;
    }
    
    if ($debug) {
        echo '</div>'; // Close debug div
    }
    
    // Display topics
    if (!empty($filtered_topics)) {
        echo '<div class="llm-early-topics">';
        echo '<ul class="llm-topics-list">';
        
        // Simplified output - just a clean list of topics
        foreach ($filtered_topics as $topic) {
            $current_class = ($current_post && $current_post->ID === $topic['id']) ? ' class="current-topic"' : '';
            echo '<li' . $current_class . '>';
            echo '<a href="' . esc_url($topic['url']) . '">' . esc_html($topic['title']) . '</a>';
            echo '</li>';
        }
        
        echo '</ul>';
        echo '</div>'; // Close llm-early-topics div
    } else {
        echo '<div class="llm-early-topics">';
        echo '<p>No topics found for this course.</p>';
        echo '</div>';
    }
    
    // Return the output
    return ob_get_clean();
}

// Register the shortcode immediately
add_shortcode('llm_early_topics', 'llm_early_topics_shortcode');
error_log('Early Topics Shortcode: Registered llm_early_topics immediately');

// Also register on init with high priority
function llm_register_early_topics_shortcode() {
    add_shortcode('llm_early_topics', 'llm_early_topics_shortcode');
    error_log('Early Topics Shortcode: Registered llm_early_topics on init');
    
    // Debug - log all registered shortcodes
    global $shortcode_tags;
    error_log('Registered Shortcodes Count: ' . count($shortcode_tags));
    error_log('Is our shortcode registered? ' . (array_key_exists('llm_early_topics', $shortcode_tags) ? 'YES' : 'NO'));
}
add_action('init', 'llm_register_early_topics_shortcode', 1); // Priority 1 = very early
