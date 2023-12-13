<?php
// Register admin page
function post_elo_admin_menu()
{
    add_menu_page(
        'Post ELO Settings', // Page title
        'Post ELO', // Menu title
        'manage_options', // Capability
        'post-elo-settings', // Menu slug
        'post_elo_settings_page' // Callback function
    );
}
add_action('admin_menu', 'post_elo_admin_menu');

// Display the admin settings page
function post_elo_settings_page()
{
?>
    <div class="wrap">
        <h1>Post ELO Settings:</h1>

        <!-- Form to flush database table -->
        <form method="post" action="">
            <?php wp_nonce_field('post_elo_update_action', 'post_elo_nonce_field'); ?>
            <input type="submit" name="flush_elo_table" value="Flush ELO Data" class="button-primary" />
        </form>
        <!-- Sync database with JSON data -->
        <form method="post" action="">
            <input type="submit" name="sync_database" value="Sync Database" class="button-secondary" />
        </form>
        <!-- Get Recommendations -->
        <form method="post" action="">
            <h2>Get Top Recommendations</h2>
            <p>Enter the context post ID and the number of recommendations:</p>
            <label for="context_post_id">Context Post ID:</label>
            <input type="number" id="context_post_id" name="context_post_id" placeholder="Context Post ID" required value="1" />
            <label for="num_recommendations">Number of Recommendations:</label>
            <input type="number" id="num_recommendations" name="num_recommendations" placeholder="Number of Recommendations" min="2" required value="4" />
            <input type="submit" name="get_top_recommendations" value="Get Recommendations" class="button-secondary" />
        </form>
    </div>
<?php
    if (isset($_POST['get_top_recommendations'])) {
        handle_probability_calculation();
    }
}

add_action('admin_init', 'post_elo_handle_form_submission');

// Handle the form submission for probability calculation
function handle_probability_calculation()
{
    $start_time = microtime(true);

    calculate_and_display_probabilities();

    $execution_time = microtime(true) - $start_time;
    echo '<div class="updated notice"><p>Calculation completed in ' . number_format($execution_time, 2) . ' seconds.</p></div>';
}
add_action('admin_init', 'post_elo_handle_form_submission');

// Handle the form submission for flushing database
function post_elo_handle_form_submission()
{
    if (!current_user_can('manage_options') || !isset($_POST['post_elo_nonce_field']) || !wp_verify_nonce($_POST['post_elo_nonce_field'], 'post_elo_update_action')) {
        return;
    }

    if (isset($_POST['flush_elo_table'])) {
        flush_elo_table();
    }
}


// Flush the ELO rating table and clear local JSON storage
function flush_elo_table()
{
    global $wpdb;
    $start_time = microtime(true);

    // Drop and reinitialize the ELO rating table in the database
    drop_elo_rating_table();
    // Clear all files in the local JSON storage directory
    clear_local_json_storage();

    $success = initialize_elo_rating_table();

    $execution_time = microtime(true) - $start_time;
    $message_class = $success ? 'updated' : 'error';
    $message_text = $success ? 'ELO database table and local JSON storage flushed and repopulated successfully in ' . number_format($execution_time, 2) . ' seconds.' : 'Error repopulating the ELO database table and clearing local JSON storage.';

    echo "<div id='message' class='$message_class notice is-dismissible'><p>$message_text</p></div>";
}

// Drop the ELO rating table
function drop_elo_rating_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'elo_rating';
    $wpdb->query("DROP TABLE IF EXISTS $table_name;");
}

// Function to clear all files in the local-storage directory
function clear_local_json_storage()
{
    $json_storage_path = plugin_dir_path(__FILE__) . '../local-storage/';

    $files = glob($json_storage_path . '*'); // Get all file names
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file); // Delete file
        }
    }
}

/*------------------------------
# Calculate probability 
------------------------------*/
require_once plugin_dir_path(__FILE__) . '../functions/database/outputs/get-top-recommendations.php';

