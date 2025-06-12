# LearnDash Topic Sidebar Documentation

## Overview

The LearnDash Topic Sidebar is a feature of the Lilac Learning Manager plugin that allows you to display a list of topics from a LearnDash course in a sidebar or widget area. The topics can be filtered by category and styled to match your site's design.

## Shortcode Usage

The main shortcode for displaying topics is:

```
[llm_early_topics]
```

### Shortcode Parameters

| Parameter    | Default | Description |
|--------------|---------|-------------|
| `category_id` | 0       | Filter topics by a specific category ID |
| `category`    | ''      | Filter topics by category name or slug |
| `acf_field`   | ''      | Use an ACF field value as the category ID or name |
| `debug`       | false   | Show debug information (true/false) |
| `show_lesson` | true    | Show lesson names (true/false) - currently hidden by CSS |
| `show_count`  | true    | Show topic counts (true/false) - currently hidden by CSS |

### Examples

**Basic usage** (shows all topics):
```
[llm_early_topics]
```

**Filter by category ID**:
```
[llm_early_topics category_id="42"]
```

**Filter by category name**:
```
[llm_early_topics category="Theory"]
```

**Using an ACF field for category selection**:
```
[llm_early_topics acf_field="topic_category"]
```

**Enable debug mode**:
```
[llm_early_topics debug="true"]
```

## ACF Integration

The shortcode supports using Advanced Custom Fields to dynamically select which category to display. This is useful when you want to allow site administrators to choose a category without editing the shortcode.

### Setting Up ACF Integration

1. Create an ACF field for your course or page (can be a Number, Text, or Select field)
2. If using a Select field, set the Return Value to either the category ID (number) or name (text)
3. Use the field name in the shortcode:
   ```
   [llm_early_topics acf_field="your_field_name"]
   ```

The shortcode will automatically detect if the field value is:
- A number (used as category ID)
- A string (used as category name)
- An array (first value is used)

## Technical Details

### Files and Structure

- `early-shortcode.php`: Main shortcode implementation
- `assets/css/llm-early-topics.css`: Styling for the topic list

### How It Works

1. The shortcode is registered very early in the WordPress lifecycle
2. When executed, it:
   - Detects the current course context
   - Retrieves all lessons and topics for the course
   - Gets topic categories and associates topics with categories
   - Filters topics by category if specified
   - Renders the topics as a clean list with styling

### Debugging

If you encounter issues, enable debug mode:
```
[llm_early_topics debug="true"]
```

This will display:
- Number of registered shortcodes
- Current post information
- Course ID
- Number of lessons and topics found
- Category information
- ACF field values (if used)

## Styling

The topics are styled as green buttons with centered text, matching the design requirements. You can customize the appearance by modifying the CSS file:

`includes/course-sidebar/assets/css/llm-early-topics.css`

The current styling includes:
- Mint green background (#4ce0b3)
- Centered text
- Proper spacing between buttons
- RTL support for Hebrew text
- Hover effects

## Troubleshooting

### Common Issues

1. **Shortcode not displaying**: Ensure LearnDash is active and you're on a course-related page.

2. **No topics showing**: Check if the course has topics and they're properly categorized.

3. **Category filtering not working**: Verify the category ID or name is correct. Enable debug mode to see available categories.

4. **ACF field not working**: Make sure ACF is active and the field name is correct. Check that the field has a value for the current post.

### Debug Logs

The shortcode writes to the WordPress debug log. Check `wp-content/debug.log` for entries starting with "Early Topics Shortcode:".

## Functions and Hooks

### Main Functions

- `llm_early_topics_shortcode($atts)`: Main shortcode callback function
- `llm_early_topics_enqueue_styles()`: Enqueues the CSS styles

### Filters

No filters are currently implemented, but future versions may include:
- Filter for modifying the topic list
- Filter for customizing the HTML output
- Filter for additional shortcode attributes

## Future Enhancements

Potential future improvements:
- Support for multiple category filtering
- Accordion-style topic display
- Progress tracking integration
- Custom sorting options
- Template overrides for theme customization
