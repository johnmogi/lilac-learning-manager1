<?php
// Security check - exit if accessed directly
defined('ABSPATH') || exit;

// Handle file upload
$message = '';
$message_type = '';

if (isset($_POST['llm_import_schools_nonce']) && wp_verify_nonce($_POST['llm_import_schools_nonce'], 'llm_import_schools')) {
    if (isset($_FILES['import_file']) && !empty($_FILES['import_file']['tmp_name'])) {
        $file = $_FILES['import_file'];
        
        // Check file type
        $filetype = wp_check_filetype($file['name'], ['csv' => 'text/csv', 'xls' => 'application/vnd.ms-excel', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
        
        if (in_array($filetype['ext'], ['csv', 'xls', 'xlsx'])) {
            // Process the file
            $imported = $this->process_import_file($file['tmp_name'], $filetype['ext']);
            
            if (!is_wp_error($imported)) {
                $message = sprintf(
                    _n('%s school imported successfully.', '%s schools imported successfully.', $imported, 'lilac-learning-manager'),
                    number_format_i18n($imported)
                );
                $message_type = 'success';
            } else {
                $message = $imported->get_error_message();
                $message_type = 'error';
            }
        } else {
            $message = __('Invalid file type. Please upload a CSV or Excel file.', 'lilac-learning-manager');
            $message_type = 'error';
        }
    } else {
        $message = __('Please select a file to import.', 'lilac-learning-manager');
        $message_type = 'error';
    }
}

// Display message if any
if (!empty($message)) {
    echo '<div class="notice notice-' . esc_attr($message_type) . ' is-dismissible"><p>' . esc_html($message) . '</p></div>';
}
?>

<div class="wrap">
    <h2><?php esc_html_e('Import Schools', 'lilac-learning-manager'); ?></h2>
    
    <div class="card">
        <h3><?php esc_html_e('Import from File', 'lilac-learning-manager'); ?></h3>
        <p><?php esc_html_e('Upload a CSV or Excel file containing school data to import.', 'lilac-learning-manager'); ?></p>
        
        <form method="post" enctype="multipart/form-data" class="llm-import-form">
            <?php wp_nonce_field('llm_import_schools', 'llm_import_schools_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="import_file"><?php esc_html_e('Choose File', 'lilac-learning-manager'); ?></label>
                    </th>
                    <td>
                        <input type="file" name="import_file" id="import_file" accept=".csv,.xls,.xlsx" required />
                        <p class="description">
                            <?php 
                            printf(
                                /* translators: %s: Download sample file link */
                                esc_html__('Download %s for reference.', 'lilac-learning-manager'),
                                '<a href="' . esc_url(LILAC_LEARNING_MANAGER_URL . 'assets/samples/schools-import-sample.csv') . '" target="_blank">' . esc_html__('sample file', 'lilac-learning-manager') . '</a>'
                            );
                            ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="import_behavior"><?php esc_html_e('Import Behavior', 'lilac-learning-manager'); ?></label>
                    </th>
                    <td>
                        <select name="import_behavior" id="import_behavior">
                            <option value="skip"><?php esc_html_e('Skip existing schools', 'lilac-learning-manager'); ?></option>
                            <option value="update"><?php esc_html_e('Update existing schools', 'lilac-learning-manager'); ?></option>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Choose what to do if a school with the same code already exists.', 'lilac-learning-manager'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary">
                    <?php esc_html_e('Import Schools', 'lilac-learning-manager'); ?>
                </button>
            </p>
        </form>
    </div>
    
    <div class="card">
        <h3><?php esc_html_e('File Format', 'lilac-learning-manager'); ?></h3>
        <p><?php esc_html_e('Your import file should be a CSV or Excel file with the following columns:', 'lilac-learning-manager'); ?></p>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Column', 'lilac-learning-manager'); ?></th>
                    <th><?php esc_html_e('Required', 'lilac-learning-manager'); ?></th>
                    <th><?php esc_html_e('Description', 'lilac-learning-manager'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>school_name</code></td>
                    <td><?php esc_html_e('Yes', 'lilac-learning-manager'); ?></td>
                    <td><?php esc_html_e('The name of the school', 'lilac-learning-manager'); ?></td>
                </tr>
                <tr>
                    <td><code>school_code</code></td>
                    <td><?php esc_html_e('Yes', 'lilac-learning-manager'); ?></td>
                    <td><?php esc_html_e('A unique code for the school', 'lilac-learning-manager'); ?></td>
                </tr>
                <tr>
                    <td><code>school_type</code></td>
                    <td><?php esc_html_e('No', 'lilac-learning-manager'); ?></td>
                    <td><?php esc_html_e('The type of school (e.g., Elementary, High School, University)', 'lilac-learning-manager'); ?></td>
                </tr>
                <tr>
                    <td><code>address</code></td>
                    <td><?php esc_html_e('No', 'lilac-learning-manager'); ?></td>
                    <td><?php esc_html_e('The school\'s address', 'lilac-learning-manager'); ?></td>
                </tr>
                <tr>
                    <td><code>city</code></td>
                    <td><?php esc_html_e('No', 'lilac-learning-manager'); ?></td>
                    <td><?php esc_html_e('The city where the school is located', 'lilac-learning-manager'); ?></td>
                </tr>
                <tr>
                    <td><code>country</code></td>
                    <td><?php esc_html_e('No', 'lilac-learning-manager'); ?></td>
                    <td><?php esc_html_e('The country where the school is located', 'lilac-learning-manager'); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<style>
.llm-import-form {
    max-width: 800px;
}
.llm-import-form .form-table th {
    width: 200px;
}
.llm-import-form .button {
    margin-top: 10px;
}
</style>
