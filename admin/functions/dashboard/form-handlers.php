<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once plugin_dir_path(dirname(__FILE__, 1)) . '/outputs/get-top-recommendations.php';
require_once plugin_dir_path(dirname(__FILE__, 1)) . '/database/updates/database-operations.php';
require_once plugin_dir_path(dirname(__FILE__, 1)) . '/database/updates/schedule/cron-management.php';

function admin_form_submission()
{

    if (!current_user_can('manage_options')) {
        return;
    }

    // Flush ELO table form submission
    if (isset($_POST['post_elo_flush_nonce_field']) && wp_verify_nonce($_POST['post_elo_flush_nonce_field'], 'post_elo_flush_action')) {
        if (isset($_POST['flush_elo_table'])) {
            $flush = flush_elo_data();
            return flush_confirmation($flush);
        }
    }

    // Sync database form submission
    if (isset($_POST['post_elo_sync_nonce_field']) && wp_verify_nonce($_POST['post_elo_sync_nonce_field'], 'post_elo_sync_action')) {
        if (isset($_POST['sync_database'])) {

            $sync = sync_json_to_database();
            return json_confirmation($sync);
        }
    }

    // Cron job schedule:
    if (isset($_POST['post_elo_update_cron_nonce']) && wp_verify_nonce($_POST['post_elo_update_cron_nonce'], 'post_elo_update_cron_action')) {
        if (isset($_POST['update_cron']) && isset($_POST['cron_start_time']) && isset($_POST['db_sync_interval_setting'])) {

            // Standardize interval and timestamp values
            $interval = intval($_POST['db_sync_interval_setting']) * 3600;
            $timestamp = DateTime::createFromFormat('Y-m-d\TH:i', $_POST['cron_start_time'])->getTimestamp();

            schedule_database_sync_job($interval, $timestamp);
        }
    }

    // Get top recommendations form submission
    if (isset($_POST['post_elo_get_recommendations_nonce_field']) && wp_verify_nonce($_POST['post_elo_get_recommendations_nonce_field'], 'post_elo_get_recommendations_action')) {
        if (isset($_POST['get_top_recommendations']) && isset($_POST['context_post_id']) && isset($_POST['num_recommendations'])) {

            // Update stored values with the most recent input
            update_option('post_elo_last_context_id', $_POST['context_post_id']);
            update_option('post_elo_last_num_recommendations', $_POST['num_recommendations']);

            // Call the probabilities_output with the new values
            // return probabilities_output('post_elo_last_context_id', 'post_elo_last_num_recommendations');
        }
    }
}

// /*------------------------------
// # Output probabilities 
// ------------------------------*/
function probabilities_output($default_context_id = 1, $default_num_recommendations = 4)
{

    // Retrieve the last used values, or use default values
    $context_post_id = get_option('post_elo_last_context_id', $default_context_id);
    $num_recommendations = get_option('post_elo_last_num_recommendations', $default_num_recommendations);

    // Call the function to get the recommendations based on stored or default values
    $posts = get_top_recommendations($context_post_id, $num_recommendations);

    if (!empty($posts)) {
        $probabilities = '<table class="wp-list-table widefat fixed striped" style="margin-top: 13px;">';
        $probabilities .= '<thead><tr><th>ID</th><th>Elo Rating</th>';

        // Table Headers for rival IDs
        foreach ($posts as $post) {
            $probabilities .= '<th>vs ' . esc_html($post["recommendation_id"]) . '</th>';
        }

        $probabilities .= '<th>Probability to win</th><th>Elo if Win</th><th>Elo if Loss</th></tr></thead>';
        $probabilities .= '<tbody>';

        // Table Rows
        foreach ($posts as $row_post) {
            $current_elo = isset($row_post['recommendation_elo']) ? $row_post['recommendation_elo'] : 'N/A';
            $elo_win = isset($row_post['elo_adjustments']['win']) ? $row_post['elo_adjustments']['win'] : 'N/A';
            $elo_loss = isset($row_post['elo_adjustments']['loss']) ? $row_post['elo_adjustments']['loss'] : 'N/A';

            $probabilities .= '<tr><td>' . esc_html($row_post['recommendation_id']) . '</td><td>' . esc_html($current_elo) . '</td>';

            // Table data for rival IDs
            foreach ($posts as $col_post) {
                $col_id = $col_post['recommendation_id'];

                if ($row_post['recommendation_id'] == $col_id) {
                    $probabilities .= '<td>-</td>';
                } else {
                    $chance = isset($row_post['win_chance'][$col_id]) ? round($row_post['win_chance'][$col_id] * 100, 2) . '%' : 'N/A';
                    $probabilities .= '<td>' . esc_html($chance) . '</td>';
                }
            }

            $joint_win_probability = isset($row_post['joint_win_probability']) ? round($row_post['joint_win_probability'] * 100, 2) . '%' : 'N/A';
            $probabilities .= '<td>' . esc_html($joint_win_probability) . '</td>';
            $probabilities .= '<td>' . esc_html($elo_win) . '</td><td>' . esc_html($elo_loss) . '</td></tr>';
        }

        $probabilities .= '</tbody></table>';
    } else {
        $probabilities = '<div class="notice notice-error"><p>No recommendations found.</p></div>';
    }


    return $probabilities;
}

// /*------------------------------------------------------------
// # Update JSON values
// ------------------------------------------------------------*/
function json_confirmation($sync)
{
    ($sync)
        ? $notice = '<div class="updated notice"><p>Successfully synced JSON data to database.</p></div>' :
        $notice = '<div class="error notice"><p>Error decoding JSON data and updating SQL database.</p></div>';

    return $notice;
}

function flush_confirmation($sync)
{
    ($sync)
        ? $notice = '<div class="updated notice"><p>Successfully flushed Elo values from JSON data SQL datasets.</p></div>' :
        $notice = '<div class="updated notice"><p>Unable to flush Elo values from JSON data SQL datasets.</p></div>';

    return $notice;
}
