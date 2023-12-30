<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once plugin_dir_path(__FILE__) . '/../utilities/elo-utils.php';

/**
 * Handles the shortcode [elo_query] to display top recommendations based on Elo ratings.
 * 
 * This function processes the shortcode attributes, validates them, fetches recommendations
 * Usage: [elo_query context_id="123" num_recommendations="4"] or [elo_query]
 * 
 * @param array $atts Shortcode attributes. Accepts 'context_id', 'num_recommendations', and 'post_type'.
 *          - 'context_id' (int) specifies the context in which the recommendations are being made.
 *          - 'num_recommendations' (int) defines how many recommendations to fetch.
 *          - 'post_type' (string) defines the type of post to consider for recommendations.
 * @return string HTML content to display the recommendations or an error message.
 */
function shortcode_get_elo($atts)
{
    $post_type_id = get_the_ID();
    // Default values for the shortcode attributes
    $atts = shortcode_atts([
        'post_type' => get_post_type($post_type_id),
        'context_id' => $post_type_id,
        'num_recommendations' => 4,
    ], $atts);

    error_log(print_r($atts, true));

    // Validate the context ID
    if (!is_numeric($atts['context_id'])) {
        error_log('`shortcode_get_elo`: Invalid context ID provided in shortcode.');
        return '<p>' . esc_html__('Error: Invalid context ID.', 'your-text-domain') . '</p>';
    }

    // Fetch recommendations based on context ID and number of recommendations
    $recommendations = get_elo($atts['context_id'], $atts['num_recommendations'], $atts['post_type']);

    if (empty($recommendations)) {
        error_log("`shortcode_get_elo`: No recommendations to display for context ID: {$atts['context_id']}");
        return '<p>' . esc_html__('No recommendations available.', 'your-text-domain') . '</p>';
    }

    // Prepare the output HTML for recommendations
    return prepare_recommendations_output($recommendations, $atts['context_id'], $atts['post_type']);
}

/**
 * Prepares the HTML output for displaying recommendations.
 *
 * This function iterates over an array of recommendation data and compiles it into a single HTML string.
 * Each recommendation is processed individually to format its corresponding HTML content.
 *
 * @param array $recommendations An array of recommendation data to be displayed.
 * @param int $context_id The ID of the context page, used for referencing the source of recommendations.
 * @param string $post_type The type of post for which recommendations are being prepared.
 * @return string Formatted HTML content for the recommendations block.
 */
function prepare_recommendations_output($recommendations, $context_id)
{
    $output = '<div class="elo-recommendations">';
    foreach ($recommendations as $recommendation) {
        $output .= format_recommendation($recommendation, $context_id, $recommendations);
    }
    $output .= '</div>';
    return $output;
}

/**
 * Formats a single recommendation into HTML.
 *
 * This function takes an individual recommendation and creates an HTML structure for it.
 * It includes data attributes for potential Ajax handling and adjusts Elo ratings for each recommendation.
 *
 * @param array $recommendation The individual recommendation data.
 * @param int $context_id The context ID for the recommendation, used for referencing.
 * @param array $all_recommendations All recommendations data, used for calculating rival adjustments.
 * @return string HTML content for a single recommendation. Returns an empty string if the recommendation data is invalid.
 */
function format_recommendation($recommendation, $context_id, $all_recommendations,)
{

    $post_type = $recommendation['post_type'];
    $current_id = $recommendation['recommendation_id'];
    $post = get_post($current_id);
    if (!$post) {
        return '';
    }

    $rival_adjustments = get_rival_adjustments($current_id, $all_recommendations);
    $elo_adjustments_encoded = json_encode($rival_adjustments);

    // Return HTML structure for a recommendation with data attributes for Ajax handling
    return '
    <div class="elo-recommendation" data-post-type="' . esc_attr($post_type) . '" data-recommendation-id="' . esc_attr($current_id) . '" data-context-id="' . esc_attr($context_id) . '" data-elo-adjustment=\'' . esc_attr($recommendation['elo_adjustments']['win']) . '\' data-rival-adjustments=\'' . esc_attr($elo_adjustments_encoded) . '\'>
        <h3>' . esc_html($post->post_title) . '</h3>
    </div>
    ';
}

/**
 * Calculates Elo adjustments for rival recommendations.
 *
 * For each recommendation, this function computes the loss adjustment value relative to all other
 * recommendations. This is used to adjust the Elo ratings in a competitive context, where the
 * performance of one item impacts the ratings of others.
 *
 * @param int $current_id ID of the current recommendation, used to exclude it from the adjustment calculations.
 * @param array $all_recommendations Array of all recommendation data, used for calculating adjustments.
 * @return array Array of rival adjustments, where the key is the recommendation ID and the value is the loss adjustment.
 */
function get_rival_adjustments($current_id, $all_recommendations)
{
    $rival_adjustments = [];
    foreach ($all_recommendations as $other_recommendation) {
        if ($other_recommendation['recommendation_id'] != $current_id) {
            $rival_adjustments[$other_recommendation['recommendation_id']] = $other_recommendation['elo_adjustments']['loss'];
        }
    }
    return $rival_adjustments;
}

add_shortcode('elo_query', 'shortcode_get_elo');
