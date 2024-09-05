<?php
/**
 * Plugin Name: Editor User Creator
 * Description: Allows a specific editor to create users and assign them only Author or Editor roles.
 * Version: 1.5
 * Author: Chetra Chann
 */
 
 // Define an array of editor user IDs who can create users can use example [107, 108, 200]
 define('SPECIFIC_EDITOR_USER_IDS', [107]); // Replace with the actual user IDs
 
 // Add capabilities to the specific users on plugin activation
 function limited_user_creator_activate() {
     foreach (SPECIFIC_EDITOR_USER_IDS as $user_id) {
         $user = new WP_User($user_id);
         if ($user->exists()) {
             $user->add_cap('create_users');
             $user->add_cap('promote_users');
             $user->add_cap('list_users');
         }
     }
 }
 register_activation_hook(__FILE__, 'limited_user_creator_activate');
 
 // Remove capabilities from the specific users on plugin deactivation
 function limited_user_creator_deactivate() {
     foreach (SPECIFIC_EDITOR_USER_IDS as $user_id) {
         $user = new WP_User($user_id);
         if ($user->exists()) {
             $user->remove_cap('create_users');
             $user->remove_cap('promote_users');
             $user->remove_cap('list_users');
         }
     }
 }
 register_deactivation_hook(__FILE__, 'limited_user_creator_deactivate');
 
 // Block the specific editors from modifying administrator accounts and roles
 function limited_user_creator_prevent_admin_role_change($user_id) {
     if (in_array(get_current_user_id(), SPECIFIC_EDITOR_USER_IDS)) {
         $user = get_userdata($user_id);
         $new_role = isset($_POST['role']) ? $_POST['role'] : '';
 
         // Prevent the specific editors from modifying Administrator accounts
         if (in_array('administrator', $user->roles) || $new_role === 'administrator') {
             wp_die(__('You cannot modify or assign the Administrator role.'));
         }
 
         // Prevent changing roles of users with roles other than author or editor
         $allowed_roles = ['author', 'editor'];
         if (!empty(array_diff($user->roles, $allowed_roles))) {
             wp_die(__('You can only modify users with Author or Editor roles.'));
         }
     }
 }
 add_action('edit_user_profile_update', 'limited_user_creator_prevent_admin_role_change');
 add_action('personal_options_update', 'limited_user_creator_prevent_admin_role_change');
 
 // Restrict the specific editors to only assign 'Author' and 'Editor' roles
 function limited_user_creator_restrict_roles($all_roles) {
     if (in_array(get_current_user_id(), SPECIFIC_EDITOR_USER_IDS)) {
         // Only allow the 'Author' and 'Editor' roles to be assigned
         return array_intersect_key(
             $all_roles,
             array(
                 'author' => '',
                 'editor' => '',
             )
         );
     }
     return $all_roles;
 }
 add_filter('editable_roles', 'limited_user_creator_restrict_roles');
 
 // Allow both the specific editors and administrators to create users
 function limited_user_creator_check_create_users($caps, $cap, $user_id) {
     if ($cap === 'create_users') {
         if (in_array(get_current_user_id(), SPECIFIC_EDITOR_USER_IDS) || current_user_can('administrator')) {
             return $caps;
         }
         return ['do_not_allow'];
     }
     return $caps;
 }
 add_filter('map_meta_cap', 'limited_user_creator_check_create_users', 10, 3);
 
 // Restrict the specific editors from viewing or editing administrator users
 function limited_user_creator_restrict_user_listing($query) {
     if (in_array(get_current_user_id(), SPECIFIC_EDITOR_USER_IDS)) {
         $query->set('role__not_in', ['administrator']);
     }
 }
 add_action('pre_get_users', 'limited_user_creator_restrict_user_listing');