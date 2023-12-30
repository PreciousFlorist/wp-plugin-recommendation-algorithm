<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Submission Operations
require_once plugin_dir_path(dirname(__FILE__, 1)) . '/outputs/get-top-recommendations.php';
require_once plugin_dir_path(dirname(__FILE__, 1)) . '/database/updates/database-operations.php';
require_once plugin_dir_path(dirname(__FILE__, 1)) . '/database/updates/schedule/cron-management.php';
require_once plugin_dir_path(dirname(__FILE__, 1)) . '/database/cleanup.php';
require_once plugin_dir_path(dirname(__FILE__, 1)) . '/database/updates/download-all-tables.php';
require_once plugin_dir_path(dirname(__FILE__, 1)) . '/database/initialize.php';

// Validation handlers
require_once plugin_dir_path(__FILE__) . '/form-validations/register-post-types.php';
require_once plugin_dir_path(__FILE__) . '/form-validations/database-drop-tables.php';
require_once plugin_dir_path(__FILE__) . '/form-validations/database-backup-tables.php';
require_once plugin_dir_path(__FILE__) . '/form-validations/database-flush-tables.php';
require_once plugin_dir_path(__FILE__) . '/form-validations/database-sync-tables.php';
require_once plugin_dir_path(__FILE__) . '/form-validations/cron-schedule-time.php';
require_once plugin_dir_path(__FILE__) . '/form-validations/cron-batch-size.php';
require_once plugin_dir_path(__FILE__) . '/form-validations/json-recommendation-pool.php';
require_once plugin_dir_path(__FILE__) . '/form-validations/fetch-top-recommendations.php';

function admin_form_submission()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $notice = null;

    $notice .= handle_register_post_types(); // Get list of approved Post Types
    $notice .= handle_drop_db_tables(); // Drop all database tables
    $notice .= handle_backup_db_tables(); // Backup all database tables
    $notice .= handle_flush_db_tables(); // Flush individual post data
    $notice .= handle_sync_db_tables(); // Sync individual post data
    $notice .= handle_cron_schedule(); // Schedule individual cron job
    $notice .= handle_cron_batch_size(); // Set individual batch size for db sync
    $notice .= handle_json_recommendation_pool(); // Set individual recommendation pool size
    $notice .= fetch_top_recommendations(); // Fetch top recommendations for individual context

    // Return notice when appropriate
    if ($notice) {
        return $notice;
    }
}
