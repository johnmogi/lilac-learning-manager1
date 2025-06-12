<?php
namespace LilacLearningManager\ThankYou;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Intercepts and prevents the automatic course redirect for products with manual activation enabled
 */
class Manual_Subscription_Interceptor {
    /**
     * Debug mode
     *
     * @var bool
     */
    private $debug = true;
    
    /**
     * Log entries
     *
     * @var array
     */
    private $logs = [];
    /**
     * Initialize interceptor hooks
     */
    public function __construct() {
        // Enable debug logging
        $this->debug = true;
        $this->log('Initializing Manual_Subscription_Interceptor');
        // HIGHEST PRIORITY HOOKS - to ensure they run first
        
        // Completely disable course redirects for our products
        add_action('init', [$this, 'disable_course_redirect_hooks'], 1);
        
        // Prevent automatic redirect to course
        add_filter('learndash_woocommerce_auto_complete_order', [$this, 'maybe_disable_auto_complete'], 1, 2);
        
        // Disable automatic enrollment if manual activation is enabled
        add_filter('learndash_woocommerce_auto_enroll', [$this, 'maybe_disable_auto_enroll'], 1, 4);
        
        // Prevent automatic course access
        add_filter('learndash_woocommerce_get_course_id', [$this, 'maybe_prevent_course_access'], 1, 3);
        
        // Add our custom hooks at various stages to ensure redirect blocking
        add_action('wp', [$this, 'ultra_early_redirect_blocker'], 1); // Ultra early
        add_action('template_redirect', [$this, 'cancel_course_redirect'], 1); // Normal
        add_filter('wp_redirect', [$this, 'block_learndash_redirects'], 1, 2); // Last line of defense
        
        // Add debug log output on shutdown
        add_action('shutdown', [$this, 'output_debug_log']);
    }
    
    /**
     * Ultra early redirect blocker that runs on the 'wp' hook
     * This catches redirects before most other hooks run
     */
    public function ultra_early_redirect_blocker() {
        global $wp;
        
        // Process on any checkout page, order-received page or if redirect parameter is present
        if (!is_checkout() && !is_wc_endpoint_url('order-received') && !isset($_GET['ld-wc-redirect'])) {
            return;
        }
        
        // FOR TESTING: Block ALL LearnDash redirects completely
        
        // Remove ALL actions from WooCommerce thankyou hook
        remove_all_actions('woocommerce_thankyou');
        
        // Re-add only core WooCommerce actions
        add_action('woocommerce_thankyou', 'woocommerce_order_details_table', 10);
        
        // Remove redirect parameters if present
        if (isset($_GET['ld-wc-redirect'])) {
            // Add debugging info
            error_log('LILAC DEBUG: Blocking ld-wc-redirect parameter');
            
            // Redirect to same URL without the parameter
            wp_redirect(remove_query_arg('ld-wc-redirect'));
            exit;
        }
        
        // Disable ALL LearnDash redirect hooks
        if (class_exists('LearnDash_WooCommerce')) {
            global $learndash_woocommerce;
            
            if ($learndash_woocommerce) {
                // Remove all methods that might trigger redirects
                remove_action('template_redirect', array($learndash_woocommerce, 'check_redirect'));
                remove_action('woocommerce_order_status_completed', array($learndash_woocommerce, 'auto_complete_transaction'));
                remove_action('woocommerce_payment_complete', array($learndash_woocommerce, 'auto_complete_transaction'));
                
                // Log the block
                error_log('LILAC DEBUG: Blocked ALL LearnDash WooCommerce redirects');
            }
        }
    }
    
