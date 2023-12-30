<?php
// Check if the file is being accessed directly
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function form_set_cron_schedule($post_type)
{

?>

    <div class="postbox">
        <h2 class="hndle"><span>Set Cron Schedule</span></h2>

        <div class="inside">
            <form method="post" action="">
                <?php
                wp_nonce_field('post_elo_update_cron_action', 'post_elo_update_cron_nonce');
                require_once plugin_dir_path(__FILE__) . 'partials/input-cron-schedule.php';
                ?>
                <input type="hidden" name="post_type" value="<?= esc_attr($post_type); ?>" />
                <input type="submit" name="update_cron" class="button button-secondary" value="Update" />
            </form>
        </div>
    </div>

<?php
}
