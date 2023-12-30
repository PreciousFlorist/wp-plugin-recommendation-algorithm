<?php
require_once plugin_dir_path(__FILE__) . 'update-rows.php';
require_once plugin_dir_path(dirname(__FILE__), 2) . 'initialize.php';
require_once plugin_dir_path((__FILE__)) . 'schedule/cron-management.php';

/**
 * Synchronizes the database with updated Elo ratings from JSON files.
 *
 * Processes JSON files in a loop, checking each for updates. It accumulating data for a batch SQL update, 
 * updating Elo ratings in the database after processing each batch or reaching a batch size limit. 
 * If a full refresh is requested or if updates are detected, it refreshes the corresponding 
 * JSON files with top recommendations based on updated ratings, ensuring that each JSON file 
 * is updated only once, either due to data changes or recommendation pool size changes.
 * Tracks contexts updated in the current run to facilitate a full refresh if requested.
 *
 * @param string $post_type The post type for which the database synchronization is being performed.
 * @param bool $full_refresh (optional) Indicates whether to perform a full refresh of all contexts.
 * @return void Outputs messages to the admin page, logs errors, and schedules the next synchronization run.
 * 
 */
function sync_json_to_database($post_type, $full_refresh = false)
{

    (!$post_type) ? error_log("`sync_json_to_database`: Could not fetch updates for: " . $post_type) : null;


    $start_time = microtime(true); // Record the start time for performance tracking

    global $wpdb;

    $table_name = $wpdb->prefix . 'elo_rating_' . $post_type; // The table name for elo ratings
    $json_storage_path = plugin_dir_path(dirname(__FILE__, 4)) . 'local-storage/' . $post_type . '/'; // The path to the JSON storage directory
    $files = glob($json_storage_path . '*.json'); // All JSON files from the storage directory
    $local_storage_limit = get_option('local_storage_limit_' . $post_type); // The current limit for local recommendation storage

    // Track updated contexts
    $contexts = [];
    $batch_updates = [];
    $num_of_files = count($files) - 1; // Remove 1 since the foreach loop starts at 0 and count starts at 1

    // Flag to track overall success of batch updates
    $batch_update_success = true;
    $batch_size = get_option('batch_size' . $post_type);

    foreach ($files as $i => $file) { // Iterate through each JSON file

        // Extract the Context ID from file name
        if (!preg_match('/context-(\d+)\.json$/', $file, $matches)) {
            continue;
        }
        $context = $matches[1];
        $contexts[$context] = false; // All valid contexts

        // Perform a batch update if the batch size is reached or all files have been processed
        if (count($batch_updates) >= $batch_size || $i === $num_of_files) {
            if (!empty($batch_updates)) {
                // Update the Elo ratings in the database
                $update_success = update_elo_ratings($table_name, $batch_updates);

                if (!$update_success) {
                    error_log("Batch update failed for context: $context");
                }

                // Flag to track the success of refreshing JSON files
                $refresh_success = true;

                // Refresh the JSON files for each updated context
                foreach ($batch_updates as $context => $recommendations) {
                    $local_storage_path = $json_storage_path . 'context-' . $context . '.json';
                    $refresh_success = refresh_json_with_top_recommendations($table_name, $context, $local_storage_path, $local_storage_limit);
                }

                // Check for failures in either database update or JSON refresh
                if (!$update_success || !$refresh_success) {
                    $batch_update_success = false;
                }

                $batch_updates = []; // Reset batch data
            }
        }

        // Read the content of the JSON file
        $json_content = file_get_contents($file);
        if ($json_content === false) {
            error_log("Failed to read JSON file: $context");
            continue;
        }

        $data = json_decode($json_content, true); // Decode the JSON content
        if (!is_array($data) || !isset($data['updated']) || !$data['updated']) { // No updates recorded
            continue;
        }

        foreach ($data as $recommendation_id => &$recommendation) {
            // Check for the 'updated' flag and process accordingly
            if (is_array($recommendation) && isset($recommendation['updated']) && $recommendation['updated']) {
                // Add the updated recommendation to the batch update data
                $batch_updates[$context][] = [
                    'recommended_post_id' => $recommendation_id,
                    'elo_value' => $recommendation['elo_value']
                ];
            }
        }

        $contexts[$context] = true; // All updated contexts
    }

    if ($full_refresh) { // Perform a full refresh if requested for any remaining contexts
        foreach ($contexts as $context => $is_updated) {
            if (!$is_updated) {
                $local_storage_path = $json_storage_path . 'context-' . $context . '.json';
                refresh_json_with_top_recommendations($table_name, $context, $local_storage_path, $local_storage_limit); // Refresh the JSON file with top recommendations
            }
        }
    }

    // Calculate the duration of the update process
    $duration = microtime(true) - $start_time;

    // Log the overall status of the update process
    $status = $batch_update_success ? "Success - All updates processed" : "Failed - Review error logs";
    log_cron_job_execution($status, $duration, $post_type);

    // Schedule the next run based on the configured interval
    $interval = get_option('db_sync_interval_setting_' . $post_type);
    schedule_database_sync_job($post_type, $interval);

    return $batch_update_success;
}

