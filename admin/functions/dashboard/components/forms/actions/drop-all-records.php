<?php
// Check if the file is being accessed directly
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function form_drop_db()
{
?>
    <div class="postbox">

        <h2 class="hndle"><span>Drop Database</span></h2>
        <div class="inside">
            <form method="post" action="">
                <?php wp_nonce_field('drop_db_action', 'drop_db_nonce_field'); ?>
                <input type="submit" name="drop_db_tables" class="button button-primary" value="Delete All Data" />
            </form>
        </div>
    </div>

<?php
}
