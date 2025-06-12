<?php
namespace LilacLearningManager\Subscriptions;

/**
 * Manages course meta for subscription settings
 */
class Course_Meta {
    /**
     * @var Subscription_Types
     */
    private $subscription_types;
    
    /**
     * Initialize course meta
     * 
     * @param Subscription_Types $subscription_types
     */
    public function __construct($subscription_types) {
        $this->subscription_types = $subscription_types;
        $this->setup_hooks();
    }
    
    /**
     * Set up WordPress hooks
     */
    private function setup_hooks() {
        // Add meta box to course edit screen
        add_action('add_meta_boxes', [$this, 'add_subscription_meta_box']);
        
        // Save meta box data
        add_action('save_post_sfwd-courses', [$this, 'save_subscription_meta'], 10, 2);
        
        // Add custom column to courses list
        add_filter('manage_sfwd-courses_posts_columns', [$this, 'add_subscription_column']);
        add_action('manage_sfwd-courses_posts_custom_column', [$this, 'render_subscription_column'], 10, 2);
    }
    
    /**
     * Add subscription meta box to course edit screen
     */
    public function add_subscription_meta_box() {
        add_meta_box(
            'lilac_subscription_settings',
            __('Subscription Settings', 'lilac-learning-manager'),
            [$this, 'render_subscription_meta_box'],
            'sfwd-courses',
            'side',
            'default'
        );
    }
    
    /**
     * Render subscription meta box
     * 
     * @param \WP_Post $post Current post object
     */
    public function render_subscription_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('lilac_subscription_meta_box', 'lilac_subscription_meta_nonce');
        
        // Get current values
        $requires_subscription = get_post_meta($post->ID, '_lilac_requires_subscription', true);
        $requires_subscription = $requires_subscription !== 'no'; // Default to yes
        
        $subscription_type = get_post_meta($post->ID, '_lilac_subscription_type', true);
        $subscription_type = $subscription_type ?: Subscription_Types::TYPE_TIME_LIMITED; // Default
        
        $allowed_options = get_post_meta($post->ID, '_lilac_subscription_options', true);
        $allowed_options = $allowed_options ? maybe_unserialize($allowed_options) : [];
        
        $requires_manual_activation = get_post_meta($post->ID, '_lilac_requires_manual_activation', true);
        $requires_manual_activation = $requires_manual_activation === 'yes';
        
        // Get all subscription types
        $types = $this->subscription_types->get_subscription_types();
        
        ?>
        <div class="lilac-subscription-settings">
            <p>
                <label>
                    <input type="checkbox" name="lilac_requires_subscription" value="yes" 
                           <?php checked($requires_subscription); ?>>
                    <?php _e('Require subscription for access', 'lilac-learning-manager'); ?>
                </label>
            </p>
            
            <p>
                <label>
                    <input type="checkbox" name="lilac_requires_manual_activation" value="yes" 
                           <?php checked($requires_manual_activation); ?>>
                    <?php _e('Require manual activation', 'lilac-learning-manager'); ?>
                </label>
                <span class="description">
                    <?php _e('If checked, users will need to manually activate their subscription after purchase.', 'lilac-learning-manager'); ?>
                </span>
            </p>
            
