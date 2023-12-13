<?php
// Register admin page
function post_elo_admin_menu()
{
    add_menu_page(
        'Post ELO Settings', // Page title
        'Post ELO', // Menu title
        'manage_options', // Capability
        'post-elo-settings', // Menu slug
        'post_elo_settings_page' // Callback function
    );
}
add_action('admin_menu', 'post_elo_admin_menu');

// Display the admin settings page
function post_elo_settings_page()
{
?>
    <div class="wrap">
        <h1>Post ELO Settings:</h1>

        <!-- Form to flush database table -->
        <form method="post" action="">
            <?php wp_nonce_field('post_elo_update_action', 'post_elo_nonce_field'); ?>
            <input type="submit" name="flush_elo_table" value="Flush ELO Database Table" class="button-primary" />
        </form>
        <!-- Get Recommendations -->
        <form method="post" action="">
            <h2>Get Top Recommendations</h2>
            <p>Enter the context post ID and the number of recommendations:</p>
            <label for="context_post_id">Context Post ID:</label>
            <input type="number" id="context_post_id" name="context_post_id" placeholder="Context Post ID" required value="1" />
            <label for="num_recommendations">Number of Recommendations:</label>
            <input type="number" id="num_recommendations" name="num_recommendations" placeholder="Number of Recommendations" min="2" required value="4" />
            <input type="submit" name="get_top_recommendations" value="Get Recommendations" class="button-secondary" />
        </form>
    </div>
<?php
    if (isset($_POST['get_top_recommendations'])) {
        handle_probability_calculation();
    }
}

add_action('admin_init', 'post_elo_handle_form_submission');

// Handle the form submission for probability calculation
function handle_probability_calculation()
{
    $start_time = microtime(true);

    calculate_and_display_probabilities();

    $execution_time = microtime(true) - $start_time;
    echo '<div class="updated notice"><p>Calculation completed in ' . number_format($execution_time, 2) . ' seconds.</p></div>';
}
add_action('admin_init', 'post_elo_handle_form_submission');

// Handle the form submission for flushing database
function post_elo_handle_form_submission()
{
    if (!current_user_can('manage_options') || !isset($_POST['post_elo_nonce_field']) || !wp_verify_nonce($_POST['post_elo_nonce_field'], 'post_elo_update_action')) {
        return;
    }

    if (isset($_POST['flush_elo_table'])) {
        flush_elo_table();
    }
}


// Flush the ELO rating table
function flush_elo_table()
{
    $start_time = microtime(true);

    drop_elo_rating_table();
    $success = initialize_elo_rating_table();

    $execution_time = microtime(true) - $start_time;
    $message_class = $success ? 'updated' : 'error';
    $message_text = $success ? 'ELO database table flushed and repopulated successfully in ' . number_format($execution_time, 2) . ' seconds.' : 'Error repopulating the ELO database table.';

    echo "<div id='message' class='$message_class notice is-dismissible'><p>$message_text</p></div>";
}

// Drop the ELO rating table
function drop_elo_rating_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'elo_rating';
    $wpdb->query("DROP TABLE IF EXISTS $table_name;");
}

/*------------------------------
# Calculate probability 
------------------------------*/
require_once plugin_dir_path(__FILE__) . '../functions/database/outputs/get-top-recommendations.php';

function calculate_and_display_probabilities()
{
    if (isset($_POST['context_post_id'], $_POST['num_recommendations']) && is_numeric($_POST['context_post_id']) && is_numeric($_POST['num_recommendations'])) {

        $context_post_id = $_POST['context_post_id'];
        $num_recommendations = $_POST['num_recommendations'];

        $posts = get_top_recommendations($context_post_id, $num_recommendations);

        if (!empty($posts)) {
            echo '<div class="wrap"><h3>Probability and Elo Rating Adjustment Results:</h3>';
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>ID</th><th>Elo Rating</th>';

            // Table Headers for rival IDs
            foreach ($posts as $post) {
                echo '<th>vs ' . esc_html($post["recommendation_id"]) . '</th>';
            }

            echo '<th>Probability to win</th><th>Elo if Win</th><th>Elo if Loss</th></tr></thead>';
            echo '<tbody>';

            // Table Rows
            foreach ($posts as $row_post) {
                $current_elo = isset($row_post['recommendation_elo']) ? $row_post['recommendation_elo'] : 'N/A';
                $elo_win = isset($row_post['elo_adjustments']['win']) ? $row_post['elo_adjustments']['win'] : 'N/A';
                $elo_loss = isset($row_post['elo_adjustments']['loss']) ? $row_post['elo_adjustments']['loss'] : 'N/A';

                echo '<tr><td>' . esc_html($row_post['recommendation_id']) . '</td><td>' . esc_html($current_elo) . '</td>';

                // Table data for rival IDs
                foreach ($posts as $col_post) {
                    $col_id = $col_post['recommendation_id'];

                    if ($row_post['recommendation_id'] == $col_id) {
                        echo '<td>-</td>';
                    } else {
                        $chance = isset($row_post['win_chance'][$col_id]) ? round($row_post['win_chance'][$col_id] * 100, 2) . '%' : 'N/A';
                        echo '<td>' . esc_html($chance) . '</td>';
                    }
                }

                $joint_win_probability = isset($row_post['joint_win_probability']) ? round($row_post['joint_win_probability'] * 100, 2) . '%' : 'N/A';
                echo '<td>' . $joint_win_probability . '</td>';
                echo '<td>' . esc_html($elo_win) . '</td><td>' . esc_html($elo_loss) . '</td></tr>';
            }

            echo '</tbody></table>';
            echo '</div>';
        } else {
            echo '<div class="error notice"><p>No recommendations found.</p></div>';
        }
    }
}
