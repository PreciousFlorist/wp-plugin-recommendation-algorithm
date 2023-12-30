<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Function to add Elo ratings in a batch.
 * This function is designed to handle both single and multiple Elo rating updates.
 * 
 * @param array $update_data An array of associative arrays, each containing Elo rating data. Each element must have:
 * - 'context_post_id' (int): The ID of the context post.
 * - 'recommended_post_id' (int): The ID of the recommended post.
 * - 'elo_value' (int): The Elo rating value to be assigned.
 * @param string $post_type The type of post the Elo ratings are associated with, used to determine the correct database table.
 *
 * @return bool Returns true if the batch update is successful or if there are no new records to add. 
 * Returns false if there is an error or no update data is provided.
 */
function insert_elo_ratings($update_data, $post_type)
{

    if (empty($update_data)) {
        error_log("No update data provided for post type: $post_type");
        return false;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'elo_rating_' . $post_type;

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
        error_log("Processed batch.");
        return true;
    }

    // Return true if there were no new records to add (placeholders array was empty)
    return true;
}
