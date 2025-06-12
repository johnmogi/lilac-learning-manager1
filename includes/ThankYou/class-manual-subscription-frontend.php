<?php
namespace LilacLearningManager\ThankYou;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles frontend functionality for manual subscription activation
 */
class Manual_Subscription_Frontend {
    /**
     * Initialize frontend hooks
     */
    public function __construct() {
        // Register shortcode
        add_shortcode('lilac_activate_subscription', [$this, 'render_activation_form']);
        
        // Handle form submission
        add_action('template_redirect', [$this, 'handle_activation_submission']);
        
        // Modify thank you page
        add_action('woocommerce_thankyou', [$this, 'maybe_add_activation_notice'], 5);
    }
    
    /**
     * Render the subscription activation form
     */
    public function render_activation_form() {
        if (!is_user_logged_in()) {
            return $this->get_login_required_message();
        }
        
        $user_id = get_current_user_id();
        $order = $this->get_user_latest_order($user_id);
        
        if (!$order) {
            return $this->get_no_orders_message();
        }
        
        // Check if activation is needed
        $needs_activation = get_post_meta($order->get_id(), Manual_Subscription::META_KEY_NEEDS_ACTIVATION, true);
        
        if (!$needs_activation) {
            return $this->get_no_activation_needed_message();
        }
        
        // Display the activation form
        ob_start();
        ?>
        <div class="lilac-subscription-activation">
            <h2><?php esc_html_e('Activate Your Subscription', 'lilac-learning-manager'); ?></h2>
            <p><?php esc_html_e('You can activate your subscription now or come back later to start your access period.', 'lilac-learning-manager'); ?></p>
            
            <form method="post" class="lilac-activation-form">
                <?php wp_nonce_field('lilac_activate_subscription', 'lilac_subscription_nonce'); ?>
                <input type="hidden" name="lilac_order_id" value="<?php echo esc_attr($order->get_id()); ?>">
                <button type="submit" name="lilac_activate_subscription" class="button">
                    <?php esc_html_e('Activate Subscription Now', 'lilac-learning-manager'); ?>
                </button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Handle form submission
     */
    public function handle_activation_submission() {
        if (!isset($_POST['lilac_activate_subscription']) || !is_user_logged_in()) {
            return;
        }
        
        if (!isset($_POST['lilac_subscription_nonce']) || 
            !wp_verify_nonce($_POST['lilac_subscription_nonce'], 'lilac_activate_subscription')) {
            wp_die(__('Security check failed', 'lilac-learning-manager'));
        }
        
        $order_id = isset($_POST['lilac_order_id']) ? intval($_POST['lilac_order_id']) : 0;
        $order = wc_get_order($order_id);
        $user_id = get_current_user_id();
        
        // Verify order belongs to user
        if (!$order || $order->get_user_id() !== $user_id) {
            wp_die(__('Invalid order', 'lilac-learning-manager'));
        }
        
        // Process activation
        $this->activate_subscription($order);
        
        // Redirect to avoid form resubmission
        wp_redirect(add_query_arg('activated', '1', get_permalink()));
        exit;
    }
    
    /**
     * Activate subscription for an order
     */
    private function activate_subscription($order) {
        $user_id = $order->get_user_id();
        
        // Grant course access for each product in the order
        foreach ($order->get_items() as $item) {
            $product = wc_get_product($item->get_product_id());
            $course_id = $product->get_meta('_ld_course');
            
            if ($course_id) {
                ld_update_course_access($user_id, $course_id, true);
            }
        }
        
        // Mark as activated
        update_post_meta($order->get_id(), Manual_Subscription::META_KEY_NEEDS_ACTIVATION, '0');
        
        // Trigger action for other plugins
        do_action('lilac_subscription_activated', $order->get_id(), $user_id);
    }
    
    /**
     * Add activation notice to thank you page if needed
     */
    public function maybe_add_activation_notice($order_id) {
        $needs_activation = get_post_meta($order_id, Manual_Subscription::META_KEY_NEEDS_ACTIVATION, true);
        
        if ($needs_activation) {
            $activation_url = home_url(Manual_Subscription::ACTIVATION_PAGE_SLUG);
            wc_add_notice(
                sprintf(
                    __('Thank you for your order! You can activate your subscription at any time by visiting the %sactivation page%s.', 'lilac-learning-manager'),
                    '<a href="' . esc_url($activation_url) . '">',
                    '</a>'
                ),
                'success'
            );
        }
    }
    
    /**
     * Helper: Get user's latest order
     */
    private function get_user_latest_order($user_id) {
        $orders = wc_get_orders([
            'customer_id' => $user_id,
            'limit' => 1,
            'orderby' => 'date',
            'order' => 'DESC'
        ]);
        
        return !empty($orders) ? $orders[0] : false;
    }
    
    /**
     * Helper: Login required message
     */
    private function get_login_required_message() {
        return sprintf(
            __('Please %1$slog in%2$s to activate your subscription.', 'lilac-learning-manager'),
            '<a href="' . esc_url(wp_login_url(get_permalink())) . '">',
            '</a>'
        );
    }
    
    /**
     * Helper: No orders message
     */
    private function get_no_orders_message() {
        return '<p>' . __('No orders found that require activation.', 'lilac-learning-manager') . '</p>';
    }
    
    /**
     * Helper: No activation needed message
     */
    private function get_no_activation_needed_message() {
        return '<p>' . __('Your subscription is already active!', 'lilac-learning-manager') . '</p>';
    }
}
