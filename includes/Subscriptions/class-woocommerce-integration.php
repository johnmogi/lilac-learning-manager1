<?php
namespace LilacLearningManager\Subscriptions;

/**
 * Handles WooCommerce integration for subscriptions
 */
class WooCommerce_Integration {
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
     * @var Course_Meta
     */
    private $course_meta;
    
    /**
     * Constructor
     * 
     * @param Subscription_Manager $subscription_manager
     * @param Subscription_Types $subscription_types
     * @param Subscription_UI $subscription_ui
     * @param Course_Meta $course_meta
     */
    public function __construct(
        Subscription_Manager $subscription_manager,
        Subscription_Types $subscription_types,
        Subscription_UI $subscription_ui,
        Course_Meta $course_meta
    ) {
        $this->subscription_manager = $subscription_manager;
        $this->subscription_types = $subscription_types;
        $this->subscription_ui = $subscription_ui;
        $this->course_meta = $course_meta;
        
        $this->setup_hooks();
    }
    
    /**
     * Set up WordPress hooks
     */
    private function setup_hooks() {
        // Add content to WooCommerce thank you page
        add_action('woocommerce_thankyou', [$this, 'display_subscription_activation'], 20);
        
        // Add course selection to WooCommerce product admin
        add_action('woocommerce_product_options_general_product_data', [$this, 'add_course_field']);
        add_action('woocommerce_process_product_meta', [$this, 'save_course_field']);
        
        // Store course info in order meta when purchased
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'add_course_to_order_item'], 10, 4);
    }
    
    /**
     * Display subscription activation forms on thank you page
     * 
     * @param int $order_id WooCommerce order ID
     */
    public function display_subscription_activation($order_id) {
        // Get order
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            echo '<div class="lilac-subscription-section">';
            echo '<h3>' . __('Course Access', 'lilac-learning-manager') . '</h3>';
            echo '<div class="lilac-subscription-container">';
            echo '<div class="lilac-message lilac-message-warning">';
            echo __('Please log in to activate your course access.', 'lilac-learning-manager');
            echo '</div>';
            echo '</div>';
            echo '</div>';
            return;
        }
        
        $user_id = get_current_user_id();
        $has_courses = false;
        $course_items = [];
        
        // Loop through order items
        foreach ($order->get_items() as $item_id => $item) {
            // Check if item has course ID
            $course_id = wc_get_order_item_meta($item_id, '_lilac_course_id', true);
            if (!$course_id) {
                continue;
            }
            
            // Check if course exists
            $course = get_post($course_id);
            if (!$course || $course->post_type !== 'sfwd-courses') {
                continue;
            }
            
            // Get course subscription settings
            $settings = $this->course_meta->get_course_subscription_settings($course_id);
            
            // Check if course requires manual activation
            if (!isset($settings['requires_manual_activation']) || !$settings['requires_manual_activation']) {
                continue;
            }
            
            // Add course to list
            $has_courses = true;
            $course_items[] = [
                'course_id' => $course_id,
                'course_title' => get_the_title($course_id),
                'settings' => $settings
            ];
        }
        
        // If no courses require manual activation, exit
        if (!$has_courses) {
            return;
        }
        
        // Display activation section
        echo '<div class="lilac-subscription-section">';
        echo '<h3>' . __('Activate Your Course Access', 'lilac-learning-manager') . '</h3>';
        echo '<p>' . __('Please activate your subscription for the following courses:', 'lilac-learning-manager') . '</p>';
        
        // Loop through courses and display activation forms
        foreach ($course_items as $item) {
            // Check if user already has an active subscription
            $subscription = $this->subscription_manager->get_user_subscription($user_id, $item['course_id']);
            
            echo '<div class="lilac-course-activation-item">';
            echo '<h4>' . esc_html($item['course_title']) . '</h4>';
            
            if (!empty($subscription) && $subscription['status'] === 'active') {
                // User already has active subscription
                echo $this->subscription_ui->render_subscription_ui($user_id, $item['course_id']);
            } else {
                // Create pending subscription if it doesn't exist
                if (empty($subscription) || $subscription['status'] !== 'pending') {
                    $this->subscription_manager->create_pending_subscription($user_id, $item['course_id']);
                }
                
                // Display activation form
                echo $this->subscription_ui->render_subscription_ui($user_id, $item['course_id']);
            }
            
            echo '</div>';
        }
        
        echo '</div>';
        
        // Add script to scroll to activation section if 'activate' parameter is in URL
        ?>
        <script>
        jQuery(document).ready(function($) {
            if (window.location.href.indexOf('activate=1') > -1) {
                $('html, body').animate({
                    scrollTop: $('.lilac-subscription-section').offset().top - 50
                }, 500);
            }
        });
        </script>
        <?php
    }
    
    /**
     * Add course selection field to WooCommerce product
     */
    public function add_course_field() {
        global $post;
        
        // Get all LearnDash courses
        $courses = get_posts([
            'post_type' => 'sfwd-courses',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);
        
        $course_options = ['' => __('Select a course', 'lilac-learning-manager')];
        foreach ($courses as $course) {
            $course_options[$course->ID] = $course->post_title;
        }
        
        // Get current value
        $course_id = get_post_meta($post->ID, '_lilac_course_id', true);
        
        // Output select field
        woocommerce_wp_select([
            'id' => '_lilac_course_id',
            'label' => __('LearnDash Course', 'lilac-learning-manager'),
            'description' => __('Select the LearnDash course associated with this product.', 'lilac-learning-manager'),
            'desc_tip' => true,
            'options' => $course_options,
            'value' => $course_id
        ]);
        
        // Add manual activation checkbox
        woocommerce_wp_checkbox([
            'id' => '_lilac_manual_activation',
            'label' => __('Require Manual Activation', 'lilac-learning-manager'),
            'description' => __('If checked, the user will need to manually activate their subscription after purchase.', 'lilac-learning-manager'),
            'desc_tip' => true,
            'value' => get_post_meta($post->ID, '_lilac_manual_activation', true) === 'yes' ? 'yes' : 'no'
        ]);
    }
    
    /**
     * Save course field value
     * 
     * @param int $post_id Product ID
     */
    public function save_course_field($post_id) {
        // Save course ID
        $course_id = isset($_POST['_lilac_course_id']) ? absint($_POST['_lilac_course_id']) : '';
        update_post_meta($post_id, '_lilac_course_id', $course_id);
        
        // Save manual activation setting
        $manual_activation = isset($_POST['_lilac_manual_activation']) ? 'yes' : 'no';
        update_post_meta($post_id, '_lilac_manual_activation', $manual_activation);
        
        // If course ID and manual activation are set, update course meta
        if ($course_id && $manual_activation === 'yes') {
            $settings = $this->course_meta->get_course_subscription_settings($course_id);
            $settings['requires_manual_activation'] = true;
            $this->course_meta->update_course_subscription_settings($course_id, $settings);
        }
    }
    
    /**
     * Add course info to order item meta
     * 
     * @param \WC_Order_Item_Product $item Order item
     * @param string $cart_item_key Cart item key
     * @param array $values Cart item values
     * @param \WC_Order $order Order object
     */
    public function add_course_to_order_item($item, $cart_item_key, $values, $order) {
        $product_id = $item->get_product_id();
        $course_id = get_post_meta($product_id, '_lilac_course_id', true);
        
        if ($course_id) {
            $item->add_meta_data('_lilac_course_id', $course_id);
            
            // Check if manual activation is required
            $manual_activation = get_post_meta($product_id, '_lilac_manual_activation', true);
            if ($manual_activation === 'yes') {
                $item->add_meta_data('_lilac_manual_activation', 'yes');
            }
        }
    }
}