/**
 * Refreshes the JSON file with the top Elo-rated recommendations for a specific context.
 *
 * This function is designed to update the JSON file associated with a given context in the specified post type.
 * It first clears the existing content of the JSON file and then queries the database to fetch the top recommendations
 * based on Elo ratings. The number of top recommendations fetched is determined by the 'local_storage_limit' setting for
 * the post type. These top recommendations are then written to the JSON file, providing a denormalized data structure 
 * for efficient access and use.
 *
 * @param string $post_type The post type for which the recommendations are being refreshed.
 * @param int $context The ID of the context post for which the recommendations are being fetched.
 *
 * The structure of the JSON data is as follows:
 * {
 *     "recommended_post_id1": {"elo_value": elo_value1},
 *     "recommended_post_id2": {"elo_value": elo_value2},
 *     ...
 * }
 * Each key is a recommended post ID, and the associated value is an object containing the Elo rating.
 *
 * @return void The function does not return anything. It updates the JSON file directly.
 */
function refresh_json_with_top_recommendations(
    $table_name,
    $context,
    $local_storage_path,
    $local_storage_limit
) {
    global $wpdb;

    if (!file_exists($local_storage_path)) {
        touch($local_storage_path); // Create the file if it doesn't exist
    }

    // Query to fetch top recommendations
    $results = $wpdb->get_results(
        $wpdb->prepare(

            "SELECT recommended_post_id, recommended_post_elo
            FROM $table_name
            WHERE context_post_id = %d
            ORDER BY recommended_post_elo
            DESC LIMIT %d",

            $context,
            $local_storage_limit
        ),
        ARRAY_A
    );

    // Prepare data for JSON
    $json_data = [];
    foreach ($results as $row) {
        $json_data[$row['recommended_post_id']] = ['elo_value' => (int) $row['recommended_post_elo']];
    }

    if (file_put_contents($local_storage_path, json_encode($json_data))) { // Write to JSON file, overwriting existing content
        return true;
    } else {
        error_log("`refresh_json_with_top_recommendations`: Failed to refresh JSON for context: $context");
        return false;
    }
}


/**
 * Flushes the ELO rating table and clears local JSON storage.
 *
 * This function handles the flushing of the ELO rating table in the database and
 * the deletion of all files in the local JSON storage directory. It is triggered
 * when the "Flush ELO Data" button is clicked on the admin page. After dropping the
 * existing ELO rating table, it reinitializes it and clears the JSON storage to
 * reset the data.
 *
 * @return bool Returns true if the table is successfully reinitialized, false otherwise.
 */
function flush_elo_data($post_type)
{
    global $wpdb;

    // Drop and reinitialize the ELO rating table in the database
    $table_name = $wpdb->prefix . 'elo_rating';
    $wpdb->query("DROP TABLE IF EXISTS $table_name;");
    // Clear all files in the local JSON storage directory
    $json_storage_path = plugin_dir_path(dirname(__FILE__, 1)) . 'local-storage/' . $post_type . '/';

    $files = glob($json_storage_path . '*'); // Get all file names
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file); // Delete file
        }
    }
    error_log($post_type);
    return initialize_elo_rating_table($post_type);
}
