<?php

function post_elo_enqueue_admin_scripts()
{
    global $post_elo_admin_page;
    $screen = get_current_screen();

    if ($screen->id !== $post_elo_admin_page) {
        return;
    }

    wp_enqueue_script('post-elo-cron-timer', plugin_dir_url(dirname(__FILE__, 2)) . 'js/cron-timer.js', array(), '0.0.1', true);

    // Ensure the function exists or include the file that defines it
    if (!function_exists('calculate_next_sync_time')) {
        require_once 'path_to_the_file_that_defines_the_function.php';
    }

    $sync_data = calculate_next_sync_time();
    wp_localize_script('post-elo-cron-timer', 'postEloData', array(
        'timeRemaining' => $sync_data['time_remaining']
    ));
}
add_action('admin_enqueue_scripts', 'post_elo_enqueue_admin_scripts');
