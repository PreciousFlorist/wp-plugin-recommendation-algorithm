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
function schedule_database_sync_job($interval, $timestamp)
{
    // Update the interval values
    update_option('db_sync_interval_setting', $interval);
    update_option('next_sync_time', $timestamp);

    // Clear any existing cron jobs for this hook
    wp_clear_scheduled_hook('db_sync_cron_action');
    // Schedule the new cron event using the custom interval
    wp_schedule_event($timestamp, 'custom_db_sync_interval', 'db_sync_cron_action');
}

/**
 * Adds a custom interval for the WordPress cron scheduler.
 *
 * @param array $schedules Current array of scheduled intervals.
 * @return array Modified array of schedules with the custom interval added.
 */
function add_custom_cron_interval($schedules)
{
    $interval = get_option('db_sync_interval_setting', 3600); // Default to 1 hour if no setting is found
    $schedules['custom_db_sync_interval'] = array(
        'interval' => $interval,
        'display' => esc_html__('Interval set by Post ELO Settings', 'post-elo')
    );

    return $schedules;
}
add_filter('cron_schedules', 'add_custom_cron_interval');


/**
 * Logs the execution of the database synchronization cron job.
 *
 * @param bool $status   The status of the cron job execution (success or failure).
 * @param float $duration The duration of the cron job execution in seconds.
 */
function log_cron_job_execution($status, $duration)
{
    $log_file = plugin_dir_path(__FILE__) . 'cron_log.json';
    $logs = [];

    if (file_exists($log_file)) {
        $logs = json_decode(file_get_contents($log_file), true) ?: [];
    }

    array_unshift($logs, [
        'date' => current_time('mysql'),
        'duration' => number_format($duration, 2), // Format to 2 decimal places
        'status' => ($status) ? 'Success' : 'Failed'
    ]);

    // Keep only the last 25 logs
    $logs = array_slice($logs, 0, 25);

    file_put_contents($log_file, json_encode($logs, JSON_PRETTY_PRINT));
}

/**
 * Executes the database synchronization process.
 */
function post_elo_sync_database_cron_job()
{
    $start_time = microtime(true);
    $status = sync_json_to_database();
    $duration = microtime(true) - $start_time;

    log_cron_job_execution($status, $duration);
}
add_action('db_sync_cron_action', 'post_elo_sync_database_cron_job');

/**
 * Calculates the next synchronization time and the formatted time remaining.
 *
 * @return array Contains formatted next sync time and formatted time remaining.
 */
function calculate_next_sync_time()
{
    $next_sync_time = get_option('next_sync_time'); // Get the stored next sync time
    $interval = get_option('db_sync_interval_setting', 3600); // in seconds
    $current_time = current_time('timestamp');

    // Calculate the next sync time only if it's not set or already passed
    if (!$next_sync_time || $current_time > $next_sync_time) {
        $next_sync_time = $current_time + $interval;
        update_option('next_sync_time', $next_sync_time); // Update the stored next sync time
    }

    $time_remaining = $next_sync_time - $current_time;

    // Ensure time remaining is not negative
    $time_remaining = max($time_remaining, 0);

    // Format the time remaining
    $days = floor($time_remaining / 86400);
    $hours = floor(($time_remaining % 86400) / 3600);
    $minutes = floor(($time_remaining % 3600) / 60);
    $seconds = $time_remaining % 60;

    $formatted_time_remaining = '';
    if ($days > 0) {
        $formatted_time_remaining .= $days . ' days ';
    }
    if ($hours > 0) {
        $formatted_time_remaining .= $hours . ' hours ';
    }
    if ($minutes > 0) {
        $formatted_time_remaining .= $minutes . ' minutes ';
    }
    if ($seconds > 0) {
        $formatted_time_remaining .= $seconds . ' seconds';
    }

    return [
        'next_sync_time_formatted' => date('Y-m-d\TH:i', $next_sync_time),
        'time_remaining_formatted' => $formatted_time_remaining,
        'time_remaining' => $time_remaining
    ];
}
