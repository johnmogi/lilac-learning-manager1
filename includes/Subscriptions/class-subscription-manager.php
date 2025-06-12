<?php
namespace LilacLearningManager\Subscriptions;

use WP_Error;

/**
 * Handles core subscription functionality for LearnDash courses
 */
class Subscription_Manager {
    /**
     * @var array Subscription duration options
     */
    private $durations = [];

    /**
     * Initialize the subscription manager
     */
    public function __construct() {
        $this->setup_hooks();
        $this->initialize_durations();
    }

    /**
     * Set up WordPress hooks
     */
    private function setup_hooks() {
        // Frontend hooks
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        // WooCommerce integration
        add_action('woocommerce_order_status_completed', [$this, 'handle_order_completed'], 10, 2);
        
        // LearnDash hooks
        add_filter('learndash_can_attempt_again', [$this, 'check_subscription_access'], 10, 2);
        
        // Shortcodes
        add_shortcode('lilac_subscription_button', [$this, 'render_subscription_button']);
    }

    /**
     * Get subscription duration options
     * 
     * @return array
     */
    public function get_durations() {
        return $this->durations;
    }

    /**
     * Initialize subscription duration options
     */
    private function initialize_durations() {
        $this->durations = apply_filters('lilac_subscription_durations', [
            '2_weeks' => [
                'label'    => __('2 Weeks', 'lilac-learning-manager'),
                'duration' => '+2 weeks',
                'days'     => 14
            ],
            '1_month' => [
                'label'    => __('1 Month', 'lilac-learning-manager'),
                'duration' => '+1 month',
                'days'     => 30
            ],
            '1_year' => [
                'label'    => __('1 Year', 'lilac-learning-manager'),
                'duration' => '+1 year',
                'days'     => 365
            ]
        ]);
    }


    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        if (is_singular('sfwd-courses')) {
            wp_enqueue_style(
                'lilac-subscription',
                plugins_url('assets/css/subscription.css', LILAC_LEARNING_MANAGER_PATH . 'lilac-learning-manager.php'),
                [],
                LILAC_LEARNING_MANAGER_VERSION
            );

            wp_enqueue_script(
                'lilac-subscription',
                plugins_url('assets/js/subscription.js', LILAC_LEARNING_MANAGER_PATH . 'lilac-learning-manager.php'),
                ['jquery'],
                LILAC_LEARNING_MANAGER_VERSION,
                true
            );

            wp_localize_script('lilac-subscription', 'lilacSubscription', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('lilac_subscription_nonce'),
                'i18n'    => [
                    'error' => __('An error occurred. Please try again.', 'lilac-learning-manager'),
                    'activating' => __('Activating...', 'lilac-learning-manager'),
                    'select_duration' => __('Please select a duration', 'lilac-learning-manager'),
                    'expires_on' => __('Expires on', 'lilac-learning-manager'),
                ]
            ]);
        }
    }

    /**
     * Handle WooCommerce order completion
     */
    public function handle_order_completed($order_id, $order) {
        // Skip if not a valid order
        if (!is_a($order, 'WC_Order')) {
            $order = wc_get_order($order_id);
        }

        $user_id = $order->get_user_id();
        
        // Skip if no user ID (guest checkout)
        if (!$user_id) {
            return;
        }
        
        // Process each item in the order
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $course_id = $this->get_course_for_product($product_id);
            
            if ($course_id) {
                $this->create_pending_subscription($user_id, $course_id, $order_id);
            }
        }
    }

    /**
     * Create a pending subscription
     */
    private function create_pending_subscription($user_id, $course_id, $order_id) {
        $subscription_data = [
            'status'      => 'pending',
            'course_id'   => $course_id,
            'order_id'    => $order_id,
            'created_at'  => current_time('mysql'),
            'modified_at' => current_time('mysql')
        ];
        
        update_user_meta($user_id, "_lilac_course_subscription_{$course_id}", $subscription_data);
        
        do_action('lilac_subscription_created', $user_id, $course_id, $subscription_data);
        
        return $subscription_data;
    }

    /**
     * Activate a user's subscription
     */
    public function activate_subscription($user_id, $course_id, $duration) {
        // Verify the duration is valid
        if (!isset($this->durations[$duration])) {
            return new WP_Error('invalid_duration', __('Invalid subscription duration.', 'lilac-learning-manager'));
        }
        
        // Get current subscription or create new one
        $subscription = $this->get_user_subscription($user_id, $course_id);
        
        // If no subscription exists, create a new one
        if (empty($subscription)) {
            return new WP_Error('no_subscription', __('No subscription found for this course.', 'lilac-learning-manager'));
        }
        
        // Check if already active
        if ($subscription['status'] === 'active') {
            return new WP_Error('already_active', __('Your subscription is already active.', 'lilac-learning-manager'));
        }
        
        // Calculate start and end times
        $start_time = current_time('timestamp');
        $end_time = strtotime($this->durations[$duration]['duration'], $start_time);
        
        // Update subscription data
        $subscription['status'] = 'active';
        $subscription['start_time'] = $start_time;
        $subscription['end_time'] = $end_time;
        $subscription['duration'] = $duration;
        $subscription['activated_at'] = current_time('mysql');
        $subscription['modified_at'] = current_time('mysql');
        
        // Save updated subscription
        update_user_meta($user_id, "_lilac_course_subscription_{$course_id}", $subscription);
        
        // Grant course access
        ld_update_course_access($user_id, $course_id);
        
        // Trigger action for other plugins
        do_action('lilac_subscription_activated', $user_id, $course_id, $subscription);
        
        return $subscription;
    }

    /**
     * Get subscription details for a user and course
     */
    public function get_user_subscription($user_id, $course_id) {
        return get_user_meta($user_id, "_lilac_course_subscription_{$course_id}", true);
    }

    /**
     * Check if user has active subscription
     */
    public function has_active_subscription($user_id, $course_id) {
        $subscription = $this->get_user_subscription($user_id, $course_id);
        
        // No subscription found
        if (empty($subscription)) {
            return false;
        }
        
        // Check if subscription is active
        if ($subscription['status'] !== 'active') {
            return false;
        }
        
        // Check if subscription has expired
        if (isset($subscription['end_time']) && current_time('timestamp') > $subscription['end_time']) {
            $this->expire_subscription($user_id, $course_id);
            return false;
        }
        
        // Check renewable end date if set
        $renewable_end_date = get_post_meta($course_id, '_lilac_renewable_end_date', true);
        if (!empty($renewable_end_date)) {
            $renewable_timestamp = strtotime($renewable_end_date . ' 23:59:59');
            if (current_time('timestamp') > $renewable_timestamp) {
                $this->expire_subscription($user_id, $course_id);
                return false;
            }
        }
        
        return true;
    }

    /**
     * Expire a subscription
     */
    public function expire_subscription($user_id, $course_id) {
        $subscription = $this->get_user_subscription($user_id, $course_id);
        
        if (empty($subscription)) {
            return false;
        }
        
        // Skip if already expired
        if ($subscription['status'] === 'expired') {
            return true;
        }
        
        // Update subscription status
        $subscription['status'] = 'expired';
        $subscription['expired_at'] = current_time('mysql');
        $subscription['modified_at'] = current_time('mysql');
        
        // Save updated subscription
        update_user_meta($user_id, "_lilac_course_subscription_{$course_id}", $subscription);
        
        // Revoke course access
        ld_cancel_course_access($user_id, $course_id);
        
        // Trigger action for other plugins
        do_action('lilac_subscription_expired', $user_id, $course_id, $subscription);
        
        return true;
    }

    /**
     * Get course ID for a WooCommerce product
     */
    private function get_course_for_product($product_id) {
        // Check if LearnDash WooCommerce integration is active
        if (function_exists('learndash_get_course_id')) {
            // Try to get course ID from product meta (LearnDash WooCommerce integration)
            $course_id = get_post_meta($product_id, '_related_course', true);
            if ($course_id) {
                return $course_id;
            }
            
            // Try to get course ID from product title/slug (fallback)
            $product = wc_get_product($product_id);
            if ($product) {
                $slug = $product->get_slug();
                $args = [
                    'post_type' => 'sfwd-courses',
                    'name' => $slug,
                    'post_status' => 'publish',
                    'numberposts' => 1,
                    'fields' => 'ids'
                ];
                $courses = get_posts($args);
                if (!empty($courses)) {
                    return $courses[0];
                }
            }
        }
        
        // Check for custom meta field (can be set in product edit screen)
        $course_id = get_post_meta($product_id, '_lilac_related_course', true);
        if ($course_id) {
            return $course_id;
        }
        
        return false;
    }

    /**
     * Render subscription button shortcode
     */
    public function render_subscription_button($atts) {
        // Only show to logged-in users
        if (!is_user_logged_in()) {
            return $this->render_login_required_message();
        }
        
        $atts = shortcode_atts([
            'course_id' => get_the_ID(),
            'label'     => __('Activate Subscription', 'lilac-learning-manager'),
            'class'     => 'button lilac-subscription-button',
        ], $atts);

        $course_id = absint($atts['course_id']);
        $user_id = get_current_user_id();
        
        // Check if course exists
        if (!get_post($course_id)) {
            return '<div class="lilac-message lilac-message-error">' . 
                   __('Invalid course ID.', 'lilac-learning-manager') . 
                   '</div>';
        }
        
        // Get user's subscription status
        $subscription = $this->get_user_subscription($user_id, $course_id);
        
        // No subscription found
        if (empty($subscription)) {
            return $this->render_no_subscription_message();
        }
        
        // Subscription is active
        if ($subscription['status'] === 'active') {
            return $this->render_active_subscription($subscription);
        }
        
        // Subscription is pending activation
        if ($subscription['status'] === 'pending') {
            return $this->render_pending_subscription($course_id, $atts);
        }
        
        // Subscription is expired
        if ($subscription['status'] === 'expired') {
            return $this->render_expired_subscription($subscription);
        }
        
        // Fallback for unknown status
        return $this->render_unknown_status_message();
    }
    
    /**
     * Render login required message
     */
    private function render_login_required_message() {
        $login_url = wp_login_url(get_permalink());
        $register_url = wp_registration_url();
        
        return sprintf(
            '<div class="lilac-message lilac-message-info">' .
            '<p>%s <a href="%s">%s</a> %s <a href="%s">%s</a>.</p>' .
            '</div>',
            __('Please', 'lilac-learning-manager'),
            esc_url($login_url),
            __('log in', 'lilac-learning-manager'),
            __('or', 'lilac-learning-manager'),
            esc_url($register_url),
            __('create an account', 'lilac-learning-manager')
        );
    }
    
    /**
     * Render no subscription message
     */
    private function render_no_subscription_message() {
        return '<div class="lilac-message lilac-message-info">' . 
               __('No active subscription found for this course.', 'lilac-learning-manager') . 
               '</div>';
    }
    
    /**
     * Render active subscription message
     */
    private function render_active_subscription($subscription) {
        $end_date = date_i18n(get_option('date_format'), $subscription['end_time']);
        $days_remaining = ceil(($subscription['end_time'] - current_time('timestamp')) / DAY_IN_SECONDS);
        
        $message = sprintf(
            __('Your subscription is active and will expire on %s', 'lilac-learning-manager'),
            '<strong>' . $end_date . '</strong>'
        );
        
        if ($days_remaining <= 30) {
            $message .= ' ' . sprintf(
                _n('(%s day remaining)', '(%s days remaining)', $days_remaining, 'lilac-learning-manager'),
                $days_remaining
            );
        }
        
        return '<div class="lilac-message lilac-message-success">' . $message . '</div>';
    }
    
    /**
     * Render pending subscription UI
     */
    private function render_pending_subscription($course_id, $atts) {
        ob_start();
        ?>
        <div class="lilac-subscription-activation">
            <button type="button" 
                    class="<?php echo esc_attr($atts['class']); ?>" 
                    data-course-id="<?php echo esc_attr($course_id); ?>"
                    data-nonce="<?php echo esc_attr(wp_create_nonce('lilac_activate_subscription')); ?>">
                <?php echo esc_html($atts['label']); ?>
            </button>
            
            <div class="lilac-duration-options" style="display: none; margin-top: 15px; padding: 15px; background: #f9f9f9; border-radius: 4px;">
                <h4 style="margin-top: 0;"><?php _e('Select Subscription Duration', 'lilac-learning-manager'); ?></h4>
                <select class="lilac-duration-select" style="width: 100%; padding: 8px; margin-bottom: 10px;">
                    <?php foreach ($this->durations as $key => $duration) : ?>
                        <option value="<?php echo esc_attr($key); ?>">
                            <?php echo esc_html($duration['label']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="button button-primary lilac-confirm-activation" style="margin-right: 10px;">
                    <?php _e('Confirm Activation', 'lilac-learning-manager'); ?>
                </button>
                <button type="button" class="button lilac-cancel-activation">
                    <?php _e('Cancel', 'lilac-learning-manager'); ?>
                </button>
            </div>
            
            <div class="lilac-activation-message" style="margin-top: 10px;"></div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render expired subscription message
     */
    private function render_expired_subscription($subscription) {
        $end_date = date_i18n(get_option('date_format'), strtotime($subscription['expired_at']));
        
        $message = sprintf(
            __('Your subscription expired on %s.', 'lilac-learning-manager'),
            '<strong>' . $end_date . '</strong>'
        );
        
        $message .= ' ' . __('Please purchase a new subscription to regain access.', 'lilac-learning-manager');
        
        return '<div class="lilac-message lilac-message-error">' . $message . '</div>';
    }
    
    /**
     * Render unknown status message
     */
    private function render_unknown_status_message() {
        return '<div class="lilac-message lilac-message-error">' . 
               __('Unknown subscription status. Please contact support.', 'lilac-learning-manager') . 
               '</div>';
    }

    /**
     * Check if user has subscription access to a course
     */
    public function check_subscription_access($can_attempt, $post_id) {
        // Only check for course content
        if (get_post_type($post_id) !== 'sfwd-courses' && get_post_type($post_id) !== 'sfwd-lessons' && get_post_type($post_id) !== 'sfwd-topic') {
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
        
        // Check subscription
        return $this->has_active_subscription($user_id, $course_id);
    }
}
