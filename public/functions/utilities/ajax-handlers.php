<?php

/**
 * Handles AJAX request to update Elo ratings.
 *
 * This function updates the Elo ratings of a selected recommendation and its rivals based on user interactions.
 * It reads the existing Elo data from a JSON file, updates the ratings, and writes the changes back to the file.
 * The function is designed to work with both logged-in and logged-out users.
 *
 * Usage: Triggered via AJAX call on user interaction with the recommendations.
 *
 * @global $_POST['context_id'] The ID of the context page from where the recommendation is selected.
 * @global $_POST['recommendation_id'] The ID of the selected recommendation.
 * @global $_POST['elo_adjustment'] The new Elo value for the selected recommendation.
 * @global $_POST['rival_adjustments'] JSON string of Elo adjustments for rival recommendations.
 *
 * @return void Responds with JSON success or error message.
 */
function handle_elo_update()
{
    // Check for the required POST data
    $context_id = filter_input(INPUT_POST, 'context_id', FILTER_SANITIZE_NUMBER_INT);
    $winner_id = filter_input(INPUT_POST, 'recommendation_id', FILTER_SANITIZE_NUMBER_INT);
    $winner_adjustment = filter_input(INPUT_POST, 'elo_adjustment', FILTER_SANITIZE_NUMBER_INT);
    $rival_adjustments_raw = filter_input(INPUT_POST, 'rival_adjustments', FILTER_DEFAULT);
    $rival_adjustments = $rival_adjustments_raw ? json_decode(stripslashes($rival_adjustments_raw), true) : null;

    if (!$winner_id || !$context_id || !is_array($rival_adjustments)) {
        wp_send_json_error('`handle_elo_update`: Invalid or incomplete data');
        return;
    }

    // Path to the JSON file
    $plugin_base_dir = dirname(plugin_dir_path(__FILE__), 3);  // Go up two levels from the current file's directory
    $json_storage_path = $plugin_base_dir . '/local-storage/';
    $json_file_name = $json_storage_path . 'context-' . $context_id . '.json';

    // Check if the JSON file exists
    if (!file_exists($json_file_name)) {
        wp_send_json_error('`handle_elo_update`: JSON file not found for context ID: ' . $context_id);
        return;
    }

    // Read and decode the JSON file
    $json_content = file_get_contents($json_file_name);
    $data = json_decode($json_content, true);

    if (!$json_content || !is_array($data)) {
        wp_send_json_error('`handle_elo_update`: Error reading or decoding JSON file.');
        return;
    }

    // Update Elo ratings based on user interaction
    $updates = 0;
    $totalToUpdate = count($rival_adjustments) + 1; // +1 for the winner

    foreach ($data as $id => &$recommendation) {
        if ($id == $winner_id) {
            $recommendation['elo_value'] = (int) $winner_adjustment;
            $recommendation['updated'] = true;
            $updates++;
        } elseif (array_key_exists($id, $rival_adjustments)) {
            $recommendation['elo_value'] = (int) $rival_adjustments[$id];
            $recommendation['updated'] = true;
            $updates++;
        }

        // Break the loop when all necessary updates are done
        if ($updates == $totalToUpdate) {
            break;
        }
    }

    if (!$updates == $totalToUpdate) {
        wp_send_json_error('`handle_elo_update`: Target IDs not found.');
        return;
    } else {
        $data['updated'] = true; // Set updated flag for the parent context
    }

    // Resort the recommendations based on the updated Elo values
    uasort($data, function ($a, $b) {

        if (is_array($a) && is_array($b) && isset($a['elo_value']) && isset($b['elo_value'])) {
            return $b['elo_value'] <=> $a['elo_value'];
        }
        // Return 0 if comparison isn't valid (this keeps the original order)
        return 0;
    });

    // Save the updated data back to the JSON file
    if (file_put_contents($json_file_name, json_encode($data)) === false) {
        wp_send_json_error('`handle_elo_update`: Error writing to JSON file.');
        return;
    }

    // Success response
    wp_send_json_success('`handle_elo_update`: Elo rating updated successfully.');
}

// Register the AJAX actions
add_action('wp_ajax_update_elo_rating', 'handle_elo_update');
add_action('wp_ajax_nopriv_update_elo_rating', 'handle_elo_update'); // For logged-out users