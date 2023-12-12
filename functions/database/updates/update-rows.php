<?php

/**
 * Function to add Elo ratings in a batch.
 * This function is designed to handle both single and multiple Elo rating updates.
 * 
 * @param array $update_data Array of data for updating Elo ratings.
 * Each element of this array should be an associative array with keys:
 * - 'context_post_id': ID of the context post
 * - 'recommended_post_id': ID of the recommended post
 * - 'elo_value': Elo rating value to be assigned
 * 
 * @return bool Returns true if the batch update is successful, false otherwise.
 */
function add_post_elo_rating($update_data)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'elo_rating';

    // Store values for a batch insert query
    $values = [];
    $placeholders = [];
    foreach ($update_data as $data) {
        // Extracting values from the data array
        $context_post_id = $data['context_post_id'];
        $recommended_post_id = $data['recommended_post_id'];
        $elo_value = $data['elo_value'];

        // Fetch existing records for the current context_post_id
        $existing_record = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE context_post_id = %d AND recommended_post_id = %d", $context_post_id, $recommended_post_id));

        // Skip if the pair already exists
        if ($existing_record > 0) continue;

        // Update values for batch update
        $values[] = $context_post_id;
        $values[] = $recommended_post_id;
        $values[] = $elo_value;
        $placeholders[] = '(%d, %d, %d)';
    }

    // Initialize $result to null
    $result = null;

    // Insert new records in batch if any
    if (!empty($placeholders)) {
        $query = "INSERT INTO $table_name (context_post_id, recommended_post_id, recommended_post_elo) VALUES " . implode(', ', $placeholders);
        $result = $wpdb->query($wpdb->prepare($query, $values));
    }

    if ($result === false) {
        error_log("Failed to batch add Elo ratings.");
        return false;
    } else {
        error_log("Batch added Elo ratings or no new data to add.");
        return true;
    }

    // Return true if there were no new records to add (placeholders array was empty)
    return true;
}
