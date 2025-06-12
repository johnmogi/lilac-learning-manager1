<?php
namespace LilacLearningManager\Subscriptions;

/**
 * Controls access to courses based on subscription status
 */
class Access_Controller {
    /**
     * @var Subscription_Manager
     */
    private $subscription_manager;
    
    /**
     * @var Subscription_Types
     */
    private $subscription_types;
    
    /**
     * Initialize the access controller
     * 
     * @param Subscription_Manager $subscription_manager
     * @param Subscription_Types $subscription_types
     */
    public function __construct($subscription_manager, $subscription_types) {
        $this->subscription_manager = $subscription_manager;
        $this->subscription_types = $subscription_types;
        $this->setup_hooks();
    }
    
    /**
     * Set up WordPress hooks
     */
    private function setup_hooks() {
        // LearnDash hooks for access control
        add_filter('learndash_can_attempt_again', [$this, 'check_access'], 10, 2);
        add_filter('learndash_content_access', [$this, 'check_content_access'], 10, 2);
        
        // Check for expired subscriptions daily
        add_action('lilac_daily_subscription_check', [$this, 'process_expired_subscriptions']);
        
        // Schedule daily check if not already scheduled
        if (!wp_next_scheduled('lilac_daily_subscription_check')) {
            wp_schedule_event(time(), 'daily', 'lilac_daily_subscription_check');
        }
    }
    
    /**
     * Check if user has access to a course
     * 
     * @param bool $can_attempt Current access status
     * @param int $post_id Post ID to check
     * @return bool Whether user can access the content
     */
    public function check_access($can_attempt, $post_id) {
        // Only check for course content
        if (get_post_type($post_id) !== 'sfwd-courses' && 
            get_post_type($post_id) !== 'sfwd-lessons' && 
            get_post_type($post_id) !== 'sfwd-topic') {
            return $can_attempt;
        }
        
        // Get the course ID
        $course_id = get_post_meta($post_id, 'course_id', true);
        if (empty($course_id) && get_post_type($post_id) === 'sfwd-courses') {
            $course_id = $post_id;
        }
        
        // If still no course ID, allow access (not part of a course)
        if (empty($course_id)) {
            return $can_attempt;
        }
        
        $user_id = get_current_user_id();
        
        // Allow access to admins
        if (current_user_can('manage_options')) {
            return true;
        }
        
        // Check if course requires subscription
        $requires_subscription = get_post_meta($course_id, '_lilac_requires_subscription', true);
        if ($requires_subscription !== 'yes') {
            return $can_attempt;
        }
        
        // Check subscription status
        return $this->user_can_access($user_id, $course_id);
    }
    
    /**
     * Check if user has access to course content
     * 
     * @param bool $has_access Current access status
     * @param array $post_data Post data
     * @return bool Whether user can access the content
     */
    public function check_content_access($has_access, $post_data) {
        if (empty($post_data['post']->ID)) {
            return $has_access;
        }
        
        return $this->check_access($has_access, $post_data['post']->ID);
    }
    
    /**
     * Check if a user has access to a course
     * 
     * @param int $user_id User ID
     * @param int $course_id Course ID
     * @return bool Whether user has access
     */
    public function user_can_access($user_id, $course_id) {
        if (!$user_id || !$course_id) {
            return false;
        }
        
        // Get subscription details
        $subscription = $this->subscription_manager->get_user_subscription($user_id, $course_id);
        
        if (!$subscription) {
            return false;
        }
        
        // Check subscription status
        if ($subscription['status'] === 'active') {
            // Check if subscription has expired
            $now = current_time('mysql');
            if (!empty($subscription['expires_at']) && $subscription['expires_at'] < $now) {
                // Subscription has expired, update status
                $this->subscription_manager->expire_subscription($user_id, $course_id);
                return false;
            }
            return true;
        }
        
        return false;
    }
    
