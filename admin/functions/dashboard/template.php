<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Page Config
require_once plugin_dir_path(__FILE__) . '/components/layout-tabs.php';

// Plugin Configuration
require_once plugin_dir_path(__FILE__) . '/components/form-register-post-types.php';
require_once plugin_dir_path(__FILE__) . '/components/form-drop-database.php';
require_once plugin_dir_path(__FILE__) . '/components/form-backup-database.php';

// Individual Post Components
require_once plugin_dir_path(__FILE__) . '/components/form-flush-data.php';
require_once plugin_dir_path(__FILE__) . '/components/form-cron-schedule.php';
require_once plugin_dir_path(__FILE__) . '/components/form-recommendation-pool.php';
require_once plugin_dir_path(__FILE__) . '/components/form-post-batch-size.php';
require_once plugin_dir_path(__FILE__) . '/components/table-cron-logs.php';
require_once plugin_dir_path(__FILE__) . '/components/table-probabilities.php';

function post_elo_settings_page()
{
    // Active tab
    $current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
    // Retrieve enabled post types
    $current_post_type = $current_tab !== 'general' ? $current_tab : null;
    $notice = admin_form_submission();

    error_log(print_r(get_option('post_elo_enabled_post_types', []), true));
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
