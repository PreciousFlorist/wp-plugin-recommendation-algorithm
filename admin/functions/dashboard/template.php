<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Renders the settings page for Post ELO plugin in the WordPress admin.
 */

require_once plugin_dir_path(__FILE__) . '/components/form-flush-data.php';
require_once plugin_dir_path(__FILE__) . '/components/form-sync-data.php';
require_once plugin_dir_path(__FILE__) . '/components/form-cron-schedule.php';

require_once plugin_dir_path(__FILE__) . '/components/table-cron-logs.php';
require_once plugin_dir_path(__FILE__) . '/components/table-probabilities.php';

function post_elo_settings_page()
{

    admin_form_submission();

?>

    <div class="wrap" style="display: flex; flex-direction: column; gap: 10px;">
        <h1>Hello world</h1>

        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <!-- Main -->
                <div id="post-body-content">
                    <?php
                    echo table_cron_logs();
                    echo table_probabilities();
                    ?>
                </div>
                <!-- Sidebar -->
                <div id="postbox-container-1" class="postbox-container">
                    <?php
                    echo form_flush_all();
                    echo form_sync_all();
                    echo form_cron_schedule();
                    ?>
                </div>
            </div>
        </div>
    </div>
<?php
}
