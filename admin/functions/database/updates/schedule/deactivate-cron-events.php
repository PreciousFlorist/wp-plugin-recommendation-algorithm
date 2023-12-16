<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Clears the scheduled cron job on plugin deactivation.
 */
function post_elo_deactivate_cron_job()
{
    $timestamp = wp_next_scheduled('db_sync_cron_action');
    wp_unschedule_event($timestamp, 'db_sync_cron_action');
}
