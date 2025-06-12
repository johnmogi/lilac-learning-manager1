# Subscription Management for Lilac Learning Manager

This extension adds subscription-based access control to your LearnDash courses, allowing you to sell course access with flexible subscription periods.

## Features

- **Flexible Subscription Durations**: Offer multiple subscription periods (2 weeks, 1 month, 1 year)
- **Manual Activation**: Allow users to activate their subscription when they're ready
- **Course-Specific Settings**: Configure subscription behavior per course
- **WooCommerce Integration**: Seamlessly works with WooCommerce for course purchases
- **Renewable End Dates**: Set course-specific end dates for all subscriptions
- **Detailed User Management**: Track subscription status and expiration dates

## Installation

1. Upload the `lilac-learning-manager` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Ensure LearnDash and WooCommerce are installed and activated

## Configuration

### Global Settings

1. Go to `Lilac Learning Manager > Subscriptions` in your WordPress admin
2. Configure the default subscription settings
3. Set up the default subscription duration
4. Enable/disable manual activation

### Per-Course Settings

1. Edit any LearnDash course
2. Find the "Subscription Settings" meta box
3. Configure subscription options specific to that course
4. Set a renewable end date if needed

## Shortcodes

### Subscription Button

Display a subscription activation button on any page or course:

```
[lilac_subscription_button course_id="123" label="Start Learning Now"]
```

**Parameters:**
- `course_id`: (int) The ID of the course (default: current post ID)
- `label`: (string) Button text (default: "Activate Subscription")
- `class`: (string) Additional CSS classes for the button

### Subscription Status

Display the user's subscription status for a course:

```
[lilac_subscription_status course_id="123"]
```

**Parameters:**
- `course_id`: (int) The ID of the course (default: current post ID)
- `show_title`: (yes/no) Whether to show the status title (default: yes)
- `show_expiry`: (yes/no) Whether to show the expiry date (default: yes)
- `show_days_remaining`: (yes/no) Whether to show days remaining (default: yes)

## Hooks and Filters

### Actions

- `lilac_subscription_created` - Fires when a new subscription is created
  - Params: `$user_id`, `$course_id`, `$subscription_data`

- `lilac_subscription_activated` - Fires when a subscription is activated
  - Params: `$user_id`, `$course_id`, `$subscription_data`

- `lilac_subscription_expired` - Fires when a subscription expires
  - Params: `$user_id`, `$course_id`, `$subscription_data`

### Filters

- `lilac_subscription_durations` - Modify available subscription durations
  - Params: `$durations` (array of duration options)
  - Example:
    ```php
    add_filter('lilac_subscription_durations', function($durations) {
        $durations['3_months'] = [
            'label' => '3 Months',
            'duration' => '+3 months',
            'days' => 90
        ];
        return $durations;
    });
    ```

## WooCommerce Integration

The plugin automatically detects course purchases made through WooCommerce. To set up:

1. Create a product in WooCommerce for your course
2. In the product settings, add the course ID to the `_related_course` custom field
3. When the order is completed, the subscription will be created automatically

## Styling

The plugin includes default styles that can be overridden in your theme. Use the following CSS classes:

- `.lilac-subscription-button` - Main subscription button
- `.lilac-message` - Status/error messages
- `.lilac-subscription-status` - Subscription status container
- `.lilac-duration-options` - Duration selection container

## Troubleshooting

### Subscription not activating
- Ensure the user has purchased the course
- Check that the order status is marked as completed
- Verify that the course ID is correctly linked to the WooCommerce product

### Access not granted
- Check the subscription status in the user's profile
- Verify that the subscription hasn't expired
- Ensure the course is published and visible

## Support

For support, please open an issue on the [GitHub repository](https://github.com/yourusername/lilac-learning-manager) or contact support@yourwebsite.com.
