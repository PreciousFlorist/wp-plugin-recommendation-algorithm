<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Function to calculate the probability of winning for each recommendation in a given context.
 * This function calculates pairwise winning probabilities using the Elo rating system.
 * It adjusts each recommendation's Elo rating based on its probability of winning against all available rivals.
 * 
 * @param array $context_post_id An array containing the ID of the context post where recommendations are shown.
 * @param array $rival_recommendations An array of IDs for rival recommendations.
 * 
 * @return array Returns an array of data for each recommendation, including:
 *               - recommendation_id: ID of the recommended post.
 *               - recommendation_elo: Current Elo rating of the recommended post.
 *               - k: K-rating factor for the recommended post.
 *               - win_chance: Array of probabilities of winning against each rival.
 *               - joint_win_probability: Calculated joint probability of winning against all rivals.
 *               - elo_adjustments: Adjusted Elo ratings for win/loss scenarios.
 */
function probability_of_win($context_post_id, $rival_recommendations, $file_path, $post_type)
{

    if (!file_exists($file_path)) {
        error_log("`probability_of_win`: JSON file for context ID: $context_post_id not found");
        return [];
    }

    $data = json_decode(file_get_contents($file_path), true);
    if ($data === null) {
        error_log("`probability_of_win`: Error decoding JSON data for context ID: $context_post_id. JSON Error: " . json_last_error_msg());
        return [];
    }

    $number_of_rivals = count($rival_recommendations) - 1;

    $sensitivity = 400;
    // Rating thresholds for k-rating:
    $low = 2300;
    $mid = 2400;
    // Default k-rating (maximum rating change after win/loss):
    $k = 40;

    $elo_data = [];

    // Fetch Elo ratings for each recommendation from JSON data
    foreach ($rival_recommendations as $recommendation_id) {
        if (isset($data[$recommendation_id])) {
            $elo_value = $data[$recommendation_id]['elo_value'];
            $k_value = ($elo_value <= $low) ? $k : (($elo_value <= $mid) ? ($k / 2) : ($k / 4));
            $elo_data[$recommendation_id] = [
                'post_type' => $post_type,
                'recommendation_id' => $recommendation_id,
                'recommendation_elo' => $elo_value,
                'k' => $k_value,
                'win_chance' => [],
                'joint_win_probability' => [],
                'elo_adjustments' => []
            ];
        }
    }

    // Calculate the odds of winning for each pair of calculations
    /*------------------------------------------------------------
    # The formula used to calculate the odds of winning for each pair of calculaitons is:
    # Ea = 1 / (1 + 10 ^ ((Rb - Ra) / 400))
    # Eb = 1 / (1 + 10 ^ ((Ra - Rb) / 400))
    #
    # Where Ra represents Reccomendation A and Rb represent Reccomendation B
    # The 400 refers to the sensitivity of the score to this difference
    #
    # Using example scores of 1350 and 1450, the calculation looks like this:
    # 
    #    Ea = 1 / (1 + 10 ^ ((1350 - 1450) / 400))
    #       = 1 / (1 + 10 ^ (-100 / 400))
    #       = 1 / (1 + 10 ^ (-0.25))
    #       ≈ 0.64
    #
    # Meaning that Recommendation A has a 64% chance of winning
    # Executing this calculation for Eb will provide a 36% chance of winning
    #
    # The formula to then adjust an Elo rating adjustment formula is:
    # R'a = Ra + K * (Sa - Ea)
    #
    # Where:
    # R'a = New Elo rating of the reccomendation after the match
    # Ra = Current Elo rating of the reccomendation
    # K = A constant factor determining the maximum change
    # Sa = Actual outcome of the match (1 for win, 0 for loss)
    # Ea = Expected outcome of the match, as calculated above
    #
    # If Reccomendation A has a win probability of 0.64 it means that they are expected to score 0.36 points from the match.
    # So, if Reccomendation A wins (Sa = 1), the new rating R'a is calculated as:
    #   R'a = 1350 + 32 * (1 - 0.36)
    #       = 1350 + 32 * 0.64
    #       = 1350 + 20.48
    #       ≈ 1370
    #
    # Therefore, if Reccomendation A wins against Reccomendation B, their new rating would be approximately 1370.
    # Conversely, if Reccomendation A loses (Sa = 0):
    #
    #   R'a = 1350 + 32 * (0 - 0.36)
    #       = 1350 - 11.52
    #       ≈ 1338
    #
    # Meaning that if Reccomendation A loses, their new rating would be approximately 1338.
    ------------------------------------------------------------*/

    // Outer loop iterates over each recommendation.
    for ($i = 0; $i < count($rival_recommendations); $i++) {

        $recommendation_i = $rival_recommendations[$i];

        $ra = $elo_data[$recommendation_i]['recommendation_elo'];
        $k = $elo_data[$recommendation_i]['k'];

        // Inner loop starts from the next recommendation in the array to avoid duplicate pairs and self-comparison.
        for ($j = $i + 1; $j < count($rival_recommendations); $j++) {

            $recommendation_j = $rival_recommendations[$j];
            $rb = $elo_data[$recommendation_j]['recommendation_elo'];

            // Calculate winning probabilities via pairwise comparisons
            // Ea = 1 / (1 + 10 ^ ((Rb - Ra) / 400))
            $probability_a = 1 / (1 + pow(10, ($rb - $ra) / $sensitivity));
            $probability_b = 1 - $probability_a;

            $elo_data[$recommendation_i]['win_chance'][$recommendation_j] = $probability_a;
            $elo_data[$recommendation_j]['win_chance'][$recommendation_i] = $probability_b;
        }

        // Calculate the overall oods of winning once individual probabilities have been calculated against all rivals
        if (count($elo_data[$recommendation_i]['win_chance']) === $number_of_rivals) {

            // Calculate joint probability using the Multiplication Probability Rule (General)
            //  P(A ∩ B) = P(A) P(B|A)
            $joint_probability = 1;
            foreach ($elo_data[$recommendation_i]['win_chance'] as $individual_probability) {
                $joint_probability *= $individual_probability;
            }

            // Calculate Elo adjustments for win/loss scenarios
            // R'a = Ra + K * (Sa - Ea)
            $adjusted_rating_win = $ra + $k * (1 - $joint_probability);
            $adjusted_rating_loss = $ra + $k * (0 - $joint_probability);

            // Store the adjustments
            $elo_data[$recommendation_i]['joint_win_probability'] = $joint_probability;
            $elo_data[$recommendation_i]['elo_adjustments'] = ['win' => round($adjusted_rating_win), 'loss' => round($adjusted_rating_loss)];
        }
    }

    return $elo_data;
}
