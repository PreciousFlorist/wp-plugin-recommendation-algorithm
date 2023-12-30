<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


// Register admin page
function post_elo_admin_menu()
{
    global $post_elo_admin_page;

    $post_elo_admin_page = add_menu_page(
        'Post ELO Settings', // Page title
        'Post ELO', // Menu title
        'manage_options', // Capability
        'post-elo-settings', // Menu slug
        'post_elo_settings_page' // Callback function
    );
}
add_action('admin_menu', 'post_elo_admin_menu');

// if (!defined('ABSPATH')) {
//     exit; // Exit if accessed directly
// }

// function post_elo_admin_menu()
// {
//     add_menu_page(
//         'Post ELO Settings',
//         'Post ELO',
//         'manage_options',
//         'post-elo-settings',
//         'post_elo_settings_page'
//     );
// }
// add_action('admin_menu', 'post_elo_admin_menu');

// /*------------------------------------------------------------
// # SETTINGS
// ------------------------------------------------------------*/

// function post_elo_init_settings()
// {
//     // Register each setting your forms will submit
//     register_setting('post_elo_options', 'post_elo_batch_size', array(
//         'type' => 'integer',
//         'default' => get_option('post_elo_batch_size') // The same default value as used in the placeholder
//     ));

//     // ... register other settings ...

//     // Add sections to your settings page
//     add_settings_section('post_elo_general_settings', 'General Settings', 'post_elo_general_settings_callback', 'post_elo_settings');
//     // ... add other sections ...

//     // Add fields to your sections
//     add_settings_field('post_elo_batch_size', 'Batch Size', 'post_elo_batch_size_callback', 'post_elo_settings', 'post_elo_general_settings');
//     // ... add other fields ...
// }
// add_action('admin_init', 'post_elo_init_settings');


// function post_elo_general_settings_callback()
// {
//     echo '<p>General settings for Post ELO.</p>';
// }

// function post_elo_batch_size_callback()
// {
//     // Use get_option to retrieve stored values
//     echo "<input type='number' name='post_elo_batch_size' value='" . esc_attr(get_option('post_elo_batch_size')) . "/>";
// }

// /*------------------------------
// # BATCH SIZES
// ------------------------------*/

