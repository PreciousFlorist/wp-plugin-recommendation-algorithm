<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


/**
 * Enqueues the Elo interaction script and localizes it.
 *
 * This function is responsible for loading the JavaScript file that handles the Elo rating interactions.
 * It enqueues the script, ensuring it is loaded on the front-end of the WordPress site.
 * Additionally, it uses `wp_localize_script` to provide the script with the URL for the AJAX calls to `admin-ajax.php`.
 * This setup enables the JavaScript to make AJAX requests to the WordPress backend.
 *
 * The script is enqueued with the handle 'elo-interaction', and the script URL is dynamically determined
 * based on the plugin directory structure.
 *
 * Usage: This function is hooked into WordPress's `wp_enqueue_scripts` action, which handles script loading.
 */
function enqueue_elo_script()
{
    // Enqueue the JavaScript file that handles Elo interactions.
    wp_enqueue_script('elo-interaction', plugins_url('js/elo-interactions.js', dirname(__FILE__, 2)), array(), null, true);

    // Localize the script with data needed for AJAX requests.
    // This creates a JavaScript object that can be used in the 'elo-interactions.js' script.
    // 'ajax_object' contains 'ajax_url', which is the URL to WordPress's AJAX handler (`admin-ajax.php`).
    wp_localize_script('elo-interaction', 'ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_elo_script');
