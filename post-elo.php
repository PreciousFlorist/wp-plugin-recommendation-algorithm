<?php
// Make sure we don't expose any info if called directly
if (!defined('ABSPATH')) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit; // Exit if accessed directly
}

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


// Initialization
require_once plugin_dir_path(__FILE__) . 'admin/functions/database/initialize.php';
register_activation_hook(__FILE__, 'initialize_elo_rating_table');

// Deactivation
require_once plugin_dir_path(__FILE__) . 'admin/functions/database/updates/schedule/deactivate-cron-events.php';
register_deactivation_hook(__FILE__, 'post_elo_deactivate_cron_job');

// Admin page
require_once plugin_dir_path(__FILE__) . 'admin/admin-menu.php';

// Public functions
require_once plugin_dir_path(__FILE__) . 'public/functions/shortcodes/elo-shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'public/functions/utilities/ajax-handlers.php';
require_once plugin_dir_path(__FILE__) . 'public/functions/utilities/script-loader.php';

// Enqueue scripts
require_once plugin_dir_path(__FILE__) . 'admin/functions/enqueue/scripts.php';