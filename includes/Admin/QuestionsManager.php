<?php
/**
 * Handles the Questions management in admin
 *
 * @package LilacLearningManager\Admin
 */

namespace LilacLearningManager\Admin;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

class QuestionsManager {
    /**
     * Initialize the class
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        add_submenu_page(
            'lilac-learning-manager',
            'שאלות',
            'שאלות',
            'manage_options',
            'llm-questions',
            array($this, 'render_questions_page')
        );
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts($hook) {
        if ('lilac-learning-manager_page_llm-questions' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'llm-questions-admin',
            plugin_dir_url(dirname(__DIR__)) . 'assets/css/questions-admin.css',
            array(),
            LILAC_LEARNING_MANAGER_VERSION
        );

        wp_enqueue_script(
            'llm-questions-admin',
            plugin_dir_url(dirname(__DIR__)) . 'assets/js/questions-admin.js',
            array('jquery'),
            LILAC_LEARNING_MANAGER_VERSION,
            true
        );

        wp_localize_script('llm-questions-admin', 'llmQuestions', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('llm_questions_nonce'),
            'i18n' => array(
                'saving' => 'שומר...',
                'saved' => 'נשמר',
                'error' => 'שגיאה בשמירה',
                'confirm_delete' => 'האם אתה בטוח שברצונך למחוק את השאלה הנבחרת? פעולה זו אינה ניתנת לביטול.'
            )
        ));
    }

    /**
     * Render the questions management page
     */
    public function render_questions_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Include the template
        include_once plugin_dir_path(dirname(__DIR__)) . 'admin/views/questions/manage.php';
    }
}

// Initialize the class
new QuestionsManager();
