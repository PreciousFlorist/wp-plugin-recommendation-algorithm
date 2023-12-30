<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function register_approved_post_types()
{


?>
    <div class="postbox" style="background-color: #f6f7f7;">
        <div class="postbox-header">
            <h2 class="hndle ui-sortable-handle" style="background-color: #fff;"><span>Enable Post Types</span></h2>

        </div>
        <div class="inside">

            <?php
            $post_types = get_post_types(['public' => true], 'names');
            foreach ($post_types as $post_type) {
                $is_enabled = get_option('elo_enabled_cpts_' . $post_type, false);
            ?>
                <form method="post" action="" style="border: 1px solid #c3c4c7; padding: 0px 10px; margin-top: 12px; background-color: #fff;">
                    <p>
                        <label>
                            <input type="checkbox" name="elo_enabled_cpts_<?php echo esc_attr($post_type); ?>" <?php checked($is_enabled, true); ?>>
                            <?php echo esc_html(ucfirst($post_type)); ?>
                        </label>
                    </p>

                    <?php
                    require plugin_dir_path(__FILE__) . 'partials/input-batch-size.php';
                    require plugin_dir_path(__FILE__) . 'partials/input-recommendation-pool.php';
                    require plugin_dir_path(__FILE__) . 'partials/input-cron-schedule.php';
                    ?>

                    <?php wp_nonce_field('post_elo_post_type_settings_save_' . $post_type, 'post_elo_post_type_settings_nonce_' . $post_type); ?>
                    <p>

                        <input class="button button-primary" type="submit" name="update_post_type_<?php echo esc_attr($post_type); ?>" value="Update <?php echo esc_html(ucfirst($post_type)); ?>" />

                    </p>
                </form>
            <?php
            }
            ?>
        </div>

    </div>

<?php
}