            <div class="lilac-subscription-options" style="margin-top: 15px; <?php echo !$requires_subscription ? 'display: none;' : ''; ?>">
                <p>
                    <label for="lilac_subscription_type"><?php _e('Subscription Type:', 'lilac-learning-manager'); ?></label>
                    <select name="lilac_subscription_type" id="lilac_subscription_type" style="width: 100%;">
                        <?php foreach ($types as $type_id => $type) : ?>
                            <option value="<?php echo esc_attr($type_id); ?>" <?php selected($subscription_type, $type_id); ?>>
                                <?php echo esc_html($type['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="description">
                        <?php _e('Select the subscription model for this course.', 'lilac-learning-manager'); ?>
                    </span>
                </p>
                
                <?php foreach ($types as $type_id => $type) : ?>
                    <div class="lilac-subscription-type-options" id="lilac-options-<?php echo esc_attr($type_id); ?>" 
                         style="<?php echo $subscription_type !== $type_id ? 'display: none;' : ''; ?>">
                        <p><strong><?php _e('Allowed Options:', 'lilac-learning-manager'); ?></strong></p>
                        
                        <?php 
                        $options = $this->subscription_types->get_type_options($type_id);
                        foreach ($options as $option_id => $option) : 
                        ?>
                            <p>
                                <label>
                                    <input type="checkbox" name="lilac_subscription_options[<?php echo esc_attr($type_id); ?>][]" 
                                           value="<?php echo esc_attr($option_id); ?>" 
                                           <?php checked(in_array($option_id, isset($allowed_options[$type_id]) ? $allowed_options[$type_id] : [])); ?>>
                                    <?php echo esc_html($option['label']); ?>
                                </label>
                            </p>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Toggle subscription options
            $('input[name="lilac_requires_subscription"]').change(function() {
                if ($(this).is(':checked')) {
                    $('.lilac-subscription-options').show();
                } else {
                    $('.lilac-subscription-options').hide();
                }
            });
            
            // Toggle subscription type options
            $('#lilac_subscription_type').change(function() {
                var selectedType = $(this).val();
                $('.lilac-subscription-type-options').hide();
                $('#lilac-options-' + selectedType).show();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Save subscription meta
     * 
     * @param int $post_id Post ID
     * @param \WP_Post $post Post object
     */
    public function save_subscription_meta($post_id, $post) {
        // Check if nonce is set
        if (!isset($_POST['lilac_subscription_meta_nonce'])) {
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['lilac_subscription_meta_nonce'], 'lilac_subscription_meta_box')) {
            return;
        }
        
        // Check if autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save requires subscription
        $requires_subscription = isset($_POST['lilac_requires_subscription']) ? 'yes' : 'no';
        update_post_meta($post_id, '_lilac_requires_subscription', $requires_subscription);
        
        // Save manual activation setting
        $requires_manual_activation = isset($_POST['lilac_requires_manual_activation']) ? 'yes' : 'no';
        update_post_meta($post_id, '_lilac_requires_manual_activation', $requires_manual_activation);
        
        // Save subscription type
        if (isset($_POST['lilac_subscription_type'])) {
            $subscription_type = sanitize_text_field($_POST['lilac_subscription_type']);
            update_post_meta($post_id, '_lilac_subscription_type', $subscription_type);
        }
        
        // Save subscription options
        $options = isset($_POST['lilac_subscription_options']) ? $_POST['lilac_subscription_options'] : [];
        $sanitized_options = [];
        
        foreach ($options as $type_id => $type_options) {
            $sanitized_options[$type_id] = array_map('sanitize_text_field', $type_options);
        }
        
        update_post_meta($post_id, '_lilac_subscription_options', $sanitized_options);
    }
    
    /**
     * Add subscription column to courses list
     * 
     * @param array $columns Existing columns
     * @return array Modified columns
     */
    public function add_subscription_column($columns) {
        $new_columns = [];
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            // Add subscription column after title
            if ($key === 'title') {
                $new_columns['lilac_subscription'] = __('Subscription', 'lilac-learning-manager');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Render subscription column
     * 
     * @param string $column Column name
     * @param int $post_id Post ID
     */
    public function render_subscription_column($column, $post_id) {
        if ($column !== 'lilac_subscription') {
            return;
        }
        
        $requires_subscription = get_post_meta($post_id, '_lilac_requires_subscription', true);
        
        if ($requires_subscription === 'yes') {
            $subscription_type = get_post_meta($post_id, '_lilac_subscription_type', true);
            $subscription_type = $subscription_type ?: Subscription_Types::TYPE_TIME_LIMITED;
            
            $types = $this->subscription_types->get_subscription_types();
            $type_label = isset($types[$subscription_type]) ? $types[$subscription_type]['label'] : $subscription_type;
            
            echo '<span class="lilac-subscription-enabled">' . esc_html($type_label) . '</span>';
        } else {
            echo '<span class="lilac-subscription-disabled">' . __('No', 'lilac-learning-manager') . '</span>';
        }
    }
    
    /**
     * Get subscription settings for a course
     * 
     * @param int $course_id Course ID
     * @return array Subscription settings
     */
    public function get_course_subscription_settings($course_id) {
        $requires_subscription = get_post_meta($course_id, '_lilac_requires_subscription', true);
        $subscription_type = get_post_meta($course_id, '_lilac_subscription_type', true);
        $subscription_type = $subscription_type ?: Subscription_Types::TYPE_TIME_LIMITED;
        
        $allowed_options = get_post_meta($course_id, '_lilac_subscription_options', true);
        $allowed_options = $allowed_options ? maybe_unserialize($allowed_options) : [];
        
        $requires_manual_activation = get_post_meta($course_id, '_lilac_requires_manual_activation', true);
        
        return [
            'requires_subscription' => $requires_subscription === 'yes',
            'subscription_type' => $subscription_type,
            'allowed_options' => $allowed_options,
            'requires_manual_activation' => $requires_manual_activation === 'yes'
        ];
    }
}
