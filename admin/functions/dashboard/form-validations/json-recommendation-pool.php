<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles the form submission for updating the recommendation pool size in the Post ELO plugin.
 *
 * This function is triggered upon form submission to update the size of the recommendation pool
 * for a specific post type in the Post ELO plugin. It checks the form submission for validity using
 * a nonce for security purposes. If the form is valid and the recommendation pool size is specified,
 * this function updates the 'local_storage_limit' option for the given post type. It also triggers
 * a synchronization of JSON data to the database, considering the new recommendation pool size.
 *
 * @return void
 */
function handle_json_recommendation_pool()
{
    if (
        isset($_POST['post_elo_update_recommendation_pool_nonce'])
        && wp_verify_nonce($_POST['post_elo_update_recommendation_pool_nonce'], 'post_elo_update_recommendation_pool')
        && isset($_POST['update_recommendation_pool'])
        && isset($_POST['post_type'])
    ) {
        $post_type = $_POST['post_type'];
        $recommendation_limit = (int)$_POST['recommendation_limit'];

        if ($recommendation_limit > 0) {

            $option_name = 'local_storage_limit_' . $post_type;
            $existing_recommendation_limit = (int)get_option($option_name);

            if ($recommendation_limit === $existing_recommendation_limit) {
                return '<div class="notice notice-warning is-dismissible"><p>The existing recommendation pool for ' . ucfirst($post_type) . ' contexts already matches the requested value of ' . $recommendation_limit . '. No updates have been made.</p></div>';
            }

            // Save the recommendation limit in an option
            $start_time = microtime(true);
            update_option($option_name, $recommendation_limit);
            // Update the local storage for all contexts
            sync_json_to_database($post_type, true);
            $duration = number_format(microtime(true) - $start_time, 2);

            return '<div class="notice notice-success is-dismissible"><p><span style="font-family: monospace; margin-right: 5px; font-style: italic;">' . $duration . ' seconds:</span> Successfully updated the recommendation pool for ' . ucfirst($post_type) . ' contexts to ' . $recommendation_limit . '.</p></div>';
        } else {
            return '<div class="notice notice-error is-dismissible"><p>You must select a recommendation pool greater than 0.</p></div>';
        }
    }
}
