<?php
namespace LilacLearningManager\Subscriptions;

/**
 * Manages different subscription types and their behavior
 */
class Subscription_Types {
    /**
     * Subscription type constants
     */
    const TYPE_TIME_LIMITED = 'time_limited';
    const TYPE_FIXED_DATE = 'fixed_date';
    const TYPE_HYBRID = 'hybrid';
    
    /**
     * @var array Registered subscription types
     */
    private $types = [];
    
    /**
     * Initialize subscription types
     */
    public function __construct() {
        $this->register_default_types();
    }
    
    /**
     * Register default subscription types
     */
    private function register_default_types() {
        // Time-limited subscription (e.g., 2 weeks, 1 month)
        $this->register_subscription_type(self::TYPE_TIME_LIMITED, [
            'label' => __('Time-Limited Access', 'lilac-learning-manager'),
            'description' => __('Access for a specific duration from activation date', 'lilac-learning-manager'),
            'calculate_expiration' => [$this, 'calculate_time_limited_expiration'],
            'durations' => [
                '2_weeks' => [
                    'label' => __('2 Weeks', 'lilac-learning-manager'),
                    'duration' => '+2 weeks',
                    'days' => 14
                ],
                '1_month' => [
                    'label' => __('1 Month', 'lilac-learning-manager'),
                    'duration' => '+1 month',
                    'days' => 30
                ]
            ]
        ]);
        
        // Fixed-date subscription (expires on June 30th)
        $this->register_subscription_type(self::TYPE_FIXED_DATE, [
            'label' => __('Annual Access', 'lilac-learning-manager'),
            'description' => __('Access until a specific date regardless of purchase date', 'lilac-learning-manager'),
            'calculate_expiration' => [$this, 'calculate_fixed_date_expiration'],
            'fixed_dates' => [
                'annual' => [
                    'label' => __('Annual (June 30)', 'lilac-learning-manager'),
                    'month' => 6, // June
                    'day' => 30
                ]
            ]
        ]);
        
        // Hybrid subscription (combination of time-limited and fixed-date)
        $this->register_subscription_type(self::TYPE_HYBRID, [
            'label' => __('Hybrid Access', 'lilac-learning-manager'),
            'description' => __('Access for a minimum duration or until fixed date, whichever is later', 'lilac-learning-manager'),
            'calculate_expiration' => [$this, 'calculate_hybrid_expiration'],
            'options' => [
                'min_2w_annual' => [
                    'label' => __('Min. 2 Weeks + Annual', 'lilac-learning-manager'),
                    'min_duration' => '+2 weeks',
                    'fixed_month' => 6,
                    'fixed_day' => 30
                ],
                'min_1m_annual' => [
                    'label' => __('Min. 1 Month + Annual', 'lilac-learning-manager'),
                    'min_duration' => '+1 month',
                    'fixed_month' => 6,
                    'fixed_day' => 30
                ]
            ]
        ]);
    }
    
    /**
     * Register a new subscription type
     * 
     * @param string $type_id Unique identifier for the subscription type
     * @param array $config Configuration for the subscription type
     * @return bool Success
     */
    public function register_subscription_type($type_id, $config) {
        if (empty($type_id) || !is_array($config)) {
            return false;
        }
        
        // Ensure required fields
        $required = ['label', 'description', 'calculate_expiration'];
        foreach ($required as $field) {
            if (!isset($config[$field])) {
                return false;
            }
        }
        
        $this->types[$type_id] = $config;
        return true;
    }
    
    /**
     * Get all registered subscription types
     * 
     * @return array Subscription types
     */
    public function get_subscription_types() {
        return $this->types;
    }
    
    /**
     * Get a specific subscription type
     * 
     * @param string $type_id Subscription type ID
     * @return array|null Subscription type config or null if not found
     */
    public function get_subscription_type($type_id) {
        return isset($this->types[$type_id]) ? $this->types[$type_id] : null;
    }
    
