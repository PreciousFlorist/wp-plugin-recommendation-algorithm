<?php
// Check if the file is being accessed directly
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function form_cron_schedule()
{
    require_once plugin_dir_path(dirname(__FILE__, 2)) . '/database/updates/schedule/cron-management.php';

    // Retrieve data needed for the form
    $sync_data = calculate_next_sync_time();
    $next_sync = $sync_data['next_sync_time_formatted'];
?>

    <div class="postbox">
        <h2 class="hndle"><span>Set Cron Schedule</span></h2>
        <div class="inside">
            <form method="post" action="">
                <?php wp_nonce_field('post_elo_update_cron_action', 'post_elo_update_cron_nonce'); ?>
                <p>Specify how frequently you would like to backup changes made to the local Elo storage onto the database.</p>
                <p>
                    <label for="cron_start_time">Next Sync Time:</label>
                    <input type="datetime-local" id="cron_start_time" name="cron_start_time" style="width: 100%; margin-top: 13px;" value="<?php echo esc_attr($next_sync); ?>" required />
                </p>
                <p>
                    <label for="db_sync_interval_setting">Update frequency (in hours):</label>
                    <input type="number" name="db_sync_interval_setting" style="width: 100%; margin-top: 13px;" value="<?php echo esc_attr(get_option('db_sync_interval_setting', 3600) / 3600); ?>" />
                </p>
                <input type="submit" name="update_cron" class="button button-primary" value="Update" />
            </form>
        </div>
    </div>

<?php
}
