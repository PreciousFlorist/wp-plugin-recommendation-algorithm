<?php

/**
 * Fetches the most recent logs from the cron log JSON file.
 *
 * @return array An array of the most recent log entries.
 */
function get_recent_cron_logs()
{
    $log_file = plugin_dir_path(__FILE__) . 'cron_log.json';

    if (file_exists($log_file)) {
        $logs = json_decode(file_get_contents($log_file), true);
        if (!is_array($logs)) {
            return []; // Return an empty array if the JSON is invalid
        }
        return array_slice($logs, 0, 5); // Return the five most recent logs
    }

    return []; // Return an empty array if the file doesn't exist
}
