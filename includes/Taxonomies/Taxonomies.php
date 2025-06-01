<?php
namespace LilacLearningManager\Taxonomies;

/**
 * Register custom taxonomies for the plugin.
 */
class Taxonomies {
    /**
     * Initialize the class and register taxonomies.
     */
    public function __construct() {
        add_action('init', [$this, 'register_taxonomies'], 0);
    }

    /**
     * Register custom taxonomies.
     */
    public function register_taxonomies() {
        // Register Topic Category
        $this->register_topic_category_taxonomy();
        
        // Register School Type
        $this->register_school_type_taxonomy();
    }

    /**
     * Register Topic Category taxonomy.
     */
    private function register_topic_category_taxonomy() {
        $labels = [
            'name'              => _x('Topic Categories', 'taxonomy general name', 'lilac-learning-manager'),
            'singular_name'     => _x('Topic Category', 'taxonomy singular name', 'lilac-learning-manager'),
            'search_items'      => __('Search Topic Categories', 'lilac-learning-manager'),
            'all_items'         => __('All Topic Categories', 'lilac-learning-manager'),
            'parent_item'       => __('Parent Topic Category', 'lilac-learning-manager'),
            'parent_item_colon' => __('Parent Topic Category:', 'lilac-learning-manager'),
            'edit_item'         => __('Edit Topic Category', 'lilac-learning-manager'),
            'update_item'       => __('Update Topic Category', 'lilac-learning-manager'),
            'add_new_item'      => __('Add New Topic Category', 'lilac-learning-manager'),
            'new_item_name'     => __('New Topic Category Name', 'lilac-learning-manager'),
            'menu_name'         => __('Categories', 'lilac-learning-manager'),
        ];

        $args = [
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'topic-category'],
            'show_in_rest'      => true,
        ];

        register_taxonomy('llm_topic_category', ['llm_topic'], $args);
    }

    /**
     * Register School Type taxonomy.
     */
    private function register_school_type_taxonomy() {
        $labels = [
            'name'              => _x('School Types', 'taxonomy general name', 'lilac-learning-manager'),
            'singular_name'     => _x('School Type', 'taxonomy singular name', 'lilac-learning-manager'),
            'search_items'      => __('Search School Types', 'lilac-learning-manager'),
            'all_items'         => __('All School Types', 'lilac-learning-manager'),
            'parent_item'       => __('Parent School Type', 'lilac-learning-manager'),
            'parent_item_colon' => __('Parent School Type:', 'lilac-learning-manager'),
            'edit_item'         => __('Edit School Type', 'lilac-learning-manager'),
            'update_item'       => __('Update School Type', 'lilac-learning-manager'),
            'add_new_item'      => __('Add New School Type', 'lilac-learning-manager'),
            'new_item_name'     => __('New School Type Name', 'lilac-learning-manager'),
            'menu_name'         => __('School Types', 'lilac-learning-manager'),
        ];

        $args = [
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'school-type'],
            'show_in_rest'      => true,
        ];

        register_taxonomy('llm_school_type', ['llm_school'], $args);
    }
}
