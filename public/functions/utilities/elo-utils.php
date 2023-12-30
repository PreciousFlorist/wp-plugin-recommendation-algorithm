<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Retrieve top recommendations based on Elo ratings.
 *
 * Fetches a list of recommended posts based on Elo ratings for a given context.
 * Returns an array of recommendations with their details.
 *
 * @param int|null $context_page_id The ID of the context page. Defaults to the current page ID.
 * @param int $num_recommendations The number of recommendations to fetch. Defaults to 4.
 * @return array Array of recommendations with details, or an empty array if no recommendations found or on error.
 */
function get_elo($context_page_id = null, $num_recommendations = 4, $post_type = 'post')
{
    if (is_null($context_page_id)) {
        $context_page_id = get_the_ID();
    }

    if (!$context_page_id) {
        error_log('`get_elo_recommendations`: Context page ID is not available.');
        return [];
    }

    // Adjust the path based on the new file location
    require_once dirname(plugin_dir_path(dirname(__FILE__, 2))) . '/admin/functions/outputs/get-top-recommendations.php';
    
    $recommendations = function_exists('get_top_recommendations') ? get_top_recommendations($context_page_id, $num_recommendations, $post_type) : [];

    if (empty($recommendations)) {
        error_log("`get_elo_recommendations`: No recommendations found for context page ID: $context_page_id");
    }

    return $recommendations;
}
