<?php
// Check if the file is being accessed directly
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function form_flush_post_type($post_type)
{
?>
    <div class="postbox">
        <h2 class="hndle"><span>Flush All <?= ucfirst($post_type) ?> Records</span></h2>
        <div class="inside">
            <form method="post" action="">
                <?php wp_nonce_field('post_elo_flush_action', 'post_elo_flush_nonce_field'); ?>
                <input type="hidden" name="post_type" value="<?= esc_attr($post_type); ?>" />
                <input type="submit" name="flush_elo_table" class="button button-primary" value="Refresh" />
            </form>
        </div>
    </div>

<?php
}