    /**
     * Global WordPress redirect filter to catch any redirects to course pages
     * This is our last line of defense against unwanted redirects
     * FOR TESTING: Block ALL course redirects completely
     *
     * @param string $location The redirect location
     * @param int $status The redirect status code
     * @return string The possibly modified redirect location
     */
    public function block_learndash_redirects($location, $status) {
        // Log all redirects for debugging
        error_log('LILAC DEBUG: Redirect detected - From: ' . $_SERVER['REQUEST_URI'] . ' To: ' . $location);
        
        // Check if this is a redirect to a course page
        if (strpos($location, '/courses/') !== false || 
            strpos($location, 'sfwd-courses') !== false ||
            strpos($location, 'course_id') !== false ||
            strpos($location, 'learndash') !== false) {
            
            // FOR TESTING: Block ANY course redirect
            error_log('LILAC DEBUG: BLOCKED course redirect to: ' . $location);
            
            // If we're on the thank you page, stay there
            if (is_wc_endpoint_url('order-received')) {
                global $wp;
                if (!empty($wp->query_vars['order-received'])) {
                    $order_id = absint($wp->query_vars['order-received']);
                    $order = wc_get_order($order_id);
                    if ($order) {
                        // Add debug query parameter to indicate redirect was blocked
                        $thank_you_url = add_query_arg('redirect-blocked', '1', $order->get_checkout_order_received_url());
                        error_log('LILAC DEBUG: Redirecting back to thank you page: ' . $thank_you_url);
                        return $thank_you_url;
                    }
                }
            }
            
            // If we're not on thank you page but coming from it
            $referer = wp_get_referer();
            if ($referer && strpos($referer, 'order-received') !== false) {
                // Redirect back to referring thank you page
                $return_url = add_query_arg('redirect-blocked', '1', remove_query_arg('ld-wc-redirect', $referer));
                error_log('LILAC DEBUG: Redirecting back to referrer: ' . $return_url);
                return $return_url;
            }
            
            // Last resort: stay on current page by returning empty location
            error_log('LILAC DEBUG: Blocking redirect by returning current URL');
            return remove_query_arg('ld-wc-redirect', $_SERVER['REQUEST_URI']);
        }
        
        return $location;
    }
    
    /**
     * Maybe prevent automatic order completion for manual subscription products
     */
    public function maybe_disable_auto_complete($auto_complete, $order) {
        $order_id = is_object($order) && method_exists($order, 'get_id') ? $order->get_id() : 'unknown';
        $this->log('maybe_disable_auto_complete check for order: ' . $order_id);
        // Skip if not an order object
        if (!is_object($order) || !method_exists($order, 'get_id')) {
            return $auto_complete;
        }
        
        // Check if any product requires manual activation
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $enable_manual_activation = get_field('enable_subscription_toggle', $product_id);
            
            if ($enable_manual_activation) {
                $this->log('Found product ' . $product_id . ' requiring manual activation in order ' . $order->get_id());
                update_post_meta($order->get_id(), Manual_Subscription::META_KEY_NEEDS_ACTIVATION, '1');
                $this->log('PREVENTING AUTO COMPLETION for order ' . $order->get_id());
                return false; // Prevent auto completion
            }
        }
        
