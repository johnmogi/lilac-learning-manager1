# Lilac Learning Manager

A powerful LearnDash extension that adds subscription management and advanced course access control to your WordPress learning management system.

## 🚀 Features

### Core Features
- Course management with advanced organization
- Program taxonomy for better course categorization
- User management and access control
- WooCommerce integration for course sales

### Subscription System (New!)
- **Flexible Subscription Durations**: Offer 2 weeks, 1 month, or 1 year access
- **Manual Activation**: Let users activate their subscription when they're ready
- **Course-Specific Settings**: Configure subscription options per course
- **Renewable End Dates**: Set course-specific end dates for all subscriptions
- **WooCommerce Integration**: Automatically create subscriptions from purchases
- **User Management**: Track subscription status and expiration

## 📦 Installation

1. Download the latest version from the WordPress plugin repository
2. Upload the `lilac-learning-manager` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Ensure LearnDash and WooCommerce are installed and activated

## ⚙️ Configuration

### Subscription Setup
1. Go to `Lilac Learning Manager > Subscriptions` in your WordPress admin
2. Configure global subscription settings
3. Set default subscription duration
4. Enable/disable manual activation

### Per-Course Settings
1. Edit any LearnDash course
2. Find the "Subscription Settings" meta box
3. Configure course-specific subscription options
4. Set renewable end dates if needed

## 🛠 Usage

### Shortcodes

#### Subscription Button
Display an activation button on any page or course:
```
[lilac_subscription_button course_id="123" label="Start Learning Now"]
```

#### Subscription Status
Show the user's subscription status:
```
[lilac_subscription_status course_id="123"]
```

## 🏗 File Structure

```
lilac-learning-manager/
├── includes/
│   └── Subscriptions/
│       ├── class-subscription-manager.php     # Core subscription logic
│       ├── class-subscription-admin.php      # Admin interface
│       ├── class-subscription-ajax.php       # AJAX handlers
│       └── class-subscription-shortcodes.php # Frontend shortcodes
├── assets/
│   ├── css/
│   │   └── subscription.css     # Subscription styles
│   └── js/
│       └── subscription.js      # Frontend functionality
├── lilac-learning-manager.php     # Main plugin file
└── README.md                      # This file
```

## 🔌 Integration

### WooCommerce
1. Create a WooCommerce product for your course
2. Add the course ID to the product's `_related_course` custom field
3. The subscription will be created automatically upon purchase

### LearnDash
Works seamlessly with LearnDash courses. No additional configuration needed.

## 🛠 Development

### Hooks and Filters

#### Actions
- `lilac_subscription_created` - When a new subscription is created
- `lilac_subscription_activated` - When a subscription is activated
- `lilac_subscription_expired` - When a subscription expires

#### Filters
- `lilac_subscription_durations` - Modify available subscription durations

### Extending
You can extend the subscription system using WordPress hooks or by extending the core classes.

## 📝 License

GPL-2.0+ © Your Name

## 📬 Support

For support, please open an issue on GitHub or contact support@yourwebsite.com.
