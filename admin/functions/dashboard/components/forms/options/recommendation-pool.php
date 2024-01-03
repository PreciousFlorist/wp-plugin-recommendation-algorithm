<?php
// Check if the file is being accessed directly
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function form_active_recommendations($post_type)
{

?>

    <div class="postbox">
        <h2 class="hndle"><span>Active Recommendation Pool</span></h2>

        <div class="inside">
            <form method="post" action="">
                <?php
                wp_nonce_field('post_elo_update_recommendation_pool', 'post_elo_update_recommendation_pool_nonce');
                require_once plugin_dir_path(dirname(__FILE__, 1)) . 'partials/inputs/recommendation-pool.php';
                ?>
                <input type="hidden" name="post_type" value="<?= esc_attr($post_type); ?>" />
                <input type="submit" name="update_recommendation_pool" class="button button-secondary" value="Update" />
            </form>
        </div>
    </div>

<?php
}
