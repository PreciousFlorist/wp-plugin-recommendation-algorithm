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
function fetch_top_recommendations()
{
    if (
        isset($_POST['post_elo_get_recommendations_nonce_field'])
        && wp_verify_nonce($_POST['post_elo_get_recommendations_nonce_field'], 'post_elo_get_recommendations_action')
    ) {
        if (
            isset($_POST['get_top_recommendations'])
            && isset($_POST['context_post_id'])
            && isset($_POST['num_recommendations'])
            && isset($_POST['post_type'])
        ) {

            $post_type = $_POST['post_type'];
            // Update stored values with the most recent input
            update_option('post_elo_last_context_id_' . $post_type, $_POST['context_post_id']);
            update_option('post_elo_last_num_recommendations_' . $post_type, $_POST['num_recommendations']);
        }
    }
}