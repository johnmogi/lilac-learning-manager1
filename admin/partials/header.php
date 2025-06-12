<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap lilac-admin-header">
    <div class="lilac-admin-header-content">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <div class="lilac-admin-actions">
            <?php do_action('lilac_admin_header_actions'); ?>
        </div>
    </div>
    <hr class="wp-header-end">
</div>
