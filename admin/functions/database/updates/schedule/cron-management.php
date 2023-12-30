<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once plugin_dir_path(dirname((__FILE__), 1)) . 'database-operations.php';

/**
 * Schedules the database synchronization cron job.
 *
 * @param int $interval  The interval in seconds at which the cron job should run.
 * @param int $timestamp The timestamp at which the cron job should first run.
 */
function schedule_database_sync_job($post_type, $interval = 'hourly')
{

    $hook_name = 'elo_sync_db_cron_action_' . $post_type;
    destroy_scheduled_hook($hook_name);

    $timestamp = time();

    switch ($interval) {
        case 'hourly':
            $unformatted_interval = 3600; // 1 hour = 3600 seconds
            break;

        case 'twicedaily':
            $unformatted_interval = 43200; // 12 hours = 43200 seconds
            break;

        case 'daily':
            $unformatted_interval = 86400; // 24 hours = 86400 seconds
            break;
    }

    $run_time = $timestamp + $unformatted_interval;

    wp_schedule_event($run_time, $interval, $hook_name, array($post_type), true);

    $next_scheduled = wp_next_scheduled($hook_name, array($post_type));
    update_option('elo_cron_next_run_' . $post_type, $next_scheduled);
}

function destroy_scheduled_hook($hook_name)
{
    $crons = _get_cron_array();
    if (isset($crons) && is_array($crons)) {
        foreach ($crons as $timestamp => $cron) {
            if (isset($cron[$hook_name])) {
                foreach ($cron[$hook_name] as $key => $event) {
                    wp_unschedule_event($timestamp, $hook_name, $event['args']);
                }
            }
        }
    }
}

/**
 * Calculates the next synchronization time and the formatted time remaining.
 *
 * @return array Contains formatted next sync time and formatted time remaining.
 */
function calculate_next_sync_time($post_type)
{

    $next_update = get_option('elo_cron_next_run_' . $post_type);

    if (!$next_update) {
        error_log("No cron job scheduled for post type: " . $post_type);
        return; // Exit the function if no cron job is scheduled
    }

    $remaining_time = $next_update - time();

    $inline_script = "var postEloData_" . esc_js($post_type) . " = " . json_encode(array('timeRemaining' => $remaining_time)) . ";";
    wp_enqueue_script('post-elo-cron-timer', plugin_dir_url(dirname(__FILE__, 4)) . 'js/cron-timer.js', array(), '0.0.1', true);
    wp_add_inline_script('post-elo-cron-timer', $inline_script);

    return format_time_remaining($remaining_time);
}

function format_time_remaining($seconds)
{
    $days = floor($seconds / 86400);
    $hours = floor(($seconds % 86400) / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $seconds = $seconds % 60;

    $formatted = '';
    if ($days > 0) $formatted .= $days . ' day ';
    if ($hours > 0) $formatted .= $hours . ' hours ';
    if ($minutes > 0) $formatted .= $minutes . ' minutes ';
    if ($seconds > 0) $formatted .= $seconds . ' seconds';

    return $formatted;
}

/**
 * Logs the execution of the database synchronization cron job.
 *
 * @param bool $status      The status of the cron job execution (success or failure).
 * @param float $duration   The duration of the cron job execution in seconds.
 */
function log_cron_job_execution($status, $duration, $post_type)
{

    $log_file = plugin_dir_path(__FILE__) . 'cron_log_' . $post_type . '.json';

    if (!file_exists($log_file)) {
        touch($log_file); // Create the file if it doesn't exist
    }

    $logs = json_decode(file_get_contents($log_file), true) ?: [];

    array_unshift($logs, [
        'date'     => date('Y-m-d H:i:s', current_time('timestamp')),
        'duration' => number_format($duration, 2),
        'status'   => $status
    ]);

    // Keep only the last 25 logs
    $logs = array_slice($logs, 0, 25);

    file_put_contents($log_file, json_encode($logs, JSON_PRETTY_PRINT));

    (!$log_file) ? error_log("`log_cron_job_execution`: Could not update log file: " . $log_file) : null;
    (!$logs) ? error_log("`log_cron_job_execution`: Could not fetch most recent logs: " . print_r($logs, true)) : null;

    return ($log_file && $logs) ? true : false;
}
