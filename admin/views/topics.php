<?php
// Security check - exit if accessed directly
defined('ABSPATH') || exit;

// Get the current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'all';

// Define tabs
$tabs = [
    'all'       => __('All Topics', 'lilac-learning-manager'),
    'add_new'   => __('Add New', 'lilac-learning-manager'),
    'categories' => __('Categories', 'lilac-learning-manager'),
    'import'    => __('Import', 'lilac-learning-manager'),
    'export'    => __('Export', 'lilac-learning-manager'),
];
?>

<div class="wrap lilac-learning-manager-topics">
    <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <a href="<?php echo esc_url(admin_url('post-new.php?post_type=llm_topic')); ?>" class="page-title-action">
        <?php esc_html_e('Add New', 'lilac-learning-manager'); ?>
    </a>
    
    <a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=llm_topic_category&post_type=llm_topic')); ?>" class="page-title-action">
        <?php esc_html_e('Manage Categories', 'lilac-learning-manager'); ?>
    </a>
    
    <hr class="wp-header-end">
    
    <nav class="nav-tab-wrapper">
        <?php foreach ($tabs as $tab => $label) : ?>
            <a href="<?php echo esc_url(add_query_arg('tab', $tab)); ?>" 
               class="nav-tab <?php echo $current_tab === $tab ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html($label); ?>
            </a>
        <?php endforeach; ?>
    </nav>
    
    <div class="lilac-tab-content">
        <?php
        switch ($current_tab) {
            case 'add_new':
                include_once 'topics/add-new.php';
                break;
                
            case 'categories':
                include_once 'topics/categories.php';
                break;
                
            case 'import':
                include_once 'topics/import.php';
                break;
                
            case 'export':
                include_once 'topics/export.php';
                break;
                
            case 'all':
            default:
                include_once 'topics/list.php';
                break;
        }
        ?>
    </div>
</div>
