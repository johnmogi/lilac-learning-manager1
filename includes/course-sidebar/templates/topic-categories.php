<?php
/**
 * Template for displaying topic categories in the sidebar.
 *
 * @package LilacLearningManager
 * @since 1.0.0
 *
 * @var array $topic_categories Array of topic categories.
 * @var array $atts Shortcode attributes.
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="llm-topic-categories">
    <?php foreach ($topic_categories as $category_id => $category) : ?>
        <div class="llm-topic-category">
            <h3 class="llm-topic-category-title">
                <?php echo esc_html($category['name']); ?>
                <?php if (!empty($atts['show_count'])) : ?>
                    <span class="llm-topic-count">(<?php echo (int) $category['count']; ?>)</span>
                <?php endif; ?>
            </h3>
            
            <?php if (!empty($category['topics'])) : ?>
                <ul class="llm-topic-list">
                    <?php foreach ($category['topics'] as $topic) : ?>
                        <li class="llm-topic-item<?php echo (get_queried_object_id() === $topic->ID) ? ' current-topic' : ''; ?>">
                            <a href="<?php echo esc_url(get_permalink($topic->ID)); ?>">
                                <?php echo esc_html($topic->post_title); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
