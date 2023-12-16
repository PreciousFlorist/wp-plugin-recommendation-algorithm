<?php
// Check if the file is being accessed directly
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function form_flush_all()
{
    ob_start();
?>

    <div class="postbox">
        <h2 class="hndle"><span>Flush ELO Data</span></h2>
        <div class="inside">
            <form method="post" action="">
                <?php wp_nonce_field('post_elo_flush_action', 'post_elo_flush_nonce_field'); ?>
                <input type="submit" name="flush_elo_table" class="button button-primary" value="Flush ELO Data" />
            </form>
        </div>
    </div>

<?php
    return ob_get_clean();
}
