<?php
/**
 * Questions management template
 *
 * @package LilacLearningManager\Admin\Views
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap llm-questions-wrap" dir="rtl">
    <h1 class="wp-heading-inline">ניהול שאלות</h1>
    <a href="<?php echo esc_url(admin_url('post-new.php?post_type=sfwd-question')); ?>" class="page-title-action">
        הוסף שאלה חדשה
    </a>
    <hr class="wp-header-end">

    <div class="llm-questions-filters">
        <form method="get">
            <input type="hidden" name="page" value="llm-questions">
            
            <div class="tablenav top">
                <div class="alignleft actions">
                    <?php // Program filter dropdown will go here ?>
                    <?php // Course filter dropdown will go here ?>
                    
                    <input type="submit" class="button" value="סנן">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=llm-questions')); ?>" class="button">נקה סינון</a>
                </div>
                <br class="clear">
            </div>
        </form>
    </div>

    <form id="llm-questions-form" method="post">
        <?php wp_nonce_field('bulk-questions'); ?>
        
        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <label for="bulk-action-selector-top" class="screen-reader-text">בחר פעולה קבוצתית</label>
                <select name="action" id="bulk-action-selector-top">
                    <option value="-1">פעולות קבוצתיות</option>
                    <option value="delete">מחק</option>
                    <option value="assign-program">הקצה לתוכנית</option>
                </select>
                <input type="submit" id="doaction" class="button action" value="החל">
            </div>
            <div class="tablenav-pages">
                <?php // Pagination will go here ?>
            </div>
            <br class="clear">
        </div>

        <table class="wp-list-table widefat fixed striped table-view-list">
            <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column">
                        <input id="cb-select-all-1" type="checkbox">
                    </td>
                    <th scope="col" class="manage-column column-title column-primary">
                        <span>שאלה</span>
                    </th>
                    <th scope="col" class="manage-column">רמז</th>
                    <th scope="col" class="manage-column">קורס</th>
                    <th scope="col" class="manage-column">תוכנית</th>
                    <th scope="col" class="manage-column">נוצרה</th>
                </tr>
            </thead>
            <tbody id="the-list">
                <?php // Questions will be listed here ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px;">
                        טוען שאלות...
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input id="cb-select-all-2" type="checkbox">
                    </td>
                    <th scope="col" class="manage-column column-title column-primary">
                        <span>שאלה</span>
                    </th>
                    <th scope="col" class="manage-column">רמז</th>
                    <th scope="col" class="manage-column">קורס</th>
                    <th scope="col" class="manage-column">תוכנית</th>
                    <th scope="col" class="manage-column">נוצרה</th>
                </tr>
            </tfoot>
        </table>

        <div class="tablenav bottom">
            <div class="alignleft actions bulkactions">
                <label for="bulk-action-selector-bottom" class="screen-reader-text">בחר פעולה קבוצתית</label>
                <select name="action2" id="bulk-action-selector-bottom">
                    <option value="-1">פעולות קבוצתיות</option>
                    <option value="delete">מחק</option>
                    <option value="assign-program">הקצה לתוכנית</option>
                </select>
                <input type="submit" id="doaction2" class="button action" value="החל">
            </div>
            <div class="tablenav-pages">
                <?php // Pagination will go here ?>
            </div>
            <br class="clear">
        </div>
    </form>
</div>

<!-- Inline edit form (hidden by default) -->
<div id="llm-inline-edit" style="display: none;">
    <div class="inline-edit-wrapper">
        <h2>ערוך שאלה</h2>
        <div class="inline-edit-fields">
            <div class="field">
                <label for="llm-question-title">שאלה</label>
                <input type="text" id="llm-question-title" class="widefat">
            </div>
            <div class="field">
                <label for="llm-question-hint">רמז</label>
                <textarea id="llm-question-hint" class="widefat" rows="3"></textarea>
            </div>
            <div class="field">
                <label for="llm-question-program">תוכנית</label>
                <select id="llm-question-program" class="widefat">
                    <?php // Programs will be loaded here ?>
                </select>
            </div>
            <div class="field">
                <label for="llm-question-course">קורס</label>
                <select id="llm-question-course" class="widefat">
                    <?php // Courses will be loaded here ?>
                </select>
            </div>
            <div class="submit-buttons">
                <button type="button" class="button button-primary save">שמור שינויים</button>
                <button type="button" class="button cancel">ביטול</button>
                <span class="spinner"></span>
            </div>
        </div>
    </div>
</div>
