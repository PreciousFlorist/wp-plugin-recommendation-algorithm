<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Page Config
require_once plugin_dir_path(__FILE__) . '/components/layouts/tabs.php';

// Plugin Configuration
require_once plugin_dir_path(__FILE__) . '/components/forms/actions/init-post-types.php';
require_once plugin_dir_path(__FILE__) . '/components/forms/actions/drop-all-records.php';
require_once plugin_dir_path(__FILE__) . '/components/forms/actions/backup-all-records.php';

// Individual Post Components
require_once plugin_dir_path(__FILE__) . '/components/forms/actions/flush-data.php';
require_once plugin_dir_path(__FILE__) . '/components/forms/options/cron-schedule.php';
require_once plugin_dir_path(__FILE__) . '/components/forms/options/recommendation-pool.php';
require_once plugin_dir_path(__FILE__) . '/components/forms/options/post-batch-size.php';
// Tables
require_once plugin_dir_path(__FILE__) . '/components/tables/cron-logs.php';
require_once plugin_dir_path(__FILE__) . '/components/tables/probabilities.php';

function post_elo_settings_page()
{
    // Active tab
    $current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
    // Retrieve enabled post types
    $current_post_type = $current_tab !== 'general' ? $current_tab : null;
    $notice = admin_form_submission();
?>

    <div class="wrap" style="display: flex; flex-direction: column; gap: 10px;">
        <h1>Dynamic Content Recommendations</h1>

        <?php if (!empty($notice)) echo $notice; ?>

        <!-- Post Type Tabs -->
        <h2 class="nav-tab-wrapper">
            <?php post_elo_render_tabs(); ?>
        </h2>

        <div id="poststuff">

            <div id="post-body" class="metabox-holder columns-2">
                <!-- Main -->
                <div id="post-body-content">

                    <?php
                    if ($current_tab == 'general') {
                        echo register_approved_post_types();
                    } else {
                        echo table_probabilities($current_post_type);
                        echo table_cron_logs($current_post_type);
                    }
                    ?>
                </div>
                <!-- Sidebar -->
                <div id="postbox-container-1" class="postbox-container">
                    <?php
                    if ($current_tab == 'general') {
                        if (get_option('post_elo_enabled_post_types', [])) {
                            echo form_drop_db();
                            echo form_backup_db();
                        }
                    } else {
                        echo form_flush_post_type($current_post_type);
                        echo form_post_batch_size($current_post_type);
                        echo form_active_recommendations($current_post_type);
                        echo form_set_cron_schedule($current_post_type);
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
<?php
}
