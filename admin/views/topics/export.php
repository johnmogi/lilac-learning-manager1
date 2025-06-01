<?php
// Security check - exit if accessed directly
defined('ABSPATH') || exit;

// Handle export
if (isset($_POST['llm_export_topics_nonce']) && wp_verify_nonce($_POST['llm_export_topics_nonce'], 'llm_export_topics')) {
    // Set headers for file download
    $filename = 'topics-export-' . date('Y-m-d') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for Excel compatibility
    fputs($output, "\xEF\xBB\xBF");
    
    // Get filter parameters
    $category = isset($_POST['category']) ? intval($_POST['category']) : 0;
    $difficulty = isset($_POST['difficulty']) ? sanitize_text_field($_POST['difficulty']) : '';
    $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
    $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
    
    // Build query args
    $args = [
        'post_type'      => 'llm_topic',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ];
    
    // Add date filters
    if (!empty($date_from) || !empty($date_to)) {
        $date_query = [];
        
        if (!empty($date_from)) {
            $date_query['after'] = $date_from;
        }
        
        if (!empty($date_to)) {
            $date_query['before'] = $date_to;
        }
        
        $date_query['inclusive'] = true;
        $args['date_query'] = [$date_query];
    }
    
    // Add taxonomy filter
    if (!empty($category)) {
        $args['tax_query'] = [
            [
                'taxonomy' => 'llm_topic_category',
                'field'    => 'term_id',
                'terms'    => $category,
            ],
        ];
    }
    
    // Add difficulty filter
    if (!empty($difficulty)) {
        $args['meta_query'] = [
            [
                'key'     => '_llm_topic_difficulty',
                'value'   => $difficulty,
                'compare' => '=',
            ],
        ];
    }
    
    // Get topics
    $query = new WP_Query($args);
    
    // Add CSV headers
    $headers = [
        'topic_id',
        'topic_title',
        'topic_slug',
        'topic_content',
        'topic_excerpt',
        'categories',
        'difficulty',
        'duration',
        'featured_image',
        'status',
        'author',
        'date_created',
        'date_modified',
    ];
    
    fputcsv($output, $headers);
    
    // Add topic data
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $topic_id = get_the_ID();
            
            // Get topic categories
            $categories = wp_get_post_terms($topic_id, 'llm_topic_category', ['fields' => 'names']);
            $category_list = !empty($categories) ? implode(', ', $categories) : '';
            
            // Get topic meta
            $topic_meta = [
                'difficulty' => get_post_meta($topic_id, '_llm_topic_difficulty', true),
                'duration'   => get_post_meta($topic_id, '_llm_topic_duration', true),
            ];
            
            // Get featured image
            $featured_image = '';
            $thumbnail_id = get_post_thumbnail_id($topic_id);
            if ($thumbnail_id) {
                $featured_image = wp_get_attachment_url($thumbnail_id);
            }
            
            // Prepare row data
            $row = [
                'topic_id'       => $topic_id,
                'topic_title'    => get_the_title(),
                'topic_slug'     => get_post_field('post_name'),
                'topic_content'  => get_the_content(),
                'topic_excerpt'  => get_the_excerpt(),
                'categories'     => $category_list,
                'difficulty'     => $topic_meta['difficulty'],
                'duration'       => $topic_meta['duration'],
                'featured_image' => $featured_image,
                'status'         => get_post_status(),
                'author'         => get_the_author(),
                'date_created'   => get_the_date('Y-m-d H:i:s'),
                'date_modified'  => get_the_modified_date('Y-m-d H:i:s'),
            ];
            
            // Add row to CSV
            fputcsv($output, array_values($row));
        }
        wp_reset_postdata();
    }
    
    fclose($output);
    exit;
}

// Get categories for filter dropdown
$categories = get_terms([
    'taxonomy'   => 'llm_topic_category',
    'hide_empty' => false,
]);

// Difficulty levels
$difficulty_levels = [
    '' => __('All Levels', 'lilac-learning-manager'),
    'beginner' => __('Beginner', 'lilac-learning-manager'),
    'intermediate' => __('Intermediate', 'lilac-learning-manager'),
    'advanced' => __('Advanced', 'lilac-learning-manager'),
];
?>

<div class="wrap">
    <h2><?php esc_html_e('Export Topics', 'lilac-learning-manager'); ?></h2>
    
    <div class="card">
        <h3><?php esc_html_e('Export to CSV', 'lilac-learning-manager'); ?></h3>
        <p><?php esc_html_e('Export your topics data to a CSV file.', 'lilac-learning-manager'); ?></p>
        
        <form method="post" class="llm-export-form">
            <?php wp_nonce_field('llm_export_topics', 'llm_export_topics_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="category"><?php esc_html_e('Filter by Category', 'lilac-learning-manager'); ?></label>
                    </th>
                    <td>
                        <select name="category" id="category">
                            <option value=""><?php esc_html_e('All Categories', 'lilac-learning-manager'); ?></option>
                            <?php foreach ($categories as $cat) : ?>
                                <option value="<?php echo esc_attr($cat->term_id); ?>">
                                    <?php echo esc_html($cat->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="difficulty"><?php esc_html_e('Filter by Difficulty', 'lilac-learning-manager'); ?></label>
                    </th>
                    <td>
                        <select name="difficulty" id="difficulty">
                            <?php foreach ($difficulty_levels as $value => $label) : ?>
                                <option value="<?php echo esc_attr($value); ?>">
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="date_from"><?php esc_html_e('Date Range', 'lilac-learning-manager'); ?></label>
                    </th>
                    <td>
                        <input type="date" name="date_from" id="date_from" class="regular-text" />
                        <span class="date-separator"><?php esc_html_e('to', 'lilac-learning-manager'); ?></span>
                        <input type="date" name="date_to" id="date_to" class="regular-text" />
                        <p class="description">
                            <?php esc_html_e('Leave empty to export all topics regardless of creation date.', 'lilac-learning-manager'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary">
                    <?php esc_html_e('Export Topics', 'lilac-learning-manager'); ?>
                </button>
            </p>
        </form>
    </div>
    
    <div class="card">
        <h3><?php esc_html_e('Export Options', 'lilac-learning-manager'); ?></h3>
        <p><?php esc_html_e('The exported file will include the following information for each topic:', 'lilac-learning-manager'); ?></p>
        
        <ul class="ul-disc">
            <li><?php esc_html_e('Topic ID, Title, and Slug', 'lilac-learning-manager'); ?></li>
            <li><?php esc_html_e('Content and Excerpt', 'lilac-learning-manager'); ?></li>
            <li><?php esc_html_e('Categories and Difficulty Level', 'lilac-learning-manager'); ?></li>
            <li><?php esc_html_e('Duration and Featured Image URL', 'lilac-learning-manager'); ?></li>
            <li><?php esc_html_e('Status, Author, and Dates', 'lilac-learning-manager'); ?></li>
        </ul>
        
        <p>
            <strong><?php esc_html_e('Note:', 'lilac-learning-manager'); ?></strong> 
            <?php esc_html_e('The exported file will be in CSV format, which can be opened in spreadsheet applications like Microsoft Excel or Google Sheets.', 'lilac-learning-manager'); ?>
        </p>
    </div>
</div>

<style>
.llm-export-form .form-table th {
    width: 200px;
}
.llm-export-form .date-separator {
    margin: 0 10px;
    vertical-align: middle;
}
.llm-export-form .button {
    margin-top: 10px;
}
</style>
