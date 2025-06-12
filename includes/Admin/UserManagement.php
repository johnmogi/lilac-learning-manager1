<?php
namespace LilacLearningManager\Admin;

use LilacLearningManager\Users\UserRoles;

class UserManagement {
    /**
     * Initialize user management functionality
     */
    public static function init() {
        // Add user role column to users list
        add_filter('manage_users_columns', [__CLASS__, 'add_user_role_column']);
        add_filter('manage_users_custom_column', [__CLASS__, 'show_user_role_column_content'], 10, 3);
        
        // Add role selector to user profile
        add_action('show_user_profile', [__CLASS__, 'add_role_profile_fields']);
        add_action('edit_user_profile', [__CLASS__, 'add_role_profile_fields']);
        
        // Save role from profile
        add_action('personal_options_update', [__CLASS__, 'save_role_profile_fields']);
        add_action('edit_user_profile_update', [__CLASS__, 'save_role_profile_fields']);
    }
    
    /**
     * Add a role column to the users list table
     */
    public static function add_user_role_column($columns) {
        $columns['llm_role'] = __('User Role', 'lilac-learning-manager');
        return $columns;
    }
    
    /**
     * Show the user's role in the users list table
     */
    public static function show_user_role_column_content($output, $column_name, $user_id) {
        if ('llm_role' !== $column_name) {
            return $output;
        }
        
        $user = get_userdata($user_id);
        $roles = UserRoles::get_custom_roles();
        $user_roles = array_intersect($user->roles, array_keys($roles));
        
        $role_labels = [];
        foreach ($user_roles as $role) {
            if (isset($roles[$role])) {
                $role_labels[] = $roles[$role];
            }
        }
        
        return $role_labels ? esc_html(implode(', ', $role_labels)) : '—';
    }
    
    /**
     * Add role selector to user profile
     */
    public static function add_role_profile_fields($user) {
        if (!current_user_can('promote_users')) {
            return;
        }
        
        $user_roles = $user->roles;
        $roles = UserRoles::get_custom_roles();
        ?>
        <h3><?php _e('Lilac Learning Manager Role', 'lilac-learning-manager'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="llm_user_role"><?php _e('User Role', 'lilac-learning-manager'); ?></label></th>
                <td>
                    <select name="llm_user_role" id="llm_user_role" class="regular-text">
                        <option value=""><?php _e('— No Lilac Role —', 'lilac-learning-manager'); ?></option>
                        <?php foreach ($roles as $role => $label) : ?>
                            <option value="<?php echo esc_attr($role); ?>" <?php selected(in_array($role, $user_roles)); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php _e('Select the primary role for this user in the Lilac Learning Manager system.', 'lilac-learning-manager'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save role from user profile
     */
    public static function save_role_profile_fields($user_id) {
        if (!current_user_can('promote_users')) {
            return false;
        }
        
        if (!isset($_POST['llm_user_role'])) {
            return;
        }
        
        $new_role = sanitize_text_field($_POST['llm_user_role']);
        $roles = UserRoles::get_custom_roles();
        $user = get_userdata($user_id);
        
        if (!$user) {
            return;
        }
        
        // Remove all Lilac roles first
        foreach (array_keys($roles) as $role) {
            $user->remove_role($role);
        }
        
        // Add the new role if one was selected
        if (!empty($new_role) && array_key_exists($new_role, $roles)) {
            $user->add_role($new_role);
        }
    }
}
