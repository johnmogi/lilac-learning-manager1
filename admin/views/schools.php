<?php
// Security check - exit if accessed directly
defined('ABSPATH') || exit;

// Get the current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'all';

// Define tabs
$tabs = [
    'all'       => __('All Schools', 'lilac-learning-manager'),
    'add_new'   => __('Add New', 'lilac-learning-manager'),
    'import'    => __('Import', 'lilac-learning-manager'),
    'export'    => __('Export', 'lilac-learning-manager'),
];
?>

<div class="wrap lilac-learning-manager-schools">
    <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <a href="<?php echo esc_url(admin_url('post-new.php?post_type=llm_school')); ?>" class="page-title-action">
        <?php esc_html_e('Add New', 'lilac-learning-manager'); ?>
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
                include_once 'schools/add-new.php';
                break;
                
            case 'import':
                include_once 'schools/import.php';
                break;
                
            case 'export':
                include_once 'schools/export.php';
                break;
                
            case 'all':
            default:
                include_once 'schools/list.php';
                break;
        }
        ?>
    </div>
</div>
