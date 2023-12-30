<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Function to update Elo ratings in a batch for multiple contexts.
 * 
 * @param string $table_name The name of the database table for Elo ratings.
 * @param array $updates Multi-dimensional associative array containing Elo rating updates.
 * 
 * Top-level keys represent context IDs. Each associated value is an array of updates
 * for that context, where each update is an associative array with:
 *      - 'recommended_post_id': ID of the recommended post
 *      - 'elo_value': Elo rating value to be updated (as an integer).
 * 
 * @return bool Returns true if all updates in the batch are successful, false if any update fails.
 */
function update_elo_ratings($table_name, $updates)
{
    global $wpdb;

    foreach ($updates as $context => $recommendations) {

        foreach ($recommendations as $recommendation) {

            $elo_value = $recommendation['elo_value'];
            $recommended_post_id = $recommendation['recommended_post_id'];

            // Update existing record
            $result = $wpdb->update(
                $table_name,
                // Update `recommended_post_elo`
                ['recommended_post_elo' => $elo_value],
                // Where `context_post_id` and `recommended_post_id` match loop values
                ['context_post_id' => $context, 'recommended_post_id' => $recommended_post_id],

                ['%d'], // Format for the values to update
                ['%d', '%d'] // Format for the WHERE conditions
            );

            if ($result === false) {
                error_log("Failed to update Elo rating for recommended_post_id: $recommended_post_id");
                return false;
            }
        }
    }

    return true;
}