function calculate_and_display_probabilities()
{
    if (isset($_POST['context_post_id'], $_POST['num_recommendations']) && is_numeric($_POST['context_post_id']) && is_numeric($_POST['num_recommendations'])) {

        $context_post_id = $_POST['context_post_id'];
        $num_recommendations = $_POST['num_recommendations'];

        $posts = get_top_recommendations($context_post_id, $num_recommendations);

        if (!empty($posts)) {
            echo '<div class="wrap"><h3>Probability and Elo Rating Adjustment Results:</h3>';
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>ID</th><th>Elo Rating</th>';

            // Table Headers for rival IDs
            foreach ($posts as $post) {
                echo '<th>vs ' . esc_html($post["recommendation_id"]) . '</th>';
            }

            echo '<th>Probability to win</th><th>Elo if Win</th><th>Elo if Loss</th></tr></thead>';
            echo '<tbody>';

            // Table Rows
            foreach ($posts as $row_post) {

                $current_elo = isset($row_post['recommendation_elo']) ? $row_post['recommendation_elo'] : 'N/A';
                $elo_win = isset($row_post['elo_adjustments']['win']) ? $row_post['elo_adjustments']['win'] : 'N/A';
                $elo_loss = isset($row_post['elo_adjustments']['loss']) ? $row_post['elo_adjustments']['loss'] : 'N/A';

                echo '<tr><td>' . esc_html($row_post['recommendation_id']) . '</td><td>' . esc_html($current_elo) . '</td>';

                // Table data for rival IDs
                foreach ($posts as $col_post) {
                    $col_id = $col_post['recommendation_id'];

                    if ($row_post['recommendation_id'] == $col_id) {
                        echo '<td>-</td>';
                    } else {
                        $chance = isset($row_post['win_chance'][$col_id]) ? round($row_post['win_chance'][$col_id] * 100, 2) . '%' : 'N/A';
                        echo '<td>' . esc_html($chance) . '</td>';
                    }
                }

                $joint_win_probability = isset($row_post['joint_win_probability']) ? round($row_post['joint_win_probability'] * 100, 2) . '%' : 'N/A';
                echo '<td>' . $joint_win_probability . '</td>';
                echo '<td>' . esc_html($elo_win) . '</td><td>' . esc_html($elo_loss) . '</td></tr>';
            }

            echo '</tbody></table>';
            echo '</div>';

            // Add form to select a winner
            /*------------------------------
            # Select a winner
            ------------------------------*/
            echo '<form method="post" action="">';
            echo '<h2>Select a Winner</h2>';

            foreach ($posts as $row_post) {
                $recommendation_id = $row_post['recommendation_id'];
                echo '<label>';
                echo '<input type="radio" name="selected_winner" value="' . esc_attr($recommendation_id) . '"> ';
                echo esc_html($recommendation_id);
                echo '</label><br>';
            }

            foreach ($posts as $post) {
                echo '<input type="hidden" name="recommendations[' . $post['recommendation_id'] . '][win]" value="' . $post['elo_adjustments']['win'] . '">';
                echo '<input type="hidden" name="recommendations[' . $post['recommendation_id'] . '][loss]" value="' . $post['elo_adjustments']['loss'] . '">';
            }

            echo '<input type="hidden" name="context_post_id" value="' . esc_attr($context_post_id) . '">';
            echo '<input type="submit" name="update_elo_ratings" value="Update Elo Ratings" class="button-primary">';
            echo '</form>';
        } else {
            echo '<div class="error notice"><p>No recommendations found.</p></div>';
        }
    }
}


/*------------------------------------------------------------
# Update JSON values
------------------------------------------------------------*/
add_action('admin_init', 'post_elo_update_elo_ratings');

/**
 * Updates the Elo ratings in the local JSON storage
 *
 * This function is triggered after a form submission on the admin page. It reads and updates the
 * Elo ratings in the JSON file for a specific context, based on the selected winner and
 * recalculated Elo ratings. After updating, it re-sorts the recommendations based on the new ratings
 * and saves the updated data back to the JSON file.
 *
 * @return void Outputs messages to the admin page and logs errors as needed.
 */
