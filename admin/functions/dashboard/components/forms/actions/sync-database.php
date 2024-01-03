<?php
// Check if the file is being accessed directly
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function form_sync_database($post_type)
{
?>
    <form method="post" action="">
        <?php wp_nonce_field('post_elo_sync_action', 'post_elo_sync_nonce_field'); ?>
        <input type="hidden" name="post_type" value="<?= esc_attr($post_type); ?>" />
        <input type="submit" name="sync_database" class="button button-secondary" value="Backup Now" />
    </form>

<?php
}
