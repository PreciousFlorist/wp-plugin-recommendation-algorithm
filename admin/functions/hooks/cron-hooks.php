<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Retrieve the list of post types that have Elo rating enabled.
$post_types = get_option('post_elo_enabled_post_types', []);

// error_log('Enabled Post Types: ' . print_r($post_types, true));


// Register a cron action for each enabled post type.
foreach ($post_types as $post_type) {
    add_action('elo_sync_db_cron_action_' . $post_type, 'sync_json_to_database', 10, 1);
}