    /**
     * Get access status for a user and course
     * 
     * @param int $user_id User ID
     * @param int $course_id Course ID
     * @return array Access status information
     */
    public function get_access_status($user_id, $course_id) {
        if (!$user_id || !$course_id) {
            return [
                'has_access' => false,
                'status' => 'no_subscription',
                'message' => __('No subscription found.', 'lilac-learning-manager')
            ];
        }
        
        // Get subscription details
        $subscription = $this->subscription_manager->get_user_subscription($user_id, $course_id);
        
        if (!$subscription) {
            return [
                'has_access' => false,
                'status' => 'no_subscription',
                'message' => __('No subscription found.', 'lilac-learning-manager')
            ];
        }
        
        // Check subscription status
        switch ($subscription['status']) {
            case 'active':
                $now = current_time('mysql');
                if (!empty($subscription['expires_at']) && $subscription['expires_at'] < $now) {
                    // Subscription has expired, update status
                    $this->subscription_manager->expire_subscription($user_id, $course_id);
                    return [
                        'has_access' => false,
                        'status' => 'expired',
                        'message' => sprintf(
                            __('Your subscription expired on %s.', 'lilac-learning-manager'),
                            date_i18n(get_option('date_format'), strtotime($subscription['expires_at']))
                        ),
                        'expires_at' => $subscription['expires_at']
                    ];
                }
                
                // Calculate days remaining
                $days_remaining = ceil((strtotime($subscription['expires_at']) - strtotime($now)) / DAY_IN_SECONDS);
                
                return [
                    'has_access' => true,
                    'status' => 'active',
                    'message' => sprintf(
                        __('Your subscription is active until %s (%d days remaining).', 'lilac-learning-manager'),
                        date_i18n(get_option('date_format'), strtotime($subscription['expires_at'])),
                        $days_remaining
                    ),
                    'expires_at' => $subscription['expires_at'],
                    'days_remaining' => $days_remaining
                ];
                
            case 'pending':
                return [
                    'has_access' => false,
                    'status' => 'pending',
                    'message' => __('Your subscription is pending activation.', 'lilac-learning-manager')
                ];
                
            case 'expired':
                return [
                    'has_access' => false,
                    'status' => 'expired',
                    'message' => sprintf(
                        __('Your subscription expired on %s.', 'lilac-learning-manager'),
                        date_i18n(get_option('date_format'), strtotime($subscription['expires_at']))
                    ),
                    'expires_at' => $subscription['expires_at']
                ];
                
            default:
                return [
                    'has_access' => false,
                    'status' => 'unknown',
                    'message' => __('Unknown subscription status.', 'lilac-learning-manager')
                ];
        }
    }
    
    /**
     * Process expired subscriptions
     */
    public function process_expired_subscriptions() {
        global $wpdb;
        
        $now = current_time('mysql');
        
        // Get all active subscriptions that have expired
        $table_name = $wpdb->prefix . 'lilac_subscriptions';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return;
        }
        
        $expired_subscriptions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT user_id, course_id FROM $table_name 
                WHERE status = 'active' AND expires_at < %s",
                $now
            ),
            ARRAY_A
        );
        
        if (!$expired_subscriptions) {
            return;
        }
        
        foreach ($expired_subscriptions as $subscription) {
            $this->subscription_manager->expire_subscription(
                $subscription['user_id'],
                $subscription['course_id']
            );
            
            // Trigger action for expired subscription
            do_action(
                'lilac_subscription_expired',
                $subscription['user_id'],
                $subscription['course_id']
            );
        }
    }
    
    /**
     * Extend a subscription
     * 
     * @param int $user_id User ID
     * @param int $course_id Course ID
     * @param string $subscription_type Subscription type ID
     * @param string $option_id Option ID within the subscription type
     * @return bool|string New expiration date or false on failure
     */
    public function extend_subscription($user_id, $course_id, $subscription_type, $option_id) {
        // Get current subscription
        $subscription = $this->subscription_manager->get_user_subscription($user_id, $course_id);
        
        if (!$subscription) {
            return false;
        }
        
        // Calculate new expiration date
        $start_date = current_time('mysql');
        if ($subscription['status'] === 'active' && !empty($subscription['expires_at'])) {
            // If subscription is active, use current expiration as start date
            // This effectively extends the subscription
            $start_date = $subscription['expires_at'];
        }
        
        $new_expiration = $this->subscription_types->calculate_expiration(
            $subscription_type,
            $option_id,
            ['start_date' => $start_date]
        );
        
        if (!$new_expiration) {
            return false;
        }
        
        // Update subscription
        global $wpdb;
        $table_name = $wpdb->prefix . 'lilac_subscriptions';
        
        $updated = $wpdb->update(
            $table_name,
            [
                'status' => 'active',
                'expires_at' => $new_expiration,
                'subscription_type' => $subscription_type,
                'option_id' => $option_id,
                'updated_at' => current_time('mysql')
            ],
            [
                'user_id' => $user_id,
                'course_id' => $course_id
            ],
            ['%s', '%s', '%s', '%s', '%s'],
            ['%d', '%d']
        );
        
        if ($updated) {
            // Grant course access in LearnDash
            if (function_exists('ld_update_course_access')) {
                ld_update_course_access($user_id, $course_id);
            }
            
            // Trigger action for extended subscription
            do_action(
                'lilac_subscription_extended',
                $user_id,
                $course_id,
                $new_expiration,
                $subscription_type,
                $option_id
            );
            
            return $new_expiration;
        }
        
        return false;
    }
}
