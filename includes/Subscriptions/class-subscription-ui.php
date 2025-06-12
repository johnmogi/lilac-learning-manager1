<?php
namespace LilacLearningManager\Subscriptions;

/**
 * Handles frontend UI for subscription management
 */
class Subscription_UI {
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
     * Initialize subscription UI
     * 
     * @param Subscription_Manager $subscription_manager
     * @param Subscription_Types $subscription_types
     * @param Access_Controller $access_controller
     */
    public function __construct($subscription_manager, $subscription_types, $access_controller) {
        $this->subscription_manager = $subscription_manager;
        $this->subscription_types = $subscription_types;
        $this->access_controller = $access_controller;
        $this->setup_hooks();
    }
    
    /**
     * Set up WordPress hooks
     */
    private function setup_hooks() {
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        // Add shortcode for subscription button
        add_shortcode('lilac_subscription_button', [$this, 'render_subscription_button']);
        
        // Add shortcode for subscription status
        add_shortcode('lilac_subscription_status', [$this, 'render_subscription_status']);
        
        // Add subscription section to WooCommerce order received page
        add_action('woocommerce_order_details_after_order_table', [$this, 'add_subscription_section']);
        
        // Add subscription section to WooCommerce account orders page
        add_action('woocommerce_order_details_after_order_table', [$this, 'add_subscription_section']);
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        // Only load on relevant pages
        if (!is_singular('sfwd-courses') && 
            !is_wc_endpoint_url('order-received') && 
            !is_wc_endpoint_url('view-order')) {
            return;
        }
        
        // Enqueue our new subscription UI CSS
        wp_enqueue_style(
            'lilac-subscription-ui',
            plugins_url('assets/css/subscription-ui.css', dirname(dirname(__FILE__)) . '/lilac-learning-manager.php'),
            [],
            LILAC_LEARNING_MANAGER_VERSION
        );

        // Enqueue our new subscription UI JavaScript
        wp_enqueue_script(
            'lilac-subscription-ui',
            plugins_url('assets/js/subscription-ui.js', dirname(dirname(__FILE__)) . '/lilac-learning-manager.php'),
            ['jquery'],
            LILAC_LEARNING_MANAGER_VERSION,
            true
        );

        // Localize script with data and translations
        wp_localize_script('lilac-subscription-ui', 'lilac_subscription_vars', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'reload_after_activation' => apply_filters('lilac_reload_after_activation', '0')
        ]);
        
