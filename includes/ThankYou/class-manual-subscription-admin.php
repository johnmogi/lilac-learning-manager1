<?php
namespace LilacLearningManager\ThankYou;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles admin functionality for manual subscription activation
 */
class Manual_Subscription_Admin {
    /**
     * Initialize admin hooks
     */
    public function __construct() {
        // Add ACF fields for products
        add_action('acf/init', [$this, 'add_acf_fields']);
        
        // Save ACF field on order
        add_action('woocommerce_checkout_update_order_meta', [$this, 'save_subscription_toggle'], 10, 1);
    }
    
    /**
     * This method is kept for future extensibility
     * We're using the existing ACF field 'enable_subscription_toggle'
     */
    public function add_acf_fields() {
        // Using existing ACF field - no need to add a new one
        return;
    }
    
    /**
     * Save subscription toggle from checkout
     */
    public function save_subscription_toggle($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        // Check all products in order for manual activation setting
        $needs_activation = false;
        
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $enable_manual_activation = get_field('enable_subscription_toggle', $product_id);
            
            if ($enable_manual_activation) {
                $needs_activation = true;
                break;
            }
        }
        
        if ($needs_activation) {
            // Mark order as needing manual activation
            update_post_meta($order_id, Manual_Subscription::META_KEY_NEEDS_ACTIVATION, '1');
            
            // Remove course access until manually activated
            if ($order->get_user_id()) {
                foreach ($order->get_items() as $item) {
                    $product = wc_get_product($item->get_product_id());
                    $course_id = $product->get_meta('_ld_course');
                    if ($course_id) {
                        ld_update_course_access($order->get_user_id(), $course_id, false);
                    }
                }
            }
        }
    }
}
