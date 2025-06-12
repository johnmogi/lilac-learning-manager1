<?php
namespace LilacLearningManager\Subscriptions;

/**
 * Handles subscription-related shortcodes
 */
class Subscription_Shortcodes {
    /**
     * @var Subscription_Manager
     */
    private $subscription_manager;
    
    /**
     * @var Subscription_Types
     */
    private $subscription_types;
    
    /**
     * @var Subscription_UI
     */
    private $subscription_ui;

    /**
     * Constructor
     * 
     * @param Subscription_Manager $subscription_manager
     * @param Subscription_Types $subscription_types
     * @param Subscription_UI $subscription_ui
     */
    public function __construct(
        Subscription_Manager $subscription_manager,
        Subscription_Types $subscription_types,
        Subscription_UI $subscription_ui
    ) {
        $this->subscription_manager = $subscription_manager;
        $this->subscription_types = $subscription_types;
        $this->subscription_ui = $subscription_ui;
        $this->setup_hooks();
    }

    /**
     * Set up WordPress hooks
     */
    private function setup_hooks() {
        // Add shortcode for subscription button
        add_shortcode('lilac_subscription_button', [$this, 'render_subscription_button']);
        
        // Add shortcode for subscription status
        add_shortcode('lilac_subscription_status', [$this, 'render_subscription_status']);
    }

    /**
     * Render subscription button shortcode
     */
    public function render_subscription_button($atts) {
        // Only proceed if user is logged in
        if (!is_user_logged_in()) {
            return $this->render_login_required_message();
        }

        // Parse attributes
        $atts = shortcode_atts([
            'course_id' => get_the_ID(),
            'label'     => __('Activate Subscription', 'lilac-learning-manager'),
            'class'     => 'lilac-subscription-button',
            'show_status' => 'yes',
        ], $atts);

        $course_id = absint($atts['course_id']);
        $user_id = get_current_user_id();
        
        // Check if course exists
        if (!get_post($course_id)) {
            return $this->render_error_message(__('Invalid course ID.', 'lilac-learning-manager'));
        }
        
        // Use the subscription UI class to render the subscription status
        return $this->subscription_ui->render_subscription_ui($user_id, $course_id);
    }
    
    /**
     * Render subscription status shortcode
     */
    public function render_subscription_status($atts) {
        // Only proceed if user is logged in
        if (!is_user_logged_in()) {
            return '';
        }

        // Parse attributes
        $atts = shortcode_atts([
            'course_id' => get_the_ID(),
            'show_title' => 'yes',
            'show_expiry' => 'yes',
            'show_days_remaining' => 'yes',
            'show_ui' => 'no',
        ], $atts);

        $course_id = absint($atts['course_id']);
        $user_id = get_current_user_id();
        
        // Check if course exists
        if (!get_post($course_id)) {
            return '';
        }
        
        // If show_ui is yes, use the full subscription UI
        if ($atts['show_ui'] === 'yes') {
            return $this->subscription_ui->render_subscription_ui($user_id, $course_id);
        }
        
        // Otherwise, just show the status information
        $subscription = $this->subscription_manager->get_user_subscription($user_id, $course_id);
        
        // No subscription or not active
        if (empty($subscription) || $subscription['status'] !== 'active') {
            return '';
        }
        
        // Prepare output
        ob_start();
        ?>
        <div class="lilac-subscription-status-display">
            <?php if ($atts['show_title'] === 'yes') : ?>
                <h4><?php _e('Your Subscription', 'lilac-learning-manager'); ?></h4>
            <?php endif; ?>
            
            <div class="lilac-subscription-active">
                <?php 
                if (empty($subscription['expires_at']) || $subscription['expires_at'] === '0000-00-00 00:00:00') {
                    echo __('Your subscription is active (no expiration date).', 'lilac-learning-manager');
                } else {
                    $expiry_date = date_i18n(get_option('date_format'), strtotime($subscription['expires_at']));
                    $days_remaining = ceil((strtotime($subscription['expires_at']) - strtotime(current_time('mysql'))) / DAY_IN_SECONDS);
                    
                    if ($atts['show_expiry'] === 'yes') {
                        printf(
                            __('Your subscription is active until %s', 'lilac-learning-manager'),
                            '<strong>' . $expiry_date . '</strong>'
                        );
                        
                        if ($atts['show_days_remaining'] === 'yes') {
                            echo ' ';
                            printf(
                                _n('(%s day remaining)', '(%s days remaining)', $days_remaining, 'lilac-learning-manager'),
                                $days_remaining
                            );
                        }
                        
                        if ($days_remaining <= 7) {
                            echo ' <span class="lilac-days-left">' . 
                                __('Expiring soon!', 'lilac-learning-manager') . 
                                '</span>';
                        }
                    }
                }
                ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render login required message
     */
    private function render_login_required_message() {
        ob_start();
        ?>
        <div class="lilac-subscription-container">
            <div class="lilac-message lilac-message-info">
                <p>
                    <?php _e('Please log in to manage your subscription.', 'lilac-learning-manager'); ?>
                </p>
                <p>
                    <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="button">
                        <?php _e('Log In', 'lilac-learning-manager'); ?>
                    </a>
                    
                    <?php if (get_option('users_can_register')) : ?>
                    <a href="<?php echo esc_url(wp_registration_url()); ?>" class="button">
                        <?php _e('Register', 'lilac-learning-manager'); ?>
                    </a>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render error message
     */
    private function render_error_message($message) {
        return '<div class="lilac-subscription-container"><div class="lilac-message lilac-message-error">' . esc_html($message) . '</div></div>';
    }
}
