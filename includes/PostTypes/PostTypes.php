<?php
namespace LilacLearningManager\PostTypes;

/**
 * Register custom post types for the plugin.
 */
class PostTypes {
    /**
     * Initialize the class and register post types.
     */
    public function __construct() {
        add_action('init', [$this, 'register_post_types'], 0);
    }

    /**
     * Register custom post types.
     */
    public function register_post_types() {
        // Register School post type
        $this->register_school_post_type();
        
        // Register Topic post type
        $this->register_topic_post_type();
    }

    /**
     * Register School post type.
     */
    private function register_school_post_type() {
        $labels = [
            'name'               => _x('Schools', 'post type general name', 'lilac-learning-manager'),
            'singular_name'      => _x('School', 'post type singular name', 'lilac-learning-manager'),
            'menu_name'          => _x('Schools', 'admin menu', 'lilac-learning-manager'),
            'name_admin_bar'     => _x('School', 'add new on admin bar', 'lilac-learning-manager'),
            'add_new'            => _x('Add New', 'school', 'lilac-learning-manager'),
            'add_new_item'       => __('Add New School', 'lilac-learning-manager'),
            'new_item'           => __('New School', 'lilac-learning-manager'),
            'edit_item'          => __('Edit School', 'lilac-learning-manager'),
            'view_item'          => __('View School', 'lilac-learning-manager'),
            'all_items'          => __('All Schools', 'lilac-learning-manager'),
            'search_items'       => __('Search Schools', 'lilac-learning-manager'),
            'not_found'          => __('No schools found.', 'lilac-learning-manager'),
            'not_found_in_trash' => __('No schools found in Trash.', 'lilac-learning-manager')
        ];

        $args = [
            'labels'             => $labels,
            'description'        => __('School information pages', 'lilac-learning-manager'),
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => false, // We'll handle this in our admin menu
            'query_var'          => true,
            'rewrite'            => ['slug' => 'school'],
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 5,
            'supports'           => ['title', 'editor', 'thumbnail', 'excerpt'],
            'show_in_rest'       => true,
        ];

        register_post_type('llm_school', $args);
    }

    /**
     * Register Topic post type.
     */
    private function register_topic_post_type() {
        $labels = [
            'name'               => _x('Topics', 'post type general name', 'lilac-learning-manager'),
            'singular_name'      => _x('Topic', 'post type singular name', 'lilac-learning-manager'),
            'menu_name'          => _x('Topics', 'admin menu', 'lilac-learning-manager'),
            'name_admin_bar'     => _x('Topic', 'add new on admin bar', 'lilac-learning-manager'),
            'add_new'            => _x('Add New', 'topic', 'lilac-learning-manager'),
            'add_new_item'       => __('Add New Topic', 'lilac-learning-manager'),
            'new_item'           => __('New Topic', 'lilac-learning-manager'),
            'edit_item'          => __('Edit Topic', 'lilac-learning-manager'),
            'view_item'          => __('View Topic', 'lilac-learning-manager'),
            'all_items'          => __('All Topics', 'lilac-learning-manager'),
            'search_items'       => __('Search Topics', 'lilac-learning-manager'),
            'not_found'          => __('No topics found.', 'lilac-learning-manager'),
            'not_found_in_trash' => __('No topics found in Trash.', 'lilac-learning-manager')
        ];

        $args = [
            'labels'             => $labels,
            'description'        => __('Learning topics and materials', 'lilac-learning-manager'),
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => false, // We'll handle this in our admin menu
            'query_var'          => true,
            'rewrite'            => ['slug' => 'topic'],
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 6,
            'supports'           => ['title', 'editor', 'thumbnail', 'excerpt', 'comments'],
            'show_in_rest'       => true,
            'taxonomies'         => ['llm_topic_category'],
        ];

        register_post_type('llm_topic', $args);
    }
}
