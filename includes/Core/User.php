<?php
namespace LilacLearningManager\Core;

class User {
    /**
     * Get user role display name
     */
    public static function get_role_display_name($role) {
        $roles = Roles::get_roles();
        return $roles[$role] ?? '';
    }

    /**
     * Update user role
     */
    public static function update_role($user_id, $new_role) {
        if (!current_user_can('edit_user', $user_id)) {
            return new \WP_Error('forbidden', __('You do not have permission to edit this user.', 'lilac-learning-manager'));
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return new \WP_Error('invalid_user', __('Invalid user ID.', 'lilac-learning-manager'));
        }

        // Remove all Lilac roles first
        $roles = array_keys(Roles::get_roles());
        foreach ($roles as $role) {
            $user->remove_role($role);
        }

        // Add the new role if it's valid
        if (!empty($new_role) && array_key_exists($new_role, Roles::get_roles())) {
            $user->add_role($new_role);
            return true;
        }

        return false;
    }

    /**
     * Get users by role
     */
    public static function get_users_by_role($role, $args = []) {
        if (!in_array($role, array_keys(Roles::get_roles()))) {
            return [];
        }

        $defaults = [
            'role' => $role,
            'number' => -1,
            'orderby' => 'display_name',
            'order' => 'ASC',
        ];

        $args = wp_parse_args($args, $defaults);
        return get_users($args);
    }

    /**
     * Add role column to users list
     */
    public static function add_role_column($columns) {
        $columns['llm_role'] = __('User Role', 'lilac-learning-manager');
        return $columns;
    }

    /**
     * Display role in users list
     */
    public static function show_role_column($output, $column_name, $user_id) {
        if ('llm_role' !== $column_name) {
            return $output;
        }

        $user = get_userdata($user_id);
        $roles = Roles::get_roles();
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
        $roles = Roles::get_roles();
        $lilac_roles = array_intersect_key($roles, array_flip($user_roles));
        $current_role = !empty($lilac_roles) ? array_key_first($lilac_roles) : '';
        ?>
        <h3><?php _e('Lilac Learning Manager', 'lilac-learning-manager'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="llm_user_role"><?php _e('User Role', 'lilac-learning-manager'); ?></label></th>
                <td>
                    <select name="llm_user_role" id="llm_user_role" class="regular-text">
                        <option value=""><?php _e('— No Role —', 'lilac-learning-manager'); ?></option>
                        <?php foreach ($roles as $role => $label) : ?>
                            <option value="<?php echo esc_attr($role); ?>" <?php selected($role, $current_role); ?>>
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
     * Save role from profile
     */
    public static function save_role_profile_fields($user_id) {
        if (!current_user_can('edit_user', $user_id) || !isset($_POST['llm_user_role'])) {
            return;
        }

        $new_role = sanitize_text_field($_POST['llm_user_role']);
        self::update_role($user_id, $new_role);
    }
}