        return $auto_complete;
    }
    
    /**
     * Maybe disable auto enrollment for manual subscription products
     */
    public function maybe_disable_auto_enroll($should_enroll, $user_id, $product_id, $order) {
        $order_id = is_object($order) && method_exists($order, 'get_id') ? $order->get_id() : 'unknown';
        $this->log('maybe_disable_auto_enroll check - user: ' . $user_id . ', product: ' . $product_id . ', order: ' . $order_id);
        // Check if this product requires manual activation
        $enable_manual_activation = get_field('enable_subscription_toggle', $product_id);
        
        if ($enable_manual_activation) {
            $this->log('PREVENTING AUTO ENROLLMENT for product ' . $product_id);
            return false; // Prevent auto enrollment
        }
        
        return $should_enroll;
    }
    
    /**
     * Maybe prevent course access for manual subscription products
     */
    public function maybe_prevent_course_access($course_id, $product_id, $order_id) {
        $this->log('maybe_prevent_course_access check - course: ' . $course_id . ', product: ' . $product_id . ', order: ' . $order_id);
        // Check if product requires manual activation
        $enable_manual_activation = get_field('enable_subscription_toggle', $product_id);
        
        if ($enable_manual_activation) {
            // Check if already manually activated
            $order = wc_get_order($order_id);
            if ($order && get_post_meta($order_id, Manual_Subscription::META_KEY_NEEDS_ACTIVATION, true)) {
                $this->log('PREVENTING COURSE ACCESS to course ' . $course_id . ' for order ' . $order_id);
                return 0; // Return 0 to prevent automatic access to course
            }
        }
        
        return $course_id;
    }
    
    /**
     * Completely disable LearnDash WooCommerce integration redirect hooks
     * This is the most aggressive approach to prevent automatic course redirect
     */
    public function disable_course_redirect_hooks() {
        $this->log('disable_course_redirect_hooks called');
        global $wp_filter;
        
        // Check if there's an order that needs manual activation
        $order_id = null;
        $needs_activation = false;
        
        // Check if we're on the thank you page
        if (is_wc_endpoint_url('order-received')) {
            global $wp;
            if (!empty($wp->query_vars['order-received'])) {
                $order_id = absint($wp->query_vars['order-received']);
                $needs_activation = get_post_meta($order_id, Manual_Subscription::META_KEY_NEEDS_ACTIVATION, true);
                
                // If not already flagged, check each product in the order
                if (!$needs_activation && $order_id) {
                    $order = wc_get_order($order_id);
                    if ($order) {
                        foreach ($order->get_items() as $item) {
                            $product_id = $item->get_product_id();
                            $enable_manual_activation = get_field('enable_subscription_toggle', $product_id);
                            
                            if ($enable_manual_activation) {
                                update_post_meta($order_id, Manual_Subscription::META_KEY_NEEDS_ACTIVATION, '1');
                                $needs_activation = true;
                                break;
                            }
                        }
                    }
                }
            }
        }
        
        // Remove LearnDash redirect actions from WooCommerce - This is always done for safety
        $wc_hooks_to_check = [
            'woocommerce_payment_complete',
            'woocommerce_order_status_completed',
            'woocommerce_order_status_processing',
            'woocommerce_thankyou',
            'woocommerce_before_thank_you',
            'woocommerce_after_checkout_form',
            'woocommerce_checkout_order_processed'
        ];
        
        // For maximum effectiveness, completely remove all LearnDash WooCommerce hooks if we're on a thank you page
        // that needs manual activation, or if we have the ld-wc-redirect parameter
        $remove_aggressively = ($needs_activation || isset($_GET['ld-wc-redirect']));
        
        foreach ($wc_hooks_to_check as $hook_name) {
            if (!empty($wp_filter[$hook_name])) {
                foreach ($wp_filter[$hook_name]->callbacks as $priority => $callbacks) {
                    foreach ($callbacks as $id => $callback) {
                        // If the callback function/method contains 'learndash', remove it
                        $should_remove = false;
                        
                        if (is_array($callback['function'])) {
                            // For class methods
                            if (is_object($callback['function'][0])) {
                                $class_name = get_class($callback['function'][0]);
                                if (strpos($class_name, 'LearnDash') !== false || 
                                    strpos($class_name, 'learndash') !== false) {
                                    $should_remove = true;
                                }
                            } elseif (is_string($callback['function'][0])) {
                                $class_name = $callback['function'][0];
                                if (strpos($class_name, 'LearnDash') !== false || 
                                    strpos($class_name, 'learndash') !== false) {
                                    $should_remove = true;
                                }
                            }
                        } elseif (is_string($callback['function']) && 
                                 (strpos($callback['function'], 'learndash') !== false ||
                                  strpos($callback['function'], 'sfwd') !== false)) {
                            // For direct function calls
                            $should_remove = true;
                        }
                        
                        if ($should_remove) {
                            remove_action($hook_name, $callback['function'], $priority);
                            remove_filter($hook_name, $callback['function'], $priority);
                        }
                    }
                }
            }
        }
        
        // If we have a redirect parameter, time to be very aggressive
        if (isset($_GET['ld-wc-redirect'])) {
            $this->log('LD-WC-REDIRECT PARAMETER DETECTED - removing all LearnDash hooks');
            // Specifically target the auto_complete_transaction function
            remove_action('woocommerce_payment_complete', array('Learndash_WooCommerce', 'auto_complete_transaction'));
            remove_action('woocommerce_thankyou', array('Learndash_WooCommerce', 'auto_complete_transaction'));
            
            // Also block any potential redirects in template_redirect and wp_loaded
            add_action('template_redirect', function() {
                if (isset($_GET['ld-wc-redirect'])) {
                    // Redirect back to the thank you page without the parameter
                    wp_redirect(remove_query_arg('ld-wc-redirect'));
                    exit;
                }
            }, 0); // Highest priority (0)
        }
        
        // For orders that need manual activation, hook into template_includes to ensure
        // we never load a LearnDash course page right after checkout
        if ($needs_activation) {
            add_filter('template_include', function($template) {
                // If this is a learndash course page and we just completed checkout, block it
                if (function_exists('learndash_get_post_type_slug') && 
                    get_post_type() === learndash_get_post_type_slug('course') &&
                    isset($_SERVER['HTTP_REFERER']) && 
                    strpos($_SERVER['HTTP_REFERER'], 'order-received') !== false) {
                    
                    // Force redirect to the order received page
                    global $wp;
                    $order_id = absint($wp->query_vars['order-received'] ?? 0);
                    if ($order_id) {
                        $order = wc_get_order($order_id);
                        if ($order) {
                            wp_redirect($order->get_checkout_order_received_url());
                            exit;
                        }
                    }
                }
                return $template;
            }, 100);
        }
    }
        
        // Remove specific known redirect hooks
        remove_filter('woocommerce_payment_complete_order_status', ['LearnDash_WooCommerce', 'auto_complete_order']);
        remove_action('woocommerce_order_status_completed', ['LearnDash_WooCommerce', 'add_course_access']);
        remove_action('woocommerce_payment_complete', ['LearnDash_WooCommerce', 'add_course_access']);
        
        // Also remove them with priority 10 (default)
        remove_action('woocommerce_order_status_completed', ['LearnDash_WooCommerce', 'add_course_access'], 10);
        remove_action('woocommerce_payment_complete', ['LearnDash_WooCommerce', 'add_course_access'], 10);
    }
    
    /**
     * Cancel course redirect if it's already happening
     * This is a last resort to prevent redirect
     */
    public function cancel_course_redirect() {
        // Log current URL and referrer
        $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'no_referrer';
        $this->log('cancel_course_redirect called - URL: ' . $current_url);
        $this->log('Referrer: ' . $referrer);
        
        // Check if we're on a thank you page or potentially being redirected to a course
        $is_thank_you = is_wc_endpoint_url('order-received');
        $has_redirect_param = isset($_GET['ld-wc-redirect']);
        $is_course_page = (function_exists('learndash_get_post_type_slug') && get_post_type() === learndash_get_post_type_slug('course'));
        
        $this->log('Status: thank_you=' . ($is_thank_you ? 'true' : 'false') . ', has_redirect=' . ($has_redirect_param ? 'true' : 'false') . ', is_course_page=' . ($is_course_page ? 'true' : 'false'));
        
        // If nothing relevant is happening, exit early
        if (!$is_thank_you && !$has_redirect_param && !$is_course_page) {
            return;
        }
        
        // Get order ID from URL if we're on the thank you page
        $order_id = null;
        if ($is_thank_you) {
            global $wp;
            if (!empty($wp->query_vars['order-received'])) {
                $order_id = absint($wp->query_vars['order-received']);
            }
        }
        
        // If we don't have an order ID but have a session, try to get the last order
        if (!$order_id && function_exists('WC') && WC()->session) {
            $order_id = WC()->session->get('last_order_id');
        }
        
        // If we have an order ID, check if it needs manual activation
        if ($order_id) {
            $this->log('Order ID found: ' . $order_id);
            $order = wc_get_order($order_id);
            if (!$order) {
                $this->log('Unable to get order object for ID: ' . $order_id);
                return;
            }
            
            // First check if order already has the manual activation flag
            $needs_activation = get_post_meta($order_id, Manual_Subscription::META_KEY_NEEDS_ACTIVATION, true);
            $this->log('Order needs activation flag: ' . ($needs_activation ? 'true' : 'false'));
            
            // If not already flagged, check each product
            if (!$needs_activation) {
                foreach ($order->get_items() as $item) {
                    $product_id = $item->get_product_id();
                    $enable_manual_activation = get_field('enable_subscription_toggle', $product_id);
                    
                    if ($enable_manual_activation) {
                        $this->log('Product ' . $product_id . ' requires manual activation');
                        update_post_meta($order_id, Manual_Subscription::META_KEY_NEEDS_ACTIVATION, '1');
                        $needs_activation = true;
                        break;
                    }
                }
            }
            
            // If this order needs manual activation, block any redirects
            if ($needs_activation) {
                $this->log('ORDER NEEDS MANUAL ACTIVATION - Attempting to block redirect');
                // Block course auto-enrollment one more time for good measure
                foreach ($order->get_items() as $item) {
                    $product_id = $item->get_product_id();
                    $product = wc_get_product($product_id);
                    if ($product) {
                        $course_id = $product->get_meta('_related_course');
                        if ($course_id && $order->get_user_id()) {
                            // Force remove course access
                            ld_update_course_access($order->get_user_id(), $course_id, false);
                        }
                    }
                }
                
                // If we're being redirected, stop it and go back to thank you page
                if ($has_redirect_param || ($is_course_page && !$is_thank_you)) {
                    $thank_you_url = $order->get_checkout_order_received_url();
                    
                    // Add debug parameter to track redirect prevention
                    $thank_you_url = add_query_arg('lilac-redirect-prevented', '1', $thank_you_url);
                    
                    $this->log('PREVENTING REDIRECT: Redirecting back to thank you page: ' . $thank_you_url);
                    
                    // Redirect back to thank you page
                    wp_redirect($thank_you_url);
                    exit;
                }
            }
        } else if ($has_redirect_param) {
            // If we have a redirect parameter but no order ID, still block the redirect
            // This is a failsafe in case we can't determine the order
            $this->log('REDIRECT PARAMETER DETECTED with no order ID - blocking redirect');
            wp_redirect(remove_query_arg('ld-wc-redirect'));
            exit;
        }
    }
    
    /**
     * Log message for debugging
     * 
     * @param string $message The message to log
     */
    private function log($message) {
        if ($this->debug) {
            $timestamp = date('[Y-m-d H:i:s]');
            $this->logs[] = $timestamp . ' ' . $message;
        }
    }
    
    /**
     * Output debug log to error log and hidden HTML comment
     */
    public function output_debug_log() {
        if ($this->debug && !empty($this->logs)) {
            // Only output on frontend pages where we'd expect redirects
            $should_output = is_wc_endpoint_url('order-received') || 
                            isset($_GET['ld-wc-redirect']) ||
                            (function_exists('learndash_get_post_type_slug') && 
                             get_post_type() === learndash_get_post_type_slug('course'));
            
            if ($should_output) {
                // Write to error log
                error_log('===== LILAC SUBSCRIPTION INTERCEPTOR LOG START =====');
                foreach ($this->logs as $log) {
                    error_log($log);
                }
                error_log('===== LILAC SUBSCRIPTION INTERCEPTOR LOG END =====');
                
                // Output as HTML comment if admin is logged in
                if (current_user_can('administrator')) {
                    echo '<!-- LILAC SUBSCRIPTION INTERCEPTOR LOG\n';
                    foreach ($this->logs as $log) {
                        echo esc_html($log) . "\n";
                    }
                    echo '-->';
                }
            }
        }
    }
}
