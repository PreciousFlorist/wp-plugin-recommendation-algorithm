<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

require_once plugin_dir_path(__FILE__) . '/../utilities/elo-utils.php';

/**
 * Shortcode to display top recommendations based on Elo ratings.
 * 
 * Usage: [elo_query context_id="123" num_recommendations="4"] or [elo_query]
 * 
 * @param array $atts Shortcode attributes. Accepts 'context_id' and 'num_recommendations'.
 * @return string HTML content to display the recommendations.
 */
function shortcode_get_elo($atts)
{
    // Default values for the shortcode attributes
    $atts = shortcode_atts([
        'context_id' => get_the_ID(),
        'num_recommendations' => 4,
    ], $atts);

    // Validate the context ID
    if (!is_numeric($atts['context_id'])) {
        error_log('`shortcode_get_elo`: Invalid context ID provided in shortcode.');
        return '<p>' . esc_html__('Error: Invalid context ID.', 'your-text-domain') . '</p>';
    }

    // Fetch recommendations based on context ID and number of recommendations
    $recommendations = get_elo($atts['context_id'], $atts['num_recommendations']);
    if (empty($recommendations)) {
        error_log("`shortcode_get_elo`: No recommendations to display for context ID: {$atts['context_id']}");
        return '<p>' . esc_html__('No recommendations available.', 'your-text-domain') . '</p>';
    }

    // Prepare the output HTML for recommendations
    return prepare_recommendations_output($recommendations, $atts['context_id']);
}

/**
 * Prepares the HTML output for displaying recommendations.
 *
 * @param array $recommendations Array of recommendation data.
 * @param int $context_id The ID of the context page.
 * @return string HTML content for the recommendations.
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
 * @param array $recommendation The recommendation data.
 * @param int $context_id The context ID for the recommendation.
 * @param array $all_recommendations All recommendations data for calculating rival adjustments.
 * @return string HTML content for a single recommendation.
 */
function format_recommendation($recommendation, $context_id, $all_recommendations)
{
    $current_id = $recommendation['recommendation_id'];
    $post = get_post($current_id);
    if (!$post) {
        return '';
    }

    $rival_adjustments = get_rival_adjustments($current_id, $all_recommendations);
    $elo_adjustments_encoded = json_encode($rival_adjustments);

    // Return HTML structure for a recommendation with data attributes for Ajax handling
    return '<div class="elo-recommendation" data-recommendation-id="' . esc_attr($current_id) . '" data-context-id="' . esc_attr($context_id) . '" data-elo-adjustment=\'' . esc_attr($recommendation['elo_adjustments']['win']) . '\' data-rival-adjustments=\'' . esc_attr($elo_adjustments_encoded) . '\'><h3>' . esc_html($post->post_title) . '</h3></div>';
}

/**
 * Gets Elo adjustments for rival recommendations.
 * For each recommendation, this function calculates the loss adjustment value for all other recommendations.
 *
 * @param int $current_id ID of the current recommendation.
 * @param array $all_recommendations Array of all recommendation data.
 * @return array Array of rival adjustments.
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
