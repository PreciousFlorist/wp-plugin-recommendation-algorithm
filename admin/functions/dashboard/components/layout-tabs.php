<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function post_elo_render_tabs()
{
    $current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';

    // Tab for general settings
    echo '<a href="' . admin_url('admin.php?page=post-elo-settings&tab=general') . '" class="nav-tab ' . ($current_tab == 'general' ? 'nav-tab-active' : '') . '">General Settings</a>';

    // Tabs for each post type
    $enabled_post_types = get_option('post_elo_enabled_post_types', []);
    foreach ($enabled_post_types as $post_type) {
        echo '<a href="' . admin_url('admin.php?page=post-elo-settings&tab=' . $post_type) . '" class="nav-tab ' . ($current_tab == $post_type ? 'nav-tab-active' : '') . '">' . ucfirst($post_type) . '</a>';
    }
}