    /**
     * Calculate expiration date for time-limited subscription
     * 
     * @param array $params Parameters for calculation
     * @return string Expiration date in MySQL format
     */
    public function calculate_time_limited_expiration($params) {
        $duration = isset($params['duration']) ? $params['duration'] : '+30 days';
        $start_date = isset($params['start_date']) ? $params['start_date'] : current_time('mysql');
        
        return date('Y-m-d H:i:s', strtotime($duration, strtotime($start_date)));
    }
    
    /**
     * Calculate expiration date for fixed-date subscription
     * 
     * @param array $params Parameters for calculation
     * @return string Expiration date in MySQL format
     */
    public function calculate_fixed_date_expiration($params) {
        $month = isset($params['month']) ? $params['month'] : 6; // Default to June
        $day = isset($params['day']) ? $params['day'] : 30; // Default to 30th
        
        $current_year = date('Y');
        $current_month = date('n');
        
        // If we've already passed the expiration date this year, use next year
        if ($current_month > $month || ($current_month == $month && date('j') > $day)) {
            $year = $current_year + 1;
        } else {
            $year = $current_year;
        }
        
        return date('Y-m-d 23:59:59', strtotime("$year-$month-$day"));
    }
    
    /**
     * Calculate expiration date for hybrid subscription
     * 
     * @param array $params Parameters for calculation
     * @return string Expiration date in MySQL format
     */
    public function calculate_hybrid_expiration($params) {
        // Calculate minimum duration expiration
        $min_duration = isset($params['min_duration']) ? $params['min_duration'] : '+30 days';
        $start_date = isset($params['start_date']) ? $params['start_date'] : current_time('mysql');
        $min_expiry = date('Y-m-d H:i:s', strtotime($min_duration, strtotime($start_date)));
        
        // Calculate fixed date expiration
        $month = isset($params['fixed_month']) ? $params['fixed_month'] : 6;
        $day = isset($params['fixed_day']) ? $params['fixed_day'] : 30;
        
        $current_year = date('Y');
        $current_month = date('n');
        
        // If we've already passed the expiration date this year, use next year
        if ($current_month > $month || ($current_month == $month && date('j') > $day)) {
            $year = $current_year + 1;
        } else {
            $year = $current_year;
        }
        
        $fixed_expiry = date('Y-m-d 23:59:59', strtotime("$year-$month-$day"));
        
        // Return the later of the two dates
        return (strtotime($min_expiry) > strtotime($fixed_expiry)) ? $min_expiry : $fixed_expiry;
    }
    
    /**
     * Get available subscription options for a specific type
     * 
     * @param string $type_id Subscription type ID
     * @return array Options for the subscription type
     */
    public function get_type_options($type_id) {
        $type = $this->get_subscription_type($type_id);
        
        if (!$type) {
            return [];
        }
        
        switch ($type_id) {
            case self::TYPE_TIME_LIMITED:
                return isset($type['durations']) ? $type['durations'] : [];
                
            case self::TYPE_FIXED_DATE:
                return isset($type['fixed_dates']) ? $type['fixed_dates'] : [];
                
            case self::TYPE_HYBRID:
                return isset($type['options']) ? $type['options'] : [];
                
            default:
                return [];
        }
    }
    
    /**
     * Calculate expiration date based on subscription type and options
     * 
     * @param string $type_id Subscription type ID
     * @param string $option_id Option ID within the subscription type
     * @param array $params Additional parameters
     * @return string|false Expiration date in MySQL format or false on failure
     */
    public function calculate_expiration($type_id, $option_id, $params = []) {
        $type = $this->get_subscription_type($type_id);
        
        if (!$type || !isset($type['calculate_expiration']) || !is_callable($type['calculate_expiration'])) {
            return false;
        }
        
        $options = $this->get_type_options($type_id);
        
        if (empty($options) || !isset($options[$option_id])) {
            return false;
        }
        
        // Merge option settings with provided params
        $calculation_params = array_merge($options[$option_id], $params);
        
        return call_user_func($type['calculate_expiration'], $calculation_params);
    }
}
