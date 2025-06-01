<?php
// Security check - exit if accessed directly
defined('ABSPATH') || exit;

// Handle export
if (isset($_POST['llm_export_schools_nonce']) && wp_verify_nonce($_POST['llm_export_schools_nonce'], 'llm_export_schools')) {
    // Set headers for file download
    $filename = 'schools-export-' . date('Y-m-d') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for Excel compatibility
    fputs($output, "\xEF\xBB\xBF");
    
    // Get filter parameters
    $school_type = isset($_POST['school_type']) ? sanitize_text_field($_POST['school_type']) : '';
    $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
    $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
    
    // Build query args
    $args = [
        'post_type'      => 'llm_school',
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
    if (!empty($school_type)) {
        $args['tax_query'] = [
            [
                'taxonomy' => 'llm_school_type',
                'field'    => 'term_id',
                'terms'    => $school_type,
            ],
        ];
    }
    
    // Get schools
    $query = new WP_Query($args);
    
    // Add CSV headers
    $headers = [
        'school_id',
        'school_name',
        'school_code',
        'school_type',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'phone',
        'email',
        'website',
        'contact_person',
        'contact_phone',
        'contact_email',
        'date_created',
        'date_modified',
    ];
    
    fputcsv($output, $headers);
    
    // Add school data
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $school_id = get_the_ID();
            
            // Get school type terms
            $school_types = wp_get_post_terms($school_id, 'llm_school_type', ['fields' => 'names']);
            $school_type = !empty($school_types) ? $school_types[0] : '';
            
            // Get school meta
            $school_meta = [
                'school_code'    => get_post_meta($school_id, '_llm_school_code', true),
                'address'        => get_post_meta($school_id, '_llm_school_address', true),
                'city'           => get_post_meta($school_id, '_llm_school_city', true),
                'state'          => get_post_meta($school_id, '_llm_school_state', true),
                'country'        => get_post_meta($school_id, '_llm_school_country', true),
                'postal_code'    => get_post_meta($school_id, '_llm_school_postal_code', true),
                'phone'          => get_post_meta($school_id, '_llm_school_phone', true),
                'email'          => get_post_meta($school_id, '_llm_school_email', true),
                'website'        => get_post_meta($school_id, '_llm_school_website', true),
                'contact_person' => get_post_meta($school_id, '_llm_school_contact_person', true),
                'contact_phone'  => get_post_meta($school_id, '_llm_school_contact_phone', true),
                'contact_email'  => get_post_meta($school_id, '_llm_school_contact_email', true),
            ];
            
            // Prepare row data
            $row = [
                'school_id'      => $school_id,
                'school_name'    => get_the_title(),
                'school_code'    => $school_meta['school_code'],
                'school_type'    => $school_type,
                'address'        => $school_meta['address'],
                'city'           => $school_meta['city'],
                'state'          => $school_meta['state'],
                'country'        => $school_meta['country'],
                'postal_code'    => $school_meta['postal_code'],
                'phone'          => $school_meta['phone'],
                'email'          => $school_meta['email'],
                'website'        => $school_meta['website'],
                'contact_person' => $school_meta['contact_person'],
                'contact_phone'  => $school_meta['contact_phone'],
                'contact_email'  => $school_meta['contact_email'],
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

// Get school types for filter dropdown
$school_types = get_terms([
    'taxonomy'   => 'llm_school_type',
    'hide_empty' => false,
]);
?>

<div class="wrap">
    <h2><?php esc_html_e('Export Schools', 'lilac-learning-manager'); ?></h2>
    
    <div class="card">
        <h3><?php esc_html_e('Export to CSV', 'lilac-learning-manager'); ?></h3>
        <p><?php esc_html_e('Export your schools data to a CSV file.', 'lilac-learning-manager'); ?></p>
        
        <form method="post" class="llm-export-form">
            <?php wp_nonce_field('llm_export_schools', 'llm_export_schools_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="school_type"><?php esc_html_e('Filter by School Type', 'lilac-learning-manager'); ?></label>
                    </th>
                    <td>
                        <select name="school_type" id="school_type">
                            <option value=""><?php esc_html_e('All Types', 'lilac-learning-manager'); ?></option>
                            <?php foreach ($school_types as $type) : ?>
                                <option value="<?php echo esc_attr($type->term_id); ?>">
                                    <?php echo esc_html($type->name); ?>
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
                            <?php esc_html_e('Leave empty to export all schools regardless of creation date.', 'lilac-learning-manager'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary">
                    <?php esc_html_e('Export Schools', 'lilac-learning-manager'); ?>
                </button>
            </p>
        </form>
    </div>
    
    <div class="card">
        <h3><?php esc_html_e('Export Options', 'lilac-learning-manager'); ?></h3>
        <p><?php esc_html_e('The exported file will include the following information for each school:', 'lilac-learning-manager'); ?></p>
        
        <ul class="ul-disc">
            <li><?php esc_html_e('School ID and Name', 'lilac-learning-manager'); ?></li>
            <li><?php esc_html_e('School Code and Type', 'lilac-learning-manager'); ?></li>
            <li><?php esc_html_e('Contact Information', 'lilac-learning-manager'); ?></li>
            <li><?php esc_html_e('Address Details', 'lilac-learning-manager'); ?></li>
            <li><?php esc_html_e('Creation and Modification Dates', 'lilac-learning-manager'); ?></li>
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
