<?php

/**
 * Function to update Elo ratings in a batch.
 * 
 * @param array $update_data Array of data for updating Elo ratings.
 * Each element of this array should be an associative array with keys:
 * - 'recommended_post_id': ID of the recommended post
 * - 'elo_value': Elo rating value to be updated
 * 
 * @return bool Returns true if the batch update is successful, false otherwise.
 */
function update_elo_ratings($update_data)
{

    global $wpdb;
    $table_name = $wpdb->prefix . 'elo_rating';

    foreach ($update_data as $data) {
        $recommended_post_id = $data['recommended_post_id'];
        $elo_value = $data['elo_value'];

        // Update existing record
        $result = $wpdb->update(
            $table_name,
            ['recommended_post_elo' => $elo_value], // Columns to update
            ['recommended_post_id' => $recommended_post_id] // Where clause
        );

        if ($result === false) {
            error_log("Failed to update Elo rating for recommended_post_id: $recommended_post_id");
            return false;
        }
    }

    return true;
}
