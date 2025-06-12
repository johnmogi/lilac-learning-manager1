<?php
namespace LilacLearningManager\Subscriptions;

/**
 * Handles admin interface for subscription management
 */
class Subscription_Admin {
    /**
     * @var Subscription_Manager
     */
    private $subscription_manager;

    /**
     * Constructor
     * 
     * @param Subscription_Manager $subscription_manager
     */
    public function __construct(Subscription_Manager $subscription_manager) {
        $this->subscription_manager = $subscription_manager;
        $this->setup_hooks();
    }

    /**
     * Set up WordPress hooks
     */
    private function setup_hooks() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('add_meta_boxes', [$this, 'add_course_meta_box']);
        add_action('save_post_sfwd-courses', [$this, 'save_course_meta'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Add subscription settings page to admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'lilac-learning-manager',
            __('Subscription Settings', 'lilac-learning-manager'),
            __('Subscriptions', 'lilac-learning-manager'),
            'manage_options',
            'lilac-subscriptions',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting(
            'lilac_subscription_settings',
            'lilac_subscription_options',
            ['sanitize_callback' => [$this, 'validate_options']]
        );

        add_settings_section(
            'lilac_subscription_general',
            __('General Settings', 'lilac-learning-manager'),
            [$this, 'render_section_general'],
            'lilac-subscriptions'
        );

        add_settings_field(
            'enable_manual_activation',
            __('Manual Activation', 'lilac-learning-manager'),
            [$this, 'render_enable_manual_activation'],
            'lilac-subscriptions',
            'lilac_subscription_general'
        );

        add_settings_field(
            'default_duration',
            __('Default Duration', 'lilac-learning-manager'),
            [$this, 'render_default_duration'],
            'lilac-subscriptions',
            'lilac_subscription_general'
        );
    }

    /**
     * Validate plugin options
     */
    public function validate_options($input) {
        $output = get_option('lilac_subscription_options', []);
        
        if (isset($input['enable_manual_activation'])) {
            $output['enable_manual_activation'] = (bool) $input['enable_manual_activation'];
        } else {
            $output['enable_manual_activation'] = false;
        }

        if (isset($input['default_duration']) && array_key_exists($input['default_duration'], $this->subscription_manager->get_durations())) {
            $output['default_duration'] = sanitize_text_field($input['default_duration']);
        }

        return $output;
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Show success/error messages
        if (isset($_GET['settings-updated'])) {
            add_settings_error(
                'lilac_messages',
                'lilac_message',
                __('Settings Saved', 'lilac-learning-manager'),
                'updated'
            );
        }
        
        // Show error/update messages
        settings_errors('lilac_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('lilac_subscription_settings');
                do_settings_sections('lilac-subscriptions');
                submit_button(__('Save Settings', 'lilac-learning-manager'));
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render general section description
     */
    public function render_section_general() {
        echo '<p>' . esc_html__('Configure general subscription settings.', 'lilac-learning-manager') . '</p>';
    }

    /**
     * Render manual activation setting field
     */
    public function render_enable_manual_activation() {
        $options = get_option('lilac_subscription_options', []);
        $value = isset($options['enable_manual_activation']) ? $options['enable_manual_activation'] : false;
        ?>
        <label>
            <input type="checkbox" 
                   name="lilac_subscription_options[enable_manual_activation]" 
                   value="1" 
                   <?php checked($value, true); ?>>
            <?php esc_html_e('Allow users to manually activate their subscription', 'lilac-learning-manager'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('If enabled, users will need to manually activate their subscription after purchase.', 'lilac-learning-manager'); ?>
        </p>
        <?php
    }

    /**
     * Render default duration setting field
     */
    public function render_default_duration() {
        $options = get_option('lilac_subscription_options', []);
        $current = $options['default_duration'] ?? '1_year';
        $durations = $this->subscription_manager->get_durations();
        ?>
        <select name="lilac_subscription_options[default_duration]">
            <?php foreach ($durations as $key => $duration) : ?>
                <option value="<?php echo esc_attr($key); ?>" <?php selected($current, $key); ?>>
                    <?php echo esc_html($duration['label']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php esc_html_e('Default subscription duration for new courses.', 'lilac-learning-manager'); ?>
        </p>
        <?php
    }

    /**
     * Add meta box to course edit screen
     */
    public function add_course_meta_box() {
        add_meta_box(
            'lilac_subscription_settings',
            __('Subscription Settings', 'lilac-learning-manager'),
            [$this, 'render_course_meta_box'],
            'sfwd-courses',
            'side',
            'default'
        );
    }

    /**
     * Render course meta box
     */
    public function render_course_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('lilac_save_course_meta', 'lilac_course_meta_nonce');
        
        // Get saved meta values
        $subscription_enabled = get_post_meta($post->ID, '_lilac_subscription_enabled', true);
        $renewable_end_date = get_post_meta($post->ID, '_lilac_renewable_end_date', true);
        $custom_durations = get_post_meta($post->ID, '_lilac_custom_durations', true);
        
        // Default values
        if (empty($subscription_enabled)) {
            $subscription_enabled = 'default';
        }
        
        // Get available durations
        $durations = $this->subscription_manager->get_durations();
        ?>
        <div class="lilac-course-settings">
            <p>
                <strong><?php esc_html_e('Subscription Access', 'lilac-learning-manager'); ?></strong>
            </p>
            
            <p>
                <label>
                    <input type="radio" name="lilac_subscription_enabled" value="default" 
                           <?php checked($subscription_enabled, 'default'); ?>>
                    <?php esc_html_e('Use global settings', 'lilac-learning-manager'); ?>
                </label><br>
                
                <label>
                    <input type="radio" name="lilac_subscription_enabled" value="enabled" 
                           <?php checked($subscription_enabled, 'enabled'); ?>>
                    <?php esc_html_e('Enable for this course', 'lilac-learning-manager'); ?>
                </label><br>
                
                <label>
                    <input type="radio" name="lilac_subscription_enabled" value="disabled" 
                           <?php checked($subscription_enabled, 'disabled'); ?>>
                    <?php esc_html_e('Disable for this course', 'lilac-learning-manager'); ?>
                </label>
            </p>

            <div class="lilac-renewable-date" style="margin-top: 15px;">
                <p>
                    <strong><?php esc_html_e('Renewable End Date', 'lilac-learning-manager'); ?></strong>
                    <span class="dashicons dashicons-editor-help" 
                          title="<?php esc_attr_e('Set a specific date when subscriptions for this course will expire, regardless of activation date.', 'lilac-learning-manager'); ?>">
                    </span>
                </p>
                <input type="date" 
                       name="lilac_renewable_end_date" 
                       value="<?php echo esc_attr($renewable_end_date); ?>"
                       class="regular-text">
                <p class="description">
                    <?php esc_html_e('Leave empty to use duration-based expiration.', 'lilac-learning-manager'); ?>
                </p>
            </div>

            <div class="lilac-custom-durations" style="margin-top: 15px;">
                <p>
                    <strong><?php esc_html_e('Available Durations', 'lilac-learning-manager'); ?></strong>
                </p>
                <?php foreach ($durations as $key => $duration) : 
                    $checked = empty($custom_durations) || in_array($key, (array) $custom_durations);
                ?>
                    <label style="display: block; margin: 5px 0;">
                        <input type="checkbox" 
                               name="lilac_custom_durations[]" 
                               value="<?php echo esc_attr($key); ?>"
                               <?php checked($checked); ?>>
                        <?php echo esc_html($duration['label']); ?>
                    </label>
                <?php endforeach; ?>
                <p class="description">
                    <?php esc_html_e('Select which subscription durations are available for this course.', 'lilac-learning-manager'); ?>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Save course meta box data
     */
    public function save_course_meta($post_id, $post) {
        // Check if nonce is set and valid
        if (!isset($_POST['lilac_course_meta_nonce']) || 
            !wp_verify_nonce($_POST['lilac_course_meta_nonce'], 'lilac_save_course_meta')) {
            return;
        }

        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save subscription enabled setting
        if (isset($_POST['lilac_subscription_enabled'])) {
            $subscription_enabled = sanitize_text_field($_POST['lilac_subscription_enabled']);
            update_post_meta($post_id, '_lilac_subscription_enabled', $subscription_enabled);
        }

        // Save renewable end date
        $renewable_end_date = isset($_POST['lilac_renewable_end_date']) ? 
                             sanitize_text_field($_POST['lilac_renewable_end_date']) : '';
        update_post_meta($post_id, '_lilac_renewable_end_date', $renewable_end_date);

        // Save custom durations
        $custom_durations = isset($_POST['lilac_custom_durations']) ? 
                           array_map('sanitize_text_field', $_POST['lilac_custom_durations']) : [];
        update_post_meta($post_id, '_lilac_custom_durations', $custom_durations);
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        global $post_type;
        
        // Only load on course edit screen and plugin settings page
        if (($hook === 'post.php' || $hook === 'post-new.php') && $post_type === 'sfwd-courses') {
            wp_enqueue_style(
                'lilac-admin-css',
                plugins_url('assets/css/admin.css', LILAC_LEARNING_MANAGER_PATH . 'lilac-learning-manager.php'),
                [],
                LILAC_LEARNING_MANAGER_VERSION
            );
            
            wp_enqueue_script(
                'lilac-admin-js',
                plugins_url('assets/js/admin.js', LILAC_LEARNING_MANAGER_PATH . 'lilac-learning-manager.php'),
                ['jquery'],
                LILAC_LEARNING_MANAGER_VERSION,
                true
            );
        }
    }
}
