<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once plugin_dir_path((__FILE__)) . 'updates/schedule/cron-management.php';

/**
 * Handles various cleanup tasks on plugin deactivation, such as clearing scheduled cron jobs,
 * dropping Elo rating tables, clearing local storage directories, and deleting registered options.
 */
function post_elo_cleanup_on_deactivation()
{
    clear_scheduled_cron_jobs();
    drop_elo_rating_tables();
    clear_local_storage_directories();
    delete_registered_options();
}

/**
 * Clears all scheduled cron jobs for the plugin. If a specific post type is provided,
 * only the cron jobs for that type are cleared. Otherwise, it clears cron jobs for all public post types.
 * 
 * @param string|null $post_type The specific post type to clear cron jobs for, or null for all public post types.
 */
function clear_scheduled_cron_jobs($post_type = null)
{
    if ($post_type) {
        error_log("Clearing scheduled cron jobs for post type: $post_type");
        destroy_scheduled_hook('elo_sync_db_cron_action_' . $post_type);
        remove_action('elo_sync_db_cron_action_' . $post_type, 'sync_json_to_database');
    } else {
        $post_types = get_post_types(['public' => true], 'names');
        foreach ($post_types as $post_type) {
            error_log("Clearing scheduled cron jobs for post type: $post_type");
            destroy_scheduled_hook('elo_sync_db_cron_action_' . $post_type);
            remove_action('elo_sync_db_cron_action_' . $post_type, 'sync_json_to_database');
        }
    }
}

/**
 * Deletes local storage directories for each post type. If a specific post type is provided,
 * only the directory for that type is deleted. Otherwise, it deletes directories for all public post types.
 * 
 * @param string|null $post_type The specific post type to delete storage for, or null for all public post types.
 */
function clear_local_storage_directories($post_type = null)
{
    if ($post_type) {
        error_log("Deleting local storage for post type: $post_type");
        $storage_directory = plugin_dir_path(dirname(__FILE__, 3)) . 'local-storage/' . $post_type;
        if (file_exists($storage_directory)) {
            delete_directory($storage_directory);
        }
    } else {
        $post_types = get_post_types(['public' => true], 'names');
        foreach ($post_types as $post_type) {

            $storage_directory = plugin_dir_path(dirname(__FILE__, 3)) . 'local-storage/' . $post_type;
            error_log("Deleting local storage for post type: $post_type\n $storage_directory");

            if (file_exists($storage_directory)) {
                delete_directory($storage_directory);
            }
        }
    }
}

/**
 * Recursively deletes a directory and its contents. Throws an exception if the provided path is not a directory.
 * 
 * @param string $dirPath Path of the directory to delete.
 * @throws InvalidArgumentException If the provided path is not a directory.
 */
function delete_directory($dirPath)
{
    if (!is_dir($dirPath)) {
        error_log("$dirPath is not a directory");
        throw new InvalidArgumentException("$dirPath must be a directory");
    }

    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
        $dirPath .= '/';
    }

    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
        error_log("Deleting $file");
        if (is_dir($file)) {
            delete_directory($file);
        } else {
            unlink($file);
        }
    }

    rmdir($dirPath);
}

/**
 * Drops Elo rating tables from the database for each post type.
 */
function drop_elo_rating_tables($post_type = null)
{
    global $wpdb;

    if ($post_type) {
        error_log("Dropping elo_rating_$post_type table");
        $table_name = $wpdb->prefix . 'elo_rating_' . $post_type;
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
        return;
    } else {

        $post_types = get_post_types(['public' => true], 'names');

        foreach ($post_types as $post_type) {
            error_log("Dropping elo_rating_$post_type table");
            $table_name = $wpdb->prefix . 'elo_rating_' . $post_type;
            $wpdb->query("DROP TABLE IF EXISTS $table_name");
        }
    }
}

/**
 * Deletes all options registered by the plugin in the WordPress database.
 */
function delete_registered_options($post_type = null)
{
    if ($post_type) {
        delete_option('elo_enabled_cpts_' . $post_type);
        delete_option('local_storage_limit_' . $post_type);
    } else {
        delete_option('post_elo_enabled_post_types');
        delete_option('post_elo_initialized_post_types');

        foreach (get_post_types(['public' => true], 'names') as $post_type) {
            delete_option('elo_enabled_cpts_' . $post_type);
            delete_option('local_storage_limit_' . $post_type);
        }
    }
}
