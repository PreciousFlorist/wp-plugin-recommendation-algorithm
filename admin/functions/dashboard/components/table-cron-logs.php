<?php
// Check if the file is being accessed directly
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
require_once plugin_dir_path(dirname((__FILE__), 2)) . 'database/updates/schedule/cron-log.php';
require_once plugin_dir_path(dirname((__FILE__), 2)) . 'database/updates/schedule/cron-management.php';

function table_cron_logs($post_type)
{
    // Timer data for next cron event
    $initial_countdown_text = calculate_next_sync_time($post_type);
    $cron_logs = get_recent_cron_logs($post_type);

    if ($cron_logs) : ?>
        <div class="postbox">
            <h2 class="hndle"><span>Cron Log - Recent Updates</span></h2>
            <div class="inside">



                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Duration</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cron_logs as $log) : ?>
                            <tr>
                                <td><?php echo esc_html($log['date']); ?></td>
                                <td><?php echo esc_html($log['duration']); ?> seconds</td>
                                <td><?php echo esc_html($log['status']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 13px;">
                    <p style="margin: 0px;">The next backup will occur in: <span id="countdownTimer_<?php echo $post_type; ?>" style="font-family: monospace;"><?php echo $initial_countdown_text; ?></span> </p>

                    <form method="post" action="">
                        <?php wp_nonce_field('post_elo_sync_action', 'post_elo_sync_nonce_field'); ?>
                        <input type="hidden" name="post_type" value="<?= esc_attr($post_type); ?>" />
                        <input type="submit" name="sync_database" class="button button-secondary" value="Backup Now" />
                    </form>
                </div>
            </div>
        </div>
<?php
    endif;
}
