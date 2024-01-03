<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles the update of settings for specific post types in the Post ELO plugin.
 *
 * This function is triggered upon form submission to update Post ELO settings. It processes the user's choices
 * regarding enabling or disabling specific post types for ELO rating, updating cron schedules, batch sizes, 
 * and recommendation limits for each post type. It performs the following actions:
 * - Enables or disables post types for ELO rating based on user input.
 * - Updates related WordPress options for each post type, including cron schedules, batch sizes, and recommendation limits.
 * - Initializes database tables for newly enabled post types.
 * - Cleans up resources for disabled post types, including removing associated options and clearing scheduled cron jobs.
 * - Generates and returns an HTML formatted notice indicating the status of the operation, including changes made and the duration of the operation.
 *
 * @return string HTML formatted notice indicating the outcome of the operation, including changes made and the duration of the operation.
 */

function handle_register_post_types()
{
    $existing_post_types = get_option('post_elo_enabled_post_types', []);

    // Check if a specific post type update was submitted
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'update_post_type_') === 0) {

            // Post type
            $post_type = str_replace('update_post_type_', '', $key);
            // Nonce
            $nonce_name = 'post_elo_post_type_settings_nonce_' . $post_type;
            $nonce_action = 'post_elo_post_type_settings_save_' . $post_type;


            if (
                isset($_POST[$nonce_name])
                && wp_verify_nonce($_POST[$nonce_name], $nonce_action)
            ) {
                // Options
                $post_type_option = 'elo_enabled_cpts_' . $post_type;
                $interval_option = 'db_sync_interval_setting_' . $post_type;
                $batch_size_option = 'batch_size' . $post_type;
                $recommendation_limit_option = 'local_storage_limit_' . $post_type;

                $start_time = microtime(true);
                $is_enabled = isset($_POST[$post_type_option]);

                $selected_interval = isset($_POST[$interval_option]) ? $_POST[$interval_option] : 'hourly';
                $batch_size = isset($_POST['post_batch_size']) ? (int)$_POST['post_batch_size'] : 15;
                $recommendation_limit = isset($_POST['recommendation_limit']) ? (int)$_POST['recommendation_limit'] : 20;

                $notice = null;
                $notice_status = 'success';

                // Enabling a post type
                if ($is_enabled && !in_array($post_type, $existing_post_types)) {
                    update_option('elo_enabled_cpts_' . $post_type, true);
                    $existing_post_types[] = $post_type;
                    $notice = 'Added "' . ucfirst($post_type) . '" to the list of enabled post types';

                    // Update the cron schedule
                    if (get_option($interval_option) !== $selected_interval) {
                        update_option($interval_option, $selected_interval);
                    }

                    // Update the batch size
                    if (get_option($batch_size_option) !== $batch_size) {
                        update_option($batch_size_option, $batch_size);
                    }

                    // Process the recommendation limit setting
                    if (get_option($recommendation_limit_option) !== $recommendation_limit) {
                        update_option($recommendation_limit_option, $recommendation_limit);
                    }
                }
                // Disabling a post type
                elseif (!$is_enabled && in_array($post_type, $existing_post_types)) {
                    update_option('elo_enabled_cpts_' . $post_type, false);
                    $existing_post_types = array_diff($existing_post_types, [$post_type]);
                    $notice = 'Removed "' . ucfirst($post_type) . '" from the list of enabled post types';
                    delete_registered_options($post_type);
                    clear_scheduled_cron_jobs($post_type);
                }
                // Processing request with no changes
                else {
                    $notice = 'No changes to the existing list of enabled post types were requested.';
                    $notice_status = 'warning';
                }

                // Banner formatting
                if ($notice) {
                    $duration = number_format(microtime(true) - $start_time, 2);
                    $notice = '<div class="notice notice-' . $notice_status . ' is-dismissible"><p><span style="font-family: monospace; margin-right: 5px; font-style: italic;"><!--duration--> seconds:</span> ' . $notice . '</p></div>';
                    break;
                }
            }
        }
    }

    // Update the global option once after all changes
    if (isset($notice)) {
        update_option('post_elo_enabled_post_types', $existing_post_types);
        elo_init(); // Initialize the database tables for the enabled post types

        $duration = number_format(microtime(true) - $start_time, 2); // Stop timer
        $notice = str_replace('<!--duration-->', $duration, $notice); // Replace placeholder with duration

        return $notice;
    }

    return null;
}
