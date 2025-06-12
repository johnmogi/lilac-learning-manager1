<?php
/**
 * Temporary script to list user roles and capabilities
 */

// Include WordPress
require_once('wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('You do not have sufficient permissions to access this page.');
}

// Get all roles
$wp_roles = wp_roles();
$roles = $wp_roles->roles;

// Output the roles and capabilities
echo '<h2>Current User Roles and Capabilities</h2>';
echo '<pre>';
foreach ($roles as $role_name => $role_info) {
    echo "<strong>Role:</strong> " . esc_html($role_name) . "<br>";
    echo "<strong>Display Name:</strong> " . esc_html($role_info['name']) . "<br>";
    echo "<strong>Capabilities:</strong> <br>";
    foreach ($role_info['capabilities'] as $cap => $has_cap) {
        echo "- " . esc_html($cap) . "<br>";
    }
    echo "<hr>";
}
echo '</pre>';
