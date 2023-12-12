<?php

/**
 * @package post-elo
 * @version 0.0.1
 */
/**
 * Plugin Name: Post ELO
 * Plugin URI: https://shanewalders.ca/
 * Description: Create an ELO rating for your posts, and dynamically output recommendations based on user engagement rankings.
 * Author: Shane Walders
 * Author URI: https://shanewalders.ca/
 * Version: 0.0.1
 */


// Make sure we don't expose any info if called directly
if (!function_exists('add_action')) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}
// Admin page
require_once plugin_dir_path(__FILE__) . '/admin/admin-menu.php';

// Initialize and populate default elo values
require_once plugin_dir_path(__FILE__) . 'functions/database/initialize.php';
register_activation_hook(__FILE__, 'initialize_elo_rating_table');

