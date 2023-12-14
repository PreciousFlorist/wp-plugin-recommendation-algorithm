<?php

/**
 * Get the top recommendations for a given context post.
 * 
 * @param int $context_post_id The ID of the context post.
 * @param int $num_recommendations The number of recommendations to fetch.
 * @return array An array of top recommended post IDs.
 */

require_once plugin_dir_path(__FILE__) . '../calculations/winning-probability.php';

function get_top_recommendations($context_post_id, $num_recommendations)
{
    $json_storage_path = plugin_dir_path(dirname(__FILE__, 3)) . 'local-storage/';
    $json_file_name = $json_storage_path . 'context-' . $context_post_id . '.json';

    // Read the JSON file directly
    $json_content = @file_get_contents($json_file_name);
    if ($json_content === false) {
        error_log("Error reading JSON file for context ID: $context_post_id");
        return [];
    }

    // Decode JSON
    $recommendations_data = json_decode($json_content, true);
    if ($recommendations_data === null) {
        error_log("Error decoding JSON data for context ID: $context_post_id. JSON Error: " . json_last_error_msg());
        return [];
    }

    // Get top N recommendations
    $top_recommendations = array_slice($recommendations_data, 0, $num_recommendations, true);
    // Extracting just the post IDs for the probability_of_win function
    $recommendation_ids = array_keys($top_recommendations);

    // Check if there are enough recommendations to show
    if (count($recommendation_ids) < $num_recommendations) {
        error_log("Not enough recommendations available for context ID: $context_post_id");
        return [];
    }

    return probability_of_win($context_post_id, $recommendation_ids);
}
