<?php
namespace LilacLearningManager\Subscriptions;

/**
 * Handles AJAX requests for subscription management
 */
class Subscription_Ajax {
    /**
     * @var Subscription_Manager
     */
    private $subscription_manager;
    
    /**
     * @var Subscription_Types
     */
    private $subscription_types;
    
    /**
     * @var Access_Controller
     */
    private $access_controller;

    /**
     * Constructor
     * 
     * @param Subscription_Manager $subscription_manager
     * @param Subscription_Types $subscription_types
     * @param Access_Controller $access_controller
     */
    public function __construct(
        Subscription_Manager $subscription_manager,
        Subscription_Types $subscription_types,
        Access_Controller $access_controller
    ) {
        $this->subscription_manager = $subscription_manager;
        $this->subscription_types = $subscription_types;
        $this->access_controller = $access_controller;
        $this->setup_hooks();
    }

    /**
     * Set up WordPress hooks
     */
    private function setup_hooks() {
        // Subscription activation
        add_action('wp_ajax_lilac_activate_subscription', [$this, 'handle_activation']);
        add_action('wp_ajax_nopriv_lilac_activate_subscription', [$this, 'handle_no_privileges']);
        
        // Subscription extension
        add_action('wp_ajax_lilac_extend_subscription', [$this, 'handle_extension']);
        add_action('wp_ajax_nopriv_lilac_extend_subscription', [$this, 'handle_no_privileges']);
        
        // Get subscription options
        add_action('wp_ajax_lilac_get_subscription_options', [$this, 'handle_get_options']);
        add_action('wp_ajax_nopriv_lilac_get_subscription_options', [$this, 'handle_no_privileges']);
    }

