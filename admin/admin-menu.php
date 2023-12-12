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

        <!-- Form for calculating winning probabilities -->
        <form method="post" action="">
            <h2>Calculate Winning Probabilities</h2>
            <p>Enter the context post ID and up to four rival post IDs:</p>
            <input type="text" name="context_post_id" placeholder="Context Post ID" value="1" />

            <?php
            $default_recomm_ids = array(163, 150, 51, 34); // Default recommendation IDs
            for ($i = 0; $i < 4; $i++) {
                $value = isset($default_recomm_ids[$i]) ? $default_recomm_ids[$i] : '';
                echo "<input type='text' name='post_ids[]' placeholder='Rival Post ID " . ($i + 1) . "' value='$value' />";
            }
            ?>

            <input type="submit" name="calculate_probabilities" value="Calculate Probabilities" class="button-secondary" />
        </form>
    </div>
<?php
    if (isset($_POST['calculate_probabilities'])) {
        handle_probability_calculation();
    }
}
add_action('admin_init', 'post_elo_handle_form_submission');

// Handle the form submission for probability calculation
function handle_probability_calculation() {
    $start_time = microtime(true);

    calculate_and_display_probabilities();

    $execution_time = microtime(true) - $start_time;
    echo '<div class="updated notice"><p>Calculation completed in ' . number_format($execution_time, 2) . ' seconds.</p></div>';
}
add_action('admin_init', 'post_elo_handle_form_submission');

// Handle the form submission for flushing database
function post_elo_handle_form_submission() {
    if (!current_user_can('manage_options') || !isset($_POST['post_elo_nonce_field']) || !wp_verify_nonce($_POST['post_elo_nonce_field'], 'post_elo_update_action')) {
        return;
    }

    if (isset($_POST['flush_elo_table'])) {
        flush_elo_table();
    }
}


// Flush the ELO rating table
function flush_elo_table() {
    $start_time = microtime(true);

    drop_elo_rating_table();
    $success = initialize_elo_rating_table();

    $execution_time = microtime(true) - $start_time;
    $message_class = $success ? 'updated' : 'error';
    $message_text = $success ? 'ELO database table flushed and repopulated successfully in ' . number_format($execution_time, 2) . ' seconds.' : 'Error repopulating the ELO database table.';

    echo "<div id='message' class='$message_class notice is-dismissible'><p>$message_text</p></div>";
}

// Drop the ELO rating table
function drop_elo_rating_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'elo_rating';
    $wpdb->query("DROP TABLE IF EXISTS $table_name;");
}

/*------------------------------
# Calculate probability 
------------------------------*/
require_once plugin_dir_path(__FILE__) . '../functions/calculations/winning-probability.php';

function calculate_and_display_probabilities()
{
    if (isset($_POST['context_post_id'], $_POST['post_ids']) && is_numeric($_POST['context_post_id']) && is_array($_POST['post_ids'])) {
        $context_post_id = $_POST['context_post_id'];
        $post_ids = array_filter($_POST['post_ids'], function ($id) {
            return !empty($id) && is_numeric($id);
        });

        if (count($post_ids) >= 2) {
            $probabilities = probability_of_win($context_post_id, $post_ids);

            echo '<div class="wrap"><h3>Probability and Elo Rating Adjustment Results:</h3>';

            // Table Header
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>ID</th><th>Elo Rating</th>';

            foreach ($post_ids as $id) {
                echo '<th>vs ' . esc_html($id) . '</th>';
            }

            echo '<th>Probability to win</th><th>Elo if Win</th><th>Elo if Loss</th></tr></thead>';
            echo '<tbody>';

            // Table Rows
            foreach ($post_ids as $row_id) {
                $current_elo = isset($probabilities[$row_id]) ? $probabilities[$row_id]['recommendation_elo'] : 'N/A';
                $elo_win = isset($probabilities[$row_id]['elo_adjustments']['win']) ? $probabilities[$row_id]['elo_adjustments']['win'] : 'N/A';
                $elo_loss = isset($probabilities[$row_id]['elo_adjustments']['loss']) ? $probabilities[$row_id]['elo_adjustments']['loss'] : 'N/A';

                echo '<tr><td>' . esc_html($row_id) . '</td><td>' . esc_html($current_elo) . '</td>';

                foreach ($post_ids as $col_id) {
                    if ($row_id == $col_id) {
                        echo '<td>-</td>';
                    } else {
                        $chance = isset($probabilities[$row_id]['win_chance'][$col_id]) ? round($probabilities[$row_id]['win_chance'][$col_id] * 100, 2) : 'N/A';
                        echo '<td>' . esc_html($chance) . '%</td>';
                    }
                }

                $mean_probability = isset($probabilities[$row_id]['joint_win_probability']) ? round($probabilities[$row_id]['joint_win_probability'] * 100, 2) . '%' : 'N/A';
                echo '<td>' . $mean_probability . '</td>';
                echo '<td>' . esc_html($elo_win) . '</td><td>' . esc_html($elo_loss) . '</td></tr>';
            }

            echo '</tbody></table>';
            echo '</div>';
        } else {
            echo '<div class="error notice"><p>Please enter at least two valid rival post IDs.</p></div>';
        }
    }
}

