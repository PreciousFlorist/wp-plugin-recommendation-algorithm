<?php
// Check if the file is being accessed directly
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function form_post_batch_size($post_type)
{

    // Fetch the current limit from the options table or set a default value
    $current_limit = get_option('batch_size' . $post_type, 15);
?>

    <div class="postbox">
        <h2 class="hndle"><span>Batch Size</span></h2>

        <div class="inside">
            <form method="post" action="">
                <?php wp_nonce_field('post_elo_update_post_batch_size', 'post_elo_update_post_batch_size_nonce'); ?>
                <p>
                    <label for="post_batch_size">Specify how many contexts can be updated at a time when deploying updates to the databse.</label>
                    <input type="number" id="post_batch_size" style="margin-top: 13px; width: 100%;" name="post_batch_size" value="<?php echo esc_attr($current_limit); ?>" min="1" step="1" required>
                </p>

                <input type="hidden" name="post_type" value="<?= esc_attr($post_type); ?>" />
                <input type="submit" name="update_post_batch_size" class="button button-secondary" value="Update" />
            </form>
        </div>
    </div>

<?php
}
