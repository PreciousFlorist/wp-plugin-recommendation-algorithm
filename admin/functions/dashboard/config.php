<?php
if ( ! defined( 'ABSPATH' ) ) {
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