function post_elo_update_elo_ratings()
{
    // Check if the required data is set in the POST request
    if (!isset($_POST['update_elo_ratings'], $_POST['context_post_id'], $_POST['selected_winner'])) {
        return; // Exit if the required POST data is not set
    }

    $context_post_id = $_POST['context_post_id'];
    $selected_winner = $_POST['selected_winner'];
    $recommendations = $_POST['recommendations'];

    // Path to the JSON file
    $json_storage_path = plugin_dir_path(__FILE__) . '../local-storage/';
    $json_file_name = $json_storage_path . 'context-' . $context_post_id . '.json';

    // Check if the JSON file exists
    if (!file_exists($json_file_name)) {
        echo '<div class="error notice"><p>JSON file not found for context ID: ' . esc_html($context_post_id) . '.</p></div>';
        return;
    }

    // Read the content of the JSON file
    $json_content = file_get_contents($json_file_name);
    if ($json_content === false) {
        error_log("Error reading JSON file: $json_file_name");
        echo '<div class="error notice"><p>Error reading JSON file for context ID: ' . esc_html($context_post_id) . '.</p></div>';
        return;
    }

    // Decode the JSON content into an associative array
    $data = json_decode($json_content, true);
    if (!is_array($data)) {
        error_log("Error decoding JSON data from file: $json_file_name. JSON Error: " . json_last_error_msg());
        echo '<div class="error notice"><p>Error decoding JSON data for context ID: ' . esc_html($context_post_id) . '.</p></div>';
        return;
    }

    $found_winner = false;
    // Iterate over each recommendation and update Elo ratings
    foreach ($data as $recommendation_id => &$recommendation) {
        if (isset($recommendations[$recommendation_id])) {
            $recommendationData = $recommendations[$recommendation_id];
            // Check if the current recommendation is the selected winner
            if ($recommendation_id == $selected_winner) {
                $recommendation['elo_value'] = intval($recommendationData['win']);  // Apply the win adjustment
                $found_winner = true;
            } else {
                $recommendation['elo_value'] = intval($recommendationData['loss']); // Apply the loss adjustment
            }
            $recommendation['updated'] = true;
        }
    }



    if (!$found_winner) {
        echo '<div class="error notice"><p>Selected winner ID not found in recommendations.</p></div>';
        return;
    } else {

        // Resort the recommendations based on the updated Elo values
        uasort($data, function ($a, $b) {
            return $b['elo_value'] <=> $a['elo_value'];
        });
        // Save the sorted data back to the JSON file
        file_put_contents($json_file_name, json_encode($data));

        // Add flag to indicate that this context has been updated
        $data['updated'] = true;
        file_put_contents($json_file_name, json_encode($data));
    }

    if (file_put_contents($json_file_name, json_encode($data)) === false) {
        error_log("Error writing to JSON file: $json_file_name");
        echo '<div class="error notice"><p>Error updating JSON data for context ID: ' . esc_html($context_post_id) . '.</p></div>';
        return;
    }

    echo '<div class="updated notice"><p>Elo ratings updated successfully for context ID: ' . esc_html($context_post_id) . '.</p></div>';
}

/*------------------------------------------------------------
# Sync database with JSON datasets 
------------------------------------------------------------*/

add_action('admin_init', 'post_elo_handle_sync_database_submission');

// Handle the form submission for syncing database
function post_elo_handle_sync_database_submission()
{
    if (isset($_POST['sync_database'])) {
        post_elo_sync_database();
    }
}

/**
 * Synchronizes the database with updated Elo ratings from JSON files.
 *
 * This function is triggered when the "Sync Database" button is clicked. It loops over
 * all context JSON files, checks for the "updated" flag, and accumulates data for a batch
 * SQL update to the database.
 *
 * @return void Outputs messages to the admin page and logs errors as needed.
 */

require_once plugin_dir_path(__FILE__) . '../functions/database/updates/update-rows.php';

function post_elo_sync_database()
{
    if (!isset($_POST['sync_database'])) {
        return; // Exit if the sync button wasn't pressed
    }

    global $wpdb;

    $json_storage_path = plugin_dir_path(__FILE__) . '../local-storage/';
    $files = glob($json_storage_path . '*.json');
    $batch_update_data = [];

    foreach ($files as $file) {
        $json_content = file_get_contents($file);
        if ($json_content === false) {
            continue; // Skip files that can't be read
        }

        $data = json_decode($json_content, true);
        if (!is_array($data) || !isset($data['updated']) || !$data['updated']) {
            continue; // Skip files without 'updated' flag
        }

        foreach ($data as $recommendation_id => &$recommendation) {
            if (is_array($recommendation) && isset($recommendation['updated']) && $recommendation['updated']) {
                // Accumulate data for batch update
                $batch_update_data[] = [
                    'recommended_post_id' => $recommendation_id,
                    'elo_value' => $recommendation['elo_value']
                ];
                // Remove 'updated' flag from recommendation 
                unset($recommendation['updated']);
            }
        }
        // Remove 'updated' flag from the context 
        unset($data['updated']);
        file_put_contents($file, json_encode($data));
    }

    if (!empty($batch_update_data)) {
        // Perform batch SQL update
        update_elo_ratings($batch_update_data);

        echo '<div class="updated notice"><p>Database synchronized with updated Elo ratings.</p></div>';
    } else {
        echo '<div class="updated notice"><p>No updates needed for the database.</p></div>';
    }
}
