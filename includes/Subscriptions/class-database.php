<?php
namespace LilacLearningManager\Subscriptions;

/**
 * Handles database setup and migrations for subscriptions
 */
class Database {
    /**
     * Initialize database
     */
    public function __construct() {
        // No hooks needed, this is called directly during activation
    }
    
    /**
     * Create subscription tables
     * 
     * @return bool Success
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Main subscriptions table
        $table_name = $wpdb->prefix . 'lilac_subscriptions';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            course_id bigint(20) unsigned NOT NULL,
            order_id bigint(20) unsigned DEFAULT NULL,
            subscription_type varchar(50) NOT NULL DEFAULT 'time_limited',
            option_id varchar(50) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            started_at datetime DEFAULT NULL,
            expires_at datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY user_course (user_id, course_id),
            KEY status (status),
            KEY expires_at (expires_at)
        ) $charset_collate;";
        
        // Subscription meta table
        $meta_table_name = $wpdb->prefix . 'lilac_subscription_meta';
        
        $sql_meta = "CREATE TABLE $meta_table_name (
            meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            subscription_id bigint(20) unsigned NOT NULL,
            meta_key varchar(255) NOT NULL,
            meta_value longtext,
            PRIMARY KEY (meta_id),
            KEY subscription_id (subscription_id),
            KEY meta_key (meta_key(191))
        ) $charset_collate;";
        
        // Execute SQL
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $result = dbDelta($sql);
        $result_meta = dbDelta($sql_meta);
        
        // Check if tables were created
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return false;
        }
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$meta_table_name'") !== $meta_table_name) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Migrate existing subscription data from post meta
     * 
     * @return int Number of migrated subscriptions
     */
    public function migrate_legacy_data() {
        global $wpdb;
        
        $migrated = 0;
        $table_name = $wpdb->prefix . 'lilac_subscriptions';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return 0;
        }
        
        // Get all orders with subscription meta
        $orders = $wpdb->get_results(
            "SELECT post_id FROM $wpdb->postmeta 
            WHERE meta_key LIKE '_lilac_subscribed_%' 
            GROUP BY post_id",
            ARRAY_A
        );
        
        if (!$orders) {
            return 0;
        }
        
