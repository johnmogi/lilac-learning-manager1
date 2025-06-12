<?php
namespace LilacLearningManager\ThankYou;

/**
 * Handles Thank You page settings and functionality
 */
class Thank_You_Settings {
    /**
     * Settings page slug
     */
    const PAGE_SLUG = 'lilac-thank-you-settings';

    /**
     * Option name for storing course IDs
     */
    const OPTION_NAME = 'lilac_thank_you_courses';

    /**
     * Initialize the class
     */
    public function __construct() {
        try {
            // Only initialize in admin
            if (!is_admin()) {
                return;
            }
            
            // Verify required plugins are active
            if (!class_exists('WooCommerce')) {
                throw new Exception(__('WooCommerce is required for the Thank You Alerts feature', 'lilac-learning-manager'));
            }
            
            if (!class_exists('SFWD_LMS')) {
                throw new Exception(__('LearnDash is required for the Thank You Alerts feature', 'lilac-learning-manager'));
            }
            
            // Register settings
            add_action('admin_init', [$this, 'register_settings']);
            
            // Add settings page to menu
            add_action('admin_menu', [$this, 'add_settings_page'], 20);
            
            // Add WooCommerce thank you page hook
            add_action('woocommerce_thankyou', [$this, 'maybe_show_thank_you_alert'], 10, 1);
            
        } catch (Exception $e) {
            // Log error
            error_log('Error initializing Thank You Settings: ' . $e->getMessage());
            
            // Show admin notice
            add_action('admin_notices', function() use ($e) {
                ?>
                <div class="notice notice-error">
                    <p><strong><?php _e('Lilac Learning Manager - Thank You Alerts:', 'lilac-learning-manager'); ?></strong> 
                    <?php echo esc_html($e->getMessage()); ?></p>
                </div>
                <?php
            });
        }
    }
    
    /**
     * Load admin styles
     */
    public function load_admin_styles() {
        add_action('admin_enqueue_scripts', function() {
            wp_enqueue_style(
                'lilac-thank-you-admin',
                LILAC_LEARNING_MANAGER_URL . 'assets/css/thank-you-admin.css',
                [],
                LILAC_LEARNING_MANAGER_VERSION
            );
        });
    }

