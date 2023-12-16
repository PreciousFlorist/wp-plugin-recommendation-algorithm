<?php
// Check if the file is being accessed directly
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
require_once plugin_dir_path(dirname((__FILE__), 2)) . 'database/updates/schedule/cron-log.php';
require_once plugin_dir_path(dirname((__FILE__), 2)) . 'database/updates/schedule/cron-management.php';

function table_cron_logs()
{
    // Timer data for next cron event
    $sync_data = calculate_next_sync_time();
    $initialCountdownText = $sync_data['time_remaining_formatted'];
    $cron_logs = get_recent_cron_logs();

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
                <p style="margin-top: 10px;">The next backup will occur in: <span id="countdownTimer" style="font-family: monospace;"><?php echo $initialCountdownText; ?></span> </p>
            </div>
        </div>
<?php
    endif;
}