        wp_localize_script('lilac-subscription-ui', 'lilac_subscription_i18n', [
            'ajax_error' => __('An error occurred. Please try again.', 'lilac-learning-manager'),
            'processing' => __('Processing...', 'lilac-learning-manager'),
            'select_option' => __('Select an option', 'lilac-learning-manager'),
            'active_until' => __('Active until', 'lilac-learning-manager'),
            'subscription_active' => __('Subscription Active', 'lilac-learning-manager'),
            'subscription_expired' => __('Subscription Expired', 'lilac-learning-manager'),
            'subscription_pending' => __('Subscription Pending', 'lilac-learning-manager'),
            'activate' => __('Activate', 'lilac-learning-manager'),
            'extend' => __('Extend', 'lilac-learning-manager'),
            'cancel' => __('Cancel', 'lilac-learning-manager')
        ]);
    }
    
    /**
     * Add subscription section to WooCommerce order page
     * 
     * @param \WC_Order $order Order object
     */
    public function add_subscription_section($order) {
        // Skip if not on order received or view order page
        if (!is_wc_endpoint_url('order-received') && !is_wc_endpoint_url('view-order')) {
            return;
        }
        
        $order_id = $order->get_id();
        $user_id = $order->get_user_id();
        
        // Skip if no user ID (guest checkout)
        if (!$user_id) {
            echo $this->render_login_required_message();
            return;
        }
        
        // Get courses from order
        $courses = $this->get_courses_from_order($order);
        
        if (empty($courses)) {
            return;
        }
        
        echo '<h2>' . __('Course Access', 'lilac-learning-manager') . '</h2>';
        echo '<div class="lilac-subscription-section">';
        
        foreach ($courses as $course_id) {
            $course = get_post($course_id);
            
            if (!$course) {
                continue;
            }
            
            echo '<div class="lilac-course-subscription" data-course-id="' . esc_attr($course_id) . '">';
            echo '<h3>' . esc_html($course->post_title) . '</h3>';
            
            // Get course subscription settings
            $course_meta = new Course_Meta($this->subscription_types);
            $settings = $course_meta->get_course_subscription_settings($course_id);
            
            if (!$settings['requires_subscription']) {
                echo '<div class="lilac-message lilac-message-success">';
                echo __('This course does not require a subscription. You have permanent access.', 'lilac-learning-manager');
                echo '</div>';
                continue;
            }
            
            // Get subscription status
            $subscription = $this->subscription_manager->get_user_subscription($user_id, $course_id);
            
            if (!$subscription) {
                // Create pending subscription if not exists
                $this->subscription_manager->create_pending_subscription($user_id, $course_id, $order_id);
                $subscription = $this->subscription_manager->get_user_subscription($user_id, $course_id);
            }
            
            // Render appropriate UI based on subscription status
            switch ($subscription['status']) {
                case 'active':
                    echo $this->render_active_subscription($subscription);
                    break;
                    
                case 'pending':
                    echo $this->render_pending_subscription($course_id, $settings);
                    break;
                    
                case 'expired':
                    echo $this->render_expired_subscription($subscription);
                    break;
                    
                default:
                    echo $this->render_unknown_status_message();
            }
            
            echo '</div>'; // .lilac-course-subscription
        }
        
        echo '</div>'; // .lilac-subscription-section
    }
    
    /**
     * Get courses from WooCommerce order
     * 
     * @param \WC_Order $order Order object
     * @return array Course IDs
     */
    private function get_courses_from_order($order) {
        $courses = [];
        
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $course_id = $this->subscription_manager->get_course_for_product($product_id);
            
            if ($course_id) {
                $courses[] = $course_id;
            }
        }
        
        return array_unique($courses);
    }
    
    /**
     * Render subscription button shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string Rendered HTML
     */
    public function render_subscription_button($atts) {
        $atts = shortcode_atts([
            'course_id' => 0,
            'order_id' => 0,
            'label' => __('Activate Subscription', 'lilac-learning-manager'),
            'class' => 'button button-primary lilac-activate-subscription'
        ], $atts, 'lilac_subscription_button');
        
        // Get course ID
        $course_id = intval($atts['course_id']);
        
        // If no course ID provided, try to get from current post
        if (!$course_id && is_singular('sfwd-courses')) {
            $course_id = get_the_ID();
        }
        
        if (!$course_id) {
            return '<div class="lilac-message lilac-message-error">' . 
                   __('No course specified.', 'lilac-learning-manager') . 
                   '</div>';
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return $this->render_login_required_message();
        }
        
        $user_id = get_current_user_id();
        
        // Get subscription status
        $subscription = $this->subscription_manager->get_user_subscription($user_id, $course_id);
        
        // If order ID provided, check if course is in order
        if ($atts['order_id']) {
            $order = wc_get_order($atts['order_id']);
            
            if ($order && $order->get_user_id() === $user_id) {
                $courses = $this->get_courses_from_order($order);
                
                if (in_array($course_id, $courses) && !$subscription) {
                    // Create pending subscription if not exists
                    $this->subscription_manager->create_pending_subscription($user_id, $course_id, $atts['order_id']);
                    $subscription = $this->subscription_manager->get_user_subscription($user_id, $course_id);
                }
            }
        }
        
        if (!$subscription) {
            return $this->render_no_subscription_message();
        }
        
        // Render appropriate UI based on subscription status
        switch ($subscription['status']) {
            case 'active':
                return $this->render_active_subscription($subscription);
                
            case 'pending':
                // Get course subscription settings
                $course_meta = new Course_Meta($this->subscription_types);
                $settings = $course_meta->get_course_subscription_settings($course_id);
                
                return $this->render_pending_subscription($course_id, $settings);
                
            case 'expired':
                return $this->render_expired_subscription($subscription);
                
            default:
                return $this->render_unknown_status_message();
        }
    }
    
    /**
     * Render subscription status shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string Rendered HTML
     */
    public function render_subscription_status($atts) {
        $atts = shortcode_atts([
            'course_id' => 0,
            'show_expiry' => 'yes'
        ], $atts, 'lilac_subscription_status');
        
        // Get course ID
        $course_id = intval($atts['course_id']);
        
        // If no course ID provided, try to get from current post
        if (!$course_id && is_singular('sfwd-courses')) {
            $course_id = get_the_ID();
        }
        
        if (!$course_id) {
            return '';
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '';
        }
        
        $user_id = get_current_user_id();
        
        // Get access status
        $status = $this->access_controller->get_access_status($user_id, $course_id);
        
        if ($status['has_access']) {
            $output = '<span class="lilac-subscription-status lilac-status-active">' . 
                      __('Active', 'lilac-learning-manager') . 
                      '</span>';
                      
            if ($atts['show_expiry'] === 'yes' && !empty($status['expires_at'])) {
                $expiry_date = date_i18n(get_option('date_format'), strtotime($status['expires_at']));
                $output .= ' <span class="lilac-subscription-expiry">(' . 
                           sprintf(__('Expires: %s', 'lilac-learning-manager'), $expiry_date) . 
                           ')</span>';
            }
            
            return $output;
        } else {
            return '<span class="lilac-subscription-status lilac-status-inactive">' . 
                   __('Inactive', 'lilac-learning-manager') . 
                   '</span>';
        }
    }
    
    /**
     * Render login required message
     * 
     * @return string Rendered HTML
     */
    private function render_login_required_message() {
        $login_url = wp_login_url(get_permalink());
        
        $message = sprintf(
            __('Please <a href="%s">log in</a> to manage your subscription.', 'lilac-learning-manager'),
            esc_url($login_url)
        );
        
        return '<div class="lilac-message lilac-message-info">' . $message . '</div>';
    }
    
    /**
     * Render no subscription message
     * 
     * @return string Rendered HTML
     */
    private function render_no_subscription_message() {
        return '<div class="lilac-message lilac-message-error">' . 
               __('You do not have a subscription for this course.', 'lilac-learning-manager') . 
               '</div>';
    }
    
    /**
     * Render active subscription message
     * 
     * @param array $subscription Subscription data
     * @return string Rendered HTML
     */
    private function render_active_subscription($subscription) {
        $course_id = $subscription['course_id'];
        $now = current_time('mysql');
        $expires_at = $subscription['expires_at'];
        $show_extension = false;
        $days_remaining = 0;
        
        // Get course subscription settings
        $course_meta = new Course_Meta($this->subscription_types);
        $settings = $course_meta->get_course_subscription_settings($course_id);
        
        ob_start();
        ?>
        <div class="lilac-subscription-container">
            <div class="lilac-subscription-status-display">
                <div class="lilac-subscription-active">
                    <?php 
                    if (empty($expires_at) || $expires_at === '0000-00-00 00:00:00') {
                        echo __('Your subscription is active (no expiration date).', 'lilac-learning-manager');
                    } else {
                        $expiry_date = date_i18n(get_option('date_format'), strtotime($expires_at));
                        $days_remaining = ceil((strtotime($expires_at) - strtotime($now)) / DAY_IN_SECONDS);
                        $show_extension = ($days_remaining <= 30); // Show extension form if 30 or fewer days left
                        
                        printf(
                            __('Your subscription is active until %s (%d days remaining).', 'lilac-learning-manager'),
                            '<strong>' . $expiry_date . '</strong>',
                            $days_remaining
                        );
                        
                        if ($days_remaining <= 7) {
                            echo ' <span class="lilac-days-left">' . 
                                 __('Expiring soon!', 'lilac-learning-manager') . 
                                 '</span>';
                        }
                    }
                    ?>
                </div>
            </div>
            
            <?php if ($show_extension && isset($settings['allows_extension']) && $settings['allows_extension']) : ?>
            <form class="lilac-subscription-extension-form" data-course-id="<?php echo esc_attr($course_id); ?>">
                <?php wp_nonce_field('lilac_subscription_nonce', 'lilac_subscription_nonce'); ?>
                
                <div class="lilac-form-row">
                    <h4><?php _e('Extend Your Subscription', 'lilac-learning-manager'); ?></h4>
                </div>
                
                <div class="lilac-form-row lilac-form-row-inline">
                    <label for="subscription-type-ext-<?php echo esc_attr($course_id); ?>">
                        <?php _e('Subscription Type', 'lilac-learning-manager'); ?>
                    </label>
                    
                    <select id="subscription-type-ext-<?php echo esc_attr($course_id); ?>" 
                            class="lilac-subscription-type-select" 
                            name="subscription_type" 
                            disabled>
                        <option value="<?php echo esc_attr($subscription['subscription_type']); ?>" selected>
                            <?php echo esc_html($this->subscription_types->get_type_label($subscription['subscription_type'])); ?>
                        </option>
                    </select>
                </div>
                
                <div class="lilac-form-row lilac-form-row-inline">
                    <label for="subscription-option-ext-<?php echo esc_attr($course_id); ?>">
                        <?php _e('Extension Option', 'lilac-learning-manager'); ?>
                    </label>
                    
                    <select id="subscription-option-ext-<?php echo esc_attr($course_id); ?>" 
                            class="lilac-subscription-option-select" 
                            name="option_id">
                        <option value=""><?php _e('Select an option', 'lilac-learning-manager'); ?></option>
                    </select>
                </div>
                
                <div class="lilac-form-row">
                    <button type="submit" class="lilac-subscription-button" disabled>
                        <?php _e('Extend Subscription', 'lilac-learning-manager'); ?>
                    </button>
                </div>
                
                <div class="lilac-subscription-status"></div>
            </form>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render pending subscription UI
     * 
     * @param int $course_id Course ID
     * @param array $settings Course subscription settings
     * @return string Rendered HTML
     */
    private function render_pending_subscription($course_id, $settings) {
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
        
        // If no options available, show error
        if (empty($type_options)) {
            return '<div class="lilac-message lilac-message-error">' . 
                   __('No subscription options available for this course.', 'lilac-learning-manager') . 
                   '</div>';
        }
        
        ob_start();
        ?>
        <div class="lilac-subscription-container">
            <div class="lilac-subscription-status-display">
                <div class="lilac-subscription-pending">
                    <?php _e('Your subscription is pending activation.', 'lilac-learning-manager'); ?>
                </div>
            </div>
            
            <form class="lilac-subscription-form" data-course-id="<?php echo esc_attr($course_id); ?>">
                <?php wp_nonce_field('lilac_subscription_nonce', 'lilac_subscription_nonce'); ?>
                
                <div class="lilac-form-row lilac-form-row-inline">
                    <label for="subscription-type-<?php echo esc_attr($course_id); ?>">
                        <?php _e('Subscription Type', 'lilac-learning-manager'); ?>
                    </label>
                    
                    <select id="subscription-type-<?php echo esc_attr($course_id); ?>" 
                            class="lilac-subscription-type-select" 
                            name="subscription_type" 
                            disabled>
                        <option value="<?php echo esc_attr($subscription_type); ?>" selected>
                            <?php echo esc_html($this->subscription_types->get_type_label($subscription_type)); ?>
                        </option>
                    </select>
                </div>
                
                <div class="lilac-form-row lilac-form-row-inline">
                    <label for="subscription-option-<?php echo esc_attr($course_id); ?>">
                        <?php _e('Subscription Option', 'lilac-learning-manager'); ?>
                    </label>
                    
                    <select id="subscription-option-<?php echo esc_attr($course_id); ?>" 
                            class="lilac-subscription-option-select" 
                            name="option_id">
                        <option value=""><?php _e('Select an option', 'lilac-learning-manager'); ?></option>
                        <?php foreach ($type_options as $option_id => $option) : ?>
                            <option value="<?php echo esc_attr($option_id); ?>">
                                <?php echo esc_html($option['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="lilac-form-row">
                    <button type="submit" class="lilac-subscription-button" disabled>
                        <?php _e('Activate Subscription', 'lilac-learning-manager'); ?>
                    </button>
                </div>
                
                <div class="lilac-subscription-status"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render expired subscription message
     * 
     * @param array $subscription Subscription data
     * @return string Rendered HTML
     */
    private function render_expired_subscription($subscription) {
        $course_id = $subscription['course_id'];
        $end_date = date_i18n(get_option('date_format'), strtotime($subscription['expires_at']));
        
        // Get course subscription settings
        $course_meta = new Course_Meta($this->subscription_types);
        $settings = $course_meta->get_course_subscription_settings($course_id);
        
        ob_start();
        ?>
        <div class="lilac-subscription-container">
            <div class="lilac-subscription-status-display">
                <div class="lilac-subscription-expired">
                    <?php 
                    printf(
                        __('Your subscription expired on %s.', 'lilac-learning-manager'),
                        '<strong>' . $end_date . '</strong>'
                    );
                    ?>
                </div>
            </div>
            
            <?php if (isset($settings['allows_renewal']) && $settings['allows_renewal']) : ?>
            <form class="lilac-subscription-form" data-course-id="<?php echo esc_attr($course_id); ?>">
                <?php wp_nonce_field('lilac_subscription_nonce', 'lilac_subscription_nonce'); ?>
                
                <div class="lilac-form-row">
                    <h4><?php _e('Renew Your Subscription', 'lilac-learning-manager'); ?></h4>
                </div>
                
                <div class="lilac-form-row lilac-form-row-inline">
                    <label for="subscription-type-<?php echo esc_attr($course_id); ?>">
                        <?php _e('Subscription Type', 'lilac-learning-manager'); ?>
                    </label>
                    
                    <select id="subscription-type-<?php echo esc_attr($course_id); ?>" 
                            class="lilac-subscription-type-select" 
                            name="subscription_type" 
                            disabled>
                        <option value="<?php echo esc_attr($settings['subscription_type']); ?>" selected>
                            <?php echo esc_html($this->subscription_types->get_type_label($settings['subscription_type'])); ?>
                        </option>
                    </select>
                </div>
                
                <div class="lilac-form-row lilac-form-row-inline">
                    <label for="subscription-option-<?php echo esc_attr($course_id); ?>">
                        <?php _e('Subscription Option', 'lilac-learning-manager'); ?>
                    </label>
                    
                    <select id="subscription-option-<?php echo esc_attr($course_id); ?>" 
                            class="lilac-subscription-option-select" 
                            name="option_id">
                        <option value=""><?php _e('Select an option', 'lilac-learning-manager'); ?></option>
                    </select>
                </div>
                
                <div class="lilac-form-row">
                    <button type="submit" class="lilac-subscription-button" disabled>
                        <?php _e('Renew Subscription', 'lilac-learning-manager'); ?>
                    </button>
                </div>
                
                <div class="lilac-subscription-status"></div>
            </form>
            <?php else : ?>
                <div class="lilac-message lilac-message-info">
                    <?php _e('Please purchase a new subscription to regain access.', 'lilac-learning-manager'); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render unknown status message
     * 
     * @return string Rendered HTML
     */
    private function render_unknown_status_message() {
        return '<div class="lilac-message lilac-message-error">' . 
               __('Unknown subscription status. Please contact support.', 'lilac-learning-manager') . 
               '</div>';
    }
}
