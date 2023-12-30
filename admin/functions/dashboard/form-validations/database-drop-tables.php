<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles the form submission for dropping all database tables associated with the Post ELO plugin.
 *
 * This function is triggered when a user submits a request to delete all database tables related to the Post ELO plugin.
 * It first verifies the request's authenticity and authorization by checking a nonce field. Once the request is validated,
 * the function logs the intent to delete the tables for audit purposes. It then calls `post_elo_cleanup_on_deactivation` 
 * to perform the actual deletion of the tables. After completion, it calculates the duration of the operation and returns 
 * an HTML-formatted success message, which includes the time taken to drop all data. This function is critical for 
 * ensuring clean removal of plugin data from the database, aiding in scenarios like plugin deactivation or uninstallation.
 *
 * @return string HTML formatted message indicating the outcome of the operation, including the duration of the operation.
 */
function handle_drop_db_tables()
{
    // Drop all database tables form submission
    if (
        isset($_POST['drop_db_nonce_field'])
        && wp_verify_nonce($_POST['drop_db_nonce_field'], 'drop_db_action')
        && error_log("Deleting all tables")
    ) {
        error_log("Deleting all tables");
        $start_time = microtime(true);
        post_elo_cleanup_on_deactivation();
        $duration = number_format(microtime(true) - $start_time, 2);

        return '<div class="notice notice-success is-dismissible"><p><span style="font-family: monospace; margin-right: 5px; font-style: italic;">' . $duration . ' seconds:</span> Dropped all data for each enabled post type</p></div>';
    }
}