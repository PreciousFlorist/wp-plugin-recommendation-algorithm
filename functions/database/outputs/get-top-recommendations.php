<?php
// Located in wp-content/plugins/post-elo/functions/database/outputs/get-top-recommendations.php

/**
 * Get the top recommendations for a given context post.
 * 
 * @param int $context_post_id The ID of the context post.
 * @param int $num_recommendations The number of recommendations to fetch.
 * @return array An array of top recommended post IDs.
 */

require_once plugin_dir_path(__FILE__) . '../../calculations/winning-probability.php';

function get_top_recommendations($context_post_id, $num_recommendations)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'elo_rating';

    // SQL query to fetch top recommendations
    $query = $wpdb->prepare(
        "SELECT recommended_post_id FROM $table_name WHERE context_post_id = %d ORDER BY recommended_post_elo DESC LIMIT %d",
        $context_post_id,
        $num_recommendations
    );

    $results = $wpdb->get_results($query, ARRAY_A);

    if ($results === null || is_wp_error($results)) {
        error_log("Failed to fetch Elo ratings for context ID: $context_post_id");
        return false;
    }

    $top_recommendations = wp_list_pluck($results, 'recommended_post_id');

    return probability_of_win($context_post_id, $top_recommendations);
}
