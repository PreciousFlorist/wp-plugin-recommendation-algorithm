<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles the synchronization of the database with local JSON files for a specific post type.
 *
 * This function manages the form submission for synchronizing Elo rating data between local JSON 
 * files and the database tables associated with a specific post type in the Post ELO plugin. It 
 * ensures the security and integrity of the request using a nonce field. If the request is valid, 
 * it fetches the specified post type and executes the synchronization function. Following the sync 
 * operation, it returns a confirmation message indicating the success or failure of the process.
 *
 * @return void
 */
function handle_sync_db_tables()
{
    if (
        isset($_POST['post_elo_sync_nonce_field'])
        && wp_verify_nonce($_POST['post_elo_sync_nonce_field'], 'post_elo_sync_action')
    ) {
        if (
            isset($_POST['sync_database'])
            && isset($_POST['post_type'])
        ) {
            $post_type = $_POST['post_type'];
            $sync = sync_json_to_database($post_type);

            $notice = ($sync)
                ? '<div class="updated notice"><p>Successfully synced JSON data to database.</p></div>'
                : '<div class="error notice"><p>Error decoding JSON data and updating SQL database.</p></div>';

            error_log($notice);
            return $notice;
        }
    }
}