        foreach ($orders as $order_data) {
            $order_id = $order_data['post_id'];
            $order = wc_get_order($order_id);
            
            if (!$order) {
                continue;
            }
            
            $user_id = $order->get_user_id();
            
            if (!$user_id) {
                continue;
            }
            
            // Get all subscription meta for this order
            $subscription_meta = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT meta_key, meta_value FROM $wpdb->postmeta 
                    WHERE post_id = %d AND meta_key LIKE '_lilac_subscribed_%'",
                    $order_id
                ),
                ARRAY_A
            );
            
            foreach ($subscription_meta as $meta) {
                // Extract course ID from meta key
                preg_match('/_lilac_subscribed_(\d+)/', $meta['meta_key'], $matches);
                
                if (empty($matches[1])) {
                    continue;
                }
                
                $course_id = $matches[1];
                $is_subscribed = $meta['meta_value'] === 'yes';
                
                if (!$is_subscribed) {
                    continue;
                }
                
                // Get expiry date
                $expires_at = get_post_meta($order_id, "_lilac_access_expires_{$course_id}", true);
                
                // Check if subscription already exists
                $existing = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT id FROM $table_name 
                        WHERE user_id = %d AND course_id = %d",
                        $user_id,
                        $course_id
                    )
                );
                
                if ($existing) {
                    continue;
                }
                
                // Insert new subscription
                $now = current_time('mysql');
                $status = $expires_at && strtotime($expires_at) < strtotime($now) ? 'expired' : 'active';
                
                $wpdb->insert(
                    $table_name,
                    [
                        'user_id' => $user_id,
                        'course_id' => $course_id,
                        'order_id' => $order_id,
                        'subscription_type' => 'time_limited',
                        'option_id' => '1_month', // Default to 1 month for legacy data
                        'status' => $status,
                        'started_at' => $now,
                        'expires_at' => $expires_at ?: null,
                        'created_at' => $now,
                        'updated_at' => $now
                    ],
                    ['%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
                );
                
                if ($wpdb->insert_id) {
                    $migrated++;
                }
            }
        }
        
        return $migrated;
    }
    
    /**
     * Add subscription meta
     * 
     * @param int $subscription_id Subscription ID
     * @param string $meta_key Meta key
     * @param mixed $meta_value Meta value
     * @return int|false Meta ID or false on failure
     */
    public function add_subscription_meta($subscription_id, $meta_key, $meta_value) {
        global $wpdb;
        
        $meta_table = $wpdb->prefix . 'lilac_subscription_meta';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$meta_table'") !== $meta_table) {
            return false;
        }
        
        $meta_value = maybe_serialize($meta_value);
        
        $result = $wpdb->insert(
            $meta_table,
            [
                'subscription_id' => $subscription_id,
                'meta_key' => $meta_key,
                'meta_value' => $meta_value
            ],
            ['%d', '%s', '%s']
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Update subscription meta
     * 
     * @param int $subscription_id Subscription ID
     * @param string $meta_key Meta key
     * @param mixed $meta_value Meta value
     * @return bool Success
     */
    public function update_subscription_meta($subscription_id, $meta_key, $meta_value) {
        global $wpdb;
        
        $meta_table = $wpdb->prefix . 'lilac_subscription_meta';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$meta_table'") !== $meta_table) {
            return false;
        }
        
        $meta_value = maybe_serialize($meta_value);
        
        // Check if meta exists
        $meta_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT meta_id FROM $meta_table 
                WHERE subscription_id = %d AND meta_key = %s",
                $subscription_id,
                $meta_key
            )
        );
        
        if ($meta_id) {
            // Update existing meta
            $result = $wpdb->update(
                $meta_table,
                ['meta_value' => $meta_value],
                [
                    'subscription_id' => $subscription_id,
                    'meta_key' => $meta_key
                ],
                ['%s'],
                ['%d', '%s']
            );
        } else {
            // Add new meta
            $result = $this->add_subscription_meta($subscription_id, $meta_key, $meta_value);
        }
        
        return $result !== false;
    }
    
    /**
     * Get subscription meta
     * 
     * @param int $subscription_id Subscription ID
     * @param string $meta_key Meta key
     * @param bool $single Whether to return a single value
     * @return mixed Meta value(s)
     */
    public function get_subscription_meta($subscription_id, $meta_key = '', $single = true) {
        global $wpdb;
        
        $meta_table = $wpdb->prefix . 'lilac_subscription_meta';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$meta_table'") !== $meta_table) {
            return $single ? '' : [];
        }
        
        if (!empty($meta_key)) {
            // Get specific meta
            $query = $wpdb->prepare(
                "SELECT meta_value FROM $meta_table 
                WHERE subscription_id = %d AND meta_key = %s",
                $subscription_id,
                $meta_key
            );
            
            $result = $single ? $wpdb->get_var($query) : $wpdb->get_col($query);
        } else {
            // Get all meta
            $query = $wpdb->prepare(
                "SELECT meta_key, meta_value FROM $meta_table 
                WHERE subscription_id = %d",
                $subscription_id
            );
            
            $results = $wpdb->get_results($query, ARRAY_A);
            
            if (!$results) {
                return $single ? '' : [];
            }
            
            $result = [];
            foreach ($results as $row) {
                $result[$row['meta_key']] = $row['meta_value'];
            }
        }
        
        if ($single && !empty($result)) {
            return maybe_unserialize($result);
        } elseif (!$single && is_array($result)) {
            return array_map('maybe_unserialize', $result);
        }
        
        return $single ? '' : [];
    }
}
