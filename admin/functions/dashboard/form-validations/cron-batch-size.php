<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles the form submission for setting the batch size for database synchronization.
 *
 * This function processes the form submission for configuring the batch size
 * used in database operations within the Post ELO plugin. It utilizes a nonce
 * for security, ensuring the request's integrity. Upon form submission, it checks
 * if the batch size update and post type are specified. The function then validates
 * and updates the batch size setting for the provided post type, ensuring the 
 * batch size is a positive integer before saving it.
 *
 * @return void
 */
function handle_cron_batch_size()
{
    if (
        isset($_POST['post_elo_update_post_batch_size_nonce'])
        && wp_verify_nonce($_POST['post_elo_update_post_batch_size_nonce'], 'post_elo_update_post_batch_size')
        && isset($_POST['update_post_batch_size'])
        && isset($_POST['post_type'])
    ) {
        $post_type = $_POST['post_type'];
        $batch_size = (int)$_POST['post_batch_size'];

        if ($batch_size > 0) {
            // Save the batch size in an option
            $option_name = 'batch_size_' . $post_type;
            $existing_batch_size = (int)get_option($option_name);

            if ($batch_size === $existing_batch_size) {
                return '<div class="notice notice-warning is-dismissible"><p>The existing batch size for ' . ucfirst($post_type) . ' contexts already matches the requested value of ' . $batch_size . '. No updates have been made.</p></div>';
            }

            $start_time = microtime(true);
            update_option($option_name, $batch_size);
            $duration = number_format(microtime(true) - $start_time, 2);

            return '<div class="notice notice-success is-dismissible"><p><span style="font-family: monospace; margin-right: 5px; font-style: italic;">' . $duration . ' seconds:</span> Successfully updated ' . ucfirst($post_type) . ' contexts recommendations to be processed in batches of ' . $batch_size . '.</p></div>';
        } else {
            return '<div class="notice notice-error is-dismissible"><p>You must select a batch size greater than 0.</p></div>';
        }
    }
}
