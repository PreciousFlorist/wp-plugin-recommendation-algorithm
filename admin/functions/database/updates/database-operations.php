<?php
require_once plugin_dir_path(__FILE__) . 'update-rows.php';
require_once plugin_dir_path(dirname(__FILE__), 2) . 'initialize.php';

/**
 * Synchronizes the database with updated Elo ratings from JSON files.
 *
 * This function is triggered when the "Sync Database" button is clicked. It loops over
 * all context JSON files, checks for the "updated" flag, and accumulates data for a batch
 * SQL update to the database.
 *
 * @return void Outputs messages to the admin page and logs errors as needed.
 */
function sync_json_to_database()
{

    global $wpdb;

    $json_storage_path = plugin_dir_path(__FILE__) . '../../local-storage/';
    $files = glob($json_storage_path . '*.json');
    $batch_update_data = [];

    foreach ($files as $file) {
        $json_content = file_get_contents($file);

        if ($json_content === false) {
            continue; // Skip content can't be read
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

    // Validate batch collection
    if (empty($batch_update_data)) {
        error_log("`sync_json_to_database`: No data to update.");
        return true;
    }

    // Perform batch SQL update
    if (update_elo_ratings($batch_update_data)) {
        error_log("`sync_json_to_database`: Successfully deployed JSON updates to database.");
        return true;
    } else {
        error_log("`sync_json_to_database`: Failed to deploy JSON updates to database.");
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
function flush_elo_data()
{
    global $wpdb;

    // Drop and reinitialize the ELO rating table in the database
    $table_name = $wpdb->prefix . 'elo_rating';
    $wpdb->query("DROP TABLE IF EXISTS $table_name;");
    // Clear all files in the local JSON storage directory
    $json_storage_path = plugin_dir_path(__FILE__) . '../local-storage/';

    $files = glob($json_storage_path . '*'); // Get all file names
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file); // Delete file
        }
    }

    return initialize_elo_rating_table();
}
