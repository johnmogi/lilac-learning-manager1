<?php
/**
 * Template for displaying course topics in a list
 *
 * @package LilacLearningManager
 * @since 1.0.0
 *
 * @var array $topics Array of topic objects
 * @var array $llm_atts Shortcode attributes
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Debug output
$debug = isset($llm_atts['debug']) ? filter_var($llm_atts['debug'], FILTER_VALIDATE_BOOLEAN) : false;
$show_lesson = isset($llm_atts['show_lesson']) ? filter_var($llm_atts['show_lesson'], FILTER_VALIDATE_BOOLEAN) : true;

if ($debug) {
    echo '<div class="llm-debug" style="background: #f0f0f0; padding: 10px; margin-bottom: 20px; border: 1px solid #ddd;">';
    echo '<h4>Course Topics Template Debug</h4>';
    echo '<p><strong>Number of topics:</strong> ' . count($topics) . '</p>';
    echo '<p><strong>Show lesson:</strong> ' . ($show_lesson ? 'Yes' : 'No') . '</p>';
    echo '</div>';
}
?>

<div class="llm-course-topics">
    <ul class="llm-topic-list">
        <?php foreach ($topics as $topic) : 
            $topic_id = $topic->ID;
            $topic_title = $topic->post_title;
            $topic_url = get_permalink($topic_id);
            $is_current = (get_queried_object_id() === $topic_id) ? 'current-topic' : '';
            
            // Get lesson info if needed
            $lesson_info = '';
            if ($show_lesson) {
                $lesson_id = get_post_meta($topic_id, 'lesson_id', true);
                if ($lesson_id) {
                    $lesson_title = get_the_title($lesson_id);
                    $lesson_info = '<span class="llm-lesson-name">' . esc_html($lesson_title) . ' &raquo; </span>';
                }
            }
            
            if (empty($topic_url)) continue; // Skip if no permalink found
            ?>
            <li class="llm-topic-item <?php echo esc_attr($is_current); ?>">
                <a href="<?php echo esc_url($topic_url); ?>">
                    <?php 
                    if ($show_lesson && !empty($lesson_info)) {
                        echo $lesson_info;
                    }
                    echo esc_html($topic_title); 
                    
                    if ($debug) : ?>
                        <span class="llm-topic-id">(#<?php echo (int) $topic_id; ?>)</span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