    /**
     * Handle subscription activation AJAX request
     */
    public function handle_activation() {
        try {
            // Verify nonce
            check_ajax_referer('lilac_activate_subscription', 'nonce');
            
            // Check if user is logged in
            if (!is_user_logged_in()) {
                throw new \Exception(__('You must be logged in to activate a subscription.', 'lilac-learning-manager'));
            }

            // Get and validate parameters
            $user_id = get_current_user_id();
            $course_id = isset($_POST['course_id']) ? absint($_POST['course_id']) : 0;
            $subscription_type = isset($_POST['subscription_type']) ? sanitize_text_field($_POST['subscription_type']) : '';
            $option_id = isset($_POST['option_id']) ? sanitize_text_field($_POST['option_id']) : '';
            
            if (!$course_id) {
                throw new \Exception(__('Invalid course ID.', 'lilac-learning-manager'));
            }
            
            if (empty($subscription_type) || empty($option_id)) {
                throw new \Exception(__('Please select a subscription option.', 'lilac-learning-manager'));
            }
            
            // Get course subscription settings
            $course_meta = new Course_Meta($this->subscription_types);
            $settings = $course_meta->get_course_subscription_settings($course_id);
            
            // Verify subscription type is allowed for this course
            if ($settings['subscription_type'] !== $subscription_type) {
                throw new \Exception(__('Invalid subscription type for this course.', 'lilac-learning-manager'));
            }
            
            // Verify option is allowed for this subscription type
            $allowed_options = isset($settings['allowed_options'][$subscription_type]) 
                              ? $settings['allowed_options'][$subscription_type] 
                              : [];
                              
            if (!empty($allowed_options) && !in_array($option_id, $allowed_options)) {
                throw new \Exception(__('Invalid subscription option for this course.', 'lilac-learning-manager'));
            }

            // Calculate expiration date
            $expiration_date = $this->subscription_types->calculate_expiration(
                $subscription_type,
                $option_id,
                ['start_date' => current_time('mysql')]
            );
            
            if (!$expiration_date) {
                throw new \Exception(__('Failed to calculate expiration date.', 'lilac-learning-manager'));
            }

            // Attempt to activate subscription
            $subscription = $this->subscription_manager->get_user_subscription($user_id, $course_id);
            
            if (!$subscription) {
                throw new \Exception(__('No pending subscription found.', 'lilac-learning-manager'));
            }
            
            // Update subscription
            global $wpdb;
            $table_name = $wpdb->prefix . 'lilac_subscriptions';
            
            $updated = $wpdb->update(
                $table_name,
                [
                    'status' => 'active',
                    'subscription_type' => $subscription_type,
                    'option_id' => $option_id,
                    'started_at' => current_time('mysql'),
                    'expires_at' => $expiration_date,
                    'updated_at' => current_time('mysql')
                ],
                [
                    'user_id' => $user_id,
                    'course_id' => $course_id
                ],
                ['%s', '%s', '%s', '%s', '%s', '%s'],
                ['%d', '%d']
            );
            
            if ($updated === false) {
                throw new \Exception(__('Failed to activate subscription.', 'lilac-learning-manager'));
            }
            
            // Grant course access in LearnDash
            if (function_exists('ld_update_course_access')) {
                ld_update_course_access($user_id, $course_id);
            }
            
            // Trigger action for activated subscription
            do_action(
                'lilac_subscription_activated',
                $user_id,
                $course_id,
                $subscription_type,
                $option_id,
                $expiration_date
            );

            // Success response
            $expiry_date = date_i18n(get_option('date_format'), strtotime($expiration_date));
            
            wp_send_json_success([
                'message' => sprintf(
                    __('Your subscription has been activated and will expire on %s', 'lilac-learning-manager'),
                    $expiry_date
                ),
                'expires_at' => $expiration_date,
                'expiry_date' => $expiry_date
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Handle subscription extension AJAX request
     */
    public function handle_extension() {
        try {
            // Verify nonce
            check_ajax_referer('lilac_subscription_nonce', 'nonce');
            
            // Check if user is logged in
            if (!is_user_logged_in()) {
                throw new \Exception(__('You must be logged in to extend a subscription.', 'lilac-learning-manager'));
            }

            // Get and validate parameters
            $user_id = get_current_user_id();
            $course_id = isset($_POST['course_id']) ? absint($_POST['course_id']) : 0;
            $subscription_type = isset($_POST['subscription_type']) ? sanitize_text_field($_POST['subscription_type']) : '';
            $option_id = isset($_POST['option_id']) ? sanitize_text_field($_POST['option_id']) : '';
            
            if (!$course_id) {
                throw new \Exception(__('Invalid course ID.', 'lilac-learning-manager'));
            }
            
            if (empty($subscription_type) || empty($option_id)) {
                throw new \Exception(__('Please select a subscription option.', 'lilac-learning-manager'));
            }

            // Attempt to extend subscription
            $new_expiration = $this->access_controller->extend_subscription(
                $user_id,
                $course_id,
                $subscription_type,
                $option_id
            );
            
            if (!$new_expiration) {
                throw new \Exception(__('Failed to extend subscription.', 'lilac-learning-manager'));
            }

            // Success response
            $expiry_date = date_i18n(get_option('date_format'), strtotime($new_expiration));
            
            wp_send_json_success([
                'message' => sprintf(
                    __('Your subscription has been extended and will now expire on %s', 'lilac-learning-manager'),
                    $expiry_date
                ),
                'expires_at' => $new_expiration,
                'expiry_date' => $expiry_date
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Handle get subscription options AJAX request
     */
    public function handle_get_options() {
        try {
            // Verify nonce
            check_ajax_referer('lilac_subscription_nonce', 'nonce');
            
            // Get and validate parameters
            $course_id = isset($_POST['course_id']) ? absint($_POST['course_id']) : 0;
            
            if (!$course_id) {
                throw new \Exception(__('Invalid course ID.', 'lilac-learning-manager'));
            }
            
            // Get course subscription settings
            $course_meta = new Course_Meta($this->subscription_types);
            $settings = $course_meta->get_course_subscription_settings($course_id);
            
            if (!$settings['requires_subscription']) {
                throw new \Exception(__('This course does not require a subscription.', 'lilac-learning-manager'));
            }
            
            $subscription_type = $settings['subscription_type'];
            $allowed_options = isset($settings['allowed_options'][$subscription_type]) 
                              ? $settings['allowed_options'][$subscription_type] 
                              : [];
            
            // Get options for this subscription type
            $type_options = $this->subscription_types->get_type_options($subscription_type);
            
            // Filter options based on allowed options
            if (!empty($allowed_options)) {
                $filtered_options = [];
                foreach ($allowed_options as $option_id) {
                    if (isset($type_options[$option_id])) {
                        $filtered_options[$option_id] = $type_options[$option_id];
                    }
                }
                $type_options = $filtered_options;
            }
            
            // Success response
            wp_send_json_success([
                'subscription_type' => $subscription_type,
                'options' => $type_options
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle unauthorized AJAX requests
     */
    public function handle_no_privileges() {
        wp_send_json_error([
            'message' => __('You must be logged in to perform this action.', 'lilac-learning-manager')
        ]);
    }
}
