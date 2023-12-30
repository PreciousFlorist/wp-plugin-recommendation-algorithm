<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles the request to flush Elo rating data from the database tables for a specific post type.
 *
 * This function manages the form submission for clearing Elo rating data from the
 * database tables associated with a specific post type in the Post ELO plugin. It checks the
 * validity and authorization of the request using a nonce field. If the request is valid,
 * it fetches the specified post type and invokes the function to clear Elo data for that post type.
 * It then returns a confirmation message based on the success or failure of the flush operation.
 *
 * @return void
 */
function handle_flush_db_tables()
{
    if (
        isset($_POST['post_elo_flush_nonce_field'])
        && wp_verify_nonce($_POST['post_elo_flush_nonce_field'], 'post_elo_flush_action')
        && isset($_POST['flush_elo_table'])
        && isset($_POST['post_type'])
    ) {

        $post_type = $_POST['post_type'];

        $start_time = microtime(true);
        $flush = flush_elo_data($post_type);
        $duration = number_format(microtime(true) - $start_time, 2);

        $notice = ($flush)
            ? '<div class="notice notice-success is-dismissible"><p><span style="font-family: monospace; margin-right: 5px; font-style: italic;">' . $duration . ' seconds:</span> Successfully flushed all plugin data for ' . ucfirst($post_type) . ' from JSON data SQL datasets.</p></div>'
            : '<div class="notice notice-error is-dismissible"><p><span style="font-family: monospace; margin-right: 5px; font-style: italic;">' . $duration . ' seconds:</span> Unable to flush plugin data for ' . ucfirst($post_type) . '  from JSON data SQL datasets.</p></div>';

        return $notice;
    }
}
