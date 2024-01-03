<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles the scheduling of automated cron jobs for database synchronization.
 *
 * This function processes the form submission related to scheduling cron jobs 
 * for database synchronization in the Post ELO plugin. It ensures the security 
 * and integrity of the request using a nonce field. Upon verification, it checks
 * if the cron update and post type are set, and retrieves the desired interval 
 * for database synchronization. This interval is then saved as an option and used 
 * to schedule the cron job for the specified post type.
 *
 * @return void
 */
function handle_cron_schedule()
{
    if (
        isset($_POST['post_elo_update_cron_nonce'], $_POST['update_cron'], $_POST['post_type'])
        && wp_verify_nonce($_POST['post_elo_update_cron_nonce'], 'post_elo_update_cron_action')
    ) {
        $post_type = sanitize_text_field($_POST['post_type']);
        $interval_setting = 'db_sync_interval_setting_' . $post_type;

        // Check if the specific schedule field is set
        if (isset($_POST[$interval_setting])) {

            $interval = sanitize_text_field($_POST[$interval_setting]);
            $existing_interval = get_option($interval_setting);

            if ($interval === $existing_interval) {
                return '<div class="notice notice-warning is-dismissible"><p>The existing cron interval for ' . ucfirst($post_type) . ' contexts already runs ' . $interval . '. No updates have been made.</p></div>';
            }

            $start_time = microtime(true);
            update_option($interval_setting, $interval); // Save the selected interval
            schedule_database_sync_job($post_type, $interval);
            $duration = number_format(microtime(true) - $start_time, 2);

            return '<div class="notice notice-success is-dismissible"><p><span style="font-family: monospace; margin-right: 5px; font-style: italic;">' . $duration . ' seconds:</span> Successfully updated the cron interval for ' . ucfirst($post_type) . ' contexts to trigger ' . $interval . '.</p></div>';
        }
    }
}
