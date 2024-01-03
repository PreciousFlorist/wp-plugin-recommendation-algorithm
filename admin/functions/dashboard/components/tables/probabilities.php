<?php
// Check if the file is being accessed directly
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once plugin_dir_path(dirname(__FILE__, 1)) . 'forms/actions/fetch-top-recommendations.php';
require_once plugin_dir_path(dirname((__FILE__), 1)) . 'forms/actions/sync-database.php';

function table_probabilities($post_type)
{
    // Default placeholder values for probability chart
    $stored_context_post_id = get_option('post_elo_last_context_id_' . $post_type);
    $stored_num_recommendations = get_option('post_elo_last_num_recommendations_' . $post_type);
    // Fetch the current limit from the options table or set a default value
    $current_limit = get_option('local_storage_limit_' . $post_type);
?>

    <div class="postbox">
        <h2 class="hndle"><span>View Probabilities</span></h2>
        <div style="display:flex; gap: 20px;">
            <div class="inside">
                <?= form_fetch_recommendations($stored_context_post_id, $stored_num_recommendations, $current_limit, $post_type) ?>
                <?= probabilities_output($stored_context_post_id, $stored_num_recommendations, $post_type) ?>
            </div>
        </div>
    </div>

<?php
}

/**
 * Generates the HTML output for a probabilities table.
 * This function creates a table that displays probabilities and other related data
 * for the provided context and post type. It's used within the table_probabilities
 * function to render the final output on the settings page.
 *
 * @param int $stored_context_post_id The default context ID to be used.
 * @param int $stored_num_recommendations The default number of recommendations.
 * @param string $post_type The post type for which the probabilities are being calculated.
 * @return string The HTML content of the probabilities table.
 */

function probabilities_output($stored_context_post_id, $stored_num_recommendations, $post_type)
{

    // Retrieve the last used values, or use default values
    $context_post_id = get_option('post_elo_last_context_id_' . $post_type, $stored_context_post_id);
    $num_recommendations = get_option('post_elo_last_num_recommendations_' . $post_type, $stored_num_recommendations);

    // Call the function to get the recommendations based on stored or default values
    $posts = get_top_recommendations($context_post_id, $num_recommendations, $post_type);

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
