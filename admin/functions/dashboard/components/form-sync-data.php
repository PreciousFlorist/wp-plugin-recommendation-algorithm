<?php
// Check if the file is being accessed directly
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function form_sync_all()
{
    ob_start();
?>

    <div class="postbox">
        <h2 class="hndle"><span>Sync Database</span></h2>
        <div class="inside">
            <form method="post" action="">
                <?php wp_nonce_field('post_elo_sync_action', 'post_elo_sync_nonce_field'); ?>
                <input type="submit" name="sync_database" class="button button-secondary" value="Sync Database" />
            </form>
        </div>
    </div>

<?php
    return ob_get_clean();
}