    /**
     * Add settings page to admin menu
     */
    public function add_settings_page() {
        try {
            // First, verify the parent menu exists
            if (!menu_page_url('lilac-learning-manager', false)) {
                throw new Exception('Parent menu "lilac-learning-manager" does not exist');
            }
            
            $hook = add_submenu_page(
                'lilac-learning-manager', // Parent menu slug
                __('Thank You Page Settings', 'lilac-learning-manager'), // Page title
                __('Thank You Alerts', 'lilac-learning-manager'), // Menu title
                'manage_options', // Capability
                self::PAGE_SLUG, // Menu slug
                [$this, 'render_settings_page'] // Callback function
            );
            
            if (!$hook) {
                throw new Exception('WordPress failed to add the submenu page');
            }
            
            // Load our admin styles
            add_action('load-' . $hook, [$this, 'load_admin_styles']);
            
            return $hook;
            
        } catch (Exception $e) {
            $error_message = sprintf(
                'Error adding Thank You Alerts menu: %s [%s]',
                $e->getMessage(),
                $e->getCode()
            );
            
            error_log($error_message);
            
            // Add admin notice
            add_action('admin_notices', function() use ($error_message) {
                ?>
                <div class="notice notice-error">
                    <p><?php 
                        printf(
                            __('Lilac Learning Manager: %s', 'lilac-learning-manager'),
                            esc_html($error_message)
                        );
                    ?></p>
                </div>
                <?php
            });
            
            return false;
        }
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting(
            'lilac_thank_you_group',
            self::OPTION_NAME,
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitize_course_selection'],
                'default' => []
            ]
        );
    }

    /**
     * Sanitize course selection
     */
    public function sanitize_course_selection($input) {
        if (!is_array($input)) {
            return [];
        }
        return array_map('absint', $input);
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Get all LearnDash courses
        $courses = get_posts([
            'post_type' => 'sfwd-courses',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'post_status' => 'publish'
        ]);

        // Get saved course IDs
        $selected_courses = get_option(self::OPTION_NAME, []);
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('lilac_thank_you_group');
                do_settings_sections('lilac_thank_you_group');
                ?>
                
                <div class="lilac-thank-you-settings">
                    <h2><?php esc_html_e('Course Selection', 'lilac-learning-manager'); ?></h2>
                    <p class="description">
                        <?php esc_html_e('Select which courses should trigger the Thank You page alert when purchased.', 'lilac-learning-manager'); ?>
                    </p>
                    
                    <div class="lilac-course-selection">
                        <?php if (!empty($courses)) : ?>
                            <ul class="lilac-course-list">
                                <?php foreach ($courses as $course) : ?>
                                    <li>
                                        <label>
                                            <input type="checkbox" 
                                                   name="<?php echo esc_attr(self::OPTION_NAME); ?>[]" 
                                                   value="<?php echo esc_attr($course->ID); ?>"
                                                   <?php checked(in_array($course->ID, $selected_courses, true)); ?>>
                                            <?php echo esc_html($course->post_title); ?>
                                            <span class="course-id">(ID: <?php echo esc_html($course->ID); ?>)</span>
                                        </label>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else : ?>
                            <p><?php esc_html_e('No courses found.', 'lilac-learning-manager'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php submit_button(__('Save Settings', 'lilac-learning-manager')); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, self::PAGE_SLUG) === false) {
            return;
        }

        wp_enqueue_style(
            'lilac-thank-you-admin',
            plugins_url('assets/css/thank-you-admin.css', LILAC_LEARNING_MANAGER_FILE),
            [],
            LILAC_LEARNING_MANAGER_VERSION
        );
    }

    /**
     * Show thank you alert if conditions are met
     */
    public function maybe_show_thank_you_alert($order_id) {
        // Get the order
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Get selected course IDs from settings
        $selected_course_ids = get_option(self::OPTION_NAME, []);
        if (empty($selected_course_ids)) {
            return;
        }

        // Check if any purchased product is linked to a selected course
        $has_matching_course = false;
        
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $course_ids = $this->get_courses_for_product($product_id);
            
            // Check if any of the product's courses are in our selected courses
            if (!empty(array_intersect($course_ids, $selected_course_ids))) {
                $has_matching_course = true;
                break;
            }
        }

        // Show alert if we found a matching course
        if ($has_matching_course) {
            // Remove any existing notices to avoid duplicates
            wc_clear_notices();
            
            // Add our custom success notice
            wc_add_notice(
                $this->get_thank_you_message(),
                'success',
                ['lilac-thank-you-alert' => true]
            );
        }
    }

    /**
     * Get all course IDs for a WooCommerce product
     */
    private function get_courses_for_product($product_id) {
        $course_ids = [];
        
        // Check LearnDash WooCommerce integration
        $ld_course_id = get_post_meta($product_id, '_related_course', true);
        if ($ld_course_id) {
            $course_ids[] = $ld_course_id;
        }
        
        // Check for multiple courses (comma-separated)
        $ld_courses = get_post_meta($product_id, '_related_course_id', false);
        if (!empty($ld_courses)) {
            $course_ids = array_merge($course_ids, $ld_courses);
        }
        
        // Check custom meta field
        $custom_course_id = get_post_meta($product_id, '_lilac_related_course', true);
        if ($custom_course_id) {
            $course_ids[] = $custom_course_id;
        }
        
        // Filter and return unique course IDs
        return array_unique(array_filter(array_map('absint', $course_ids)));
    }

    /**
     * Get the thank you message
     */
    private function get_thank_you_message() {
        $message = apply_filters(
            'lilac_thank_you_alert_message',
            __('Thank you for your purchase! Your course access has been activated.', 'lilac-learning-manager')
        );

        return wp_kses_post($message);
    }
}

// Initialize the class
function lilac_init_thank_you_settings() {
    if (class_exists('WooCommerce') && class_exists('SFWD_LMS')) {
        new Thank_You_Settings();
    }
}
add_action('plugins_loaded', 'LilacLearningManager\\ThankYou\\lilac_init_thank_you_settings');
