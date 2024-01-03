
<?php
// Check if the file is being accessed directly
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function form_post_batch_size($post_type)
{
?>

    <div class="postbox">
        <h2 class="hndle"><span>Batch Size</span></h2>

        <div class="inside">
            <form method="post" action="">
                <?php

                wp_nonce_field('post_elo_update_post_batch_size', 'post_elo_update_post_batch_size_nonce');
                require plugin_dir_path(dirname(__FILE__, 1)) . 'partials/inputs/batch-size.php';

                ?>

                <input type="hidden" name="post_type" value="<?= esc_attr($post_type); ?>" />
                <input type="submit" name="update_post_batch_size" class="button button-secondary" value="Update" />
            </form>
        </div>
    </div>

<?php
}
